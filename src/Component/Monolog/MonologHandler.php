<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Monolog;

use Baichuan\Library\Utility\ContextHandler;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class MonologHandler
 * @package Baichuan\Library\Component\Monolog
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 12:05
 * memo : 基於 psr/logger 實現（即：規範），使用 monolog/monolog 作爲驅動
 * @method static info(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static debug(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static notice(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static alert(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static warning(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static error(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static emergency(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 * @method static critical(mixed $message, string $label = '', array $context = [], string $name = '', string $group = 'default');
 */
class MonologHandler
{

    public static function __callStatic($function, $argument)
    {
        [$message, $label, $context, $name, $group] = $argument + ['', '', [], '', 'default'];
        $logger = static::instance($name, $group);
        $logger->{$function}(self::formatMessage($message, $label), $context);
    }

    public static function instance(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    public static function formatMessage(&$variable, string $label = '', bool $jsonEncodeStatus = false, bool $stdout = true): string
    {
        return formatTraceVariable($variable, $label, $jsonEncodeStatus);
    }

}
