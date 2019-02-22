<?php

namespace Utils;

use \Curl\Curl;
use Utils\LogUtils;

class WechatUtils
{
    /**
     * encodingAesKey 非法
     *
     * @var int
     */
    const OK = 0;
    /**
     * aes 解密失败
     *
     * @var int
     */
    const ILLEGAL_AES_KEY = -41001;
    /**
     * 解密后得到的buffer非法
     *
     * @var int
     */
    const ILLEGAL_IV = -41002;
    /**
     * base64加密失败
     *
     * @var int
     */
    const ILLEGAL_BUFFER = -41003;
    /**
     * base64解密失败
     *
     * @var int
     */
    const DECODE_BASE64_ERROR = -41004;

    /**
     * 获取微信session_key
     *
     * @param string $appId
     * @param string $appSecret
     * @param string $jsCode
     *
     * @return string
     * @throws ConfigNotExistsException
     */
    public static function getWechatSessionKey($appId, $appSecret, $jsCode)
    {
        $url = 'https://api.weixin.qq.com/sns/jscode2session';

        $param = [
            'appid'      => $appId,
            'secret'     => $appSecret,
            'js_code'    => $jsCode,
            'grant_type' => 'authorization_code'
        ];

        $curl = new Curl();
        $responseData = $curl->get($url, $param);

        // 如果调用失败
        if ($curl->error) {
            $log = [
                'phpFile'      => __FILE__,
                'phpCodeLine'  => __LINE__,
                'error' => $curl->error
            ];
            LogUtils::addLog('AUTH', '获取微信sessionKey失败', $log);
            return '';
        }

        // 处理返回信息
        $responseData = json_decode($responseData, true);
        if (false === $responseData || isset($responseData['errcode'])) {
            $log = [
                'phpFile'      => __FILE__,
                'phpCodeLine'  => __LINE__,
                'requestData' => $param,
                'responseData' => $responseData
            ];
            LogUtils::addLog('AUTH', '获取微信sessionKey失败', $log);
            return '';
        }

        return $responseData;
    }

    /**
     * 检验数据的真实性，并且获取解密后的明文.
     *
     * @param string $appId         小程序的appid
     * @param string $sessionKey    用户在小程序登录后获取的会话密钥
     * @param string $encryptedData 加密的用户数据
     * @param string $iv            与用户数据一同返回的初始向量
     *
     * @return array
     */
    public static function decryptData($appId, $sessionKey, $encryptedData, $iv)
    {
        if (strlen($sessionKey) != 24) {
            $data = [
                'appId'         => $appId,
                'sessionKey'    => $sessionKey,
                'encryptedData' => $encryptedData,
                'iv'            => $iv,
                'msg'           => 'sessionKey != 24'
            ];
            return $data;
        }
        $aesKey = \base64_decode($sessionKey);

        if (strlen($iv) != 24) {
            $data = [
                'appId'         => $appId,
                'sessionKey'    => $sessionKey,
                'encryptedData' => $encryptedData,
                'iv'            => $iv,
                'msg' => 'iv != 24'
            ];
            return $data;
        }
        $aesIV = \base64_decode($iv);

        $aesCipher = \base64_decode($encryptedData);

        $result = \openssl_decrypt($aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);

        $data = \json_decode($result, true);
        if (false == $data) {
            $data = [
                'appId'         => $appId,
                'sessionKey'    => $sessionKey,
                'encryptedData' => $encryptedData,
                'iv'            => $iv,
                'msg' => 'json_decode = false'
            ];
            return $data;
        }
        if ($data['watermark']['appid'] != $appId) {
            $data = [
                'appId'         => $appId,
                'sessionKey'    => $sessionKey,
                'encryptedData' => $encryptedData,
                'iv'            => $iv,
                'msg' => 'watermark.appid != $appid'
            ];
            return $data;
        }

        return $data;
    }
}