<?php

namespace Controller;
use Model\ClientSession;
use Model\StoreMember;
use Model\AppUser;
use Model\StoreInfo;
use Service\View;
use Service\Mail;

/**
 * Class BaseController
 * @author xiaozhu
 */
class BaseController
{
    protected $view;
    protected $mail;

    public function __construct()
    {

    }

    // public function __destruct()
    // {

    //     // 导入页面
    //     $view = $this->view;

    //     if ( $view instanceof View ) {
    //         if (is_array($view->data)) {
    //             extract($view->data);
    //             echo $view->view->make($view->viewName, $view->data)->render();

    //         } else {
    //             echo $view->view->make($view->viewName)->render();
    //         }
    //     }
    // }

    // public function redirect($url)
    // {
    //     header("location: ".$url);
    //     exit;
    // }

    /**
     * 输出json数据
     * @param bool $status
     * @param string $msg
     * @param array $data
     */
    public function echoJson($status = true, $msg = '', $data = [])
    {
        $res = json_encode(['status' => $status, 'msg' => $msg, 'data' => $data]);
        exit($res);
    }
}
