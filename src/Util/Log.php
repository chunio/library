<?php

declare(strict_types=1);

namespace Baichuan\Library\Util;

use Hyperf\Context\Context;
use Hyperf\Utils\Str;

class Log
{

    public static function pullRequestId(): string
    {
        if (!($requestId = Context::get('requestId'))) {
            $requestId = "requestId#" . Str::random(32);
            Context::set('requestId', $requestId);
        }
        return $requestId;
    }

}
