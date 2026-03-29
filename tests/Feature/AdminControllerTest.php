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
    /** Vérifie que la page d'index admin se charge correctement. */
    public function testAdminIndexPageLoads(): void
    {
        $response = $this->get('/fr/admin');

        $response->assertStatus(200);
        $response->assertViewIs('admin.index');
    }

    /** Vérifie que la page de confirmation s'affiche même pour un email sans secrets. */
    public function testRequestAccessShowsConfirmationEvenForNonExistentEmail(): void
    {
        Mail::fake();

        $response = $this->post('/fr/admin/request-access', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertRedirect(route('admin.accessSent'));
        Mail::assertNothingSent();
    }

    /** Vérifie qu'un magic link est envoyé quand l'email a des secrets associés. */
    public function testRequestAccessSendsMagicLinkForExistingSecrets(): void
    {
        Mail::fake();

        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->post('/fr/admin/request-access', [
            'email' => 'test@example.com',
        ]);

        $response->assertRedirect(route('admin.accessSent'));

        Mail::assertSent(MagicLinkMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });

        $this->assertEquals(1, MagicLink::count());

        $secret->delete();
    }

    /** Vérifie que le magic link expire dans un délai de 5 minutes. */
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

    /** Vérifie que GET verify affiche la page de confirmation sans consommer le token. */
    public function testVerifyGetShowsConfirmationPage(): void
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

        $response->assertStatus(200);
        $response->assertViewIs('admin.verify-confirm');
        $response->assertViewHas('token', $tokenData['token']);

        $secret->delete();
    }

    /** Vérifie que GET verify ne marque pas le token comme utilisé (protection scanner email). */
    public function testVerifyGetDoesNotConsumeToken(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->get("/fr/admin/verify/{$tokenData['token']}");

        $magicLink = MagicLink::findByToken($tokenData['token']);
        $this->assertNull($magicLink->used_at);

        $secret->delete();
    }

    /** Vérifie que POST verify consomme le token et redirige vers le dashboard. */
    public function testVerifyPostRedirectsToDashboard(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->post("/fr/admin/verify/{$tokenData['token']}");

        $response->assertRedirect(route('admin.dashboard', ['locale' => 'fr']));
        $this->assertTrue(session()->has('admin_email_hash'));

        $secret->delete();
    }

    /** Vérifie le flux complet : POST verify puis accès au dashboard avec session persistante. */
    public function testVerifyPostThenDashboardFlowWorksEndToEnd(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');
        $tokenService = app(TokenService::class);
        $tokenData = $tokenService->generateMagicLinkToken();

        MagicLink::create([
            'email_hash' => MagicLink::hashEmail('test@example.com'),
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->post("/fr/admin/verify/{$tokenData['token']}");

        $dashboard = $this->get('/fr/admin/dashboard');
        $dashboard->assertStatus(200);
        $dashboard->assertViewIs('admin.dashboard');

        $secret->delete();
    }

    /** Vérifie qu'un token invalide affiche la page d'erreur (POST). */
    public function testVerifyPostWithInvalidTokenShowsError(): void
    {
        $response = $this->post('/fr/admin/verify/invalid-token');

        $response->assertStatus(200);
        $response->assertViewIs('admin.invalid-link');
    }

    /** Vérifie qu'un magic link expiré affiche la page d'erreur. */
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

    /** Vérifie qu'un magic link déjà utilisé affiche la page d'erreur. */
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

    /** Vérifie que le dashboard redirige vers le login sans authentification. */
    public function testDashboardRequiresAuthentication(): void
    {
        $response = $this->get('/fr/admin/dashboard');

        $response->assertRedirect(route('admin.index', ['locale' => 'fr']));
    }

    /** Vérifie que le dashboard affiche les secrets de l'utilisateur authentifié. */
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

    /** Vérifie que le logout détruit la session et redirige vers le login. */
    public function testLogoutClearsSession(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->post('/fr/admin/logout');

        $response->assertRedirect(route('admin.index', ['locale' => 'fr']));
        $this->assertFalse(session()->has('admin_email_hash'));
    }

    /** Vérifie que la révocation nécessite une authentification. */
    public function testRevokeRequiresAuthentication(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->postJson("/fr/admin/secrets/{$secret->id}/revoke");

        $response->assertStatus(401);

        $secret->delete();
    }

    /** Vérifie que la révocation d'un secret fonctionne correctement. */
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

    /** Vérifie que la prolongation nécessite une authentification. */
    public function testExtendRequiresAuthentication(): void
    {
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->postJson("/fr/admin/secrets/{$secret->id}/extend", [
            'hours' => 168,
        ]);

        $response->assertStatus(401);

        $secret->delete();
    }

    /** Vérifie que la prolongation d'un secret fonctionne correctement. */
    public function testExtendSecretWorks(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');
        $secret = $this->createSecretWithEmail('test@example.com');
        $originalExpireAt = $secret->expire_at;

        $response = $this->withSession(['admin_email_hash' => $emailHash])
            ->postJson("/fr/admin/secrets/{$secret->id}/extend", [
                'hours' => 168,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        $secret->refresh();
        $this->assertTrue($secret->expire_at->gt($originalExpireAt));

        $secret->delete();
    }

    /** Vérifie qu'un utilisateur ne peut pas révoquer les secrets d'un autre utilisateur. */
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

    /** Vérifie que le poll nécessite une authentification. */
    public function testPollRequiresAuthentication(): void
    {
        $response = $this->getJson('/fr/admin/dashboard/poll');

        $response->assertStatus(401);
    }

    /** Vérifie que le poll retourne les secrets en JSON pour un utilisateur authentifié. */
    public function testPollReturnsSecretsForAuthenticatedUser(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');
        $secret = $this->createSecretWithEmail('test@example.com');

        $response = $this->withSession([
            'admin_email_hash' => $emailHash,
            'admin_expires_at' => now()->addMinutes(30)->timestamp,
        ])->getJson('/fr/admin/dashboard/poll');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'secrets' => [
                ['id', 'read_count', 'max_views', 'first_read_at', 'expire_at', 'is_revoked', 'is_expired', 'has_reached_max_views', 'is_accessible'],
            ],
        ]);

        $data = $response->json('secrets.0');
        $this->assertEquals($secret->id, $data['id']);
        $this->assertEquals(0, $data['read_count']);
        $this->assertFalse($data['is_revoked']);
        $this->assertTrue($data['is_accessible']);

        $secret->delete();
    }

    /** Vérifie que le poll ne retourne pas les secrets d'un autre utilisateur. */
    public function testPollDoesNotReturnOtherUsersSecrets(): void
    {
        $emailHash = MagicLink::hashEmail('alice@example.com');
        $secret = $this->createSecretWithEmail('bob@example.com');

        $response = $this->withSession([
            'admin_email_hash' => $emailHash,
            'admin_expires_at' => now()->addMinutes(30)->timestamp,
        ])->getJson('/fr/admin/dashboard/poll');

        $response->assertStatus(200);
        $response->assertJsonCount(0, 'secrets');

        $secret->delete();
    }

    /** Vérifie que le poll reflète les changements de statut d'un secret lu. */
    public function testPollReflectsReadCountChanges(): void
    {
        $emailHash = MagicLink::hashEmail('test@example.com');
        $secret = $this->createSecretWithEmail('test@example.com');
        $secret->update(['max_views' => 1]);

        $secret->incrementReadCount();

        $response = $this->withSession([
            'admin_email_hash' => $emailHash,
            'admin_expires_at' => now()->addMinutes(30)->timestamp,
        ])->getJson('/fr/admin/dashboard/poll');

        $data = $response->json('secrets.0');
        $this->assertEquals(1, $data['read_count']);
        $this->assertTrue($data['has_reached_max_views']);
        $this->assertFalse($data['is_accessible']);
        $this->assertNotNull($data['first_read_at']);

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
