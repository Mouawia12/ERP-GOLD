<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php
        $printSettings = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
        $branch = $invoice->branch;
        $subscriber = $branch->subscriber;
        $isSale = $invoice->type === 'sale';
        $documentTitle = $invoice->sale_type === 'standard'
            ? ($isSale ? __('main.sales_standard') : __('main.sales_standard_return'))
            : ($isSale ? __('main.sales_simplified') : __('main.sales_simplified_return'));
        $companyNameAr = $subscriber?->name ?: (method_exists($branch, 'getTranslation') ? $branch->getTranslation('name', 'ar') : $branch->name);
        $companyNameEn = method_exists($branch, 'getTranslation')
            ? ($branch->getTranslation('name', 'en') ?: $companyNameAr)
            : $companyNameAr;
        $branchNameAr = method_exists($branch, 'getTranslation') ? ($branch->getTranslation('name', 'ar') ?: $branch->name) : $branch->name;
        $branchNameEn = method_exists($branch, 'getTranslation') ? ($branch->getTranslation('name', 'en') ?: $branchNameAr) : $branchNameAr;
        $formattedDate = \Carbon\Carbon::parse($invoice->date)->format('d/m/Y');
        $formattedTime = $invoice->time ? \Carbon\Carbon::parse($invoice->time)->format('H:i') : now()->format('H:i');
        $fmtMoney = fn ($value) => number_format((float) $value, 2);
        $fmtWeight = fn ($value) => number_format((float) $value, 3);
        $currencyLabel = 'ريال';
        $saleOrderNumber = $invoice->serial ?: '---';
        $grandTotal = $invoice->round_net_total ?: $invoice->net_total;
        $paymentTypeLabel = [
            'cash' => 'نقدي',
            'credit_card' => 'شبكة / بطاقة',
            'bank_transfer' => 'تحويل بنكي',
        ][$invoice->payment_type ?: 'cash'] ?? 'نقدي';
        $paymentBreakdown = [
            ['label' => 'نقدي', 'value' => $invoice->cash_paid_total],
            ['label' => 'شبكة', 'value' => $invoice->credit_card_paid_total],
        ];

        if ((float) $invoice->bank_transfer_paid_total > 0) {
            $paymentBreakdown[] = ['label' => 'تحويل', 'value' => $invoice->bank_transfer_paid_total];
        }

        $bankPaymentLines = collect($invoice->payment_lines_breakdown ?? [])
            ->filter(fn ($paymentLine) => ! empty($paymentLine['bank_account_name']))
            ->map(function ($paymentLine) {
                $label = $paymentLine['method_label'].' - '.$paymentLine['bank_account_name'];
                if (! empty($paymentLine['reference_no'])) {
                    $label .= ' / '.$paymentLine['reference_no'];
                }

                return [
                    'label' => $label,
                    'value' => $paymentLine['amount'],
                ];
            });

        foreach ($bankPaymentLines as $paymentLine) {
            $paymentBreakdown[] = $paymentLine;
        }

        $invoiceSummaryRows = [
            ['label' => 'صافي الفاتورة قبل الخصم', 'value' => $invoice->lines_total],
            ['label' => 'إجمالي الخصم', 'value' => $invoice->discount_total],
            ['label' => 'صافي الفاتورة بعد الخصم', 'value' => $invoice->lines_total_after_discount],
            ['label' => 'إجمالي الضريبة المضافة', 'value' => $invoice->taxes_total],
            ['label' => 'الصافي شامل الضريبة', 'value' => $grandTotal],
        ];

        $branchAddressAr = $branch->short_address ?: $branch->full_address ?: '---';
        $branchAddressEn = $branchNameEn . ' - ' . ($branch->city ?: $branch->region ?: $branchAddressAr);
        $backUrl = route($isSale ? 'sales.index' : 'sales_return.index', $invoice->sale_type);
        $whatsappUrl = ! empty($invoice->client_phone)
            ? route('send.invoice.whatsapp', $invoice->id)
            : null;
    @endphp
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $documentTitle }} {{ $invoice->bill_number }}</title>
    @include('admin.invoices.partials.print_a5_traditional_styles')
</head>
<body
    data-print-format="a5"
    data-print-template="{{ $printSettings['template'] }}"
    data-show-header="{{ $printSettings['show_header'] ? '1' : '0' }}"
    data-show-footer="{{ $printSettings['show_footer'] ? '1' : '0' }}"
>
    <div class="page">
        <div class="page-content">
            <header class="invoice-header">
                <section class="company-block company-ar">
                    <p class="company-line company-name">{{ $companyNameAr }}</p>
                    <p class="company-line">الرقم الضريبي: <span class="ltr">{{ $branch->tax_number ?: '---' }}</span></p>
                    <p class="company-line">السجل التجاري: <span class="ltr">{{ $branch->commercial_register ?: '---' }}</span></p>
                    @if(! empty($branch->license_number))
                        <p class="company-line">رخصة المعادن: <span class="ltr">{{ $branch->license_number }}</span></p>
                    @endif
                    <p class="company-line">الفرع: {{ $branchNameAr }}</p>
                </section>

                <section class="header-center">
                    <img src="{{ $brandLogoUrl }}" alt="Logo" class="brand-logo">
                    <h1 class="invoice-title">{{ $invoice->sale_type === 'standard' ? 'فاتورة ضريبية' : 'فاتورة ضريبية مبسطة' }}</h1>
                    <p class="invoice-title-en">{{ $invoice->sale_type === 'standard' ? 'Tax Invoice' : 'Simplified Tax Invoice' }}</p>
                </section>

                <section class="company-block company-en">
                    <p class="company-line company-name">{{ $companyNameEn }}</p>
                    <p class="company-line">Tax Number: <span class="ltr">{{ $branch->tax_number ?: '---' }}</span></p>
                    <p class="company-line">Commercial Registry: <span class="ltr">{{ $branch->commercial_register ?: '---' }}</span></p>
                    @if(! empty($branch->license_number))
                        <p class="company-line">Mineral License: <span class="ltr">{{ $branch->license_number }}</span></p>
                    @endif
                    <p class="company-line">Branch: {{ $branchNameEn }}</p>
                </section>
            </header>

            <div class="invoice-rule"></div>

            <section class="invoice-head-meta">
                <div class="{{ ! empty($invoice->zatcaQrCode) ? 'qr-box' : 'qr-box is-placeholder' }}">
                    @if(! empty($invoice->zatcaQrCode))
                        <img src="{{ $invoice->zatcaQrCode }}" alt="QR Code">
                    @else
                        <span class="qr-placeholder">QR</span>
                    @endif
                </div>

                <div class="invoice-meta-list">
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">الرقم:</span>
                        <span class="invoice-meta-value ltr">{{ $invoice->bill_number }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">نوع السداد:</span>
                        <span class="invoice-meta-value">{{ $paymentTypeLabel }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">التليفون:</span>
                        <span class="invoice-meta-value ltr">{{ $invoice->customerPhone ?: '---' }}</span>
                    </div>
                </div>

                <div class="invoice-meta-list">
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">التاريخ:</span>
                        <span class="invoice-meta-value ltr">{{ $formattedDate }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">الوقت:</span>
                        <span class="invoice-meta-value ltr">{{ $formattedTime }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">العميل:</span>
                        <span class="invoice-meta-value">{{ $invoice->customerName ?: '---' }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">أمر البيع:</span>
                        <span class="invoice-meta-value ltr">{{ $saleOrderNumber }}</span>
                    </div>
                </div>
            </section>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">مسلسل</th>
                        <th style="width: 16%;">الوصف</th>
                        <th style="width: 8%;">العيار</th>
                        <th style="width: 8%;">الوزن</th>
                        <th style="width: 9%;">ما خلا المعدن</th>
                        <th style="width: 9%;">سعر الجرام</th>
                        <th style="width: 7%;">العدد</th>
                        <th style="width: 11%;">الإجمالي</th>
                        <th style="width: 8%;">VAT</th>
                        <th style="width: 8%;">نسبة الضريبة</th>
                        <th style="width: 10%;">الإجمالي شامل الضريبة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->details->values() as $index => $detail)
                        @php
                            $weight = $isSale ? $detail->out_weight : $detail->in_weight;
                            $nonMetal = $detail->no_metal_type === 'fixed'
                                ? (float) $detail->no_metal
                                : ((float) $weight * ((float) $detail->no_metal / 100));
                            $taxRate = (float) $detail->line_total > 0
                                ? (((float) $detail->line_tax / (float) $detail->line_total) * 100)
                                : 0;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="description-cell">
                                <span class="description-main">{{ strip_tags((string) $detail->item->title) }}</span>
                            </td>
                            <td>{{ $detail->carat_display_label ?: '---' }}</td>
                            <td><span class="ltr">{{ $fmtWeight($weight) }}</span></td>
                            <td><span class="ltr">{{ $fmtWeight($nonMetal) }}</span></td>
                            <td><span class="ltr">{{ $fmtMoney($detail->unit_price) }}</span></td>
                            <td><span class="ltr">{{ $detail->out_quantity ?: 0 }}</span></td>
                            <td><span class="ltr">{{ $fmtMoney($detail->line_total) }}</span></td>
                            <td><span class="ltr">{{ $fmtMoney($detail->line_tax) }}</span></td>
                            <td><span class="ltr">{{ $fmtMoney($taxRate) }}%</span></td>
                            <td><span class="ltr">{{ $fmtMoney($detail->round_net_total) }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <section class="summary-grid reference-summary-grid">
                <div class="summary-stack">
                    <table class="totals-table">
                        <thead>
                            <tr>
                                <th colspan="2">ملخص الفاتورة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoiceSummaryRows as $summaryRow)
                                <tr>
                                    <td>{{ $summaryRow['label'] }}</td>
                                    <td><span class="ltr">{{ $fmtMoney($summaryRow['value']) }}</span> {{ $currencyLabel }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="summary-stack">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th colspan="2">طرق الدفع</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentBreakdown as $paymentRow)
                                <tr>
                                    <td>{{ $paymentRow['label'] }}</td>
                                    <td><span class="ltr">{{ $fmtMoney($paymentRow['value']) }}</span> {{ $currencyLabel }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>

            <p class="seller-line">البائع: {{ $invoice->user->name ?: '---' }}</p>

            @if(! empty($invoice->invoice_terms))
                <p class="notes-line">ملاحظات: {{ $invoice->invoice_terms }}</p>
            @endif
        </div>

        <footer class="page-footer">
            <div class="footer-right">{{ $branchAddressAr }}</div>
            <div class="footer-left">{{ $branchAddressEn }}</div>
        </footer>
    </div>

    @include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl'))
</body>
</html>
