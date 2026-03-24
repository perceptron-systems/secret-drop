<?php

namespace Tests\Unit;

use App\Services\CaptchaService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CaptchaServiceTest extends TestCase
{
    private CaptchaService $captcha;

    protected function setUp(): void
    {
        parent::setUp();
        $this->captcha = new CaptchaService();
    }

    public function test_generate_returns_token_and_challenge(): void
    {
        $result = $this->captcha->generate('test-identifier');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('challenge', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertMatchesRegularExpression('/^\d+ [+\-*] \d+$/', $result['challenge']);
    }

    public function test_generate_creates_cache_entry(): void
    {
        $result = $this->captcha->generate('test-identifier');

        $this->assertNotNull(Cache::get('captcha:'.$result['token']));
    }

    public function test_verify_returns_true_for_correct_answer(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);

        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertTrue($this->captcha->verify($result['token'], $expectedAnswer, $identifier));
    }

    public function test_verify_returns_false_for_incorrect_answer(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);

        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);
        $wrongAnswer = $expectedAnswer + 1;

        $this->assertFalse($this->captcha->verify($result['token'], $wrongAnswer, $identifier));
    }

    public function test_verify_returns_false_for_invalid_token(): void
    {
        $this->assertFalse($this->captcha->verify('invalid-token', 42, 'test-identifier'));
    }

    public function test_verify_returns_false_for_wrong_identifier(): void
    {
        $result = $this->captcha->generate('identifier-1');
        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertFalse($this->captcha->verify($result['token'], $expectedAnswer, 'identifier-2'));
    }

    public function test_verify_consumes_token(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);
        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertTrue($this->captcha->verify($result['token'], $expectedAnswer, $identifier));
        $this->assertFalse($this->captcha->verify($result['token'], $expectedAnswer, $identifier));
    }

    public function test_verify_accepts_string_answer(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);
        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertTrue($this->captcha->verify($result['token'], (string) $expectedAnswer, $identifier));
    }

    public function test_challenge_has_reasonable_numbers(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $result = $this->captcha->generate('test');
            $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

            $this->assertGreaterThanOrEqual(0, $expectedAnswer);
            $this->assertLessThanOrEqual(100, $expectedAnswer);
        }
    }

    public function test_get_expected_answer_returns_null_for_invalid_token(): void
    {
        $this->assertNull($this->captcha->getExpectedAnswer('nonexistent-token'));
    }
}
