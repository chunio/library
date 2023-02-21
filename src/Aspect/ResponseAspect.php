<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Resource\Json\JsonResource;
use Hyperf\Resource\Response\Response;

/**
 * @Aspect
 * Class ResponseAspect
 * @package Component\Hyperf\Aspect
 * author : zengweitao@gmail.com
 * datetime: 2023/02/21 20:35
 * memo : 修復JsonResource無法輸出null/bool/float/integer/string/...
 */
class ResponseAspect extends AbstractAspect
{

    public $classes = [
        'Hyperf\Resource\Response\Response::wrap',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        /** @var Response $instance */
        $instance = $proceedingJoinPoint->getInstance();
        $return = $proceedingJoinPoint->process();
        if ($instance->resource instanceof JsonResource) {
            if ($instance->resource->wrap) {
                $resource = $instance->resource->resource;
                if (is_null($resource) || is_string($resource) || is_numeric($resource) || is_bool($resource)) {
                    $return[$instance->resource->wrap] = $resource;
                }
            }
            return $return;
        }
        return $return;
    }

}
