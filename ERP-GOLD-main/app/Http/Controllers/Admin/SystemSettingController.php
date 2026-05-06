<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GoldCarat;
use App\Models\GoldCaratType;
use App\Models\Item;
use App\Services\Auth\LoginModeService;
use App\Services\Branding\BrandLogoService;
use App\Services\Invoices\InvoiceBackgroundService;
use App\Services\Invoices\InvoicePrintSettingsService;
use App\Services\Invoices\InvoiceTermsService;
use App\Services\Items\DefaultItemSettingsService;
use App\Services\Purchases\DefaultPurchaseSupplierService;
use App\Services\Shifts\SalesShiftModeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly LoginModeService $loginModeService,
        private readonly BrandLogoService $brandLogoService,
        private readonly InvoicePrintSettingsService $invoicePrintSettingsService,
        private readonly InvoiceTermsService $invoiceTermsService,
        private readonly DefaultPurchaseSupplierService $defaultPurchaseSupplierService,
        private readonly SalesShiftModeService $salesShiftModeService,
        private readonly DefaultItemSettingsService $defaultItemSettingsService,
        private readonly InvoiceBackgroundService $invoiceBackgroundService,
    ) {
        $this->middleware('permission:employee.system_settings.show', ['only' => ['editLoginMode', 'editSalesShiftMode', 'editDefaultPurchaseSupplier', 'editInvoiceTerms', 'editInvoicePrint', 'editBranding', 'editDefaultItemSettings', 'editInvoiceBackground']]);
        $this->middleware('permission:employee.system_settings.edit', ['only' => ['updateLoginMode', 'updateSalesShiftMode', 'updateDefaultPurchaseSupplier', 'updateInvoiceTerms', 'updateInvoicePrint', 'togglePrintFlag', 'updateBranding', 'updateDefaultItemSettings', 'uploadInvoiceBackground', 'saveInvoiceBackgroundScale', 'toggleInvoiceBackground', 'deleteInvoiceBackground']]);
    }

    public function editLoginMode(): View
    {
        return view('admin.settings.login_mode', [
            'loginMode' => $this->loginModeService->currentMode(),
        ]);
    }

    public function updateLoginMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'login_mode' => 'required|in:'.implode(',', $this->loginModeService->availableModes()),
        ]);

        $this->loginModeService->setMode($validated['login_mode']);
        $this->loginModeService->syncAuthenticatedSession($request->user('admin-web'), $request->session()->getId());

        return redirect()
            ->route('admin.system-settings.login-mode.edit')
            ->with('success', 'تم تحديث إعداد تسجيل الدخول بنجاح.');
    }

    public function editSalesShiftMode(): View
    {
        return view('admin.settings.sales_shift_mode', [
            'salesShiftMode' => $this->salesShiftModeService->currentMode(),
        ]);
    }

    public function updateSalesShiftMode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'sales_shift_mode' => 'required|in:'.implode(',', $this->salesShiftModeService->availableModes()),
        ]);

        $this->salesShiftModeService->setMode($validated['sales_shift_mode']);

        return redirect()
            ->route('admin.system-settings.sales-shift.edit')
            ->with('success', 'تم تحديث إعداد اعتماد البيع بالشفت بنجاح.');
    }

    public function editDefaultPurchaseSupplier(Request $request): View
    {
        $user = $request->user('admin-web');

        return view('admin.settings.default_purchase_supplier', [
            'suppliers' => $this->defaultPurchaseSupplierService->supplierOptions($user),
            'defaultSupplierId' => $this->defaultPurchaseSupplierService->currentSupplierId($user),
        ]);
    }

    public function updateDefaultPurchaseSupplier(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'default_supplier_id' => 'nullable|integer',
        ]);

        $supplierId = filled($validated['default_supplier_id'] ?? null)
            ? (int) $validated['default_supplier_id']
            : null;

        if (
            $supplierId !== null
            && ! $this->defaultPurchaseSupplierService->supplierIsVisibleToUser($request->user('admin-web'), $supplierId)
        ) {
            throw ValidationException::withMessages([
                'default_supplier_id' => 'المورد المحدد غير موجود أو غير متاح لهذا المستخدم.',
            ]);
        }

        $this->defaultPurchaseSupplierService->setSupplierId($supplierId);

        return redirect()
            ->route('admin.system-settings.default-purchase-supplier.edit')
            ->with('success', 'تم تحديث المورد الافتراضي للمشتريات بنجاح.');
    }

    public function editInvoiceTerms(): View
    {
        return view('admin.settings.invoice_terms', [
            'invoiceTermContexts' => $this->invoiceTermsService->contexts(),
            'invoiceTermTemplates' => $this->invoiceTermsService->templates(),
            'defaultInvoiceTermsTemplateKeys' => $this->invoiceTermsService->defaultTemplateKeys(),
        ]);
    }

    public function updateInvoiceTerms(Request $request): RedirectResponse
    {
        $allowedContexts = implode(',', collect($this->invoiceTermsService->contexts())->pluck('key')->all());

        $validated = $request->validate([
            'templates' => 'nullable|array',
            'templates.*.key' => 'nullable|string|max:100',
            'templates.*.title' => 'nullable|string|max:255',
            'templates.*.content' => 'nullable|string|max:5000',
            'templates.*.context' => 'nullable|string|in:'.$allowedContexts,
            'templates.*.show_on_invoice' => 'nullable|boolean',
            'default_template_keys' => 'nullable|array',
            'default_template_keys.*' => 'nullable|string|max:100',
        ]);

        $templates = collect($validated['templates'] ?? [])
            ->map(fn ($template) => [
                'key' => $template['key'] ?? null,
                'title' => $template['title'] ?? null,
                'content' => $template['content'] ?? null,
                'context' => $template['context'] ?? null,
                'show_on_invoice' => $template['show_on_invoice'] ?? true,
            ])
            ->all();

        $this->invoiceTermsService->setTemplates(
            $templates,
            $validated['default_template_keys'] ?? [],
        );

        return redirect()
            ->route('admin.system-settings.invoice-terms.edit')
            ->with('success', 'تم تحديث شروط الفاتورة الافتراضية بنجاح.');
    }

    public function editInvoicePrint(): View
    {
        return view('admin.settings.invoice_print', [
            'printSettings' => $this->invoicePrintSettingsService->currentSettings(false),
            'availableFormats' => $this->invoicePrintSettingsService->availableFormats(),
            'availableTemplates' => $this->invoicePrintSettingsService->availableTemplates(),
            'availableOrientations' => $this->invoicePrintSettingsService->availableOrientations(),
        ]);
    }

    public function updateInvoicePrint(Request $request): RedirectResponse
    {
        $dimensionRules = [];
        foreach (['a4', 'a5'] as $format) {
            foreach (['margin_top', 'margin_right', 'margin_bottom', 'margin_left'] as $key) {
                $dimensionRules["dimensions.$format.$key"] = 'nullable|numeric|min:0|max:30';
            }
            $dimensionRules["dimensions.$format.header_height"] = 'nullable|numeric|min:0|max:80';
            $dimensionRules["dimensions.$format.footer_height"] = 'nullable|numeric|min:0|max:60';
            $dimensionRules["dimensions.$format.content_offset_top"] = 'nullable|numeric|min:0|max:80';
        }
        $dimensionRules['dimensions.font_scale'] = 'nullable|numeric|min:0.7|max:1.6';

        $validated = $request->validate(array_merge([
            'format' => 'required|in:'.implode(',', $this->invoicePrintSettingsService->availableFormats()),
            'template' => 'required|in:'.implode(',', array_keys($this->invoicePrintSettingsService->availableTemplates())),
            'orientation' => 'required|in:'.implode(',', array_keys($this->invoicePrintSettingsService->availableOrientations())),
        ], $dimensionRules));

        $showHeader = $request->boolean('show_header');
        $showFooter = $request->boolean('show_footer');

        $this->invoicePrintSettingsService->setSettings(
            $validated['format'],
            $showHeader,
            $showFooter,
            $validated['template'],
            $validated['orientation'],
            $validated['dimensions'] ?? null,
        );

        $bg = $this->invoiceBackgroundForRequest($request);
        $bg->setHideHeader(! $showHeader);
        $bg->setHideFooter(! $showFooter);

        return redirect()
            ->route('admin.system-settings.invoice-print.edit')
            ->with('success', 'تم تحديث إعدادات طباعة الفواتير بنجاح.');
    }

    public function togglePrintFlag(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'flag' => 'required|in:show_header,show_footer',
            'enabled' => 'required|boolean',
        ]);

        $enabled = (bool) $validated['enabled'];

        if ($validated['flag'] === 'show_header') {
            $this->invoicePrintSettingsService->setShowHeader($enabled);
            $this->invoiceBackgroundForRequest($request)->setHideHeader(! $enabled);
        } else {
            $this->invoicePrintSettingsService->setShowFooter($enabled);
            $this->invoiceBackgroundForRequest($request)->setHideFooter(! $enabled);
        }

        return response()->json([
            'ok' => true,
            'flag' => $validated['flag'],
            'enabled' => $enabled,
        ]);
    }

    public function editBranding(): View
    {
        return view('admin.settings.branding', [
            'brandLogoUrl' => $this->brandLogoService->logoUrl(),
        ]);
    }

    public function editDefaultItemSettings(): View
    {
        return view('admin.settings.default_item_settings', [
            'settings' => $this->defaultItemSettingsService->currentSettings(),
            'inventoryClassifications' => Item::inventoryClassificationOptions(),
            'saleModes' => Item::saleModeOptions(),
            'carats' => GoldCarat::all(),
            'caratTypes' => GoldCaratType::all(),
        ]);
    }

    public function updateDefaultItemSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'inventory_classification' => 'nullable|string',
            'sale_mode' => 'nullable|string',
            'gold_carat_type_id' => 'nullable|integer',
            'gold_carat_id' => 'nullable|integer',
            'no_metal_type' => 'nullable|in:fixed,percent',
            'no_metal' => 'nullable|numeric|min:0',
            'labor_cost_per_gram' => 'nullable|numeric|min:0',
            'profit_margin_per_gram' => 'nullable|numeric|min:0',
        ]);

        $this->defaultItemSettingsService->setSettings(array_map(
            fn ($v) => $v ?? '',
            $validated,
        ));

        return redirect()
            ->route('admin.system-settings.default-item-settings.edit')
            ->with('success', 'تم تحديث الإعدادات الافتراضية للأصناف بنجاح.');
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'brand_logo' => 'required|image|max:2048',
        ]);

        $this->brandLogoService->storeUploadedLogo($validated['brand_logo']);

        return redirect()
            ->route('admin.system-settings.branding.edit')
            ->with('success', 'تم تحديث الشعار الرئيسي بنجاح.');
    }

    public function editInvoiceBackground(Request $request): View
    {
        $branchId = $request->user('admin-web')?->branch_id;
        $availableTypes = InvoiceBackgroundService::availableInvoiceTypes();
        $selectedType = InvoiceBackgroundService::normalizeInvoiceType($request->query('invoice_type'))
            ?? InvoiceBackgroundService::TYPE_SALES_STANDARD;
        $selectedFormat = InvoiceBackgroundService::normalizeFormat($request->query('format'))
            ?? InvoiceBackgroundService::FORMAT_A4;

        // Service for context-scoped reads (slider values, paper config).
        $contextService = $this->invoiceBackgroundForRequest($request)
            ->forContext($selectedType, $selectedFormat);

        // Service for branch-level reads (image path, enabled, image info, render mode).
        $branchService = $this->invoiceBackgroundForRequest($request);

        $sampleInvoice = $this->findSampleInvoiceForType($selectedType, $branchId);

        $paperSize = $contextService->currentPaperSize(false) ?: $selectedFormat;
        $paperOrientation = $contextService->currentPaperOrientation(false);
        $previewUrl = $sampleInvoice
            ? $this->buildPreviewUrl($sampleInvoice, $selectedFormat, $paperOrientation)
            : null;

        return view('admin.settings.invoice_background', [
            'hasTemplate' => $branchService->hasTemplate(),
            'isEnabled' => $branchService->isEnabled(),
            'scale' => $contextService->currentScale(false),
            'paperSize' => $paperSize,
            'paperOrientation' => $paperOrientation,
            'imageInfo' => $branchService->currentImageInfo(),
            'contentTop' => $contextService->currentContentTop(false),
            'contentBottom' => $contextService->currentContentBottom(false),
            'contentWidth' => $contextService->currentContentWidth(false),
            'contentScale' => $contextService->currentContentScale(false),
            'fontScale' => $contextService->currentFontScale(false),
            'offsetX' => $contextService->currentOffsetX(false),
            'hideHeader' => $contextService->isHideHeader(false),
            'hideFooter' => $contextService->isHideFooter(false),
            'sampleInvoice' => $sampleInvoice,
            'previewUrl' => $previewUrl,
            'availableInvoiceTypes' => $availableTypes,
            'selectedInvoiceType' => $selectedType,
            'selectedFormat' => $selectedFormat,
        ]);
    }

    /**
     * Find a sample invoice that matches the selected document type so the
     * preview iframe shows the actual layout the user is configuring.
     */
    private function findSampleInvoiceForType(string $invoiceType, ?int $branchId): ?\App\Models\Invoice
    {
        $constraints = match ($invoiceType) {
            InvoiceBackgroundService::TYPE_SALES_STANDARD => ['type' => 'sale', 'sale_type' => 'standard'],
            InvoiceBackgroundService::TYPE_SALES_SIMPLIFIED => ['type' => 'sale', 'sale_type' => 'simplified'],
            InvoiceBackgroundService::TYPE_SALES_RETURN_STANDARD => ['type' => 'sale_return', 'sale_type' => 'standard'],
            InvoiceBackgroundService::TYPE_SALES_RETURN_SIMPLIFIED => ['type' => 'sale_return', 'sale_type' => 'simplified'],
            InvoiceBackgroundService::TYPE_PURCHASE => ['type' => 'purchase'],
            InvoiceBackgroundService::TYPE_PURCHASE_RETURN => ['type' => 'purchase_return'],
            default => [],
        };

        $primary = \App\Models\Invoice::query()->latest();
        foreach ($constraints as $column => $value) {
            $primary->where($column, $value);
        }
        if ($branchId) {
            $primary->where('branch_id', $branchId);
        }

        $invoice = $primary->first();

        if ($invoice) {
            return $invoice;
        }

        // Fallback: any invoice (so the preview pane is never blank when at
        // least one invoice exists in the system).
        $fallback = \App\Models\Invoice::query()->latest();
        if ($branchId) {
            $fallback->where('branch_id', $branchId);
        }

        return $fallback->first();
    }

    private function buildPreviewUrl(\App\Models\Invoice $invoice, string $paperSize, string $paperOrientation): string
    {
        $route = match ($invoice->type) {
            'purchase' => 'purchases.show',
            'purchase_return' => 'purchase_return.show',
            default => 'sales.show',
        };

        return route($route, [
            'id' => $invoice->id,
            'paper' => $paperSize,
            'orientation' => $paperOrientation,
            'bg_paper_size' => $paperSize,
            'bg_paper_orientation' => $paperOrientation,
        ]);
    }

    public function uploadInvoiceBackground(Request $request): RedirectResponse
    {
        $request->validate([
            'background_file' => 'required|file|mimes:jpeg,jpg,png,webp,pdf|max:10240',
        ]);

        try {
            $this->invoiceBackgroundForRequest($request)->upload($request->file('background_file'));
        } catch (\RuntimeException $e) {
            return redirect()
                ->route('admin.system-settings.invoice-background.edit')
                ->withErrors(['background_file' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.system-settings.invoice-background.edit')
            ->with('success', 'تم رفع التصميم بنجاح وتفعيله.');
    }

    public function saveInvoiceBackgroundScale(Request $request): \Illuminate\Http\JsonResponse
    {
        $validTypes = array_keys(InvoiceBackgroundService::availableInvoiceTypes());

        $validated = $request->validate([
            'scale' => 'required|numeric|min:0.3|max:2.0',
            'print_format' => 'nullable|in:'.implode(',', $this->invoicePrintSettingsService->availableFormats()),
            'print_orientation' => 'nullable|in:'.implode(',', array_keys($this->invoicePrintSettingsService->availableOrientations())),
            'paper_size' => 'nullable|in:a4,a5',
            'paper_orientation' => 'nullable|in:portrait,landscape',
            'content_top' => 'nullable|numeric|min:0|max:200',
            'content_bottom' => 'nullable|numeric|min:0|max:200',
            'content_width' => 'nullable|numeric|min:50|max:100',
            'content_scale' => 'nullable|numeric|min:0.5|max:1.5',
            'font_scale' => 'nullable|numeric|min:0.7|max:1.4',
            'hide_header' => 'nullable|boolean',
            'hide_footer' => 'nullable|boolean',
            'offset_x' => 'nullable|numeric|min:-50|max:50',
            'offset_y' => 'nullable|numeric|min:-50|max:50',
            'invoice_type' => 'nullable|in:'.implode(',', $validTypes),
            'format' => 'nullable|in:a4,a5',
        ]);

        $invoiceBackgroundService = $this->invoiceBackgroundForRequest($request)
            ->forContext(
                $validated['invoice_type'] ?? null,
                $validated['format'] ?? ($validated['paper_size'] ?? null)
            );

        $invoiceBackgroundService->setScale((float) $validated['scale']);

        if (isset($validated['paper_size'])) {
            $invoiceBackgroundService->setPaperSize($validated['paper_size']);
        }
        if (isset($validated['paper_orientation'])) {
            $invoiceBackgroundService->setPaperOrientation($validated['paper_orientation']);
        }
        if (isset($validated['content_top'])) {
            $invoiceBackgroundService->setContentTop((float) $validated['content_top']);
        }
        if (isset($validated['content_bottom'])) {
            $invoiceBackgroundService->setContentBottom((float) $validated['content_bottom']);
        }
        if (isset($validated['content_width'])) {
            $invoiceBackgroundService->setContentWidth((float) $validated['content_width']);
        }
        if (isset($validated['content_scale'])) {
            $invoiceBackgroundService->setContentScale((float) $validated['content_scale']);
        }
        if (isset($validated['font_scale'])) {
            $invoiceBackgroundService->setFontScale((float) $validated['font_scale']);
        }
        $hasContext = ! empty($validated['invoice_type']) && ! empty($validated['format']);

        if (array_key_exists('hide_header', $validated)) {
            $hideHeader = (bool) $validated['hide_header'];
            $invoiceBackgroundService->setHideHeader($hideHeader);
            // Only update the global digital-header preference when no per-context
            // scope is selected — otherwise the user would unexpectedly affect
            // every other invoice type they configure.
            if (! $hasContext) {
                $this->invoicePrintSettingsService->setShowHeader(! $hideHeader);
            }
        }
        if (array_key_exists('hide_footer', $validated)) {
            $hideFooter = (bool) $validated['hide_footer'];
            $invoiceBackgroundService->setHideFooter($hideFooter);
            if (! $hasContext) {
                $this->invoicePrintSettingsService->setShowFooter(! $hideFooter);
            }
        }
        if (isset($validated['offset_x'])) {
            $invoiceBackgroundService->setOffsetX((float) $validated['offset_x']);
        }
        if (isset($validated['offset_y'])) {
            $invoiceBackgroundService->setOffsetY((float) $validated['offset_y']);
        }

        if (isset($validated['print_format'])) {
            $currentPrintSettings = $this->invoicePrintSettingsService->currentSettings(false);
            $this->invoicePrintSettingsService->setSettings(
                $validated['print_format'],
                (bool) $currentPrintSettings['show_header'],
                (bool) $currentPrintSettings['show_footer'],
                (string) $currentPrintSettings['template'],
                $validated['print_orientation'] ?? (string) $currentPrintSettings['orientation'],
            );
        }

        return response()->json(['ok' => true]);
    }

    public function toggleInvoiceBackground(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        $this->invoiceBackgroundForRequest($request)->setEnabled((bool) $validated['enabled']);

        return redirect()
            ->route('admin.system-settings.invoice-background.edit')
            ->with('success', (bool) $validated['enabled'] ? 'تم تفعيل خلفية الفاتورة.' : 'تم إيقاف خلفية الفاتورة.');
    }

    public function deleteInvoiceBackground(Request $request): RedirectResponse
    {
        $this->invoiceBackgroundForRequest($request)->delete();

        return redirect()
            ->route('admin.system-settings.invoice-background.edit')
            ->with('success', 'تم حذف التصميم بنجاح.');
    }

    private function invoiceBackgroundForRequest(Request $request): InvoiceBackgroundService
    {
        return $this->invoiceBackgroundService->forBranch(
            $request->user('admin-web')?->branch_id ? (int) $request->user('admin-web')->branch_id : null
        );
    }
}
