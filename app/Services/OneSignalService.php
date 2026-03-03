<?php

namespace App\Services;

use Berkayk\OneSignal\OneSignalFacade as OneSignal;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Log;

class OneSignalService
{
    public function sendToUser(string $message, string $playerId, ?string $heading = null, ?string $url = null, ?array $data = null): array
    {
        return $this->sendToUsers($message, [$playerId], $heading, $url, $data);
    }

    public function sendToUsers(string $message, array $playerIds, ?string $heading = null, ?string $url = null, ?array $data = null): array
    {
        try {
            $params = [
                'contents' => ['en' => $message],
                'include_player_ids' => $playerIds,
            ];

            if ($heading) {
                $params['headings'] = ['en' => $heading];
            }

            if ($url) {
                $params['url'] = $url;
            }

            if ($data) {
                $params['data'] = $data;
            }

            $response = OneSignal::sendNotificationCustom($params);
            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['id'])) {
                return [
                    'success' => true,
                    'notification_id' => $body['id'],
                    'recipients' => $body['recipients'] ?? 0,
                ];
            }

            Log::info('OneSignal API Response (sendToUsers)', [
                'player_ids' => $playerIds,
                'response' => $body,
            ]);

            return [
                'success' => false,
                'error' => 'Unknown response format',
                'response' => $body,
            ];

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            Log::error('OneSignal API Error', [
                'player_ids' => $playerIds,
                'error' => $body,
            ]);

            return [
                'success' => false,
                'error' => $body['errors'] ?? 'Unknown error',
                'status_code' => $response->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('OneSignal Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile() . ':' . $e->getLine(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to all users
     *
     * @param string $message
     * @param string|null $heading
     * @param string|null $url
     * @param array|null $data
     * @return array
     */
    public function sendToAll(string $message, ?string $heading = null, ?string $url = null, ?array $data = null): array
    {
        try {
            $params = [
                'contents' => ['en' => $message],
                'included_segments' => ['All'],
            ];

            if ($heading) {
                $params['headings'] = ['en' => $heading];
            }

            if ($url) {
                $params['url'] = $url;
            }

            if ($data) {
                $params['data'] = $data;
            }

            $response = OneSignal::sendNotificationCustom($params);
            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['id'])) {
                return [
                    'success' => true,
                    'notification_id' => $body['id'],
                    'recipients' => $body['recipients'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'Unknown response format',
                'response' => $body,
            ];

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            Log::error('OneSignal API Error (sendToAll)', ['error' => $body]);

            return [
                'success' => false,
                'error' => $body['errors'] ?? 'Unknown error',
                'status_code' => $response->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('OneSignal Exception (sendToAll)', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send notification to users with specific tags
     *
     * @param string $message
     * @param array $tags Example: [['field' => 'tag', 'key' => 'premium', 'relation' => '=', 'value' => '1']]
     * @param string|null $heading
     * @param string|null $url
     * @param array|null $data
     * @return array
     */
    public function sendByTags(string $message, array $tags, ?string $heading = null, ?string $url = null, ?array $data = null): array
    {
        try {
            $params = [
                'contents' => ['en' => $message],
                'filters' => $tags,
            ];

            if ($heading) {
                $params['headings'] = ['en' => $heading];
            }

            if ($url) {
                $params['url'] = $url;
            }

            if ($data) {
                $params['data'] = $data;
            }

            $response = OneSignal::sendNotificationCustom($params);
            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['id'])) {
                return [
                    'success' => true,
                    'notification_id' => $body['id'],
                    'recipients' => $body['recipients'] ?? 0,
                ];
            }

            return [
                'success' => false,
                'error' => 'Unknown response format',
                'response' => $body,
            ];

        } catch (ClientException $e) {
            $response = $e->getResponse();
            $body = json_decode($response->getBody()->getContents(), true);

            Log::error('OneSignal API Error (sendByTags)', [
                'tags' => $tags,
                'error' => $body,
            ]);

            return [
                'success' => false,
                'error' => $body['errors'] ?? 'Unknown error',
                'status_code' => $response->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('OneSignal Exception (sendByTags)', [
                'message' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

