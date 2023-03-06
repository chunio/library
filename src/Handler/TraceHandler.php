<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Baichuan\Library\Constant\RedisKeyEnum;
use Co\Redis;
use Hyperf\Utils\Str;

class TraceHandler
{

    const EVENT = [
        'TRACE' => 'trace',
        'SERVICE' => 'service',
    ];

//    public static $jsonEncodeStatus = false;//是否單行
//
//    public static $output = true;//是否輸出至控制台

    public static $ttl = 300;//unit:second

    public static $lastestReleaseTime = 0;

    public static $trace = [];

    /**
     * @return bool
     * author : zengweitao@gmail.com
     * datetime: 2023/03/02 18:22
     * memo : request
     */
    public static function init(): bool
    {
        //TODO:存在其他非請求入口
        $traceId = ContextHandler::pullTraceId();
        if(!(self::$trace[$traceId] ?? [])) {
            self::$trace[$traceId] = [//template
                'traceId' => $traceId,
                'request' => ContextHandler::pullRequestAbstract(),
                'trace' => [],
                'service' => [],
                //'response' => [],
                'activeTime' => time(),
            ];
        }
        return true;
    }

    public static function ApiElapsedTimeRank(float $elapsedTime): bool
    {
        $requestAbstract = ContextHandler::pullRequestAbstract();////
        $replaceApi = str_replace([':', 'http'], '', $requestAbstract['api']);
        $numRedisKey = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Num:'] . $replaceApi;
        $secondRedisKey = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Second:'] . $replaceApi;
        redisInstance()->incr($numRedisKey);
        redisInstance()->incrByFloat($secondRedisKey, $elapsedTime);
        return true;
    }

    public static function push($variable, string $label = 'default', string $event = self::EVENT['TRACE'], int $debugBacktraceLimit = 2): bool
    {
        switch ($event){
            case self::EVENT['TRACE']:
                $index = microtime(true) . '(' . Str::random(10) . ')';//TODO:並發時，需防止覆蓋同一指針下標
                self::$trace[ContextHandler::pullTraceId()][$event][$index] = traceFormatter($variable, $label, $debugBacktraceLimit, false);
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
     * memo : response
     */
    public static function output(/*string $response*/)
    {
//        try {
        $traceArray = self::pull();
        if($traceArray['trace'] || $traceArray['service']){
            //$responseArray = json_decode($responseJson, true);
            //$responseArray['data'] = 'hide';
            //$traceArray['response'] = $responseJson;
            if(MonologHandler::$jsonEncodeStatus) {
                $trace = prettyJsonEncode($traceArray) . "\n";
            }else{
                $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
            }
            if(MonologHandler::$output) echo $trace;
            MonologHandler::info($trace,'', [], MonologHandler::$formatter['NONE']);
        }
//        } catch (Throwable $e) {
//            $trace = prettyJsonEncode([
//                'date' => date('Y-m-d H:i:s'),
//                'traceId' => ContextHandler::pullTraceId(),
//                'script' => $e->getFile() . "(line:{$e->getLine()})",
//                'label' => __FUNCTION__ . "throwable",
//                'message' => $e->getMessage(),
//                'request' => ContextHandler::pullRequestAbstract(),
//                'customTrace' => [],
//            ]);
//        }
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
