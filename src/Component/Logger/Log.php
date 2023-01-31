<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Hyperf\Context\Context;
use Hyperf\Database\Exception\QueryException;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Str;
use Monolog\Utils;
use Psr\Http\Message\ServerRequestInterface;
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

    public static function customNormalize($variable, string $label = ''): string
    {
        try {
            //請求信息[START]
            if($RequestClass = Context::get(ServerRequestInterface::class)){
                $body = prettyJsonEncode($RequestClass->getParsedBody());
                $body = self::trimByMaxLength('request', $body);
                $request =  [
                    'api' => "[" . $RequestClass->getMethod() . "]" . $RequestClass->getUri()->__toString(),
                    'header' => self::simplifyHeaders($RequestClass->getHeaders()),
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
            //TODO:none...
        }
        return $template ?? '';
    }

    private static function trimByMaxLength(string $type, ?string $content): string
    {
        if (!$content) {
            return '';
        }
        // 檢測長度
        if (config('log.request.max_len.' . $type, 0) > 0) {
            $len = config('log.request.max_len.' . $type);
            if (strlen($content) >= $len) {
                $content = mb_substr($content, 0, $len, 'utf-8') . '...';
            }
        }
        return $content;
    }

    private static function simplifyHeaders(array $headers)
    {
        return array_map(fn ($i) => 1 == count($i) ? $i[0] : $i, $headers);
    }

}
