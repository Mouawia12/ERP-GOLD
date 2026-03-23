<?php

namespace App\Services\Invoices;

use App\Models\SystemSetting;

class InvoiceTermsService
{
    public const SETTING_KEY = 'default_invoice_terms';
    public const TEMPLATES_KEY = 'invoice_terms_templates';
    public const DEFAULT_TEMPLATE_KEY = 'default_invoice_terms_template_key';

    /**
     * @return array<int, array{key: string, title: string, content: string}>
     */
    public function templates(): array
    {
        $stored = json_decode((string) SystemSetting::getValue(self::TEMPLATES_KEY, ''), true);

        if (! is_array($stored) || empty($stored)) {
            return $this->defaultTemplates();
        }

        $templates = collect($stored)
            ->map(function ($template, $index) {
                $title = trim((string) ($template['title'] ?? ''));
                $content = $this->normalize((string) ($template['content'] ?? ''));
                $key = $this->sanitizeKey((string) ($template['key'] ?? $title ?: 'template-' . ($index + 1)));
                $key = $key !== '' ? $key : 'template-' . ($index + 1);

                if ($title === '' || $content === '' || $key === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'title' => $title,
                    'content' => $content,
                ];
            })
            ->filter()
            ->unique('key')
            ->values()
            ->all();

        return $templates !== [] ? $templates : $this->defaultTemplates();
    }

    public function defaultTemplateKey(): string
    {
        $templates = $this->templates();
        $defaultKey = $this->sanitizeKey((string) SystemSetting::getValue(self::DEFAULT_TEMPLATE_KEY, ''));
        $keys = collect($templates)->pluck('key')->all();

        return in_array($defaultKey, $keys, true) ? $defaultKey : $templates[0]['key'];
    }

    /**
     * @return array{key: string, title: string, content: string}
     */
    public function defaultTemplate(): array
    {
        $defaultKey = $this->defaultTemplateKey();

        return collect($this->templates())
            ->firstWhere('key', $defaultKey) ?? $this->defaultTemplates()[0];
    }

    public function defaultTerms(): string
    {
        $storedDefault = $this->normalize(SystemSetting::getValue(self::SETTING_KEY, ''));

        if ($storedDefault !== '') {
            return $storedDefault;
        }

        return $this->defaultTemplate()['content'];
    }

    public function setDefaultTerms(?string $terms): void
    {
        SystemSetting::putValue(self::SETTING_KEY, $this->normalize($terms));
    }

    /**
     * @param  array<int, array{key?: string|null, title?: string|null, content?: string|null}>  $templates
     */
    public function setTemplates(array $templates, ?string $defaultTemplateKey = null): void
    {
        $normalizedTemplates = collect($templates)
            ->map(function ($template, $index) {
                $title = trim((string) ($template['title'] ?? ''));
                $content = $this->normalize((string) ($template['content'] ?? ''));
                $key = $this->sanitizeKey((string) ($template['key'] ?? $title ?: 'template-' . ($index + 1)));
                $key = $key !== '' ? $key : 'template-' . ($index + 1);

                if ($title === '' || $content === '' || $key === '') {
                    return null;
                }

                return [
                    'key' => $key,
                    'title' => $title,
                    'content' => $content,
                ];
            })
            ->filter()
            ->unique('key')
            ->values();

        if ($normalizedTemplates->isEmpty()) {
            $normalizedTemplates = collect($this->defaultTemplates());
        }

        $defaultKey = $this->sanitizeKey((string) $defaultTemplateKey);
        if (! $normalizedTemplates->pluck('key')->contains($defaultKey)) {
            $defaultKey = $normalizedTemplates->first()['key'];
        }

        SystemSetting::putValue(self::TEMPLATES_KEY, json_encode($normalizedTemplates->all(), JSON_UNESCAPED_UNICODE));
        SystemSetting::putValue(self::DEFAULT_TEMPLATE_KEY, $defaultKey);
        SystemSetting::putValue(
            self::SETTING_KEY,
            (string) $normalizedTemplates->firstWhere('key', $defaultKey)['content']
        );
    }

    public function templateContent(?string $templateKey): ?string
    {
        if (blank($templateKey)) {
            return null;
        }

        $template = collect($this->templates())->firstWhere('key', $this->sanitizeKey((string) $templateKey));

        return $template['content'] ?? null;
    }

    public function resolveSnapshot(?string $terms, bool $fieldProvided = true): ?string
    {
        if (! $fieldProvided) {
            return $this->emptyToNull($this->defaultTerms());
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

    /**
     * @return array<int, array{key: string, title: string, content: string}>
     */
    private function defaultTemplates(): array
    {
        return [
            [
                'key' => 'retail-exchange',
                'title' => 'استبدال وبيع تجزئة',
                'content' => "يحق الاستبدال خلال 3 أيام مع إبراز الفاتورة الأصلية.\nلا تُقبل القطع المعدلة أو التالفة بعد الاستلام.",
            ],
            [
                'key' => 'purchase-supplier',
                'title' => 'شراء من مورد',
                'content' => "يتم اعتماد الوزن بعد الفحص والمطابقة.\nأي فروقات لاحقة تُسوّى بحسب نتيجة الفحص النهائي.",
            ],
            [
                'key' => 'cash-party',
                'title' => 'عميل نقدي',
                'content' => "تمت المعاملة نقدًا وفق البيانات المثبتة في الفاتورة.\nيلتزم العميل بمراجعة الوزن والقيمة قبل مغادرة الفرع.",
            ],
        ];
    }

    private function sanitizeKey(string $key): string
    {
        $key = strtolower(trim($key));
        $key = preg_replace('/[^a-z0-9\-_]+/', '-', $key) ?: '';

        return trim($key, '-_');
    }
}
