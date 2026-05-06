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
     * Default dimensions for each paper format (millimetres).
     * These are the safe values that produce a clean single-page print
     * on common Saudi printers without empty trailing pages.
     */
    public const DIMENSION_DEFAULTS = [
        'a4' => [
            'margin_top' => 8.0,
            'margin_right' => 8.0,
            'margin_bottom' => 8.0,
            'margin_left' => 8.0,
            'header_height' => 0.0,
            'footer_height' => 0.0,
            'content_offset_top' => 0.0,
        ],
        'a5' => [
            'margin_top' => 5.0,
            'margin_right' => 5.0,
            'margin_bottom' => 5.0,
            'margin_left' => 5.0,
            'header_height' => 0.0,
            'footer_height' => 0.0,
            'content_offset_top' => 0.0,
        ],
        'font_scale' => 1.0,
    ];

    public const DIMENSION_LIMITS = [
        'margin' => ['min' => 0.0, 'max' => 30.0],
        'header_height' => ['min' => 0.0, 'max' => 80.0],
        'footer_height' => ['min' => 0.0, 'max' => 60.0],
        'content_offset_top' => ['min' => 0.0, 'max' => 80.0],
        'font_scale' => ['min' => 0.7, 'max' => 1.6],
    ];

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
     * @return array{format: string, show_header: bool, show_footer: bool, template: string, orientation: string, dimensions: array<string, array<string, float>|float>}
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
            'dimensions' => $this->resolvedDimensions($userSettings),
        ];
    }

    /**
     * @return array{a4: array<string, float>, a5: array<string, float>, font_scale: float}
     */
    public function resolvedDimensions(?UserInvoicePrintSetting $userSettings = null): array
    {
        $userSettings ??= $this->userSettings();

        return [
            'a4' => $this->formatDimensions('a4', $userSettings),
            'a5' => $this->formatDimensions('a5', $userSettings),
            'font_scale' => $this->clampDimension(
                'font_scale',
                $userSettings?->font_scale ?? self::DIMENSION_DEFAULTS['font_scale']
            ),
        ];
    }

    /**
     * @return array<string, float>
     */
    private function formatDimensions(string $format, ?UserInvoicePrintSetting $userSettings): array
    {
        $defaults = self::DIMENSION_DEFAULTS[$format] ?? self::DIMENSION_DEFAULTS['a4'];
        $prefix = $format.'_';
        $columnFor = static fn (string $key) => $prefix.$key;

        $resolve = function (string $key) use ($defaults, $userSettings, $columnFor) {
            $value = $userSettings?->{$columnFor($key)};

            return $this->clampDimension($key, $value ?? $defaults[$key]);
        };

        return [
            'margin_top' => $resolve('margin_top'),
            'margin_right' => $resolve('margin_right'),
            'margin_bottom' => $resolve('margin_bottom'),
            'margin_left' => $resolve('margin_left'),
            'header_height' => $resolve('header_height'),
            'footer_height' => $resolve('footer_height'),
            'content_offset_top' => $resolve('content_offset_top'),
        ];
    }

    private function clampDimension(string $key, float|int|string|null $value): float
    {
        $float = (float) ($value ?? 0);
        $bucket = match ($key) {
            'margin_top', 'margin_right', 'margin_bottom', 'margin_left' => 'margin',
            default => $key,
        };
        $limits = self::DIMENSION_LIMITS[$bucket] ?? null;

        if (! $limits) {
            return $float;
        }

        return max($limits['min'], min($limits['max'], $float));
    }

    /**
     * @param  array<string, mixed>|null  $dimensions
     */
    public function setSettings(
        string $format,
        bool $showHeader,
        bool $showFooter,
        string $template,
        string $orientation,
        ?array $dimensions = null
    ): void {
        $user = $this->currentUser();
        $payload = [
            'format' => $format,
            'show_header' => $showHeader,
            'show_footer' => $showFooter,
            'template' => $template,
            'orientation' => $orientation,
        ];

        if ($dimensions !== null) {
            $payload = array_merge($payload, $this->dimensionColumns($dimensions));
        }

        if ($user instanceof User) {
            UserInvoicePrintSetting::query()->updateOrCreate(
                ['user_id' => $user->id],
                $payload,
            );

            return;
        }

        SystemSetting::putValue(self::FORMAT_KEY, $format);
        SystemSetting::putValue(self::SHOW_HEADER_KEY, $showHeader ? '1' : '0');
        SystemSetting::putValue(self::SHOW_FOOTER_KEY, $showFooter ? '1' : '0');
        SystemSetting::putValue(self::TEMPLATE_KEY, $template);
        SystemSetting::putValue(self::ORIENTATION_KEY, $orientation);
    }

    /**
     * @param  array<string, mixed>  $dimensions
     * @return array<string, float>
     */
    private function dimensionColumns(array $dimensions): array
    {
        $columns = [];

        foreach (['a4', 'a5'] as $format) {
            $section = is_array($dimensions[$format] ?? null) ? $dimensions[$format] : [];
            foreach (['margin_top', 'margin_right', 'margin_bottom', 'margin_left',
                'header_height', 'footer_height', 'content_offset_top'] as $key) {
                if (array_key_exists($key, $section) && $section[$key] !== null && $section[$key] !== '') {
                    $columns[$format.'_'.$key] = $this->clampDimension($key, $section[$key]);
                }
            }
        }

        if (array_key_exists('font_scale', $dimensions) && $dimensions['font_scale'] !== null && $dimensions['font_scale'] !== '') {
            $columns['font_scale'] = $this->clampDimension('font_scale', $dimensions['font_scale']);
        }

        return $columns;
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
