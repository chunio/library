<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Exception;

class AuthException extends AbstractException
{

    public function __construct($code = 401, $params = [], $extra = null, $message = '', $status = 401, \Throwable $previous = null)
    {
        parent::__construct($code, $params, $extra, $message, $status, $previous);
    }

    public static function throw($message, $code = 401)
    {
        throw new self($code, [], null, $message);
    }

}
