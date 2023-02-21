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
     * @param float $elapsedTime
     * @return bool
     * author : zengweitao@msn.com
     * datetime : 2022-04-25 15:10
     * memo : //TODO:待優化
     */
    public static function pushSqlTrace(string $sql, float $elapsedTime): bool
    {
        self::refresh();
        self::$trace[ContextHandler::pullTraceId()]['sql'][/*TODO:並發時，需防止覆蓋同一指針下標*/] = [//TODO:防止內存洩漏
            'sql' => $sql,//sql
            'unitElapsedTime' => sprintf("%0.10f", ($elapsedTime / 1000))//單位：秒
        ];
        return true;
    }

    public static function pullSqlTrace(): array
    {
        self::refresh();
        return self::$trace[ContextHandler::pullTraceId()]['sql'];
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
