<?php

namespace App\Services\Items;

use App\Models\SystemSetting;

class DefaultItemSettingsService
{
    public const SETTING_KEY = 'default_item_settings';

    /**
     * @return array<string, mixed>
     */
    public function currentSettings(): array
    {
        $raw = SystemSetting::getValue(self::SETTING_KEY, '');

        if (empty($raw)) {
            return $this->defaults();
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_merge($this->defaults(), $decoded) : $this->defaults();
    }

    /**
     * @param array<string, mixed> $settings
     */
    public function setSettings(array $settings): void
    {
        SystemSetting::putValue(self::SETTING_KEY, json_encode($settings));
    }

    /**
     * @return array<string, mixed>
     */
    private function defaults(): array
    {
        return [
            'inventory_classification' => '',
            'sale_mode'                => '',
            'gold_carat_type_id'       => '',
            'gold_carat_id'            => '',
            'no_metal_type'            => 'fixed',
            'no_metal'                 => '',
            'labor_cost_per_gram'      => '',
            'profit_margin_per_gram'   => '',
        ];
    }
}
