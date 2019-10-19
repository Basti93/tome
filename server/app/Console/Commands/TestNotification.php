<?php

namespace App\Console\Commands;

use App\NotificationToken;
use App\User;
use Illuminate\Console\Command;
use App\UserTrainingNotification;
use LaravelFCM\Facades\FCM;
use LaravelFCM\Message\PayloadDataBuilder;

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

        $dataBuilder = new PayloadDataBuilder();
        $dataBuilder->addData([
            'title' => $title,
            'body' => $text,
        ]);
        $data = $dataBuilder->build();
        $downstreamResponse = FCM::sendTo($token, null, null, $data);

        $this->info('Message successfully send to ' . $downstreamResponse->numberSuccess() . ' recipients');
        $this->info('Failed to send message to ' . $downstreamResponse->numberFailure() . ' recipients');
        //clean up the unused tokens
        foreach ($downstreamResponse->tokensToDelete() as $tokenToDelete) {
            NotificationToken::whereToken($tokenToDelete)->delete();
        }

    }
}
