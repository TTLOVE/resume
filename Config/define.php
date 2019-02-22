<?php

//公共常量
define('IS_POST','POST' === $_SERVER['REQUEST_METHOD']);
define('IS_GET','GET' === $_SERVER['REQUEST_METHOD']);
define('IS_AJAX', isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');

// 是否显示报错
// define("IS_ERROR", false);
define("IS_ERROR", true);
// 设置微信号信息
define("APP_ID", '123123123');
define("APP_SECRET", '111111');
// 生成token秘钥
define("ENCRYPT_KEY", 'u3%TW&YJFOyx');
