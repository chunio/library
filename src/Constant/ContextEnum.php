<?php

declare(strict_types=1);

namespace Baichuan\Library\Constant;

use Hyperf\Constants\AbstractConstants;

/**
 * class ContextEnum
 */
class ContextEnum extends AbstractConstants
{

    public const TraceId = 'TraceId';//請求ID

    public const RequestStartMicroTime = 'RequestStartMicroTime';//請求起始時間（單位：毫秒）

    public const RequestAbstract = 'RequestAbstract';//請求信息

    public const SignaturePayload = 'SignaturePayload';//[簽名]payload

}