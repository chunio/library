<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Baichuan\Library\Handler\MonologHandler;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

/**
 * @Aspect
 */
class MongoAspect extends AbstractAspect
{
    public $classes = [
        //"Hyperf\GoTask\MongoClient\Collection::makePayload",
        "Hyperf\GoTask\GoTaskProxy::call",
    ];
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $payload = $proceedingJoinPoint->getArguments()[1];
        $command = \MongoDB\BSON\toPHP($payload);//TODO:待優化至採集可執行命令
        $start = microtime(true);
        $return = $proceedingJoinPoint->process();
        $end = microtime(true);
        MonologHandler::pushDBTrace(MonologHandler::$TRACE_EVENT['MONGODB'], prettyJsonEncode($command), $end - $start);
        return $return;
    }
}
