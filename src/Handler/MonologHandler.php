<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * Class MonologHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/20 16:13
 * memo : 基於 psr/logger 實現（即：規範），使用 monolog/monolog 作爲驅動
 * @method static info($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static debug($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static notice($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static alert($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static warning($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static error($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static emergency($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static critical($message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 */
class MonologHandler
{

    public static $TRACE_EVENT = [
        'MYSQL' => 'MYSQL',
        'MONGODB' => 'MONGODB',
    ];

    public static $trace = [];

    public static $ttl = 300;//unit:second

    public static function __callStatic($function, $argument)
    {
        [$message, $label, $context, $name, $group] = $argument + ['', '', [], '', 'default'];
        $logger = static::instance($name, $group);
        $logger->{$function}(commonFormatVariable($message, $label, debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)), $context);
    }

    public static function instance(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    /**
     * @param string $command
     * @param int $unitElapsedTime //單位：毫秒
     * @return bool
     * author : zengweitao@msn.com
     * datetime : 2022-04-25 15:10
     * memo : //TODO:待優化
     */
    public static function pushDBTrace(string $event, string $command, int $unitElapsedTime): bool
    {
        self::refresh();
        self::$trace[ContextHandler::pullTraceId()][$event][/*TODO:並發時，需防止覆蓋同一指針下標*/] = [//TODO:防止內存洩漏
            'command'/*如：sql*/ => $command,
            'unitElapsedTime' => floatval(number_format((string)($unitElapsedTime / 1000), 5,'.',''))//單位：秒
        ];
        return true;
    }

    /**
     * @param string $command
     * @param float $millisecond 單位：毫秒
     * @return bool
     * author : zengweitao@msn.com
     * datetime : 2022-04-25 15:10
     * memo : //TODO:待優化
     */
    public static function pushCustomTrace(string $event, string $command, float $millisecond): bool
    {
        self::refresh();
        self::$trace[ContextHandler::pullTraceId()][$event][/*TODO:並發時，需防止覆蓋同一指針下標*/] = [//TODO:防止內存洩漏
            'command'/*如：sql*/ => $command,
            'unitElapsedTime' => floatval(number_format((string)($millisecond / 1000), 5,'.',''))//單位：秒
        ];
        return true;
    }

    public static function pullCustomTrace(): array
    {
        self::refresh();
        return self::$trace[ContextHandler::pullTraceId()];
    }

    //TODO:將自動清理添加至定時器
    public static function release(): bool
    {
        foreach (self::$trace as $traceId => $value){
            if((time() - $value['activeTime']) > self::$ttl){
                unset(self::$trace[$traceId]);
            }
        }
        return true;
    }

    public static function refresh(): bool
    {
        self::$trace[ContextHandler::pullTraceId()]['activeTime'] = time();
        return true;
    }


}
