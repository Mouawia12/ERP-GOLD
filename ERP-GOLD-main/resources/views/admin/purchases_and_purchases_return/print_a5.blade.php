<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php
        $printSettings = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
        $branch = $invoice->branch;
        $subscriber = $branch->subscriber;
        $isPurchase = $invoice->type === 'purchase';
        $documentTitle = $isPurchase ? __('main.purchase_invoice') : __('main.purchase_return_invoice');
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
        $printTemplate = $printSettings['template'] ?? 'classic';
        $showHeader = $printSettings['show_header'] ?? true;
        $showFooter = $printSettings['show_footer'] ?? true;
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
            ['label' => 'قبل الضريبة', 'value' => $invoice->lines_total],
            ['label' => 'الخصم', 'value' => $invoice->discount_total],
            ['label' => 'الضريبة', 'value' => $invoice->taxes_total],
            ['label' => 'الصافي', 'value' => $invoice->net_total],
        ];

        $caratSummary = [];
        foreach ($invoice->details as $detail) {
            $weightValue = $invoice->type === 'purchase_return'
                ? $detail->out_weight
                : ($detail->in_weight ?: $detail->out_weight);
            if (preg_match('/(24|22|21|18)/', (string) $detail->carat_display_label, $matches)) {
                $caratKey = $matches[1];
                $caratSummary[$caratKey] = ($caratSummary[$caratKey] ?? 0) + (float) $weightValue;
            }
        }

        $caratSummary = collect(['24', '22', '21', '18'])
            ->filter(fn ($carat) => array_key_exists($carat, $caratSummary))
            ->map(fn ($carat) => ['label' => 'عيار '.$carat, 'value' => $caratSummary[$carat]])
            ->values();

        $branchAddressAr = $branch->short_address ?: $branch->full_address ?: '---';
        $branchAddressEn = $branchNameEn . ' - ' . ($branch->city ?: $branch->region ?: $branchAddressAr);
        $backUrl = route($invoice->type === 'purchase_return' ? 'purchase_return.index' : 'purchases.index');
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
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $showHeader ? '1' : '0' }}"
    data-show-footer="{{ $showFooter ? '1' : '0' }}"
    class="invoice-print-format-a5 invoice-template-{{ $printTemplate }}"
>
    <div class="page">
        <div class="page-content">
            @if($showHeader)
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
                        <h1 class="invoice-title">{{ $documentTitle }}</h1>
                        <p class="invoice-title-en">{{ $isPurchase ? 'Purchase Invoice' : 'Purchase Return' }}</p>
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
            @endif

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
                        <span class="invoice-meta-label">المورد:</span>
                        <span class="invoice-meta-value">{{ $invoice->customerName ?: '---' }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">مرجع المورد:</span>
                        <span class="invoice-meta-value ltr">{{ $invoice->supplier_bill_number ?: '---' }}</span>
                    </div>
                </div>
            </section>

            <table class="items-table">
                <thead>
                    <tr>
                        <th style="width: 6%;">م</th>
                        <th style="width: 36%;">الوصف</th>
                        <th style="width: 22%;">المواصفات</th>
                        <th style="width: 16%;">التسعير</th>
                        <th style="width: 20%;">الإجمالي</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->details->values() as $index => $detail)
                        @php
                            $weight = $invoice->type === 'purchase_return'
                                ? $detail->out_weight
                                : ($detail->in_weight ?: $detail->out_weight);
                            $quantity = $detail->in_quantity ?: $detail->out_quantity ?: 1;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="description-cell">
                                <span class="description-main">{{ strip_tags((string) $detail->item->title) }}</span>
                                <span class="sub-line ltr">{{ $detail->unit->barcode ?: '---' }}</span>
                            </td>
                            <td>
                                <span class="description-main">{{ $detail->carat_display_label ?: '---' }}</span>
                                <span class="sub-line">وزن: <span class="ltr">{{ $fmtWeight($weight) }}</span></span>
                                <span class="sub-line">عدد: <span class="ltr">{{ $quantity }}</span></span>
                            </td>
                            <td>
                                <span class="description-main ltr">{{ $fmtMoney($detail->line_total) }}</span>
                                <span class="sub-line">VAT: <span class="ltr">{{ $fmtMoney($detail->line_tax) }}</span></span>
                            </td>
                            <td>
                                <span class="description-main ltr">{{ $fmtMoney($detail->net_total) }}</span>
                                <span class="sub-line">شامل الضريبة</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <section class="summary-grid">
                <div class="summary-stack">
                    <table class="payment-table">
                        <thead>
                            <tr>
                                <th colspan="2">طرق السداد</th>
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

                    @if($caratSummary->isNotEmpty())
                        <table class="carat-table">
                            <thead>
                                <tr>
                                    <th colspan="2">تفصيل العيارات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($caratSummary as $row)
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td><span class="ltr">{{ $fmtWeight($row['value']) }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @endif
                </div>

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
            </section>

            <p class="seller-line">الموظف: {{ $invoice->user->name ?: '---' }}</p>

            @if(! empty($invoice->invoice_terms))
                <p class="notes-line">ملاحظات: {{ $invoice->invoice_terms }}</p>
            @endif
        </div>

        @if($showFooter)
            <footer class="page-footer">
                <div class="footer-right">{{ $branchAddressAr }}</div>
                <div class="footer-left">{{ $branchAddressEn }}</div>
            </footer>
        @endif
    </div>

    @include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl'))
</body>
</html>
