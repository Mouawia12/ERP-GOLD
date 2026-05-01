@extends('layouts.print', [
    'title' => __('main.balance_report'),
    'printFormat' => $printFormat['format'] ?? 'a4',
    'printOrientation' => $printFormat['orientation'] ?? 'landscape',
    'pageMargin' => $printFormat['format'] === 'a5' ? '6mm' : '8mm',
    'bodyClass' => 'trial-balance-print-page',
    'backUrl' => $backUrl ?? route('trail_balance.index'),
    'pdfUrl' => $pdfUrl ?? null,
    'hidePrintActions' => $hidePrintActions ?? false,
])

@push('styles')
<style>
    .trial-balance-print-page .print-page {
        min-height: 180mm;
    }

    .trial-balance-table {
        font-size: 10px;
    }

    .trial-balance-table th:first-child,
    .trial-balance-table td:first-child {
        width: 26%;
        text-align: right;
    }

    .trial-balance-table th:not(:first-child),
    .trial-balance-table td:not(:first-child) {
        width: 10.5%;
    }

    @media print {
        .trial-balance-table {
            font-size: 8.5px;
            line-height: 1.35;
        }

        .trial-balance-table th,
        .trial-balance-table td {
            padding: 3px 4px !important;
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
            <h1 class="print-report-title">{{ __('main.balance_report') }}</h1>
            <div>[ {{ $periodFrom }} - {{ $periodTo }} ]</div>
            <div>الفرع: {{ $branchLabel ?? ($branch?->name ?: 'جميع الفروع') }}</div>
            <div>المستوى: {{ $accountLevel ? 'مستوى ' . $accountLevel : 'تفصيلي (آخر مستوى)' }}</div>
        </div>

        <div class="print-generated-at">
            تاريخ الإنشاء<br>
            <strong>{{ $generatedAt ?? now()->format('Y-m-d H:i') }}</strong>
        </div>
    </header>

    <div class="print-table-wrap">
        @include('admin.reports.trail_balance.partials.table')
    </div>
</article>
@endsection
