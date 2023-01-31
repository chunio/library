<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Logger;

use Monolog\Formatter\JsonFormatter;

class CustomJsonFormatter extends JsonFormatter
{

    /** @var self::BATCH_MODE_* */
    protected $batchMode;
    /** @var bool */
    protected $appendNewline;
    /** @var bool */
    protected $ignoreEmptyContextAndExtra;
    /** @var bool */
    protected $includeStacktraces = false;

    /**
     * @param self::BATCH_MODE_* $batchMode
     */
    public function __construct(int $batchMode = self::BATCH_MODE_JSON, bool $appendNewline = true, bool $ignoreEmptyContextAndExtra = false, bool $includeStacktraces = false)
    {
        $this->batchMode = $batchMode;
        $this->appendNewline = $appendNewline;
        $this->ignoreEmptyContextAndExtra = $ignoreEmptyContextAndExtra;
        $this->includeStacktraces = $includeStacktraces;

        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function format(array $record): string
    {
        xdebug($record,'jsonformatcomein');
        $normalized = $this->normalize($record);
        if (isset($normalized['context']) && $normalized['context'] === []) {
            if ($this->ignoreEmptyContextAndExtra) {
                unset($normalized['context']);
            } else {
                $normalized['context'] = new \stdClass;
            }
        }
        if (isset($normalized['extra']) && $normalized['extra'] === []) {
            if ($this->ignoreEmptyContextAndExtra) {
                unset($normalized['extra']);
            } else {
                $normalized['extra'] = new \stdClass;
            }
        }
        xdebug($normalized,'$normalized');
        $return = $this->toJson($normalized, true) . ($this->appendNewline ? "\n" : '');
        xdebug($return,'$return');
        return $return;
    }

}
