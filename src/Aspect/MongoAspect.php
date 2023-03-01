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
        "Hyperf\GoTask\MongoClient\Collection::makePayload",
    ];
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        try{
            [$partial, $option] = $proceedingJoinPoint->getArguments();//payload//TODO:待優化至採集可執行命令
            $command = prettyJsonEncode(['partial' => $partial, 'option' => $option]);
            $start = microtime(true);
            $return = $proceedingJoinPoint->process();
            $end = microtime(true);
            MonologHandler::pushDBTrace(MonologHandler::$TRACE_EVENT['MONGODB'], $command, $end - $start);
            monolog($return,'$return');
            return $return;
        }catch (\Throwable $e){
            monolog($e);
        }
    }
}
