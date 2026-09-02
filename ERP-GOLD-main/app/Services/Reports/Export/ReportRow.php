<?php

namespace App\Services\Reports\Export;

/**
 * صف في جدول تقرير. الطابع (variant) يحمل دور الصف لا لونه: صف عادي، عنوان
 * قسم، أو صف مجموع — ويترك لكل راسم أن يعبّر عنه بأدواته.
 */
final class ReportRow
{
    public const NORMAL = 'normal';
    public const SECTION = 'section';
    public const TOTAL = 'total';

    /**
     * @param  list<ReportCell>  $cells
     */
    private function __construct(
        public readonly array $cells,
        public readonly string $variant,
    ) {
    }

    /**
     * @param  list<ReportCell>  $cells
     */
    public static function normal(array $cells): self
    {
        return new self($cells, self::NORMAL);
    }

    /**
     * @param  list<ReportCell>  $cells
     */
    public static function section(array $cells): self
    {
        return new self($cells, self::SECTION);
    }

    /**
     * @param  list<ReportCell>  $cells
     */
    public static function total(array $cells): self
    {
        return new self($cells, self::TOTAL);
    }
}
