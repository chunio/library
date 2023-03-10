<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use App\Constant\RedisKeyEnum;
use Hyperf\Utils\WaitGroup;

/**
 * class CoroutineHandler
 * @package Component
 * author : zengweitao@msn.com
 * datetime : 2022-04-17 10:17
 * memo : null
 */
class CoroutineHandler
{

    const INIT = [
        'WAITGROUP_ADD_LIMIT' => 3,//30/25/20/15/10/5/4/too many requests
        'WAITGROUP_WAIT_TIMEOUT' => 5,//單位：秒
        'CO_CACHE_BUTTON' => true,//是否緩存協程#執行結果#
        'CO_CACHE_TTL' => 7200,//
        'CO_CACHE_KEY' => 'HASH:AsyncHandler:',
        //'FULL_PAGE_LIMIT' => 10000,//
        //'FULL_DATE_LIMIT' => 180,
        'RETRY_LIMIT' => 3,//[同一批次的]重試次數
    ];

    /**
     * @param callable $coroutine
     * @return int
     * author : zengweitao@msn.com
     * datetime : 2022-04-17 10:16
     * memo : null
     */
    public static function co(callable $coroutine)
    {
        return co(function() use($coroutine){
            try{
                self::before();
                $coroutine();
                self::after();
            }catch (\Throwable $e){
                self::throwable($e);
            }
        });
    }

    /**
     * author : zengweitao@msn.com
     * datetime : 2022-04-17 10:16
     * memo : null
     */
    public static function before(): void
    {

    }

    /**
     * author : zengweitao@msn.com
     * datetime : 2022-04-17 10:16
     * memo : null
     */
    public static function after(): void
    {

    }

    public static function throwable(\Throwable $e): void
    {
        throw $e;
    }

    //建立常用模型，start##################################################

    //模型1，拉取{$fullList}的所有數據
    public static function fullListModel(array &$channel, array &$throwable, callable $func, array $fullList, int $pageSize, bool $merge = false, string $cacheId = ''): bool
    {
        $cacheId = $cacheId ?: md5(__CLASS__ . __FUNCTION__ . serialize($func)/*TODO:是否唯一？*/ . json_encode($fullList) . $pageSize . intval($merge));//要求：唯一+可重入
        $syncQueryNum = ceil(count($fullList) / $pageSize); //[串行時]請求次數
        $asyncQueryNum = ceil($syncQueryNum / self::INIT['WAITGROUP_ADD_LIMIT']); //[並行時]請求次數
        $lastWaitGroupNum = $syncQueryNum - (($asyncQueryNum - 1) * self::INIT['WAITGROUP_ADD_LIMIT']); //[並行時]最後一次串行次數
        $Redis = redisInstance();
        $redisKey = RedisKeyEnum::HASH[self::INIT['CO_CACHE_KEY']] . $cacheId;
        $loopIndex = 1;
        while ($loopIndex <= $asyncQueryNum) {
            $eachWaitGroupNum = $loopIndex == $asyncQueryNum ? $lastWaitGroupNum : self::INIT['WAITGROUP_ADD_LIMIT'];
            $WaitGroup = new WaitGroup();
            $WaitGroup->add(intval($eachWaitGroupNum));
            //預循環{$waitGroupAddLimit}次，start-----
            for($virtualLoopIndex = 1; $virtualLoopIndex <= $eachWaitGroupNum; $virtualLoopIndex++){
                $pointer = (string)((($loopIndex - 1) * self::INIT['WAITGROUP_ADD_LIMIT']) + $virtualLoopIndex);
                $eachValue = $Redis->hGet($redisKey, $pointer);
                $throwable[$pointer] = $pointer;//default，執行成功時移除
                if(self::INIT['CO_CACHE_BUTTON'] && $eachValue === false){
                    $slicePagination = multiPagination($fullList, intval($pointer), $pageSize);
                    $sliceList = $slicePagination['list'];
                    self::co(function() use(&$channel, &$throwable, $WaitGroup, $func, $Redis, $redisKey, $sliceList, $pointer){
                        $eachResult = $func($sliceList, intval($pointer));
                        if($eachResult) $Redis->hSet($redisKey, $pointer, igbinary_serialize($eachResult));
                        unset($throwable[$pointer]);
                        $WaitGroup->done();
                    });
                }else{
                    $channel[$pointer] = igbinary_unserialize($eachValue);
                    unset($throwable[$pointer]);
                    $WaitGroup->done();
                }
            }
            //預循環{$waitGroupAddLimit}次，end-----
            $WaitGroup->wait(self::INIT['WAITGROUP_WAIT_TIMEOUT']);//TODO:採集超時協程
            $loopIndex++;
        }
        $Redis->expire($redisKey, self::INIT['CO_CACHE_TTL']);
        ksort($channel,SORT_STRING);//升序排序
        if($merge){
            $mergeList = [];
            foreach ($channel as $pointer => &$eachList) {
                $mergeList = array_merge($mergeList, $eachList);
                unset($channel[$pointer]);
            }
            $channel = $mergeList;
        }
        return true;
    }

    /**
     * author : zengweitao@msn.com
     * datetime : 2022-04-29 16:56
     * memo : 模型2，拉取遠程所有分頁數據
     */
    public static function fullPageModel(array &$return, array &$throwable, callable $func, int $pageSize, bool $merge = false, string $cacheId = ''): bool
    {
        $cacheId = $cacheId ?: md5(__CLASS__ . __FUNCTION__ . serialize($func) . $pageSize . intval($merge));//要求：唯一+可重入
        $total = 0;//init
        $func(1, $pageSize, $total);
        if($remainNum/*剩餘請求次數（即：頁數）*/ = intval(($total - $pageSize) > 0 ? (ceil($total / $pageSize) - 1/*減去首頁（已拉取）*/) : 0)){
            $page = 2;//次頁開始異步
            $Redis = redisInstance();
            $redisKey = RedisKeyEnum::HASH[self::INIT['CO_CACHE_KEY']] . $cacheId;
            $waitGroupNum = ceil($remainNum / self::INIT['WAITGROUP_ADD_LIMIT']);
            $lastCoNum = $remainNum % self::INIT['WAITGROUP_ADD_LIMIT'] ?: self::INIT['WAITGROUP_WAIT_TIMEOUT'];//最後一次循環的協程數
            for ($loopIndex = 1; $loopIndex <= $waitGroupNum; $loopIndex++){
                $eachCoNum = $loopIndex != $waitGroupNum ? self::INIT['WAITGROUP_WAIT_TIMEOUT'] : $lastCoNum;
                $WaitGroup = new \Hyperf\Utils\WaitGroup();
                $WaitGroup->add($eachCoNum);
                for($coIndex = 1; $coIndex <= $eachCoNum; $coIndex++){
                    $eachHashField = "{$loopIndex}:{$coIndex}";
                    $eachValue = $Redis->hGet($redisKey, $eachHashField);
                    $throwable[$page] = $page;//default，執行成功時移除
                    if(self::INIT['CO_CACHE_BUTTON'] && $eachValue !== false){
                        $return[$page] = igbinary_unserialize($eachValue);
                        unset($throwable[$page]);
                        $WaitGroup->done();
                    }else{
                        self::co(function() use(&$return, &$throwable, $WaitGroup, $Redis, $redisKey, $eachHashField, $func, $page, $pageSize){
                            $eachResult = $func($page, $pageSize);
                            $Redis->hSet($redisKey, $eachHashField, igbinary_serialize($eachResult));
                            $return[$page] = $eachResult;
                            unset($throwable[$page]);
                            $WaitGroup->done();
                        });
                    }
                    $page++;
                }
                $WaitGroup->wait(self::INIT['WAITGROUP_WAIT_TIMEOUT']);
            }
            $Redis->expire($redisKey, self::INIT['CO_CACHE_TTL']);
            ksort($return,SORT_STRING);//升序排序
        }
        if($merge){
            $fullPageList = [];
            foreach ($return as $page => &$eachPageList) {
                $fullPageList = array_merge($fullPageList, $eachPageList);
                unset($return[$page]);//當註銷最後的一個元素時，不會解除引用
            }
            $return = $fullPageList;
        }
        return true;
    }

    /**
     * author : zengweitao@msn.com
     * datetime : 2022-05-06 20:39
     * memo : 模型3，拉取目標時間範圍的所有數據
     */
    public static function fullDateModel(array &$channel, array &$throwable, callable $func, array $date, string $cacheId = ''): bool
    {
        $cacheId = $cacheId ?: md5(__CLASS__ . __FUNCTION__ . serialize($func)/*TODO:是否唯一？*/ . json_encode($date));//要求：唯一+可重入
        $remainNum = (strtotime($date[1]) - strtotime($date[0])) / 86400 + 1;//剩餘請求次數（即：天數）
        $waitGroupNum = ceil($remainNum / self::INIT['WAITGROUP_ADD_LIMIT']);
        $lastCoNum = ($remainNum % self::INIT['WAITGROUP_ADD_LIMIT']) ?: self::INIT['WAITGROUP_ADD_LIMIT'];//最後一次循環的協程數
        $Redis = redisInstance();
        $redisKey = RedisKeyEnum::HASH[self::INIT['CO_CACHE_KEY']] . $cacheId;
        for ($loopIndex = 1; $loopIndex <= $waitGroupNum; $loopIndex++){
            $eachCoNum = ($loopIndex < $waitGroupNum) ? self::INIT['WAITGROUP_ADD_LIMIT'] : $lastCoNum;
            $startTime = strtotime($date[0]) + (86400 * self::INIT['WAITGROUP_ADD_LIMIT'] * ($loopIndex - 1));
            $endTime = $startTime + 86400 * ($eachCoNum - 1);
            $WaitGroup = new \Hyperf\Utils\WaitGroup();
            $WaitGroup->add($eachCoNum);
            for ($coStartTime = $startTime/*局部變量*/; $coStartTime <= $endTime; $coStartTime += 86400){
                $eachDate = date('Y-m-d', $coStartTime);//不可省略
                $eachValue = $Redis->hGet($redisKey, $eachDate);
                $throwable[$eachDate] = $eachDate;//default，執行成功時移除
                if(self::INIT['CO_CACHE_BUTTON'] && $eachValue === false){
                    self::co(function() use(&$channel, &$throwable, $WaitGroup, $func, $Redis, $redisKey, $eachDate){
                        $eachResult = $func([$eachDate, $eachDate]);
                        if($eachResult) $Redis->hSet($redisKey, $eachDate, igbinary_serialize($eachResult));
                        unset($throwable[$eachDate]);
                        $WaitGroup->done();
                    });
                }else{
                    $channel[$eachDate] = igbinary_unserialize($eachValue);
                    unset($throwable[$eachDate]);
                    $WaitGroup->done();
                }
            }
            $WaitGroup->wait(self::INIT['WAITGROUP_WAIT_TIMEOUT']);//TODO:採集超時的協程信息
        }
        $Redis->expire($redisKey, self::INIT['CO_CACHE_TTL']);
        ksort($channel,SORT_STRING);//升序排序
        return true;
    }

    //TODO:降級/熔斷
    public static function retry(callable $func, int $retryLimit = 0)
    {
        $actionNum = 1;
        $retryLimit = $retryLimit ?: self::INIT['RETRY_LIMIT'];
        do{
            try{
                return $func();
            }catch (\Throwable $e){
                monologHandler("retryComeIn{$actionNum}");
                $actionNum += 1;
                if($actionNum > $retryLimit) {
                    monologHandler('retryThrowable');
                    throw $e;
                }
            }
        }while($actionNum <= $retryLimit);
    }

    //建立常用模型，end##################################################

}
