<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;

/**
 * Class RedisHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 18:58
 * memo : 待添加：//igbinary_serialize() 時間快，壓縮高
 */
class MongoDBHandler{

    /**
     * @Inject()
     * @var MongoClient
     */
    public $MongoClient;

}