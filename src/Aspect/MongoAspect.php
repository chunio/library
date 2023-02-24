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
        "Hyperf\GoTask\GoTaskProxy::call",
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        var_dump($proceedingJoinPoint->arguments);
        return $proceedingJoinPoint->process();
    }
}
