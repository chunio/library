<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Throwable;

class TraceHandler
{

    const EVENT = [
        'TRACE' => 'trace',
        'SERVICE' => 'service',
    ];

    public static $ttl = 300;//unit:second

    public static $lastestReleaseTime = 0;

    public static $trace = [];

    public static function initRequest(): bool
    {
        $traceId = ContextHandler::pullTraceId();
        if(!(self::$trace[$traceId] ?? [])) {
            self::$trace[$traceId] = [
                'traceId' => $traceId,
                'trace' => [],
                'service' => [],
                'request' => ContextHandler::pullRequestAbstract(),
                'response' => [],
                'activeTime' => time(),
            ];
        }
        return true;
    }

    public static function push($variable, string $label = 'default', string $event = self::EVENT['TRACE']): bool
    {
        switch ($event){
            case self::EVENT['TRACE']:
                $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);//TODO：此函數性能如何？
                $file1 = ($startIndex = strrpos(($file1 = $traceInfo[2]['file']), env('APP_NAME'))) ? substr($file1, $startIndex + 1) : $file1;
                $index = microtime(true) . '#' . md5((string)rand());//TODO:並發時，需防止覆蓋同一指針下標
                self::$trace[ContextHandler::pullTraceId()][$event][$index] = [
                    'date' => date('Y-m-d H:i:s'),
                    "script" =>  "./{$file1}(line:{$traceInfo[2]['line']})",
                    'label' => $label,
                    'message' => prettyVariable($variable),
                ];
                break;
            case self::EVENT['SERVICE']:
                self::$trace[ContextHandler::pullTraceId()][$event][$label][] = $variable;
                break;
        }
        return true;
    }

    public static function pull(): array
    {
        return self::$trace[ContextHandler::pullTraceId()] ?? [];
    }

    /**
     * author : zengweitao@gmail.com
     * datetime: 2023/02/10 16:58
     * memo : null
     */
    public static function outputResponse(bool $jsonEncodeStatus = false): string
    {
        try {
            $traceArray = self::pull();
            if($jsonEncodeStatus) {
                $trace = prettyJsonEncode($traceArray) . "\n";
            }else{
                $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
            }
            if(matchEnvi('local')) echo $trace;
            return $trace;
        } catch (Throwable $e) {
            return prettyJsonEncode([
                'date' => date('Y-m-d H:i:s'),
                'traceId' => ContextHandler::pullTraceId(),
                'script' => $e->getFile() . "(line:{$e->getLine()})",
                'label' => __FUNCTION__ . "throwable",
                'message' => $e->getMessage(),
                'request' => ContextHandler::pullRequestAbstract(),
                'customTrace' => [],
            ]);
        }
    }

    //TODO:將自動清理添加至定時器
    public static function release(): bool
    {
        $currentTime = time();
        if($currentTime - self::$lastestReleaseTime > self::$ttl){
            foreach (self::$trace as $traceId => $value){
                if((time() - $value['activeTime']) > self::$ttl){
                    unset(self::$trace[$traceId]);
                }
            }
            self::$lastestReleaseTime = $currentTime;
        }
        return true;
    }

    public static function refresh(): bool
    {
        self::$trace[ContextHandler::pullTraceId()]['activeTime'] = time();
        return true;
    }

}
