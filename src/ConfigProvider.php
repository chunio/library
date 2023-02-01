<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @see     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 *
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Baichuan\Library;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            //合併變量
            'dependencies' => [
                //PingServiceInterface::class => PingService::class,
            ],
            'processes' => [
            ],
            'commands' => [
            ],
            'aspects' => [
            ],
            'listeners' => [
            ],
            'annotations' => [
                'scan' => [
                    'collectors' => [
                        //ErrorCodeCollector::class,
                        //WsMiddlewareAnnotationCollector::class,
                    ],
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map' => [
                        // 需映射的類名 => 類所在的文件路徑
                        //\Hyperf\Amqp\ConsumerManager::class => __DIR__ . '/class_map/Hyperf/Amqp/ConsumerManager.php',
                        //\Hyperf\SocketIOServer\Emitter\Emitter::class => __DIR__ . '/class_map/Hyperf/SocketIOServer/Emitter/Emitter.php',
                        //\Mix\Redis\Subscribe\Subscriber::class => __DIR__ . '/class_map/Mix/Redis/Subscribe/Subscriber.php',
                    ],
                ],
            ],
            //複製文件
            'publish' => [
                [
                    'id' => 'config',
                    'description' => '備註信息1',
                    'source' => __DIR__ . '/../publish/example1.php',
                    'destination' => BASE_PATH . '/config/autoload/example1.php',
                ],
                [
                    'id' => 'config',
                    'description' => '備註信息2',
                    'source' => __DIR__ . '/../publish/example2.php',
                    'destination' => BASE_PATH . '/config/autoload/example2.php',
                ],
                [
                    'id' => 'config',
                    'description' => '日誌',
                    'source' => __DIR__ . '/../publish/logger.php',
                    'destination' => BASE_PATH . '/config/autoload/logger.php',
                ],
            ],
            'macros' => [
            ],
        ];
    }
}
