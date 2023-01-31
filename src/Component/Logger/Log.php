<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Hyperf\Context\Context;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class Log
 * @package Baichuan\Library\Component\Logger
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 12:05
 * memo : null
 * @method static info(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static debug(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static notice(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static alert(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static warning(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static error(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static emergency(mixed $message, array $context = [], $name = '', $group = 'default');
 * @method static critical(mixed $message, array $context = [], $name = '', $group = 'default');
 */
class Log
{

    public static function __callStatic($function, $argument)
    {
        [$message, $context, $name, $group] = $argument + ['', [], '', 'default'];
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
        $logger = static::get($name, $group);
        $logger->{$function}(self::customNormalize($message), $context);
    }

    public static function currentTraceId(): string
    {
        if (!($traceId = Context::get('traceId'))) {
            $traceId = Str::random(32);
            Context::set('traceId', $traceId);
        }
        return $traceId;
    }

    public static function get(string $name = '', string $group = 'default'): LoggerInterface
    {
        if (!$name) {
            $name = config('app_name');
        }
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get($name, $group);
    }

    public static function customNormalize($variable, string $title = 'defaultTitle'): string
    {
        $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        try {
            $scriptName = $line = '';
            if ($traceInfo[1]) {//last track
                $file = $traceInfo[1]['file'];
                $line = $traceInfo[1]['line'];
                $scriptName = ($startIndex = strrpos($file, env('APP_NAME'))) ? substr($file, $startIndex + 1) : $file;
            }
            //end-----
            //special type conversion，start-----
            if (true === $variable) {
                $variable = 'TRUE(BOOL)';
            } elseif (false === $variable) {
                $variable = 'FALSE(BOOL)';
            } elseif (null === $variable) {
                $variable = 'NULL';
            } elseif ('' === $variable) {
                $variable = "(EMPTY STRING)";
            } elseif ($variable instanceof Throwable) {
                $variable = [
                    'file' => $variable->getFile() . "(line:{$variable->getLine()})",
                    'message' => $variable->getMessage(),
                    'trace' => $variable->getTrace()
                ];
            }
            //special type conversion，end-----
            $content = @print_r($variable, true);
            //TODO:變量大小限制
            //##################################################
            //input layout，start-----
            $template = "\n:<<UNIT[START]\n";
            $template .= "/**********\n";
            $template .= " * date : " . date('Y-m-d H:i:s') . "\n";
            $template .= " * path : {$scriptName}(line:{$line})\n";
            $template .= " * traceId : " . self::currentTraceId() . "\n";
            $template .= "/**********\n";
            $template .= "{$content}\n";
            $template .= "UNIT[END]\n";
            //input layout，end-----
            return $template;
        } catch (\Throwable $e) {
            //TODO:none...
        }
    }

}
