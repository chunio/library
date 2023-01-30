<?php

declare(strict_types=1);

if (!function_exists('xdebug')) {
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
        return config('app_env') == $envi;
    }
}
