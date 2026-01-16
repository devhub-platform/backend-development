<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\LlamaModelService
 */
class LlamaModelService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\LlamaModelService::class;
    }
}
