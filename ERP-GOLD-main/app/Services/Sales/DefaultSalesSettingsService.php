<?php

namespace App\Services\Sales;

use App\Models\Item;
use App\Models\SystemSetting;

class DefaultSalesSettingsService
{
    public const SETTING_KEY = 'default_sales_settings';

    /**
     * خيارات نوع البيع المتاحة في فواتير المبيعات.
     *
     * @return array<string, string>
     */
    public static function saleClassificationOptions(): array
    {
        return [
            Item::CLASSIFICATION_GOLD => 'ذهب',
            Item::CLASSIFICATION_SILVER => 'فضة',
            Item::CLASSIFICATION_COLLECTIBLE => 'مقتنيات ثمينة',
        ];
    }

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

    public function defaultSaleClassification(): string
    {
        $classification = $this->currentSettings()['sale_classification'] ?? '';

        return array_key_exists($classification, self::saleClassificationOptions())
            ? $classification
            : Item::CLASSIFICATION_GOLD;
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
            'sale_classification' => Item::CLASSIFICATION_GOLD,
        ];
    }
}
