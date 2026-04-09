<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldPrice;
use App\Services\Pricing\GoldPriceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PricingController extends Controller
{
    public function __construct(
        private readonly GoldPriceSyncService $goldPriceSyncService
    ) {
        $this->middleware('auth:admin-web');
        $this->middleware('permission:employee.gold_prices.show', ['only' => ['index', 'get_gold_stock_market_prices', 'Gold_Price_Api', 'pricing']]);
        $this->middleware('permission:employee.gold_prices.edit', ['only' => ['sync', 'update']]);
    }

    public function index()
    {
        $currentGoldPrice = $this->goldPriceSyncService->current();
        $latestMarketSnapshot = $this->goldPriceSyncService->latestRemoteSnapshot('SAR')
            ?? $this->goldPriceSyncService->latestRemoteSnapshot();
        $priceHistory = $this->goldPriceSyncService->history();

        return view('admin.pricing.index', compact('currentGoldPrice', 'latestMarketSnapshot', 'priceHistory'));
    }

    public function get_gold_stock_market_prices()
    {
        $latestUsdSnapshot = $this->goldPriceSyncService->latestRemoteSnapshot('USD');
        $latestSarSnapshot = $this->goldPriceSyncService->latestRemoteSnapshot('SAR');
        $remoteHistory = $this->goldPriceSyncService->history()
            ->where('source', 'remote')
            ->take(10)
            ->values();

        return view('admin.pricing.stock_market', compact('latestUsdSnapshot', 'latestSarSnapshot', 'remoteHistory'));
    }

    public function live(Request $request): JsonResponse
    {
        $request->validate([
            'refresh' => ['nullable', 'boolean'],
            'force' => ['nullable', 'boolean'],
        ]);

        $beforeRefresh = $this->goldPriceSyncService->current();
        $beforeRefreshTimestamp = $beforeRefresh?->last_update?->toIso8601String();
        $autoRefreshed = false;
        $message = null;
        $hasError = false;

        if ($request->boolean('refresh') || $request->boolean('force')) {
            try {
                $this->goldPriceSyncService->refreshCurrentSnapshotIfDue(
                    auth('admin-web')->id(),
                    $request->boolean('force')
                );
            } catch (RuntimeException $exception) {
                $message = $exception->getMessage();
                $hasError = true;
            }
        }

        $currentGoldPrice = $this->goldPriceSyncService->current();
        $currentTimestamp = $currentGoldPrice?->last_update?->toIso8601String();
        $autoRefreshed = $beforeRefreshTimestamp !== null
            ? $beforeRefreshTimestamp !== $currentTimestamp
            : $currentTimestamp !== null;

        if (! $message) {
            if (! $currentGoldPrice) {
                $message = $this->goldPriceSyncService->remoteSyncConfigured()
                    ? 'لا يوجد Snapshot أسعار محفوظ حتى الآن.'
                    : 'التحديث التلقائي لأسعار الذهب متوقف حتى يتم ضبط مفتاح الخدمة.';
            } elseif (! $this->goldPriceSyncService->remoteSyncConfigured()) {
                $message = 'يتم عرض آخر Snapshot محفوظ، لكن التحديث التلقائي متوقف حتى يتم ضبط مفتاح الخدمة.';
            } else {
                $message = $autoRefreshed
                    ? 'تم تحديث أسعار الذهب تلقائيًا.'
                    : 'الأسعار المعروضة هي أحدث Snapshot محفوظ حاليًا.';
            }
        }

        return response()->json([
            'success' => ! $hasError,
            'message' => $message,
            'auto_refreshed' => $autoRefreshed,
            'refresh_interval_minutes' => $this->goldPriceSyncService->autoRefreshIntervalMinutes(),
            'remote_sync_configured' => $this->goldPriceSyncService->remoteSyncConfigured(),
            'current' => $this->serializeCurrentGoldPrice($currentGoldPrice),
            'latest_market_snapshot' => $this->serializeHistorySnapshot(
                $this->goldPriceSyncService->latestRemoteSnapshot('SAR')
                ?? $this->goldPriceSyncService->latestRemoteSnapshot()
            ),
            'market_snapshots' => [
                'USD' => $this->serializeHistorySnapshot($this->goldPriceSyncService->latestRemoteSnapshot('USD')),
                'SAR' => $this->serializeHistorySnapshot($this->goldPriceSyncService->latestRemoteSnapshot('SAR')),
            ],
        ]);
    }

    public function pricing()
    {
        $pricings = GoldPrice::query()->orderByDesc('last_update')->limit(1)->get();

        return view('admin.welcome', compact('pricings'));
    }

    /**
     * Legacy wrapper kept for old internal calls.
     */
    public function updatePricng()
    {
        return $this->goldPriceSyncService->syncCurrentSaudiPrice(auth('admin-web')->id());
    }

    public function sync(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['nullable', 'in:SAR,USD'],
            'redirect_to' => ['nullable', 'in:prices,stock_market'],
        ]);

        $redirectRoute = ($validated['redirect_to'] ?? null) === 'stock_market'
            ? 'gold.stock.market.prices'
            : 'prices';

        try {
            $this->goldPriceSyncService->syncFromRemote($validated['currency'] ?? 'SAR', auth('admin-web')->id());

            return redirect()
                ->route($redirectRoute)
                ->with('success', 'تم تحديث أسعار الذهب من الخدمة الخارجية بنجاح.');
        } catch (RuntimeException $exception) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', $exception->getMessage());
        }
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string', 'max:10'],
            'price14' => ['required', 'numeric', 'min:0'],
            'price18' => ['required', 'numeric', 'min:0'],
            'price21' => ['required', 'numeric', 'min:0'],
            'price22' => ['required', 'numeric', 'min:0'],
            'price24' => ['required', 'numeric', 'min:0'],
        ]);

        $this->goldPriceSyncService->updateManually($validated, auth('admin-web')->id());

        return redirect()
            ->route('prices')
            ->with('success', 'تم تحديث أسعار الذهب يدويًا وحفظها في السجل.');
    }

    public function Gold_Price_Api(string $curr = 'SAR'): JsonResponse
    {
        try {
            return response()->json(
                $this->goldPriceSyncService->fetchRemoteSnapshot($curr)
            );
        } catch (RuntimeException|ValidationException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    private function serializeCurrentGoldPrice(?GoldPrice $goldPrice): array
    {
        if (! $goldPrice) {
            return [
                'exists' => false,
                'last_update_label' => 'لا يوجد تحديث',
            ];
        }

        return [
            'exists' => true,
            'currency' => $goldPrice->currency,
            'source' => $goldPrice->source,
            'source_label' => $goldPrice->source_label,
            'last_update' => $goldPrice->last_update?->toIso8601String(),
            'last_update_label' => $goldPrice->last_update?->format('Y-m-d H:i:s') ?? 'لا يوجد تحديث',
            'ounce_price' => (float) $goldPrice->ounce_price,
            'ounce_price_label' => number_format((float) $goldPrice->ounce_price, 2),
            'ounce_14_price' => (float) $goldPrice->ounce_14_price,
            'ounce_14_price_label' => number_format((float) $goldPrice->ounce_14_price, 2),
            'ounce_18_price' => (float) $goldPrice->ounce_18_price,
            'ounce_18_price_label' => number_format((float) $goldPrice->ounce_18_price, 2),
            'ounce_21_price' => (float) $goldPrice->ounce_21_price,
            'ounce_21_price_label' => number_format((float) $goldPrice->ounce_21_price, 2),
            'ounce_22_price' => (float) $goldPrice->ounce_22_price,
            'ounce_22_price_label' => number_format((float) $goldPrice->ounce_22_price, 2),
            'ounce_24_price' => (float) $goldPrice->ounce_24_price,
            'ounce_24_price_label' => number_format((float) $goldPrice->ounce_24_price, 2),
        ];
    }

    private function serializeHistorySnapshot($snapshot): array
    {
        if (! $snapshot) {
            return [
                'exists' => false,
                'synced_at_label' => 'لا يوجد تحديث',
            ];
        }

        return [
            'exists' => true,
            'currency' => $snapshot->currency,
            'source' => $snapshot->source,
            'source_label' => $snapshot->source_label,
            'synced_at' => $snapshot->synced_at?->toIso8601String(),
            'synced_at_label' => $snapshot->synced_at?->format('Y-m-d H:i:s') ?? 'لا يوجد تحديث',
            'ounce_price' => (float) $snapshot->ounce_price,
            'ounce_price_label' => number_format((float) $snapshot->ounce_price, 2),
            'ounce_21_price' => (float) $snapshot->ounce_21_price,
            'ounce_21_price_label' => number_format((float) $snapshot->ounce_21_price, 2),
            'ounce_24_price' => (float) $snapshot->ounce_24_price,
            'ounce_24_price_label' => number_format((float) $snapshot->ounce_24_price, 2),
        ];
    }
}
