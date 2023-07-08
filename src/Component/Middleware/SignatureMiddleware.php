<?php

declare(strict_types=1);

namespace Component\Hyperf\Http\Middleware;

use Baichuan\Library\Constant\ContextEnum;
use Hyperf\Di\Exception\Exception;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Context\Context;

class SignatureMiddleware implements MiddlewareInterface
{//

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //signature[START]
        //.payload={"client_id":10000,"device_type":"ios","device_id":"714367b5ee79b4e391cc075eb2b512d1","account_id":100000019,"package_name":"com#mtios#ryenovel","version":"1#3#0"}.d5fe18479296485de713003a28f54154d0105392d8407e6291bb4449faf79bdf
        /**
         * app : d5fe18479296485de713003a28f54154d0105392d8407e6291bb4449faf79bdf
         * cms : x1fe18479296485de713003a28f54154816k5392d8407e6291bb4449faf79bdu
         */
        if(1){//button
            $currentTime = time();
            $auth = $request->getHeaderLine('auth');
            if(!$auth) {
                throw new Exception('hacker attack (0)');
            }
            try{
                [$payloadBase64, $tokenBase64, $signatureBase64] = explode('.', $auth);
            }catch (\Throwable $e){
                throw new Exception("hacker attack ({$auth} is incorrect)");
            }
            //DEBUG_LABEL[START]//
            if(in_array(env('APP_ENV'), ['local', 'sandbox', 'test', 'official'/*TODO:待優化*/])){
                if($signatureBase64 === 'd5fe18479296485de713003a28f54154d0105392d8407e6291bb4449faf79bdf'){
                    if(substr($tokenBase64,0,8) === 'payload='){//
                        [$field, $payloadJson] = explode('=', $tokenBase64);
                        $payload = json_decode($payloadJson,true);
                        //特殊字符轉換[START]
                        if(isset($payload['version'])){
                            $payload['version'] = str_replace('#', '.',  $payload['version']);
                        }
                        if(isset($payload['package_name'])){
                            $payload['package_name'] = str_replace('#', '.',  $payload['package_name']);
                        }
                        //特殊字符轉換[END]
                        return $payload;
                    }
                    return [//default
                        'client_id' => 10000,
                        'device_id' => 'sandbox0000000000000000000000001',
                        'device_type' => 'postman'
                    ];
                }
                if($signatureBase64 === 'x1fe18479296485de713003a28f54154816k5392d8407e6291bb4449faf79bdu') return ['client_id' => 10001];
            }
            //DEBUG_LABEL[END]
            $payload = json_decode(base64_decode($payloadBase64),true);
            $token = base64_decode($tokenBase64);
            $clientSignature = base64_decode($signatureBase64);
            if(
            !isset(
                $payload['client_id'],//1
                $payload['device_id'],
                $payload['device_type'],//2
                $payload['device_model'],
                $payload['timestamp'],
                $payload['version'],
                $payload['build_number'],
                $payload['package_name']//2
            )
            ) {
                throw new Exception('hacker attack (1)');
            }
            //format[START]
            $payload['client_id'] = intval($payload['client_id']);
            $payload['timestamp'] = intval($payload['timestamp']);
            //format[END]
            if(($currentTime - $payload['timestamp']) > config('system.middleware.signature.timeout',7200)) {
                $code = env('APP_ENV') === 'official' ? 401 : ErrorCodeSys::AUTH_TIMEOUT;//DEBUG_LABEL
                throw new Exception("timestamp error ( {$payload['timestamp']} {$currentTime} )", $code);
            }
            $parameter = array_merge($request->getParsedBody(), $request->getQueryParams());
            $secret = config("system.middleware.signature.client.{$payload['client_id']}.secret");
            //step0：併入{auth_token && timestamp}
            $tempStruct = array_merge($payload, ['token' => $token], $parameter);
            //step1：排除{空值||數組}
            foreach ($tempStruct as $key => $value) {
                if ((!$value && $value !== 0 && $value !== '0') || is_array($value)) unset($tempStruct[$key]);
            }
            //step2：參數鍵名降序排序
            ksort($tempStruct);
            $serverSignature = strtolower(md5(urlencode(http_build_query($tempStruct)) . $secret));
            /*****
            $alarm = [
                '$payload' => $payload,
                '$token' => $token,
                '$clientSignature' => $clientSignature,
                '$secret' => $secret,
                '$tempStruct' => $tempStruct,
                '$serverSignature' => $serverSignature,
                'httpBuildQuery' => http_build_query($tempStruct),
                'urlencode' => urlencode(http_build_query($tempStruct)),
            ];
            *****/
            //step3：http_build_query >> md5 >> strtolower
            if($clientSignature !== $serverSignature) {
                throw new Exception('hacker attack (2)');
            }
        }
        Context::set(ContextEnum::SignaturePayload, $payload ?? []);
        //signature[END]
        return $handler->handle($request);
    }

}
