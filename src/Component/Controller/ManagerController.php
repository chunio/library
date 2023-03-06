<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Controller;

use App\Logic\ManagerLogic;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Router\Router;

/**
 * @AutoController()
 */
class ManagerController extends AbstractController
{

    public static function addRoutes()
    {
        Router::addRoute(['GET', 'POST'], '/manager', [self::class, 'api_rank']);
    }
    /**
     * @RequestMapping(path="api_rank", methods="get,post")
     */
    public function ApiRank()
    {
        make(ManagerLogic::class)->apiRank();
        return $this->success(1);
    }

}
