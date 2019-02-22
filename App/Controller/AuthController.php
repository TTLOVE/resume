<?php

namespace Controller;

use Leaf\Loger\LogDriver as Log;
use Utils\WechatUtils;
use Utils\StringUtils;
use Service\Auth\AuthService;
use Service\Auth\AuthCacheService;

/**
 * Class AuthController 验证控制器
 */
class AuthController extends BaseController
{
    /**
     * 微信授权
     *
     * @return string
     */
    public function authorization()
    {
        $jsCode = trim($_POST['jsCode']);
        $iv = trim($_POST['iv']);
        $encryptedData = trim($_POST['encryptedData']);

        // 授权信息
        // 微信号对应appId和appSecret
        $appId = trim($_POST['jsCode']);
        $appSecret = trim($_POST['jsCode']);

        //调微信获取信息
        $responseData = WechatUtils::getWechatSessionKey($appId, $appSecret, $jsCode);

        if (!isset($responseData['openid']) || empty($responseData['openid'])) {
            $log = [
                'phpFile'      => __FILE__,
                'phpCodeLine'  => __LINE__,
                'responseData' => $responseData
            ];
            (new Log())->error('auth', "调微信获取信息失败,信息:" . json_encode($log));
            $this->echoJson(-1, '用户授权失败');
        }

        $sessionKey = $responseData['session_key'];
        $openId = $responseData['openid'];

        //获取用户信息 
        // todo 读取用户信息
        //用户信息,根据openId获取用户信息
        $userInfo = (new UserService())->getUserInfoByUserId($openId);

        if (empty($userInfo)) {
            if (empty($encryptedData) || empty($iv)) {
                //1. 处理授权生成临时token
                $object = $this->handleAuthTmpToken($sessionKey);
            } else {
                //解密微信加密字符串
                $decryptData = WechatUtils::decryptData($appId, $sessionKey, $encryptedData, $iv);

                if (!isset($decryptData['openId']) || !isset($decryptData['unionId']) || !isset($decryptData['nickName']) || !isset($decryptData['avatarUrl'])) {
                    $log = [
                        'phpFile'      => __FILE__,
                        'phpCodeLine'  => __LINE__,
                        'decryptData'  => $decryptData,
                        'responseData' => $responseData
                    ];
                    (new Log())->error('auth', "解密微信加密字符串失败,信息:" . json_encode($log));
                    $this->echoJson(-2, '用户授权失败');
                }

                $unionId = $decryptData['unionId'];
                $nickname = $decryptData['nickName'];
                $avatar = $decryptData['avatarUrl'];

                //todo 注册用户
                // try {
                //     $passportResponse = (new PassportService())->register($openId, $unionId, $nickname, $avatar, $appId);
                // } catch (PassportException $e) {
                //     $apiResponseKey = 'user_authorization_error';
                //     return $this->apiResponse($this->getApiCode($apiResponseKey), $this->getApiMessage($apiResponseKey));
                // }

                //用户ID
                $userId = $passportResponse['response']['userId'];

                $object = $this->handleAuthToken($userId, $userInfo['is_bind_phone'], $sessionKey);
            }
        } else {
            //3. 处理授权生成token
            $object = $this->handleAuthToken($userId, $userInfo['is_bind_phone'], $sessionKey);
        }
        return $response->body($data);
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
     * @throws ConfigNotExistsException
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

        //已开通钛卡
        $isOpenTimeCard = TimeCardService::OPENED_TIME_CARD;
        //注册获取钛币
        $registerTimeCoin = 0;

        $object = [
            'token'              => $token,
            'is_open_time_card'  => $isOpenTimeCard,
            'is_bind_phone'      => $isBindPhone,
            'register_time_coin' => $registerTimeCoin
        ];

        return $object;
    }
}
