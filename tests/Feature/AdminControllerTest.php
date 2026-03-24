<?php

namespace Tests\Feature;

use App\Mail\MagicLinkMail;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\TokenService;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    public function testAdminIndexPageLoads(): void
    {
        $response = $this->get('/fr/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    public function testRequestAccessShowsConfirmationEvenForNonExistentEmail(): void
    {
        Mail::fake();

        $response = $this->post('/fr/admin/request-access', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.access-sent');
        Mail::assertNothingSent();
    }

    public function testRequestAccessSendsMagicLinkForExistingSecrets(): void
    {
        Mail::fake();

        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->post('/fr/admin/request-access', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('admin.access-sent');

        Mail::assertSent(MagicLinkMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });

        $this->assertEquals(1, MagicLink::count());

        $secret->delete();
    }

    public function testMagicLinkExpireIn5Minutes(): void
    {
        Mail::fake();

        $secret = $this->createSecretWithEmail('test@example.com');

        $this->post('/fr/admin/request-access', [
            'email' => 'test@example.com',
        ]);

        $magicLink = MagicLink::first();
        $this->assertNotNull($magicLink);
        $this->assertTrue($magicLink->expire_at->diffInMinutes(now()) <= 5);

        $secret->delete();
    }

    public function testVerifyValidMagicLinkRedirectsToDashboard(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->get("/fr/admin/verify/{$tokenData['token']}");

        $response->assertRedirect(route('admin.dashboard', ['locale' => 'fr']));
        $this->assertTrue(session()->has('admin_email_hash'));

        $secret->delete();
    }

    public function testVerifyExpiredMagicLinkShowsInvalidPage(): void
    {
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->subMinutes(1),
        ]);

        $response = $this->get("/fr/admin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.invalid-link');
    }

    public function testVerifyUsedMagicLinkShowsInvalidPage(): void
    {
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
            'used_at' => now(),
        ]);

        $response = $this->get("/fr/admin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('admin.invalid-link');
    }

    public function testDashboardRequiresAuthentication(): void
    {
        $response = $this->get('/fr/admin/dashboard');

        $response->assertRedirect(route('admin.index', ['locale' => 'fr']));
    }

    public function testDashboardShowsSecretsForAuthenticatedUser(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->get('/fr/admin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('admin.dashboard');
        $response->assertViewHas('secrets');

        $secret->delete();
    }

    public function testLogoutClearsSession(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->post('/fr/admin/logout');

        $response->assertRedirect(route('admin.index', ['locale' => 'fr']));
        $this->assertFalse(session()->has('admin_email_hash'));
    }

    public function testRevokeRequiresAuthentication(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertStatus(403);

        $secret->delete();
    }

    public function testRevokeSecretWorks(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $secret->refresh();
        $this->assertTrue($secret->isRevoked());

        $secret->delete();
    }

    public function testExtendRequiresAuthentication(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->postJson("/fr/admin/secrets/{$secret->id}/extend", [
            'days' => 7,
        ]);

        $response->assertStatus(403);

        $secret->delete();
    }

    public function testExtendSecretWorks(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');
        $secret = $this->createSecretWithEmail('test@example.com');
        $originalExpireAt = $secret->expire_at;

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->postJson("/fr/admin/secrets/{$secret->id}/extend", [
                'days' => 7,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $secret->refresh();
        $this->assertTrue($secret->expire_at->gt($originalExpireAt));

        $secret->delete();
    }

    public function testCannotAccessOtherUsersSecrets(): void
    {
        $emailHash = MagicLink::hashEmail('attacker@example.com');
        $secret = $this->createSecretWithEmail('victim@example.com');

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertStatus(404);

        $secret->refresh();
        $this->assertFalse($secret->isRevoked());

        $secret->delete();
    }

    private function createSecretWithEmail(string $email): Secret
    {
        return Secret::create([
            'token' => bin2hex(random_bytes(16)),
            'admin_token_hash' => hash('sha256', bin2hex(random_bytes(16))),
            'type' => 'text',
            'cipher_meta' => ['alg' => 'AES-256-GCM', 'iv' => 'test', 'version' => 1],
            'ciphertext' => 'encrypted',
            'expire_at' => now()->addDays(7),
            'creator_email_hash' => MagicLink::hashEmail($email),
        ]);
    }
}
