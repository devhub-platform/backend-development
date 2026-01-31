<?php

namespace App\Http\Controllers\V1\AiModels;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\SummarizeLlamaService;

class LlamaController
{
    public function sendLlamaAiRequest(Request $request, SummarizeLlamaService $service)
    {
        return $service->sendAiRequest($request);
    }
}
