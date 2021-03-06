<?php

namespace Controller;

use Utils\WechatUtils;
use Utils\StringUtils;
use Utils\LogUtils;
use Service\Auth\AuthService;
use Service\Auth\AuthCacheService;
use Model\User\User;

/**
 * Class AuthController 验证控制器
 */
class AuthController extends BaseController
{
    /**
     * 临时token用户ID
     *
     * @var int
     */
    const TMP_TOKEN_USER_ID = AuthService::TMP_TOKEN_USER_ID;
    /**
     * 默认用户token有效时间
     *
     * @var int
     */
    const DEFAULT_USER_TOKEN_EFFECTIVE_TIME = AuthService::DEFAULT_USER_TOKEN_EFFECTIVE_TIME;

    /**
     * 微信授权
     *
     * @return string
     */
    public function authorization()
    {
        $jsCode = trim($_POST['jsCode']);
        $iv = isset($_POST['iv']) ? trim($_POST['iv']) : '';
        $encryptedData = isset($_POST['encryptedData']) ? trim($_POST['encryptedData']) : ''; 

        // 授权信息
        //调微信获取信息
        $responseData = WechatUtils::getWechatSessionKey(APP_ID, APP_SECRET, $jsCode);

        $responseData = [
            'openid' => '111',
            'session_key' => '111',
        ];

        if (!isset($responseData['openid']) || empty($responseData['openid'])) {
            $log = [
                'phpFile'      => __FILE__,
                'phpCodeLine'  => __LINE__,
                'responseData' => $responseData
            ];
            LogUtils::addLog('AUTH', '调微信获取信息失败', $log);
            $this->echoJson(-1, '用户授权失败');
            return false;
        }

        $sessionKey = $responseData['session_key'];
        $openId = $responseData['openid'];

        //获取用户信息 
        // todo 读取用户信息
        //用户信息,根据openId获取用户信息
        // $userInfo = (new UserService())->getUserInfoByUserId($openId);
        $userInfo = [];

        if (empty($userInfo)) {
            if (empty($encryptedData) || empty($iv)) {
                //1. 处理授权生成临时token
                $object = $this->handleAuthTmpToken($sessionKey);
            } else {
                //解密微信加密字符串
                $decryptData = WechatUtils::decryptData(APP_ID, $sessionKey, $encryptedData, $iv);
                $decryptData = [
                    'openId' => '123123',
                    'unionId' => '11111',
                    'nickName' => '11111',
                    'avatarUrl' => '2222222',
                ];

                if (!isset($decryptData['openId']) || !isset($decryptData['unionId']) || !isset($decryptData['nickName']) || !isset($decryptData['avatarUrl'])) {
                    $log = [
                        'phpFile'      => __FILE__,
                        'phpCodeLine'  => __LINE__,
                        'decryptData'  => $decryptData,
                        'responseData' => $responseData
                    ];
                    LogUtils::addLog('AUTH', '解密微信加密字符串失败', $log);
                    $this->echoJson(-2, '用户授权失败');
                }

                $unionId = $decryptData['unionId'];
                $nickname = $decryptData['nickName'];
                $avatar = $decryptData['avatarUrl'];

                // 注册用户
                $nowTime = time();
                $insertData[] = [
                    $decryptData['nickName'],
                    $decryptData['openId'],
                    $decryptData['unionId'],
                    $decryptData['avatarUrl'],
                    $nowTime,
                    $nowTime,
                ];
                $userId = (new User())->addUser($insertData);

                if (empty($userId)) {
                    $this->echoJson(-3, '生成用户失败');
                    return false;
                }

                $object = $this->handleAuthToken($userId, $sessionKey);
            }
        } else {
            //3. 处理授权生成token
            $object = $this->handleAuthToken($userId, $userInfo['is_bind_phone'], $sessionKey);
        }
        return $this->echoJson(1, $object);
    }

    /**
     * 处理授权临时token
     *
     * @param string $sessionKey 微信sessionKey
     *
     * @return array
     * @throws ConfigNotExistsException
     */
    private function handleAuthTmpToken($sessionKey)
    {
        $randomStr = StringUtils::createRoundString(28);
        //生成临时token
        $token = (new AuthService())->genToken(self::TMP_TOKEN_USER_ID, $randomStr);

        $data = [
            'token'      => $token,
            'sessionKey' => $sessionKey
        ];

        (new AuthCacheService())->setexUserTmpToken(sha1($randomStr), self::DEFAULT_USER_TOKEN_EFFECTIVE_TIME, $data);

        $object = [
            'token' => $token,
        ];

        return $object;
    }

    /**
     * 处理授权token
     *
     * @param int    $userId      用户ID
     * @param string $sessionKey  微信sessionKey
     *
     * @return array
     */
    private function handleAuthToken($userId, $sessionKey)
    {
        $userService = new UserService();

        //更新登录(授权)时间
        $userService->modifyLastLoginTimeByUserId($userId);

        //生成token
        $randomStr = StringUtils::createRoundString(28);
        $token = (new AuthService())->genToken($userId, $randomStr);

        $data = [
            'token'      => $token,
            'sessionKey' => $sessionKey
        ];

        (new AuthCacheService())->setexUserAliveToken($userId, self::DEFAULT_USER_TOKEN_EFFECTIVE_TIME, $data);

        $object = [
            'token' => $token,
        ];

        return $object;
    }
}
