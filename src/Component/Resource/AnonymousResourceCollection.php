<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Resource;

/**
 * Class AnonymousResourceCollection.
 */
class AnonymousResourceCollection extends ResourceCollection
{
    /**
     * The name of the resource being collected.
     *
     * @var string
     */
    public $collects;

    /**
     * Create a new anonymous resource collection.
     *
     * @param mixed $resource
     */
    public function __construct($resource, string $collects)
    {
        $this->collects = $collects;

        parent::__construct($resource);//
    }
}
