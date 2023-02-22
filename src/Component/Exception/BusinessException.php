<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Exception;

class BusinessException extends AbstractException
{

    public static function throw($message, $code = 120, $statusCode = 200)
    {
        throw new self($code, [], null, $message, $statusCode);
    }

}
