<?php

namespace App\Services\Reports\Export;

/**
 * خانة واحدة في جدول تقرير، موصوفة بمعناها لا بشكلها: نص أم رقم، محاذاتها،
 * وعمق إزاحتها في شجرة الحسابات. يقرأها راسم HTML للطباعة و PDF، ويقرأها
 * كاتب Excel، فيخرج الجدولان من وصف واحد ولا يفترق أحدهما عن الآخر.
 */
final class ReportCell
{
    public const TEXT = 'text';
    public const NUMBER = 'number';

    private function __construct(
        public readonly string|float|null $value,
        public readonly string $type,
        public readonly string $align,
        public readonly int $colspan,
        public readonly int $rowspan,
        public readonly int $indent,
        public readonly bool $bold,
    ) {
    }

    public static function header(string $value, int $colspan = 1, int $rowspan = 1): self
    {
        return new self($value, self::TEXT, 'center', $colspan, $rowspan, 0, true);
    }

    public static function text(?string $value, int $colspan = 1, bool $bold = false): self
    {
        return new self($value, self::TEXT, 'center', $colspan, 1, 0, $bold);
    }

    /**
     * اسم الحساب: يُحاذى يمينًا ويُزاح بعمقه في الشجرة كما في العرض على الشاشة.
     */
    public static function label(string $value, int $indent = 0, bool $bold = false): self
    {
        return new self($value, self::TEXT, 'right', 1, 1, max(0, $indent), $bold);
    }

    public static function number(?float $value, bool $bold = false): self
    {
        return new self($value === null ? null : (float) $value, self::NUMBER, 'center', 1, 1, 0, $bold);
    }

    public static function blank(int $colspan = 1): self
    {
        return new self(null, self::TEXT, 'center', $colspan, 1, 0, false);
    }
}
