<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php
        $invoiceTermsService = app(\App\Services\Invoices\InvoiceTermsService::class);
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
        $showInvoiceTerms = $invoiceTermsService->shouldShowInvoiceTermsForInvoice($invoice);
        $previewNotice = $invoiceTermsService->currentDefaultDiffersFromInvoiceSnapshot($invoice)
            ? 'هذه الفاتورة تعرض نسخة الشروط المحفوظة وقت الإنشاء. أي تعديل جديد على الشروط يطبق على الفواتير الجديدة فقط.'
            : null;
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
            $weightValue = $isPurchase
                ? ($detail->in_weight ?: $detail->out_weight)
                : $detail->out_weight;
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
        $inlineInvoiceTerms = $invoiceTermsService->formatTermsForPrint($invoice->invoice_terms);
        $branchAddressAr = $branch->short_address ?: $branch->full_address ?: '---';
        $branchAddressEn = $branchNameEn . ' - ' . ($branch->city ?: $branch->region ?: $branchAddressAr);
        $backUrl = route($isPurchase ? 'purchases.index' : 'purchase_return.index');
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

        html, body {
            margin: 0;
            padding: 0;
            color: #111;
            font-family: 'Almarai', 'DejaVu Sans', sans-serif;
            font-size: var(--invoice-screen-font-size);
            font-weight: 700;
            line-height: 1.45;
        }

        body {
            --invoice-accent: #555;
            --sheet-background: #fff;
            --table-header-bg: #e0e0e0;
            --screen-background: #f3f4f6;
            --screen-outline: #d4d4d8;
            --invoice-screen-font-size: 13px;
            --invoice-print-font-size: 11px;
            --invoice-title-font-size: 24px;
            --invoice-title-en-font-size: 14px;
            --invoice-meta-font-size: 16px;
            --company-font-size: 12px;
            --logo-frame-width: 168px;
            --logo-frame-height: 88px;
            --logo-size: 124px;
            --page-padding-block-start: 4mm;
            --page-padding-inline: 4mm;
            --page-padding-block-end: 6mm;
            --qr-column-width: 46mm;
            --qr-size: 46mm;
            --meta-list-gap: 10px;
            --table-cell-padding: 6px;
            background: var(--sheet-background);
        }

        body.invoice-template-compact {
            --invoice-title-font-size: 21px;
            --invoice-title-en-font-size: 13px;
            --invoice-meta-font-size: 14px;
            --company-font-size: 11px;
            --logo-frame-width: 152px;
            --logo-frame-height: 80px;
            --logo-size: 108px;
            --page-padding-block-start: 3mm;
            --page-padding-inline: 3mm;
            --page-padding-block-end: 5mm;
            --qr-column-width: 42mm;
            --qr-size: 42mm;
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

        table, th, td { font-size: inherit; }

        .page {
            width: 194mm;
            max-width: 100%;
            min-height: 267mm;
            margin: 0 auto;
            display: flex;
            flex-direction: column;
            background: var(--sheet-background);
            padding: var(--page-padding-block-start) var(--page-padding-inline) var(--page-padding-block-end);
            overflow: hidden;
        }

        .page-content { flex: 1; min-width: 0; }

        .ltr { direction: ltr; unicode-bidi: embed; display: inline-block; }

        .invoice-rule, .page-footer { border-top: 1px solid var(--invoice-accent); }

        .invoice-header {
            display: grid;
            grid-template-columns: 1fr 168px 1fr;
            column-gap: 14px;
            align-items: start;
        }

        .company-block { min-height: 88px; font-size: var(--company-font-size); }
        .company-block.company-en { direction: ltr; text-align: left; }
        .company-block.company-ar { text-align: right; }
        .company-line { margin: 0 0 5px; overflow-wrap: anywhere; }
        .company-name { font-weight: 700; }

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

        .invoice-title { margin: 0; font-size: var(--invoice-title-font-size); font-weight: 700; }
        .invoice-title-en { margin: 2px 0 0; font-size: var(--invoice-title-en-font-size); font-weight: 700; }
        .invoice-rule { margin: 8px 0 12px; }

        .invoice-head-meta {
            display: grid;
            grid-template-columns: var(--qr-column-width) minmax(0, 1fr) minmax(0, 1fr);
            column-gap: 14px;
            align-items: start;
            direction: ltr;
            margin-bottom: 14px;
        }

        .invoice-head-meta > * { min-width: 0; }

        .items-table, .totals-table, .payment-table, .carat-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .items-table td, .items-table th,
        .totals-table td, .totals-table th,
        .payment-table td, .payment-table th,
        .carat-table td, .carat-table th {
            border: 1px solid #999;
            padding: var(--table-cell-padding);
            vertical-align: middle;
        }

        .items-table th, .totals-table th, .payment-table th, .carat-table th {
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
            font-size: var(--invoice-meta-font-size);
            line-height: 1.6;
            font-weight: 700;
        }

        .invoice-meta-label { white-space: nowrap; flex: 0 0 auto; }
        .invoice-meta-value { flex: 1 1 auto; min-width: 0; word-break: break-word; overflow-wrap: anywhere; }

        .qr-box {
            width: 100%;
            max-width: var(--qr-column-width);
            min-height: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            padding: 0;
            border: 0;
            aspect-ratio: 1 / 1;
        }

        .qr-box.is-placeholder {
            min-height: var(--qr-size);
            border: 1px dashed #999;
        }

        .qr-box img { width: min(100%, var(--qr-size)); height: auto; aspect-ratio: 1 / 1; object-fit: contain; }
        .qr-placeholder { font-size: 11px; color: #666; }

        .items-table { margin-bottom: 12px; }

        .items-table th, .items-table td {
            text-align: center;
            page-break-inside: avoid;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .items-table tbody tr { page-break-inside: avoid; }
        .items-table .description-cell { text-align: right; overflow-wrap: anywhere; }
        .items-table .description-cell .sub-line { display: block; margin-top: 2px; font-size: 11px; }

        .summary-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
            column-gap: 10px;
            margin-bottom: 10px;
        }

        .summary-stack { display: flex; flex-direction: column; gap: 8px; }

        .totals-table td:first-child, .payment-table td:first-child, .carat-table td:first-child { font-weight: 700; }
        .totals-table td:last-child, .payment-table td:last-child, .carat-table td:last-child { text-align: left; }
        .totals-table td, .payment-table td, .carat-table td,
        .totals-table th, .payment-table th, .carat-table th { overflow-wrap: anywhere; word-break: break-word; }

        .seller-line { margin: 0 0 10px; font-weight: 700; overflow-wrap: anywhere; }

        .terms-box { margin: 0 0 10px; padding: 8px 10px; border: 1px solid #999; font-size: 10px; line-height: 1.45; }
        .terms-box-title { font-weight: 700; margin-bottom: 4px; }
        .terms-box-content { white-space: normal; overflow-wrap: anywhere; line-height: 1.6; }

        .page-footer {
            margin-top: auto;
            padding-top: 8px;
            display: flex;
            justify-content: space-between;
            gap: 12px;
            font-size: 11px;
            overflow-wrap: anywhere;
            border-top: 1px solid var(--invoice-accent);
        }

        .page-footer .footer-left { direction: ltr; text-align: left; }
        .no-print { display: none !important; }

        @media screen {
            body { padding: 8px 0 18px; background: var(--screen-background); }
            .page { width: min(194mm, calc(100vw - 24px)); box-shadow: 0 0 0 1px var(--screen-outline); }
        }

        body.invoice-template-compact .items-table td, body.invoice-template-compact .items-table th,
        body.invoice-template-compact .totals-table td, body.invoice-template-compact .totals-table th,
        body.invoice-template-compact .payment-table td, body.invoice-template-compact .payment-table th,
        body.invoice-template-compact .carat-table td, body.invoice-template-compact .carat-table th {
            padding: 4px;
        }

        @media print {
            html, body { background: #fff; font-size: var(--invoice-print-font-size); }
            .page { width: auto; max-width: none; min-height: auto; padding: 2mm 2.5mm 3mm; box-shadow: none; }
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
                        <p class="invoice-title-en">{{ $isPurchase ? 'Purchase Invoice' : 'Purchase Return Invoice' }}</p>
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
                        <span class="invoice-meta-label">رقم الفاتورة:</span>
                        <span class="invoice-meta-value ltr">{{ $invoice->bill_number }}</span>
                    </div>
                    <div class="invoice-meta-row">
                        <span class="invoice-meta-label">نوع السداد:</span>
                        <span class="invoice-meta-value">{{ $paymentTypeLabel }}</span>
                    </div>
                    @if($invoice->supplier_bill_number)
                        <div class="invoice-meta-row">
                            <span class="invoice-meta-label">مرجع المورد:</span>
                            <span class="invoice-meta-value ltr">{{ $invoice->supplier_bill_number }}</span>
                        </div>
                    @endif
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
                    @if($invoice->customerPhone)
                        <div class="invoice-meta-row">
                            <span class="invoice-meta-label">جوال المورد:</span>
                            <span class="invoice-meta-value ltr">{{ $invoice->customerPhone }}</span>
                        </div>
                    @endif
                    @if($invoice->customerIdentityNumber)
                        <div class="invoice-meta-row">
                            <span class="invoice-meta-label">هوية المورد:</span>
                            <span class="invoice-meta-value ltr">{{ $invoice->customerIdentityNumber }}</span>
                        </div>
                    @endif
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
                            $weight = $isPurchase
                                ? ($detail->in_weight ?: $detail->out_weight)
                                : $detail->out_weight;
                            $quantity = $detail->in_quantity ?: $detail->out_quantity ?: 1;
                            $nonMetal = $detail->no_metal_type === 'fixed'
                                ? (float) $detail->no_metal
                                : ((float) $weight * ((float) $detail->no_metal / 100));
                            $taxRate = $detail->line_total > 0
                                ? (((float) $detail->line_tax / (float) $detail->line_total) * 100)
                                : 0;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td class="description-cell">{{ strip_tags((string) $detail->item->title) }}</td>
                            <td>{{ $detail->carat_display_label }}</td>
                            <td><span class="ltr">{{ $fmtWeight($weight) }}</span></td>
                            <td><span class="ltr">{{ $fmtWeight($nonMetal) }}</span></td>
                            <td><span class="ltr">{{ $fmtMoney($detail->unit_price) }}</span></td>
                            <td><span class="ltr">{{ $quantity }}</span></td>
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

            @if($showInvoiceTerms)
                <section class="terms-box">
                    <div class="terms-box-title">ملاحظات</div>
                    <div class="terms-box-content">{{ $inlineInvoiceTerms }}</div>
                </section>
            @endif

            <p class="seller-line">الموظف: {{ $invoice->user->name ?: '---' }}</p>
        </div>

        @if($showFooter)
            <footer class="page-footer">
                <div class="footer-right">{{ $branchAddressAr }}</div>
                <div class="footer-left">{{ $branchAddressEn }}</div>
            </footer>
        @endif
    </div>

    @include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl', 'previewNotice'))
</body>
</html>
