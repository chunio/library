<?php

declare(strict_types=1);

namespace Baichuan\Library\Utility;

use Baichuan\Library\Constant\RedisKeyEnum;

/**
 * Class RedisHandler
 * @package Baichuan\Library\Utility
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 18:58
 * memo : 待添加：//igbinary_serialize() 時間快，壓縮高
 */
class RedisHandler{

    static public $init = [
        'ttl' => 86400 * 31,//單位：秒
    ];

    /**
     * @param callable $callable
     * @param int $ttl
     * @return mixed
     * @throws \ReflectionException
     * author : zengweitao@msn.com
     * datetime : 2022-04-17 15:50
     * memo : null
     */
    static function attemptGet(callable $callable, string $redisKey, $ttl = null)
    {
        $Redis = redisInstance();
        $value = $Redis->get($redisKey);
        if ($value === false) {
            $value = $callable();
            $Redis->set($redisKey, commonJsonEncode($value), ($ttl === null ? self::$init['ttl'] : $ttl));
            return $value;
        }
        return json_decode($value, true);
    }

    /**
     * @param callable $callable
     * @param int $ttl
     * @return mixed
     * @throws \ReflectionException
     * author : zengweitao@msn.com
     * datetime : 2022-04-17 15:50
     * memo : null
     */
    static function attemptHashGet(string $redisKey, string $hashField, callable $callable, int $ttl = 0)
    {
        $Redis = redisInstance();
        $value = $Redis->hGet($redisKey, $hashField);
        if ($value === false) {
            $result = $callable();
            $value = commonJsonEncode($result);
            $Redis->hSet($redisKey, $hashField, $value);
        }
        if($ttl !== -1) $Redis->expire($redisKey,$ttl ?: self::$init['ttl']);
        return json_decode($value, true);
    }

    /**
     * @param string $mutexName
     * @param callable|null $mainFunc
     * @param int $lockedTime
     * @return array|mixed|null
     * @throws \Throwable
     * author : zengweitao@msn.com
     * datetime : 2022-04-17 16:38
     * memo : 互斥鎖
     */
    public static function mutex(string $mutexName, callable $mainFunc = null, int $lockedTime = 3/* , int &$retry = 0 */, bool $returnCacheResult = true)
    {
        try {
            $owner = uniqid('', true);
            $Redis = redisInstance();
            $lockedRedisKey = RedisKeyEnum::STRING['STRING:MutexName:'] . $mutexName;
            $resultRedisKey = RedisKeyEnum::STRING['STRING:MutexResult:'] . $mutexName;
            if ($Redis->set($lockedRedisKey, $owner, ['EX' => $lockedTime, 'NX']) === true) {
                $result = $mainFunc();
                if($returnCacheResult) $Redis->lPush($resultRedisKey, commonJsonEncode($result)); // 共享#並發邏輯#返回值
            } elseif($returnCacheResult) {
                if ($result/* 返回:「含:1鍵名，2鍵值」的索引數組 */ = $Redis->brPop([$resultRedisKey], $lockedTime)) {// 阻塞，提取#並發邏輯#返回值
                    $result = json_decode($result[1], true);
                    $Redis->lPush($resultRedisKey, commonJsonEncode($result));
                }
            }
        } finally {
            if (isset($Redis, $owner, $lockedRedisKey, $resultRedisKey) && ($Redis->get($lockedRedisKey) == $owner)) {
                $Redis->expire($resultRedisKey, $lockedTime);
                $Redis->del($lockedRedisKey);
            }
        }
        return $result ?? null;
    }

    //信號量
    public static function semInit()
    {

    }

//    /**
//     * @param string $mutexName
//     * @param callable|null $mainFunc
//     * @param int $lockedTime
//     * @return array|mixed|null
//     * @throws \Throwable
//     * author : zengweitao@msn.com
//     * datetime : 2022-04-17 16:38
//     * memo : 條件變量
//     */
//    static public function pthreadCondInt(string $mutexName, callable $mainFunc = null, int $lockedTime = 3/*, int &$retry = 0*/)
//    {
//        try {
//            //TODO：註冊進程結束函數
//            $owner = uniqid('', true);
//            $Redis = redisInstance();
//            $lockedRedisKey = RedisKeyEnum::STRING['STRING:PthreadCondInt:'] . $mutexName;
//            $resultRedisKey = RedisKeyEnum::STRING['STRING:PthreadCondInt:'] . $mutexName;
//            if ($Redis->set($lockedRedisKey, $owner, ['EX' => $lockedTime, 'NX']) === true) {
//                $result = $mainFunc();
//                $Redis->lPush($resultRedisKey, json_encode($result)); //共享#並發邏輯#返回值
//            } else {
//                if ($result/*返回:「含:1鍵名，2鍵值」的索引數組*/ = $Redis->brPop([$resultRedisKey], $lockedTime)) {//阻塞，提取#並發邏輯#返回值
//                    $result = json_decode($result[1], true);
//                    $Redis->lPush($resultRedisKey, json_encode($result));
//                } else {
//                    //TODO:log
//                    //if($retry) //TODO:限流/重試
//                }
//            }
//        } catch (\Throwable $e) {
//            TraceHandler::sendAlarm2DingTalk($e);
//            throw $e;
//        } finally {
//            if (isset($Redis, $owner, $lockedRedisKey, $resultRedisKey) && ($Redis->get($lockedRedisKey) == $owner)) {
//                $Redis->expire($resultRedisKey, $lockedTime);
//                $Redis->del($lockedRedisKey);
//            }
//        }
//        return $result ?? null;
//    }

//    /**
//     * author : zengweitao@msn.com
//     * datetime : 2022-05-12 17:23
//     * memo : 分佈式lRange()
//     */
//    public function multiLRange(string $queue, int $unitConsumeNum, callable $func): void
//    {
//        try{
//            static $tempQueue = [];
//            $Redis = redisInstance();
//            $slice = $Redis->lRange($queue, 0, $unitConsumeNum - 1);//批量出隊//TODO:操作臨界資源（互斥鎖+重置指針）
//            $tempQueue[] = $slice;
//            if ($slice) {
//                if ($func()) {
//                    $count = count($slice);
//                    $Redis->lTrim($queue, $count, -1);//指定保留元素
//                }
//            }
//        }catch (\Throwable $e){
//            xdebug($e,__FUNCTION__ . 'Throwable');
//        }
//    }

    //隊列管理，支持：1插隊（手動干預優先級）
    public function queueManager(){}

    static public function matchDelete(string $keyword): array
    {
        $Redis = redisInstance();
        if($cacheList = $Redis->keys("*{$keyword}*")){
            if($cachePrefix = config('redis.default.options.2')){
                array_walk($cacheList,function(&$value, $key) use($cachePrefix){
                    $value = str_replace($cachePrefix,'',$value);
                });
            }
            $Redis->del(...$cacheList);
        }
        return $cacheList;
    }

    static public function matchList(string $keyword): array
    {
        $Redis = redisInstance();
        if($cacheList = $Redis->keys("*{$keyword}*")){
            if($cachePrefix = config('redis.default.options.2')){
                array_walk($cacheList,function(&$value, $key) use($cachePrefix){
                    $value = str_replace($cachePrefix,'',$value);
                });
            }

        }
        return $cacheList;
    }

}
