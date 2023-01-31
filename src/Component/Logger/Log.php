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
 * @method static info(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static debug(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static notice(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static alert(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static warning(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static error(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static emergency(string|Stringable $message, array $context = [], $name = '', $group = 'default');
 * @method static critical(string|Stringable $message, array $context = [], $name = '', $group = 'default');
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
        $logger->{$function}($message, [self::customNormalize($context)]);
    }

    public static function currentTraceId(): string
    {
        if (!($traceId = Context::get('traceId'))) {
            $traceId = Str::random(64);
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
            if ($traceInfo[0]) {//last track
                $file = $traceInfo[0]['file'];
                $line = $traceInfo[0]['line'];
                $startIndex = strrpos($file, DIRECTORY_SEPARATOR);
                $scriptName = substr($file, $startIndex + 1);
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
                $variable = "(empty string)";
            } elseif ($variable instanceof Throwable) {
                $variable = [
                    'message' => $variable->getMessage(),
                    'file' => $variable->getFile() . "(line:{$variable->getLine()})",
                ];
                $title .= "Throwable";
            }
            //special type conversion，end-----
            $content = @print_r($variable, true);
            //##################################################
            //input layout，start-----
            $template = "{\n\n}//" . date('Y-m-d H:i:s') . ",start-----\n";
            $template .= "//{$title}(" . $_SERVER['DOCUMENT_ROOT'] . ">>{$scriptName}/line:{$line})\n";
            $template .= "{$content}\n";
            $template .= "//end-----";
            //input layout，end-----
            return $template;
        } catch (\Throwable $e) {
            //TODO:none...
        }
    }

}
