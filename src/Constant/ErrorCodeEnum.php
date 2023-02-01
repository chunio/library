<?php

declare(strict_types=1);

namespace Baichuan\Library\Constant;

use Hyperf\Constants\AbstractConstants;

class ErrorCodeEnum extends AbstractConstants
{

    /**
     * @Message("SUCCESS")
     */
    const SUCCESS = 0;

    /**
     * @Message("FAIL")
     */
    const FAIL = 120;

    /**
     * @Message("參數錯誤")
     */
    const REQUEST_INVALID = 422;


    /**
     * @Message("認證失敗")
     */
    const AUTH = 401;


    /**
     * @Message("缺少權限")
     */
    const PERMIT = 403;

}
