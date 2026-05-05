<?php

namespace App\Services\Invoices;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\UserInvoicePrintSetting;

class InvoicePrintSettingsService
{
    public const FORMAT_A4 = 'a4';
    public const FORMAT_A5 = 'a5';
    public const ORIENTATION_PORTRAIT = 'portrait';
    public const ORIENTATION_LANDSCAPE = 'landscape';

    public const FORMAT_KEY = 'invoice_print_format';
    public const SHOW_HEADER_KEY = 'invoice_print_show_header';
    public const SHOW_FOOTER_KEY = 'invoice_print_show_footer';
    public const TEMPLATE_KEY = 'invoice_print_template';
    public const ORIENTATION_KEY = 'invoice_print_orientation';

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
     * @return array<string, string>
     */
    public function availableOrientations(): array
    {
        return [
            self::ORIENTATION_PORTRAIT => 'طولي',
            self::ORIENTATION_LANDSCAPE => 'عرضي',
        ];
    }

    /**
     * @return array{format: string, show_header: bool, show_footer: bool, template: string, orientation: string}
     */
    public function currentSettings(bool $allowRequestOverride = true): array
    {
        $userSettings = $this->userSettings();
        $requestedFormat = $allowRequestOverride ? request()->query('paper') : null;
        $requestedOrientation = $allowRequestOverride ? request()->query('orientation') : null;
        $availableFormats = $this->availableFormats();
        $availableOrientations = array_keys($this->availableOrientations());
        $format = in_array($requestedFormat, $availableFormats, true)
            ? $requestedFormat
            : $this->storedFormat($userSettings);
        $showHeader = $this->storedShowHeader($userSettings);
        $showFooter = $this->storedShowFooter($userSettings);
        $template = $this->storedTemplate($userSettings);
        $storedOrientation = $this->storedOrientation($userSettings);
        $availableTemplates = array_keys($this->availableTemplates());
        $resolvedFormat = in_array($format, $availableFormats, true) ? $format : self::FORMAT_A4;
        $orientation = in_array($requestedOrientation, $availableOrientations, true)
            ? $requestedOrientation
            : $storedOrientation;
        $resolvedOrientation = in_array($orientation, $availableOrientations, true)
            ? $orientation
            : $this->defaultOrientation($resolvedFormat, $showHeader, $showFooter);

        return [
            'format' => $resolvedFormat,
            'show_header' => $showHeader,
            'show_footer' => $showFooter,
            'template' => in_array($template, $availableTemplates, true) ? $template : 'classic',
            'orientation' => $resolvedOrientation,
        ];
    }

    public function setSettings(string $format, bool $showHeader, bool $showFooter, string $template, string $orientation): void
    {
        $user = $this->currentUser();

        if ($user instanceof User) {
            UserInvoicePrintSetting::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'format' => $format,
                    'show_header' => $showHeader,
                    'show_footer' => $showFooter,
                    'template' => $template,
                    'orientation' => $orientation,
                ],
            );

            return;
        }

        SystemSetting::putValue(self::FORMAT_KEY, $format);
        SystemSetting::putValue(self::SHOW_HEADER_KEY, $showHeader ? '1' : '0');
        SystemSetting::putValue(self::SHOW_FOOTER_KEY, $showFooter ? '1' : '0');
        SystemSetting::putValue(self::TEMPLATE_KEY, $template);
        SystemSetting::putValue(self::ORIENTATION_KEY, $orientation);
    }

    public function setShowHeader(bool $value): void
    {
        $current = $this->currentSettings(false);
        $this->setSettings(
            $current['format'],
            $value,
            $current['show_footer'],
            $current['template'],
            $current['orientation'],
        );
    }

    public function setShowFooter(bool $value): void
    {
        $current = $this->currentSettings(false);
        $this->setSettings(
            $current['format'],
            $current['show_header'],
            $value,
            $current['template'],
            $current['orientation'],
        );
    }

    private function booleanSetting(string $key, bool $default): bool
    {
        $fallback = $default ? '1' : '0';

        return SystemSetting::getValue($key, $fallback) === '1';
    }

    private function defaultOrientation(string $format, bool $showHeader, bool $showFooter): string
    {
        if ($format === self::FORMAT_A5 && ! $showHeader && ! $showFooter) {
            return self::ORIENTATION_LANDSCAPE;
        }

        return self::ORIENTATION_PORTRAIT;
    }

    private function currentUser(): ?User
    {
        $user = auth('admin-web')->user();

        return $user instanceof User ? $user : null;
    }

    private function userSettings(): ?UserInvoicePrintSetting
    {
        $user = $this->currentUser();

        if (! $user instanceof User) {
            return null;
        }

        return $user->invoicePrintSettings()->first();
    }

    private function storedFormat(?UserInvoicePrintSetting $userSettings): string
    {
        return (string) ($userSettings?->format ?: SystemSetting::getValue(self::FORMAT_KEY, self::FORMAT_A4));
    }

    private function storedShowHeader(?UserInvoicePrintSetting $userSettings): bool
    {
        if ($userSettings instanceof UserInvoicePrintSetting) {
            return (bool) $userSettings->show_header;
        }

        return $this->booleanSetting(self::SHOW_HEADER_KEY, true);
    }

    private function storedShowFooter(?UserInvoicePrintSetting $userSettings): bool
    {
        if ($userSettings instanceof UserInvoicePrintSetting) {
            return (bool) $userSettings->show_footer;
        }

        return $this->booleanSetting(self::SHOW_FOOTER_KEY, true);
    }

    private function storedTemplate(?UserInvoicePrintSetting $userSettings): string
    {
        return (string) ($userSettings?->template ?: SystemSetting::getValue(self::TEMPLATE_KEY, 'classic'));
    }

    private function storedOrientation(?UserInvoicePrintSetting $userSettings): string
    {
        return (string) ($userSettings?->orientation ?: SystemSetting::getValue(self::ORIENTATION_KEY, ''));
    }
}
