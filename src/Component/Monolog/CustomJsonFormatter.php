<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Monolog;

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
        return $record['message'];
    }

}
