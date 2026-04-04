<!DOCTYPE html>
<html>
<head>
    @php
        $printSettings = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
        $printFormat = $printSettings['format'];
        $printTemplate = $printSettings['template'];
        $sheetWidth = $printFormat === 'a5' ? '148mm' : '210mm';
        $screenFontSize = $printFormat === 'a5' ? '12px' : '13px';
        $printFontSize = $printFormat === 'a5' ? '10px' : '11px';
        $headerHeight = $printFormat === 'a5' ? '2.3cm' : '2.9cm';
        $qrWidth = $printFormat === 'a5' ? '82px' : '104px';
        $isPurchase = $invoice->type === 'purchase';
        $documentTitle = $isPurchase ? __('main.purchase_invoice') : __('main.purchase_return_invoice');
        $operationLabel = $isPurchase ? 'مشتريات' : 'مرتجع مشتريات';
        $bankPaymentLines = collect($invoice->payment_lines_breakdown ?? [])
            ->filter(fn ($paymentLine) => ! empty($paymentLine['bank_account_name']));
    @endphp
    <title>{{ $documentTitle }} رقم {{ $invoice->bill_number }}</title>
    <meta charset="utf-8"/>
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet"/>
    @include('admin.invoices.partials.print_styles', [
        'printFormat' => $printFormat,
        'sheetWidth' => $sheetWidth,
        'screenFontSize' => $screenFontSize,
        'printFontSize' => $printFontSize,
        'headerHeight' => $headerHeight,
        'qrWidth' => $qrWidth,
    ])
</head>
<body
    dir="rtl"
    data-print-format="{{ $printFormat }}"
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $printSettings['show_header'] ? '1' : '0' }}"
    data-show-footer="{{ $printSettings['show_footer'] ? '1' : '0' }}"
    class="invoice-print-format-{{ $printFormat }} invoice-template-{{ $printTemplate }}"
>
<div class="pos_details">
    <div class="invoice-print-sheet">
        @if($printSettings['show_header'])
            <header class="print-header-section">
                <div class="header-main">
                    <div class="header-copy">
                        <span class="header-kicker">وثيقة مشتريات</span>
                        <h1 class="header-title">{{ $documentTitle }}</h1>
                        <p class="header-subtitle">الفرع: {{ $invoice->branch->name }}</p>
                    </div>
                    <img src="{{ $brandLogoUrl }}" class="print-brand-logo" alt="Logo">
                </div>
                <div class="header-meta-list">
                    <span class="header-meta-pill">س.ت: {{ $invoice->branch->tax_number ?: '---' }}</span>
                    <span class="header-meta-pill">ر.ض: {{ $invoice->branch->commercial_register ?: '---' }}</span>
                    <span class="header-meta-pill">هاتف: {{ $invoice->branch->phone ?: '---' }}</span>
                    <span class="header-meta-pill">{{ strtoupper($printFormat) }}</span>
                </div>
            </header>
        @endif

        <section class="invoice-hero">
            <div class="hero-copy">
                <span class="hero-overline">{{ $operationLabel }}</span>
                <h2 class="hero-title">رقم الفاتورة: <span dir="ltr">{{ $invoice->bill_number }}</span></h2>
                <p class="hero-subtitle">عرض منظم لبيانات المورد والعناصر والضرائب مع تنسيق مخصص للطباعة الرسمية.</p>
                <div class="hero-pills">
                    <span class="hero-pill">التاريخ: <span dir="ltr">&nbsp;{{ \Carbon\Carbon::parse($invoice->date)->format('d-m-Y') }}</span></span>
                    <span class="hero-pill">النوع: {{ $documentTitle }}</span>
                    <span class="hero-pill">الفرع: {{ $invoice->branch->name }}</span>
                    @if($invoice->supplier_bill_number)
                        <span class="hero-pill">مرجع المورد: <span dir="ltr">&nbsp;{{ $invoice->supplier_bill_number }}</span></span>
                    @endif
                </div>
            </div>
            <div class="hero-qr">
                <span class="hero-qr-label">QR</span>
                <img src="{{ $invoice->zatcaQrCode }}" class="invoice-print-qr" alt="QR Code"/>
            </div>
        </section>

        <section class="meta-panels">
            <article class="meta-panel">
                <div class="panel-heading">
                    <h3 class="panel-title">بيانات المورد</h3>
                    <span class="panel-hint">{{ $invoice->customerName ? 'بيانات محفوظة مع الفاتورة' : 'بدون مورد محفوظ' }}</span>
                </div>
                <div class="panel-list">
                    <div class="panel-item">
                        <span class="panel-label">اسم المورد</span>
                        <span class="panel-value" dir="ltr">{{ $invoice->customerName ?: '---' }}</span>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">هاتف المورد</span>
                        <span class="panel-value" dir="ltr">{{ $invoice->customerPhone ?: '---' }}</span>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">رقم الهوية</span>
                        <span class="panel-value" dir="ltr">{{ $invoice->customerIdentityNumber ?: '---' }}</span>
                    </div>
                </div>
            </article>

            <article class="meta-panel">
                <div class="panel-heading">
                    <h3 class="panel-title">بيانات الفرع</h3>
                    <span class="panel-hint">{{ $operationLabel }}</span>
                </div>
                <div class="panel-list">
                    <div class="panel-item">
                        <span class="panel-label">الفرع</span>
                        <span class="panel-value">{{ $invoice->branch->name }}</span>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">الرقم الضريبي</span>
                        <span class="panel-value" dir="ltr">{{ $invoice->branch->tax_number ?: '---' }}</span>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">السجل التجاري</span>
                        <span class="panel-value" dir="ltr">{{ $invoice->branch->commercial_register ?: '---' }}</span>
                    </div>
                    <div class="panel-item">
                        <span class="panel-label">هاتف الفرع</span>
                        <span class="panel-value" dir="ltr">{{ $invoice->branch->phone ?: '---' }}</span>
                    </div>
                </div>
            </article>
        </section>

        <section class="section-card table-section">
            <div class="section-heading">
                <h3 class="section-title">تفاصيل الأصناف</h3>
                <span class="section-hint">{{ $invoice->details->count() }} سطر</span>
            </div>
            <table class="table-bordered invoice-print-table">
                <thead>
                <tr>
                    <th style="width: 32%;">الصنف</th>
                    <th style="width: 18%;">العيار / الوزن</th>
                    <th style="width: 18%;">العدد / الأجور</th>
                    <th style="width: 16%;">الضريبة</th>
                    <th style="width: 16%;">الإجمالي</th>
                </tr>
                </thead>
                <tbody id="tbody">
                @foreach($invoice->details as $detail)
                    @php
                        $weight = $invoice->type === 'purchase_return'
                            ? $detail->out_weight
                            : ($detail->in_weight ?: $detail->out_weight);
                    @endphp
                    <tr>
                        <td>
                            <span class="line-primary">{!! $detail->item->title !!}</span>
                            <span class="line-secondary">كود الصنف: {{ $detail->unit->barcode ?: '---' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="line-primary">{{ $detail->carat_display_label ?: '---' }}</span>
                            <span class="line-secondary">الوزن: {{ $weight ?: '0' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="line-primary">العدد: 1</span>
                            <span class="line-secondary">الأجور: {{ number_format((float) $detail->line_total, 2) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="line-primary">{{ number_format((float) $detail->line_tax, 2) }}</span>
                            <span class="line-secondary">قبل الضريبة: {{ number_format((float) $detail->line_total, 2) }}</span>
                        </td>
                        <td class="text-center">
                            <span class="line-primary line-total">{{ number_format((float) $detail->net_total, 2) }}</span>
                            <span class="line-secondary">شامل الضريبة</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </section>

        <section class="summary-grid">
            <article class="section-card summary-card">
                <div class="section-heading">
                    <h3 class="section-title">الملخص المالي</h3>
                    <span class="section-hint">القيم النهائية</span>
                </div>
                <div class="summary-list">
                    <div class="summary-row">
                        <span class="summary-label">الإجمالي قبل الضريبة</span>
                        <span class="summary-value">{{ number_format((float) $invoice->lines_total, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">الخصم</span>
                        <span class="summary-value">{{ number_format((float) $invoice->discount_total, 2) }}</span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">ضريبة القيمة المضافة</span>
                        <span class="summary-value">{{ number_format((float) $invoice->taxes_total, 2) }}</span>
                    </div>
                    <div class="summary-row total">
                        <span class="summary-label">قيمة الفاتورة</span>
                        <span class="summary-value">{{ number_format((float) $invoice->net_total, 2) }}</span>
                    </div>
                </div>
            </article>

            <article class="section-card summary-card">
                <div class="section-heading">
                    <h3 class="section-title">وسائل السداد</h3>
                    <span class="section-hint">تفصيل السداد</span>
                </div>
                <div class="payment-list">
                    <div class="payment-row">
                        <span class="payment-label">{{ __('main.cash') }}</span>
                        <span class="payment-value">{{ number_format((float) $invoice->cash_paid_total, 2) }}</span>
                    </div>
                    <div class="payment-row">
                        <span class="payment-label">{{ __('main.visa') }}</span>
                        <span class="payment-value">{{ number_format((float) $invoice->credit_card_paid_total, 2) }}</span>
                    </div>
                    <div class="payment-row">
                        <span class="payment-label">تحويل بنكي</span>
                        <span class="payment-value">{{ number_format((float) $invoice->bank_transfer_paid_total, 2) }}</span>
                    </div>
                    @foreach($bankPaymentLines as $paymentLine)
                        <div class="payment-row">
                            <span class="payment-label">
                                {{ $paymentLine['method_label'] }} - {{ $paymentLine['bank_account_name'] }}
                                @if($paymentLine['reference_no'])
                                    <br><small dir="ltr">{{ $paymentLine['reference_no'] }}</small>
                                @endif
                            </span>
                            <span class="payment-value">{{ number_format((float) $paymentLine['amount'], 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </article>

            <article class="section-card summary-card">
                <div class="section-heading">
                    <h3 class="section-title">شروط الفاتورة</h3>
                    <span class="section-hint">تظهر كما حُفظت على الفاتورة</span>
                </div>
                <div class="terms-body">{{ $invoice->invoice_terms ?: '---' }}</div>
            </article>
        </section>

        @if($printSettings['show_footer'])
            <div class="row print-footer-section" style="direction:rtl">
                <div class="col-6 text-center">
                    <span class="signature-label">اسم الموظف</span>
                    <div class="signature-line">{{ $invoice->user->name }}</div>
                </div>
                <div class="col-6 text-center">
                    <span class="signature-label">مدير الفرع</span>
                    <div class="signature-line">........</div>
                </div>
            </div>
        @endif
    </div>
</div>

@php
    $backUrl = route($invoice->type === 'purchase_return' ? 'purchase_return.index' : 'purchases.index');
    $whatsappUrl = ! empty($invoice->client_phone)
        ? route('send.invoice.whatsapp', $invoice->id)
        : null;
@endphp
@include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl'))
</body>
</html>
