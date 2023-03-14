<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;

//TODO:待調試
class MongoDBHandler
{

    /**
     * @var string
     * db.tupop_stat.createIndex({"payment_datetime":-1})//創建倒序索引
     * db.tupop_stat.createIndex({"coin_status":"hashed"})//創建哈希索引
     */

    public $db = '';

    public $collection = '';

    public $commonField = [
        'delete_time',
        'update_time',
        'create_time',
    ];

    public $operator = [
        'IN' => '$in',
        'NIN' => '$nin',
        '>' => '$gt',
        '>=' => '$gte',
        '<' => '$lt',
        '<=' => '$lte',
        '!=' => '$ne'
    ];

    /**
     * @Inject()
     * @var MongoClient
     */
    public $MongoClient;

    public function __construct(string $collection, string $db = '')//
    {
        $this->db = $db ?: config('mongodb.db');
        $this->collection = $collection;
        //$this->MongoClient->database()->collection($collection);
    }

    public function one(array $where, array $select = [], array $order = []): array
    {
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
        return $this->MongoClient->database($this->db)->collection($this->collection)->findOne($where, $option);
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
        $formatWhere = [];//空表示全部
        if($where){
            foreach ($where as $value){
                [$unitField, $unitOperator, $unitValue] = $value;
                if($mongoOperation = ($this->operator[$unitOperator] ?? '')){
                    $formatWhere[$unitField][$mongoOperation] = $unitValue;
                }else{
                    $formatWhere[$unitField] = $unitValue;//默認：等於
                }
            }
        }
        //format where[END]
        //format option[START]
        $option = [];
        if($select){
            $id = false;
            foreach ($select as $unitField){
                if($unitField === '_id') $id = true;
                $option['projection'/*聲明需返回的字段*/][$unitField] = 1;//1表示返回
            }
            if(!$id) $option['projection']['_id'] = 0;//因爲_id默認返回
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
    public function aggregate(string $field): array
    {
        return $this->MongoClient->database($this->db)->collection($this->collection)->aggregate([
            [
                '$group' =>
                    [
                        '_id' => $field,
                        'count' => ['$sum' => 1]
                    ]
            ],
        ]);
    }

    public function commonInsert(array $data)/*: ?ObjectId|array*/
    {
        if(!($data[0] ?? [])){
            $InsertOneResult = $this->MongoClient->database($this->db)->collection($this->collection)->insertOne($data);
            return $InsertOneResult->getInsertedId();
        }else{
            $InsertManyResult = $this->MongoClient->database($this->db)->collection($this->collection)->insertMany($data);
            return $InsertManyResult->getInsertedIDs();
        }
    }

    /**
     * @param array $where
     * @param array $data
     * @return int 返回改變行數
     * author : zengweitao@gmail.com
     * datetime: 2023/02/23 13:48
     * memo : 返回改變行數
     */
    public function commonUpdate(array $where, array $data): int
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
        $update = [
            '$set' => $data
        ];
        $UpdateResult = $this->MongoClient->database($this->db)->collection($this->collection)->updateMany($formatWhere, $update);
        return $UpdateResult->getModifiedCount();
    }

    public function commonDelete($where): int
    {
        $DeleteResult = $this->MongoClient->database($this->db)->collection($this->collection)->deleteMany($where);
        return $DeleteResult->getDeletedCount();
    }

    public function commonCount($where): int
    {
        return $this->MongoClient->database($this->db)->collection($this->collection)->countDocuments($where);
    }

}