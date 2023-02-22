<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Exception;

use Baichuan\Library\Constant\ErrorCodeEnum;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Server\Response;

abstract class AbstractException extends HttpException implements ImplementException
{
    /**
     * 额外数据.
     * @var null
     */
    protected $extra;

    /**
     * ErrorCode 的国际化参数
     * @var null
     */
    protected $params = [];

    /**
     * 直接外面指定了 message
     * @var null
     */
    protected $customMessage;

    public function __construct($code = 120, array $params = [], $extra = null, $message = '', $status = 200, \Throwable $previous = null)
    {
        $code = $code ?: ErrorCodeEnum::FAIL;

        $this->extra = $extra;
        $this->params = $params;
        $this->customMessage = $message;

        if (!$message) {
            $message = $this->codeToMessage($code, $params);
        }
        if (!$message) {
            $message = Response::getReasonPhraseByCode($status);
        }
        parent::__construct($status, $message, $code, $previous);
    }

    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @return mixed
     */
    public function getParams()
    {
        return $this->params;
    }

    public function getCustomMessage()
    {
        return $this->customMessage;
    }

    public function getAppCode(): int
    {
        return $this->getCode();
    }

    public function getMessageLocale(?string $locale = null)
    {
        if ($this->getCustomMessage()) {
            return $this->getCustomMessage();
        }

        $rtn = $this->codeToMessage($this->code, $this->params, $locale);
        if (!$rtn) {
            return $this->getMessage();
        }

        return $rtn;
    }

    protected function codeToMessage(int $code, array $params = [], ?string $locale = null): string
    {
        $name = "message";

        $message = ErrorCodeCollector::getValue($code, $name);

        $result = __($message, $params, $locale);
        // If the result of translate doesn't exist, the result is equal with message, so we will skip it.
        if ($result && $result !== $message) {
            return $result;
        }

        $count = count($params);
        if ($count > 0) {
            return sprintf($message, ...$params);
        }

        return $message;
    }
}
