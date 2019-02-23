<?php

namespace Service\Auth;

use Utils\StringUtils;

class AuthService 
{
    /**
     * 临时token用户ID
     *
     * @var int
     */
    const TMP_TOKEN_USER_ID = 0;
    /**
     * 默认用户token有效时间
     *
     * @var int
     */
    const DEFAULT_USER_TOKEN_EFFECTIVE_TIME = 3600;

    /**
     * 验证token
     *
     * @param string $token
     *
     * @throws TokenException
     * @throws ConfigNotExistsException
     * @throws DecryptFailException
     *
     * @return array
     */
    public function authTmpToken($token)
    {
        $decryptToken = $this->decryptToken($token);

        if ($decryptToken['userId'] == self::TMP_TOKEN_USER_ID) {
            //token有效期时间验证
            $authCacheService = new AuthCacheService();

            $userRedisData = $authCacheService->getUsertmpToken(sha1($decryptToken['randomStr']));
            $userRedisData = \json_decode($userRedisData, true);

            if (!isset($userRedisData['token']) || $token != $userRedisData['token']) {
                $returndata = [
                    'status' => false,
                    'msg' => 'user_tmp_token_expired'
                ];
                return $returndata;
            }

            $authCacheService->setexUserTmpToken(sha1($decryptToken['randomStr']), self::DEFAULT_USER_TOKEN_EFFECTIVE_TIME, $userRedisData);

            $data = [
                'userId'    => $decryptToken['userId'],
                'randomStr' => isset($decryptToken['randomStr']) ? $decryptToken['randomStr'] : '',
                'timestamp' => $decryptToken['timestamp']
            ];
        } else {
            $data = $this->authToken($token);
        }

        return [
            'status' => true,
            'data' => $data
        ];
    }

    /**
     * 验证token
     *
     * @param string $token
     *
     * @throws TokenException
     * @throws ConfigNotExistsException
     * @throws DecryptFailException
     *
     * @return array
     */
    public function authToken($token)
    {
        $decryptToken = $this->decryptToken($token);

        if ($decryptToken['status']==false) {
            $returnData = [
                'status' => false,
                'msg' => $decryptToken['msg']
            ];
            return $returnData;
        }

        $decryptToken = $decryptToken['data'];

        //使用临时token则报错
        if ($decryptToken['userId'] == self::TMP_TOKEN_USER_ID) {
            $returnData = [
                'status' => false,
                'msg' => 'user_not_authorization'
            ];
            return $returnData;
        }

        //token有效期时间验证
        $authCacheService = new AuthCacheService();

        $userRedisData = $authCacheService->getUserAliveToken($decryptToken['userId']);
        $userRedisData = \json_decode($userRedisData, true);

        if (!isset($userRedisData['token']) || $token != $userRedisData['token']) {
            $returnData = [
                'status' => false,
                'msg' => 'user_token_expired'
            ];
            return $returnData;
        }

        $authCacheService->setexUserAliveToken($decryptToken['userId'], self::DEFAULT_USER_TOKEN_EFFECTIVE_TIME, $userRedisData);

        $data = [
            'userId'    => $decryptToken['userId'],
            'randomStr' => isset($decryptToken['randomStr']) ? $decryptToken['randomStr'] : '',
            'timestamp' => $decryptToken['timestamp']
        ];

        return [
            'status' => true,
            'data' => $data
        ];
    }

    /**
     * 解析token
     *
     * @param string $token token
     *
     * @return array
     * @throws ConfigNotExistsException
     * @throws DecryptFailException
     * @throws TokenException
     */
    private function decryptToken($token)
    {
        if (\is_null($token) || empty($token)) {
            $returnData = [
                'status' => false,
                'msg' => 'user_token_error'
            ];
            return $returnData;
        }

        $decryptToken = StringUtils::decrypt($token, ENCRYPT_KEY);

        if ($decryptToken == false) {
            $returnData = [
                'status' => false,
                'msg' => 'user_token_decrypt_error'
            ];
            return $returnData;
        }

        $decryptToken = \json_decode($decryptToken, true);

        if (\is_null($decryptToken) || false == $decryptToken || !isset($decryptToken['userId']) || !isset($decryptToken['timestamp'])) {
            $returnData = [
                'status' => false,
                'msg' => 'user_token_decrypt_error'
            ];
            return $returnData;
        }

        return [
            'status' => true,
            'data' => $decryptToken
        ];
    }

    /**
     * 生成token
     *
     * @param int    $userId    用户ID
     * @param string $randomStr 随机字符串
     *
     * @throws ConfigNotExistsException
     * @return string
     */
    public function genToken($userId, $randomStr)
    {
        $timestamp = time();
        $encryptKey = ENCRYPT_KEY;

        return StringUtils::generateToken($userId, $randomStr, $timestamp, $encryptKey);
    }
}