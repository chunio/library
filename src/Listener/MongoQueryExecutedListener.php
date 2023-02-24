<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Baichuan\Library\Listener;

use Baichuan\Library\Handler\MongoHandler;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use MongoDB\Driver\Command;
use Psr\Container\ContainerInterface;

/**
 * @Listener
 */
class MongoQueryExecutedListener implements ListenerInterface
{

//    /**
//     * @var ContainerInterface
//     */
//    private $container;
//
//    public function __construct(ContainerInterface $container)
//    {
//        $this->container = $container;
//    }

    public function listen(): array
    {
        return [
            MongoHandler::class,
        ];
    }

    /**
     * @param Command $event
     */
    public function process(object $event)
    {
        if (/*matchEnvi('local') && */$event instanceof MongoHandler) {
            monolog('MongoQueryExecutedListener come in');
            //MonologHandler::pushCustomTrace(__FUNCTION__, json_encode($event), 0);
        }
    }
}
