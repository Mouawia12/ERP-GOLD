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
}
