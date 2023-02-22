<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Baichuan\Library\Component\Exception;

use Hyperf\Di\MetadataCollector;

class ErrorCodeCollector extends MetadataCollector
{
    /**
     * @var array
     */
    protected static $container = [];

    public static function getValue($code, $key)
    {
        return static::$container[$code][$key] ?? '';
    }
}
