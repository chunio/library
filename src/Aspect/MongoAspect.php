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
            $command = $proceedingJoinPoint->getArguments();//payload//TODO:待優化至採集可執行命令
            //$command = filterControlCharacter(str_replace(['"'],'[x]', $command));
            monolog($command,'D01');
//            monolog([
//                '$command' => $command,
//            ],'D1');
            $start = microtime(true);
            monolog('A11111111');
            $return = $proceedingJoinPoint->process();
            monolog('B11111111');
            $end = microtime(true);

            MonologHandler::pushDBTrace(MonologHandler::$TRACE_EVENT['MONGODB'], $command, intval($end - $start));
            return $return;
        }catch (\Throwable $e){
            monolog($e);
        }
    }
}
