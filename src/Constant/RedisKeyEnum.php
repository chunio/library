<?php

declare(strict_types=1);

namespace Baichuan\Library\Constant;

use Hyperf\Constants\AbstractConstants;

/**
 * Class RedisKeyEnum
 * @package Baichuan\Library\Constant
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 18:47
 * memo : null
 */
class RedisKeyEnum extends AbstractConstants
{
    public const STRING = [
        'STRING:Example:' => 'STRING:Example:',
        'STRING:MutexName:' => 'STRING:MutexName:',//[互斥鎖]名稱前綴
        'STRING:MutexResult:' => 'STRING:MutexResult:',//[互斥鎖]結果前綴
        'STRING:BookShelfList:' => 'STRING:PthreadCondInt:',//[互斥鎖？]條件變量
    ];

    public const HASH = [
        //'HASH:Unlock:' => 'HASH:Unlock:',//
    ];

    public const LIST = [ // 「LIST」conflicts with the system keyword
    ];

    public const SET = [
    ];

    public const SORTED_SET = [
    ];

}
