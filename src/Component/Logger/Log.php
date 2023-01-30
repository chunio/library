<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Hyperf\Context\Context;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Psr\Log\LoggerInterface;

/**
 * Class Log
 * @package Baichuan\Library\Component\Logger
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 12:05
 * memo : null
 * @method static emergency(string $message, $context = [], $name = '', $group = 'default');
 * @method static alert(string $message, $context = [], $name = '', $group = 'default');
 * @method static critical(string $message, $context = [], $name = '', $group = 'default');
 * @method static error(string $message, $context = [], $name = '', $group = 'default');
 * @method static warning(string $message, $context = [], $name = '', $group = 'default');
 * @method static notice(string $message, $context = [], $name = '', $group = 'default');
 * @method static info(string $message, $context = [], $name = '', $group = 'default');
 * @method static debug(string $message, $context = [], $name = '', $group = 'default');
 */
class Log
{

    public static function __callStatic($name, $arguments)
    {
        [$message, $context, $log_name, $log_group] = $arguments + ['', [], '', 'default'];
        if ($context instanceof \Throwable) {
            if ($context instanceof QueryException) {
                $details = [
                    'SQL' => $context->getSql(),
                ];
            }
            /*****
            elseif ($context instanceof AbstractException) {
            $details = $context->getExtra();
            }
             *****/
            $context = [
                'error' => get_class($context),
                'error_code' => $context->getCode(),
                'error_message' => $context->getMessage(),
                'error_file' => $context->getFile(),
                'error_line' => $context->getLine(),
                'trace' => $context->getTraceAsString(),
            ];

            if (!empty($details)) {
                $context['details'] = $details;
            }
        }
        $logger = static::get($log_name, $log_group);
        $logger->{$name}($message, $context);
    }

    public static function pullRequestId(): string
    {
        if (!($requestId = Context::get('requestId'))) {
            $requestId = "requestId#" . Str::random(32);
            Context::set('requestId', $requestId);
        }
        return $requestId;
    }

    public static function get(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

}
