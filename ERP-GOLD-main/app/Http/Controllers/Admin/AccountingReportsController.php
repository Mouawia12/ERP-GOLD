<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\FinancialYear;
use App\Models\InvoiceDetail;
use App\Models\JournalEntryDocument;
use Carbon\Carbon;
use Illuminate\Http\Request;
use DB;

class AccountingReportsController extends Controller
{
    public function trail_balance()
    {
        return view('admin.reports.trail_balance.search');
    }

    public function trail_balance_search(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');

        $accounts = Account::whereDoesntHave('childrens')->where(function ($query) use ($periodFrom, $periodTo) {
            $query->whereHas('documents', function ($query) use ($periodFrom, $periodTo) {
                $query->whereBetween('document_date', [$periodFrom, $periodTo]);
            })->orWhereHas('openingBalanceRelation', function ($query) use ($periodFrom, $periodTo) {
                $query->where('financial_year', FinancialYear::where('is_active', true)->first()->id);
            });
        })->get();

        return view('admin.reports.trail_balance.index', compact('periodFrom', 'periodTo', 'accounts'));
    }

    public function income_statement()
    {
        return view('admin.reports.income_statement.search');
    }

    public function income_statement_search(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');

        $revenuesAccount = Account::where('parent_account_id', null)->where('account_type', 'revenues')->where('transfer_side', 'income_statement')->first();
        $expensesAccount = Account::where('parent_account_id', null)->where('account_type', 'expenses')->where('transfer_side', 'income_statement')->first();

        if (!$revenuesAccount || !$expensesAccount) {
            return redirect()->back()->with('error', 'Revenues or Expenses account not found');
        }

        $profitTotal = abs($revenuesAccount->closingBalance($periodFrom, $periodTo)) - abs($expensesAccount->closingBalance($periodFrom, $periodTo));

        return view('admin.reports.income_statement.index', compact('periodFrom', 'periodTo', 'revenuesAccount', 'expensesAccount', 'profitTotal'));
    }

    public function balance_sheet()
    {
        return view('admin.reports.balance_sheet.search');
    }

    public function balance_sheet_search(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');

        $assetsAccount = Account::where('parent_account_id', null)->where('account_type', 'assets')->where('transfer_side', 'budget')->first();
        $equityAccount = Account::where('parent_account_id', null)->where('account_type', 'equity')->where('transfer_side', 'budget')->first();
        $liabilitiesAccount = Account::where('parent_account_id', null)->where('account_type', 'liabilities')->where('transfer_side', 'budget')->first();

        if (!$assetsAccount || !$equityAccount || !$liabilitiesAccount) {
            return redirect()->back()->with('error', 'Assets, Equity or Liabilities account not found');
        }

        $profitTotal = 0;

        $assetsTotal = abs($assetsAccount->closingBalance($periodFrom, $periodTo));
        $liabilitiesTotal = abs($liabilitiesAccount->closingBalance($periodFrom, $periodTo));
        $equityTotal = abs($equityAccount->closingBalance($periodFrom, $periodTo));
        $profitTotal = $assetsTotal - ($liabilitiesTotal + $equityTotal);

        return view('admin.reports.balance_sheet.index', compact('periodFrom', 'periodTo', 'assetsAccount', 'equityAccount', 'liabilitiesAccount', 'profitTotal'));
    }

    public function account_statement()
    {
        $accounts = Account::get();

        return view('admin.reports.account_statement.search', compact('accounts'));
    }

    public function account_statement_search(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->startOfYear()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->endOfYear()->format('Y-m-d');

        $account = Account::find($request->account_id);
        $documents = JournalEntryDocument::whereIn('account_id', $account->childrensIds)->whereBetween('document_date', [$periodFrom, $periodTo])->get();

        return view('admin.reports.account_statement.index', compact('periodFrom', 'periodTo', 'account', 'documents'));
    }

    public function tax_declaration()
    {
        return view('admin.reports.tax_declaration.search');
    }

    public function tax_declaration_search(Request $request)
    {
        $periodFrom = $request->date_from ? Carbon::parse($request->date_from)->format('Y-m-d') : Carbon::now()->format('Y-m-d');
        $periodTo = $request->date_to ? Carbon::parse($request->date_to)->format('Y-m-d') : Carbon::now()->format('Y-m-d');

        $saleTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'sale');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 15);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $saleReturnTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'sale_return');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 15);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $salesTaxTotal = $saleTotal->tax_total - $saleReturnTotal->tax_total;
        $salesTotal = $saleTotal->total - $saleReturnTotal->total;

        $saleZeroTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'sale');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 0);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $saleZeroReturnTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'sale_return');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 0);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $salesZeroTaxTotal = $saleZeroTotal->tax_total - $saleZeroReturnTotal->tax_total;
        $salesZeroTotal = $saleZeroTotal->total - $saleZeroReturnTotal->total;

        $salesFinalTaxTotal = $salesTaxTotal + $salesZeroTaxTotal;
        $salesFinalTotal = $salesTotal + $salesZeroTotal;

        // purchase

        $purchaseTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'purchase');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 15);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $purchaseReturnTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'purchase_return');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 15);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $purchaseTaxTotal = $purchaseTotal->tax_total - $purchaseReturnTotal->tax_total;
        $purchaseTotal = $purchaseTotal->total - $purchaseReturnTotal->total;

        $purchaseZeroTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'purchase');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 0);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $purchaseZeroReturnTotal = InvoiceDetail::whereHas('invoice', function ($query) {
            return $query->where('type', 'purchase_return');
        })->whereHas('tax', function ($query) {
            return $query->where('rate', 0);
        })->whereBetween('date', [$periodFrom, $periodTo])->select(DB::raw('SUM(line_tax) as tax_total , SUM(line_total) as total'))->first();

        $purchaseZeroTaxTotal = $purchaseZeroTotal->tax_total - $purchaseZeroReturnTotal->tax_total;
        $purchaseZeroTotal = $purchaseZeroTotal->total - $purchaseZeroReturnTotal->total;

        $purchaseFinalTaxTotal = $purchaseTaxTotal + $purchaseZeroTaxTotal;
        $purchaseFinalTotal = $purchaseTotal + $purchaseZeroTotal;

        $fullTaxTotal = $salesFinalTaxTotal - $purchaseFinalTaxTotal;
        $fullTotal = $salesFinalTotal - $purchaseFinalTotal;
        return view('admin.reports.tax_declaration.index', compact('periodFrom', 'periodTo', 'salesTaxTotal', 'salesTotal', 'salesZeroTaxTotal', 'salesZeroTotal', 'salesFinalTaxTotal', 'salesFinalTotal', 'purchaseTaxTotal', 'purchaseTotal', 'purchaseZeroTaxTotal', 'purchaseZeroTotal', 'purchaseFinalTaxTotal', 'purchaseFinalTotal', 'fullTaxTotal', 'fullTotal'));
    }
}
