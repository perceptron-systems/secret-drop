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

    public function testIndexPageDisplaysLoginForm(): void
    {
        $response = $this->get('/fr/superadmin');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.index');
    }

    public function testIndexRedirectsToDashboardWhenAuthenticated(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin');

        $response->assertRedirect(route('superadmin.dashboard', ['locale' => 'fr']));
    }

    public function testRequestAccessShowsConfirmationPage(): void
    {
        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'random@example.com',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.access-sent');
    }

    public function testRequestAccessSendsEmailWhenSuperAdmin(): void
    {
        Config::set('app.super_admin_email', 'superadmin@example.com');

        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'superadmin@example.com',
        ]);

        $response->assertStatus(200);
        Mail::assertSent(\App\Mail\SuperAdminMagicLinkMail::class);

        $this->assertDatabaseHas('magic_links', [
            'email_hash' => 'superadmin',
        ]);
    }

    public function testRequestAccessDoesNotSendEmailWhenNotSuperAdmin(): void
    {
        Config::set('app.super_admin_email', 'superadmin@example.com');

        $response = $this->post('/fr/superadmin/request-access', [
            'email' => 'random@example.com',
        ]);

        $response->assertStatus(200);
        Mail::assertNothingSent();
    }

    public function testVerifyWithValidTokenRedirectsToDashboard(): void
    {
        $tokenData = $this->tokenService->generateMagicLinkToken();
        MagicLink::create([
            'email_hash' => 'superadmin',
            'token_hash' => $tokenData['hash'],
            'expire_at' => now()->addMinutes(5),
        ]);

        $response = $this->get("/fr/superadmin/verify/{$tokenData['token']}");

        $response->assertRedirect(route('superadmin.dashboard', ['locale' => 'fr']));
    }

    public function testVerifyWithInvalidTokenShowsError(): void
    {
        $response = $this->get('/fr/superadmin/verify/invalid-token');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.invalid-link');
    }

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

    public function testDashboardRequiresAuthentication(): void
    {
        $response = $this->get('/fr/superadmin/dashboard');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
    }

    public function testDashboardDisplaysStatsWhenAuthenticated(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
        $response->assertViewHas('stats');
    }

    public function testLogoutClearsSession(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->post('/fr/superadmin/logout');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
        $response->assertSessionMissing('super_admin_verified');
    }

    public function testSessionExpiresAfterTimeout(): void
    {
        $response = $this->withSession([
            'super_admin_verified' => true,
            'super_admin_expires_at' => now()->subHours(3)->timestamp,
        ])->get('/fr/superadmin/dashboard');

        $response->assertRedirect(route('superadmin.index', ['locale' => 'fr']));
    }

    public function testDashboardWithInvalidPeriodFallsBackToDefault(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard?period=2d');

        $response->assertStatus(200);
        $response->assertViewIs('superadmin.dashboard');
        $response->assertViewHas('period', '30d');
    }

    public function testDashboardWithValidPeriodUsesRequestedPeriod(): void
    {
        $response = $this->withSession(['super_admin_verified' => true])
            ->get('/fr/superadmin/dashboard?period=7d');

        $response->assertStatus(200);
        $response->assertViewHas('period', '7d');
    }
}
