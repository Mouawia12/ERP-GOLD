{{-- صفحة طباعة موحّدة للتقارير المحاسبية، على منوال صفحة ميزان المراجعة:
     منها يُطبع التقرير ومنها يُبنى ملف PDF بالحمولة نفسها. --}}
@php
    $columnCount = $table->columnCount();
    // الجداول العريضة تحتاج خطًا أصغر لتدخل في عرض الورقة
    $tableFontSize = $columnCount >= 9 ? '9px' : ($columnCount >= 6 ? '10px' : '12px');
    $printFormatName = $printFormat['format'] ?? 'a4';
@endphp

@extends('layouts.print', [
    'title' => $table->title,
    'printFormat' => $printFormatName,
    'printOrientation' => $printFormat['orientation'] ?? 'landscape',
    'pageMargin' => $printFormatName === 'a5' ? '6mm' : '8mm',
    'bodyClass' => 'accounting-report-print-page',
    'backUrl' => $backUrl ?? null,
    'pdfUrl' => $pdfUrl ?? null,
    'excelUrl' => $excelUrl ?? null,
    'hidePrintActions' => $hidePrintActions ?? false,
])

@push('styles')
<style>
    .accounting-report-print-page .print-page {
        min-height: 180mm;
    }

    .report-table {
        font-size: {{ $tableFontSize }};
    }

    {{-- عمود الحساب يحمل اسمًا مزاحًا بعمقه في الشجرة، فيأخذ نصيبًا أوسع
         كلما قلّت الأعمدة الرقمية إلى جانبه --}}
    .report-table th:first-child,
    .report-table td:first-child {
        width: {{ $columnCount >= 9 ? '18%' : ($columnCount >= 6 ? '26%' : '34%') }};
    }

    .report-table__row--section > td {
        background: #f4f7ff;
    }

    @media print {
        .report-table {
            font-size: calc({{ $tableFontSize }} - 1px);
            line-height: 1.35;
        }

        /* بلا !important: الحشوة اليمنى تأتي من سطر الخانة نفسها لتظهر إزاحة
           الشجرة، و !important هنا كان يمحوها فتتساوى كل المستويات */
        .report-table th,
        .report-table td {
            padding: 3px 4px;
        }
    }
</style>
@endpush

@section('content')
<article class="print-page">
    <header class="print-report-header">
        <div class="print-company-block">
            <strong>{{ $company['name'] ?? config('app.name') }}</strong>
            @if(! empty($company['tax_number']))
                <br>الرقم الضريبي: {{ $company['tax_number'] }}
            @endif
            @if(! empty($company['commercial_register']))
                <br>السجل التجاري: {{ $company['commercial_register'] }}
            @endif
            @if(! empty($company['phone']))
                <br>الهاتف: {{ $company['phone'] }}
            @endif
        </div>

        <div class="print-report-meta" style="text-align:center;">
            <h1 class="print-report-title">{{ $table->title }}</h1>
            @foreach ($table->meta as $line)
                <div>{{ $line }}</div>
            @endforeach
        </div>

        <div class="print-generated-at">
            تاريخ الإنشاء<br>
            <strong>{{ $generatedAt ?? now()->format('Y-m-d H:i') }}</strong>
        </div>
    </header>

    <div class="print-table-wrap">
        @include('admin.reports.partials.report_table', ['table' => $table])
    </div>
</article>
@endsection
