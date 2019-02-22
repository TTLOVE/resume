<?php

if ( isset ($_SERVER ['RUNTIME_ENVIROMENT']) && !empty ($_SERVER ['RUNTIME_ENVIROMENT']) && $_SERVER ['RUNTIME_ENVIROMENT'] == 'DEV' ) {
    $logPath = '/tmp/resume/'; // 设置日志记录位置
    return [
        'auth' => $logPath . 'auth/', // 用户授权
    ];
} elseif ( isset ($_SERVER ['RUNTIME_ENVIROMENT']) && !empty ($_SERVER ['RUNTIME_ENVIROMENT']) && $_SERVER ['RUNTIME_ENVIROMENT'] == 'DOCKER' ) {
    $logPath = '/tmp/resume/'; // 设置日志记录位置
    return [
        'auth' => $logPath . 'auth/', // 用户授权
    ];
} else {
    // $logPath = '/data/tmp/resume/'; // 设置日志记录位置
    $logPath = '/tmp/resume/'; // 设置日志记录位置
    return [
        'auth' => $logPath . 'auth/', // 用户授权
    ];
}

