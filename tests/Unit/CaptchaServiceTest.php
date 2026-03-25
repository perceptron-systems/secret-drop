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

    /** Vérifie que generate retourne un token et un challenge. */
    public function test_generate_returns_token_and_challenge(): void
    {
        $result = $this->captcha->generate('test-identifier');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('challenge', $result);
        $this->assertNotEmpty($result['token']);
        $this->assertMatchesRegularExpression('/^\d+ [+\-*] \d+$/', $result['challenge']);
    }

    /** Vérifie que generate crée une entrée en cache. */
    public function test_generate_creates_cache_entry(): void
    {
        $result = $this->captcha->generate('test-identifier');

        $this->assertNotNull(Cache::get('captcha:'.$result['token']));
    }

    /** Vérifie que verify retourne true pour une bonne réponse. */
    public function test_verify_returns_true_for_correct_answer(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);

        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertTrue($this->captcha->verify($result['token'], $expectedAnswer, $identifier));
    }

    /** Vérifie que verify retourne false pour une mauvaise réponse. */
    public function test_verify_returns_false_for_incorrect_answer(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);

        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);
        $wrongAnswer = $expectedAnswer + 1;

        $this->assertFalse($this->captcha->verify($result['token'], $wrongAnswer, $identifier));
    }

    /** Vérifie que verify retourne false pour un token invalide. */
    public function test_verify_returns_false_for_invalid_token(): void
    {
        $this->assertFalse($this->captcha->verify('invalid-token', 42, 'test-identifier'));
    }

    /** Vérifie que verify retourne false pour un mauvais identifiant. */
    public function test_verify_returns_false_for_wrong_identifier(): void
    {
        $result = $this->captcha->generate('identifier-1');
        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertFalse($this->captcha->verify($result['token'], $expectedAnswer, 'identifier-2'));
    }

    /** Vérifie que le token est consommé après vérification. */
    public function test_verify_consumes_token(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);
        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertTrue($this->captcha->verify($result['token'], $expectedAnswer, $identifier));
        $this->assertFalse($this->captcha->verify($result['token'], $expectedAnswer, $identifier));
    }

    /** Vérifie que verify accepte une réponse en string. */
    public function test_verify_accepts_string_answer(): void
    {
        $identifier = 'test-identifier';
        $result = $this->captcha->generate($identifier);
        $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

        $this->assertTrue($this->captcha->verify($result['token'], (string) $expectedAnswer, $identifier));
    }

    /** Vérifie que les nombres du challenge sont raisonnables (0-100). */
    public function test_challenge_has_reasonable_numbers(): void
    {
        for ($i = 0; $i < 50; $i++) {
            $result = $this->captcha->generate('test');
            $expectedAnswer = $this->captcha->getExpectedAnswer($result['token']);

            $this->assertGreaterThanOrEqual(0, $expectedAnswer);
            $this->assertLessThanOrEqual(100, $expectedAnswer);
        }
    }

    /** Vérifie que getExpectedAnswer retourne null pour un token invalide. */
    public function test_get_expected_answer_returns_null_for_invalid_token(): void
    {
        $this->assertNull($this->captcha->getExpectedAnswer('nonexistent-token'));
    }
}
