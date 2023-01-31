<?php

declare(strict_types=1);

namespace Baichuan\Library\Aspect;

use Baichuan\Library\Component\Logger\Log;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Context\Context;
use Psr\Http\Message\ResponseInterface;
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
            Context::set("requestStartMicroTime", microtime(true));
            /** @var Request $swooleRequest */
            $swooleRequest = $proceedingJoinPoint->getArguments()[0];
            $requestId = matchNonNullValue('x-request-id', $swooleRequest->header, $swooleRequest->get);
            // 設置{$requestId}至請求上下文
            Context::set('requestId', $requestId);
            $url = $swooleRequest->server['request_uri'];
            Context::set('api.url', $url);
            $method = $swooleRequest->server['request_method'];
            Context::set('api.method', $method);
        }
        //  打印請求內容
        if ($proceedingJoinPoint->className == 'Hyperf\HttpServer\CoreMiddleware' && $proceedingJoinPoint->methodName == 'dispatch') {
            $res = $proceedingJoinPoint->process();
            //xdebug($request,'$request0');
            //$this->logRequest($request);
            $request = Context::get(ServerRequestInterface::class);
            Log::info($request);
            return $res;
        }
        $res = $proceedingJoinPoint->process();
        // 打印響應內容
        if ($proceedingJoinPoint->className == 'Hyperf\HttpServer\ResponseEmitter' && $proceedingJoinPoint->methodName == 'emit') {
            $request = Context::get(ServerRequestInterface::class);
            $response = $proceedingJoinPoint->getArguments()[0];
            //xdebug($response,'$response0');
            //$this->logResponse($request, $response);
            //Log::info('response', $response);
            return $res;
        }
        return $res;
    }

    public function isExceptUriPath(ServerRequestInterface $request): bool
    {
        $exceptMethods = config('log.request.except.methods', []);
        $exceptUriPath = config('log.request.except.uris', []);

        return in_array(strtoupper($request->getMethod()), $exceptMethods)
            || in_array($request->getUri()->getPath(), $exceptUriPath);
    }

    private function formatRequest(): array
    {
        $request = Context::get(ServerRequestInterface::class);
        $body = prettyJsonEncode($request->getParsedBody());
        $body = $this->trimByMaxLength('request', $body);
        return [
            'api' => "[" . $request->getMethod() . "]" . $request->getUri()->__toString() . $request->getUri()->getPath(),
            'header' => $this->simplifyHeaders($request->getHeaders()),
            'query' => prettyJsonEncode($request->getQueryParams()),
            'body' => $body,
        ];
    }

    private function logResponse(ServerRequestInterface $request, ResponseInterface $response)
    {
        if ($this->isExceptUriPath($request)) {
            return;
        }

        $hasError = $response->getStatusCode() >= 500;
        if ($hasError || 1) {
            $body = $response->getBody()->getContents();
            $isJson = false !== strpos($response->getHeaderLine('Content-Type'), 'application/json');
            $json = $isJson ? json_decode($body, true) : [];

            $body = $isJson ? prettyJsonEncode($json) : 'Content-Type : ' . $response->getHeaderLine('Content-Type');
            $body = $this->trimByMaxLength('response', $body);

            $responseInfo = [
                'body' => $body,
                'app_code' => $json['code'] ?? null,
                'status' => $response->getStatusCode(),
                'elapsed' => $json['elapsed'] ?? round(microtime(true) - Context::get('request_start_at'), 6),
            ];

            Log::info("--", [//DEBUG_LABEL
                'resp' => $responseInfo,
            ]);
        } else {
            $responseInfo = [
                'body' => "无错误，忽略显示内容",
                'app_code' => 0,
                'status' => $response->getStatusCode(),
                'elapsed' => round(microtime(true) - Context::get('request_start_at'), 6),
            ];
            Log::info("发送响应", [
                'resp' => $responseInfo,
            ]);
        }
    }

    private function trimByMaxLength(string $type, ?string $content): string
    {
        if (!$content) {
            return '';
        }
        // 长度截断
        if (config('log.request.max_len.' . $type, 0) > 0) {
            $len = config('log.request.max_len.' . $type);
            if (strlen($content) >= $len) {
                $content = mb_substr($content, 0, $len, 'utf-8') . '...';
            }
        }

        return $content;
    }

    private function simplifyHeaders(array $headers)
    {
        return array_map(fn ($i) => 1 == count($i) ? $i[0] : $i, $headers);
    }
}
