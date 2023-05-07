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

    public static $jsonEncodeStatus = false;//是否單行

    public static $output = true;//是否輸出至控制台

    //註冊#樣式方式#
    public static $formatter = [
        'NONE' => null,
        'COMMON' => 'traceFormatter',//function name
    ];

    public static function __callStatic($function, $argument)
    {
        [$message, $label, $context, $formatFunc, $name, $group] = $argument + ['', '', [], self::$formatter['COMMON'], '', 'default'];
        $logger = static::instance($name, $group);
        $message = $formatFunc ? TraceHandler::$formatFunc($message, $label) : $message;
        $logger->{$function}($message, $context);
    }

    public static function instance(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

}
