<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class MonologHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/20 16:13
 * memo : 基於 psr/logger 實現（即：規範），使用 monolog/monolog 作爲驅動
 * @method static info($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static debug($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static notice($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static alert($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static warning($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static error($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static emergency($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 * @method static critical($message, string $label = '', array $context = [], string $formatter = '', string $name = '', string $group = 'default');
 */
class MonologHandler
{

    //註冊#樣式方式#
    public static $formatter = [
        'NONE' => null,
        'COMMON' => 'traceFormatter',//function name
    ];

    public static $jsonEncodeStatus = false;//是否單行

    public static $output = true;//是否輸出至控制台

    public static function __callStatic($function, $argument)
    {
        [$message, $label, $context, $formatFunc, $name, $group] = $argument + ['', '', [], self::$formatter['COMMON'], '', 'default'];
        $logger = static::instance($name, $group);
        $message = $formatFunc ? self::$formatFunc($message, $label) : $message;
        $logger->{$function}($message, $context);
    }

    public static function instance(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

//    public static function commonFormatter($variable, string $label = 'default', int $debugBacktraceLimit = 2): string
//    {
//        try {
//            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $debugBacktraceLimit);//TODO：此函數性能如何？
//            $file1 = ($startIndex = strrpos(($file1 = $traceInfo[2]['file']), env('APP_NAME'))) ? substr($file1, $startIndex + 1) : $file1;
//            $traceArray = [
//                'traceId' => ContextHandler::pullTraceId(),
//                'date' => date('Y-m-d H:i:s'),
//                "script" =>  "./{$file1}(line:{$traceInfo[2]['line']})",
//                'label' => $label,
//                'message' => variableFormatter($variable),
//            ];
//            //check memory[START]
////            $traceJson = prettyJsonEncode($traceArray);
////            if(strlen($traceJson) > (($megabyteLimit = 1024/*unit:KB*/) * 1024)){//超出限額則截取
////                $jsonEncodeStatus = true;
////                $traceJson = substr($traceJson, 0,$megabyteLimit * 1024);
////            }
//            //check memory[END]
//            if(self::$jsonEncodeStatus) {
//                $trace = prettyJsonEncode($traceArray) . "\n";
//            }else{
//                $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
//            }
//            if(self::$output) echo $trace;
//            return $trace;
//        } catch (Throwable $e) {
//            return prettyJsonEncode([
//                'traceId' => ContextHandler::pullTraceId(),
//                'date' => date('Y-m-d H:i:s'),
//                'script' => $e->getFile() . "(line:{$e->getLine()})",
//                'label' => "{$label} throwable",
//                'message' => $e->getMessage(),
//            ]);
//        }
//    }

}
