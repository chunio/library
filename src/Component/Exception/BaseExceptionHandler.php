<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Exception;

use Baichuan\Library\Constant\ErrorCodeEnum;
use Baichuan\Library\Http\Resource\JsonResource;
use Baichuan\Library\Component\Logger\Log;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Utils\Arr;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class BaseExceptionHandler extends ExceptionHandler
{

    protected $ignoreExceptionList = [
        //ValidationException::class,
    ];

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 阻止異常冒泡
        $this->stopPropagation();
        // 格式化輸出
        $resource = $this->formatResource($throwable);
        if (!in_array(get_class($throwable), $this->ignoreExceptionList)) {
            Log::error($throwable->getMessage(), $throwable);
        }
        // 轉移至下一個異常處理器
        return $resource;
    }

    /**
     * 判斷該異常處理器是否要對該異常進行處理
     */
    public function isValid(Throwable $throwable): bool
    {
        return true;
    }

    protected function formatResource(Throwable $throwable): JsonResource
    {
        $statusCode = 500;
        $appCode = ErrorCodeEnum::FAIL;
        $message = $throwable->getMessage();
        if (method_exists($throwable, 'getAppCode')) {
            $appCode = $throwable->getAppCode();
        } elseif ($throwable->getCode()) {
            $message = "[" . $throwable->getCode() . "]" . $message;
        }
        if (method_exists($throwable, 'getStatusCode')) {
            $statusCode = $throwable->getStatusCode();
        }
        //if ($statusCode == 500 && match('official')) {
        //    $message = "【{$appCode}】system exception \n index ：" . Log::pullRequestId();
        //}
        $jsonResource = (new \Hyperf\Resource\Json\JsonResource(null))
            ->setAppCode($appCode)
            ->setMsg($message)
            ->setStatusCode($statusCode);
        if (!in_array(get_class($throwable), $this->ignoreExceptionList)) {
            $trace = $throwable->getTrace();
            $jsonResource->additional['trace'] = array_map(function ($item) {
                return Arr::except($item, 'args');
            }, $trace);
        }
        return $jsonResource;
    }

}
