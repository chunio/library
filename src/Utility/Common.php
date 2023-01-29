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
     * @return mixed
     * author : zengweitao@msn.com
     * datetime : 2021-10-11 12:39
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