<?php

namespace App\Services\Invoices;

use App\Models\Invoice;
use App\Models\SystemSetting;

class InvoiceTermsService
{
    public const SETTING_KEY = 'default_invoice_terms';
    public const TEMPLATES_KEY = 'invoice_terms_templates';
    public const LEGACY_DEFAULT_TEMPLATE_KEY = 'default_invoice_terms_template_key';
    public const DEFAULT_TEMPLATE_KEYS = 'default_invoice_terms_template_keys';

    public const CONTEXT_SALES_SIMPLIFIED = 'sales_simplified';
    public const CONTEXT_SALES_STANDARD = 'sales_standard';
    public const CONTEXT_PURCHASES = 'purchases';

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

        if ($templates === []) {
            $templates = $this->defaultTemplates();
        }

        $templates = $this->mergeFallbackTemplates($templates);

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
        $stored = json_decode((string) SystemSetting::getValue(self::DEFAULT_TEMPLATE_KEYS, ''), true);
        $stored = is_array($stored) ? $stored : [];
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
        $defaultKey = $this->defaultTemplateKey($context);
        $template = collect($this->templates($context))->firstWhere('key', $defaultKey);

        if (is_array($template)) {
            return $template;
        }

        return collect($this->defaultTemplates())
            ->firstWhere('context', $context) ?? $this->defaultTemplates()[0];
    }

    public function defaultTerms(string $context): string
    {
        $legacyTerms = $this->legacyDefaultTerms();

        if ($legacyTerms !== '' && ! $this->hasScopedTemplates()) {
            return $legacyTerms;
        }

        return $this->defaultTemplate($context)['content'];
    }

    public function shouldShowOnInvoice(string $context): bool
    {
        $legacyTerms = $this->legacyDefaultTerms();

        if ($legacyTerms !== '' && ! $this->hasScopedTemplates()) {
            return true;
        }

        return (bool) ($this->defaultTemplate($context)['show_on_invoice'] ?? true);
    }

    public function shouldShowSnapshotOnInvoice(?string $terms, string $context): bool
    {
        $normalizedTerms = $this->normalize($terms);

        if ($normalizedTerms === '') {
            return false;
        }

        $legacyTerms = $this->legacyDefaultTerms();

        if ($legacyTerms !== '' && ! $this->hasScopedTemplates()) {
            return true;
        }

        $matchedTemplate = collect($this->templates($context))->first(function (array $template) use ($normalizedTerms) {
            return $this->normalize($template['content'] ?? '') === $normalizedTerms;
        });

        if (is_array($matchedTemplate)) {
            return (bool) ($matchedTemplate['show_on_invoice'] ?? true);
        }

        return true;
    }

    public function shouldShowInvoiceTermsForInvoice(Invoice $invoice): bool
    {
        return $this->shouldShowSnapshotOnInvoice(
            $invoice->invoice_terms,
            $this->contextForInvoice($invoice),
        );
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

        $normalizedTemplates = $this->mergeFallbackTemplates($normalizedTemplates);
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
        $stored = json_decode((string) SystemSetting::getValue(self::TEMPLATES_KEY, ''), true);

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
     * @param  array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>  $templates
     * @return array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>
     */
    private function mergeFallbackTemplates(array $templates): array
    {
        $existingContexts = collect($templates)->pluck('context')->unique()->all();
        $fallbackTemplates = [];

        foreach ($this->contexts() as $context) {
            if (in_array($context['key'], $existingContexts, true)) {
                continue;
            }

            $fallbackTemplates = array_merge($fallbackTemplates, $this->fallbackTemplatesForContext($context['key']));
        }

        return array_values(array_merge($templates, $fallbackTemplates));
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

    /**
     * @return array<int, array{key: string, title: string, content: string, context: string, show_on_invoice: bool}>
     */
    private function fallbackTemplatesForContext(string $context): array
    {
        $legacyTerms = $this->legacyDefaultTerms();

        if ($legacyTerms !== '') {
            return [[
                'key' => $context . '-default',
                'title' => 'الشروط الافتراضية',
                'content' => $legacyTerms,
                'context' => $context,
                'show_on_invoice' => true,
            ]];
        }

        return array_values(array_filter(
            $this->defaultTemplates(),
            fn (array $template) => $template['context'] === $context,
        ));
    }

    private function hasScopedTemplates(): bool
    {
        $stored = json_decode((string) SystemSetting::getValue(self::TEMPLATES_KEY, ''), true);

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
