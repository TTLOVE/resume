<?php

use \NoahBuscher\Macaw\Macaw;
use Controller\BaseController;

// 接口
Macaw::post('v1/authorization', 'Controller\Auth\AuthController@authorization'); // 授权
Macaw::get('v1/user', 'Controller\User\ApiUserController@getUserInfo'); // 获取用户信息


Macaw::$error_callback = function() {
    (new BaseController())->echoJson(false, 'api_not_exit');
};

Macaw::dispatch();