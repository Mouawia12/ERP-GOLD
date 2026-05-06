<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    @php
        $invoiceTermsService = app(\App\Services\Invoices\InvoiceTermsService::class);
        $printSettings = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
        $branch = $invoice->branch;
        $subscriber = $branch->subscriber;
        $isSale = $invoice->type === 'sale';
        $documentTitle = $invoice->sale_type === 'standard'
            ? ($isSale ? __('main.sales_standard') : __('main.sales_standard_return'))
            : ($isSale ? __('main.sales_simplified') : __('main.sales_simplified_return'));
        $documentTitleEn = $invoice->sale_type === 'standard'
            ? ($isSale ? 'Tax Invoice' : 'Tax Invoice Return')
            : ($isSale ? 'Simplified Tax Invoice' : 'Simplified Tax Invoice Return');
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

        foreach (collect($invoice->payment_lines_breakdown ?? [])->filter(fn ($paymentLine) => ! empty($paymentLine['bank_account_name'])) as $paymentLine) {
            $label = $paymentLine['method_label'].' - '.$paymentLine['bank_account_name'];
            if (! empty($paymentLine['reference_no'])) {
                $label .= ' / '.$paymentLine['reference_no'];
            }

            $paymentBreakdown[] = [
                'label' => $label,
                'value' => $paymentLine['amount'],
            ];
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

        $returnedTotal = $isSale ? $invoice->returned_total : 0;
        $hasReturns = $isSale && $returnedTotal > 0;
        $netAfterReturns = $isSale ? $invoice->net_after_returns : 0;

        $invoiceSummaryRows = [
            ['label' => 'صافي الفاتورة قبل الخصم', 'value' => $invoice->lines_total],
            ['label' => 'إجمالي الخصم', 'value' => $invoice->discount_total],
            ['label' => 'صافي الفاتورة بعد الخصم', 'value' => $invoice->lines_total_after_discount],
            ['label' => 'إجمالي الضريبة المضافة', 'value' => $invoice->taxes_total],
            ['label' => 'الصافي شامل الضريبة', 'value' => $invoice->round_net_total ?: $invoice->net_total],
        ];

        if ($hasReturns) {
            $invoiceSummaryRows[] = ['label' => 'إجمالي المرتجعات', 'value' => $returnedTotal, 'is_return' => true];
            $invoiceSummaryRows[] = ['label' => 'الصافي بعد المرتجع', 'value' => $netAfterReturns, 'is_net_after_return' => true];
        }

        $detailRows = $invoice->details->values();
        $inlineInvoiceTerms = $invoiceTermsService->formatTermsForPrint($invoice->invoice_terms);
        $branchAddressAr = $branch->short_address ?: $branch->full_address ?: '---';
        $branchAddressEn = $branchNameEn . ' - ' . ($branch->city ?: $branch->region ?: $branchAddressAr);
        $backUrl = route($isSale ? 'sales.index' : 'sales_return.index', $invoice->sale_type);
        $whatsappUrl = ! empty($invoice->client_phone)
            ? route('send.invoice.whatsapp', $invoice->id)
            : null;
        $bgService  = app(\App\Services\Invoices\InvoiceBackgroundService::class)
            ->forBranch((int) $invoice->branch_id)
            ->forContext(
                \App\Services\Invoices\InvoiceBackgroundService::detectInvoiceTypeFromInvoice($invoice),
                \App\Services\Invoices\InvoiceBackgroundService::FORMAT_A4
            );
        $bgImageUrl = $bgService->currentImageUrl();
        $bgScale      = $bgService->currentScale();
        $bgOffsetX    = $bgService->currentOffsetX();
        $bgContentTop    = $bgService->currentContentTop();
        $bgContentBottom = $bgService->currentContentBottom();
        $bgHideHeader    = $bgService->isHideHeader();
        $bgHideFooter    = $bgService->isHideFooter();
        $bgContentWidth  = $bgService->currentContentWidth();
        $bgContentScale  = $bgService->currentContentScale();
        $bgFontScale     = $bgService->currentFontScale();
        $bgPaperSize      = $bgService->currentPaperSize();
        $bgPaperOrientation = $bgService->currentPaperOrientation();
        $bgRenderMode     = $bgService->currentRenderMode();
        if ($bgHideHeader && $bgImageUrl) {
            $showHeader = false;
        }
        if ($bgHideFooter && $bgImageUrl) {
            $showFooter = false;
        }
        $compactStandalonePrint = ! $showHeader && ! $showFooter;
    @endphp
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $documentTitle }} {{ $invoice->bill_number }}</title>
    @include('admin.invoices.partials.print_dimension_vars', ['printSettings' => $printSettings, 'dimensionFormat' => 'a4'])
    <style>
        @page {
            size: A4 {{ ($printSettings['orientation'] ?? 'portrait') === 'landscape' ? 'landscape' : 'portrait' }};
            margin: {{ $compactStandalonePrint ? '6mm 8mm' : '8mm' }};
        }

        @font-face {
            font-family: 'Almarai';
            src: url("{{ asset('assets/fonts/Almarai.ttf') }}");
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            font-family: 'Almarai', 'DejaVu Sans', sans-serif;
        }

        body {
            direction: rtl;
            font-size: 12px;
            color: #111;
            background: #fff;
            line-height: 1.5;
            font-weight: 700;
            --line-color: #d5d9df;
            --line-strong: #9aa0a6;
            --head-bg: #f1f4f8;
        }

        table, th, td { direction: rtl; }
        th, td { vertical-align: middle; }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .ltr { direction: ltr; unicode-bidi: bidi-override; display: inline-block; }
        .small { font-size: 11px; }

        .title { font-size: 18px; font-weight: bold; margin: 4px 0 0; }
        .subtitle { font-size: 13px; font-weight: bold; margin: 2px 0 0; }

        .hr { border-top: 1px solid #555; margin: 6px 0 8px; }

        table { width: 100%; border-collapse: collapse; }

        .header-table td { vertical-align: top; }
        .header-table .block { line-height: 1.6; font-size: 12px; }
        .header-table .logo img { max-width: 110px; max-height: 110px; object-fit: contain; }
        .header-table .company-en { direction: ltr; text-align: left; }
        .header-table .company-name { font-weight: bold; }

        .meta-table, .items-table, .summary-table, .payment-table, .carat-table {
            border: 1px solid var(--line-color);
        }
        .meta-table th, .meta-table td,
        .items-table th, .items-table td,
        .summary-table th, .summary-table td,
        .payment-table th, .payment-table td,
        .carat-table th, .carat-table td {
            border: 1px solid var(--line-color);
            padding: 4px;
        }
        .items-table th,
        .summary-table th,
        .payment-table th,
        .carat-table th {
            background: var(--head-bg);
            font-weight: 700;
        }
        .items-table th .head-main,
        .items-table th .head-sub {
            display: block;
            line-height: 1.15;
        }
        .items-table th .head-main { font-size: 11px; font-weight: 700; }
        .items-table th .head-sub  { margin-top: 1px; font-size: 9px; color: #6b7280; direction: ltr; font-weight: 600; }

        .items-table td { text-align: center; }
        .items-table td.description-cell { text-align: right; overflow-wrap: anywhere; }
        .items-table td.description-cell .sub-line { display: block; font-size: 10px; margin-top: 2px; }

        .summary-table td:last-child,
        .payment-table td:last-child,
        .carat-table td:last-child {
            text-align: left;
        }

        .qr-box {
            width: 200px;
            max-width: 100%;
            margin: 0 auto;
            text-align: center;
        }
        .qr-box img { max-width: 195px; max-height: 195px; }

        .meta-grid { margin-top: 8px; }
        .meta-grid td { vertical-align: middle; }
        .meta-details-table { margin: 6px auto 0; width: auto; border: 0; }
        .meta-details-table td {
            padding: 3px 14px;
            text-align: center;
            font-size: 13px;
            border: 0;
        }

        .section-gap { margin-top: 8px; }
        .section-gap-sm { margin-top: 2px; }

        .page { display: flex; flex-direction: column; }
        .page-content { flex: 1 1 auto; }

        .footer {
            border-top: 1px solid #555;
            padding: 6px 0 0;
            margin-top: 10px;
            background: #fff;
            font-size: 11px;
        }

        .terms-box {
            margin: 8px 0 10px;
            padding: 8px 10px;
            border: 1px solid #999;
            font-size: 11px;
            line-height: 1.6;
        }
        .terms-box-title {
            font-weight: bold;
            margin-bottom: 4px;
        }
        .terms-box-content {
            white-space: normal;
            overflow-wrap: anywhere;
        }

        .seller-line {
            margin: 6px 0 8px;
            font-weight: bold;
            text-align: right;
        }

        .return-row td,
        .return-row th { color: #c0392b; font-weight: 700; }
        .net-after-return-row td,
        .net-after-return-row th { color: #27ae60; font-weight: 700; border-top: 2px solid #27ae60; }
        .footer { border-top: 1px solid var(--line-color); }
        .hr { border-top: 1px solid var(--line-strong); }

        .no-print { display: none !important; }

        @media screen {
            body {
                background: #f3f4f6;
                padding: 8px 0 18px;
            }
            .page {
                width: min(194mm, calc(100vw - 24px));
                margin: 0 auto;
                background: #fff;
                box-shadow: 0 0 0 1px #d4d4d8;
                padding: 8mm;
                min-height: 290mm;
            }
        }

        @media print {
            html, body { background: #fff; font-size: 11px; }
            html, body { height: auto; }
            .page { min-height: 0; }
            .page-content { page-break-inside: auto; }
            .items-table tr,
            .summary-table tr,
            .payment-table tr,
            .carat-table tr { page-break-inside: avoid; }
            thead { display: table-header-group; }
            tfoot { display: table-footer-group; }
        }

        body.invoice-template-compact { font-size: 11px; }
        body.invoice-template-compact .title { font-size: 16px; }
        body.invoice-template-compact .subtitle { font-size: 12px; }
        body.invoice-template-compact .meta-details-table td { font-size: 12px; padding: 2px 10px; }
        body.invoice-template-compact .items-table th,
        body.invoice-template-compact .items-table td,
        body.invoice-template-compact .summary-table th,
        body.invoice-template-compact .summary-table td,
        body.invoice-template-compact .payment-table th,
        body.invoice-template-compact .payment-table td,
        body.invoice-template-compact .carat-table th,
        body.invoice-template-compact .carat-table td { padding: 2px 4px; }
        body.invoice-template-compact .header-table .logo img { max-width: 90px; max-height: 90px; }

        body.invoice-template-modern { color: #0f172a; }
        body.invoice-template-modern .items-table th,
        body.invoice-template-modern .summary-table th,
        body.invoice-template-modern .payment-table th,
        body.invoice-template-modern .carat-table th { background: #dbe4f0; }
        body.invoice-template-modern .hr { border-top-color: #1f2937; }
        body.invoice-template-modern .footer { border-top-color: #1f2937; }
        body.invoice-template-modern .title,
        body.invoice-template-modern .subtitle,
        body.invoice-template-modern .header-table .company-name { color: #0f172a; }

        body.invoice-paper-ready .footer { display: none; }
        @media screen {
            body.invoice-paper-ready .page { padding: 6mm 8mm; min-height: auto; }
        }
    </style>
</head>
<body
    data-print-format="a4"
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $showHeader ? '1' : '0' }}"
    data-show-footer="{{ $showFooter ? '1' : '0' }}"
    class="invoice-print-format-a4 invoice-template-{{ $printTemplate }}{{ $compactStandalonePrint ? ' invoice-paper-ready' : '' }}"
>
@include('admin.invoices.partials.print_background', compact('bgImageUrl', 'bgScale', 'bgOffsetX', 'bgContentTop', 'bgContentBottom', 'bgContentWidth', 'bgContentScale', 'bgFontScale', 'bgHideHeader', 'bgHideFooter', 'bgPaperSize', 'bgPaperOrientation', 'bgRenderMode'))
<div class="page">
<div class="page-content">

@if($showHeader)
    <table class="header-table">
        <tr>
            <td class="text-right" style="width: 33%;">
                <div class="block">
                    <span class="company-name">{{ $companyNameAr }}</span><br>
                    الرقم الضريبي: <span class="ltr">{{ $branch->tax_number ?: '---' }}</span><br>
                    السجل التجاري: <span class="ltr">{{ $branch->commercial_register ?: '---' }}</span><br>
                    @if(!empty($branch->license_number))
                        رخصة المعادن: <span class="ltr">{{ $branch->license_number }}</span><br>
                    @endif
                    {{ $branchNameAr }}<br>
                </div>
            </td>
            <td class="text-center" style="width: 34%;">
                <div class="logo">
                    @if(!empty($brandLogoUrl))
                        <img src="{{ $brandLogoUrl }}" alt="Logo">
                    @endif
                </div>
                <div class="title">{{ $documentTitle }}</div>
                <div class="subtitle">{{ $documentTitleEn }}</div>
            </td>
            <td class="text-left company-en" style="width: 33%;">
                <div class="block">
                    <span class="company-name">{{ $companyNameEn }}</span><br>
                    Tax Number: {{ $branch->tax_number ?: '---' }}<br>
                    Commercial Registry: {{ $branch->commercial_register ?: '---' }}<br>
                    @if(!empty($branch->license_number))
                        Mineral License: {{ $branch->license_number }}<br>
                    @endif
                    {{ $branchNameEn }}<br>
                </div>
            </td>
        </tr>
    </table>

    <div class="hr"></div>
@endif

<table class="meta-grid">
    <tr>
        <td style="width: 70%; vertical-align: top; padding-top: 10px;">
            @php
                $isStandardInvoice = $invoice->sale_type === 'standard';
                $showAddressRow = $isStandardInvoice || filled($invoice->customerAddress);
                $showTaxRow = $isStandardInvoice || filled($invoice->customerTaxNumber) || filled($invoice->customerIdentityNumber);
            @endphp
            <table class="meta-details-table">
                <tr>
                    <td>التاريخ: <span class="ltr">{{ $formattedDate }}</span></td>
                    <td>الرقم: <span class="ltr">{{ $invoice->bill_number }}</span></td>
                </tr>
                <tr>
                    <td>الوقت: <span class="ltr">{{ $formattedTime }}</span></td>
                    <td>نوع السداد: {{ $paymentTypeLabel }}</td>
                </tr>
                <tr>
                    <td>العميل: {{ $invoice->customerName ?: '---' }}</td>
                    <td>التليفون: <span class="ltr">{{ $invoice->customerPhone ?: '---' }}</span></td>
                </tr>
                @if($showTaxRow)
                    <tr>
                        <td>الرقم الضريبي للعميل: <span class="ltr">{{ $invoice->customerTaxNumber ?: '---' }}</span></td>
                        <td>الهوية: <span class="ltr">{{ $invoice->customerIdentityNumber ?: '---' }}</span></td>
                    </tr>
                @endif
                @if($showAddressRow)
                    <tr>
                        <td colspan="2">العنوان: {{ $invoice->customerAddress ?: '---' }}</td>
                    </tr>
                @endif
                <tr>
                    <td colspan="2">أمر البيع: <span class="ltr">{{ $saleOrderNumber }}</span></td>
                </tr>
            </table>
        </td>
        <td style="width: 30%; vertical-align: top;">
            <div class="qr-box">
                @if(!empty($invoice->zatcaQrCode))
                    <img src="{{ $invoice->zatcaQrCode }}" alt="QR">
                @endif
            </div>
        </td>
    </tr>
</table>

<div class="section-gap-sm"></div>

<table class="items-table">
    <colgroup>
        <col style="width: 5%;">
        <col style="width: 18%;">
        <col style="width: 7%;">
        <col style="width: 7%;">
        <col style="width: 7%;">
        <col style="width: 8%;">
        <col style="width: 6%;">
        <col style="width: 10%;">
        <col style="width: 7%;">
        <col style="width: 7%;">
        <col style="width: 8%;">
    </colgroup>
    <thead>
        <tr>
            <th><span class="head-main">م</span><span class="head-sub">(No.)</span></th>
            <th><span class="head-main">الوصف</span><span class="head-sub">(Item)</span></th>
            <th><span class="head-main">العيار</span><span class="head-sub">(Karat)</span></th>
            <th><span class="head-main">الوزن</span><span class="head-sub">(Weight)</span></th>
            <th><span class="head-main">ما خلا المعدن</span><span class="head-sub">(Non Metal)</span></th>
            <th><span class="head-main">سعر الجرام</span><span class="head-sub">(Gram Price)</span></th>
            <th><span class="head-main">العدد</span><span class="head-sub">(Count)</span></th>
            <th><span class="head-main">الإجمالي</span><span class="head-sub">(Total)</span></th>
            <th><span class="head-main">الضريبة</span><span class="head-sub">(VAT)</span></th>
            <th><span class="head-main">نسبة الضريبة</span><span class="head-sub">(VAT %)</span></th>
            <th><span class="head-main">الإجمالي شامل الضريبة</span><span class="head-sub">(Total With VAT)</span></th>
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
                    @if(in_array($detail->item->inventory_classification, ['collectible', 'silver']))
                        @if($detail->item->stone_size_1 || $detail->item->stone_size_2)
                            <span class="sub-line">مقاس الحجر: {{ $detail->item->stone_size_1 }}{{ $detail->item->stone_size_2 ? ' / ' . $detail->item->stone_size_2 : '' }}</span>
                        @endif
                        @if($detail->item->stone_clarity || $detail->item->stone_color)
                            <span class="sub-line">{{ $detail->item->stone_clarity }}{{ ($detail->item->stone_clarity && $detail->item->stone_color) ? ' - ' : '' }}{{ $detail->item->stone_color }}</span>
                        @endif
                        @if($detail->item->brand)
                            <span class="sub-line">{{ $detail->item->brand }}{{ $detail->item->model_number ? ' / ' . $detail->item->model_number : '' }}</span>
                        @endif
                    @endif
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

<div class="section-gap"></div>

<table>
    <tr>
        <td style="width: 50%; vertical-align: top; padding-left: 6px;">
            <table class="payment-table">
                <tr>
                    <th colspan="2" class="text-center">طرق الدفع</th>
                </tr>
                @foreach($paymentBreakdown as $paymentRow)
                    <tr>
                        <th>{{ $paymentRow['label'] }}</th>
                        <td><span class="ltr">{{ $fmtMoney($paymentRow['value']) }}</span> {{ $currencyLabel }}</td>
                    </tr>
                @endforeach
            </table>

            @if($caratSummary->isNotEmpty())
                <div class="section-gap"></div>
                <table class="carat-table">
                    <tr>
                        <th colspan="2" class="text-center">تفصيل العيارات</th>
                    </tr>
                    @foreach($caratSummary as $row)
                        <tr>
                            <th>{{ $row['label'] }}</th>
                            <td><span class="ltr">{{ $fmtWeight($row['value']) }}</span></td>
                        </tr>
                    @endforeach
                </table>
            @endif
        </td>
        <td style="width: 50%; vertical-align: top; padding-right: 6px;">
            <table class="summary-table">
                @foreach($invoiceSummaryRows as $summaryRow)
                    @php
                        $isReturn = $summaryRow['is_return'] ?? false;
                        $isNetAfterReturn = $summaryRow['is_net_after_return'] ?? false;
                        $rowClass = $isReturn ? 'return-row' : ($isNetAfterReturn ? 'net-after-return-row' : '');
                    @endphp
                    <tr class="{{ $rowClass }}">
                        <th>{{ $summaryRow['label'] }}{{ $isReturn ? ' (-)' : '' }}</th>
                        <td><span class="ltr">{{ $fmtMoney($summaryRow['value']) }}</span> {{ $currencyLabel }}</td>
                    </tr>
                @endforeach
            </table>
        </td>
    </tr>
</table>

<div class="section-gap"></div>

@if($showInvoiceTerms && filled($inlineInvoiceTerms))
    <div class="terms-box">
        <div class="terms-box-title">شروط الفاتورة</div>
        <div class="terms-box-content">{{ $inlineInvoiceTerms }}</div>
    </div>
@endif

<table style="width: 100%; border: 0; margin-top: 14px;">
    <tr>
        <td style="width: 50%; text-align: center; border: 0; padding: 0 10px;">
            <div style="font-weight: 700; margin-bottom: 4px;">اسم البائع</div>
            <div style="border-top: 1px solid var(--line-strong); padding-top: 6px; min-height: 22px;">{{ $invoice->user?->name ?: '------' }}</div>
        </td>
        <td style="width: 50%; text-align: center; border: 0; padding: 0 10px;">
            <div style="font-weight: 700; margin-bottom: 4px;">مدير الفرع</div>
            <div style="border-top: 1px solid var(--line-strong); padding-top: 6px; min-height: 22px;">------</div>
        </td>
    </tr>
</table>

</div>
@if($showFooter)
    <div class="footer">
        <table>
            <tr>
                <td class="text-right">{{ $branchAddressAr }}</td>
                <td class="text-left ltr">{{ $branchAddressEn }}</td>
            </tr>
        </table>
    </div>
@endif
</div>

@include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl', 'previewNotice', 'bgImageUrl', 'bgScale'))
</body>
</html>
