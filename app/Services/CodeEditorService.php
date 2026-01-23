<?php

namespace App\Services;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CodeEditorService
{
    private $pistonBaseUrl = 'https://emkc.org/api/v2/piston';
    private const RUNTIMES_CACHE_KEY = 'code_editor_runtimes';
    private const RUNTIMES_CACHE_TTL = 3600;

    /**
     * Get all available runtimes with caching
     */
    public function getRuntimes()
    {
        try {
            $cachedRuntimes = Cache::get(self::RUNTIMES_CACHE_KEY);
            if ($cachedRuntimes !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedRuntimes,
                    'cached' => true
                ]);
            }

            $response = Http::timeout(10)->get($this->pistonBaseUrl . '/runtimes');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch runtimes from Piston API');
            }

            $runtimes = $response->json();

            Cache::put(self::RUNTIMES_CACHE_KEY, $runtimes, self::RUNTIMES_CACHE_TTL);

            return response()->json([
                'success' => true,
                'data' => $runtimes,
                'cached' => false
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching runtimes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch runtimes',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Execute code with the specified language and version
     */
    public function executeCode(CodeEditorRequest $request)
    {
        $validated = $request->validated();

        $extension = $this->getFileExtension($validated['language']);
        $timeout = $validated['timeout'] ?? 30;

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

            $response = Http::timeout($timeout + 5)
                ->post($this->pistonBaseUrl . '/execute', $pistonPayload);

            if (!$response->successful()) {
                Log::warning('Piston API error: ' . $response->status(), ['body' => $response->body()]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to execute code',
                    'error' => $this->parseExecutionError($response)
                ], $response->status());
            }

            $result = $response->json();

            return response()->json([
                'success' => true,
                'language' => $validated['language'],
                'version' => $validated['version'],
                'file' => 'main.' . $extension,
                'output' => $this->parseExecutionOutput($result),
                'execution_time' => $result['run']['compile_output'] ?? null,
                'exit_code' => $result['run']['code'] ?? 0,
            ]);

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('Connection error executing code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to code execution service',
                'error' => 'Connection timeout'
            ], 504);
        } catch (\Exception $e) {
            Log::error('Error executing code: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error executing code',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function searchInRuntimes(Request $request): JsonResponse
    {
        $searchTerm = $request->input('q')
            ?? $request->input('query')
            ?? $request->input('search')
            ?? $request->input('language');

        if (!$searchTerm) {
            return response()->json([
                'success' => false,
                'message' => 'Search term is required'
            ], 400);
        }

        $searchTerm = Str::trim(Str::lower($searchTerm));

        try {

            $cachedRuntimes = Cache::get(self::RUNTIMES_CACHE_KEY);

            if ($cachedRuntimes === null) {
                $response = Http::timeout(10)->get($this->pistonBaseUrl . '/runtimes');
                if (!$response->successful()) {
                    throw new \Exception('Failed to fetch runtimes');
                }
                $runtimes = $response->json();
                Cache::put(self::RUNTIMES_CACHE_KEY, $runtimes, self::RUNTIMES_CACHE_TTL);
            } else {
                $runtimes = $cachedRuntimes;
            }

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
                    'message' => 'No runtimes found matching: ' . htmlspecialchars($searchTerm),
                    'search_term' => $searchTerm
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => array_values($filteredRuntimes),
                'count' => count($filteredRuntimes)
            ]);
        } catch (\Exception $e) {
            Log::error('Error searching runtimes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error searching runtimes',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getSupportedLanguages(): JsonResponse
    {
        try {
            $runtimes = Cache::get(self::RUNTIMES_CACHE_KEY);

            if ($runtimes === null) {
                $response = Http::timeout(10)->get($this->pistonBaseUrl . '/runtimes');
                if (!$response->successful()) {
                    throw new \Exception('Failed to fetch runtimes');
                }
                $runtimes = $response->json();
                Cache::put(self::RUNTIMES_CACHE_KEY, $runtimes, self::RUNTIMES_CACHE_TTL);
            }

            // Extract unique languages
            $languages = array_unique(array_column($runtimes, 'language'));
            sort($languages);

            return response()->json([
                'success' => true,
                'data' => array_values($languages),
                'count' => count($languages)
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting supported languages: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error getting supported languages',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Clear the runtimes cache
     */
    public function clearRuntimesCache(): JsonResponse
    {
        try {
            Cache::forget(self::RUNTIMES_CACHE_KEY);
            return response()->json([
                'success' => true,
                'message' => 'Runtimes cache cleared successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error clearing cache',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Parse execution output from Piston API response
     */
    private function parseExecutionOutput(array $result): array
    {
        $stdout = $result['run']['stdout'] ?? '';
        $stderr = $result['run']['stderr'] ?? '';
        $output = trim($stdout . ($stderr ? PHP_EOL . $stderr : ''));

        return [
            'stdout' => trim($stdout),
            'stderr' => trim($stderr),
            'combined' => $output ?: 'No output produced'
        ];
    }

    private function parseExecutionError($response): string
    {
        try {
            $body = $response->json();
            return $body['message'] ?? 'Unknown error occurred';
        } catch (\Exception $e) {
            return 'Failed to parse error response';
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
            'golang' => 'go', 'clojure' => 'clj', 'elixir' => 'exs',
            'lua' => 'lua', 'groovy' => 'groovy', 'dart' => 'dart'
        ];

        return $extensions[$lang] ?? 'txt';
    }
}
