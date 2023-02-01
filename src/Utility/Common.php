<?php

declare(strict_types=1);

use Baichuan\Library\Constant\AnsiColorEnum;
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
        if (!in_array(env('APP_ENV'), ['local', 'dev', 'test'])) {
            //return;
        }
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
        } catch (\Throwable $e) {
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
            if ($member instanceof \Closure) {
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
