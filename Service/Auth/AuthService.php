<?php

namespace Service\Auth;

use Utils\StringUtils;

class AuthService extends Service
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

            if ('dev' == App::getRuntimeEnv()) {
                //若开发环境则不判断token是否一致
                $tokenKeys = $authCacheService->keysUserTmpToken(sha1($decryptToken['randomStr']));

                if (empty($tokenKeys)) {
                    $routerStatusKey = 'user_tmp_token_expired';
                    throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
                }

                $userRedisData = $authCacheService->getUserTmpToken(sha1($decryptToken['randomStr']));
                $userRedisData = \json_decode($userRedisData, true);

                $userRedisData = [
                    'token'      => $token,
                    'sessionKey' => isset($userRedisData['sessionKey']) ? $userRedisData['sessionKey'] : ''
                ];
            } else {
                $userRedisData = $authCacheService->getUsertmpToken(sha1($decryptToken['randomStr']));
                $userRedisData = \json_decode($userRedisData, true);

                if (!isset($userRedisData['token']) || $token != $userRedisData['token']) {
                    $routerStatusKey = 'user_tmp_token_expired';
                    throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
                }
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

        return $data;
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

        //使用临时token则报错
        if ($decryptToken['userId'] == self::TMP_TOKEN_USER_ID) {
            $routerStatusKey = 'user_not_authorization';
            throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
        }

        //token有效期时间验证
        $authCacheService = new AuthCacheService();

        if ('dev' == App::getRuntimeEnv()) {
            //若开发环境则不判断token是否一致
            $tokenKeys = $authCacheService->keysUserAliveToken($decryptToken['userId']);

            if (empty($tokenKeys)) {
                $routerStatusKey = 'user_token_expired';
                throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
            }

            $userRedisData = $authCacheService->getUserAliveToken($decryptToken['userId']);
            $userRedisData = \json_decode($userRedisData, true);

            $userRedisData = [
                'token'      => $token,
                'sessionKey' => isset($userRedisData['sessionKey']) ? $userRedisData['sessionKey'] : ''
            ];
        } else {
            $userRedisData = $authCacheService->getUserAliveToken($decryptToken['userId']);
            $userRedisData = \json_decode($userRedisData, true);

            if (!isset($userRedisData['token']) || $token != $userRedisData['token']) {
                $routerStatusKey = 'user_token_expired';
                throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
            }
        }

        $authCacheService->setexUserAliveToken($decryptToken['userId'], self::DEFAULT_USER_TOKEN_EFFECTIVE_TIME, $userRedisData);

        $data = [
            'userId'    => $decryptToken['userId'],
            'randomStr' => isset($decryptToken['randomStr']) ? $decryptToken['randomStr'] : '',
            'timestamp' => $decryptToken['timestamp']
        ];

        return $data;
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
            $routerStatusKey = 'user_token_error';
            throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
        }

        $decryptToken = StringUtils::decrypt($token, Config::G('auth.encrypt_key.key'));

        if ($decryptToken == false) {
            $routerStatusKey = 'user_token_decrypt_error';
            throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
        }

        $decryptToken = \json_decode($decryptToken, true);

        if (\is_null($decryptToken) || false == $decryptToken || !isset($decryptToken['userId']) || !isset($decryptToken['timestamp'])) {
            $routerStatusKey = 'user_token_decrypt_error';
            throw new TokenException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
        }

        return $decryptToken;
    }

    /**
     * 验证来源
     *
     * @param int $source
     *
     * @return true
     * @throws ConfigNotExistsException
     * @throws SourceException
     */
    public function authSource($source)
    {
        $allowSource = [
            TcUserSource::MINI_PROGRAM
        ];

        if (!in_array($source, $allowSource)) {
            $routerStatusKey = 'user_source_error';
            throw new SourceException($this->getRouterMessage($routerStatusKey), $this->getRouterCode($routerStatusKey));
        }

        return true;
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
        $timestamp = App::getTimestamp();
        $encryptKey = ENCRYPT_KEY;

        return StringUtils::generateToken($userId, $randomStr, $timestamp, $encryptKey);
    }

    /**
     * 获得路由代码
     *
     * @param string $routerKey
     *
     * @return string
     * @throws ConfigNotExistsException
     */
    private function getRouterCode($routerKey)
    {
        $codeKey = 'router_code.code.' . $routerKey;
        return Config::G($codeKey);
    }

    /**
     * 获得路由信息
     *
     * @param string $routerKey
     *
     * @return string
     * @throws ConfigNotExistsException
     */
    private function getRouterMessage($routerKey)
    {
        $msgKey = 'router_code.message.' . $routerKey;
        return Config::G($msgKey);
    }
}