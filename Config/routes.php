<?php

use \NoahBuscher\Macaw\Macaw;
use Controller\BaseController;

// 页面
Macaw::post('v1/authorization', 'Controller\AuthController@authorization'); // 授权


Macaw::$error_callback = function() {
    (new BaseController())->echoJson(false, '没有对应接口');
};

Macaw::dispatch();
