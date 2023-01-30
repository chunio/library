<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Hyperf\Contract\ContainerInterface;

/**
 * Class StdoutLoggerFactory
 * @package Baichuan\Library\Component\Logger
 * author : zengweitao@gmail.com
 * datetime: 2023/01/30 10:51
 * memo : 將stdout日誌同步至文件
 */
class StdoutLoggerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return Log::get('sys');
    }
}
