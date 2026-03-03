<?php

namespace App\Services\AI;

class AIResponseParser
{
    public const EMPTY_RESPONSE_MESSAGE = 'I received an empty response. Please try again.';

    /**
     * Parse the raw API response and extract the text content.
     * Handles multiple response formats from different providers.
     */
    public function parse(array $body): string
    {
        if (isset($body['choices'][0]['message']['content'])) {
            return (string) $body['choices'][0]['message']['content'];
        }

        if (isset($body['choices'][0]['text'])) {
            return (string) $body['choices'][0]['text'];
        }

        if (isset($body['response'])) {
            return (string) $body['response'];
        }

        if (isset($body['message'])) {
            return (string) $body['message'];
        }

        if (isset($body['content'])) {
            return (string) $body['content'];
        }

        if (isset($body['data'][0]['content'])) {
            return (string) $body['data'][0]['content'];
        }

        return self::EMPTY_RESPONSE_MESSAGE;
    }
}
