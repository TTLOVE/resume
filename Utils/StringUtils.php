<?php

namespace Utils;

/**
 * StringUtils.
 */
class StringUtils
{
    const CIPHER = 'aes-256-cbc';
    /**
     * token所需生成参数错误
     *
     * @var int
     */
    const TOKEN_ARGUMENTS_ERROR = -1494560433;

    /**
     * 加密函数.
     *
     * @param string $plaintext 需加密的字符串
     * @param string $key       加密密钥，默认读取SECURE_CODE配置
     *
     * @throws EncryptFailException
     * @return string 加密后的字符串
     */
    public static function encrypt($plaintext, $key = '')
    {
        if (empty($key) || strlen($key) != 64) {
            throw new EncryptFailException('invalid encrypt key ' . $key);
        }
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = openssl_random_pseudo_bytes($ivLength);
        $cipherTextRaw = openssl_encrypt($plaintext, self::CIPHER, $key, $options = OPENSSL_RAW_DATA, $iv);
        $cipherText = base64_encode($cipherTextRaw . $iv);
        return $cipherText;
    }

    /**
     * 解密函数.
     *
     * @param String $ciperTextBase64 待解密字符串(base64encoded)
     * @param String $key             密钥
     *
     * @throws DecryptFailException
     * @return string 解密后的字符串
     */
    public static function decrypt($ciperTextBase64, $key = '')
    {
        if (empty($key) || strlen($key) != 64) {
            throw new DecryptFailException('invalid encrypt key ' . $key);
        }
        $ciperText = base64_decode($ciperTextBase64);
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = \substr($ciperText, -$ivLength);
        $cipherTextRaw = \substr($ciperText, 0, \strlen($ciperText) - $ivLength);
        $decrypt = openssl_decrypt($cipherTextRaw, self::CIPHER, $key, $options = OPENSSL_RAW_DATA, $iv);
        return $decrypt;
    }


    /**
     * 生成指定长度的随机字符串
     *
     * @param int $length 字串长度
     *
     * @return string
     */
    public static function createRoundString($length = 16)
    {
        $string = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $roundStr = '';
        for ($i = 0; $i < $length; $i++) {
            $roundStr .= $string[mt_rand(0, strlen($string) - 1)];
        }
        return $roundStr;
    }

    /**
     * 生成指定长度的随机字符串
     *
     * @param int $length 字串长度
     *
     * @return string
     */
    public static function createRoundEn($length = 16)
    {
        $string = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $roundStr = '';
        for ($i = 0; $i < $length; $i++) {
            $roundStr .= $string[mt_rand(0, strlen($string) - 1)];
        }
        return $roundStr;
    }

    /**
     * 生成指定长度的随机字符串
     *
     * @param int $length 字串长度
     *
     * @return string
     */
    public static function createRoundNumber($length = 16)
    {
        $string = "0123456789";
        $roundStr = '';
        for ($i = 0; $i < $length; $i++) {
            $roundStr .= $string[mt_rand(0, strlen($string) - 1)];
        }
        return $roundStr;
    }

    /**
     * 生成简单随机验证码
     *
     * @param int $len 生成随机码的长度
     *
     * @return string 返回最终生成的随机验证码
     */
    public static function createRandCaptcha($len = 6)
    {
        $chars = "123456789";
        $res = "";
        $strArr = [];
        for ($i = 0; $i < $len; $i++) {
            array_push($strArr, $chars[mt_rand(0, strlen($chars) - 1)]);
        }
        $res = implode('', $strArr);
        return $res;
    }


    /**
     * 生成API token
     *
     * @param int    $userId
     * @param string $randomStr
     * @param int    $timestamp
     * @param string $encryptKey
     *
     * @return string
     */
    public static function generateToken($userId, $randomStr, $timestamp, $encryptKey)
    {
        if ($userId < 0 || empty($randomStr) || empty($encryptKey)) {
            return false;
        }
        $pack = [
            'userId'    => $userId,
            'randomStr' => $randomStr,
            'timestamp' => $timestamp
        ];
        $token = StringUtils::encrypt(\json_encode($pack), $encryptKey);
        return $token;
    }

    /**
     * 得到生成token所需的sid
     *
     * @return string
     */
    public static function tokenRandSid()
    {
        $sid = strval(microtime(true) . rand(100, 999));
        return $sid;
    }

    /**
     * 解析token
     *
     * @tutorial 返回格式：["123456","9999","app"]
     *
     * @param string $token
     * @param string $encryptKey
     *
     * @throws TokenException
     * @return array
     */
    public static function parseToken($token, $encryptKey)
    {
        if (empty($token) || !is_string($token) || empty($encryptKey)) {
            throw new TokenException('Token arguments error', self::TOKEN_ARGUMENTS_ERROR);
        }

        $decrypt = StringUtils::decrypt($token, $encryptKey);
        $decodeData = json_decode($decrypt, true); // true返回 array 而非 object
        if (is_null($decodeData)) {
            throw new TokenException('Decode token error');
        }
        list ($decodeUserId, $decodeSid, $decodeApiClient) = $decodeData;
        if (intval($decodeUserId) <= 0 || ("" == $decodeSid) || ("" == $decodeApiClient)) {
            throw new TokenException('Decode token error');
        }
        unset($decodeUserId, $decodeSid, $decodeApiClient);
        return $decodeData;
    }

    /**
     * 生成api签名
     *
     * @param array $signData 签名数据数组
     *
     * @return string
     */
    public static function genApiSign(array $signData)
    {
        ksort($signData);
        $str = implode('', array_keys($signData)) . implode('', $signData);
        $sign = sha1($str);
        return $sign;
    }

    /**
     * 裁剪字符串
     *
     * @param String $str    被裁剪的字符串
     * @param int    $begin  裁剪起点索引
     * @param int    $length 从起点索引裁剪的长度
     *
     * @return string 返回裁剪后的结果
     */
    public static function stringSlice($str, $begin, $length)
    {
        if (!is_string($str)) {
            return $str;
        }
        if (function_exists('mb_substr')) {
            return mb_substr($str, $begin, $length, "utf-8");
        }
        return substr($str, $begin, $length);
    }

    /**
     * 字符串长度
     * 对比->mb_strlen:strlen
     * eg:People ->6:6
     * eg:繁荣的市场、畅通的物流 ->11:33 (、是汉字符号)
     * eg:字Pe,opl，e ->9:13(，是汉字符号)
     *
     * @param String $str
     *
     * @return int
     */
    public static function stringLength($str)
    {
        if (!is_string($str)) {
            return 0;
        }
        if (function_exists('mb_strlen')) {
            return mb_strlen($str, "utf-8");
        }
        return strlen($str);
    }

    /**
     * 裁剪字符串
     *
     * @param string $str   被裁剪的字符串
     * @param int    $begin 裁剪起点索引
     * @param int    $len   从起点索引裁剪的长度
     *
     * @return string 返回裁剪后的结果
     */
    public static function stringCut($str, $begin, $len)
    {
        if (!is_string($str)) return $str;
        if (function_exists('mb_substr')) {
            return mb_substr($str, $begin, $len, "utf-8");
        }
        return substr($str, $begin, $len);
    }

    /**
     * 统一格式输出
     *
     * @param int        $code
     * @param string     $message
     * @param array|null $object
     * @param array      $list
     * @param array      $extend
     * @param number     $timestamp
     *
     * @return string
     */
    public static function responseFormat($code, $message, $object = null, $list = [], $extend = null, $timestamp = 0)
    {
        $response = [
            'status'    => $code,
            'message'   => $message,
            'data'      => [
                'object' => $object,
                'list'   => $list,
                'extend' => $extend,
            ],
            'timestamp' => $timestamp,
        ];
        return \json_encode($response, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 根据多少秒生成X时Y分Z秒的字符串
     *
     * @param int $second 描述
     *
     * @return string
     */
    public static function genTimeStr($second)
    {
        $hour = intval($second / 3600);
        $minute = intval(($second - $hour * 3600) / 60);
        $second = $second - $hour * 3600 - $minute * 60;

        return $hour . '时' . $minute . '分' . $second . '秒';
    }
}
