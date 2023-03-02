<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Baichuan\Library\Handler\TraceHandler;
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
        $payloadBson = $proceedingJoinPoint->getArguments()[1];
        $payloadArray = \MongoDB\BSON\toPHP($payloadBson);//TODO:待優化至採集可執行命令
        $start = microtime(true);
        $return = $proceedingJoinPoint->process();
        $end = microtime(true);
        TraceHandler::push([
            'command' => prettyJsonEncode($payloadArray),
            'unitElapsedTime' => floatval(number_format($end - $start,5,'.',''))
        ], 'mongodb', TraceHandler::EVENT['SERVICE']);
        return $return;
    }
}
