<?php

declare(strict_types=1);

use Baichuan\Library\Component\Monolog\MonologHandler;
use Baichuan\Library\Constant\AnsiColorEnum;
use Baichuan\Library\Utility\ContextHandler;
use Hyperf\Redis\RedisFactory;

if (!function_exists('xdebug')) {
    /**
     * @param $variable
     * @param string $title
     * @param string $path
     * @param bool $append
     * @param bool $output
     * author : zengweitao@gmail.com
     * datetime: 2023/01/30 16:54
     * memo : null
     */
    function xdebug($variable, string $title = 'defaultTitle', string $path = '', bool $append = true, bool $output = true): void
    {
        $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        //co(function() use($variable, $title, $traceInfo){
        try {
            //$path = (env('APP_ENV') == 'localhost') ? "/windows/runtime/hyperf.log" : BASE_PATH . "/runtime/xdebug-0000-00-" . date("d") . ".log";//keep it for one month
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
        } catch (Throwable $e) {
            //TODO:none...
        }
        //});
    }
}

if (!function_exists('di')) {
    /**
     * @param null $id
     * @return mixed|\Psr\Container\ContainerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:51
     * memo : null
     */
    function di($id = null)
    {
        $container = Hyperf\Utils\ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }
        return $container;
    }
}

if (!function_exists('matchNonNullValue')) {
    /**
     * @param mixed $key 如key等於null，則取$parameter裡第一個非null值；如key不等於null，則取第一個$parameter($key)或$parameter[$key]的非null值
     * @param mixed ...$parameter closure || 數組
     * @return null|array|mixed
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:45
     * memo : 順序檢索第一个非null值
     */
    function matchNonNullValue(string $key, ...$parameter)
    {
        foreach ($parameter as $member) {
            if (is_null($member)) {
                continue;
            }
            if ($member instanceof Closure) {
                $value = $member($key);
            } else {
                $value = null == $key ? value($member) : data_get($member, $key);
            }
            if (!is_null($value)) {
                return $value;
            }
        }
        return null;
    }
}

if (!function_exists('matchEnvi')) {
    /**
     * author : zengweitao@gmail.com
     * datetime: 2023/01/30 11:08
     * memo : 環境匹配
     */
    function matchEnvi(string $envi): bool
    {
        return env('APP_ENV') == $envi;
    }
}

if (!function_exists('redisInstance')) {
    function redisInstance(string $poolName = 'default'): Hyperf\Redis\Redis
    {
        return di()->get(RedisFactory::class)->get($poolName);
    }
}

if (!function_exists('prettyJsonEncode')) {
    /**
     * author : zengweitao@gmail.com
     * datetime: 2023/01/30 15:10
     * memo : null
     */
    function prettyJsonEncode($object, ?int $flag = JSON_PRETTY_PRINT): string
    {
        $flagCounter = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
        if (!is_null($flag)) {
            $flagCounter |= $flag;
        }
        return json_encode($object, $flagCounter);
    }
}

if (!function_exists('commonJsonEncode')) {
    /**
     * author : zengweitao@gmail.com
     * datetime: 2023/01/30 15:10
     * memo : null
     */
    function commonJsonEncode($object, int $flag = 0): string
    {
        //JSON_PRETTY_PRINT//易讀格式（即：自動換行）
        $flagCounter = JSON_UNESCAPED_SLASHES/*不轉義反斜杠*/ | JSON_UNESCAPED_UNICODE/*unicode轉至中文*/;
        if (!$flag) {
            $flagCounter |= $flag;
        }
        return json_encode($object, $flagCounter);
    }
}

if (!function_exists('colorString')) {
    function colorString(string $string, int $fg = AnsiColorEnum::FG_BLACK, int $bg = null): string
    {
        if ($bg) {
            return "\033[{$bg};{$fg}m{$string}\033[0m";
        }
        return "\033[{$fg}m{$string}\033[0m";
    }
}

if(!function_exists('sendAlarm2DingTalk')){
    function sendAlarm2DingTalk(&$variable)
    {
        $timestamp = time() * 1000;
        $accessToken = 'b76e1cf33a222a8ddee2fde1c930be03cdc1f04a31d1a1036be9803a6f712319';
        $secret = 'SEC8e6642f7e93939b4e04edefc7e06248d8b8c8120c8ff439879fc1ad5970ff601';
        $content = '';
        $content .= "[" . env('APP_NAME') . ' / ' . env('APP_ENV') . "]";
        $content .=  str_replace("\"","'", formatTraceVariable($variable));
        $content = commonJsonEncode([
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ]);
        $parameter = [
            'access_token' => $accessToken,
            'timestamp' => $timestamp,
            'sign' => urlencode(base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true)))
        ];
        $webhook = "https://oapi.dingtalk.com/robot/send?" . http_build_query($parameter);
        $option = [
            'http' => [
                'method' => "POST",
                'header' => "Content-type:application/json;charset=utf-8",//
                'content' => $content
            ],
            "ssl" => [ //不驗證ssl證書
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ];
        MonologHandler::info('xxxx');
        return file_get_contents($webhook, false, stream_context_create($option));
    }
}

if(!function_exists('formatTraceVariable')){
    /**
     * @param $variable
     * @param string $label
     * @param bool $jsonEncodeStatus
     * @return string
     * author : zengweitao@gmail.com
     * datetime: 2023/02/10 16:58
     * memo : null
     */
    function formatTraceVariable(&$variable, string $label = '', bool $jsonEncodeStatus = false): string
    {
        try {
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);//TODO：此函數性能如何？
            $file1 = ($startIndex = strrpos(($file1 = $traceInfo[1]['file']), env('APP_NAME'))) ? substr($file1, $startIndex + 1) : $file1;
            //$file2 = ($startIndex = strrpos(($file2 = $traceInfo[2]['file']), env('APP_NAME'))) ? substr($file2, $startIndex + 1) : $file2;
            $funcFormat = function(&$variable, $jsonEncodeStatus){
                if ($variable === true) return 'TRUE(BOOL)';
                if ($variable === false) return 'FALSE(BOOL)';
                if ($variable === null) return 'NULL';
                if ($variable === '') return "(EMPTY STRING)";
                if ($variable instanceof Throwable) return ['message' => $variable->getMessage(), 'trace' => $variable->getTrace()];
                if($jsonEncodeStatus && is_object($variable)) return (array)$variable;
                return $variable;
            };
            $trace = [
                'date' => date('Y-m-d H:i:s'),
                'traceId' => ContextHandler::pullTraceId(),
                "debugBacktrace" =>  "./{$file1}(line:{$traceInfo[1]['line']})",
                /*****
                'debugBacktrace' => [
                    "./{$file1}(line:{$traceInfo[1]['line']})",
                    "./{$file2}(line:{$traceInfo[2]['line']}/func:{$traceInfo[2]['function']})",
                ],
                *****/
                'label' => $label ?: 'default',
                'message' => $funcFormat($variable, $jsonEncodeStatus),
                'request' => ContextHandler::pullRequestAbstract(),
            ];
            //check memory[START]
            $traceJson = commonJsonEncode($trace);
            if(strlen($traceJson) > (($megabyteLimit = 128/*unit:KB*/) * 1024)){//超出限額則截取
                $traceJson = substr($traceJson, 0,$megabyteLimit * 1024);
                $jsonEncodeStatus = true;
            }
            //check memory[END]
            if($jsonEncodeStatus) {
                $trace = "{$traceJson}\n";
            }else{
                $trace = print_r($trace, true);//print_r()的換行會將大變量瞬間膨脹導致內存滿載
                $trace = "\n:<<UNIT[START]\n{$trace}\nUNIT[END]\n";
            }
            echo $trace;
            return $trace;
        } catch (Throwable $e) {
            return commonJsonEncode([
                'date' => date('Y-m-d H:i:s'),
                'traceId' => ContextHandler::pullTraceId(),
                'debugBacktrace' => $e->getFile() . "(line:{$e->getLine()})",
                'label' => "{$label} throwable",
                'message' => $e->getMessage(),
                'request' => ContextHandler::pullRequestAbstract(),
            ]);
        }
    }
}