<?php

declare(strict_types=1);

use Baichuan\Library\Handler\ModelHandler;
use Baichuan\Library\Handler\MongoDBHandler;
use Baichuan\Library\Handler\MonologHandler;
use Baichuan\Library\Handler\TraceHandler;
use Baichuan\Library\Handler\UtilityHandler;
use Hyperf\Kafka\ProducerManager;
use Hyperf\Redis\RedisFactory;

//if (!function_exists('traceHandler')) {
//    function traceHandler($variable, string $label = '', string $level = 'info', $monolog = true): bool
//    {
//        if($monolog && class_exists(MonologHandler::class)){
//            MonologHandler::$level($variable, $label);
//        }else{
//            //非協程I/O[START]
//            $path = BASE_PATH . "/runtime/logs/" . __FUNCTION__ . "-0000-00-" . date("d") . ".log";//keep it for one month
//            if (!file_exists($path)) touch($path);//compatible file_put_contents() cannot be created automatically
//            $trace = TraceHandler::traceFormatter($variable, $label);
//            if (abs(filesize($path)) > 1024 * 1024 * 1024) {//flush beyond the limit/1024m
//                file_put_contents($path, $trace/*, LOCK_EX*/); //TODO:阻塞風險
//            } else {
//                file_put_contents($path, $trace, FILE_APPEND/* | LOCK_EX*/);
//            }
//            if(UtilityHandler::matchEnvi('local')) echo "$trace\n";
//            //非協程I/O[END]
//        }
//        return true;
//    }
//}

if (!function_exists('modelHandler')) {
    function modelHandler(string $model): ModelHandler
    {
        return new ModelHandler($model);
    }
}

if (!function_exists('mongoDBHandler')) {
    function mongoDBHandler(string $collection, string $db = ''): MongoDBHandler
    {
        return make(MongoDBHandler::class, [$collection, $db]);
    }
}

if (!function_exists('redisInstance')) {
    function redisInstance(string $poolName = 'default'): Hyperf\Redis\Redis
    {
        return UtilityHandler::di()->get(RedisFactory::class)->get($poolName);
    }
}

if (!function_exists('kafkaInstance')) {
    function kafkaInstance(string $poolName = 'default'): Hyperf\Kafka\Producer
    {
        return UtilityHandler::di()->get(ProducerManager::class)->getProducer($poolName);
    }
}