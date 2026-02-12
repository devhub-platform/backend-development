<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use App\Services\CodeEditorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CodeEditorController
{

    public function __construct(private CodeEditorService $service)
    {
    }

    public function runtimes(): JsonResponse
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

    public function languages(): JsonResponse
    {
        return $this->service->getSupportedLanguages();
    }
}
