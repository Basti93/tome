<?php

namespace Tests\Unit\Services;

use App\Services\FirebaseMessagingService;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\CloudMessage;
use Mockery;
use Mockery\MockInterface;
use ReflectionClass;
use Tests\TestCase;

/**
 * The service is built around kreait's Messaging contract. The constructor needs real
 * credentials, so it is bypassed and the messaging client replaced by a mock: nothing is sent.
 */
class FirebaseMessagingServiceTest extends TestCase
{
    private MockInterface $messaging;

    private FirebaseMessagingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messaging = Mockery::mock(Messaging::class);
        $reflection = new ReflectionClass(FirebaseMessagingService::class);
        $this->service = $reflection->newInstanceWithoutConstructor();
        $property = $reflection->getProperty('messaging');
        $property->setAccessible(true);
        $property->setValue($this->service, $this->messaging);
    }

    private function messagingException(string $message): MessagingException
    {
        return new class($message) extends \RuntimeException implements MessagingException
        {
            public function errors(): array
            {
                return [];
            }
        };
    }

    public function test_constructor_fails_without_a_credentials_file(): void
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Firebase credentials file not found');

        new FirebaseMessagingService;
    }

    public function test_sends_one_data_only_message_per_token_including_title_and_body(): void
    {
        $sent = [];
        $this->messaging->shouldReceive('send')->twice()->andReturnUsing(function (CloudMessage $message) use (&$sent) {
            $sent[] = $message->jsonSerialize();

            return ['name' => 'projects/x/messages/1'];
        });

        $result = $this->service->sendToDevices(['tok-a', 'tok-b'], 'Titel', 'Text', ['click_action' => 'https://example.test/x']);

        $this->assertSame(['success' => 2, 'failure' => 0, 'tokensToDelete' => [], 'tokensToRetry' => []], $result);
        $this->assertSame('tok-a', $sent[0]['token']);
        $this->assertSame('tok-b', $sent[1]['token']);
        $this->assertSame(['title' => 'Titel', 'body' => 'Text', 'click_action' => 'https://example.test/x'], $sent[0]['data']);
        $this->assertArrayNotHasKey('notification', $sent[0], 'data-only so that onMessage fires');
    }

    public function test_a_single_token_string_is_accepted(): void
    {
        $this->messaging->shouldReceive('send')->once()->andReturn([]);

        $this->assertSame(1, $this->service->sendToDevices('only-one', 't', 'b')['success']);
    }

    public function test_invalid_tokens_are_reported_for_deletion_and_others_for_retry(): void
    {
        $this->messaging->shouldReceive('send')->times(5)->andReturnUsing(function (CloudMessage $m) {
            return match ($m->jsonSerialize()['token']) {
                'ok' => [],
                'bad-arg' => throw $this->messagingException('invalid-argument'),
                'gone' => throw $this->messagingException('Requested entity was not found: not-found'),
                'denied' => throw $this->messagingException('permission-denied'),
                default => throw $this->messagingException('internal error'),
            };
        });

        $result = $this->service->sendToDevices(['ok', 'bad-arg', 'gone', 'denied', 'flaky'], 't', 'b');

        $this->assertSame(1, $result['success']);
        $this->assertSame(4, $result['failure']);
        $this->assertSame(['bad-arg', 'gone', 'denied'], $result['tokensToDelete']);
        $this->assertSame(['flaky'], $result['tokensToRetry']);
    }

    public function test_unexpected_exceptions_count_as_failures_to_retry(): void
    {
        $this->messaging->shouldReceive('send')->once()->andThrow(new \RuntimeException('boom'));

        $result = $this->service->sendToDevices(['t1'], 't', 'b');

        $this->assertSame(['success' => 0, 'failure' => 1, 'tokensToDelete' => [], 'tokensToRetry' => ['t1']], $result);
    }

    public function test_topic_messages_use_a_notification_and_return_a_boolean(): void
    {
        $captured = null;
        $this->messaging->shouldReceive('send')->once()->andReturnUsing(function (CloudMessage $m) use (&$captured) {
            $captured = $m->jsonSerialize();

            return [];
        });

        $this->assertTrue($this->service->sendToTopic('news', 'Titel', 'Text', ['k' => 'v']));
        $this->assertSame('news', $captured['topic']);
        $this->assertSame(['title' => 'Titel', 'body' => 'Text'], $captured['notification']);
        $this->assertSame(['k' => 'v'], $captured['data']);

        $this->messaging->shouldReceive('send')->once()->andThrow(new \RuntimeException('x'));
        $this->assertFalse($this->service->sendToTopic('news', 't', 'b'));
    }
}
