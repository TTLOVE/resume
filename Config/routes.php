<?php

use \NoahBuscher\Macaw\Macaw;
use Controller\BaseController;
use Utils\LogUtils;

// 接口
Macaw::post('v1/authorization', 'Controller\Auth\AuthController@authorization'); // 授权
Macaw::get('v1/user', 'Controller\User\ApiUserController@getUserInfo'); // 获取用户信息


Macaw::$error_callback = function() {
    (new BaseController())->echoJson(false, '没有对应接口');
};

Macaw::dispatch();

LogUtils::flushLog();