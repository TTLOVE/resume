<?php

namespace Controller\User;

use Controller\ApiBaseController;
use Model\User\User;

/**
 * Class WeixinFeedbackController 留言控制器
 * @author yao
 */
class ApiUserController extends ApiBaseController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo()
    {
        // 获取用户id
        $userId = $this->getUserId();
        // 根据用户id获取用户信息
        $userInfo = (new User())->getUserInfoById($userId);
        $this->echoJson(true, 'success', $userInfo);
    }
}
