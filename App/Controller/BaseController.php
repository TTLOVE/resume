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
 */
class BaseController
{
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
