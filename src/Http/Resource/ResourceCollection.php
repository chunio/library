<?php

declare(strict_types=1);

namespace Baichuan\Library\Http\Resource;

use Hyperf\Resource\Concerns\CollectsResources;
use Hyperf\Resource\Response\Response;
use Hyperf\Utils\Collection;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ResourceCollection
 * @package Baichuan\Library\Http\Resource
 * author : zengweitao@gmail.com
 * datetime: 2023/02/16 15:28
 * memo : 資源集合
 */
class ResourceCollection extends JsonResource
{
    use CollectsResources;

    /**
     * The resource that this resource collects.
     *
     * @var string
     */
    public $collects;

    /**
     * The mapped collection instance.
     *
     * @var Collection
     */
    public $collection;

    /**
     * Create a new resource instance.
     *
     * @param mixed $resource
     */
    public function __construct($resource)
    {
        parent::__construct($resource);

        $this->resource = $this->collectResource($resource);
    }

    /**
     * Return the count of items in the resource collection.
     */
    public function count(): int
    {
        return $this->collection->count();
    }

    /**
     * Transform the resource into a JSON array.
     */
    public function toArray(): array
    {
        /** @var Collection $collection */
        $collection = $this->collection->map->toArray();
        return $collection->all();
    }

    public function toResponse(): ResponseInterface
    {
        if ($this->isPaginatorResource($this->resource)) {
            return (new PaginatedResponse($this))->toResponse();
        }

        return (new Response($this))->toResponse();
    }
}
