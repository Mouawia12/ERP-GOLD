<?php

namespace App\Services\Reports\Export;

/**
 * تقرير محاسبي موصوفًا مرة واحدة: عنوانه وأسطر ترويسته وصفوفه.
 *
 * منه تُرسم صفحة الطباعة و PDF (عبر admin.reports.partials.report_table)
 * ويُكتب منه ملف Excel (عبر XlsxWriter)، فلا يختلف تصدير عن طباعة.
 */
final class ReportTable
{
    /**
     * @param  list<list<ReportCell>>  $headerRows  صفوف ترويسة الجدول
     * @param  list<ReportRow>  $bodyRows
     * @param  list<ReportRow>  $footerRows
     * @param  list<string>  $meta  أسطر تعريفية تحت العنوان (المدة، الفرع، المستوى)
     */
    public function __construct(
        public readonly string $title,
        public readonly array $meta,
        public readonly array $headerRows,
        public readonly array $bodyRows,
        public readonly array $footerRows = [],
        public readonly string $emptyMessage = 'لا توجد بيانات مطابقة للفلاتر المحددة',
        public readonly string $fileName = 'report',
    ) {
    }

    /**
     * عدد الأعمدة الفعلي، محسوبًا من أعرض صف ترويسة.
     */
    public function columnCount(): int
    {
        $widest = 0;

        foreach ($this->headerRows as $row) {
            $width = 0;

            foreach ($row as $cell) {
                $width += $cell->colspan;
            }

            $widest = max($widest, $width);
        }

        return max(1, $widest);
    }
}
