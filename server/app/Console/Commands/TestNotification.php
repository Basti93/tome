<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\Services\FirebaseMessagingService;
use Illuminate\Console\Command;

class TestNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:test {token} {title} {text}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test push notification to the given token';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $token = $this->argument('token');
        $title = $this->argument('title');
        $text = $this->argument('text');

        $firebaseService = new FirebaseMessagingService;

        // Include data payload so onMessage handler is triggered
        $dataPayload = [
            'title' => $title,
            'body' => $text,
            'click_action' => config('app.vue_url'),
        ];

        $result = $firebaseService->sendToDevices($token, $title, $text, $dataPayload);

        $this->info('Message successfully sent to '.$result['success'].' recipients');
        $this->info('Failed to send message to '.$result['failure'].' recipients');

        // Clean up invalid tokens
        foreach ($result['tokensToDelete'] as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }
    }
}
