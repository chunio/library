<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Monolog;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * Class MonologHandler
 * @package Baichuan\Library\Component\Monolog
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 12:05
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

    public static function __callStatic($function, $argument)
    {
        [$message, $label, $context, $name, $group] = $argument + ['', '', [], '', 'default'];
        $logger = static::instance($name, $group);
        $logger->{$function}(commonFormatVariable(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2), $message, $label), $context);
    }

    public static function instance(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

}
