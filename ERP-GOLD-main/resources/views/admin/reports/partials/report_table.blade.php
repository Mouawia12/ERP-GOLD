{{-- يرسم App\Services\Reports\Export\ReportTable في صفحات الطباعة و PDF.
     الوصف نفسه يقرأه XlsxWriter، فيخرج ملف Excel بالصفوف والأرقام ذاتها. --}}
@php
    $columnCount = $table->columnCount();

    $cellAttributes = function ($cell) {
        $styles = ['text-align:' . $cell->align];

        if ($cell->indent > 0) {
            // بالبكسل لا بالـ rem: dompdf يتجاهل rem فتضيع إزاحة الشجرة في PDF
            $styles[] = 'padding-right:' . ($cell->indent * 8) . 'px';
        }

        if ($cell->bold) {
            $styles[] = 'font-weight:700';
        }

        return ($cell->colspan > 1 ? ' colspan="' . $cell->colspan . '"' : '')
            . ($cell->rowspan > 1 ? ' rowspan="' . $cell->rowspan . '"' : '')
            . ' style="' . implode(';', $styles) . '"';
    };

    $cellText = function ($cell) {
        if ($cell->value === null) {
            return '';
        }

        return $cell->type === \App\Services\Reports\Export\ReportCell::NUMBER
            ? number_format((float) $cell->value, 2)
            : (string) $cell->value;
    };
@endphp

<table class="print-table report-table {{ $tableClass ?? '' }}">
    <thead>
        @foreach ($table->headerRows as $headerRow)
            <tr>
                @foreach ($headerRow as $cell)
                    <th{!! $cellAttributes($cell) !!}>{{ $cellText($cell) }}</th>
                @endforeach
            </tr>
        @endforeach
    </thead>
    <tbody>
        @forelse ($table->bodyRows as $row)
            <tr class="report-table__row--{{ $row->variant }}">
                @foreach ($row->cells as $cell)
                    <td{!! $cellAttributes($cell) !!}>{{ $cellText($cell) }}</td>
                @endforeach
            </tr>
        @empty
            <tr>
                <td colspan="{{ $columnCount }}">{{ $table->emptyMessage }}</td>
            </tr>
        @endforelse
    </tbody>
    @if (! empty($table->footerRows))
        <tfoot>
            @foreach ($table->footerRows as $row)
                <tr>
                    @foreach ($row->cells as $cell)
                        <td{!! $cellAttributes($cell) !!}>{{ $cellText($cell) }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tfoot>
    @endif
</table>
