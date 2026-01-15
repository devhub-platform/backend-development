<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use App\Services\CodeEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CodeEditorController
{
    private CodeEditorService $service;

    public function __construct(CodeEditorService $service)
    {
        $this->service = $service;
    }

    # c++ , python, javascript, java, php, ruby, go, csharp , swift , kotlin , rust , typescript , etc.
    public function runtimes()
    {
        return $this->service->getRuntimes();
    }

    public function execute(CodeEditorRequest $request): JsonResponse
    {
        return $this->service->executeCode($request);
    }

    public function searchInRuntimes(Request $request): JsonResponse
    {
        return $this->service->searchInRuntimes($request);
    }
}
