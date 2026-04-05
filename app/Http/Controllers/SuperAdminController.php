<?php

namespace App\Http\Controllers;

use App\Http\Requests\RequestSuperAdminAccessRequest;
use App\Mail\SuperAdminMagicLinkMail;
use App\Models\MagicLink;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SuperAdminController extends Controller
{
    use Concerns\HasSessionAuth;

    protected const SESSION_KEY = 'super_admin_verified';
    protected const SESSION_EXPIRES_KEY = 'super_admin_expires_at';
    private const VALID_PERIODS = ['today', '7d', '30d', '90d', '1y', 'all'];

    public function __construct(
        private TokenService $tokenService,
        private StatsService $stats,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if ($request->session()->get(self::SESSION_KEY)) {
            return redirect()->route('superadmin.dashboard');
        }

        return view('superadmin.index');
    }

    public function requestAccess(RequestSuperAdminAccessRequest $request): RedirectResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $superAdminEmail = strtolower(trim(config('app.super_admin_email', '')));

        if ($email === $superAdminEmail && $superAdminEmail !== '') {
            $tokenData = $this->tokenService->generateMagicLinkToken();

            MagicLink::create([
                'email_hash' => 'superadmin',
                'token_hash' => $tokenData['hash'],
                'expire_at' => now()->addMinutes(config('secrets.magic_link_ttl')),
            ]);

            $url = route('superadmin.verify', ['token' => $tokenData['token']]);
            Mail::to($email)
                ->locale(app()->getLocale())
                ->send(new SuperAdminMagicLinkMail($url));

            defer(fn () => $this->stats->increment(StatsService::MAGIC_LINKS_REQUESTED));
        }

        return redirect()->route('superadmin.accessSent');
    }

    public function verify(Request $request, string $locale, string $token): View|RedirectResponse
    {
        $magicLink = MagicLink::findByToken($token);

        if (! $magicLink || $magicLink->email_hash !== 'superadmin') {
            return view('superadmin.invalid-link');
        }

        if (! $magicLink->isValid()) {
            return view('superadmin.invalid-link');
        }

        if ($request->isMethod('GET')) {
            return view('superadmin.verify-confirm', [
                'token' => $token,
            ]);
        }

        $magicLink->markAsUsed();
        defer(fn () => $this->stats->increment(StatsService::MAGIC_LINKS_USED));

        $request->session()->regenerate();
        $request->session()->put(self::SESSION_KEY, true);
        $request->session()->put(self::SESSION_EXPIRES_KEY, now()->addMinutes(config('secrets.session_ttl'))->timestamp);

        return redirect()->route('superadmin.dashboard');
    }

    public function dashboard(Request $request): View|RedirectResponse
    {
        if (! $this->getSessionAuth($request)) {
            return redirect()->route('superadmin.index');
        }

        $this->renewSessionExpiry($request);

        $data = $this->collectStats($request);

        return view('superadmin.dashboard', $data);
    }

    public function poll(Request $request): JsonResponse
    {
        if (! $this->getSessionAuth($request)) {
            return response()->json(['error' => 'unauthenticated'], 401);
        }

        return response()->json($this->collectStats($request));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('superadmin.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function collectStats(Request $request): array
    {
        $period = $request->input('period', '30d');

        if (! in_array($period, self::VALID_PERIODS, true)) {
            $period = '30d';
        }

        $stats = $this->stats->getStats($period);
        $startDate = $period === 'all' ? null : $stats['start_date'];

        return [
            'stats' => $stats,
            'period' => $period,
            'heatmapCreated' => $this->stats->getHeatmap(StatsService::HEATMAP_SECRETS_CREATED, $startDate),
            'heatmapRead' => $this->stats->getHeatmap(StatsService::HEATMAP_SECRETS_READ, $startDate),
            'avgFirstReadDelay' => $this->stats->getAverageFirstReadDelay($startDate),
            'medianFirstReadDelay' => $this->stats->getMedianFirstReadDelay($startDate),
            'currentDiskUsage' => $this->stats->getCurrentDiskUsage(),
            'pageviews' => $this->stats->getPageviews($startDate),
            'readRate' => $this->stats->getReadRate($startDate),
            'creatorConcentration' => $this->stats->getCreatorConcentration(),
            'systemHealth' => $this->stats->getSystemHealth(),
            'referrers' => $this->stats->getReferrers($startDate),
            'botStats' => $this->stats->getBotStats($startDate),
            'deviceStats' => $this->stats->getDeviceStats($startDate),
            'errorStats' => [
                'total_4xx' => $stats['totals'][StatsService::HTTP_ERRORS_4XX] ?? 0,
                'total_5xx' => $stats['totals'][StatsService::HTTP_ERRORS_5XX] ?? 0,
                'by_code' => $this->stats->getErrorCodeBreakdown($startDate),
                'by_route' => $this->stats->getErrorRoutes($startDate),
            ],
            'responseTime' => $this->stats->getResponseTimeP95($startDate),
            'avgSecretSize' => $this->stats->getAverageSecretSize($startDate),
        ];
    }
}
