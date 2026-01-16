<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\LlamaModelService;

class LlamaController
{
    public function sendLlamaAiRequest(Request $request, LlamaModelService $service)
    {
        return $service->sendAiRequest($request);
    }
}
