<?php

namespace App\Services;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CodeEditorService
{
    private $pistonBaseUrl = 'https://emkc.org/api/v2/piston';

    public function getRuntimes()
    {
        try {
            $response = Http::get($this->pistonBaseUrl . '/runtimes');

            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch runtimes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function executeCode(CodeEditorRequest $request)
    {
        $validated = $request->validated();

        $extension = $this->getFileExtension($validated['language']);

        $pistonPayload = [
            'language' => $validated['language'],
            'version' => $validated['version'],
            'files' => [
                [
                    'name' => 'main.' . $extension,
                    'content' => $validated['code']
                ]
            ]
        ];

        try {
            $response = Http::timeout(35)
                ->post($this->pistonBaseUrl . '/execute', $pistonPayload);

            $result = $response->json();

            if ($response->successful()) {
                $stdout = $result['run']['stdout'] ?? '';
                $stderr = $result['run']['stderr'] ?? '';
                $output = trim($stdout . ($stderr ? PHP_EOL . $stderr : ''));

                return response()->json([
                    'language' => $validated['language'],
                    'file' => 'main.' . $extension,
                    'output' => $output,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute code',
                'error' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            Log::error('Error executing code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error executing code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getFileExtension($language)
    {
        $lang = strtolower(trim($language));

        $extensions = [
            'python' => 'py', 'py' => 'py',
            'javascript' => 'js', 'js' => 'js',
            'java' => 'java',
            'php' => 'php',
            'c++' => 'cpp', 'cpp' => 'cpp',
            'c' => 'c',
            'csharp' => 'cs', 'c#' => 'cs', 'cs' => 'cs',
            'go' => 'go',
            'rust' => 'rs',
            'ruby' => 'rb', 'rb' => 'rb',
            'typescript' => 'ts', 'ts' => 'ts',
            'kotlin' => 'kt', 'kt' => 'kt',
            'swift' => 'swift',
            'bash' => 'sh', 'sh' => 'sh',
            'r' => 'r',
            'perl' => 'pl', 'pl' => 'pl',
            'scala' => 'scala',
            'haskell' => 'hs', 'hs' => 'hs',
        ];

        return $extensions[$lang] ?? 'txt';
    }
}
