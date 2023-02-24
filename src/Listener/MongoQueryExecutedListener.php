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

namespace App\Listener;

use Baichuan\Library\Handler\MonologHandler;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use MongoDB\Driver\Command;

/**
 * @Listener
 */
class MongoQueryExecutedListener implements ListenerInterface
{
    public function __construct(/*ContainerInterface $container*/)
    {
    }

    public function listen(): array
    {
        return [
            Command::class,
        ];
    }

    /**
     * @param Command $event
     */
    public function process(object $event)
    {
        if (matchEnvi('local') && $event instanceof Command) {
            monolog('MongoQueryExecutedListener come in');
            //MonologHandler::pushCustomTrace(__FUNCTION__, json_encode($event), 0);
        }
    }
}
