<?php

namespace App\Services\Pricing;

use App\Models\GoldPrice;
use App\Models\GoldPriceHistory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class GoldPriceSyncService
{
    private const OUNCE_GRAMS = 31.1034768;
    public const AUTO_REFRESH_INTERVAL_MINUTES = 15;

    public function current(): ?GoldPrice
    {
        return GoldPrice::latestSnapshot();
    }

    public function latestRemoteSnapshot(?string $currency = null): ?GoldPriceHistory
    {
        return GoldPriceHistory::query()
            ->where('source', 'remote')
            ->when($currency, fn ($query) => $query->where('source_currency', strtoupper($currency)))
            ->orderByDesc('synced_at')
            ->orderByDesc('id')
            ->first();
    }

    public function history(int $limit = 12)
    {
        return GoldPriceHistory::query()
            ->with('actor')
            ->orderByDesc('synced_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    public function autoRefreshIntervalMinutes(): int
    {
        return self::AUTO_REFRESH_INTERVAL_MINUTES;
    }

    public function remoteSyncConfigured(): bool
    {
        return trim((string) $this->activeProviderKey()) !== '';
    }

    private function activeProvider(): string
    {
        return strtolower(trim((string) config('services.gold_api.provider', 'goldapi'))) ?: 'goldapi';
    }

    private function activeProviderKey(): string
    {
        return (string) match ($this->activeProvider()) {
            'twelvedata' => config('services.gold_api.twelvedata_key', ''),
            default => config('services.gold_api.key', ''),
        };
    }

    public function isRefreshDue(?GoldPrice $current = null, ?int $intervalMinutes = null): bool
    {
        $intervalMinutes = $intervalMinutes ?: self::AUTO_REFRESH_INTERVAL_MINUTES;
        $current ??= $this->current();

        if (! $current?->last_update) {
            return true;
        }

        return $current->last_update->lte(now()->subMinutes($intervalMinutes));
    }

    public function refreshCurrentSnapshotIfDue(?int $userId = null, bool $force = false, ?int $intervalMinutes = null): ?GoldPrice
    {
        $intervalMinutes = $intervalMinutes ?: self::AUTO_REFRESH_INTERVAL_MINUTES;
        $current = $this->current();

        if (! $this->remoteSyncConfigured()) {
            return $current;
        }

        if (! $force && ! $this->isRefreshDue($current, $intervalMinutes)) {
            return $current;
        }

        $refreshed = Cache::lock('gold-prices:auto-refresh', 45)->get(function () use ($userId, $force, $intervalMinutes) {
            $freshCurrent = $this->current();

            if (! $force && ! $this->isRefreshDue($freshCurrent, $intervalMinutes)) {
                return $freshCurrent;
            }

            return $this->syncCurrentSaudiPrice($userId);
        });

        return $refreshed instanceof GoldPrice ? $refreshed : $this->current();
    }

    /**
     * @param  array<string, mixed>  $manualInput
     */
    public function updateManually(array $manualInput, ?int $userId = null): GoldPrice
    {
        $currency = strtoupper((string) ($manualInput['currency'] ?? 'SAR'));
        $price14 = round((float) $manualInput['price14'], 2);
        $price18 = round((float) $manualInput['price18'], 2);
        $price21 = round((float) $manualInput['price21'], 2);
        $price22 = round((float) $manualInput['price22'], 2);
        $price24 = round((float) $manualInput['price24'], 2);

        return $this->persistSnapshot([
            'ounce_price' => round($price24 * self::OUNCE_GRAMS, 2),
            'ounce_14_price' => $price14,
            'ounce_18_price' => $price18,
            'ounce_21_price' => $price21,
            'ounce_22_price' => $price22,
            'ounce_24_price' => $price24,
            'currency' => $currency,
            'source' => 'manual',
            'source_currency' => $currency,
            'meta' => [
                'manual_input' => [
                    'price14' => $price14,
                    'price18' => $price18,
                    'price21' => $price21,
                    'price22' => $price22,
                    'price24' => $price24,
                ],
            ],
        ], 'manual', $currency, $userId, [
            'manual_input' => $manualInput,
        ]);
    }

    public function syncFromRemote(string $currency = 'SAR', ?int $userId = null): GoldPrice
    {
        $currency = strtoupper($currency);
        $payload = $this->fetchRemoteSnapshot($currency);

        return $this->persistSnapshot([
            'ounce_price' => round((float) ($payload['price'] ?? 0), 2),
            'ounce_14_price' => round((float) ($payload['price_gram_14k'] ?? 0), 2),
            'ounce_18_price' => round((float) ($payload['price_gram_18k'] ?? 0), 2),
            'ounce_21_price' => round((float) ($payload['price_gram_21k'] ?? 0), 2),
            'ounce_22_price' => round((float) ($payload['price_gram_22k'] ?? 0), 2),
            'ounce_24_price' => round((float) ($payload['price_gram_24k'] ?? 0), 2),
            'currency' => strtoupper((string) ($payload['currency'] ?? $currency)),
            'source' => 'remote',
            'source_currency' => $currency,
            'meta' => [
                'timestamp' => $payload['timestamp'] ?? null,
                'metal' => $payload['metal'] ?? null,
                'ask' => $payload['ask'] ?? null,
                'bid' => $payload['bid'] ?? null,
                'open_price' => $payload['open_price'] ?? null,
                'prev_close_price' => $payload['prev_close_price'] ?? null,
                'low_price' => $payload['low_price'] ?? null,
                'high_price' => $payload['high_price'] ?? null,
            ],
        ], 'remote', $currency, $userId, $payload);
    }

    /**
     * Legacy wrapper kept for old call sites.
     */
    public function syncCurrentSaudiPrice(?int $userId = null): GoldPrice
    {
        return $this->syncFromRemote('SAR', $userId);
    }

    /**
     * @return array<string, mixed>
     */
    public function fetchRemoteSnapshot(string $currency = 'SAR'): array
    {
        return match ($this->activeProvider()) {
            'twelvedata' => $this->fetchFromTwelveData($currency),
            default => $this->fetchFromGoldApi($currency),
        };
    }

    /**
     * goldapi.io — returns per-karat gram prices already in the target currency.
     *
     * @return array<string, mixed>
     */
    private function fetchFromGoldApi(string $currency = 'SAR'): array
    {
        $token = (string) config('services.gold_api.key', '');
        $baseUrl = rtrim((string) config('services.gold_api.base_url', 'https://www.goldapi.io'), '/');
        $symbol = (string) config('services.gold_api.symbol', 'XAU');

        if ($token === '') {
            throw new RuntimeException('لم يتم ضبط مفتاح خدمة أسعار الذهب في الإعدادات البيئية.');
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->withHeaders([
                'x-access-token' => $token,
                'Content-Type' => 'application/json',
            ])
            ->get(sprintf('%s/api/%s/%s', $baseUrl, $symbol, strtoupper($currency)));

        if ($response->failed()) {
            // Log the real HTTP status + body so the actual cause (invalid key
            // 401/403, exhausted monthly quota 429, endpoint down 5xx…) is
            // diagnosable from storage/logs instead of a generic message.
            Log::warning('[gold-price-sync] remote fetch failed', [
                'status' => $response->status(),
                'currency' => $currency,
                'symbol' => $symbol,
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            throw new RuntimeException(
                'تعذر تحديث أسعار الذهب من الخدمة الخارجية (رمز الاستجابة: ' . $response->status() . ').'
            );
        }

        $payload = $response->json();

        if (!is_array($payload) || !isset($payload['price_gram_21k'], $payload['price_gram_24k'], $payload['price'])) {
            Log::warning('[gold-price-sync] remote response invalid', [
                'currency' => $currency,
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            throw new RuntimeException('استجابة خدمة أسعار الذهب غير صالحة.');
        }

        return $payload;
    }

    /**
     * twelvedata.com — returns the XAU/USD spot (ounce, USD). Karat gram prices
     * and the SAR conversion are computed locally, then shaped like the goldapi
     * payload the rest of the service already consumes.
     *
     * @return array<string, mixed>
     */
    private function fetchFromTwelveData(string $currency = 'SAR'): array
    {
        $token = (string) config('services.gold_api.twelvedata_key', '');
        $baseUrl = rtrim((string) config('services.gold_api.twelvedata_base_url', 'https://api.twelvedata.com'), '/');

        if ($token === '') {
            throw new RuntimeException('لم يتم ضبط مفتاح خدمة أسعار الذهب (Twelve Data) في الإعدادات البيئية.');
        }

        $response = Http::acceptJson()
            ->timeout(15)
            ->get($baseUrl . '/price', [
                'symbol' => 'XAU/USD',
                'apikey' => $token,
            ]);

        $body = $response->json();

        // Twelve Data returns HTTP 200 even for errors, with {status:"error",...}.
        if ($response->failed() || (is_array($body) && ($body['status'] ?? null) === 'error')) {
            Log::warning('[gold-price-sync] twelvedata fetch failed', [
                'status' => $response->status(),
                'currency' => $currency,
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            $reason = is_array($body) ? ($body['message'] ?? '') : '';

            throw new RuntimeException(trim(
                'تعذر تحديث أسعار الذهب من الخدمة الخارجية (Twelve Data). ' . $reason
            ));
        }

        $ounceUsd = (float) ($body['price'] ?? 0);

        if ($ounceUsd <= 0) {
            Log::warning('[gold-price-sync] twelvedata response invalid', [
                'currency' => $currency,
                'body' => Str::limit((string) $response->body(), 500),
            ]);

            throw new RuntimeException('استجابة خدمة أسعار الذهب غير صالحة.');
        }

        return $this->buildOunceUsdPayload($ounceUsd, strtoupper($currency), [
            'provider' => 'twelvedata',
        ]);
    }

    /**
     * Build the goldapi-shaped payload from a USD ounce spot price: convert to
     * the target currency (SAR via the configured peg), derive the 24k gram
     * price, then scale by karat purity for the other karats.
     *
     * @param  array<string, mixed>  $extraMeta
     * @return array<string, mixed>
     */
    private function buildOunceUsdPayload(float $ounceUsd, string $currency, array $extraMeta = []): array
    {
        $rate = $currency === 'SAR'
            ? (float) config('services.gold_api.sar_per_usd', 3.75)
            : 1.0;

        $ounceInCurrency = $ounceUsd * $rate;
        $gram24 = $ounceInCurrency / self::OUNCE_GRAMS;

        return array_merge([
            'price' => round($ounceInCurrency, 2),
            'price_gram_24k' => round($gram24, 2),
            'price_gram_22k' => round($gram24 * 22 / 24, 2),
            'price_gram_21k' => round($gram24 * 21 / 24, 2),
            'price_gram_18k' => round($gram24 * 18 / 24, 2),
            'price_gram_14k' => round($gram24 * 14 / 24, 2),
            'currency' => $currency,
            'timestamp' => time(),
            'metal' => 'XAU',
        ], $extraMeta);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $payload
     */
    private function persistSnapshot(array $attributes, string $source, string $sourceCurrency, ?int $userId, array $payload): GoldPrice
    {
        $timestamp = Carbon::now();

        return DB::transaction(function () use ($attributes, $payload, $source, $sourceCurrency, $timestamp, $userId) {
            $current = GoldPrice::latestSnapshot();

            if ($current) {
                $current->update(array_merge($attributes, [
                    'last_update' => $timestamp,
                ]));
            } else {
                $current = GoldPrice::create(array_merge($attributes, [
                    'last_update' => $timestamp,
                ]));
            }

            GoldPriceHistory::create([
                'source' => $source,
                'source_currency' => $sourceCurrency,
                'ounce_price' => $current->ounce_price,
                'ounce_14_price' => $current->ounce_14_price,
                'ounce_18_price' => $current->ounce_18_price,
                'ounce_21_price' => $current->ounce_21_price,
                'ounce_22_price' => $current->ounce_22_price,
                'ounce_24_price' => $current->ounce_24_price,
                'currency' => $current->currency,
                'payload' => $payload,
                'synced_by_user_id' => $userId,
                'synced_at' => $timestamp,
            ]);

            return $current->fresh();
        });
    }
}
