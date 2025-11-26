<?php

namespace App\Http\Controllers\V1;

use App\Http\Requests\CodeEditorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CodeEditorController
{
    private $pistonBaseUrl = 'https://emkc.org/api/v2/piston';

    /**
     * Get available runtimes
     */
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

    public function getPackages()
    {
        try {
            $response = Http::get($this->pistonBaseUrl . '/packages');

            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function installPackages(Request $request)
    {
        $request->validate([
            'language' => 'required|string',
            'version' => 'required|string',
        ]);

        try {
            $response = Http::post("{$this->pistonBaseUrl}/packages", $request->only('language', 'version'));

            return response()->json([
                'success' => true,
                'data' => $response->json()
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch packages',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Execute code from form-data
     */
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
                    'output' => $output,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to execute code',
                'error' => $response->body()
            ], $response->status());

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error executing code',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get file extension based on language
     */
    private function getFileExtension($language)
    {
        $extensions = [
            'python' => 'py',
            'javascript' => 'js',
            'java' => 'java',
            'php' => 'php',
            'cpp' => 'cpp',
            'c' => 'c',
            'csharp' => 'cs',
            'go' => 'go',
            'rust' => 'rs',
            'ruby' => 'rb',
            'typescript' => 'ts',
            'kotlin' => 'kt',
            'swift' => 'swift',
            'bash' => 'sh',
            'r' => 'r',
            'perl' => 'pl',
            'scala' => 'scala',
            'haskell' => 'hs',
        ];

        return $extensions[strtolower($language)] ?? 'txt';
    }
}
