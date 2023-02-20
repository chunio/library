<?php

declare(strict_types=1);

namespace Baichuan\Library\Handler;

use GeoIp2\Database\Reader;

/**
 * Class GeoIPHandler
 * @package Baichuan\Library\Handler
 * author : zengweitao@gmail.com
 * datetime: 2023/02/20 16:08
 * memo : null
 */
class GeoIPHandler
{

    public function abstract(string $ip): string
    {
        $db = BASE_PATH . '/resource/GeoLite2-City.mmdb';
        $Reader = new Reader($db);
        try{
            $record = $Reader->city($ip);
            $continent = $record->continent->names['zh-CN'] ?? '';//洲
            $country = $record->country->names['zh-CN'] ?? '';
            $subdivisionClass = json_encode($record->subdivisions);
            $subdivisionArray = json_decode($subdivisionClass,true);
            $subdivision = $subdivisionArray[0]['names']['zh-CN'] ?? '';
            $city = $record->city->names['zh-CN'] ?? '';
            return "{$continent} {$country} {$subdivision} {$city}";
        }catch (\Throwable $e){
            return '';
        }
    }

}

