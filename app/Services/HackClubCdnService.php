<?php

namespace App\Services;

use App\Exceptions\HackClubCdnException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class HackClubCdnService
{
    /**
     * Resolve a stored path/URL into a public CDN URL.
     */
    public function resolvePublicUrl(?string $pathOrUrl): ?string
    {
        if (!is_string($pathOrUrl)) {
            return null;
        }

        $value = trim($pathOrUrl);

        if ($value === '') {
            return null;
        }

        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }

        $publicBaseUrl = rtrim((string) config('services.hackclub_cdn.public_base_url', 'https://cdn.hackclub.com/api/v4'), '/');

        return $publicBaseUrl . '/' . ltrim($value, '/');
    }

    /**
     * Upload a local uploaded file to Hack Club CDN.
     */
    public function uploadFile(UploadedFile $file): array
    {
        $stream = fopen($file->getRealPath(), 'r');

        if ($stream === false) {
            throw new HackClubCdnException('Unable to read file stream before upload.');
        }

        try {
            $response = $this->client()
                ->attach('file', $stream, $file->getClientOriginalName())
                ->post('upload');
        } catch (Throwable $exception) {
            throw new HackClubCdnException(
                'Hack Club CDN request failed while uploading file: ' . Str::limit($exception->getMessage(), 300),
                (int) $exception->getCode(),
            );
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }

        return $this->decodeResponse($response->status(), $response->json(), 'upload file');
    }

    /**
     * Upload an image to Hack Club CDN by source URL.
     */
    public function uploadFromUrl(string $url, ?string $downloadAuthorization = null): array
    {
        $request = $this->client();

        if (!empty($downloadAuthorization)) {
            $request = $request->withHeaders([
                'X-Download-Authorization' => $downloadAuthorization,
            ]);
        }

        try {
            $response = $request
                ->asJson()
                ->post('upload_from_url', [
                    'url' => $url,
                ]);
        } catch (Throwable $exception) {
            throw new HackClubCdnException(
                'Hack Club CDN request failed while uploading from URL: ' . Str::limit($exception->getMessage(), 300),
                (int) $exception->getCode(),
            );
        }

        return $this->decodeResponse($response->status(), $response->json(), 'upload from URL');
    }

    /**
     * Get the authenticated account and quota information.
     */
    public function me(): array
    {
        try {
            $response = $this->client()->get('me');
        } catch (Throwable $exception) {
            throw new HackClubCdnException(
                'Hack Club CDN request failed while fetching account data: ' . Str::limit($exception->getMessage(), 300),
                (int) $exception->getCode(),
            );
        }

        return $this->decodeResponse($response->status(), $response->json(), 'fetch account data');
    }

    /**
     * Helper method when callers only need the uploaded file URL.
     */
    public function uploadFileUrl(UploadedFile $file): string
    {
        $data = $this->uploadFile($file);

        return $this->extractUrl($data, 'upload file');
    }

    /**
     * Helper method when callers only need the uploaded URL from source URL upload.
     */
    public function uploadFromUrlOnly(string $url, ?string $downloadAuthorization = null): string
    {
        $data = $this->uploadFromUrl($url, $downloadAuthorization);

        return $this->extractUrl($data, 'upload from URL');
    }

    private function client(): PendingRequest
    {
        $token = (string) config('services.hackclub_cdn.token');

        if ($token === '') {
            throw new HackClubCdnException('Hack Club CDN token is missing. Set HACKCLUB_API_CDN in the environment.');
        }

        $baseUrl = rtrim((string) config('services.hackclub_cdn.base_url', 'https://cdn.hackclub.com/api/v4'), '/');
        $timeout = (int) config('services.hackclub_cdn.timeout', 30);
        $retryTimes = (int) config('services.hackclub_cdn.retry_times', 2);
        $retrySleepMs = (int) config('services.hackclub_cdn.retry_sleep_ms', 200);

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->withToken($token)
            ->timeout($timeout)
            ->retry($retryTimes, $retrySleepMs);
    }

    private function decodeResponse(int $status, mixed $payload, string $operation): array
    {
        $body = is_array($payload) ? $payload : [];

        if ($status >= 200 && $status < 300) {
            return $body;
        }

        $message = (string) ($body['error'] ?? $body['message'] ?? 'Unexpected Hack Club CDN API error.');

        if (isset($body['quota']) && is_array($body['quota'])) {
            $tier = $body['quota']['quota_tier'] ?? 'unknown';
            $message .= ' Quota tier: ' . $tier . '.';
        }

        throw new HackClubCdnException(
            'Hack Club CDN failed to ' . $operation . ': ' . Str::limit($message, 300),
            $status,
            $body,
        );
    }

    private function extractUrl(array $data, string $operation): string
    {
        $url = $data['url'] ?? null;

        if (!is_string($url) || $url === '') {
            throw new HackClubCdnException('Hack Club CDN did not return a URL for operation: ' . $operation . '.');
        }

        return $url;
    }
}


