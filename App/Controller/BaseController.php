<?php

namespace Controller;

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
        echo $res;
        fastcgi_finish_request();
    }
}
