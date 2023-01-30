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

    public function customFormat($variable, string $title = 'defaultTitle', string $path = '', bool $append = true, bool $output = true): void
    {
        $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        try {
            $path = $path ?: BASE_PATH . "/runtime/logs/xdebug-0000-00-" . date("d") . ".log";//keep it for one month
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
            if (!file_exists($path)) {//compatible file_put_contents() cannot be created automatically
                @touch($path);
            }
            $space = @count(file($path)) ? "\n\n\n" : ''; //interval control(3-1/line)
            //input layout，start-----
            $template = "{$space}//" . date('Y-m-d H:i:s') . ",start-----\n";
            $template .= "//{$title}(" . $_SERVER['DOCUMENT_ROOT'] . ">>{$scriptName}/line:{$line})\n";
            $template .= "{$content}\n";
            $template .= "//end-----";
            //input layout，end-----
            if (!$append || (abs(filesize($path)) > 1024 * 1024 * 1024)) {//flush beyond the limit/1024m
                @file_put_contents($path, $template/*, LOCK_EX*/); //TODO:阻塞風險
            } else {
                @file_put_contents($path, $template, FILE_APPEND/* | LOCK_EX*/);
            }
            if($output) echo "$template\n";//DEBUG_LABEL
        } catch (\Throwable $e) {
            //TODO:none...
        }
        //});
    }

}
