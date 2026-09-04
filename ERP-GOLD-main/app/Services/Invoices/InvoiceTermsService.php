<?php

namespace App\Services\Invoices;

use App\Models\BranchInvoiceTermsSetting;
use App\Models\Invoice;
use App\Models\SystemSetting;
use App\Services\Branches\BranchContextService;
use Illuminate\Database\QueryException;

class InvoiceTermsService
{
    public const SETTING_KEY = 'default_invoice_terms';
    public const TEMPLATES_KEY = 'invoice_terms_templates';
    public const LEGACY_DEFAULT_TEMPLATE_KEY = 'default_invoice_terms_template_key';
    public const DEFAULT_TEMPLATE_KEYS = 'default_invoice_terms_template_keys';

    public const CONTEXT_SALES_SIMPLIFIED = 'sales_simplified';
    public const CONTEXT_SALES_STANDARD = 'sales_standard';
    public const CONTEXT_PURCHASES = 'purchases';

    private ?int $branchId = null;

    private ?BranchInvoiceTermsSetting $branchSettings = null;

    private bool $branchSettingsLoaded = false;

    /**
     * الشروط تخصّ الفرع: فرع الفاتورة عند الطباعة، والفرع النشط عند ضبط
     * الشروط أو إنشاء فاتورة جديدة. تثبيت الفرع يعطي نسخة مستقلة من الخدمة
     * حتى لا يتسرّب فرع فاتورة إلى الفاتورة التي تليها في نفس الطلب.
     */
    public function forBranch(?int $branchId): self
    {
        $service = clone $this;
        $service->branchId = $branchId && $branchId > 0 ? (int) $branchId : null;
        $service->branchSettings = null;
        $service->branchSettingsLoaded = false;

        return $service;
    }

    public function forInvoice(Invoice $invoice): self
    {
        return $this->forBranch($invoice->branch_id ? (int) $invoice->branch_id : null);
    }

    /**
     * @return array<int, array{key: string, title: string}>
     */
    public function contexts(): array
    {
        return [
            [
                'key' => self::CONTEXT_SALES_SIMPLIFIED,
                'title' => 'فواتير البيع المبسطة',
            ],
            [
                'key' => self::CONTEXT_SALES_STANDARD,
                'title' => 'فواتير مبيعات الشركات',
            ],
            [
                'key' => self::CONTEXT_PURCHASES,
                'title' => 'فواتير المشتريات',
            ],
        ];
    }

    public function salesContext(string $saleType): string
    {
        return $saleType === 'standard'
            ? self::CONTEXT_SALES_STANDARD
            : self::CONTEXT_SALES_SIMPLIFIED;
    }

    /**
     * @return array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>
     */
    public function templates(?string $context = null): array
    {
        $templates = $this->normalizedStoredTemplates();

        // فرع لم تُضبط شروطه بعد يبدأ من القوالب الجاهزة. أما الفرع الذي ضبط
        // شروطه فقائمته هي المرجع: ما حذفه المالك منها يبقى محذوفًا، ولا
        // يُبعث نصّ عام قديم في صفحة فاتورة تركها بلا شروط.
        if ($templates === [] && ! $this->hasBranchScopedSettings()) {
            $templates = $this->fallbackTemplates();
        }

        if ($context === null) {
            return $templates;
        }

        return array_values(array_filter(
            $templates,
            fn (array $template) => $template['context'] === $context,
        ));
    }

    /**
     * @return array<string, string>
     */
    public function defaultTemplateKeys(): array
    {
        $stored = $this->storedDefaultTemplateKeys();
        $templateKeys = collect($this->templates())->groupBy('context');
        $legacyDefaultKey = $this->sanitizeKey((string) SystemSetting::getValue(self::LEGACY_DEFAULT_TEMPLATE_KEY, ''));
        $resolved = [];

        foreach ($this->contexts() as $context) {
            $contextKey = $context['key'];
            $contextTemplates = $templateKeys->get($contextKey, collect())->pluck('key')->all();
            $candidate = $this->sanitizeKey((string) ($stored[$contextKey] ?? ''));

            if (! in_array($candidate, $contextTemplates, true) && in_array($legacyDefaultKey, $contextTemplates, true)) {
                $candidate = $legacyDefaultKey;
            }

            $resolved[$contextKey] = in_array($candidate, $contextTemplates, true)
                ? $candidate
                : ($contextTemplates[0] ?? '');
        }

        return $resolved;
    }

    public function defaultTemplateKey(string $context): string
    {
        return $this->defaultTemplateKeys()[$context] ?? '';
    }

    /**
     * @return array{key: string, title: string, content: string, context: string, show_on_invoice: bool}
     */
    public function defaultTemplate(string $context): array
    {
        $contextTemplates = $this->templates($context);

        if ($contextTemplates === []) {
            return [
                'key' => '',
                'title' => '',
                'content' => '',
                'context' => $context,
                'show_on_invoice' => false,
            ];
        }

        $template = collect($contextTemplates)->firstWhere('key', $this->defaultTemplateKey($context));

        return is_array($template) ? $template : $contextTemplates[0];
    }

    public function defaultTerms(string $context): string
    {
        $legacyTerms = $this->legacyDefaultTerms();

        if ($legacyTerms !== '' && ! $this->hasScopedTemplates() && ! $this->hasBranchScopedSettings()) {
            return $legacyTerms;
        }

        return $this->defaultTemplate($context)['content'];
    }

    public function shouldShowOnInvoice(string $context): bool
    {
        $legacyTerms = $this->legacyDefaultTerms();

        if ($legacyTerms !== '' && ! $this->hasScopedTemplates() && ! $this->hasBranchScopedSettings()) {
            return true;
        }

        $template = $this->defaultTemplate($context);

        return $this->normalize($template['content'] ?? '') !== ''
            && (bool) ($template['show_on_invoice'] ?? true);
    }

    /**
     * نصّ الشروط الذي يظهر على الفاتورة: شروط فرعها الحالية. أي تعديل على
     * شروط الفرع يظهر على فواتيره فور حفظه، وهو ما يطلبه المالك. ويبقى عمود
     * invoice_terms في الفاتورة كما حُفظ وقت الإصدار — لا يُمَس — فيظل سجلًّا
     * لِما صدر، ويمكن العودة إليه لاحقًا إن أُريد تجميد الفواتير القديمة.
     */
    public function termsForInvoice(Invoice $invoice): string
    {
        $service = $this->forInvoice($invoice);

        return $service->normalize($service->defaultTerms($service->contextForInvoice($invoice)));
    }

    public function shouldShowInvoiceTermsForInvoice(Invoice $invoice): bool
    {
        $service = $this->forInvoice($invoice);

        return $service->shouldShowOnInvoice($service->contextForInvoice($invoice));
    }

    public function contextForInvoice(Invoice $invoice): string
    {
        if (in_array($invoice->type, ['purchase', 'purchase_return'], true)) {
            return self::CONTEXT_PURCHASES;
        }

        return $this->salesContext((string) $invoice->sale_type);
    }

    /**
     * @param  array<int, array{key?: string|null, title?: string|null, content?: string|null, context?: string|null, show_on_invoice?: bool|string|int|null}>  $templates
     * @param  array<string, string|null>  $defaultTemplateKeys
     */
    public function setTemplates(array $templates, array $defaultTemplateKeys = []): void
    {
        $normalizedTemplates = collect($templates)
            ->map(function ($template, $index) {
                $context = $this->normalizeContext((string) ($template['context'] ?? ''));
                $title = trim((string) ($template['title'] ?? ''));
                $content = $this->normalize((string) ($template['content'] ?? ''));
                $key = $this->sanitizeKey((string) ($template['key'] ?? $title ?: 'template-' . ($index + 1)));
                $key = $key !== '' ? $key : 'template-' . ($index + 1);

                if ($context === '' || $title === '' || $content === '' || $key === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'title' => $title,
                    'content' => $content,
                    'context' => $context,
                    'show_on_invoice' => filter_var($template['show_on_invoice'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                ];
            })
            ->filter()
            ->unique(fn (array $template) => $template['context'] . '|' . $template['key'])
            ->values()
            ->all();

        $resolvedDefaultKeys = [];

        foreach ($this->contexts() as $context) {
            $contextKey = $context['key'];
            $contextTemplates = array_values(array_filter(
                $normalizedTemplates,
                fn (array $template) => $template['context'] === $contextKey,
            ));
            $candidate = $this->sanitizeKey((string) ($defaultTemplateKeys[$contextKey] ?? ''));

            $resolvedDefaultKeys[$contextKey] = collect($contextTemplates)
                ->pluck('key')
                ->contains($candidate)
                    ? $candidate
                    : ($contextTemplates[0]['key'] ?? '');
        }

        $branchId = $this->resolvedBranchId();

        if ($branchId !== null) {
            BranchInvoiceTermsSetting::query()->updateOrCreate(
                ['branch_id' => $branchId],
                [
                    'templates' => $normalizedTemplates,
                    'default_template_keys' => $resolvedDefaultKeys,
                ],
            );

            $this->branchSettings = null;
            $this->branchSettingsLoaded = false;

            return;
        }

        SystemSetting::putValue(self::TEMPLATES_KEY, json_encode($normalizedTemplates, JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue(self::DEFAULT_TEMPLATE_KEYS, json_encode($resolvedDefaultKeys, JSON_UNESCAPED_UNICODE));

        $salesDefaultKey = $resolvedDefaultKeys[self::CONTEXT_SALES_SIMPLIFIED] ?? '';
        $salesDefaultTemplate = collect($normalizedTemplates)->first(function (array $template) use ($salesDefaultKey) {
            return $template['context'] === self::CONTEXT_SALES_SIMPLIFIED && $template['key'] === $salesDefaultKey;
        });
        $salesDefaultContent = is_array($salesDefaultTemplate)
            ? $salesDefaultTemplate['content']
            : $this->defaultTemplate(self::CONTEXT_SALES_SIMPLIFIED)['content'];

        SystemSetting::putValue(self::LEGACY_DEFAULT_TEMPLATE_KEY, $salesDefaultKey);
        SystemSetting::putValue(self::SETTING_KEY, $salesDefaultContent);
    }

    public function resolveSnapshot(?string $terms, string $context, bool $fieldProvided = true): ?string
    {
        if (! $fieldProvided) {
            return $this->emptyToNull($this->defaultTerms($context));
        }

        return $this->emptyToNull($this->normalize($terms));
    }

    public function formatTermsForPrint(?string $terms, string $separator = ' / '): string
    {
        $lines = preg_split('/\r\n|\r|\n/', (string) $terms) ?: [];

        return collect($lines)
            ->map(fn ($line) => trim((string) $line))
            ->filter(fn (string $line) => $line !== '')
            ->implode($separator);
    }

    private function normalize(?string $terms): string
    {
        $terms = str_replace(["\r\n", "\r"], "\n", (string) $terms);

        return trim($terms);
    }

    private function emptyToNull(string $terms): ?string
    {
        return $terms === '' ? null : $terms;
    }

    private function sanitizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9\-_]+/', '-', $key) ?: '';

        return trim($key, '-_');
    }

    private function normalizeContext(string $context): string
    {
        $allowed = collect($this->contexts())->pluck('key')->all();

        return in_array($context, $allowed, true) ? $context : '';
    }

    /**
     * @return array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>
     */
    private function normalizedStoredTemplates(): array
    {
        $stored = $this->storedTemplates();

        if (! is_array($stored) || $stored === []) {
            return [];
        }

        return collect($stored)
            ->map(function ($template, $index) {
                $title = trim((string) ($template['title'] ?? ''));
                $content = $this->normalize((string) ($template['content'] ?? ''));
                $key = $this->sanitizeKey((string) ($template['key'] ?? $title ?: 'template-' . ($index + 1)));
                $key = $key !== '' ? $key : 'template-' . ($index + 1);
                $context = $this->normalizeContext((string) ($template['context'] ?? ''));

                if ($context === '') {
                    $context = $this->inferLegacyContext($template);
                }

                if ($title === '' || $content === '' || $key === '' || $context === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'title' => $title,
                    'content' => $content,
                    'context' => $context,
                    'show_on_invoice' => filter_var($template['show_on_invoice'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true,
                ];
            })
            ->filter()
            ->unique(fn (array $template) => $template['context'] . '|' . $template['key'])
            ->values()
            ->all();
    }

    /**
     * قوالب البداية لفرع لم يُضبط بعد: الشروط العامة القديمة إن وُجدت، وإلا
     * القوالب الجاهزة لكل صفحة فاتورة.
     *
     * @return array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>
     */
    private function fallbackTemplates(): array
    {
        $legacyTerms = $this->legacyDefaultTerms();
        $templates = [];

        foreach ($this->contexts() as $context) {
            if ($legacyTerms !== '') {
                $templates[] = [
                    'key' => $context['key'] . '-default',
                    'title' => 'الشروط الافتراضية',
                    'content' => $legacyTerms,
                    'context' => $context['key'],
                    'show_on_invoice' => true,
                ];

                continue;
            }

            $templates = array_merge($templates, array_values(array_filter(
                $this->defaultTemplates(),
                fn (array $template) => $template['context'] === $context['key'],
            )));
        }

        return array_values($templates);
    }

    /**
     * @return array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>
     */
    private function defaultTemplates(): array
    {
        return [
            [
                'key' => 'retail-exchange',
                'title' => 'استبدال وبيع تجزئة',
                'content' => "يحق الاستبدال خلال 3 أيام مع إبراز الفاتورة الأصلية.\nلا تُقبل القطع المعدلة أو التالفة بعد الاستلام.",
                'context' => self::CONTEXT_SALES_SIMPLIFIED,
                'show_on_invoice' => true,
            ],
            [
                'key' => 'company-sales',
                'title' => 'مبيعات شركات',
                'content' => "يتم اعتماد الفاتورة بحسب البيانات الضريبية المسجلة للعميل.\nأي تعديل لاحق يتطلب الرجوع إلى الفاتورة الأصلية.",
                'context' => self::CONTEXT_SALES_STANDARD,
                'show_on_invoice' => true,
            ],
            [
                'key' => 'purchase-supplier',
                'title' => 'شراء من مورد',
                'content' => "يتم اعتماد الوزن بعد الفحص والمطابقة.\nأي فروقات لاحقة تُسوّى بحسب نتيجة الفحص النهائي.",
                'context' => self::CONTEXT_PURCHASES,
                'show_on_invoice' => true,
            ],
        ];
    }

    private function hasScopedTemplates(): bool
    {
        $stored = $this->storedTemplates();

        if (! is_array($stored)) {
            return false;
        }

        return collect($stored)
            ->contains(fn ($template) => is_array($template) && $this->normalizeContext((string) ($template['context'] ?? '')) !== '');
    }

    private function legacyDefaultTerms(): string
    {
        return $this->normalize(SystemSetting::getValue(self::SETTING_KEY, ''));
    }

    /**
     * @return array<int|string, mixed>
     */
    private function storedTemplates(): array
    {
        $branchSettings = $this->branchSettings();

        if ($branchSettings instanceof BranchInvoiceTermsSetting) {
            return is_array($branchSettings->templates) ? $branchSettings->templates : [];
        }

        return $this->globalStoredTemplates();
    }

    /**
     * @return array<string, string>
     */
    private function storedDefaultTemplateKeys(): array
    {
        $branchSettings = $this->branchSettings();

        if ($branchSettings instanceof BranchInvoiceTermsSetting) {
            return is_array($branchSettings->default_template_keys) ? $branchSettings->default_template_keys : [];
        }

        return $this->globalStoredDefaultTemplateKeys();
    }

    private function hasBranchScopedSettings(): bool
    {
        return $this->branchSettings() instanceof BranchInvoiceTermsSetting;
    }

    private function branchSettings(): ?BranchInvoiceTermsSetting
    {
        if ($this->branchSettingsLoaded) {
            return $this->branchSettings;
        }

        $this->branchSettingsLoaded = true;
        $branchId = $this->resolvedBranchId();

        try {
            $this->branchSettings = $branchId === null
                ? null
                : BranchInvoiceTermsSetting::query()->firstWhere('branch_id', $branchId);
        } catch (QueryException) {
            // نسخة نُشر كودها قبل تشغيل المهاجرة: نعود إلى الإعداد العام بدل
            // إسقاط صفحة الطباعة، فتطبع الفاتورة نصّها المعتاد.
            $this->branchSettings = null;
        }

        return $this->branchSettings;
    }

    /**
     * الفرع المثبَّت إن وُجد، وإلا الفرع النشط في الجلسة، وإلا فرع المستخدم.
     */
    private function resolvedBranchId(): ?int
    {
        if ($this->branchId !== null) {
            return $this->branchId;
        }

        try {
            $sessionBranchId = session()->has(BranchContextService::SESSION_KEY)
                ? (int) session(BranchContextService::SESSION_KEY)
                : 0;
        } catch (\Throwable) {
            $sessionBranchId = 0;
        }

        if ($sessionBranchId > 0) {
            return $sessionBranchId;
        }

        $userBranchId = (int) (auth('admin-web')->user()?->branch_id ?? 0);

        return $userBranchId > 0 ? $userBranchId : null;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function globalStoredTemplates(): array
    {
        $stored = json_decode((string) SystemSetting::getValue(self::TEMPLATES_KEY, ''), true);

        return is_array($stored) ? $stored : [];
    }

    /**
     * @return array<string, string>
     */
    private function globalStoredDefaultTemplateKeys(): array
    {
        $stored = json_decode((string) SystemSetting::getValue(self::DEFAULT_TEMPLATE_KEYS, ''), true);

        return is_array($stored) ? $stored : [];
    }

    /**
     * @param  array<string, mixed>  $template
     */
    private function inferLegacyContext(array $template): string
    {
        $haystack = mb_strtolower(trim(implode(' ', [
            (string) ($template['key'] ?? ''),
            (string) ($template['title'] ?? ''),
            (string) ($template['content'] ?? ''),
        ])));

        foreach (['purchase', 'supplier', 'vendor', 'شراء', 'مشتريات', 'مورد'] as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return self::CONTEXT_PURCHASES;
            }
        }

        foreach (['company', 'corporate', 'business', 'شركة', 'شركات'] as $keyword) {
            if (str_contains($haystack, $keyword)) {
                return self::CONTEXT_SALES_STANDARD;
            }
        }

        return self::CONTEXT_SALES_SIMPLIFIED;
    }
}
