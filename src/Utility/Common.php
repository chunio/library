<?php

declare(strict_types=1);

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

//if(!function_exists('sendAlarm2DingTalk')){
//    function sendAlarm2DingTalk(mixed $variable)
//    {
//        co(function()use($variable){
//            try{
//                //trace[START]
//                $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
//                $scriptName = $line = '';
//                if ($traceInfo[1]) {//last track
//                    $file = $traceInfo[1]['file'];
//                    $line = $traceInfo[1]['line'];
//                    $scriptName = ($startIndex = strrpos($file, env('APP_NAME'))) ? substr($file, $startIndex + 1) : $file;
//                }
//                //trace[END]
//                if(!config('system.alarm',0)) return false;
//                $timestamp = time() * 1000;
//                if ($variable instanceof Throwable) {
//                    $variable = json_encode([
//                        'code' => $variable->getCode(),
//                        'title' => 'Throwable',
//                        'message' => $variable->getMessage(),
//                        'file' => $variable->getFile() . "(line:{$variable->getLine()})",
//                        //'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10)
//                    ]);
//                }elseif(is_array($alarm)){
//                    $alarm = json_encode($alarm);
//                }else{
//                    $alarm = (string)$alarm;
//                }
//                //trace2[START]
//                $alarm .= "file:{$file}/line:{$line}/scriptName:{$scriptName}";
//                //trace2[END]
//                $content = '{"msgtype":"text","text":{"content":"['. env('APP_NAME') . ' / ' . env('APP_ENV') . "]\n" . str_replace("\"","'",$alarm) .'"}}';
//                //$accessToken = 'b76e1cf33a222a8ddee2fde1c930be03cdc1f04a31d1a1036be9803a6f712319';
//                //$secret = 'SEC8e6642f7e93939b4e04edefc7e06248d8b8c8120c8ff439879fc1ad5970ff601';
//                switch ($event){
//                    case 'common':
//                        $accessToken = config('system.dingtalk.common.access_token');
//                        $secret = config('system.dingtalk.common.secret');
//                        break;
//                    case 'topup':
//                        $accessToken = config('system.dingtalk.topup.access_token');
//                        $secret = config('system.dingtalk.topup.secret');
//                        break;
//                    default :
//                        $accessToken = config('system.dingtalk.common.access_token');
//                        $secret = config('system.dingtalk.common.secret');
//                }
//                $parameter = [
//                    'access_token' => $accessToken,
//                    'timestamp' => $timestamp,
//                    'sign' => urlencode(base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true)))
//                ];
//                $webhook = "https://oapi.dingtalk.com/robot/send?" . http_build_query($parameter);
//                $option = [
//                    'http' => [
//                        'method' => "POST",
//                        'header' => "Content-type:application/json;charset=utf-8",//
//                        'content' => $content
//                    ],
//                    "ssl" => [ //不驗證ssl證書
//                        "verify_peer" => false,
//                        "verify_peer_name" => false
//                    ]
//                ];
//                return file_get_contents($webhook, false, stream_context_create($option));
//            }catch (Throwable $e){
//                xdebug($e);//TODO:log
//            }
//        });
//    }
//}

if(function_exists('formatTraceVariable')){
    /**
     * @param mixed $variable
     * @param string $label
     * @param bool $jsonEncodeStatus
     * @return string
     * author : zengweitao@gmail.com
     * datetime: 2023/02/10 16:58
     * memo : null
     */
    function formatTraceVariable(mixed &$variable, string $label = '', bool $jsonEncodeStatus = false): string
    {
        try {
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);//TODO：此函數性能如何？
            $scriptName = $line = '';
            if ($traceInfo[2]) {//採集引用此方法的上兩層的方法
                $file = $traceInfo[2]['file'];
                $line = $traceInfo[2]['line'];
                $scriptName = ($startIndex = strrpos($file, env('APP_NAME'))) ? substr($file, $startIndex + 1) : $file;
            }
            xdebug('$trace0');
            $trace = [
                'label' => $label ?: 'default',
                'date' => date('Y-m-d H:i:s'),
                'path' => "./{$scriptName}(line:{$line})",
                'traceId' => ContextHandler::pullTraceId(),
                'request' => ContextHandler::pullRequestAbstract(),
                //special type conversion[START]
                'message' => function()use(&$variable, $jsonEncodeStatus){
                    if ($variable === true) return 'TRUE(BOOL)';
                    if ($variable === false) return 'FALSE(BOOL)';
                    if ($variable === null) return 'NULL';
                    if ($variable === '') return "(EMPTY STRING)";
                    if ($variable instanceof Throwable) return ['message' => $variable->getMessage(), 'trace' => $variable->getTrace()];
                    if($jsonEncodeStatus && is_object($variable)) return (array)$variable;
                    return $variable;
                },
                //special type conversion[END]
            ];
            xdebug($trace,'$trace1');
            if($jsonEncodeStatus) {
                $trace = commonJsonEncode($trace) . "\n";
            }else{
                $trace = @print_r($trace, true);
                if(strlen($trace) > (($megabyteLimit = 2/*unit:MB*/) * 1024 * 1024)){//超出限額則截取
                    $trace = substr($trace, 0,$megabyteLimit * 1024 * 1024);
                }
                $trace = "\n:<<UNIT[START]\n{$trace}\nUNIT[END]\n";
            }
            return $trace;
        } catch (Throwable $e) {
            return commonJsonEncode([
                'label' => 'throwable',
                'date' => date('Y-m-d H:i:s'),
                'path' => $e->getFile() . "(line:{$e->getLine()})",
                'traceId' => ContextHandler::pullTraceId(),
                'request' => ContextHandler::pullRequestAbstract(),
                'message' => $e->getMessage(),
            ]);
        }
    }
}