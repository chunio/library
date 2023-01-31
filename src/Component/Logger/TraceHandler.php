<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Hyperf\Context\Context;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Class Log
 * @package Baichuan\Library\Component\Logger
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 12:05
 * memo : null
 * @method static info(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static debug(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static notice(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static alert(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static warning(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static error(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static emergency(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 * @method static critical(mixed $message, string $label = '', array $context = [], $name = '', $group = 'default');
 */
class TraceHandler
{

    public static function __callStatic($function, $argument)
    {
        [$message, $label, $context, $name, $group] = $argument + ['', '', [], '', 'default'];
        $logger = static::get($name, $group);
        $logger->{$function}(self::customFormat($message, $label), $context);
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

    public static function customFormat($variable, string $label = ''): string
    {
        try {
            //請求信息[START]
            if($RequestClass = Context::get(ServerRequestInterface::class)){
                $body = prettyJsonEncode($RequestClass->getParsedBody());
                $request =  [
                    'api' => "[" . $RequestClass->getMethod() . "]" . $RequestClass->getUri()->__toString(),
                    'header' => $RequestClass->getHeaders(),
                    'query' => prettyJsonEncode($RequestClass->getQueryParams()),
                    'body' => $body,
                ];
                unset($RequestClass);
            }
            //請求信息[END]
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
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
            $log = [
                'label' => $label ?: 'default',
                'date' => date('Y-m-d H:i:s'),
                'path' => "./{$scriptName}(line:{$line})",
                'traceId' => self::currentTraceId(),
                'request' => $request ?? [],
                'message' => $variable,
            ];
            if(matchEnvi('local')){
                //input layout，start-----
                $content = @print_r($log, true);
                //TODO:變量大小限制
                //##################################################
                $template = "\n:<<UNIT[START]\n";
                //$template .= "/**********\n";
                //$template .= " * date : " . date('Y-m-d H:i:s') . "\n";
                //$template .= " * path : {$scriptName}(line:{$line})\n";
                //$template .= " * traceId : " . self::currentTraceId() . "\n";
                //$template .= "/**********\n";
                $template .= "{$content}\n";
                $template .= "UNIT[END]\n";
                //input layout，end-----
            }else{
                if(is_object($variable)){
                    $log['message'] = (array)$variable;
                }
                $template = commonJsonEncode($log) . "\n";
            }
        } catch (\Throwable $e) {
            xdebug($e);
        }
        return $template ?? '';
    }

}
