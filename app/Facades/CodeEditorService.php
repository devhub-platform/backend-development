<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \App\Services\CodeEditorService
 */
class CodeEditorService extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\CodeEditorService::class;
    }
}
