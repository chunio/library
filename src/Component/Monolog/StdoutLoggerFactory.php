<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Hyperf\Contract\ContainerInterface;

/**
 * Class StdoutLoggerFactory
 * @package Baichuan\Library\Component\Monolog
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 10:51
 * memo : 將stdout日誌同步至文件
 */
class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return MonologHandler::instance('system');
    }
}
