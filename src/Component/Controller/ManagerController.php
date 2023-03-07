<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Controller;

use Baichuan\Library\Component\Logic\ManagerLogic;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Router\Router;

///**
// * @AutoController()
// */
class ManagerController extends AbstractController
{

    public static function addRoute()
    {
        Router::addRoute(['GET', 'POST'], '/manager/api_rank', [self::class, 'api_rank']);
    }

//    /**
//     * @RequestMapping(path="api_rank", methods="get,post")
//     */
    public function apiRank()
    {
        $result = make(ManagerLogic::class)->apiRank();
        return $this->success($result);
    }

}
