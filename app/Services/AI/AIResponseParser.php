<?php

namespace App\Services\AI;

use Exception;

class AIResponseParser
{
    public function parse(array $body): string
    {
        return
            $body['choices'][0]['message']['content']
            ?? $body['choices'][0]['text']
            ?? throw new Exception('Invalid AI response format');
    }
}
