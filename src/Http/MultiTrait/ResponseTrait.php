<?php

declare(strict_types=1);

namespace Baichuan\Library\Http\MultiTrait;

use Baichuan\Library\Http\Resource\JsonResource;
use Baichuan\Library\Http\Resource\ResourceCollection;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\HttpMessage\Base\Response;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

trait ResponseTrait
{

    /**
     * @param mixed $data
     * @return \Hyperf\Resource\Json\JsonResource|JsonResource|ResourceCollection
     */
    protected function success($data = null, string $msg = 'success')
    {
        if ($data instanceof \Baichuan\Library\Http\Resource\JsonResource) {
            $data->setMsg($msg);
            return $data;
        }
        if ($data instanceof \Hyperf\Resource\Json\JsonResource) {
            return $data;
        }
        if ($data instanceof LengthAwarePaginatorInterface) {
            return JsonResource::collection($data)->setMsg($msg);
        }
        return (new JsonResource($data))->setMsg($msg)->setPreserveKeys(true);
    }

    /**
     * @param int $code
     * @param string $msg
     */
    protected function fail($code = 120, $msg = 'failure'): JsonResource
    {
        return (new JsonResource(null))->setAppCode($code)->setMsg($msg);
    }

    /**
     * 纯 json，无包装.
     * @param null $data
     * @param array $headers
     * @param int $status
     * @param string $reasonPhrase
     */
    protected function jsonRaw($data = null, $headers = [], $status = 200, $reasonPhrase = ''): PsrResponseInterface
    {
        if (!$reasonPhrase) {
            new \Exception("reason phrase should not be empty for custom status code / " . Response::getReasonPhraseByCode($status));
        }
        $rtn = make(ResponseInterface::class)->json($data)->withStatus($status, $reasonPhrase);

        foreach ($headers ?? [] as $k => $v) {
            $rtn = $rtn->withHeader($k, $v);
        }

        return $rtn;
    }

    /**
     * 返回 Html
     *
     * @author Mai Zhong Wen <yshxinjian@gmail.com>
     */
    protected function html(string $content): PsrResponseInterface
    {
        return $this->raw($content, 'text/html; charset=utf8');
    }

    /**
     * 返回 Html
     *
     * @author Mai Zhong Wen <yshxinjian@gmail.com>
     *
     * @param mixed $content_type
     */
    protected function raw(string $content, $content_type): PsrResponseInterface
    {
        $response = make(ResponseInterface::class);
        return $response->raw($content)->withHeader('Content-Type', $content_type);
    }
}
