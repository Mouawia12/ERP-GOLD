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
        $printTemplate = $printSettings['template'] ?? 'classic';
        $showHeader = $printSettings['show_header'] ?? true;
        $showFooter = $printSettings['show_footer'] ?? true;
        $saleOrderNumber = $invoice->serial ?: '---';
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

        $caratSummary = [];
        foreach ($invoice->details as $detail) {
            $weightValue = $isSale ? $detail->out_weight : $detail->in_weight;
            if (preg_match('/(24|22|21|18)/', (string) $detail->carat_display_label, $matches)) {
                $caratKey = $matches[1];
                $caratSummary[$caratKey] = ($caratSummary[$caratKey] ?? 0) + (float) $weightValue;
            }
        }

        $caratSummary = collect(['24', '22', '21', '18'])
            ->filter(fn ($carat) => array_key_exists($carat, $caratSummary))
            ->map(fn ($carat) => ['label' => 'عيار '.$carat, 'value' => $caratSummary[$carat]])
            ->values();

        $invoiceSummaryRows = [
            ['label' => 'صافي الفاتورة قبل الخصم', 'value' => $invoice->lines_total],
            ['label' => 'إجمالي الخصم', 'value' => $invoice->discount_total],
            ['label' => 'صافي الفاتورة بعد الخصم', 'value' => $invoice->lines_total_after_discount],
            ['label' => 'إجمالي الضريبة المضافة', 'value' => $invoice->taxes_total],
            ['label' => 'الصافي شامل الضريبة', 'value' => $invoice->round_net_total ?: $invoice->net_total],
        ];

        $detailRows = $invoice->details->values();
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
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 8mm 22mm 8mm;
        }

        @font-face {
            font-family: 'Almarai';
            src: url("{{ asset('assets/fonts/Almarai.ttf') }}");
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            color: #111;
            font-family: 'Almarai', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.45;
        }

        body {
            --invoice-accent: #555;
            --sheet-background: #fff;
            --table-header-bg: #e0e0e0;
            --screen-background: #f3f4f6;
            --screen-outline: #d4d4d8;
            --company-font-size: 12px;
            --logo-frame-width: 168px;
            --logo-frame-height: 88px;
            --logo-size: 124px;
            --meta-list-gap: 10px;
            --table-cell-padding: 6px;
            background: var(--sheet-background);
        }

        body.invoice-template-compact {
            --company-font-size: 11px;
            --logo-frame-width: 152px;
            --logo-frame-height: 80px;
            --logo-size: 108px;
            --meta-list-gap: 8px;
            --table-cell-padding: 4px;
        }

        body.invoice-template-modern {
            --invoice-accent: #1f2937;
            --sheet-background: #f8fafc;
            --table-header-bg: #dbe4f0;
            --screen-background: #eef2ff;
            --screen-outline: #cbd5e1;
        }

        .page {
            width: 194mm;
            min-height: 267mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            background: var(--sheet-background);
        }

        .page-content {
            flex: 1;
        }

        .ltr {
            direction: ltr;
            unicode-bidi: embed;
            display: inline-block;
        }

        .invoice-rule,
        .page-footer {
            border-top: 1px solid var(--invoice-accent);
        }

        .invoice-header {
            display: grid;
            grid-template-columns: 1fr 168px 1fr;
            column-gap: 14px;
            align-items: start;
        }

        .company-block {
            min-height: 88px;
            font-size: var(--company-font-size);
        }

        .company-block.company-en {
            direction: ltr;
            text-align: left;
        }

        .company-block.company-ar {
            text-align: right;
        }

        .company-line {
            margin: 0 0 5px;
        }

        .company-name {
            font-weight: 700;
        }

        .header-center {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }

        .brand-logo-wrap {
            width: var(--logo-frame-width);
            height: var(--logo-frame-height);
            margin: 0 auto 6px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
        }

        .brand-logo {
            width: var(--logo-size);
            height: var(--logo-size);
            object-fit: contain;
            display: block;
            margin: 0;
            transform: scale(1.42);
            transform-origin: center center;
        }

        .invoice-title {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            white-space: nowrap;
        }

        .invoice-title-en {
            margin: 2px 0 0;
            font-size: 13px;
            font-weight: 700;
            white-space: nowrap;
        }

        .invoice-rule {
            margin: 8px 0 12px;
        }

        .invoice-head-meta {
            display: grid;
            grid-template-columns: 28% 28% 44%;
            column-gap: 14px;
            align-items: start;
            direction: ltr;
            margin-bottom: 14px;
        }

        .items-table,
        .totals-table,
        .payment-table,
        .carat-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table td,
        .items-table th,
        .totals-table td,
        .totals-table th,
        .payment-table td,
        .payment-table th,
        .carat-table td,
        .carat-table th {
            border: 1px solid #999;
            padding: var(--table-cell-padding);
            vertical-align: middle;
        }

        .items-table th,
        .totals-table th,
        .payment-table th,
        .carat-table th {
            background: var(--table-header-bg);
            font-weight: 700;
        }

        .invoice-meta-list {
            direction: rtl;
            display: flex;
            flex-direction: column;
            gap: var(--meta-list-gap);
            padding-top: 12px;
        }

        .invoice-meta-row {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            font-size: 13px;
            line-height: 1.6;
            font-weight: 700;
        }

        .invoice-meta-label {
            white-space: nowrap;
        }

        .invoice-meta-value {
            min-width: 0;
            word-break: break-word;
        }

        .qr-box {
            width: 100%;
            min-height: 240px;
            display: flex;
            align-items: flex-start;
            justify-content: flex-start;
            overflow: hidden;
            padding: 0;
            border: 0;
        }

        .qr-box.is-placeholder {
            min-height: 220px;
            border: 1px dashed #999;
            align-items: center;
            justify-content: center;
        }

        .qr-box img {
            width: 220px;
            height: 220px;
            object-fit: contain;
        }

        .qr-placeholder {
            font-size: 11px;
            color: #666;
        }

        .items-table {
            margin-bottom: 12px;
        }

        .items-table th,
        .items-table td {
            text-align: center;
            page-break-inside: avoid;
        }

        .items-table tbody tr {
            page-break-inside: avoid;
        }

        .items-table .description-cell {
            text-align: right;
        }

        .items-table .description-cell .sub-line {
            display: block;
            margin-top: 2px;
            font-size: 11px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            column-gap: 10px;
            margin-bottom: 10px;
        }

        .summary-stack {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .totals-table td:first-child,
        .payment-table td:first-child,
        .carat-table td:first-child {
            font-weight: 700;
        }

        .totals-table td:last-child,
        .payment-table td:last-child,
        .carat-table td:last-child {
            text-align: left;
        }

        .seller-line {
            margin: 0 0 10px;
            font-weight: 700;
        }

        .page-footer {
            margin-top: auto;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
        }

        .page-footer .footer-left {
            direction: ltr;
            text-align: left;
        }

        .no-print {
            display: none !important;
        }

        @media screen {
            body {
                padding: 8px 0 18px;
                background: var(--screen-background);
            }

            .page {
                box-shadow: 0 0 0 1px var(--screen-outline);
            }
        }

        body.invoice-template-compact .items-table td,
        body.invoice-template-compact .items-table th,
        body.invoice-template-compact .totals-table td,
        body.invoice-template-compact .totals-table th,
        body.invoice-template-compact .payment-table td,
        body.invoice-template-compact .payment-table th,
        body.invoice-template-compact .carat-table td,
        body.invoice-template-compact .carat-table th {
            padding: 4px;
        }

        body.invoice-template-modern .invoice-title,
        body.invoice-template-modern .invoice-title-en,
        body.invoice-template-modern .company-name {
            color: #0f172a;
        }
    </style>
</head>
<body
    data-print-format="a4"
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $showHeader ? '1' : '0' }}"
    data-show-footer="{{ $showFooter ? '1' : '0' }}"
    class="invoice-print-format-a4 invoice-template-{{ $printTemplate }}"
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
                        <div class="brand-logo-wrap">
                            <img src="{{ $brandLogoUrl }}" alt="Logo" class="brand-logo">
                        </div>
                        <h1 class="invoice-title">{{ $documentTitle }}</h1>
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
                        <th style="width: 5%;">م</th>
                        <th style="width: 18%;">الوصف</th>
                        <th style="width: 7%;">العيار</th>
                        <th style="width: 7%;">الوزن</th>
                        <th style="width: 7%;">ما خلا المعدن</th>
                        <th style="width: 8%;">سعر الجرام</th>
                        <th style="width: 6%;">العدد</th>
                        <th style="width: 10%;">الإجمالي</th>
                        <th style="width: 7%;">VAT</th>
                        <th style="width: 7%;">نسبة الضريبة</th>
                        <th style="width: 8%;">الإجمالي شامل الضريبة</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($detailRows as $index => $detail)
                        @php
                            $weight = $isSale ? $detail->out_weight : $detail->in_weight;
                            $nonMetal = $detail->no_metal_type === 'fixed'
                                ? (float) $detail->no_metal
                                : ((float) $weight * ((float) $detail->no_metal / 100));
                            $taxRate = $detail->line_total > 0
                                ? (((float) $detail->line_tax / (float) $detail->line_total) * 100)
                                : 0;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="description-cell">
                                {{ strip_tags((string) $detail->item->title) }}
                                <span class="sub-line ltr">{{ $detail->unit->barcode ?: '---' }}</span>
                            </td>
                            <td>{{ $detail->carat_display_label }}</td>
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

            <section class="summary-grid">
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

                <div>
                    <table class="totals-table">
                        <thead>
                            <tr>
                                <th colspan="2">ملخص المجاميع</th>
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

            <p class="seller-line">البائع: {{ $invoice->user->name ?: '---' }}</p>
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
