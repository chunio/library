<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Baichuan\Library\Constant\ContextEnum;
use Baichuan\Library\Constant\RedisKeyEnum;
use Hyperf\Context\Context;
use Hyperf\Utils\Str;
use Throwable;

class TraceHandler
{

    const EVENT = [
        'TRACE' => 'trace',
        'SERVICE' => 'service',
    ];

//    public static $jsonEncodeStatus = false;//是否單行
//
//    public static $output = true;//是否輸出至控制台

    public static $ttl = 15;//unit:second//TODO：監控：時間/數量

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
        if(matchEnvi('local')){
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
        }
        self::release();
        return true;
    }

    public static function ApiElapsedTimeRank(): bool
    {
        try{
            $requestAbstract = ContextHandler::pullRequestAbstract();////
            $replaceApi = str_replace([':', 'http'], '', $requestAbstract['api']);
            $numRedisKey = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Num:'] . $replaceApi;
            $secondRedisKey = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Second:'] . $replaceApi;
            $elapsedTime = microtime(true) - Context::get(ContextEnum::RequestStartMicroTime);
            redisInstance()->incr($numRedisKey);
            redisInstance()->incrByFloat($secondRedisKey, $elapsedTime);
        }catch (\Throwable $e){}
        return true;
    }

    public static function push($variable, string $label = 'default', string $event = self::EVENT['TRACE'], int $debugBacktraceLimit = 2): bool
    {
        if(matchEnvi('local')){
            switch ($event){
                case self::EVENT['TRACE']:
                    $index = microtime(true) . '(' . Str::random(10) . ')';//TODO:並發時，需防止覆蓋同一指針下標
                    self::$trace[ContextHandler::pullTraceId()][$event][$index] = self::traceFormatter($variable, $label, $debugBacktraceLimit, false);
                    break;
                case self::EVENT['SERVICE']:
                    self::$trace[ContextHandler::pullTraceId()][$event][$label][] = $variable;
                    break;
            }
        }
        self::refresh();
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
        //if($traceArray['trace'] || $traceArray['service']){
            //$responseArray = json_decode($responseJson, true);
            //$responseArray['data'] = 'hide';
            //$traceArray['response'] = $responseJson;
            if(MonologHandler::$jsonEncodeStatus) {
                $trace = UtilityHandler::prettyJsonEncode($traceArray) . "\n";
            }else{
                $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
            }
            if(MonologHandler::$output) echo $trace;
            MonologHandler::info($trace,'', [], MonologHandler::$formatter['NONE']);
        //}
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
                if(($currentTime - $value['activeTime']) > self::$ttl){
                    unset(self::$trace[$traceId]);
                }
            }
        }
        return true;
    }

    public static function refresh(): bool
    {
        $currentTime = time();
        self::$trace[ContextHandler::pullTraceId()]['activeTime'] = $currentTime;
        self::$lastestReleaseTime = $currentTime;
        return true;
    }

    public static function sendAlarm2DingTalk($variable)
    {
        $timestamp = time() * 1000;
        $accessToken = 'b76e1cf33a222a8ddee2fde1c930be03cdc1f04a31d1a1036be9803a6f712319';
        $secret = 'SEC8e6642f7e93939b4e04edefc7e06248d8b8c8120c8ff439879fc1ad5970ff601';
        $content = '';
        $content .= "[" . env('APP_NAME') . ' / ' . env('APP_ENV') . "]";
        $content .=  str_replace("\"","'", self::commonFormatVariable($variable, '', debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)));
        $content = UtilityHandler::prettyJsonEncode([
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ]);
        $parameter = [
            'access_token' => $accessToken,
            'timestamp' => $timestamp,
            'sign' => urlencode(base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true)))
        ];
        $webhook = "https://oapi.dingtalk.com/robot/send?" . http_build_query($parameter);
        $option = [
            'http' => [
                'method' => "POST",
                'header' => "Content-type:application/json;charset=utf-8",//
                'content' => $content
            ],
            "ssl" => [ //不驗證ssl證書
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ];
        return file_get_contents($webhook, false, stream_context_create($option));
    }

    public static function commonFormatVariable($variable, string $label = '', array $traceInfo = [], bool $jsonEncodeStatus = false): string
    {
        try {
            $traceInfo = $traceInfo ?: debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);//TODO：此函數性能如何？
            $file1 = ($startIndex = strrpos(($file1 = $traceInfo[1]['file']), env('APP_NAME'))) ? substr($file1, $startIndex + 1) : $file1;
            $funcFormat = function($variable, $jsonEncodeStatus){
                if ($variable === true) return 'TRUE(BOOL)';
                if ($variable === false) return 'FALSE(BOOL)';
                if ($variable === null) return 'NULL';
                if ($variable === '') return "(EMPTY STRING)";
                if ($variable instanceof Throwable) return ['message' => $variable->getMessage(), 'trace' => $variable->getTrace()];
                if(is_object($variable) && $jsonEncodeStatus) return (array)$variable;
                return $variable;
            };
            $traceArray = [
                'date' => date('Y-m-d H:i:s'),
                'traceId' => ContextHandler::pullTraceId(),
                "debugBacktrace" =>  "./{$file1}(line:{$traceInfo[1]['line']})",
                'label' => $label ?: 'default',
                'message' => $funcFormat($variable, $jsonEncodeStatus),
                'request' => ContextHandler::pullRequestAbstract(),
            ];
            //check memory[START]
            $traceJson = UtilityHandler::prettyJsonEncode($traceArray);
            if(strlen($traceJson) > (($megabyteLimit = 1024/*unit:KB*/) * 1024)){//超出限額則截取
                $jsonEncodeStatus = true;
                $traceJson = substr($traceJson, 0,$megabyteLimit * 1024);
            }
            //check memory[END]
            if($jsonEncodeStatus) {
                $trace = "{$traceJson}\n";
            }else{
                $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
            }
            if(UtilityHandler::matchEnvi('local')) echo $trace;
            return $trace;
        } catch (Throwable $e) {
            return UtilityHandler::prettyJsonEncode([
                'date' => date('Y-m-d H:i:s'),
                'traceId' => ContextHandler::pullTraceId(),
                'debugBacktrace' => $e->getFile() . "(line:{$e->getLine()})",
                'label' => "{$label} throwable",
                'message' => $e->getMessage(),
                'request' => ContextHandler::pullRequestAbstract(),
            ]);
        }
    }

    public static function variableFormatter(&$variable/*, bool $jsonEncodeStatus = false*/)
    {
        if ($variable === true) return 'TRUE(BOOL)';
        if ($variable === false) return 'FALSE(BOOL)';
        if ($variable === null) return 'NULL';
        if ($variable === '') return "(EMPTY STRING)";
        if ($variable instanceof Throwable) return ['message' => $variable->getMessage(), 'trace' => $variable->getTrace()];
        if(is_object($variable)/* && $jsonEncodeStatus*/) return (array)$variable;
        //解決json_encode()錯誤：Malformed UTF-8 characters, possibly incorrectly encoded
        //if(is_string($variable)) return mb_convert_encoding($variable, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
        return $variable;
    }

    public static function traceFormatter(&$variable, string $label = 'default', int $debugBacktraceLimit = 2, bool $separator = true)/*: string|array*/
    {
        try {
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $debugBacktraceLimit);//TODO：此函數性能如何？
            $file1 = ($startIndex = strrpos(($file1 = $traceInfo[$debugBacktraceLimit - 1]['file']), env('APP_NAME'))) ? substr($file1, $startIndex + 1) : $file1;
            $traceArray = [
                'date' => date('Y-m-d H:i:s'),
                "script" =>  "./{$file1}(line:{$traceInfo[$debugBacktraceLimit - 1]['line']})",
                'label' => $label,
                'message' => self::variableFormatter($variable),
            ];
            //check memory[START]
//            $traceJson = prettyJsonEncode($traceArray);
//            if(strlen($traceJson) > (($megabyteLimit = 1024/*unit:KB*/) * 1024)){//超出限額則截取
//                $jsonEncodeStatus = true;
//                $traceJson = substr($traceJson, 0,$megabyteLimit * 1024);
//            }
            //check memory[END]
            if($separator){
                $traceArray['traceId'] = ContextHandler::pullTraceId();
                if(MonologHandler::$output ?? false) {
                    $trace = UtilityHandler::prettyJsonEncode($traceArray) . "\n";
                }else{
                    $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
                }
                if(MonologHandler::$output ?? true) echo $trace;

            }
            return $trace ?? $traceArray;
        } catch (Throwable $e) {
            return UtilityHandler::prettyJsonEncode([
                'traceId' => ContextHandler::pullTraceId(),
                'date' => date('Y-m-d H:i:s'),
                'script' => $e->getFile() . "(line:{$e->getLine()})",
                'label' => "{$label} throwable",
                'message' => $e->getMessage(),
            ]);
        }
    }

}
