<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logic;

use Baichuan\Library\Constant\RedisKeyEnum;
use Baichuan\Library\Handler\RedisHandler;
use Baichuan\Library\Handler\TraceHandler;
use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;

class ManagerLogic
{

    /**
     * @Inject()
     * @var MongoClient
     */
    public $MongoClient;

    public function apiRank(){
        $keyword = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Second:'];
        $result = RedisHandler::matchList($keyword);
        array_map(fn&($i) => str_replace($keyword,'', $i), $result);
        //$numKeyword = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Num:'];
        TraceHandler::push($result);
        return $result;
    }

}