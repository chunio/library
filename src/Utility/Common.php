<?php

declare(strict_types=1);

use Baichuan\Library\Constant\AsciiEnum;
use Baichuan\Library\Handler\ModelHandler;
use Baichuan\Library\Handler\MongoDBHandler;
use Baichuan\Library\Handler\MonologHandler;
use Baichuan\Library\Constant\AnsiColorEnum;
use Baichuan\Library\Handler\ContextHandler;
use GuzzleHttp\Cookie\CookieJar;
use Hyperf\Kafka\ProducerManager;
use Hyperf\Redis\RedisFactory;

if(!function_exists('variableFormatter')){
    function variableFormatter(&$variable/*, bool $jsonEncodeStatus = false*/)
    {
        if ($variable === true) return 'TRUE(BOOL)';
        if ($variable === false) return 'FALSE(BOOL)';
        if ($variable === null) return 'NULL';
        if ($variable === '') return "(EMPTY STRING)";
        if ($variable instanceof Throwable) return ['message' => $variable->getMessage(), 'trace' => $variable->getTrace()];
        if(is_object($variable)/* && $jsonEncodeStatus*/) return (array)$variable;
        //解決json_encode()錯誤：Malformed UTF-8 characters, possibly incorrectly encoded
        //if(is_string($variable)) return mb_convert_encoding($variable, 'UTF-8', 'UTF-8,GBK,GB2312,BIG5');
        return $variable;
    }
}

if(!function_exists('traceFormatter')){
    /**
     * @param $variable
     * @param string $label
     * @param int $debugBacktraceLimit
     * @param bool $unit 是否添加分隔符
     * @return string
     * author : zengweitao@gmail.com
     * datetime: 2023/03/02 15:45
     * memo : null
     */
    function traceFormatter(&$variable, string $label = 'default', int $debugBacktraceLimit = 2, bool $separator = true)/*: string|array*/
    {
        try {
            $traceInfo = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $debugBacktraceLimit);//TODO：此函數性能如何？
            $file1 = ($startIndex = strrpos(($file1 = $traceInfo[$debugBacktraceLimit - 1]['file']), env('APP_NAME'))) ? substr($file1, $startIndex + 1) : $file1;
            $traceArray = [
                'date' => date('Y-m-d H:i:s'),
                "script" =>  "./{$file1}(line:{$traceInfo[$debugBacktraceLimit - 1]['line']})",
                'label' => $label,
                'message' => variableFormatter($variable),
            ];
            //check memory[START]
//            $traceJson = prettyJsonEncode($traceArray);
//            if(strlen($traceJson) > (($megabyteLimit = 1024/*unit:KB*/) * 1024)){//超出限額則截取
//                $jsonEncodeStatus = true;
//                $traceJson = substr($traceJson, 0,$megabyteLimit * 1024);
//            }
            //check memory[END]
            if($separator){
                $traceArray['traceId'] = ContextHandler::pullTraceId();
                if(MonologHandler::$output ?? false) {
                    $trace = prettyJsonEncode($traceArray) . "\n";
                }else{
                    $trace = "\n:<<UNIT[START]\n" . print_r($traceArray, true) . "\nUNIT[END]\n";//print_r()的換行會將大變量瞬間膨脹導致內存滿載
                }
                if(MonologHandler::$output ?? true) echo $trace;

            }
            return $trace ?? $traceArray;
        } catch (Throwable $e) {
            return prettyJsonEncode([
                'traceId' => ContextHandler::pullTraceId(),
                'date' => date('Y-m-d H:i:s'),
                'script' => $e->getFile() . "(line:{$e->getLine()})",
                'label' => "{$label} throwable",
                'message' => $e->getMessage(),
            ]);
        }
    }
}

if (!function_exists('monolog')) {
    function monolog($variable, string $label = '', string $level = 'info', $monolog = true): bool
    {
        if($monolog && class_exists(MonologHandler::class)){
            MonologHandler::$level($variable, $label);
        }else{
            //非協程I/O[START]
            $path = BASE_PATH . "/runtime/logs/" . __FUNCTION__ . "-0000-00-" . date("d") . ".log";//keep it for one month
            if (!file_exists($path)) touch($path);//compatible file_put_contents() cannot be created automatically
            $trace = traceFormatter($variable, $label);
            if (abs(filesize($path)) > 1024 * 1024 * 1024) {//flush beyond the limit/1024m
                file_put_contents($path, $trace/*, LOCK_EX*/); //TODO:阻塞風險
            } else {
                file_put_contents($path, $trace, FILE_APPEND/* | LOCK_EX*/);
            }
            if(matchEnvi('local')) echo "$trace\n";
            //非協程I/O[END]
        }
        return true;
    }
}

if(!function_exists('sendAlarm2DingTalk')){
    function sendAlarm2DingTalk($variable)
    {
        $timestamp = time() * 1000;
        $accessToken = 'b76e1cf33a222a8ddee2fde1c930be03cdc1f04a31d1a1036be9803a6f712319';
        $secret = 'SEC8e6642f7e93939b4e04edefc7e06248d8b8c8120c8ff439879fc1ad5970ff601';
        $content = '';
        $content .= "[" . env('APP_NAME') . ' / ' . env('APP_ENV') . "]";
        $content .=  str_replace("\"","'", traceFormatter($variable, ''));
        $content = prettyJsonEncode([
            'msgtype' => 'text',
            'text' => [
                'content' => $content
            ]
        ]);
        $parameter = [
            'access_token' => $accessToken,
            'timestamp' => $timestamp,
            'sign' => urlencode(base64_encode(hash_hmac('sha256', $timestamp . "\n" . $secret, $secret, true)))
        ];
        $webhook = "https://oapi.dingtalk.com/robot/send?" . http_build_query($parameter);
        $option = [
            'http' => [
                'method' => "POST",
                'header' => "Content-type:application/json;charset=utf-8",//
                'content' => $content
            ],
            "ssl" => [ //不驗證ssl證書
                "verify_peer" => false,
                "verify_peer_name" => false
            ]
        ];
        return file_get_contents($webhook, false, stream_context_create($option));
    }
}


if (!function_exists('di')) {
    /**
     * @param null $id
     * @return mixed|\Psr\Container\ContainerInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:51
     * memo : null
     */
    function di($id = null)
    {
        $container = Hyperf\Utils\ApplicationContext::getContainer();
        if ($id) {
            return $container->get($id);
        }
        return $container;
    }
}

if (!function_exists('matchNonNullValue')) {
    /**
     * @param mixed $key 如key等於null，則取$parameter裡第一個非null值；如key不等於null，則取第一個$parameter($key)或$parameter[$key]的非null值
     * @param mixed ...$parameter closure || 數組
     * @return null|array|mixed
     * author : zengweitao@gmail.com
     * datetime: 2023/01/29 17:45
     * memo : 順序檢索第一个非null值
     */
    function matchNonNullValue(string $key, ...$parameter)
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
}

if (!function_exists('matchEnvi')) {
    /**
     * author : zengweitao@gmail.com
     * datetime: 2023/01/30 11:08
     * memo : 環境匹配
     */
    function matchEnvi(string $envi): bool
    {
        return env('APP_ENV') == $envi;
    }
}

if (!function_exists('redisInstance')) {
    function redisInstance(string $poolName = 'default'): Hyperf\Redis\Redis
    {
        return di()->get(RedisFactory::class)->get($poolName);
    }
}

if (!function_exists('kafkaInstance')) {
    function kafkaInstance(string $poolName = 'default'): Hyperf\Kafka\Producer
    {
        return di()->get(ProducerManager::class)->getProducer($poolName);
    }
}

if (!function_exists('mongoDBHandler')) {
    function mongoDBHandler(string $collection, string $db = ''): MongoDBHandler
    {
        return make(MongoDBHandler::class, [$collection, $db]);
    }
}

if (!function_exists('modelHandler')) {
    function modelHandler(string $model): ModelHandler
    {
        return new ModelHandler($model);
    }
}

if (!function_exists('prettyJsonEncode')) {
    /**
     * author : zengweitao@gmail.com
     * datetime: 2023/01/30 15:10
     * memo : null
     */
    function prettyJsonEncode($object, ?int $flag = JSON_PRETTY_PRINT)//: string|bool
    {
        //try {
            //JSON_PRETTY_PRINT//易讀格式（即：自動換行）
            $flagCounter = JSON_UNESCAPED_SLASHES/*不轉義反斜杠*/ | JSON_UNESCAPED_UNICODE/*unicode轉至中文*/;
            if (!$flag) {
                $flagCounter |= $flag;
            }
            return json_encode($object, $flagCounter);
        //}catch (\Throwable $e){
        //    return "json_encode() throwable : ". json_last_error_msg();
        //}
    }
}

if (!function_exists('colorString')) {
    function colorString(string $string, int $fg = AnsiColorEnum::FG_BLACK, int $bg = null): string
    {
        if ($bg) {
            return "\033[{$bg};{$fg}m{$string}\033[0m";
        }
        return "\033[{$fg}m{$string}\033[0m";
    }
}

if(!function_exists('commonHttpGet')){
    function commonHttpGet(string $uri, array $query = [], array $cookieDetail = [], string $cookieDomain = '')
    {
        $config = [
            'query' => $query,
        ];
        if($cookieDetail && $cookieDomain){
            $config['cookies'] = CookieJar::fromArray($cookieDetail, $cookieDomain);
        }
        $client = new GuzzleHttp\Client($config);
        $result = json_decode((string)$client->request('get', $uri, $config)->getBody(), true);
        return $result;
    }
}

if(!function_exists('commonHttpPost')){
    function commonHttpPost(string $uri, array $body = [], $header = ['Content-Type' => 'application/json'], array $cookieDetail = [], string $cookieDomain = '')
    {
        $config = [
            'headers' => $header,
            'json' => $body,
        ];
        if($cookieDetail && $cookieDomain){
            $config['cookies'] = CookieJar::fromArray($cookieDetail, $cookieDomain);
        }
        $client = new GuzzleHttp\Client($config);
        $result = json_decode((string)$client->request('get', $uri, $config)->getBody(), true);
        return $result;
    }
}

//if (!function_exists('idemExecute')) {
//    function idemExecute(callable $callable, int $ttl = 3)
//    {
//        //example : App\Controller\IndexController_index_203b44837a4e70669009dd664e81769a
//        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
//        $adminInfo = Context::get('adminInfo') ?? [];
//        $parameter = (new ReflectionFunction($callable))->getStaticVariables();
//        $unique = md5(json_encode($adminInfo) . json_encode($parameter));
//        $redisKey = (isset($trace[1]['class'], $trace[1]['function']) ? ("{$trace[1]['class']}_{$trace[1]['function']}_") : ((string)time() . "_")) . $unique;
//        $Redis = redisInstance();
//        $result = $Redis->get($redisKey);
//        if (false === $result) {
//            $result = json_encode($callable());
//            $Redis->set($redisKey, $result, $ttl);
//        }
//        return json_decode($result, true);
//    }
//}

if (!function_exists('multiPagination')) {
    function multiPagination(array $list, int $pageIndex, int $pageSize): array
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
}

if (!function_exists('commonOrder')) {
    function commonOrder(array $array, string $slaveField, string $sort = 'DESC'): array
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
}

if (!function_exists('filterControlCharacter')) {
    /**
     * @param string $string
     * @param string $replace
     * @return string
     * author : zengweitao@gmail.com
     * datetime: 2023/03/01 16:56
     * memo : 過濾[ASCII]控制字符
     */
    function filterControlCharacter(string $string)
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
}


