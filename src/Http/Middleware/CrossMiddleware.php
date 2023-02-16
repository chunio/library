<?php

declare(strict_types=1);

namespace Baichuan\Library\Http\Middleware;


use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Swoole\Http\Status;

/**
 * Class CrossMiddleware
 * author : zengweitao@gmail.com
 * datetime: 2023/02/16 14:11
 * memo : 開放跨越中間件
 */
class CrossMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (config('cross.button')) {
            $response = Context::get(ResponseInterface::class);
            foreach (config('cross.allow') as $key => $value) {
                if ($key === 'Access-Control-Allow-Origin'&& $value === '*') {
                    $response = $response->withHeader($key, $request->getHeaderLine('Origin'));
                } elseif ($key === 'Access-Control-Allow-Headers' && $value === '*') {
                    $response = $response->withHeader($key, $request->getHeaderLine('Access-Control-Request-Headers'));
                } else {
                    $response = $response->withHeader($key, $value);
                }
            }
            Context::set(ResponseInterface::class, $response);
            if ($request->getMethod() === 'OPTIONS') {
                return $response->withStatus(Status::NO_CONTENT);
            }
        }
        return $handler->handle($request);
    }
}
