<?php

namespace Controller;

use Service\Auth\AuthService;

/**
 * Class ApiBaseController 微信页面的父类
 */
class ApiBaseController extends BaseController
{
    /**
     * 用户token
     */
    private static $userId = 0;

    public function __construct($tmp=false)
    {
        $userToken = $this->getUserToken();
        // 如果头部信息不为空
        if (empty($userToken))  { 
            $this->echoJson(false, 'token_not_exit');
            exit();
        }

        // 验证token
        if ($tmp==false) {
            $authRes = (new AuthService())->authToken($userToken);
        } else {
            $authRes = (new AuthService())->authTmpToken($userToken);
        }

        if ($authRes['status']==false) {
            $this->echoJson($authRes['status'], $authRes['msg']);
            exit();
        }
 
        self::$userId = $authRes['data']['userId'];
    }

    /**
     * 获取用户token信息
     */
    private function getUserToken() {
        return isset($_SERVER['HTTP_USER_TOKEN']) ? trim($_SERVER['HTTP_USER_TOKEN']) : '';
    }

    /**
     * 获取用户id
     */
    public function getUserId() {
        return self::$userId;
    }
}
