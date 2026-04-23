<?php

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\AccountingReportsController;
use App\Http\Controllers\Admin\AccountsController;
use App\Http\Controllers\Admin\AccountSettingController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\BankAccountController;
use App\Http\Controllers\Admin\CaratController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\CurrentBranchController;
use App\Http\Controllers\Admin\FinancialVoucherController;
use App\Http\Controllers\Admin\HomeController;
use App\Http\Controllers\Admin\InitialQuantitiesController;
use App\Http\Controllers\Admin\ItemController;
use App\Http\Controllers\Admin\ItemsReportsController;
use App\Http\Controllers\Admin\JournalEntryController;
use App\Http\Controllers\Admin\ManufacturingLossSettlementController;
use App\Http\Controllers\Admin\ManufacturingOrderController;
use App\Http\Controllers\Admin\ManufacturingReceiptController;
use App\Http\Controllers\Admin\ManufacturingReturnController;
use App\Http\Controllers\Admin\PricingController;
use App\Http\Controllers\Admin\PurchasesController;
use App\Http\Controllers\Admin\RolesController;
use App\Http\Controllers\Admin\SalesController;
use App\Http\Controllers\Admin\StockReportsController;
use App\Http\Controllers\Admin\StockSettlementController;
use App\Http\Controllers\Admin\SubscribersController;
use App\Http\Controllers\Admin\SystemSettingController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Admin\WarehouseController;
use App\Models\Invoice;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Web Routes
 * |--------------------------------------------------------------------------
 * |
 * | Here is where you can register web routes for your application. These
 * | routes are loaded by the RouteServiceProvider within a group which
 * | contains the "web" middleware group. Now create something great!
 * |
 */
Route::group(
    [
        'prefix' => LaravelLocalization::setLocale(),
        'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath']
    ], function () {
        Route::get('/', function () {
            return view('admin.auth.login');
        })->name('index');

        // Public Routes
        Route::get('/', [LoginController::class, 'showLoginForm'])->name('index');
        Route::get('/home', [HomeController::class, 'index'])->name('home');

        // *********  admin Routes ******** //
        Route::group(
            [
                'namespace' => 'Admin'
            ], function () {
                Route::get('admin/login', [LoginController::class, 'showLoginForm'])->name('admin.login');
                Route::post('admin/login', [LoginController::class, 'login']);
            }
        );
        Route::group(
            ['middleware' => ['auth:admin-web', 'apply.admin.branch', 'enforce.admin.session'],
                    'prefix' => 'admin',
                    'namespace' => 'Admin'], function () {
                Route::get('/', [LoginController::class, 'showLoginForm']);
                Route::get('/home', [HomeController::class, 'index'])->name('admin.home');
                Route::get('/lock-screen', [HomeController::class, 'lock_screen'])->name('admin.lock.screen');
                Route::match(['get', 'post'], 'logout', [LoginController::class, 'logout'])->name('admin.logout');
                Route::match(['get', 'post'], 'admin/logout', [LoginController::class, 'logout'])->name('admin.logout.legacy');
                Route::post('/current-branch', [CurrentBranchController::class, 'update'])->name('admin.current_branch.update');

                Route::middleware('ops.only')->group(function () {
                    Route::get('/categories', [CategoryController::class, 'index'])->name('categories');
                    Route::post('/storeCategory', [CategoryController::class, 'store'])->name('storeCategory');
                    Route::get('/deleteCategory/{id}', [CategoryController::class, 'destroy'])->name('deleteCategory');
                    Route::get('/getCategory/{id}', [CategoryController::class, 'show'])->name('getCategory');

                    Route::get('/customers/{type}', [CustomerController::class, 'index'])->name('customers');
                    Route::get('/customers/cash/{type}', [CustomerController::class, 'cashDirectory'])->name('customers.cash');
                    Route::get('/customers/reports/{type}', [CustomerController::class, 'reportDirectory'])->name('customers.reports.index');
                    Route::get('/customers/reports/{type}/cash', [CustomerController::class, 'cashReportDirectory'])->name('customers.reports.cash');
                    Route::get('/customers/report/{id}', [CustomerController::class, 'report'])->name('customers.report');
                    Route::get('/customers/report/{id}/cash', [CustomerController::class, 'cashReport'])->name('customers.report.cash');
                    Route::post('customers/store/{type}', [CustomerController::class, 'store'])->name('customers.store');
                    Route::post('customers/quick-store/{type}', [CustomerController::class, 'quickStore'])->name('customers.quick-store');
                    Route::post('/customers/delete/{id}', [CustomerController::class, 'destroy'])->name('customers.delete');
                    Route::get('/customers/get/{id}', [CustomerController::class, 'edit'])->name('customers.get');

                    Route::get('/initial_quantities', [InitialQuantitiesController::class, 'index'])->name('initial_quantities.index');
                    Route::get('/initial_quantities/create', [InitialQuantitiesController::class, 'create'])->name('initial_quantities.create');
                    Route::post('/initial_quantities/store', [InitialQuantitiesController::class, 'store'])->name('initial_quantities.store');

                    Route::get('/stock_settlements', [StockSettlementController::class, 'index'])->name('stock_settlements.index');
                    Route::get('/stock_settlements/create', [StockSettlementController::class, 'create'])->name('stock_settlements.create');
                    Route::get('/stock_settlements/by_default/create', [StockSettlementController::class, 'create_by_default'])->name('stock_settlements.create_by_default');
                    Route::get('/stock_settlements/show/{id}', [StockSettlementController::class, 'show'])->name('stock_settlements.show');
                    Route::post('/stock_settlements/store_by_default', [StockSettlementController::class, 'store_by_default'])->name('stock_settlements.store_by_default');
                    Route::get('/stock_settlements/get_carat_type_stock', [StockSettlementController::class, 'get_carat_type_stock'])->name('stock_settlements.get_carat_type_stock');
                    Route::post('/stock_settlements/store', [StockSettlementController::class, 'store'])->name('stock_settlements.store');
                    Route::post('/stock_settlements/search', [StockSettlementController::class, 'search'])->name('stock_settlements.search');
                    Route::post('/stock_settlements/show_uncounted_items', [StockSettlementController::class, 'show_uncounted_items'])->name('stock_settlements.show_uncounted_items');

                    Route::get('/manufacturing_orders', [ManufacturingOrderController::class, 'index'])->name('manufacturing_orders.index');
                    Route::get('/manufacturing_orders/create', [ManufacturingOrderController::class, 'create'])->name('manufacturing_orders.create');
                    Route::post('/manufacturing_orders/store', [ManufacturingOrderController::class, 'store'])->name('manufacturing_orders.store');
                    Route::get('/manufacturing_orders/show/{id}', [ManufacturingOrderController::class, 'show'])->name('manufacturing_orders.show');
                    Route::get('/manufacturing_receipts/create/{orderId}', [ManufacturingReceiptController::class, 'create'])->name('manufacturing_receipts.create');
                    Route::post('/manufacturing_receipts/store/{orderId}', [ManufacturingReceiptController::class, 'store'])->name('manufacturing_receipts.store');
                    Route::get('/manufacturing_receipts/show/{id}', [ManufacturingReceiptController::class, 'show'])->name('manufacturing_receipts.show');
                    Route::get('/manufacturing_returns/create/{orderId}', [ManufacturingReturnController::class, 'create'])->name('manufacturing_returns.create');
                    Route::post('/manufacturing_returns/store/{orderId}', [ManufacturingReturnController::class, 'store'])->name('manufacturing_returns.store');
                    Route::get('/manufacturing_returns/show/{id}', [ManufacturingReturnController::class, 'show'])->name('manufacturing_returns.show');
                    Route::get('/manufacturing_loss_settlements/create/{orderId}', [ManufacturingLossSettlementController::class, 'create'])->name('manufacturing_loss_settlements.create');
                    Route::post('/manufacturing_loss_settlements/store/{orderId}', [ManufacturingLossSettlementController::class, 'store'])->name('manufacturing_loss_settlements.store');
                    Route::get('/manufacturing_loss_settlements/show/{id}', [ManufacturingLossSettlementController::class, 'show'])->name('manufacturing_loss_settlements.show');

                    Route::get('/sales/{type}', [SalesController::class, 'index'])->name('sales.index');
                    Route::get('/sales/{type}/create', [SalesController::class, 'create'])->name('sales.create');
                    Route::post('/sales/{type}/store', [SalesController::class, 'store'])->name('sales.store');
                    Route::get('/sales/show/{id}', [SalesController::class, 'show'])->name('sales.show');
                    Route::post('/sales/payments', [SalesController::class, 'sales_payment_show'])->name('sales.payments');

                    Route::get('/sales_return/{type}', [SalesController::class, 'sales_return_index'])->name('sales_return.index');
                    Route::get('/sales_return/{type}/create/{id}', [SalesController::class, 'sales_return_create'])->name('sales_return.create');
                    Route::post('/sales_return/{type}/store/{id}', [SalesController::class, 'sales_return_store'])->name('sales_return.store');
                    Route::get('/sales_return/show/{id}', [SalesController::class, 'sales_return_show'])->name('sales_return.show');

                    Route::get('/purchases', [PurchasesController::class, 'index'])->name('purchases.index');
                    Route::get('/purchases/create', [PurchasesController::class, 'create'])->name('purchases.create');
                    Route::post('/purchases/store', [PurchasesController::class, 'store'])->name('purchases.store');
                    Route::get('/purchases/show/{id}', [PurchasesController::class, 'show'])->name('purchases.show');
                    Route::post('/purchases/payments', [PurchasesController::class, 'purchase_payment_show'])->name('purchases.payments');

                    Route::get('/purchase_return', [PurchasesController::class, 'purchase_return_index'])->name('purchase_return.index');
                    Route::get('/purchase_return/create/{id}', [PurchasesController::class, 'purchase_return_create'])->name('purchase_return.create');
                    Route::post('/purchase_return/store/{id}', [PurchasesController::class, 'purchase_return_store'])->name('purchase_return.store');
                    Route::get('/purchase_return/show/{id}', [PurchasesController::class, 'purchase_return_show'])->name('purchase_return.show');

                    Route::get('/shifts', [ShiftController::class, 'index'])->name('admin.shifts.index');
                    Route::post('/shifts', [ShiftController::class, 'store'])->name('admin.shifts.store');
                    Route::patch('/shifts/{shift}/close', [ShiftController::class, 'close'])->name('admin.shifts.close');
                    Route::get('/shifts/{shift}', [ShiftController::class, 'show'])->name('admin.shifts.show');

                    Route::get('/items', [ItemController::class, 'index'])->name('items.index');
                    Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
                    Route::get('/items/edit/{id}', [ItemController::class, 'edit'])->name('items.edit');
                    Route::post('/items/store', [ItemController::class, 'store'])->name('items.store');
                    Route::get('/items/delete/{id}', [ItemController::class, 'destroy'])->name('items.delete');
                    Route::get('/items/get/{id}', [ItemController::class, 'show'])->name('items.get');
                    Route::get('/items/get_code', [ItemController::class, 'getItemCode'])->name('items.get_code');
                    Route::get('/items/lost-barcodes', [ItemController::class, 'lost_barcodes'])->name('items.lost_barcodes');
                    Route::post('/items/lost-barcodes/search', [ItemController::class, 'lost_barcodes_search'])->name('items.lost_barcodes.search');
                    Route::get('/items/{id}/barcode_table', [ItemController::class, 'barcodes_table'])->name('items.barcode_table');
                    Route::post('/items/{id}/store_barcodes', [ItemController::class, 'store_barcodes'])->name('items.store_barcodes');
                    Route::get('/items/{id}/print_barcodes', [ItemController::class, 'print_barcodes'])->name('items.print_barcodes');
                    Route::get('/items/units/{id}/print_barcode', [ItemController::class, 'print_unit_barcode'])->name('items.units.print_barcode');
                    Route::post('/items/search', [ItemController::class, 'search'])->name('items.search');
                    Route::post('/items/purchases/search', [ItemController::class, 'purchases_search'])->name('items.purchases.search');
                    Route::post('/items/initial_quantities/search', [ItemController::class, 'initial_quantities_search'])->name('items.initial_quantities.search');

                    Route::get('/accounts', [AccountsController::class, 'index'])->name('accounts.index');
                    Route::get('/accounts/create', [AccountsController::class, 'create'])->name('accounts.create');
                    Route::post('/accounts/store', [AccountsController::class, 'store'])->name('accounts.store');
                    Route::get('/accounts/delete/{id}', [AccountsController::class, 'destroy'])->name('accounts.delete');

                    Route::post('/accounts/excepted_code', [AccountsController::class, 'excepted_code'])->name('accounts.excepted_code');
                    Route::post('/accounts/search', [AccountsController::class, 'search'])->name('accounts.search');
                    Route::get('/accounts/edit/{id}', [AccountsController::class, 'edit'])->name('accounts.edit');
                    Route::post('/accounts/update/{id}', [AccountsController::class, 'update'])->name('accounts.update');
                    Route::get('/accounts/opening', [AccountsController::class, 'opening'])->name('accounts.opening');
                    Route::post('/accounts/opening', [AccountsController::class, 'opening_store'])->name('accounts.opening.store');

                    Route::get('/accounts/settings', [AccountSettingController::class, 'index'])->name('accounts.settings.index');
                    Route::get('/accounts/settings/create', [AccountSettingController::class, 'create'])->name('accounts.settings.create');
                    Route::post('/accounts/settings/create', [AccountSettingController::class, 'store'])->name('accounts.settings.store');
                    Route::get('/accounts/settings/edit/{id}', [AccountSettingController::class, 'edit'])->name('accounts.settings.edit');
                    Route::post('/accounts/settings/edit/{id}', [AccountSettingController::class, 'update'])->name('accounts.settings.update');
                    Route::get('/accounts/settings/delete/{id}', [AccountSettingController::class, 'destroy'])->name('accounts.settings.destroy');

                    Route::get('/accounts/journals/{type}', [JournalEntryController::class, 'journals'])->name('accounts.journals.index');
                    Route::get('/accounts/journals/preview/{id}', [JournalEntryController::class, 'preview_journal'])->name('accounts.journals.preview');
                    Route::get('/accounts/journals/form/create', [JournalEntryController::class, 'create'])->name('accounts.journals.create');
                    Route::post('/accounts/journals/form/store', [JournalEntryController::class, 'store'])->name('accounts.journals.store');
                    Route::get('/accounts/journals/delete/{id}', [JournalEntryController::class, 'delete'])->name('accounts.journals.delete');

                    Route::get('/reports/trail_balance', [AccountingReportsController::class, 'trail_balance'])->name('trail_balance.index');
                    Route::post('/reports/trail_balance', [AccountingReportsController::class, 'trail_balance_search'])->name('trail_balance.search');

                    Route::get('/reports/income_statement', [AccountingReportsController::class, 'income_statement'])->name('income_statement.index');
                    Route::post('/reports/income_statement', [AccountingReportsController::class, 'income_statement_search'])->name('income_statement.search');

                    Route::get('/reports/balance_sheet', [AccountingReportsController::class, 'balance_sheet'])->name('balance_sheet.index');
                    Route::post('/reports/balance_sheet', [AccountingReportsController::class, 'balance_sheet_search'])->name('balance_sheet.search');

                    Route::get('/reports/account_statement', [AccountingReportsController::class, 'account_statement'])->name('account_statement.index');
                    Route::post('/reports/account_statement', [AccountingReportsController::class, 'account_statement_search'])->name('account_statement.search');

                    Route::get('/reports/tax_declaration', [AccountingReportsController::class, 'tax_declaration'])->name('tax.declaration.index');
                    Route::post('/reports/tax_declaration', [AccountingReportsController::class, 'tax_declaration_search'])->name('tax.declaration.search');

                    Route::get('/reports/items/list', [ItemsReportsController::class, 'item_list_report'])->name('reports.items.list');
                    Route::post('/reports/items/list', [ItemsReportsController::class, 'item_list_report_search'])->name('reports.items.list.search');

                    Route::get('/reports/sold_items_report', [ItemsReportsController::class, 'sold_items_report'])->name('reports.sold_items_report.index');
                    Route::post('/reports/sold_items_report', [ItemsReportsController::class, 'sold_items_report_search'])->name('reports.sold_items_report.search');

                    Route::get('/reports/sales_report', [StockReportsController::class, 'sales_report_search'])->name('reports.sales_report.search');
                    Route::post('/reports/sales_report', [StockReportsController::class, 'sales_report'])->name('reports.sales_report.index');

                    Route::get('/reports/sales_total_report', [StockReportsController::class, 'sales_total_report_search'])->name('reports.sales_total_report.search');
                    Route::post('/reports/sales_total_report', [StockReportsController::class, 'sales_total_report'])->name('reports.sales_total_report.index');

                    Route::get('/reports/sales_return_total_report', [StockReportsController::class, 'sales_return_total_report_search'])->name('reports.sales_return_total_report.search');
                    Route::post('/reports/sales_return_total_report', [StockReportsController::class, 'sales_return_total_report'])->name('reports.sales_return_total_report.index');

                    Route::get('/reports/purchases_report', [StockReportsController::class, 'purchases_report_search'])->name('reports.purchases_report.search');
                    Route::post('/reports/purchases_report', [StockReportsController::class, 'purchases_report'])->name('reports.purchases_report.index');

                    Route::get('/reports/purchases_total_report', [StockReportsController::class, 'purchases_total_report_search'])->name('reports.purchases_total_report.search');
                    Route::post('/reports/purchases_total_report', [StockReportsController::class, 'purchases_total_report'])->name('reports.purchases_total_report.index');

                    Route::get('/reports/daily_carat_report', [StockReportsController::class, 'daily_carat_report_search'])->name('reports.daily_carat_report.search');
                    Route::post('/reports/daily_carat_report', [StockReportsController::class, 'daily_carat_report'])->name('reports.daily_carat_report.index');

                    Route::any('/reports/gold_stock', [WarehouseController::class, 'gold_stock'])->name('reports.gold_stock.index');
                    Route::get('/reports/gold_stock/search', [WarehouseController::class, 'gold_stock_search'])->name('reports.gold_stock.search');

                    // تقارير المقتنيات
                    Route::get('/reports/collectible/sales_report', [StockReportsController::class, 'collectible_sales_report_search'])->name('reports.collectible.sales_report.search');
                    Route::post('/reports/collectible/sales_report', [StockReportsController::class, 'collectible_sales_report'])->name('reports.collectible.sales_report.index');
                    Route::get('/reports/collectible/sales_total_report', [StockReportsController::class, 'collectible_sales_total_report_search'])->name('reports.collectible.sales_total_report.search');
                    Route::post('/reports/collectible/sales_total_report', [StockReportsController::class, 'collectible_sales_total_report'])->name('reports.collectible.sales_total_report.index');
                    Route::get('/reports/collectible/purchases_report', [StockReportsController::class, 'collectible_purchases_report_search'])->name('reports.collectible.purchases_report.search');
                    Route::post('/reports/collectible/purchases_report', [StockReportsController::class, 'collectible_purchases_report'])->name('reports.collectible.purchases_report.index');
                    Route::get('/reports/collectible/purchases_total_report', [StockReportsController::class, 'collectible_purchases_total_report_search'])->name('reports.collectible.purchases_total_report.search');
                    Route::post('/reports/collectible/purchases_total_report', [StockReportsController::class, 'collectible_purchases_total_report'])->name('reports.collectible.purchases_total_report.index');
                    Route::get('/reports/collectible/sales_return_report', [StockReportsController::class, 'collectible_sales_return_report_search'])->name('reports.collectible.sales_return_report.search');
                    Route::post('/reports/collectible/sales_return_report', [StockReportsController::class, 'collectible_sales_return_report'])->name('reports.collectible.sales_return_report.index');

                    // تقارير الفضة
                    Route::get('/reports/silver/sales_report', [StockReportsController::class, 'silver_sales_report_search'])->name('reports.silver.sales_report.search');
                    Route::post('/reports/silver/sales_report', [StockReportsController::class, 'silver_sales_report'])->name('reports.silver.sales_report.index');
                    Route::get('/reports/silver/sales_total_report', [StockReportsController::class, 'silver_sales_total_report_search'])->name('reports.silver.sales_total_report.search');
                    Route::post('/reports/silver/sales_total_report', [StockReportsController::class, 'silver_sales_total_report'])->name('reports.silver.sales_total_report.index');
                    Route::get('/reports/silver/purchases_report', [StockReportsController::class, 'silver_purchases_report_search'])->name('reports.silver.purchases_report.search');
                    Route::post('/reports/silver/purchases_report', [StockReportsController::class, 'silver_purchases_report'])->name('reports.silver.purchases_report.index');
                    Route::get('/reports/silver/purchases_total_report', [StockReportsController::class, 'silver_purchases_total_report_search'])->name('reports.silver.purchases_total_report.search');
                    Route::post('/reports/silver/purchases_total_report', [StockReportsController::class, 'silver_purchases_total_report'])->name('reports.silver.purchases_total_report.index');
                    Route::get('/reports/silver/sales_return_report', [StockReportsController::class, 'silver_sales_return_report_search'])->name('reports.silver.sales_return_report.search');
                    Route::post('/reports/silver/sales_return_report', [StockReportsController::class, 'silver_sales_return_report'])->name('reports.silver.sales_return_report.index');

                    // تقارير المقتنيات - إضافية
                    Route::get('/reports/collectible/purchases_return_report', [StockReportsController::class, 'collectible_purchases_return_report_search'])->name('reports.collectible.purchases_return_report.search');
                    Route::post('/reports/collectible/purchases_return_report', [StockReportsController::class, 'collectible_purchases_return_report'])->name('reports.collectible.purchases_return_report.index');
                    Route::get('/reports/collectible/weight_report', [StockReportsController::class, 'collectible_weight_report_search'])->name('reports.collectible.weight_report.search');
                    Route::post('/reports/collectible/weight_report', [StockReportsController::class, 'collectible_weight_report'])->name('reports.collectible.weight_report.index');
                    Route::get('/reports/collectible/sold_items', [ItemsReportsController::class, 'collectible_sold_items_report'])->name('reports.collectible.sold_items_report.index');
                    Route::post('/reports/collectible/sold_items', [ItemsReportsController::class, 'collectible_sold_items_report_search'])->name('reports.collectible.sold_items_report.search');
                    Route::get('/reports/collectible/items_list', [ItemsReportsController::class, 'collectible_item_list_report'])->name('reports.collectible.items.list');
                    Route::post('/reports/collectible/items_list', [ItemsReportsController::class, 'collectible_item_list_report_search'])->name('reports.collectible.items.list.search');

                    // تقارير الفضة - إضافية
                    Route::get('/reports/silver/purchases_return_report', [StockReportsController::class, 'silver_purchases_return_report_search'])->name('reports.silver.purchases_return_report.search');
                    Route::post('/reports/silver/purchases_return_report', [StockReportsController::class, 'silver_purchases_return_report'])->name('reports.silver.purchases_return_report.index');
                    Route::get('/reports/silver/weight_report', [StockReportsController::class, 'silver_weight_report_search'])->name('reports.silver.weight_report.search');
                    Route::post('/reports/silver/weight_report', [StockReportsController::class, 'silver_weight_report'])->name('reports.silver.weight_report.index');
                    Route::get('/reports/silver/sold_items', [ItemsReportsController::class, 'silver_sold_items_report'])->name('reports.silver.sold_items_report.index');
                    Route::post('/reports/silver/sold_items', [ItemsReportsController::class, 'silver_sold_items_report_search'])->name('reports.silver.sold_items_report.search');
                    Route::get('/reports/silver/items_list', [ItemsReportsController::class, 'silver_item_list_report'])->name('reports.silver.items.list');
                    Route::post('/reports/silver/items_list', [ItemsReportsController::class, 'silver_item_list_report_search'])->name('reports.silver.items.list.search');

                    Route::get('/carats/get/{id}', [CaratController::class, 'show'])->name('carats.show');

                    Route::get('/prices', [PricingController::class, 'index'])->name('prices');
                    Route::get('/gold-stock-market-prices', [PricingController::class, 'get_gold_stock_market_prices'])->name('gold.stock.market.prices');
                    Route::get('/gold-prices/live', [PricingController::class, 'live'])->name('gold.prices.live');
                    Route::post('/updatePrices', [PricingController::class, 'sync'])->name('updatePrices');
                    Route::post('/updatePricesManual', [PricingController::class, 'update'])->name('updatePricesManual');
                    Route::get('/gold-price-api', [PricingController::class, 'Gold_Price_Api'])->name('gold.price.api');
                    Route::get('/exchange_rates_all', [PricingController::class, 'exchange_rates_all'])->name('exchange_rates_all');
                    Route::get('/exchange_rates_api/{currency}', [PricingController::class, 'exchange_rates_api'])->name('exchange_rates_api');

                    Route::get('system-settings/login-mode', [SystemSettingController::class, 'editLoginMode'])
                        ->name('admin.system-settings.login-mode.edit');
                    Route::patch('system-settings/login-mode', [SystemSettingController::class, 'updateLoginMode'])
                        ->name('admin.system-settings.login-mode.update');
                    Route::get('system-settings/sales-shift', [SystemSettingController::class, 'editSalesShiftMode'])
                        ->name('admin.system-settings.sales-shift.edit');
                    Route::patch('system-settings/sales-shift', [SystemSettingController::class, 'updateSalesShiftMode'])
                        ->name('admin.system-settings.sales-shift.update');
                    Route::get('system-settings/default-purchase-supplier', [SystemSettingController::class, 'editDefaultPurchaseSupplier'])
                        ->name('admin.system-settings.default-purchase-supplier.edit');
                    Route::patch('system-settings/default-purchase-supplier', [SystemSettingController::class, 'updateDefaultPurchaseSupplier'])
                        ->name('admin.system-settings.default-purchase-supplier.update');
                    Route::get('system-settings/invoice-terms', [SystemSettingController::class, 'editInvoiceTerms'])
                        ->name('admin.system-settings.invoice-terms.edit');
                    Route::patch('system-settings/invoice-terms', [SystemSettingController::class, 'updateInvoiceTerms'])
                        ->name('admin.system-settings.invoice-terms.update');
                    Route::get('system-settings/invoice-print', [SystemSettingController::class, 'editInvoicePrint'])
                        ->name('admin.system-settings.invoice-print.edit');
                    Route::patch('system-settings/invoice-print', [SystemSettingController::class, 'updateInvoicePrint'])
                        ->name('admin.system-settings.invoice-print.update');
                    Route::get('system-settings/branding', [SystemSettingController::class, 'editBranding'])
                        ->name('admin.system-settings.branding.edit');
                    Route::patch('system-settings/branding', [SystemSettingController::class, 'updateBranding'])
                        ->name('admin.system-settings.branding.update');
                    Route::get('system-settings/default-item-settings', [SystemSettingController::class, 'editDefaultItemSettings'])
                        ->name('admin.system-settings.default-item-settings.edit');
                    Route::patch('system-settings/default-item-settings', [SystemSettingController::class, 'updateDefaultItemSettings'])
                        ->name('admin.system-settings.default-item-settings.update');
                    Route::get('system-settings/bank-accounts', [BankAccountController::class, 'index'])
                        ->name('admin.system-settings.bank-accounts.index');
                    Route::get('system-settings/bank-accounts/create', [BankAccountController::class, 'create'])
                        ->name('admin.system-settings.bank-accounts.create');
                    Route::post('system-settings/bank-accounts', [BankAccountController::class, 'store'])
                        ->name('admin.system-settings.bank-accounts.store');
                    Route::get('system-settings/bank-accounts/{bankAccount}/edit', [BankAccountController::class, 'edit'])
                        ->name('admin.system-settings.bank-accounts.edit');
                    Route::patch('system-settings/bank-accounts/{bankAccount}', [BankAccountController::class, 'update'])
                        ->name('admin.system-settings.bank-accounts.update');

                    Route::resource('branches', BranchController::class)->names([
                        'index' => 'admin.branches.index',
                        'create' => 'admin.branches.create',
                        'update' => 'admin.branches.update',
                        'destroy' => 'admin.branches.destroy',
                        'edit' => 'admin.branches.edit',
                        'store' => 'admin.branches.store',
                        'show' => 'admin.branches.show'
                    ]);
                    Route::get('branches/{id}/zatca', [BranchController::class, 'zatca_form'])
                        ->name('admin.branches.zatca');
                    Route::patch('branches/{id}/zatca', [BranchController::class, 'zatca'])
                        ->name('admin.branches.zatca.update');

                    Route::resource('users', UsersController::class)->names([
                        'index' => 'admin.users.index',
                        'create' => 'admin.users.create',
                        'update' => 'admin.users.update',
                        'destroy' => 'admin.users.destroy',
                        'edit' => 'admin.users.edit',
                        'store' => 'admin.users.store',
                        'show' => 'admin.users.show'
                    ]);
                    Route::get('users/{user}/permissions', [UsersController::class, 'editPermissions'])
                        ->name('admin.users.permissions.edit');
                    Route::patch('users/{user}/permissions', [UsersController::class, 'updatePermissions'])
                        ->name('admin.users.permissions.update');

                    Route::get('/financial_vouchers/{type}', [FinancialVoucherController::class, 'index'])->name('financial_vouchers');
                    Route::post('/financial_vouchers/{type}', [FinancialVoucherController::class, 'store'])->name('financial_vouchers.store');
                });

                Route::middleware('owner.only')->group(function () {
                    Route::resource('subscribers', SubscribersController::class)->names([
                        'index' => 'admin.subscribers.index',
                        'create' => 'admin.subscribers.create',
                        'update' => 'admin.subscribers.update',
                        'destroy' => 'admin.subscribers.destroy',
                        'edit' => 'admin.subscribers.edit',
                        'store' => 'admin.subscribers.store',
                        'show' => 'admin.subscribers.show',
                    ]);

                    Route::resource('roles', RolesController::class)->names([
                        'index' => 'admin.roles.index',
                        'create' => 'admin.roles.create',
                        'update' => 'admin.roles.update',
                        'destroy' => 'admin.roles.destroy',
                        'edit' => 'admin.roles.edit',
                        'store' => 'admin.roles.store',
                    ]);

                });

                Route::get('profile/edit/{id}', [UsersController::class, 'edit'])->name('admin.profile.edit');
                Route::patch('profile/update/{id}', [UsersController::class, 'update'])->name('admin.profile.update');
            }
        );
    }
);
