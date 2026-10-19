<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseMessagingService
{
    private $messaging;

    public function __construct()
    {
        try {
            $credentialsPath = env('FIREBASE_CREDENTIALS_PATH');
            if (! $credentialsPath || ! file_exists($credentialsPath)) {
                throw new \Exception('Firebase credentials file not found at: '.$credentialsPath);
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Send a message to one or multiple devices
     *
     * @param  array|string  $tokens  Single token or array of tokens
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array  $data  Additional data to send
     * @return array Result with success/failure counts
     */
    public function sendToDevices($tokens, string $title, string $body, array $data = []): array
    {
        $tokens = is_array($tokens) ? $tokens : [$tokens];

        // Always include title and body in data payload so onMessage is triggered
        $dataPayload = array_merge([
            'title' => $title,
            'body' => $body,
        ], $data);

        // Build message with ONLY data (no notification) to ensure onMessage fires
        // Firebase will show notification from service worker if app is in background
        $message = CloudMessage::new()
            ->withData($dataPayload);

        $successCount = 0;
        $failureCount = 0;
        $tokensToDelete = [];
        $tokensToRetry = [];

        foreach ($tokens as $token) {
            try {
                Log::info("Attempting to send message to token: {$token}");
                Log::info('Message data: '.json_encode($dataPayload));

                $targetedMessage = $message->withToken($token);
                $result = $this->messaging->send($targetedMessage);

                $successCount++;
                Log::info("✅ FCM message sent successfully to token: {$token}", ['result' => $result]);
            } catch (MessagingException $e) {
                $failureCount++;
                $errorMessage = $e->getMessage();
                $errorCode = $e->getCode();

                Log::error("❌ MessagingException for token {$token}: Code={$errorCode}, Message={$errorMessage}");

                // Token is invalid or registration expired
                if (strpos($errorMessage, 'invalid-argument') !== false ||
                    strpos($errorMessage, 'not-found') !== false ||
                    strpos($errorMessage, 'permission-denied') !== false) {
                    $tokensToDelete[] = $token;
                } else {
                    $tokensToRetry[] = $token;
                }
            } catch (\Exception $e) {
                $failureCount++;
                $tokensToRetry[] = $token;
                Log::error("❌ Unexpected exception for token {$token}: ".$e->getMessage(), [
                    'exception' => get_class($e),
                    'code' => $e->getCode(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ]);
            }
        }

        return [
            'success' => $successCount,
            'failure' => $failureCount,
            'tokensToDelete' => $tokensToDelete,
            'tokensToRetry' => $tokensToRetry,
        ];
    }

    /**
     * Send a message to a topic
     *
     * @param  string  $topic  Topic name
     * @param  string  $title  Notification title
     * @param  string  $body  Notification body
     * @param  array  $data  Additional data to send
     * @return bool Success status
     */
    public function sendToTopic(string $topic, string $title, string $body, array $data = []): bool
    {
        try {
            $notification = Notification::create($title, $body);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withTopic($topic);

            if (! empty($data)) {
                $message = $message->withData($data);
            }

            $this->messaging->send($message);
            Log::info("FCM message sent successfully to topic: {$topic}");

            return true;
        } catch (\Exception $e) {
            Log::error("FCM topic send failed for topic {$topic}: ".$e->getMessage());

            return false;
        }
    }
}
