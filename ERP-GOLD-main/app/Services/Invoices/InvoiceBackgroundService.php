<?php

namespace App\Services\Invoices;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class InvoiceBackgroundService
{
    public const SETTING_PATH = 'invoice_bg_image_path';

    public const SETTING_SCALE = 'invoice_bg_scale';

    public const SETTING_ENABLED = 'invoice_bg_enabled';

    public const SETTING_PAPER_SIZE = 'invoice_bg_paper_size';    // 'a4' | 'a5'

    public const SETTING_CONTENT_TOP = 'invoice_bg_content_top';   // mm

    public const SETTING_CONTENT_BOTTOM = 'invoice_bg_content_bottom'; // mm

    public const SETTING_CONTENT_WIDTH = 'invoice_bg_content_width'; // %, 50..100

    public const SETTING_CONTENT_SCALE = 'invoice_bg_content_scale'; // 0.5..1.5

    public const SETTING_FONT_SCALE = 'invoice_bg_font_scale'; // 0.7..1.4

    public const SETTING_OFFSET_X = 'invoice_bg_offset_x';      // %, -50..50

    public const SETTING_OFFSET_Y = 'invoice_bg_offset_y';      // %, -50..50

    public const SETTING_HIDE_HEADER = 'invoice_bg_hide_header';   // '1' | '0'

    public const SETTING_HIDE_FOOTER = 'invoice_bg_hide_footer';   // '1' | '0'

    public const SETTING_IMAGE_WIDTH = 'invoice_bg_image_width';

    public const SETTING_IMAGE_HEIGHT = 'invoice_bg_image_height';

    public const SETTING_IMAGE_MIME = 'invoice_bg_image_mime';

    public const SETTING_RENDER_MODE = 'invoice_bg_render_mode';   // 'full_page' | 'wide_strip' | 'partial'

    public const SETTING_ORIENTATION = 'invoice_bg_orientation';   // 'portrait' | 'landscape'

    /**
     * Invoice context types — used to scope settings per-document so each
     * document type can have its own alignment on the same letterhead paper.
     */
    public const TYPE_SALES_STANDARD = 'sales_standard';
    public const TYPE_SALES_SIMPLIFIED = 'sales_simplified';
    public const TYPE_SALES_RETURN_STANDARD = 'sales_return_standard';
    public const TYPE_SALES_RETURN_SIMPLIFIED = 'sales_return_simplified';
    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_PURCHASE_RETURN = 'purchase_return';

    public const FORMAT_A4 = 'a4';
    public const FORMAT_A5 = 'a5';

    /**
     * Settings whose value depends on (invoice_type, format) — these get the
     * full scope chain (branch:type:format → branch → global).
     * Other settings (image path, mime, dimensions, render mode, enabled)
     * stay at branch level only — one letterhead per branch.
     */
    private const CONTEXT_SCOPED_KEYS = [
        self::SETTING_SCALE,
        self::SETTING_PAPER_SIZE,
        self::SETTING_ORIENTATION,
        self::SETTING_CONTENT_TOP,
        self::SETTING_CONTENT_BOTTOM,
        self::SETTING_CONTENT_WIDTH,
        self::SETTING_CONTENT_SCALE,
        self::SETTING_FONT_SCALE,
        self::SETTING_OFFSET_X,
        self::SETTING_OFFSET_Y,
        self::SETTING_HIDE_HEADER,
        self::SETTING_HIDE_FOOTER,
    ];

    private ?string $lastConversionError = null;

    private ?int $branchId = null;

    private ?string $invoiceType = null;

    private ?string $format = null;

    public function forBranch(?int $branchId): self
    {
        $service = clone $this;
        $service->branchId = $branchId && $branchId > 0 ? $branchId : null;

        return $service;
    }

    public function forContext(?string $invoiceType, ?string $format = null): self
    {
        $service = clone $this;
        $service->invoiceType = self::normalizeInvoiceType($invoiceType);
        $service->format = self::normalizeFormat($format);

        return $service;
    }

    public static function availableInvoiceTypes(): array
    {
        return [
            self::TYPE_SALES_STANDARD => 'فاتورة مبيعات (ضريبية)',
            self::TYPE_SALES_SIMPLIFIED => 'فاتورة مبيعات (مبسطة)',
            self::TYPE_SALES_RETURN_STANDARD => 'مرتجع مبيعات (ضريبية)',
            self::TYPE_SALES_RETURN_SIMPLIFIED => 'مرتجع مبيعات (مبسطة)',
            self::TYPE_PURCHASE => 'فاتورة مشتريات',
            self::TYPE_PURCHASE_RETURN => 'مرتجع مشتريات',
        ];
    }

    public static function detectInvoiceTypeFromInvoice(?\App\Models\Invoice $invoice): ?string
    {
        if (! $invoice) {
            return null;
        }

        $type = (string) $invoice->type;
        $saleType = (string) ($invoice->sale_type ?: 'standard');

        return match ($type) {
            'sale' => $saleType === 'simplified'
                ? self::TYPE_SALES_SIMPLIFIED
                : self::TYPE_SALES_STANDARD,
            'sale_return' => $saleType === 'simplified'
                ? self::TYPE_SALES_RETURN_SIMPLIFIED
                : self::TYPE_SALES_RETURN_STANDARD,
            'purchase' => self::TYPE_PURCHASE,
            'purchase_return' => self::TYPE_PURCHASE_RETURN,
            default => null,
        };
    }

    public static function normalizeInvoiceType(?string $type): ?string
    {
        if ($type === null || $type === '') {
            return null;
        }

        return array_key_exists($type, self::availableInvoiceTypes()) ? $type : null;
    }

    public static function normalizeFormat(?string $format): ?string
    {
        if ($format === null || $format === '') {
            return null;
        }

        return in_array($format, [self::FORMAT_A4, self::FORMAT_A5], true) ? $format : null;
    }

    public function currentInvoiceType(): ?string
    {
        return $this->invoiceType;
    }

    public function currentFormat(): ?string
    {
        return $this->format;
    }

    /* ──────────────────── image URL ──────────────────── */

    public function currentImageUrl(): ?string
    {
        if (! $this->isEnabled()) {
            return null;
        }

        return $this->rawImageUrl();
    }

    public function rawImageUrl(): ?string
    {
        $path = $this->settingValue(self::SETTING_PATH);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $url = '/storage/'.ltrim($path, '/');

        try {
            $version = Storage::disk('public')->lastModified($path);
        } catch (\Throwable) {
            return $url;
        }

        return $url.'?v='.$version;
    }

    /* ──────────────────── enabled ──────────────────── */

    public function isEnabled(): bool
    {
        return $this->settingValue(self::SETTING_ENABLED, '0') === '1';
    }

    public function setEnabled(bool $enabled): void
    {
        $this->putSettingValue(self::SETTING_ENABLED, $enabled ? '1' : '0');
    }

    public function hasTemplate(): bool
    {
        $path = $this->settingValue(self::SETTING_PATH);

        return (bool) ($path && Storage::disk('public')->exists($path));
    }

    /* ──────────────────── scale ──────────────────── */

    public function currentScale(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_scale');
            if ($v !== null && is_numeric($v)) {
                $s = (float) $v;
                if ($s >= 0.3 && $s <= 2.0) {
                    return round($s, 2);
                }
            }
        }

        $s = (float) $this->settingValue(self::SETTING_SCALE, '1.00');

        return ($s >= 0.3 && $s <= 2.0) ? round($s, 2) : 1.0;
    }

    public function setScale(float $scale): void
    {
        $this->putSettingValue(self::SETTING_SCALE, number_format(max(0.3, min(2.0, $scale)), 2));
    }

    /* ──────────────────── paper size ──────────────────── */

    public function currentPaperSize(bool $allowRequestOverride = true): string
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_paper_size');
            if (in_array($v, ['a4', 'a5'], true)) {
                return $v;
            }
        }

        $val = $this->settingValue(self::SETTING_PAPER_SIZE, 'a4');

        return in_array($val, ['a4', 'a5'], true) ? $val : 'a4';
    }

    public function setPaperSize(string $size): void
    {
        $this->putSettingValue(self::SETTING_PAPER_SIZE, in_array($size, ['a4', 'a5'], true) ? $size : 'a4');
    }

    public function currentPaperOrientation(bool $allowRequestOverride = true): string
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_paper_orientation');
            if (in_array($v, ['portrait', 'landscape'], true)) {
                return $v;
            }
        }

        $this->ensureImageInfo();

        $val = $this->settingValue(self::SETTING_ORIENTATION, 'portrait');

        return in_array($val, ['portrait', 'landscape'], true) ? $val : 'portrait';
    }

    public function setPaperOrientation(string $orientation): void
    {
        $this->putSettingValue(
            self::SETTING_ORIENTATION,
            in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'portrait'
        );
    }

    /* ──────────────────── content top ──────────────────── */

    public function currentContentTop(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_content_top');
            if ($v !== null && is_numeric($v)) {
                $mm = (float) $v;
                if ($mm >= 0 && $mm <= 200) {
                    return round($mm, 1);
                }
            }
        }

        return max(0.0, min(200.0, (float) $this->settingValue(self::SETTING_CONTENT_TOP, '0')));
    }

    public function setContentTop(float $mm): void
    {
        $this->putSettingValue(self::SETTING_CONTENT_TOP, number_format(max(0.0, min(200.0, $mm)), 1));
    }

    /* ──────────────────── content bottom ──────────────────── */

    public function currentContentBottom(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_content_bottom');
            if ($v !== null && is_numeric($v)) {
                $mm = (float) $v;
                if ($mm >= 0 && $mm <= 200) {
                    return round($mm, 1);
                }
            }
        }

        return max(0.0, min(200.0, (float) $this->settingValue(self::SETTING_CONTENT_BOTTOM, '0')));
    }

    public function setContentBottom(float $mm): void
    {
        $this->putSettingValue(self::SETTING_CONTENT_BOTTOM, number_format(max(0.0, min(200.0, $mm)), 1));
    }

    /* ──────────────────── content width ──────────────────── */

    public function currentContentWidth(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_content_width');
            if ($v !== null && is_numeric($v)) {
                $pct = (float) $v;
                if ($pct >= 50 && $pct <= 100) {
                    return round($pct, 1);
                }
            }
        }

        $val = (float) $this->settingValue(self::SETTING_CONTENT_WIDTH, '100');

        return ($val >= 50 && $val <= 100) ? round($val, 1) : 100.0;
    }

    public function setContentWidth(float $pct): void
    {
        $this->putSettingValue(self::SETTING_CONTENT_WIDTH, number_format(max(50.0, min(100.0, $pct)), 1));
    }

    public function currentContentScale(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_content_scale');
            if ($v !== null && is_numeric($v)) {
                $scale = (float) $v;
                if ($scale >= 0.5 && $scale <= 1.5) {
                    return round($scale, 2);
                }
            }
        }

        $val = (float) $this->settingValue(self::SETTING_CONTENT_SCALE, '1.00');

        return ($val >= 0.5 && $val <= 1.5) ? round($val, 2) : 1.0;
    }

    public function setContentScale(float $scale): void
    {
        $this->putSettingValue(self::SETTING_CONTENT_SCALE, number_format(max(0.5, min(1.5, $scale)), 2));
    }

    public function currentFontScale(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_font_scale');
            if ($v !== null && is_numeric($v)) {
                $scale = (float) $v;
                if ($scale >= 0.7 && $scale <= 1.4) {
                    return round($scale, 2);
                }
            }
        }

        $val = (float) $this->settingValue(self::SETTING_FONT_SCALE, '1.00');

        return ($val >= 0.7 && $val <= 1.4) ? round($val, 2) : 1.0;
    }

    public function setFontScale(float $scale): void
    {
        $this->putSettingValue(self::SETTING_FONT_SCALE, number_format(max(0.7, min(1.4, $scale)), 2));
    }

    /* ──────────────────── offset X ──────────────────── */

    public function currentOffsetX(bool $allowRequestOverride = true): float
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_offset_x');
            if ($v !== null && is_numeric($v)) {
                $pct = (float) $v;
                if ($pct >= -50 && $pct <= 50) {
                    return round($pct, 1);
                }
            }
        }

        return max(-50.0, min(50.0, (float) $this->settingValue(self::SETTING_OFFSET_X, '0')));
    }

    public function setOffsetX(float $pct): void
    {
        $this->putSettingValue(self::SETTING_OFFSET_X, number_format(max(-50.0, min(50.0, $pct)), 1));
    }

    /* ──────────────────── offset Y ──────────────────── */

    public function currentOffsetY(): float
    {
        return max(-50.0, min(50.0, (float) $this->settingValue(self::SETTING_OFFSET_Y, '0')));
    }

    public function setOffsetY(float $pct): void
    {
        $this->putSettingValue(self::SETTING_OFFSET_Y, number_format(max(-50.0, min(50.0, $pct)), 1));
    }

    /* ──────────────────── header / footer visibility ──────────────────── */

    public function isHideHeader(bool $allowRequestOverride = true): bool
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_hide_header');
            if ($v !== null) {
                return $v === '1';
            }
        }

        return $this->settingValue(self::SETTING_HIDE_HEADER, '0') === '1';
    }

    public function setHideHeader(bool $hide): void
    {
        $this->putSettingValue(self::SETTING_HIDE_HEADER, $hide ? '1' : '0');
    }

    public function isHideFooter(bool $allowRequestOverride = true): bool
    {
        if ($allowRequestOverride) {
            $v = request()->query('bg_hide_footer');
            if ($v !== null) {
                return $v === '1';
            }
        }

        return $this->settingValue(
            self::SETTING_HIDE_FOOTER,
            $this->settingValue(self::SETTING_HIDE_HEADER, '0')
        ) === '1';
    }

    public function setHideFooter(bool $hide): void
    {
        $this->putSettingValue(self::SETTING_HIDE_FOOTER, $hide ? '1' : '0');
    }

    public function currentRenderMode(): string
    {
        $this->ensureImageInfo();

        $mode = $this->settingValue(self::SETTING_RENDER_MODE, 'full_page');

        return in_array($mode, ['full_page', 'wide_strip', 'partial'], true) ? $mode : 'full_page';
    }

    /**
     * @return array{width: int, height: int, mime: string, mode: string, orientation: string}
     */
    public function currentImageInfo(): array
    {
        $this->ensureImageInfo();

        return [
            'width' => (int) $this->settingValue(self::SETTING_IMAGE_WIDTH, '0'),
            'height' => (int) $this->settingValue(self::SETTING_IMAGE_HEIGHT, '0'),
            'mime' => (string) $this->settingValue(self::SETTING_IMAGE_MIME, ''),
            'mode' => $this->currentRenderMode(),
            'orientation' => $this->currentPaperOrientation(false),
        ];
    }

    /* ──────────────────── upload / delete ──────────────────── */

    /**
     * @throws \RuntimeException
     */
    public function upload(UploadedFile $file): void
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $isPdf = $extension === 'pdf';

        $oldPath = $this->settingValue(self::SETTING_PATH);
        $oldPathIsOwnedByCurrentScope = $this->hasStoredSetting(self::SETTING_PATH);

        if ($isPdf) {
            $imagePath = $this->convertPdfToImage($file);

            if ($imagePath === null) {
                throw new \RuntimeException(
                    $this->lastConversionError
                        ?: 'تعذر تحويل ملف PDF إلى صورة. تأكد من تثبيت Imagick وGhostscript على الخادم، أو ارفع الملف بصيغة صورة (PNG/JPG).'
                );
            }

            $this->putSettingValue(self::SETTING_PATH, $imagePath);
            $this->storeImageInfo($imagePath, 'application/pdf');
        } else {
            $newPath = (string) $file->store('invoice-backgrounds', 'public');
            $this->putSettingValue(self::SETTING_PATH, $newPath);
            $this->storeImageInfo($newPath, (string) $file->getMimeType());
        }

        if ($oldPathIsOwnedByCurrentScope && $oldPath && $oldPath !== $this->settingValue(self::SETTING_PATH) && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $this->putSettingValue(self::SETTING_ENABLED, '1');

        if (! $oldPath) {
            $this->putSettingValue(self::SETTING_HIDE_HEADER, '1');
            $this->putSettingValue(self::SETTING_HIDE_FOOTER, '1');
            $this->putSettingValue(self::SETTING_CONTENT_TOP, '50.0');
            $this->putSettingValue(self::SETTING_CONTENT_BOTTOM, '20.0');
            $this->putSettingValue(self::SETTING_CONTENT_WIDTH, '100.0');
            $this->putSettingValue(self::SETTING_CONTENT_SCALE, '1.00');
            $this->putSettingValue(self::SETTING_FONT_SCALE, '1.00');
        }
    }

    public function delete(): void
    {
        $path = $this->settingValue(self::SETTING_PATH);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $this->putSettingValue(self::SETTING_PATH, '');
        $this->putSettingValue(self::SETTING_ENABLED, '0');
        $this->putSettingValue(self::SETTING_HIDE_HEADER, '0');
        $this->putSettingValue(self::SETTING_HIDE_FOOTER, '0');
        $this->putSettingValue(self::SETTING_IMAGE_WIDTH, '0');
        $this->putSettingValue(self::SETTING_IMAGE_HEIGHT, '0');
        $this->putSettingValue(self::SETTING_IMAGE_MIME, '');
        $this->putSettingValue(self::SETTING_RENDER_MODE, 'full_page');
        $this->putSettingValue(self::SETTING_ORIENTATION, 'portrait');
    }

    /* ──────────────────── private helpers ──────────────────── */

    private function settingValue(string $key, ?string $default = null): ?string
    {
        foreach ($this->scopedKeyChain($key) as $candidateKey) {
            $value = SystemSetting::getValue($candidateKey);

            if ($value !== null) {
                return $value;
            }
        }

        return $default;
    }

    private function putSettingValue(string $key, string $value): void
    {
        SystemSetting::putValue($this->scopedKey($key), $value);
    }

    private function hasStoredSetting(string $key): bool
    {
        return SystemSetting::getValue($this->scopedKey($key)) !== null;
    }

    /**
     * Most-specific key for writes.
     *
     * Branch + type + format → context-scoped keys when both type and format
     * are set; otherwise just branch-scoped (the existing behaviour).
     */
    private function scopedKey(string $key): string
    {
        $branchId = $this->resolvedBranchId();
        $base = $branchId ? $key.':branch:'.$branchId : $key;

        if ($this->shouldScopeByContext($key)) {
            return $base.':type:'.$this->invoiceType.':format:'.$this->format;
        }

        return $base;
    }

    /**
     * Lookup chain for reads — most specific first, then progressively less so.
     *
     * Read order for a context-scoped key on branch=5, type=sales_standard, format=a4:
     *   1. invoice_bg_scale:branch:5:type:sales_standard:format:a4
     *   2. invoice_bg_scale:branch:5
     *   3. invoice_bg_scale
     *
     * For non-context keys (image path, render mode, etc.):
     *   1. invoice_bg_image_path:branch:5
     *   2. invoice_bg_image_path
     *
     * @return array<int, string>
     */
    private function scopedKeyChain(string $key): array
    {
        $branchId = $this->resolvedBranchId();
        $chain = [];

        if ($this->shouldScopeByContext($key)) {
            $branchPart = $branchId ? $key.':branch:'.$branchId : $key;
            $chain[] = $branchPart.':type:'.$this->invoiceType.':format:'.$this->format;
        }

        if ($branchId) {
            $chain[] = $key.':branch:'.$branchId;
        }

        $chain[] = $key;

        return array_values(array_unique($chain));
    }

    private function shouldScopeByContext(string $key): bool
    {
        return $this->invoiceType !== null
            && $this->format !== null
            && in_array($key, self::CONTEXT_SCOPED_KEYS, true);
    }

    private function resolvedBranchId(): ?int
    {
        if ($this->branchId !== null) {
            return $this->branchId;
        }

        try {
            $sessionBranchId = session()->has(\App\Services\Branches\BranchContextService::SESSION_KEY)
                ? (int) session(\App\Services\Branches\BranchContextService::SESSION_KEY)
                : 0;
        } catch (\Throwable) {
            $sessionBranchId = 0;
        }

        if ($sessionBranchId > 0) {
            return $sessionBranchId;
        }

        $user = auth('admin-web')->user();
        $userBranchId = $user?->branch_id ? (int) $user->branch_id : 0;

        return $userBranchId > 0 ? $userBranchId : null;
    }

    private function storeImageInfo(string $path, string $mime): void
    {
        $fullPath = Storage::disk('public')->path($path);
        $size = @getimagesize($fullPath);

        if (! $size) {
            return;
        }

        $width = (int) ($size[0] ?? 0);
        $height = (int) ($size[1] ?? 0);

        if ($width <= 0 || $height <= 0) {
            return;
        }

        $ratio = $width / $height;
        $mode = $this->detectRenderMode($ratio);
        $orientation = $ratio >= 1.10 ? 'landscape' : 'portrait';

        $this->putSettingValue(self::SETTING_IMAGE_WIDTH, (string) $width);
        $this->putSettingValue(self::SETTING_IMAGE_HEIGHT, (string) $height);
        $this->putSettingValue(self::SETTING_IMAGE_MIME, $mime !== '' ? $mime : (string) ($size['mime'] ?? ''));
        $this->putSettingValue(self::SETTING_RENDER_MODE, $mode);
        $this->putSettingValue(self::SETTING_ORIENTATION, $orientation);
    }

    private function ensureImageInfo(): void
    {
        if ((int) $this->settingValue(self::SETTING_IMAGE_WIDTH, '0') > 0) {
            return;
        }

        $path = $this->settingValue(self::SETTING_PATH);

        if (! $path || ! Storage::disk('public')->exists($path)) {
            return;
        }

        $this->storeImageInfo($path, '');
    }

    private function detectRenderMode(float $ratio): string
    {
        if ($ratio >= 2.0) {
            return 'wide_strip';
        }

        if (($ratio >= 0.58 && $ratio <= 0.82) || ($ratio >= 1.18 && $ratio <= 1.65)) {
            return 'full_page';
        }

        return 'partial';
    }

    private function convertPdfToImage(UploadedFile $file): ?string
    {
        $this->lastConversionError = null;

        if (! class_exists('\\Imagick')) {
            $this->lastConversionError = 'تعذر تحويل ملف PDF إلى صورة لأن امتداد Imagick غير مثبت على الخادم.';

            return null;
        }

        if (! $this->commandExists('gs')) {
            $this->lastConversionError = 'تعذر تحويل ملف PDF إلى صورة لأن Ghostscript غير مثبت على الخادم. ثبّت Ghostscript أو ارفع الملف بصيغة PNG/JPG.';

            return null;
        }

        try {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($file->getRealPath().'[0]');
            $imagick->setImageFormat('png');
            $imagick->setImageBackgroundColor('white');
            $image = $imagick->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
            $image->setImageFormat('png');

            Storage::disk('public')->makeDirectory('invoice-backgrounds');
            $filename = 'invoice-backgrounds/'.uniqid('bg_').'.png';
            $fullPath = Storage::disk('public')->path($filename);

            $image->writeImage($fullPath);
            $image->clear();
            $image->destroy();
            $imagick->clear();
            $imagick->destroy();

            return $filename;
        } catch (\Throwable $e) {
            $this->lastConversionError = 'تعذر تحويل ملف PDF إلى صورة. التفاصيل: '.$e->getMessage();

            return null;
        }
    }

    private function commandExists(string $command): bool
    {
        if (! function_exists('shell_exec')) {
            return true;
        }

        $escaped = escapeshellarg($command);
        $result = trim((string) shell_exec("command -v {$escaped} 2>/dev/null"));

        return $result !== '';
    }
}
