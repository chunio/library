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
        TraceHandler::push('come in');
        $secondKeyword = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Second:'];
        $numKeyword = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Num:'];
        $result = RedisHandler::matchList($secondKeyword);//
        TraceHandler::push($result);
        return $result;
    }

}