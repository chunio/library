<?php

declare(strict_types=1);

namespace Baichuan\Library\Utility;

/**
 * class ContextEnum
 */
class Envi
{

    public const TraceHandlerStatus = 1;//是否開啟鏈路跟蹤
    public const TraceHandlerSync2mongodb = 1;//是否將輸出同步至mongodb
    public const MonologHandlerJsonEncodeStatus = 1;//是否單行，值：0否，1是
    public const MonologHandlerOutput = 1;//是否輸出至控制台，值：0否，1是

}