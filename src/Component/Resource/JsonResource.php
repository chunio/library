<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Resource;

use Baichuan\Library\Constant\ContextEnum;
use Baichuan\Library\Handler\ContextHandler;
use Baichuan\Library\Handler\TraceHandler;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Status;

/**
 * Class JsonResource
 * @package Baichuan\Library\Http\Resource
 * author : zengweitao@gmail.com
 * datetime: 2023/02/16 17:48
 * memo : 執行順序：setMsg()>>toResponse()>>resolve()>>toArray()>>with()
 */
class JsonResource extends \Hyperf\Resource\Json\JsonResource
{

    protected int $statusCode = Status::OK;
    protected string $reasonPhrase = '';
    protected int $appCode = 0;
    protected string $msg = "success";//DEBUG_LABEL

    /**
     * @return JsonResource
     */
    public function setMsg(string $msg): self
    {
        $this->msg = $msg;

        return $this;
    }

    public function toResponse(): ResponseInterface
    {
        return parent::toResponse()->withStatus($this->getStatusCode(), $this->getReasonPhrase());
    }

    /**
     * Resolve the resource to an array.
     */
    public function resolve(): array
    {
        $data = $this->toArray();
        // 如是集合資源型
        if ($this instanceof ResourceCollection) {
            $data = ['list' => $data];
        }
        return $this->filter($data);
    }

    /**
     * Transform the resource into an array.
     */
    public function toArray(): array
    {
//        MonologHandler::info($this->resource,'$this->resource');
//        if (is_null($this->resource) || is_string($this->resource) || /*is_numeric($this->resource) ||*/ is_bool($this->resource)) {
//            return ['//////'];
//        }
//        return is_array($this->resource)//
//            ? $this->resource
//            : (method_exists($this->resource, 'toArray') ? $this->resource->toArray() : ['2222222222222']);
        return method_exists($this->resource, 'toArray') ? $this->resource->toArray() : [$this->resource];
    }

    /**
     * @return array
     * author : zengweitao@gmail.com
     * datetime: 2023/02/16 11:44
     * memo : null
     */
    public function with(): array
    {
        $requestStartMicroTime = Context::get(ContextEnum::RequestStartMicroTime);
        $elapsedTime = floatval(number_format((microtime(true) - $requestStartMicroTime), 5,'.',''));
        return [
            'status' => $this->getStatusCode(),
            'code' => $this->getAppCode(),
            'message' => $this->getMsg(),
            'timestamp' => time(),
            'elapsedTime' => $requestStartMicroTime ? $elapsedTime : null,//DEBUG_LABEL
            'traceId' => ContextHandler::pullTraceId(),
        ];
    }

    /**
     * Create new anonymous resource collection.
     *
     * @param mixed $resource
     *
     * @return AnonymousResourceCollection
     */
    public static function collection($resource): ?AnonymousResourceCollection
    {
        return tap(new AnonymousResourceCollection($resource, static::class), function ($collection) {
            $collection->preserveKeys = (new static([]))->preserveKeys;
        });
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    /**
     * @return $this
     */
    public function setReasonPhrase(string $reasonPhrase): self
    {
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    public function getAppCode(): int
    {
        return $this->appCode;
    }

    /**
     * @return JsonResource
     */
    public function setAppCode(int $appCode): self
    {
        $this->appCode = $appCode;

        return $this;
    }

    public function getMsg(): string
    {
        return $this->msg;
    }

    public function isPreserveKeys(): bool
    {
        return $this->preserveKeys;
    }

    public function setPreserveKeys(bool $preserveKeys): self
    {
        $this->preserveKeys = $preserveKeys;

        return $this;
    }
}
