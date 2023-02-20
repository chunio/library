<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\MultiTrait;

use Baichuan\Library\Component\Resource\JsonResource;
use Hyperf\HttpMessage\Base\Response;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

trait ResponseTrait
{

    /**
     * @param null $data
     * @param string $message
     * @return JsonResource
     * author : zengweitao@gmail.com
     * datetime: 2023/02/16 17:47
     * memo : null
     */
    protected function success($data = null, string $message = 'success')
    {
        /*****
        if ($data instanceof JsonResource) {
            $data->setMsg($message);
            return $data;
        }
        if ($data instanceof \Hyperf\Resource\Json\JsonResource) {
            return $data;
        }
        if ($data instanceof LengthAwarePaginatorInterface) {
            return JsonResource::collection($data)->setMsg($message);
        }
        *****/
        return (new JsonResource($data))->setMsg($message)->setPreserveKeys(true);
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
     * @param string $content
     * @return PsrResponseInterface
     * author : zengweitao@gmail.com
     * datetime: 2023/02/16 11:48
     * memo : 返回HTML
     */
    protected function html(string $content): PsrResponseInterface
    {
        return $this->raw($content, 'text/html; charset=utf8');
    }

    /**
     * @param string $content
     * @param $content_type
     * @return PsrResponseInterface
     * author : zengweitao@gmail.com
     * datetime: 2023/02/16 11:48
     * memo : 返回HTML
     */
    protected function raw(string $content, $content_type): PsrResponseInterface
    {
        $response = make(ResponseInterface::class);
        return $response->raw($content)->withHeader('Content-Type', $content_type);
    }
}
