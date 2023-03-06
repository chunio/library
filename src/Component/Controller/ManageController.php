<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Controller;

use App\Logic\ManageLogic;
use Baichuan\Library\Component\Resource\JsonResource;
use Baichuan\Library\Handler\TraceHandler;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Annotation\RequestMapping;

/**
 * @AutoController()
 */
class ManageController extends AbstractController
{

    /**
     * @RequestMapping(path="api_rank", methods="get,post")
     */
    public function ApiRank()
    {
        make(ManageLogic::class)->apiRank();
        return $this->success(1);
    }

}
