<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;

/**
 * @Aspect
 */
class MongoAspect extends AbstractAspect
{
    public $classes = [
        "Hyperf\GoTask\MongoClient\MongoProxy::find",
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        monolog('process come in');
        return $proceedingJoinPoint->process();
    }
}
