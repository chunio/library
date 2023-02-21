<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;

//TODO:待調試
class MongoHandler
{

    public $db = '';

    public $collection = '';

    /**
     * @Inject()
     * @var MongoClient
     */
    public $MongoClient;

    public function __construct(string $db, string $collection)
    {
       $this->MongoClient->database($db)->collection($collection);
    }

    public function insert(array $data)
    {
        if(count($data) === 1){
            return $this->MongoClient->database($this->db)->collection($this->collection)->insertOne($data);
        }else{
            return $this->MongoClient->database($this->db)->collection($this->collection)->insertMany($data);
        }
    }

}