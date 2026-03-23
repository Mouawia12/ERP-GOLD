<?php

namespace App\Services\Invoices;

use App\Models\SystemSetting;

class InvoicePrintSettingsService
{
    public const FORMAT_A4 = 'a4';
    public const FORMAT_A5 = 'a5';

    public const FORMAT_KEY = 'invoice_print_format';
    public const SHOW_HEADER_KEY = 'invoice_print_show_header';
    public const SHOW_FOOTER_KEY = 'invoice_print_show_footer';
    public const TEMPLATE_KEY = 'invoice_print_template';

    /**
     * @return array<int, string>
     */
    public function availableFormats(): array
    {
        return [
            self::FORMAT_A4,
            self::FORMAT_A5,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function availableTemplates(): array
    {
        return [
            'classic' => 'كلاسيكي',
            'compact' => 'مضغوط',
            'modern' => 'حديث',
        ];
    }

    /**
     * @return array{format: string, show_header: bool, show_footer: bool, template: string}
     */
    public function currentSettings(): array
    {
        $format = SystemSetting::getValue(self::FORMAT_KEY, self::FORMAT_A4);
        $template = SystemSetting::getValue(self::TEMPLATE_KEY, 'classic');
        $availableTemplates = array_keys($this->availableTemplates());

        return [
            'format' => in_array($format, $this->availableFormats(), true) ? $format : self::FORMAT_A4,
            'show_header' => $this->booleanSetting(self::SHOW_HEADER_KEY, true),
            'show_footer' => $this->booleanSetting(self::SHOW_FOOTER_KEY, true),
            'template' => in_array($template, $availableTemplates, true) ? $template : 'classic',
        ];
    }

    public function setSettings(string $format, bool $showHeader, bool $showFooter, string $template): void
    {
        SystemSetting::putValue(self::FORMAT_KEY, $format);
        SystemSetting::putValue(self::SHOW_HEADER_KEY, $showHeader ? '1' : '0');
        SystemSetting::putValue(self::SHOW_FOOTER_KEY, $showFooter ? '1' : '0');
        SystemSetting::putValue(self::TEMPLATE_KEY, $template);
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $fallback = $default ? '1' : '0';

        return SystemSetting::getValue($key, $fallback) === '1';
    }
}
