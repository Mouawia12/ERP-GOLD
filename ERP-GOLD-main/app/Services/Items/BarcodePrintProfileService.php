<?php

namespace App\Services\Items;

class BarcodePrintProfileService
{
    public const DEFAULT_PROFILE = 'a4_3x8';

    public function all(): array
    {
        return [
            'a4_3x8' => [
                'key' => 'a4_3x8',
                'label' => 'A4 - 3 x 8',
                'page_size' => 'A4',
                'columns' => 3,
                'label_width_mm' => 63,
                'label_height_mm' => 34,
                'gap_mm' => 4,
                'page_margin_mm' => 6,
                'barcode_scale' => 1.1,
                'barcode_height' => 22,
            ],
            'a5_2x5' => [
                'key' => 'a5_2x5',
                'label' => 'A5 - 2 x 5',
                'page_size' => 'A5',
                'columns' => 2,
                'label_width_mm' => 68,
                'label_height_mm' => 40,
                'gap_mm' => 4,
                'page_margin_mm' => 5,
                'barcode_scale' => 1.2,
                'barcode_height' => 24,
            ],
            'label_50x25' => [
                'key' => 'label_50x25',
                'label' => 'Label - 50 x 25',
                'page_size' => '50mm 25mm',
                'columns' => 1,
                'label_width_mm' => 50,
                'label_height_mm' => 25,
                'gap_mm' => 2,
                'page_margin_mm' => 2,
                'barcode_scale' => 0.9,
                'barcode_height' => 18,
            ],
        ];
    }

    public function resolve(?string $key): array
    {
        $profiles = $this->all();

        return $profiles[$key] ?? $profiles[self::DEFAULT_PROFILE];
    }
}
