<?php

namespace App\Services;

use App\NotificationToken;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Exception\Messaging\NotFound;
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
            $credentialsPath = config('services.firebase.credentials');
            if ($credentialsPath && ! str_starts_with($credentialsPath, '/')) {
                $credentialsPath = base_path($credentialsPath);
            }
            if (! $credentialsPath || ! file_exists($credentialsPath)) {
                throw new \Exception('Firebase credentials file not found at: '.$credentialsPath);
            }

            $factory = (new Factory)->withServiceAccount($credentialsPath);
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Firebase initialization failed: '.$e::class);
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
                $this->messaging->send($message->withToken($token));

                $successCount++;
            } catch (MessagingException $e) {
                $failureCount++;
                $errorMessage = $e->getMessage();

                // Never log the message itself: kreait puts the device token into it.
                Log::warning('FCM send failed: '.$e::class.' (code '.$e->getCode().')');

                // Token is invalid, unknown or unregistered (app uninstalled / token rotated)
                if ($e instanceof NotFound ||
                    strpos($errorMessage, 'invalid-argument') !== false ||
                    strpos($errorMessage, 'not-found') !== false ||
                    stripos($errorMessage, 'unregistered') !== false ||
                    strpos($errorMessage, 'permission-denied') !== false) {
                    $tokensToDelete[] = $token;
                } else {
                    $tokensToRetry[] = $token;
                }
            } catch (\Exception $e) {
                $failureCount++;
                $tokensToRetry[] = $token;
                Log::error('FCM send raised an unexpected exception: '.$e::class.' (code '.$e->getCode().')');
            }
        }

        // Drop tokens FCM reported as invalid/unregistered, they will never work again
        if ($tokensToDelete !== []) {
            NotificationToken::whereIn('token', $tokensToDelete)->delete();
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

            return true;
        } catch (\Exception $e) {
            Log::error('FCM topic send failed: '.$e::class);

            return false;
        }
    }
}
