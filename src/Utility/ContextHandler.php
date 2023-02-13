<?php

declare(strict_types=1);

namespace Baichuan\Library\Utility;

use Baichuan\Library\Constant\ContextEnum;
use Hyperf\Context\Context;
use Hyperf\Utils\Str;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class ContextHandler
 * @package Baichuan\Library\Utility
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
            $requestAbstract =  [
                'api' => "(method:" . $Request->getMethod() . ")" . $Request->getUri()->__toString(),
                'header' => array_map(fn ($i) => count($i) == 1 ? $i[0] : $i, $Request->getHeaders()),
                'query' => commonJsonEncode($Request->getQueryParams()),
                'body' => commonJsonEncode($Request->getParsedBody()),
            ];
            unset($RequestClass);
        }
        return $requestAbstract ?? [];
    }

}