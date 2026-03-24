<?php

namespace Tests\Unit;

use App\Logging\SanitizeProcessor;
use Monolog\LogRecord;
use Tests\TestCase;

class SanitizeProcessorTest extends TestCase
{
    private SanitizeProcessor $processor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->processor = new SanitizeProcessor();
    }

    private function processMessage(string $message): string
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: \Monolog\Level::Info,
            message: $message,
        );

        return ($this->processor)($record)->message;
    }

    private function processContext(array $context): array
    {
        $record = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: \Monolog\Level::Info,
            message: 'test',
            context: $context,
        );

        return ($this->processor)($record)->context;
    }

    public function testRedactsPasswordsInContext(): void
    {
        $context = $this->processContext(['password' => 'secret123']);

        $this->assertEquals('[REDACTED]', $context['password']);
    }

    public function testRedactsTokensInContext(): void
    {
        $context = $this->processContext(['token' => 'abc123']);

        $this->assertEquals('[REDACTED]', $context['token']);
    }

    public function testRedactsApiKeysInContext(): void
    {
        $context = $this->processContext(['api_key' => 'sk-12345']);

        $this->assertEquals('[REDACTED]', $context['api_key']);
    }

    public function testPreservesNonSensitiveContext(): void
    {
        $context = $this->processContext(['user_id' => 42, 'action' => 'login']);

        $this->assertEquals(42, $context['user_id']);
        $this->assertEquals('login', $context['action']);
    }

    public function testRedactsLongBase64Strings(): void
    {
        $longString = str_repeat('A', 200);
        $result = $this->processMessage("Data: {$longString}");

        $this->assertStringContainsString('[REDACTED_DATA]', $result);
        $this->assertStringNotContainsString($longString, $result);
    }

    public function testPreservesShortStrings(): void
    {
        $shortString = str_repeat('A', 50);
        $result = $this->processMessage("Data: {$shortString}");

        $this->assertStringContainsString($shortString, $result);
    }

    public function testRedactsAdminVerifyTokenInUrl(): void
    {
        $result = $this->processMessage('GET /admin/verify/abcdefghijklmnopqrstuvwxyz');

        $this->assertStringContainsString('/admin/verify/[TOKEN]', $result);
    }

    public function testRedactsSuperadminVerifyTokenInUrl(): void
    {
        $result = $this->processMessage('GET /superadmin/verify/abcdefghijklmnopqrstuvwxyz');

        $this->assertStringContainsString('/superadmin/verify/[TOKEN]', $result);
    }

    public function testRedactsUrlFragments(): void
    {
        $result = $this->processMessage('http://example.com/s/token#secretkey123');

        $this->assertStringNotContainsString('secretkey123', $result);
    }
}
