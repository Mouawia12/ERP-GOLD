<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\InvoiceDetail;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WarehouseController extends Controller
{
    public function gold_stock(Request $request)
    {
        [$periodFrom, $periodTo] = $this->resolvePeriod(
            $request,
            Carbon::now()->startOfYear()->format('Y-m-d'),
            Carbon::now()->endOfYear()->format('Y-m-d')
        );

        $filters = [
            'period_from' => $periodFrom,
            'period_to' => $periodTo,
            'branch_id' => $this->normalizeOptionalFilter($request->input('branch_id')),
        ];

        $caratsTypes = GoldCaratType::query()->orderBy('id')->get();
        $carats = GoldCarat::query()->orderBy('id')->get();
        $baseCarat = GoldCarat::query()->where('transform_factor', 1)->first();
        $company = null;
        $branch = $filters['branch_id'] ? Branch::find($filters['branch_id']) : null;

        $stockByCarat = [];
        $stockByBaseCarat = [];
        $totalDependentStock = 0.0;

        foreach ($caratsTypes as $caratType) {
            foreach ($carats->where('is_pure', $caratType->key === 'pure') as $carat) {
                $inWeight = $this->stockTotalsQuery($filters, (int) $carat->id, (int) $caratType->id)
                    ->sum('in_weight');
                $outWeight = $this->stockTotalsQuery($filters, (int) $carat->id, (int) $caratType->id)
                    ->sum('out_weight');

                $stockByCarat[$caratType->id][$carat->id] = [
                    'in' => round((float) $inWeight, 3),
                    'out' => round((float) $outWeight, 3),
                    'balance' => round((float) $inWeight - (float) $outWeight, 3),
                ];
            }

            $dependentIn = $this->stockDependentQuery($filters, (int) $caratType->id)
                ->selectRaw('COALESCE(SUM(invoice_details.in_weight * gold_carats.transform_factor), 0) as total')
                ->value('total');
            $dependentOut = $this->stockDependentQuery($filters, (int) $caratType->id)
                ->selectRaw('COALESCE(SUM(invoice_details.out_weight * gold_carats.transform_factor), 0) as total')
                ->value('total');
            $dependentBalance = (float) $dependentIn - (float) $dependentOut;

            $stockByBaseCarat[$caratType->id] = [
                'in' => round((float) $dependentIn, 3),
                'out' => round((float) $dependentOut, 3),
                'balance' => round($dependentBalance, 3),
            ];
            $totalDependentStock += $dependentBalance;
        }

        return view('admin.reports.stock_reports.gold_stock.index', compact(
            'caratsTypes',
            'carats',
            'baseCarat',
            'company',
            'periodFrom',
            'periodTo',
            'branch',
            'stockByCarat',
            'stockByBaseCarat',
            'totalDependentStock'
        ));
    }

    public function gold_stock_search()
    {
        $currentUser = auth('admin-web')->user();
        $branches = Branch::query()->where('status', 1)->orderBy('id')->get();

        return view('admin.reports.stock_reports.gold_stock.search', [
            'branches' => $branches,
            'defaultFilters' => [
                'date_from' => Carbon::now()->startOfYear()->format('Y-m-d'),
                'date_to' => Carbon::now()->endOfYear()->format('Y-m-d'),
                'branch_id' => $currentUser?->is_admin ? '' : $currentUser?->branch_id,
            ],
        ]);
    }

    private function normalizeOptionalFilter($value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' || $value === '0' || $value === 0 ? null : $value;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function resolvePeriod(Request $request, string $defaultDateFrom, string $defaultDateTo): array
    {
        $periodFrom = $request->filled('date_from')
            ? Carbon::parse($request->input('date_from'))->format('Y-m-d')
            : $defaultDateFrom;

        $periodTo = $request->filled('date_to')
            ? Carbon::parse($request->input('date_to'))->format('Y-m-d')
            : $defaultDateTo;

        return [$periodFrom, $periodTo];
    }

    private function stockTotalsQuery(array $filters, int $caratId, int $caratTypeId)
    {
        return InvoiceDetail::query()
            ->where('gold_carat_id', $caratId)
            ->where('gold_carat_type_id', $caratTypeId)
            ->whereBetween('date', [$filters['period_from'], $filters['period_to']])
            ->when($filters['branch_id'], function ($query, $branchId) {
                return $query->whereHas('invoice', function ($invoiceQuery) use ($branchId) {
                    $invoiceQuery->where('branch_id', $branchId);
                });
            });
    }

    private function stockDependentQuery(array $filters, int $caratTypeId)
    {
        return InvoiceDetail::query()
            ->join('gold_carats', 'invoice_details.gold_carat_id', '=', 'gold_carats.id')
            ->where('invoice_details.gold_carat_type_id', $caratTypeId)
            ->whereBetween('invoice_details.date', [$filters['period_from'], $filters['period_to']])
            ->when($filters['branch_id'], function ($query, $branchId) {
                return $query->whereExists(function ($subQuery) use ($branchId) {
                    $subQuery
                        ->select(DB::raw(1))
                        ->from('invoices')
                        ->whereColumn('invoices.id', 'invoice_details.invoice_id')
                        ->where('invoices.branch_id', $branchId);
                });
            });
    }
}
