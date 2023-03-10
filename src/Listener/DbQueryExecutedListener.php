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

use Baichuan\Library\Handler\TraceHandler;
use Baichuan\Library\Handler\UtilityHandler;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;

/**
 * @Listener
 */
class DbQueryExecutedListener implements ListenerInterface
{

    public function __construct(/*ContainerInterface $container*/)
    {
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param QueryExecuted $event
     */
    public function process(object $event)
    {
        if (UtilityHandler::matchEnvi('local') && $event instanceof QueryExecuted) {
            $sql = $event->sql;
            if (!Arr::isAssoc($event->bindings)) {
                foreach ($event->bindings as $key => $value) {
                    $sql = Str::replaceFirst('?', "'{$value}'", $sql);
                }
            }
            TraceHandler::push([
                'command'/*如：sql*/ => $sql,
                'unitElapsedTime' => floatval(number_format($event->time / 1000,5,'.',''))
            ], 'mysql', TraceHandler::EVENT['SERVICE']);
        }
    }

}
