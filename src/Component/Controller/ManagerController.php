<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Controller;

use Baichuan\Library\Component\Logic\ManagerLogic;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * @AutoController()
 */
class ManagerController extends AbstractController
{

    /**
     * @RequestMapping(path="api_rank", methods="get,post")
     */
    public function apiRank()
    {
        $result = make(ManagerLogic::class)->apiRank();
        return $this->success($result);
    }

}
