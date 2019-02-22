<?php

if ( isset ($_SERVER ['RUNTIME_ENVIROMENT']) && !empty ($_SERVER ['RUNTIME_ENVIROMENT']) && $_SERVER ['RUNTIME_ENVIROMENT'] == 'DEV' ) {
    $logPath = '/tmp/finance/'; // 设置日志记录位置
    return [
        'auth' => $logPath . 'auth/', // 用户授权
    ];
} elseif ( isset ($_SERVER ['RUNTIME_ENVIROMENT']) && !empty ($_SERVER ['RUNTIME_ENVIROMENT']) && $_SERVER ['RUNTIME_ENVIROMENT'] == 'DOCKER' ) {
    $logPath = '/tmp/finance/'; // 设置日志记录位置
    return [
        'auth' => $logPath . 'auth/', // 用户授权
    ];
} else {
    $logPath = '/data/tmp/finance/'; // 设置日志记录位置
    return [
        'auth' => $logPath . 'auth/', // 用户授权
    ];
}

