<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Baichuan\Library\Constant\AnsiColorEnum;
use Monolog\Formatter\LineFormatter;
use Monolog\Logger;

class CustomLineFormatter extends LineFormatter
{
    public const SIMPLE_FORMAT = "[%datetime%]【%channel%】%level_name%: %message%\ncontext[START]\n%context%\nextra[START]\n%extra%\n";
    //public const SIMPLE_FORMAT = "%datetime%||%channel||%level_name%||%message%||%context%||%extra%\n";

    protected ?string $originFormat;

    protected array $channelColor = [];

    protected bool $enableAnsiColorEnum = false;

    protected bool $hideExtra = false;

    public function __construct(
        ?string $format = null,
        ?string $dateFormat = null,
        bool $allowInlineLineBreaks = false,
        bool $ignoreEmptyContextAndExtra = true,//DEBUG_LABEL
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
        xdebug($vars,'$vars');
        // 重置模板
        $this->format = $this->originFormat;
        // 添加顏色[START]
        $this->changeFormatForLevelName($vars['level']);
        $this->changeFormatForChannel($vars['channel']);
        $this->changeFormatForMysql($vars['channel']);
        // 添加顏色[END]
        $output = parent::format($record);
        $formatLogic = function ($target) {
            $string = '';
            foreach ($target as $key => $value) {
                if ($key == "trace") {
                    continue;
                }
                $value = (is_numeric($value) || is_string($value)) ? $value : prettyJsonEncode($value);
                $string .= "{$key}:{$value}\n";
            }
            return $string;
        };
        if ($record['context']['req'] ?? []) {
            $string = "[request] ：\n" . $formatLogic($record['context']['req']);
            $output = str_replace("context[START]", "context[START]\n{$string}", $output);
        }
        if ($record['context']['resp'] ?? []) {
            $string = "[response] ：\n" . $formatLogic($record['context']['resp']);
            $output = str_replace("context[START]", "context[START]\n{$string}", $output);
        }
        if ($record['context']['details'] ?? []) {
            $string = "[detail] ：\n" . $formatLogic($record['context']['details']);
            $output = str_replace("context[START]", "context[START]\n{$string}", $output);
        }
        // 添加打印位置
        if (isset($vars['log']['file'], $vars['log']['line'])) {
            $fileline = "#" . $vars['log']['file'] . "(" . $vars['log']['line'] . ")";
            $output = str_replace("extra[START]", $fileline . "\nextra[START]", $output);
        }

        if ($this->ignoreEmptyContextAndExtra) {
            if (empty($vars['context'])) {
                $output = str_replace("context[START]\n\n", "", $output);
            }

            if (empty($vars['extra'])) {
                $output = str_replace("extra[START]\n\n", "", $output);
            }
        }

        if ($this->hideExtra) {
            $i = strpos($output, "extra[START]");
            if ($i > 0) {
                $output = substr($output, 0, strpos($output, "extra[START]")) . PHP_EOL;
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
