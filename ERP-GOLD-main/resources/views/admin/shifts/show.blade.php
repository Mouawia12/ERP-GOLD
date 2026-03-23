@extends('admin.layouts.master')

@section('content')
<style>
    .shift-report-page {
        direction: rtl;
    }
    .shift-report-page .summary-card {
        border: 1px solid #ececec;
        border-radius: 14px;
        padding: 18px;
        background: #fff;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.05);
        height: 100%;
    }
</style>

<div class="container-fluid shift-report-page">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="mb-1">تفاصيل الشفت #{{ $shift->id }}</h3>
            <p class="mb-0 text-muted">
                {{ $shift->branch->name }} | {{ $shift->user->name }} | {{ $shift->opened_at?->format('Y-m-d H:i') }}
            </p>
        </div>
        <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary">رجوع إلى الشفتات</a>
    </div>

    <div class="row mb-4">
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="summary-card">
                <h5>عهدة الشفت</h5>
                <div>البداية: <strong>{{ number_format((float) $shift->opening_cash, 2) }}</strong></div>
                <div>المتوقع: <strong>{{ number_format((float) ($shift->expected_cash ?? $summary['expected_cash']), 2) }}</strong></div>
                <div>الفعلي: <strong>{{ $shift->closing_cash !== null ? number_format((float) $shift->closing_cash, 2) : '-' }}</strong></div>
                <div>الفرق: <strong>{{ $shift->cash_difference !== null ? number_format((float) $shift->cash_difference, 2) : '-' }}</strong></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="summary-card">
                <h5>الفواتير</h5>
                <div>عدد الحركات: <strong>{{ $summary['invoice_totals']['invoice_count'] }}</strong></div>
                <div>المبيعات: <strong>{{ number_format((float) $summary['invoice_totals']['sale_total'], 2) }}</strong></div>
                <div>مرتجع البيع: <strong>{{ number_format((float) $summary['invoice_totals']['sale_return_total'], 2) }}</strong></div>
                <div>المشتريات: <strong>{{ number_format((float) $summary['invoice_totals']['purchase_total'], 2) }}</strong></div>
                <div>مرتجع الشراء: <strong>{{ number_format((float) $summary['invoice_totals']['purchase_return_total'], 2) }}</strong></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="summary-card">
                <h5>القبض والسداد</h5>
                <div>عدد السندات: <strong>{{ $summary['voucher_totals']['voucher_count'] }}</strong></div>
                <div>سندات القبض: <strong>{{ number_format((float) $summary['voucher_totals']['receipt_total'], 2) }}</strong></div>
                <div>سندات الصرف: <strong>{{ number_format((float) $summary['voucher_totals']['payment_total'], 2) }}</strong></div>
                <div>قبض نقدي: <strong>{{ number_format((float) $summary['voucher_totals']['receipt_cash_total'], 2) }}</strong></div>
                <div>قبض غير نقدي: <strong>{{ number_format((float) $summary['voucher_totals']['receipt_non_cash_total'], 2) }}</strong></div>
                <div>صرف نقدي: <strong>{{ number_format((float) $summary['voucher_totals']['payment_cash_total'], 2) }}</strong></div>
                <div>صرف غير نقدي: <strong>{{ number_format((float) $summary['voucher_totals']['payment_non_cash_total'], 2) }}</strong></div>
            </div>
        </div>
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="summary-card">
                <h5>التدفق النقدي المعلوم</h5>
                <div>مبيعات نقدية: <strong>{{ number_format((float) $summary['invoice_totals']['sale_cash_total'], 2) }}</strong></div>
                <div>مبيعات غير نقدية: <strong>{{ number_format((float) $summary['invoice_totals']['sale_card_total'], 2) }}</strong></div>
                <div>مرتجع بيع نقدي: <strong>{{ number_format((float) $summary['invoice_totals']['sale_return_cash_total'], 2) }}</strong></div>
                <div>مرتجع بيع غير نقدي: <strong>{{ number_format((float) $summary['invoice_totals']['sale_return_non_cash_total'], 2) }}</strong></div>
                <div>مشتريات نقدية: <strong>{{ number_format((float) $summary['invoice_totals']['purchase_cash_total'], 2) }}</strong></div>
                <div>مشتريات غير نقدية: <strong>{{ number_format((float) $summary['invoice_totals']['purchase_non_cash_total'], 2) }}</strong></div>
                <div>مردود شراء نقدي: <strong>{{ number_format((float) $summary['invoice_totals']['purchase_return_cash_total'], 2) }}</strong></div>
                <div>مردود شراء غير نقدي: <strong>{{ number_format((float) $summary['invoice_totals']['purchase_return_non_cash_total'], 2) }}</strong></div>
                <div>ملاحظات الفتح:</div>
                <div class="text-muted">{{ $shift->opening_notes ?: '-' }}</div>
                <div class="mt-2">ملاحظات الإغلاق:</div>
                <div class="text-muted">{{ $shift->closing_notes ?: '-' }}</div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header">
            <h4 class="mb-0">الفواتير المرتبطة بالشفت</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>رقم المستند</th>
                        <th>النوع</th>
                        <th>التاريخ</th>
                        <th>المستخدم</th>
                        <th>الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['linked_invoices'] as $invoice)
                        <tr>
                            <td>{{ $invoice->id }}</td>
                            <td>{{ $invoice->bill_number }}</td>
                            <td>
                                @switch($invoice->type)
                                    @case('sale')
                                        بيع
                                        @break
                                    @case('sale_return')
                                        مرتجع بيع
                                        @break
                                    @case('purchase')
                                        شراء
                                        @break
                                    @case('purchase_return')
                                        مرتجع شراء
                                        @break
                                    @default
                                        {{ $invoice->type }}
                                @endswitch
                            </td>
                            <td>{{ $invoice->date }} {{ $invoice->time }}</td>
                            <td>{{ $invoice->user?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $invoice->net_total, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">لا توجد فواتير مرتبطة بهذا الشفت.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">السندات المالية المرتبطة بالشفت</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>رقم السند</th>
                        <th>النوع</th>
                        <th>التاريخ</th>
                        <th>القناة</th>
                        <th>المبلغ</th>
                        <th>الوصف</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summary['linked_vouchers'] as $voucher)
                        <tr>
                            <td>{{ $voucher->id }}</td>
                            <td>{{ $voucher->bill_number }}</td>
                            <td>{{ $voucher->type === 'receipt' ? 'قبض' : 'صرف' }}</td>
                            <td>{{ $voucher->date }}</td>
                            <td>{{ $voucher->payment_channel_label }}</td>
                            <td>{{ number_format((float) $voucher->total_amount, 2) }}</td>
                            <td>{{ $voucher->description ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد سندات مالية مرتبطة بهذا الشفت.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
