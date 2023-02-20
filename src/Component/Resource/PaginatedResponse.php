<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Resource;

/**
 * 分页资源获取.
 *
 * Class PaginatedResponse
 */
class PaginatedResponse extends \Hyperf\Resource\Response\PaginatedResponse
{
    /**
     * Add the pagination information to the response.
     */
    protected function paginationInformation(): array
    {
        $paginated = $this->resource->resource->toArray();

        return [
            'data' => $this->paginationPages($paginated),
        ];
    }

    /**
     * 分页格式.
     *
     * @author: jiaying.yang@happy-seed.com
     */
    public function paginationPages(array $paginated): array
    {
        return [
            'page' => $paginated['current_page'] ?? 0,
            'page_size' => $paginated['per_page'] ?? 0,
            'total' => $paginated['total'] ?? 0,
        ];
    }
}
