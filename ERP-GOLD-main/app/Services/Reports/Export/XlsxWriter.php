<?php

namespace App\Services\Reports\Export;

use RuntimeException;
use ZipArchive;

/**
 * كاتب ملفات xlsx مصغّر يكفي جداول التقارير: ورقة واحدة باتجاه من اليمين
 * إلى اليسار، خانات نصية وأخرى رقمية حقيقية تقبل الجمع في Excel.
 *
 * كُتب هنا بدل إضافة مكتبة جداول كاملة لأن المطلوب سطر عنوان وترويسة وصفوف
 * لا أكثر، ولأن الملف الناتج xlsx صحيح يفتح دون تحذير «الامتداد لا يطابق
 * المحتوى» الذي يظهر حين يُصدَّر HTML باسم .xls.
 */
class XlsxWriter
{
    /** نسق الأرقام المدمج في Excel: ‎#,##0.00 */
    private const MONEY_FORMAT_ID = 4;

    /**
     * كل نمط: الخط والتعبئة والإطار. الباقي (المحاذاة والإزاحة والنسق الرقمي)
     * يُشتق من الخانة نفسها.
     */
    private const VARIANTS = [
        'title' => ['font' => 2, 'fill' => 0, 'border' => 0],
        'meta' => ['font' => 0, 'fill' => 0, 'border' => 0],
        'header' => ['font' => 1, 'fill' => 2, 'border' => 1],
        'body' => ['font' => 0, 'fill' => 0, 'border' => 1],
        'section' => ['font' => 1, 'fill' => 4, 'border' => 1],
        'total' => ['font' => 3, 'fill' => 3, 'border' => 1],
    ];

    /** @var array<string, int> */
    private array $styleKeys = [];

    /** @var list<array{font:int,fill:int,border:int,numFmt:int,align:string,indent:int,wrap:bool}> */
    private array $styles = [];

    public function build(ReportTable $table): string
    {
        $this->styleKeys = [];
        $this->styles = [];

        $sheet = $this->buildSheet($table);

        return $this->zip([
            '[Content_Types].xml' => $this->contentTypes(),
            '_rels/.rels' => $this->rootRels(),
            'xl/workbook.xml' => $this->workbook($table->title),
            'xl/_rels/workbook.xml.rels' => $this->workbookRels(),
            'xl/styles.xml' => $this->styles(),
            'xl/worksheets/sheet1.xml' => $sheet,
        ]);
    }

    /**
     * صفوف الورقة: العنوان، أسطر التعريف، سطر فاصل، ثم الجدول كما يُطبع.
     */
    private function buildSheet(ReportTable $table): string
    {
        $columnCount = $table->columnCount();
        $placed = [];
        $merges = [];
        $row = 1;

        $row = $this->placeBanner($table, $columnCount, $row, $placed, $merges);
        $headerStart = $row;
        $row = $this->place($table->headerRows, 'header', $row, $placed, $merges);
        $headerEnd = $row - 1;

        $bodyRows = $table->bodyRows;

        if ($bodyRows === []) {
            $bodyRows = [ReportRow::normal([ReportCell::text($table->emptyMessage, $columnCount)])];
        }

        foreach ([$bodyRows, $table->footerRows] as $group) {
            foreach ($group as $reportRow) {
                $variant = match ($reportRow->variant) {
                    ReportRow::SECTION => 'section',
                    ReportRow::TOTAL => 'total',
                    default => 'body',
                };

                $row = $this->place([$reportRow->cells], $variant, $row, $placed, $merges);
            }
        }

        return $this->sheetXml($placed, $merges, $columnCount, $headerStart, $headerEnd);
    }

    /**
     * @param  array<int, array<int, array{cell:ReportCell,variant:string}>>  $placed
     * @param  list<string>  $merges
     */
    private function placeBanner(ReportTable $table, int $columnCount, int $row, array &$placed, array &$merges): int
    {
        $banner = [[ReportCell::text($table->title, $columnCount)]];

        foreach ($table->meta as $line) {
            $banner[] = [ReportCell::text($line, $columnCount)];
        }

        $row = $this->place([$banner[0]], 'title', $row, $placed, $merges);
        $row = $this->place(array_slice($banner, 1), 'meta', $row, $placed, $merges);

        return $row + 1; // سطر فارغ يفصل الترويسة عن الجدول
    }

    /**
     * يوزّع صفوف الخانات على شبكة الورقة مراعيًا الدمج الأفقي والرأسي.
     *
     * @param  list<list<ReportCell>>  $rows
     * @param  array<int, array<int, array{cell:ReportCell,variant:string}>>  $placed
     * @param  list<string>  $merges
     * @return int  رقم الصف التالي
     */
    private function place(array $rows, string $variant, int $startRow, array &$placed, array &$merges): int
    {
        $occupied = [];
        $row = $startRow;

        foreach ($rows as $cells) {
            $column = 1;

            foreach ($cells as $cell) {
                while (isset($occupied[$row][$column])) {
                    $column++;
                }

                $placed[$row][$column] = ['cell' => $cell, 'variant' => $variant];

                if ($cell->colspan > 1 || $cell->rowspan > 1) {
                    $merges[] = $this->cellRef($column, $row)
                        . ':'
                        . $this->cellRef($column + $cell->colspan - 1, $row + $cell->rowspan - 1);
                }

                for ($r = $row; $r < $row + $cell->rowspan; $r++) {
                    for ($c = $column; $c < $column + $cell->colspan; $c++) {
                        $occupied[$r][$c] = true;
                    }
                }

                $column += $cell->colspan;
            }

            $row++;
        }

        return $row;
    }

    /**
     * @param  array<int, array<int, array{cell:ReportCell,variant:string}>>  $placed
     * @param  list<string>  $merges
     */
    private function sheetXml(array $placed, array $merges, int $columnCount, int $headerStart, int $headerEnd): string
    {
        ksort($placed);

        $rowsXml = '';

        foreach ($placed as $rowNumber => $cells) {
            ksort($cells);
            $cellsXml = '';

            foreach ($cells as $column => $entry) {
                $cellsXml .= $this->cellXml($this->cellRef($column, $rowNumber), $entry['cell'], $entry['variant']);
            }

            $rowsXml .= '<row r="' . $rowNumber . '">' . $cellsXml . '</row>';
        }

        $mergesXml = $merges === []
            ? ''
            : '<mergeCells count="' . count($merges) . '">'
                . implode('', array_map(fn ($ref) => '<mergeCell ref="' . $ref . '"/>', $merges))
                . '</mergeCells>';

        // تجميد الترويسة: تبقى أسماء الأعمدة ظاهرة مهما طال التقرير.
        $freezeRow = $headerEnd + 1;
        $pane = '<pane ySplit="' . $headerEnd . '" topLeftCell="A' . $freezeRow . '" activePane="bottomLeft" state="frozen"/>'
            . '<selection pane="bottomLeft" activeCell="A' . $freezeRow . '" sqref="A' . $freezeRow . '"/>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView rightToLeft="1" tabSelected="1" workbookViewId="0">' . $pane . '</sheetView></sheetViews>'
            . '<sheetFormatPr defaultRowHeight="16"/>'
            . $this->columnsXml($placed, $columnCount, $headerStart)
            . '<sheetData>' . $rowsXml . '</sheetData>'
            . $mergesXml
            . '</worksheet>';
    }

    /**
     * عرض كل عمود من أطول نص فيه، بحدّ أدنى وأقصى حتى لا يخرج عمود عن الصفحة.
     *
     * @param  array<int, array<int, array{cell:ReportCell,variant:string}>>  $placed
     */
    private function columnsXml(array $placed, int $columnCount, int $headerStart): string
    {
        $widths = array_fill(1, $columnCount, 12.0);

        foreach ($placed as $rowNumber => $cells) {
            if ($rowNumber < $headerStart) {
                continue; // العنوان ممدود على كل الأعمدة فلا يُقاس به عرض عمود
            }

            foreach ($cells as $column => $entry) {
                $cell = $entry['cell'];

                if ($cell->colspan > 1 || $column > $columnCount) {
                    continue;
                }

                $text = $cell->type === ReportCell::NUMBER
                    ? number_format((float) ($cell->value ?? 0), 2)
                    : (string) ($cell->value ?? '');

                $length = mb_strlen($text) + ($cell->indent * 2) + 3;
                $widths[$column] = max($widths[$column], min(48.0, (float) $length));
            }
        }

        $cols = '';

        foreach ($widths as $column => $width) {
            $cols .= '<col min="' . $column . '" max="' . $column . '" width="' . round($width, 2) . '" customWidth="1"/>';
        }

        return '<cols>' . $cols . '</cols>';
    }

    private function cellXml(string $reference, ReportCell $cell, string $variant): string
    {
        $style = $this->styleFor($cell, $variant);

        if ($cell->value === null || $cell->value === '') {
            return '<c r="' . $reference . '" s="' . $style . '"/>';
        }

        if ($cell->type === ReportCell::NUMBER) {
            return '<c r="' . $reference . '" s="' . $style . '"><v>' . $this->number((float) $cell->value) . '</v></c>';
        }

        return '<c r="' . $reference . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
            . $this->escape((string) $cell->value)
            . '</t></is></c>';
    }

    private function styleFor(ReportCell $cell, string $variant): int
    {
        $isMoney = $cell->type === ReportCell::NUMBER;
        $align = $cell->align;
        $indent = $cell->align === 'right' ? min(8, $cell->indent) : 0;
        $bold = $cell->bold;

        $key = implode('|', [$variant, $align, $indent, $isMoney ? 'n' : 't', $bold ? 'b' : '']);

        if (isset($this->styleKeys[$key])) {
            return $this->styleKeys[$key];
        }

        $base = self::VARIANTS[$variant];

        $this->styles[] = [
            'font' => $bold && $base['font'] === 0 ? 1 : $base['font'],
            'fill' => $base['fill'],
            'border' => $base['border'],
            'numFmt' => $isMoney ? self::MONEY_FORMAT_ID : 0,
            'align' => $align,
            'indent' => $indent,
            'wrap' => $variant === 'header',
        ];

        return $this->styleKeys[$key] = count($this->styles) - 1;
    }

    private function styles(): string
    {
        $xfs = '';

        foreach ($this->styles as $style) {
            $alignment = '<alignment horizontal="' . $style['align'] . '" vertical="center"'
                . ($style['wrap'] ? ' wrapText="1"' : '')
                . ($style['indent'] > 0 ? ' indent="' . $style['indent'] . '"' : '')
                . '/>';

            $xfs .= '<xf numFmtId="' . $style['numFmt'] . '" fontId="' . $style['font'] . '"'
                . ' fillId="' . $style['fill'] . '" borderId="' . $style['border'] . '" xfId="0"'
                . ' applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1"'
                . ($style['numFmt'] > 0 ? ' applyNumberFormat="1"' : '')
                . '>' . $alignment . '</xf>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="4">'
            . '<font><sz val="11"/><name val="Tahoma"/></font>'
            . '<font><b/><sz val="11"/><name val="Tahoma"/></font>'
            . '<font><b/><sz val="16"/><name val="Tahoma"/></font>'
            . '<font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Tahoma"/></font>'
            . '</fonts>'
            . '<fills count="5">'
            . '<fill><patternFill patternType="none"/></fill>'
            . '<fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FF1F2937"/><bgColor indexed="64"/></patternFill></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFF4F7FF"/><bgColor indexed="64"/></patternFill></fill>'
            . '</fills>'
            . '<borders count="2">'
            . '<border><left/><right/><top/><bottom/><diagonal/></border>'
            . '<border>'
            . '<left style="thin"><color rgb="FFCBD5E1"/></left>'
            . '<right style="thin"><color rgb="FFCBD5E1"/></right>'
            . '<top style="thin"><color rgb="FFCBD5E1"/></top>'
            . '<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            . '<diagonal/></border>'
            . '</borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="' . count($this->styles) . '">' . $xfs . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }

    private function workbook(string $title): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"'
            . ' xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $this->escape($this->sheetName($title)) . '" sheetId="1" r:id="rId1"/></sheets>'
            . '</workbook>';
    }

    /**
     * اسم الورقة في Excel لا يتجاوز 31 حرفًا ولا يقبل ‎: \ / ? * [ ]
     */
    private function sheetName(string $title): string
    {
        $name = trim(str_replace([':', '\\', '/', '?', '*', '[', ']'], ' ', $title));

        return $name === '' ? 'Report' : mb_substr($name, 0, 31);
    }

    private function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    private function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    /**
     * @param  array<string, string>  $files
     */
    private function zip(array $files): string
    {
        $path = tempnam(sys_get_temp_dir(), 'erp-xlsx-');

        if ($path === false) {
            throw new RuntimeException('تعذّر إنشاء ملف مؤقت لتصدير Excel.');
        }

        $archive = new ZipArchive();

        if ($archive->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
            @unlink($path);

            throw new RuntimeException('تعذّر فتح ملف Excel للكتابة.');
        }

        foreach ($files as $name => $contents) {
            $archive->addFromString($name, $contents);
        }

        $archive->close();

        $binary = file_get_contents($path);
        @unlink($path);

        if ($binary === false) {
            throw new RuntimeException('تعذّر قراءة ملف Excel بعد إنشائه.');
        }

        return $binary;
    }

    private function cellRef(int $column, int $row): string
    {
        $letters = '';

        while ($column > 0) {
            $remainder = ($column - 1) % 26;
            $letters = chr(65 + $remainder) . $letters;
            $column = intdiv($column - 1 - $remainder, 26);
        }

        return $letters . $row;
    }

    private function number(float $value): string
    {
        return rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.') ?: '0';
    }

    private function escape(string $value): string
    {
        // محارف التحكّم غير مسموح بها في XML وتُفسد الملف كله لو تسرّبت من بيانات قديمة
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/u', '', $value) ?? $value;

        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }
}
