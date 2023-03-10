<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logic;

use Baichuan\Library\Constant\RedisKeyEnum;
use Baichuan\Library\Handler\RedisHandler;
use Baichuan\Library\Handler\UtilityHandler;
use Hyperf\Di\Annotation\Inject;
use Hyperf\GoTask\MongoClient\MongoClient;

class ManagerLogic
{

//    /**
//     * @Inject()
//     * @var MongoClient
//     */
//    public $MongoClient;

    public function apiRank(): array
    {
        $Redis = redisInstance();
        $prefixKeyword = RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Num:'];
        $result = RedisHandler::matchList($prefixKeyword);
        $suffixKeywordList = array_map(fn($value) => str_replace($prefixKeyword,'', $value), $result);
        $rank = [];
        foreach ($suffixKeywordList as $unitSuffixKeyword){
            $num = intval($Redis->get(RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Num:'] . $unitSuffixKeyword));
            $second = floatval($Redis->get(RedisKeyEnum::STRING['STRING:ApiElapsedTimeRank:Second:'] . $unitSuffixKeyword));
            $average = floatval(number_format($second / $num, 5,'.',''));
            $rank[] = [
                'api' => $unitSuffixKeyword,
                'average' => $average,
                'second' => $second,
                'num' => $num,
            ];
        }
        return array_values(UtilityHandler::order($rank,'average', 'DESC'));
    }

}