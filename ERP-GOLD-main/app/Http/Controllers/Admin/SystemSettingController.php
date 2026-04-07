<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Auth\LoginModeService;
use App\Services\Branding\BrandLogoService;
use App\Services\Invoices\InvoicePrintSettingsService;
use App\Services\Invoices\InvoiceTermsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SystemSettingController extends Controller
{
    public function __construct(
        private readonly LoginModeService $loginModeService,
        private readonly BrandLogoService $brandLogoService,
        private readonly InvoicePrintSettingsService $invoicePrintSettingsService,
        private readonly InvoiceTermsService $invoiceTermsService,
    ) {
        $this->middleware('permission:employee.system_settings.show', ['only' => ['editLoginMode', 'editInvoiceTerms', 'editInvoicePrint', 'editBranding']]);
        $this->middleware('permission:employee.system_settings.edit', ['only' => ['updateLoginMode', 'updateInvoiceTerms', 'updateInvoicePrint', 'updateBranding']]);
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
            'login_mode' => 'required|in:' . implode(',', $this->loginModeService->availableModes()),
        ]);

        $this->loginModeService->setMode($validated['login_mode']);
        $this->loginModeService->syncAuthenticatedSession($request->user('admin-web'), $request->session()->getId());

        return redirect()
            ->route('admin.system-settings.login-mode.edit')
            ->with('success', 'تم تحديث إعداد تسجيل الدخول بنجاح.');
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
            'templates.*.context' => 'nullable|string|in:' . $allowedContexts,
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
        $validated = $request->validate([
            'format' => 'required|in:' . implode(',', $this->invoicePrintSettingsService->availableFormats()),
            'template' => 'required|in:' . implode(',', array_keys($this->invoicePrintSettingsService->availableTemplates())),
            'orientation' => 'required|in:' . implode(',', array_keys($this->invoicePrintSettingsService->availableOrientations())),
        ]);

        $this->invoicePrintSettingsService->setSettings(
            $validated['format'],
            $request->boolean('show_header'),
            $request->boolean('show_footer'),
            $validated['template'],
            $validated['orientation'],
        );

        return redirect()
            ->route('admin.system-settings.invoice-print.edit')
            ->with('success', 'تم تحديث إعدادات طباعة الفواتير بنجاح.');
    }

    public function editBranding(): View
    {
        return view('admin.settings.branding', [
            'brandLogoUrl' => $this->brandLogoService->logoUrl(),
        ]);
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
}
