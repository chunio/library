<?php

declare(strict_types=1);

namespace Baichuan\Library\Component\Exception;

interface ImplementException
{

    public function getAppCode(): int;

    public function getStatusCode(): int;

    public function getMessageLocale(?string $locale = null);

}
