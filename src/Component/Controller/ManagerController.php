<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Controller;

use Baichuan\Library\Component\Logic\ManagerLogic;
use Baichuan\Library\Handler\TraceHandler;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * @Controller()
 */
class ManagerController extends AbstractController
{

    /**
     * @RequestMapping(path="api_rank", methods="get,post")
     */
    public function ApiRank()
    {
        $result = make(ManagerLogic::class)->apiRank();
        return $this->success($result);
    }

}
