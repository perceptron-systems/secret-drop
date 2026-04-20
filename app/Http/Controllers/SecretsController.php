<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSecretRequest;
use App\Models\MagicLink;
use App\Models\Secret;
use App\Services\SecretStorageService;
use App\Services\StatsService;
use App\Services\TokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

use function Illuminate\Support\defer;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SecretsController extends Controller
{
    public function __construct(
        private TokenService $tokenService,
        private SecretStorageService $storage,
        private StatsService $stats,
    ) {
    }

    public function create(): View
    {
        return view('secrets.create');
    }

    public function store(StoreSecretRequest $request): JsonResponse
    {
        // Honeypot: bots fill hidden fields, humans don't — return fake success to avoid retries
        if ($request->filled('website')) {
            return response()->json([
                'token' => bin2hex(random_bytes(16)),
                'expire_at' => now()->addDays(7)->toIso8601String(),
            ], 201);
        }

        $validated = $request->validated();

        // Check file storage quota before accepting uploads
        if ($validated['type'] === 'file' && $this->storage->isQuotaExceeded()) {
            return response()->json([
                'error' => 'service_unavailable',
                'message' => __('messages.storage_quota_exceeded'),
            ], 503);
        }

        $expireAt = $this->calculateExpireAt($validated['expiration']);
        $token = $this->tokenService->generatePublicToken();

        $creatorEmail = $validated['creator_email'] ?? null;
        $adminTokenData = $this->tokenService->generateAdminToken();

        $secretData = [
            'token' => $token,
            'admin_token_hash' => $adminTokenData['hash'],
            'type' => $validated['type'],
            'cipher_meta' => $validated['cipher_meta'],
            'max_views' => $validated['max_views'] ?? null,
            'expire_at' => $expireAt,
            'creator_email_hash' => $creatorEmail ? MagicLink::hashEmail($creatorEmail) : null,
        ];

        $fileSize = null;
        if ($validated['type'] === 'text') {
            $secretData['ciphertext'] = $validated['ciphertext'];
        } else {
            $file = $request->file('encrypted_file');
            $fileSize = $file->getSize();
            $filePath = $this->storage->store($token, $file);

            $secretData['file_path'] = $filePath;
        }

        $secret = Secret::create($secretData);

        defer(fn () => $this->trackCreationStats($secret, $validated, $fileSize));

        return response()->json([
            'token' => $secret->token,
            'expire_at' => $expireAt->toIso8601String(),
        ], 201);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function trackCreationStats(Secret $secret, array $validated, ?int $fileSize = null): void
    {
        if ($secret->type === 'text') {
            $this->stats->increment(StatsService::SECRETS_CREATED_TEXT);
        } else {
            $this->stats->increment(StatsService::SECRETS_CREATED_FILE);
            if ($fileSize !== null && $fileSize > 0) {
                $this->stats->increment(StatsService::TOTAL_FILE_SIZE_BYTES, $fileSize);
            }
        }

        if (! empty($validated['cipher_meta']['has_passphrase'])) {
            $this->stats->increment(StatsService::SECRETS_WITH_PASSPHRASE);
        }

        if ($secret->max_views !== null) {
            $this->stats->increment(StatsService::SECRETS_WITH_MAX_VIEWS);
        }

        if (! empty($validated['split_mode'])) {
            $this->stats->increment(StatsService::SECRETS_SPLIT_MODE);
        }

        $this->stats->incrementHeatmap(StatsService::HEATMAP_SECRETS_CREATED);
    }

    public function show(string $token): View
    {
        return view('secrets.show', ['token' => $token]);
    }

    public function fetch(string $token): JsonResponse
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret || ! $secret->isAccessible()) {
            return response()->json(['error' => 'not_found'], 404);
        }

        $willBeDestroyed = $secret->max_views !== null
            && $secret->read_count + 1 >= $secret->max_views;

        $data = [
            'type' => $secret->type,
            'cipher_meta' => $secret->cipher_meta,
            'will_be_destroyed' => $willBeDestroyed,
        ];

        if ($secret->type === 'text') {
            $data['ciphertext'] = $secret->ciphertext;
        }
        // For files, metadata (filename, mime, size) is encrypted in the payload

        return response()->json($data);
    }

    public function confirmRead(string $token): JsonResponse
    {
        return DB::transaction(function () use ($token) {
            $secret = Secret::where('token', $token)->lockForUpdate()->first();

            if (! $secret || ! $secret->isAccessible()) {
                return response()->json(['error' => 'not_found'], 404);
            }

            $isFirstRead = $secret->first_read_at === null;
            $delaySeconds = $isFirstRead ? (int) $secret->created_at->diffInSeconds(now()) : null;

            $secret->incrementReadCount();

            $maxViewsReached = false;
            if ($secret->shouldBeDestroyed()) {
                if ($secret->type === 'file' && $secret->file_path) {
                    $this->storage->delete($secret->file_path);
                }
                $secret->destroyContent();
                $maxViewsReached = $secret->hasReachedMaxViews();
            }

            defer(function () use ($isFirstRead, $delaySeconds, $maxViewsReached) {
                $this->stats->increment(StatsService::SECRETS_READ);
                $this->stats->incrementHeatmap(StatsService::HEATMAP_SECRETS_READ);

                if ($isFirstRead && $delaySeconds !== null) {
                    $this->stats->trackFirstReadDelay($delaySeconds);
                }

                if ($maxViewsReached) {
                    $this->stats->increment(StatsService::SECRETS_MAX_VIEWS_REACHED);
                }
            });

            return response()->json(['success' => true]);
        });
    }

    public function download(string $token): StreamedResponse|Response
    {
        $secret = Secret::where('token', $token)->first();

        if (! $secret || $secret->type !== 'file' || ! $secret->isAccessible()) {
            return response()->view('secrets.not-found', [], 404);
        }

        if (! $secret->file_path || ! $this->storage->exists($secret->file_path)) {
            return response()->view('secrets.not-found', [], 404);
        }

        return $this->storage->download($secret->file_path);
    }

    public function revoke(string $adminToken): JsonResponse
    {
        $secret = Secret::findByAdminToken($adminToken);

        if (! $secret) {
            return response()->json(['error' => 'not_found'], 404);
        }

        if ($secret->isRevoked()) {
            return response()->json(['error' => 'already_revoked'], 409);
        }

        if ($secret->hasReachedMaxViews()) {
            return response()->json(['error' => 'already_consumed'], 409);
        }

        if ($secret->type === 'file' && $secret->file_path) {
            $this->storage->delete($secret->file_path);
        }

        $secret->revoked_at = now();
        $secret->destroyContent();

        return response()->json(['success' => true]);
    }

    private function calculateExpireAt(string $expiration): \Carbon\Carbon
    {
        $hours = config('secrets.expirations.'.$expiration, config('secrets.expirations.'.config('secrets.default_expiration')));

        return now()->addHours($hours);
    }
}
