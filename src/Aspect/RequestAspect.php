<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Baichuan\Library\Component\Monolog\MonologHandler;
use Baichuan\Library\Constant\ContextEnum;
use Baichuan\Library\Utility\ContextHandler;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Context\Context;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;

/**
 * @Aspect
 */
class RequestAspect extends AbstractAspect
{
    public $classes = [
        'Hyperf\HttpServer\Server::onRequest',
        'Hyperf\HttpServer\CoreMiddleware::dispatch',
        'Hyperf\HttpServer\ResponseEmitter::emit',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        // 記錄請求開始時間
        if ($proceedingJoinPoint->className == 'Hyperf\HttpServer\Server' && $proceedingJoinPoint->methodName == 'onRequest') {
            Context::set(ContextEnum::RequestStartMicroTime, microtime(true));
            /** @var Request $swooleRequest */
            //$swooleRequest = $proceedingJoinPoint->getArguments()[0];
            //$requestId = matchNonNullValue('x-request-id', $swooleRequest->header, $swooleRequest->get);
            MonologHandler::info('Hyperf\HttpServer\Server::onRequest');
        }
        //  打印請求內容
        if ($proceedingJoinPoint->className == 'Hyperf\HttpServer\CoreMiddleware' && $proceedingJoinPoint->methodName == 'dispatch') {
            $result = $proceedingJoinPoint->process();
            return $result;
        }
        $result = $proceedingJoinPoint->process();
        // 打印響應內容
        if ($proceedingJoinPoint->className == 'Hyperf\HttpServer\ResponseEmitter' && $proceedingJoinPoint->methodName == 'emit') {
            //$request = Context::get(ServerRequestInterface::class);
            //$response = $proceedingJoinPoint->getArguments()[0];
            return $result;
        }
        return $result;
    }

}
