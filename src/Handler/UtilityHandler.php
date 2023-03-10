<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use Baichuan\Library\Constant\AsciiEnum;
use Closure;
use GuzzleHttp\Cookie\CookieJar;
use Hyperf\Utils\ApplicationContext;

/**
 * Class UtilityHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/01 13:50
 */
class UtilityHandler
{

    public static function pagination(array $list, int $pageIndex, int $pageSize): array
    {
        $recordNum = count($list);
        $pageLimit = intval(ceil($recordNum / $pageSize));
        if ($pageIndex < 1) {
            $pageIndex = 1;
        } elseif ($pageIndex > $pageLimit && $pageLimit != 0 ) {
            $pageIndex = $pageLimit;
        }
        $start = intval(($pageIndex - 1) * $pageSize);
        $currentList = $list ? array_slice($list, $start, $pageSize) : [];
        return [
            'current_list' => $currentList,
            'page_index' => $pageIndex,
            'page_size' => $pageSize,
            'page_limit' => $pageLimit,
            'record_num' => $recordNum,
        ];
    }

    public static function order(array $array, string $slaveField, string $sort = 'DESC'): array
    {
        $newArray = $valueArray = [];
        foreach ($array as $key => $value) {
            $valueArray[$key] = $value[$slaveField];
        }
        if (strtoupper($sort) === 'ASC') {
            asort($valueArray);
        } else {
            arsort($valueArray);
        }
        reset($valueArray);
        foreach ($valueArray as $key => $value) {
            $newArray[$key] = $array[$key];
        }
        return $newArray;
    }

    public static function commonHttpPost(string $uri, array $body = [], $header = ['Content-Type' => 'application/json'], array $cookieDetail = [], string $cookieDomain = '')
    {
        $config = [
            'headers' => $header,
            'json' => $body,
        ];
        if($cookieDetail && $cookieDomain){
            $config['cookies'] = CookieJar::fromArray($cookieDetail, $cookieDomain);
        }
        $client = new \GuzzleHttp\Client($config);
        $result = json_decode((string)$client->request('get', $uri, $config)->getBody(), true);
        return $result;
    }

    public static function commonHttpGet(string $uri, array $query = [], array $cookieDetail = [], string $cookieDomain = '')
    {
        $config = [
            'query' => $query,
        ];
        if($cookieDetail && $cookieDomain){
            $config['cookies'] = CookieJar::fromArray($cookieDetail, $cookieDomain);
        }
        $client = new \GuzzleHttp\Client($config);
        $result = json_decode((string)$client->request('get', $uri, $config)->getBody(), true);
        return $result;
    }

    public static function prettyJsonEncode($object, ?int $flag = JSON_PRETTY_PRINT): string
    {
        //JSON_PRETTY_PRINT//????????????????????????????????????
        $flagCounter = JSON_UNESCAPED_SLASHES/*??????????????????*/ | JSON_UNESCAPED_UNICODE/*unicode????????????*/;
        if (!$flag) {
            $flagCounter |= $flag;
        }
        return json_encode($object, $flagCounter);
    }

    public static function filterControlCharacter(string $string)
    {
        if(!$string) return '';
        $format = '';
        for($i = 0; isset($string[$i]); $i++) {
            $asciiCode = ord($string[$i]);
            if($asciiCode <= 31 || $asciiCode == 127){
                $format .= '[' . AsciiEnum::CONTROL_CHARACTER[$asciiCode] . ']';
            }elseif($asciiCode > 31){
                $format .= $string[$i];
            }
        }
        return trim($format);
    }

    public static function matchEnvi(string $envi): bool
    {
        return env('APP_ENV') == $envi;
    }

    /**
     * @param mixed $key ???key??????null?????????$parameter???????????????null?????????key?????????null??????????????????$parameter($key)???$parameter[$key]??????null???
     * @param mixed ...$parameter closure || ??????
     * @return null|array|mixed
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:45
     * memo : ????????????????????????null???
     */
    public static function matchNonNullValue(string $key, ...$parameter)
    {
        foreach ($parameter as $member) {
            if (is_null($member)) {
                continue;
            }
            if ($member instanceof Closure) {
                $value = $member($key);
            } else {
                $value = null == $key ? value($member) : data_get($member, $key);
            }
            if (!is_null($value)) {
                return $value;
            }
        }
        return null;
    }

    /**
     * @param null $id
     * @return mixed|\Psr\Container\ContainerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:51
     * memo : null
     */
    public static function di($id = null)
    {
        $container = ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }
        return $container;
    }

}