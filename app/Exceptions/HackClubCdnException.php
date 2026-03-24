<?php

namespace App\Exceptions;

use RuntimeException;

class HackClubCdnException extends RuntimeException
{
    public function __construct(
        string                 $message,
        private readonly int   $status = 0,
        private readonly array $responseBody = [],
    )
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    public function responseBody(): array
    {
        return $this->responseBody;
    }
}

