<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;


class PushChannel
{

    private const FCM_LEGACY_ENDPOINT = 'https://fcm.googleapis.com/fcm/send';


    private const FCM_V1_ENDPOINT = 'https://fcm.googleapis.com/v1/projects/{project_id}/messages:send';


    public function send(object $notifiable, Notification $notification): void
    {
        $message = $notification->toPush($notifiable);

        if (!$notifiable->push_token) {
            Log::warning('Push notification not sent: User has no push_token', [
                'user_id' => $notifiable->id,
                'notification' => class_basename($notification),
            ]);
            return;
        }

        try {
            if ($this->shouldUseV1API()) {
                $this->sendViaFCMv1($notifiable, $message);
            } else {
                $this->sendViaFCMLegacy($notifiable, $message);
            }

            Log::info('Push notification sent successfully', [
                'user_id' => $notifiable->id,
                'push_token' => substr($notifiable->push_token, 0, 20) . '...',
                'title' => $message['title'],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send push notification', [
                'user_id' => $notifiable->id,
                'error' => $e->getMessage(),
                'notification' => class_basename($notification),
            ]);
        }
    }

    private function sendViaFCMv1(object $notifiable, array $message): void
    {
        $accessToken = $this->getAccessToken();

        if (!$accessToken) {
            throw new \Exception('Unable to obtain FCM access token for v1 API');
        }

        $projectId = config('services.fcm.project_id');
        $endpoint = str_replace('{project_id}', $projectId, self::FCM_V1_ENDPOINT);

        $payload = [
            'message' => [
                'token' => $notifiable->push_token,
                'notification' => [
                    'title' => $message['title'],
                    'body' => $message['body'],
                ],
                'data' => $message['data'] ?? [],
                'android' => [
                    'notification' => [
                        'icon' => 'ic_launcher',
                        'color' => '#2563eb',
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                ],
                'webpush' => [
                    'notification' => [
                        'icon' => $message['icon'] ?? null,
                        'badge' => $message['badge'] ?? null,
                    ],
                ],
            ],
        ];

        $response = Http::withToken($accessToken)
            ->post($endpoint, $payload);

        if (!$response->successful()) {
            throw new \Exception('FCM v1 API error: ' . $response->body());
        }
    }


    private function sendViaFCMLegacy(object $notifiable, array $message): void
    {
        $serverKey = config('services.fcm.server_key');

        if (!$serverKey) {
            throw new \Exception('FCM Server Key is not configured');
        }

        $payload = [
            'to' => $notifiable->push_token,
            'notification' => [
                'title' => $message['title'],
                'body' => $message['body'],
                'icon' => $message['icon'] ?? 'ic_launcher',
                'badge' => $message['badge'] ?? '1',
                'sound' => 'default',
                'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
            ],
            'data' => $message['data'] ?? [],
            'priority' => 'high',
            'time_to_live' => 86400,
        ];

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type' => 'application/json',
        ])->post(self::FCM_LEGACY_ENDPOINT, $payload);

        if (!$response->successful()) {
            $error = $response->json('error') ?? $response->body();
            throw new \Exception('FCM Legacy API error: ' . json_encode($error));
        }
    }


    private function shouldUseV1API(): bool
    {
        return config('services.fcm.use_v1_api', false) === true &&
            config('services.fcm.project_id') !== null;
    }


    private function getAccessToken(): ?string
    {
        $serviceAccountPath = config('services.fcm.service_account_json');

        if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
            return null;
        }

        $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

        if (!$serviceAccount) {
            Log::error('Invalid FCM service account JSON');
            return null;
        }

        try {
            $now = time();
            $claim = [
                'iss' => $serviceAccount['client_email'],
                'sub' => $serviceAccount['client_email'],
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            ];

            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];

            $token = $this->encodeJWT($header, $claim, $serviceAccount['private_key']);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $token,
            ]);

            return $response->json('access_token');
        } catch (\Exception $e) {
            Log::error('Failed to get FCM access token: ' . $e->getMessage());
            return null;
        }
    }

    private function encodeJWT(array $header, array $payload, string $privateKey): string
    {
        $header = base64_encode(json_encode($header));
        $payload = base64_encode(json_encode($payload));

        $signature = '';
        openssl_sign(
            $header . '.' . $payload,
            $signature,
            $privateKey,
            'sha256WithRSAEncryption'
        );

        return $header . '.' . $payload . '.' . base64_encode($signature);
    }
}
