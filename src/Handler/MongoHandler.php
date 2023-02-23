<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;
use Hyperf\GoTask\MongoClient\Type\InsertManyResult;
use Hyperf\GoTask\MongoClient\Type\InsertOneResult;

//TODO:待調試
class MongoHandler
{

    public $db = '';

    public $collection = '';

    public $commonField = [
        'delete_time',
        'update_time',
        'create_time',
    ];

    public $operator = [
        'IN' => '$in',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
    ];

    /**
     * @Inject()
     * @var MongoClient
     */
    public $MongoClient;

    public function __construct(string $db, string $collection)
    {
       $this->MongoClient->database($db)->collection($collection);
    }

    public function one(array $where, array $field = ['*'], array $order = []): array
    {
        return $this->MongoClient->database($this->db)->collection($this->collection)->findOne($where);
    }

    /**
     * @param array $where example:[['field1', '=', 'value1'], ['field2', '<=', 'value2'], ['field2', '>=', 'value3'], ['field3', 'IN', 'value4List']]
     * @param array $select example:['field1', 'field2', ...]
     * @param array $order example:['field1' => 'ASC', 'field2' => 'DESC', ...]
     * @return array
     * author : zengweitao@gmail.com
     * datetime: 2023/02/23 14:13
     * memo : $where僅支持邏輯與
     */
    public function commonList(array $where, array $select = [], array $group = []/*預留*/, array $order = []): array
    {
        //format where[START]
        $formatWhere = [];
        foreach ($where as $value){
            [$unitField, $unitOperator, $unitValue] = $value;
            if($mongoOperation = ($this->operator[$unitOperator] ?? '')){
                $formatWhere[$unitField][$mongoOperation] = $unitValue;
            }else{
                $formatWhere[$unitField] = $unitValue;
            }
        }
        //format where[END]
        //format option[START]
        $option = [];
        if($select){
            foreach ($select as $unitField){
                $option['projection'/*聲明需返回的字段*/][$unitField] = 1;//1表示返回
            }
        }
        if($order){
            foreach ($order as $unitField => $unitSequence){
                $option['sort'][$unitField] = ($unitSequence === 'ASC') ? 1/*正序*/ : -1/*倒敘*/;
            }
        }
        //format option[END]
        return $this->MongoClient->database($this->db)->collection($this->collection)->find($formatWhere, $option);
    }

    /**
     * @param string $field
     * author : zengweitao@gmail.com
     * datetime: 2023/02/23 20:59
     * memo : 以$field分組，並統計各每組成員總數
     */
    public function aggregate(string $field)
    {
        $result = $this->MongoClient->database($this->db)->collection($this->collection)->aggregate([
            [
                '$group' =>
                    [
                        '_id' => $field,
                        'count' => ['$sum' => 1]
                    ]
            ],
        ]);
    }

    public function commonInsert(array $data): InsertOneResult
    {
        return $this->MongoClient->database($this->db)->collection($this->collection)->insertOne($data);
    }

    public function multiInsert(array $data): InsertManyResult
    {
        return $this->MongoClient->database($this->db)->collection($this->collection)->insertMany($data);
    }

    /**
     * @param array $where
     * @param array $data
     * @return int 返回改變行數
     * author : zengweitao@gmail.com
     * datetime: 2023/02/23 13:48
     * memo : null
     */
    public function commonUpdate(array $where, array $data = []): int
    {
        $update = [
            '$set' => $data
        ];
        $UpdateResult = $this->MongoClient->database($this->db)->collection($this->collection)->updateMany($where, $update);
        return $UpdateResult->getModifiedCount();
    }

    // memo : 返回改變行數
    public function multiUpdate(array $multiWhere, array $data)
    {

    }

//    public function multiList(
//        array $mutliWhere,
//        array $field = ['*'], //example : ['id', 'name']
//        array $group = [],
//        array $order = [], //example : ['id', 'DESC']
//        int $limit = 0
//    )
//    {
//
//    }

}