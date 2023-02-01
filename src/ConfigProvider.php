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
            'logger' => [
                'default' => [
                    //支持「handlers」，參見：https://hyperf.wiki/2.2/#/zh-cn/logger
                    'handler' => [
                        'class' => \Monolog\Handler\RotatingFileHandler::class,
                        'constructor' => [
                            'filename' => BASE_PATH . '/runtime/logs/hyperf.log',//日誌文件
                            'level' => \Monolog\Logger::DEBUG,
                        ],
                    ],
                    'formatter' => [
                        'class' => \Monolog\Formatter\LineFormatter::class,
                        'constructor' => [
                            'format' => null,
                            'dateFormat' => 'Y-m-d H:i:s',
                            'allowInlineLineBreaks' => true,
                            'includeStacktraces' => true,
                        ],
                    ],
                    /*****
                    'formatter' => [
                    //'class' => \Monolog\Formatter\JsonFormatter::class,
                    'class' => Baichuan\Library\Component\Logger\CustomJsonFormatter::class,
                    'constructor' => [],
                    ],
                     *****/
                ],
            ],
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
            //複製文件（受限於框架啟動順序，部分基礎配置不適用於publish，如：logger.php）
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
            ],
            'macros' => [
            ],
        ];
    }
}
