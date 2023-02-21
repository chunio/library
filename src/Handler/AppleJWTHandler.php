<?php

declare(strict_types=1);

namespace Component;

use Hyperf\Di\Exception\Exception;

/**
 * Class AppleJWTHandler
 * @package Component
 */
class AppleJWTHandler
{

    protected static $supportedAlgorithmList = [
        'HS256' => ['hash_hmac', 'SHA256'],
        'HS512' => ['hash_hmac', 'SHA512'],
        'HS384' => ['hash_hmac', 'SHA384'],
        'RS256' => ['openssl', 'SHA256'],
        'RS384' => ['openssl', 'SHA384'],
        'RS512' => ['openssl', 'SHA512'],
    ];

    static public function check(array $payload, string $appleAccountId, string $appleJwt): bool
    {
        //$appleJwt = 'eyJraWQiOiJZdXlYb1kiLCJhbGciOiJSUzI1NiJ9.eyJpc3MiOiJodHRwczovL2FwcGxlaWQuYXBwbGUuY29tIiwiYXVkIjoibWlsZXMubGFuZC5yeWVub3ZlbCIsImV4cCI6MTY2MDM2OTUwMCwiaWF0IjoxNjYwMjgzMTAwLCJzdWIiOiIwMDE1OTkuYmM0NTkwNGE5NDNkNDZmZjkyODM5ZTg2NTYwNDYyMmMuMDczMSIsImNfaGFzaCI6IlAycGhLYjVJM0JYbEpQWGl2NGtJaGciLCJlbWFpbCI6Ijc0MjcxMDA0OEBxcS5jb20iLCJlbWFpbF92ZXJpZmllZCI6InRydWUiLCJhdXRoX3RpbWUiOjE2NjAyODMxMDAsIm5vbmNlX3N1cHBvcnRlZCI6dHJ1ZX0.KjEST5fmXHFbXAZRjoupPruORfXLi5IH4EOuhwgkY0ILb8Q6XUZc-lw5EUyBbbVvatWRXm0lLjbad_D6wdCfMYWxBdrbXO1GVYh10TzFyUQ7zrfL_YJepEpTyhV_1Uj8hFW1U6hUVx6XUiZK7hiZUHmQlQXUQfzKUl6PSvpkUQvXCREwqzhQzD3l3Y1S7VzCpDpuhoEDrvvqRIZsd4PAPKO1VuaTqyJUs_a_jWF_-PBZOVCxG53iCJWyWinwkfcK8qD85VpKX-iDJd8psct1BhdkOyi1Tap_bN8xLzENsarglSpSuBdRVDC2yoITk1yhqoeU8azMgpp13AVM7P5Vaw';
        [$headerBase64, $payloadBase64, $signature] = explode('.', $appleJwt);
        $header = json_decode(base64_decode($headerBase64), true);
        $applePayload = json_decode(base64_decode($payloadBase64), true);
        if(
            ($applePayload['aud'] != $payload['package_name']/*miles.land.ryenovel*/) ||
            ($applePayload['sub'] != $appleAccountId) ||
            ($applePayload['exp'] <= time())
        ) {
            monolog([
                $header,
                $applePayload,
                $signature
            ]);
            throw new Exception('apple jwt is incorrect');
        }
        $uri = 'https://appleid.apple.com/auth/keys';
        $config = ['timeout' => config('system.curl.timeout')];
        if(in_array(env('APP_ENV'), ['dev', 'test'])) $config['proxy'] = config('system.curl.proxy_server') . ":" . config('system.curl.proxy_port');
        $client = new \GuzzleHttp\Client($config);
        $result = json_decode((string)$client->request('get', $uri, $config)->getBody(), true);
        if(!($result['keys'] ?? '')) throw new Exception("curl {$uri} error");
        $publicKeyArray = array_column($result['keys'],null,'kid');
        //{$n|$e}爲公鑰參數，採用BASE64，使用前需先解碼
        $n = $publicKeyArray[$header['kid']]['n'];
        $e = $publicKeyArray[$header['kid']]['e'];
        $certificate = self::buildCertificate($n, $e);
        $publicKeyResource = openssl_pkey_get_public($certificate);//從證書返回公鑰//resource
        $publicKeyArray = openssl_pkey_get_details($publicKeyResource);//返回公鑰詳情
        $publicKey = $publicKeyArray['key'];
        $bool = self::verify("{$headerBase64}.{$payloadBase64}", static::base64URLDecode($signature), $publicKey, $header['alg']);
        monolog([
            '$publicKeyArray' => $publicKeyArray,
            '$bool' => $bool
        ],'checkReport');
        return $bool;
    }

    /**
     * @param string $n the RSA modulus encoded in Base64
     * @param string $e the RSA exponent encoded in Base64
     * @return string the RSA public key represented in PEM format
     * create a public key represented in PEM（僅表示文件包含base64編碼的數據位，現通常指{秘鑰|證書}） format from RSA modulus and exponent information
     * https://github.com/firebase/php-jwt/blob/feb0e820b8436873675fd3aca04f3728eb2185cb/src/JWT.php#L350
     * http://t.zoukankan.com/batsing-p-sign-in-with-apple.html
     * https://www.cnblogs.com/harmful-chan/p/15969107.html
     */
    protected static function buildCertificate(string $n, string $e): string
    {
        /*****
        # cat prikey.pkcs1.pem
        Private-Key: (1024 bit)
        modulus:
        00:b6:14:a0:94:2e:26:9b:d7:99:f5:04:3e:90:bc:
        5c:f7:f3:69:2d:e0:6b:03:be:e3:8c:e6:d9:98:79:
        90:fb:97:c3:2e:71:2c:8f:a4:f3:91:17:26:96:1a:
        82:88:fa:94:f0:00:a2:9b:0f:2d:9e:a6:aa:bb:5a:
        d6:31:39:fa:80:d4:0c:d4:d6:63:6c:93:a5:d1:4c:
        11:da:d9:7c:ee:48:5f:bf:d2:b5:d8:8f:bf:29:da:
        13:f8:76:76:0f:55:ba:42:64:27:6b:5c:d3:16:97:
        ee:e1:9a:b2:9f:5c:d0:7b:3e:f3:c0:7b:47:d0:72:
        d5:27:a3:b5:84:95:2d:fb:ab
        publicExponent: 65537 (0x10001)
        privateExponent:
        69:13:26:47:dd:0a:32:cd:0c:ef:b4:6f:56:9f:1d:
        ...
         *****/
        $modulus = static::base64URLDecode($n);//binary
        $publicExponent = static::base64URLDecode($e);//binary
        $component = [
            //pack()把數據裝進一個二進制字符串
            'modulus' => pack('Ca*a*', 2, self::derEncode(strlen($modulus)), $modulus),
            'publicExponent' => pack('Ca*a*', 2, self::derEncode(strlen($publicExponent)), $publicExponent)
        ];
        $RSAPublicKey = pack(
            'Ca*a*a*',
            48,
            self::derEncode(strlen($component['modulus']) + strlen($component['publicExponent'])),
            $component['modulus'],
            $component['publicExponent']
        );
        // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
        $rsaOID = pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
        $RSAPublicKey = chr(0) . $RSAPublicKey;
        $RSAPublicKey = chr(3) . self::derEncode(strlen($RSAPublicKey)) . $RSAPublicKey;
        $RSAPublicKey = pack(
            'Ca*a*',
            48,
            self::derEncode(strlen($rsaOID . $RSAPublicKey)),
            $rsaOID . $RSAPublicKey
        );
        $RSAPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" .
            chunk_split(base64_encode($RSAPublicKey), 64) .
            '-----END PUBLIC KEY-----';
        return $RSAPublicKey;
    }

    /**
     * @param string $input
     * @return string
     * base64_encode() : 1支持作用於二進制文件，2傳統編碼中會出現「+（加號）」，「/（斜杠）」兩個會被url直接轉義的符號
     * 因此習慣會將「+（加號）」，「/（斜杠）」分別替換至「-（橫杠）」，「_（下劃線）」
     * 官方 : https://www.php.net/manual/zh/function.base64-encode.php
     * info : https://blog.csdn.net/dw5235/article/details/114292360
     */
    static function base64URLEncode(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    /**
     * decode a string with url-safe base64
     * @param string $input a base64 encoded string
     * @return string a decoded string
     * base64_decode() : 1不檢測末位等號是否完整
     */
    static function base64URLDecode(string $input): string
    {
        return base64_decode(str_pad(strtr($input, '-_', '+/'), strlen($input) % 4, '=', STR_PAD_RIGHT));
    }

    /**
     * DER-encode the length
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4
     * see {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     * @access private
     * @param int $length
     * @return string
     */
    protected static function derEncode(int $length): string
    {
        if ($length <= 0x7F/*127*/) {
            return chr($length);//根據ASCIIf返回字符串
        }
        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }

    /**
     * verify a signature with the message, key and method
     * not all method are symmetric, so we must have a separate verify and sign method
     * @param string $message the original message (header and body)
     * @param string $signature the original signature
     * @param string|resource $key for HS*, a string key works. for RS*, must be a resource of an openssl public key
     * @param string $algorithm
     * @return bool
     * @throws Exception invalid algorithm or openssl failure
     */
    protected static function verify(string $message, string $signature, string $key, string $algorithm): bool
    {
        if (empty(static::$supportedAlgorithmList[$algorithm])) throw new Exception('algorithm not supported');
        list($function, $algorithm) = static::$supportedAlgorithmList[$algorithm];
        switch($function) {
            case 'openssl':
                $success = openssl_verify($message, $signature, $key, $algorithm);
                if ($success === 1) {
                    return true;
                } elseif ($success === 0) {
                    return false;
                }
                // return 1 on success, 0 on failure, -1 on error
                throw new Exception('openssl error: ' . openssl_error_string());
            case 'hash_hmac':
            default:
                $hash = hash_hmac($algorithm, $message, $key, true);
                if (function_exists('hash_equals')) {
                    return hash_equals($signature, $hash);
                }
                $len = min(static::customStrlen($signature), static::customStrlen($hash));
                $status = 0;
                for ($i = 0; $i < $len; $i++) {
                    $status |= (ord($signature[$i]) ^ ord($hash[$i]));
                }
                $status |= (static::customStrlen($signature) ^ static::customStrlen($hash));
                return ($status === 0);
        }
    }

    /**
     * get the number of bytes in cryptographic strings
     * @param string $string
     * @return int
     */
    protected static function customStrlen(string $string): int
    {
        if (function_exists('mb_strlen')) {
            return mb_strlen($string, '8bit');
        }
        return strlen($string);
    }

}
