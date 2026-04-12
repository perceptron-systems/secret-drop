<?php

namespace Tests\Unit;

use App\Services\ProofOfWorkService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ProofOfWorkServiceTest extends TestCase
{
    private ProofOfWorkService $pow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pow = new ProofOfWorkService();
        config(['pow.difficulty' => 4]); // Low difficulty for fast tests
    }

    /** Vérifie que generate retourne un token, un challenge et une difficulté. */
    public function test_generate_returns_token_challenge_and_difficulty(): void
    {
        $result = $this->pow->generate('test-identifier');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('challenge', $result);
        $this->assertArrayHasKey('difficulty', $result);
        $this->assertEquals(32, strlen($result['token']));
        $this->assertEquals(32, strlen($result['challenge']));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['token']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $result['challenge']);
        $this->assertEquals(4, $result['difficulty']);
    }

    /** Vérifie que generate crée une entrée en cache. */
    public function test_generate_creates_cache_entry(): void
    {
        $result = $this->pow->generate('test-identifier');

        $this->assertNotNull(Cache::get('pow:'.$result['token']));
    }

    /** Vérifie que verify retourne true pour un nonce valide. */
    public function test_verify_returns_true_for_valid_nonce(): void
    {
        $identifier = 'test-identifier';
        $result = $this->pow->generate($identifier);

        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);

        $this->assertTrue($this->pow->verify($result['token'], $nonce, $identifier));
    }

    /** Vérifie que verify retourne false pour un nonce invalide. */
    public function test_verify_returns_false_for_invalid_nonce(): void
    {
        $identifier = 'test-identifier';
        $result = $this->pow->generate($identifier);

        $this->assertFalse($this->pow->verify($result['token'], 'ffffffff', $identifier));
    }

    /** Vérifie que verify retourne false pour un token invalide. */
    public function test_verify_returns_false_for_invalid_token(): void
    {
        $this->assertFalse($this->pow->verify('invalid-token', '00000000', 'test-identifier'));
    }

    /** Vérifie que verify retourne false pour un mauvais identifiant. */
    public function test_verify_returns_false_for_wrong_identifier(): void
    {
        $result = $this->pow->generate('identifier-1');
        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);

        $this->assertFalse($this->pow->verify($result['token'], $nonce, 'identifier-2'));
    }

    /** Vérifie que le token est consommé après vérification réussie. */
    public function test_verify_consumes_token(): void
    {
        $identifier = 'test-identifier';
        $result = $this->pow->generate($identifier);
        $nonce = $this->pow->solve($result['challenge'], $result['difficulty']);

        $this->assertTrue($this->pow->verify($result['token'], $nonce, $identifier));
        $this->assertFalse($this->pow->verify($result['token'], $nonce, $identifier));
    }

    /** Vérifie que solve trouve un nonce valide. */
    public function test_solve_finds_valid_nonce(): void
    {
        $challenge = bin2hex(random_bytes(16));
        $difficulty = 4;

        $nonce = $this->pow->solve($challenge, $difficulty);

        $hash = hash('sha256', hex2bin($challenge).hex2bin($nonce), true);
        $this->assertEquals(0, ord($hash[0]) >> 4, 'First 4 bits should be zero');
    }

    /** Vérifie que verify rejette un nonce mal formaté. */
    public function test_verify_rejects_malformed_nonce(): void
    {
        $result = $this->pow->generate('test-identifier');

        // Non-hex nonce
        $this->assertFalse($this->pow->verify($result['token'], 'xyz!@#$%', 'test-identifier'));

        // Too long nonce
        $this->assertFalse($this->pow->verify($result['token'], str_repeat('a', 33), 'test-identifier'));

        // Empty nonce
        $this->assertFalse($this->pow->verify($result['token'], '', 'test-identifier'));
    }
}
