<?php

namespace App\Services\Branding;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class BrandLogoService
{
    public const SETTING_KEY = 'brand_logo_path';

    public function logoPath(): ?string
    {
        if (! Schema::hasTable('system_settings')) {
            return null;
        }

        return SystemSetting::getValue(self::SETTING_KEY);
    }

    public function logoUrl(): string
    {
        $path = $this->logoPath();

        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->url($path);
        }

        return asset('assets/img/logo.png');
    }

    public function storeUploadedLogo(UploadedFile $file): string
    {
        $oldPath = $this->logoPath();
        $newPath = $file->store('branding', 'public');

        if ($oldPath && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        SystemSetting::putValue(self::SETTING_KEY, $newPath);

        return $newPath;
    }
}
