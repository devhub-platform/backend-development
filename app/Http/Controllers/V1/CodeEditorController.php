<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use App\Services\CodeEditorService;
use Illuminate\Http\JsonResponse;

class CodeEditorController
{
    private CodeEditorService $service;

    public function __construct(CodeEditorService $service)
    {
        $this->service = $service;
    }

    public function runtimes()
    {
        return $this->service->getRuntimes();
    }

    public function execute(CodeEditorRequest $request): JsonResponse
    {
        return $this->service->executeCode($request);
    }
}
