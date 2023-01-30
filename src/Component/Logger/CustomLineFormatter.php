<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Baichuan\Library\Constant\AnsiColorEnum;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class CustomLineFormatter extends LineFormatter
{
    public const SIMPLE_FORMAT = "[%datetime%]【%channel%】%level_name%: %message%\n%context%\n=== extra ===\n%extra%\n";
    //public const SIMPLE_FORMAT = "%datetime%||%channel||%level_name%||%message%||%context%||%extra%\n";

    protected ?string $originFormat;

    protected array $channelColor = [];

    protected bool $enableAnsiColorEnum = false;

    protected bool $hideExtra = false;

    public function __construct(
        ?string $format = null,
        ?string $dateFormat = null,
        bool $allowInlineLineBreaks = false,
        bool $ignoreEmptyContextAndExtra = false,
        bool $AnsiColorEnum = false,
        bool $hideExtra = true
    ) {
        $this->enableAnsiColorEnum = $AnsiColorEnum;
        $this->hideExtra = $hideExtra;
        $this->originFormat = $format ?: self::SIMPLE_FORMAT;
        parent::__construct($this->originFormat, $dateFormat, $allowInlineLineBreaks, $ignoreEmptyContextAndExtra);
        $this->setJsonPrettyPrint(true);
    }

    public function format(array $record): string
    {
        $vars = $this->normalize($record);

        // 重置模板
        $this->format = $this->originFormat;
        // 添加颜色
        $this->changeFormatForLevelName($vars['level']);
        $this->changeFormatForChannel($vars['channel']);
        $this->changeFormatForMysql($vars['channel']);

        if (isset($record['context']['req'])) {
            $request = $record['context']['req'];
            unset($record['context']['req']);
        }
        if (isset($record['context']['resp'])) {
            $response = $record['context']['resp'];
            unset($record['context']['resp']);
        }
        if (isset($record['context']['details'])) {
            $details = $record['context']['details'];
            unset($record['context']['details']);
        }

        $output = parent::format($record);

        $formatKV = function ($target) {
            $str = '';
            foreach ($target as $k => $v) {
                if ("trace" == $k) {
                    continue;
                }
                $v = (is_numeric($v) || is_string($v)) ? $v : prettyJsonEncode($v);
                $str .= $k . ": " . $v . "\n";
            }
            return $str;
        };

        if (isset($request)) {
            $str = "[request] ：\n" . $formatKV($request);//DEBUG_LABEL
            $output = str_replace("=== context ===", "=== context ===\n{$str}", $output);
        }

        if (isset($response)) {
            $str = "[response] ：\n" . $formatKV($response);//DEBUG_LABEL
            $output = str_replace("=== context ===", "=== context ===\n{$str}", $output);
        }

        if (isset($details)) {
            $str = "[describe] ：\n" . $formatKV($details);//DEBUG_LABEL
            $output = str_replace("=== context ===", "=== context ===\n{$str}", $output);
        }

        // 添加打印位置
        if (isset($vars['log']['file'], $vars['log']['line'])) {
            $fileline = "#" . $vars['log']['file'] . "(" . $vars['log']['line'] . ")";
            $output = str_replace("=== extra ===", $fileline . "\n=== extra ===", $output);
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                $output = str_replace("=== context ===\n\n", '', $output);
            }

            if (empty($vars['extra'])) {
                $output = str_replace("=== extra ===\n\n", '', $output);
            }
        }

        if ($this->hideExtra) {
            $i = strpos($output, "=== extra ===");
            if ($i > 0) {
                $output = substr($output, 0, strpos($output, "=== extra ===")) . PHP_EOL;
            }
        }

        return $output;
    }

    protected function levelToAnsiColorEnum($level): int
    {
        switch ($level) {
            case Logger::EMERGENCY:
            case Logger::ALERT:
            case Logger::CRITICAL:
                return AnsiColorEnum::FG_PURPLE;
            case Logger::ERROR:
                return AnsiColorEnum::FG_RED;
            case Logger::WARNING:
            case Logger::NOTICE:
                return AnsiColorEnum::FG_YELLOW;
            case Logger::INFO:
                return AnsiColorEnum::FG_GREEN;
            default:
                return AnsiColorEnum::FG_SKY;
        }
    }

    protected function randomAnsiColorEnum($k)
    {
        static $colors = [
            //            AnsiColorEnum::FG_BLACK,
            //            AnsiColorEnum::FG_RED,
            AnsiColorEnum::FG_GREEN,
            AnsiColorEnum::FG_YELLOW,
            AnsiColorEnum::FG_BLUE,
            AnsiColorEnum::FG_PURPLE,
            AnsiColorEnum::FG_SKY,
        ];
        $random = (crc32($k) >> 16);
        $random = $random % count($colors);
        return $colors[$random];
    }

    protected function changeFormatForLevelName($level)
    {
        $level_name_color = $this->levelToAnsiColorEnum($level);
        $this->format = $this->changeFormatStringColor($this->format, 'level_name', $level_name_color);
    }

    protected function changeFormatForChannel($channel)
    {
        if (empty($this->channelColor[$channel])) {
            $this->channelColor[$channel] = $this->randomAnsiColorEnum($channel);
        }

        $channel_color = $this->channelColor[$channel];
        $this->format = $this->changeFormatStringColor($this->format, 'channel', $channel_color);
    }

    protected function changeFormatForMysql($channel)
    {
        if (false !== strpos($channel, 'sql')) {
            $channel_color = $this->channelColor['sql'];
            $this->format = $this->changeFormatStringColor($this->format, 'message', $channel_color);
        }
    }

    protected function changeFormatStringColor($format, $name, $color)
    {
        return $this->enableAnsiColorEnum ? str_replace("%{$name}%", colorString("%{$name}%", $color), $format) : $format;
    }

    protected function convertToString($data): string
    {
        if (null === $data || is_bool($data)) {
            return var_export($data, true);
        }

        if (is_scalar($data)) {
            return (string)$data;
        }

        $rtn = "";
        foreach ($data as $k => $v) {
            $rtn .= "{$k} : " . parent::convertToString($v) . PHP_EOL;
        }
        return $rtn;
    }
}
