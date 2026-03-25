<?php

namespace Tests\Feature;

use App\Models\MagicLink;
use App\Services\TokenService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SuperAdminControllerTest extends TestCase
{
    private TokenService $tokenService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tokenService = app(TokenService::class);
        Mail::fake();
    }

    /** Vérifie que la page d'index affiche le formulaire de login superadmin. */
    public function testIndexPageDisplaysLoginForm(): void
    {
        $response = $this->get('/fr/superadmin');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.index');
    }

    /** Vérifie qu'un superadmin déjà authentifié est redirigé vers le dashboard. */
    public function testIndexRedirectsToDashboardWhenAuthenticated(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin');

        $response->assertRedirect(route('superadmin.dashboard', ['locale' => 'fr']));
    }

    /** Vérifie que la demande d'accès affiche la page de confirmation quel que soit l'email. */
    public function testRequestAccessShowsConfirmationPage(): void
    {
        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'random@example.com',
        ]);

        $response->assertRedirect(route('superadmin.accessSent'));
    }

    /** Vérifie qu'un email avec magic link est envoyé quand l'email correspond au super admin. */
    public function testRequestAccessSendsEmailWhenSuperAdmin(): void
    {
        Config::set('app.super_admin_email', 'superadmin@example.com');

        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'superadmin@example.com',
        ]);

        $response->assertRedirect(route('superadmin.accessSent'));
        Mail::assertSent(\App\Mail\SuperAdminMagicLinkMail::class);

        $this->assertDatabaseHas('magic_links', [
            'email_hash' => 'superadmin',
        ]);
    }

    /** Vérifie qu'aucun email n'est envoyé pour un email non super admin. */
    public function testRequestAccessDoesNotSendEmailWhenNotSuperAdmin(): void
    {
        Config::set('app.super_admin_email', 'superadmin@example.com');

        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'random@example.com',
        ]);

        $response->assertRedirect(route('superadmin.accessSent'));
        Mail::assertNothingSent();
    }

    /** Vérifie que GET verify affiche la page de confirmation sans consommer le token. */
    public function testVerifyGetShowsConfirmationPage(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'superadmin',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.verify-confirm');
        $response->assertViewHas('token', $tokenData['token']);
    }

    /** Vérifie que GET verify ne marque pas le token comme utilisé (protection scanner email). */
    public function testVerifyGetDoesNotConsumeToken(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'superadmin',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $magicLink = MagicLink::findByToken($tokenData['token']);
        $this->assertNull($magicLink->used_at);
    }

    /** Vérifie que POST verify consomme le token et redirige vers le dashboard. */
    public function testVerifyPostRedirectsToDashboard(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'superadmin',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->post("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertRedirect(route('superadmin.dashboard', ['locale' => 'fr']));
        $response->assertSessionHas('super_admin_verified', true);
        $response->assertSessionHas('super_admin_expires_at');
    }

    /** Vérifie le flux complet : POST verify puis accès au dashboard avec session persistante. */
    public function testVerifyPostThenDashboardFlowWorksEndToEnd(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'superadmin',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $this->post("/fr/superadmin/verify/{$tokenData['token']}");

        $dashboard = $this->get('/fr/superadmin/dashboard');
        $dashboard->assertStatus(200);
        $dashboard->assertViewIs('superadmin.dashboard');
    }

    /** Vérifie qu'un token invalide affiche la page d'erreur (GET). */
    public function testVerifyWithInvalidTokenShowsError(): void
    {
        $response = $this->get('/fr/superadmin/verify/invalid-token');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie qu'un token invalide affiche la page d'erreur (POST). */
    public function testVerifyPostWithInvalidTokenShowsError(): void
    {
        $response = $this->post('/fr/superadmin/verify/invalid-token');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie qu'un token expiré affiche la page d'erreur. */
    public function testVerifyWithExpiredTokenShowsError(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'superadmin',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->subMinutes(1),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie qu'un token admin normal ne fonctionne pas sur la route superadmin. */
    public function testVerifyWithNonSuperadminTokenShowsError(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'regular-user-hash',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

    /** Vérifie que le dashboard redirige vers le login sans authentification. */
    public function testDashboardRequiresAuthentication(): void
    {
        $response = $this->get('/fr/superadmin/dashboard');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
    }

    /** Vérifie que le dashboard affiche les statistiques quand authentifié. */
    public function testDashboardDisplaysStatsWhenAuthenticated(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
        $response->assertViewHas('stats');
    }

    /** Vérifie que le logout détruit la session et redirige vers le login. */
    public function testLogoutClearsSession(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->post('/fr/superadmin/logout');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
        $response->assertSessionMissing('super_admin_verified');
    }

    /** Vérifie que la session expirée redirige vers le login. */
    public function testSessionExpiresAfterTimeout(): void
    {
        $response = $this->withSession([
            'super_admin_verified' => true,
            'super_admin_expires_at' => now()->subHours(3)->timestamp,
        ])->get('/fr/superadmin/dashboard');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
    }

    /** Vérifie qu'une période invalide est remplacée par la valeur par défaut (30d). */
    public function testDashboardWithInvalidPeriodFallsBackToDefault(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard?period=2d');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
        $response->assertViewHas('period', '30d');
    }

    /** Vérifie qu'une période valide est bien utilisée pour le dashboard. */
    public function testDashboardWithValidPeriodUsesRequestedPeriod(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard?period=7d');

        $response->assertStatus(200);
        $response->assertViewHas('period', '7d');
    }
}
