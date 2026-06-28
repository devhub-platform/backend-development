<?php

namespace App\Services;

use App\Http\Requests\CodeEditorRequests\CodeEditorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Client\PendingRequest;

class CodeEditorService
{
    private string $pistonBaseUrl;
    private ?string $pistonApiKey;

    public function __construct()
    {
        $this->pistonBaseUrl = config('services.piston.base_url');
        $this->pistonApiKey = config('services.piston.api_key');
    }

    private function httpClient(int $timeout = 10): PendingRequest
    {
        $client = Http::timeout($timeout);

        if ($this->pistonApiKey) {
            $client->withHeaders([
                'Authorization' => $this->pistonApiKey,
            ]);
        }

        return $client;
    }

    public function getRuntimes(): JsonResponse
    {
        try {
            $response = $this->httpClient()->get($this->pistonBaseUrl . '/runtimes');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch runtimes from Piston API');
            }

            $runtimes = $response->json();

            return response()->json([
                'success' => true,
                'data' => $runtimes,
                'count' => count($runtimes)
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

    public function executeCode(CodeEditorRequest $request): JsonResponse
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
            ],
        ];

        if (array_key_exists('stdin', $validated)) {
            $pistonPayload['stdin'] = (string)$validated['stdin'];
        }

        try {
            $response = $this->httpClient($timeout + 5)
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
            $run = $result['run'] ?? [];

            return response()->json([
                'success' => true,
                'language' => $result['language'] ?? $validated['language'],
                'version' => $result['version'] ?? $validated['version'],
                'run' => [
                    'stderr' => $run['signal'] ?? '',
                    'stdout' => $run['stdout'] ?? '',
                    'stderr' => $run['stderr'] ?? '',
                    'code' => $run['code'] ?? 0,
                    'output' => $run['output'] ?? trim(($run['stdout'] ?? '') . ($run['stderr'] ?? '')),
                    'memory' => $run['memory'] ?? null,
                    'message' => $run['message'] ?? null,
                    'cpu_time' => $run['cpu_time'] ?? null,
                    'wall_time' => $run['wall_time'] ?? null,
                    'status' => $run['status'] ?? null,
                ],
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

        $searchTerm = Str::trim(Str::lower((string)$searchTerm));

        if ($searchTerm === '') {
            return response()->json([
                'success' => false,
                'message' => 'Search term is required.',
            ], 400);
        }

        if (Str::length($searchTerm) < 1) {
            return response()->json([
                'success' => false,
                'message' => 'Search term must be at least 2 characters.',
            ], 422);
        }

        $perPage = max(1, min((int)$request->input('per_page', 20), 100));
        $page = max(1, (int)$request->input('page', 1));
        $grouped = filter_var($request->input('grouped', false), FILTER_VALIDATE_BOOLEAN);

        try {
            $response = $this->httpClient()->get($this->pistonBaseUrl . '/runtimes');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch runtimes from Piston API (HTTP ' . $response->status() . ')');
            }

            $runtimes = $response->json();

            if (!is_array($runtimes)) {
                throw new \UnexpectedValueException('Invalid response format from runtimes API.');
            }


            $filtered = array_values(array_filter($runtimes, function (array $runtime) use ($searchTerm) {
                if (str_contains(strtolower($runtime['language'] ?? ''), $searchTerm)) {
                    return true;
                }

                if (str_contains(strtolower($runtime['version'] ?? ''), $searchTerm)) {
                    return true;
                }

                foreach ($runtime['aliases'] ?? [] as $alias) {
                    if (str_contains(strtolower((string)$alias), $searchTerm)) {
                        return true;
                    }
                }

                return false;
            }));

            if (empty($filtered)) {
                return response()->json([
                    'success' => false,
                    'message' => "No runtimes found matching \"{$searchTerm}\".",
                    'search_term' => $searchTerm,
                ], 404);
            }

            $totalCount = count($filtered);

            if ($grouped) {
                $groupedData = [];
                foreach ($filtered as $runtime) {
                    $lang = $runtime['language'] ?? 'unknown';
                    $groupedData[$lang][] = [
                        'version' => $runtime['version'] ?? null,
                        'aliases' => $runtime['aliases'] ?? [],
                    ];
                }
                ksort($groupedData);

                return response()->json([
                    'success' => true,
                    'search_term' => $searchTerm,
                    'total' => $totalCount,
                    'data' => $groupedData,
                ]);
            }


            $offset = ($page - 1) * $perPage;
            $pageItems = array_slice($filtered, $offset, $perPage);
            $lastPage = (int)ceil($totalCount / $perPage);

            return response()->json([
                'success' => true,
                'search_term' => $searchTerm,
                'data' => $pageItems,
                'meta' => [
                    'total' => $totalCount,
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'last_page' => $lastPage,
                    'from' => $totalCount > 0 ? $offset + 1 : null,
                    'to' => $totalCount > 0 ? min($offset + $perPage, $totalCount) : null,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error searching runtimes: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error searching runtimes.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    public function getSupportedLanguages(): JsonResponse
    {
        try {
            $response = $this->httpClient()->get($this->pistonBaseUrl . '/runtimes');

            if (!$response->successful()) {
                throw new \Exception('Failed to fetch runtimes');
            }

            $runtimes = $response->json();

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
            'lua' => 'lua', 'groovy' => 'groovy', 'dart' => 'dart',
            'bash' => 'sh', 'shell' => 'sh', 'python2' => 'py', 'python3' => 'py',
            'dash' => 'dash', 'racket' => 'rkt', 'f#' => 'fs', 'fsharp' => 'fs',
        ];

        return $extensions[$lang] ?? 'txt';
    }
}
