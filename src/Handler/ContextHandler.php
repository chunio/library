<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Baichuan\Library\Constant\ContextEnum;
use Hyperf\Context\Context;
use Hyperf\Utils\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ContextHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 13:50
 * memo : 上下文管理器
 */
class ContextHandler
{

    public static function pullTraceId(): string
    {
        //TODO:待擴展關聯nginx傳入x-request-id
        if (!($traceId = Context::get(ContextEnum::TraceId))) {
            $traceId = Str::random(32);
            Context::set(ContextEnum::TraceId, $traceId);
        }
        return $traceId;
    }

    public static function pullRequestAbstract(): array
    {
        if(
            (!$requestAbstract = Context::get(ContextEnum::RequestAbstract)) &&
            ($Request = Context::get(ServerRequestInterface::class))
        ){
            $header = array_map(fn ($v) => count($v) === 1 ? $v[0] : $v, $Request->getHeaders());
            unset($header['user-agent']);//ignore
            $requestAbstract = [
                'api' => explode('?', $Request->getUri()->__toString())[0],
                'method' => $Request->getMethod(),
                'header' => $header,
                'query' => UtilityHandler::prettyJsonEncode($Request->getQueryParams()),
                'body' => UtilityHandler::prettyJsonEncode($Request->getParsedBody()),
            ];
            unset($RequestClass);
        }
        return $requestAbstract ?? [];
    }

}