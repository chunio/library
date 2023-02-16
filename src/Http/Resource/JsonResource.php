<?php

declare(strict_types=1);

namespace Baichuan\Library\Http\Resource;

use Baichuan\Library\Constant\ContextEnum;
use Baichuan\Library\Utility\ContextHandler;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Swoole\Http\Status;

class JsonResource extends \Hyperf\Resource\Json\JsonResource
{
    protected int $statusCode = Status::OK;

    protected string $reasonPhrase = '';

    protected int $appCode = 0;

    protected string $msg = "success";//DEBUG_LABEL

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

    /**
     * Transform the resource into an array.
     */
    public function toArray(): array
    {
        if (is_null($this->resource) || is_string($this->resource) || /*is_numeric($this->resource) ||*/ is_bool($this->resource)) {
            return ['//////'];
        }

        return is_array($this->resource)//
            ? $this->resource
            : (method_exists($this->resource, 'toArray') ? $this->resource->toArray() : ['2222222222222']);
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
        return [
            'status' => $this->getStatusCode(),
            'code' => $this->getAppCode(),
            'message' => $this->getMsg(),
            'timestamp' => time(),
            'elapsedTime' => $requestStartMicroTime ? floatval(number_format((microtime(true) - $requestStartMicroTime), 5,'.','')) : null,//DEBUG_LABEL
            'traceId' => ContextHandler::pullTraceId(),
        ];
    }

    /**
     * Resolve the resource to an array.
     */
    public function resolve(): array
    {
        $data = $this->toArray();

        // 如果是集合资源型，则用list
        if ($this instanceof ResourceCollection) {
            $data = ['list' => $data];
        }

        return $this->filter((array)$data);
    }

    public function toResponse(): ResponseInterface
    {
        return parent::toResponse()->withStatus($this->getStatusCode(), $this->getReasonPhrase());
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

    /**
     * @return JsonResource
     */
    public function setMsg(string $msg): self
    {
        $this->msg = $msg;

        return $this;
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
