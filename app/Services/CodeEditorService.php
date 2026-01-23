<?php

namespace App\Services;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

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

    public function searchInRuntimes(Request $request): JsonResponse
    {
        $validated = $request->input('q')
            ?? $request->input('query')
            ?? $request->input('search')
            ?? $request->input('language')
            ?? 'python';

        $searchTerm = Str::trim(Str::lower($validated));

        try {
            $response = Http::get($this->pistonBaseUrl . '/runtimes');
            $runtimes = $response->json();

            if (!is_array($runtimes)) {
                throw new \UnexpectedValueException('Invalid response format from runtimes API');
            }

            $filteredRuntimes = array_filter($runtimes, function ($runtime) use ($searchTerm) {
                $language = strtolower($runtime['language'] ?? '');
                $version = strtolower($runtime['version'] ?? '');
                return strpos($language, $searchTerm) !== false || strpos($version, $searchTerm) !== false;
            });

            if (empty($filteredRuntimes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No runtimes found matching the search term'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => array_values($filteredRuntimes)
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching runtimes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching runtimes',
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
