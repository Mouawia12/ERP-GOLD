<?php

namespace App\Services\Shifts;

use App\Models\SystemSetting;

class SalesShiftModeService
{
    public const SETTING_KEY = 'sales_shift_mode';
    public const MODE_ENABLED = 'enabled';
    public const MODE_DISABLED = 'disabled';

    /**
     * @return array<int, string>
     */
    public function availableModes(): array
    {
        return [
            self::MODE_ENABLED,
            self::MODE_DISABLED,
        ];
    }

    public function currentMode(): string
    {
        $mode = SystemSetting::getValue(self::SETTING_KEY, self::MODE_ENABLED);

        return in_array($mode, $this->availableModes(), true)
            ? $mode
            : self::MODE_ENABLED;
    }

    public function requiresShift(): bool
    {
        return $this->currentMode() === self::MODE_ENABLED;
    }

    public function setMode(string $mode): void
    {
        SystemSetting::putValue(self::SETTING_KEY, $mode);
    }
}
