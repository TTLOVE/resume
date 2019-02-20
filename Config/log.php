<?php

if ( isset ($_SERVER ['RUNTIME_ENVIROMENT']) && !empty ($_SERVER ['RUNTIME_ENVIROMENT']) && $_SERVER ['RUNTIME_ENVIROMENT'] == 'DEV' ) {
    $logPath = '/tmp/finance/'; // 设置日志记录位置
    return [
        'userInfo' => $logPath . 'userInfo/', // 用户信息
        'borrow' => $logPath . 'borrow/', // 借款信息
        'notify' => $logPath . 'notify/', // 回调信息
        'klPay' => $logPath . 'klPay/', // 钱包信息
        'product' => $logPath . 'product/', // 产品信息
        'repay' => $logPath . 'repay/', // 还款信息
        'klApi' => $logPath . 'klApi/', // klapi接口信息
        'message' => $logPath . 'message/', // 消息接口信息
    ];
} elseif ( isset ($_SERVER ['RUNTIME_ENVIROMENT']) && !empty ($_SERVER ['RUNTIME_ENVIROMENT']) && $_SERVER ['RUNTIME_ENVIROMENT'] == 'DOCKER' ) {
    $logPath = '/tmp/finance/'; // 设置日志记录位置
    return [
        'userInfo' => $logPath . 'userInfo/', // 用户信息
        'borrow' => $logPath . 'borrow/', // 借款信息
        'notify' => $logPath . 'notify/', // 回调信息
        'klPay' => $logPath . 'klPay/', // 钱包信息
        'product' => $logPath . 'product/', // 产品信息
        'repay' => $logPath . 'repay/', // 还款信息
        'klApi' => $logPath . 'klApi/', // klapi接口信息
        'message' => $logPath . 'message/', // 消息接口信息
    ];
} else {
    $logPath = '/data/tmp/finance/'; // 设置日志记录位置
    return [
        'userInfo' => $logPath . 'userInfo/', // 用户信息
        'borrow' => $logPath . 'borrow/', // 借款信息
        'notify' => $logPath . 'notify/', // 回调信息
        'klPay' => $logPath . 'klPay/', // 钱包信息
        'product' => $logPath . 'product/', // 产品信息
        'repay' => $logPath . 'repay/', // 还款信息
        'klApi' => $logPath . 'klApi/', // klapi接口信息
        'message' => $logPath . 'message/', // 消息接口信息
    ];
}

