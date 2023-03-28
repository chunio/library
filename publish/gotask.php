<?php

declare(strict_types=1);

use Baichuan\Library\Handler\UtilityHandler;

return [
    'enable' => env('GOTASK_ENABLE', true),
    'executable' => BASE_PATH . '/bin/app',
    'socket_address' => env('GOTASK_SOCKET_ADDR', \Hyperf\GoTask\ConfigProvider::address()),
    'go2php' => [
        'enable' => true,
        'address' => '127.0.0.1:6002',
    ],
    'go_build' => [
        'enable' => UtilityHandler::matchEnvi('local') && system('command -v go'),
        'workdir' => BASE_PATH . '/gotask',
        'command' => 'go build -o ../bin/app cmd/app.go',
    ],
    'go_log' => [
        'redirect' => true,
        'level' => 'info',
    ],
    'pool' => [
        'min_connections' => 1,
        'max_connections' => 30,
        'connect_timeout' => 10.0,
        'wait_timeout' => 30.0,
        'heartbeat' => -1,
        'max_idle_time' => (float)env('GOTASK_MAX_IDLE_TIME', 60),
    ],
];
