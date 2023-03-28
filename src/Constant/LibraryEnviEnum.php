<?php

declare(strict_types=1);

namespace Baichuan\Library\Constant;

use Hyperf\Constants\AbstractConstants;

//return [
//    'traceHandlerStatus' => 1,//是否開啟鏈路跟蹤
//    'traceHandlerSync2mongodb' => 1,//是否將輸出同步至mongodb
//    'monologHandlerJsonEncodeStatus' => 1,//是否單行，值：0否，1是
//    'monologHandlerOutput' => 1,//是否輸出至控制台，值：0否，1是
////    'library' => [
////        'handler' => [
////             'traceHandler' => [
////                'status' => 1,//是否開啟鏈路跟蹤
////                'sync2mongodb' => 1//是否將輸出備份至mongodb
////            ],
////            'monologHandler' => [
////                'jsonEncodeStatus' => 1,//是否單行，值：0否，1是
////                'output' => 1,//是否輸出至控制台，值：0否，1是
////            ]
////        ]
////    ]
//];

/**
 * class ContextEnum
 */
class LibraryEnviEnum extends AbstractConstants
{

    public const TraceHandlerStatus = 1;//是否開啟鏈路跟蹤
    public const TraceHandlerSync2mongodb = 1;//是否將輸出同步至mongodb
    public const MonologHandlerJsonEncodeStatus = 1;//是否單行，值：0否，1是
    public const MonologHandlerOutput = 1;//是否輸出至控制台，值：0否，1是

}