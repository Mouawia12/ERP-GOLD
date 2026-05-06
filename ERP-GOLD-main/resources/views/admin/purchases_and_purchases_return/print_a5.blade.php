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
        $documentTitleEn = $isPurchase ? 'Purchase Invoice' : 'Purchase Return Invoice';
        $companyNameAr = $subscriber?->name ?: (method_exists($branch, 'getTranslation') ? $branch->getTranslation('name', 'ar') : $branch->name);
        $branchNameAr = method_exists($branch, 'getTranslation') ? ($branch->getTranslation('name', 'ar') ?: $branch->name) : $branch->name;
        $formattedDate = \Carbon\Carbon::parse($invoice->date)->format('d-m-Y');
        $formattedTime = $invoice->time ? \Carbon\Carbon::parse($invoice->time)->format('H:i') : now()->format('H:i');
        $fmtMoney = fn ($value) => number_format((float) $value, 2);
        $fmtWeight = fn ($value) => number_format((float) $value, 3);
        $printTemplate = $printSettings['template'] ?? 'classic';
        $showHeader = $printSettings['show_header'] ?? true;
        $showFooter = $printSettings['show_footer'] ?? true;
        $printOrientation = $printSettings['orientation'] ?? 'portrait';
        $showInvoiceTerms = $invoiceTermsService->shouldShowInvoiceTermsForInvoice($invoice);
        $inlineInvoiceTerms = $invoiceTermsService->formatTermsForPrint($invoice->invoice_terms);
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
        foreach (collect($invoice->payment_lines_breakdown ?? [])->filter(fn ($l) => ! empty($l['bank_account_name'])) as $pl) {
            $lbl = $pl['method_label'].' - '.$pl['bank_account_name'];
            if (! empty($pl['reference_no'])) $lbl .= ' / '.$pl['reference_no'];
            $paymentBreakdown[] = ['label' => $lbl, 'value' => $pl['amount']];
        }

        $summaryRows = [
            ['label' => 'الإجمالي قبل الضريبة', 'label_en' => '(Total Without Vat)', 'value' => $invoice->lines_total],
            ['label' => 'الخصم', 'label_en' => '(Discount Value)', 'value' => $invoice->discount_total],
            ['label' => 'ضريبة القيمة المضافة', 'label_en' => '(Add Value Vat)', 'value' => $invoice->taxes_total],
            ['label' => 'قيمة الفاتورة', 'label_en' => '(Total)', 'value' => $invoice->round_net_total ?: $invoice->net_total],
        ];

        $itemsPerFirstPage = $showInvoiceTerms ? 3 : 5;
        $allDetails = $invoice->details->values();
        $firstPageDetails = $allDetails->take($itemsPerFirstPage);
        $remainingDetails = $allDetails->skip($itemsPerFirstPage);
        $continuationChunks = $remainingDetails->chunk(8)->values();

        $previewNotice = $invoiceTermsService->currentDefaultDiffersFromInvoiceSnapshot($invoice)
            ? 'هذه الفاتورة تعرض نسخة الشروط المحفوظة وقت الإنشاء. أي تعديل جديد على الشروط يطبق على الفواتير الجديدة فقط.'
            : null;
        $backUrl = route($isPurchase ? 'purchases.index' : 'purchase_return.index');
        $whatsappUrl = ! empty($invoice->client_phone)
            ? route('send.invoice.whatsapp', $invoice->id)
            : null;
        $branchAddressAr = $branch->short_address ?: $branch->full_address ?: '---';
        $bgService  = app(\App\Services\Invoices\InvoiceBackgroundService::class)
            ->forBranch((int) $invoice->branch_id)
            ->forContext(
                \App\Services\Invoices\InvoiceBackgroundService::detectInvoiceTypeFromInvoice($invoice),
                \App\Services\Invoices\InvoiceBackgroundService::FORMAT_A5
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
    @include('admin.invoices.partials.print_a5_reference_styles')
    @include('admin.invoices.partials.print_dimension_vars', ['printSettings' => $printSettings, 'dimensionFormat' => 'a5'])
</head>
<body
    data-print-format="a5"
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $showHeader ? '1' : '0' }}"
    data-show-footer="{{ $showFooter ? '1' : '0' }}"
    data-paper-orientation="{{ $printOrientation }}"
    class="invoice-print-format-a5 invoice-template-{{ $printTemplate }} invoice-orientation-{{ $printOrientation }}{{ $compactStandalonePrint ? ' invoice-paper-ready' : '' }}"
>
@include('admin.invoices.partials.print_background', compact('bgImageUrl', 'bgScale', 'bgOffsetX', 'bgContentTop', 'bgContentBottom', 'bgContentWidth', 'bgContentScale', 'bgFontScale', 'bgHideHeader', 'bgHideFooter', 'bgPaperSize', 'bgPaperOrientation', 'bgRenderMode'))
    <div class="page">
        <div class="page-content">
            <div class="invoice-shell">
                @if($showHeader)
                    <section class="micro-header">
                        <div class="micro-header-block">
                            <span class="micro-header-title">{{ $companyNameAr }}</span>
                            <span>الفرع: {{ $branchNameAr }}</span>
                        </div>
                        <div class="micro-header-block">
                            <span>الرقم الضريبي: <span class="ltr">{{ $branch->tax_number ?: '---' }}</span></span>
                            <span>السجل التجاري: <span class="ltr">{{ $branch->commercial_register ?: '---' }}</span></span>
                        </div>
                    </section>
                @endif

                <section class="compact-head">
                    <div class="{{ ! empty($invoice->zatcaQrCode) ? 'compact-qr' : 'compact-qr is-placeholder' }}">
                        @if(! empty($invoice->zatcaQrCode))
                            <img src="{{ $invoice->zatcaQrCode }}" alt="QR Code">
                        @else
                            <span class="qr-placeholder">QR</span>
                        @endif
                    </div>

                    <div class="compact-title-block">
                        <h1 class="compact-title">{{ $documentTitle }}</h1>
                        <p class="compact-subtitle">{{ $documentTitleEn }}</p>
                    </div>

                    <div class="compact-meta">
                        <div class="compact-meta-row">
                            <span class="compact-meta-label">رقم الفاتورة :</span>
                            <span class="compact-meta-value ltr">{{ $invoice->bill_number }}</span>
                        </div>
                        <div class="compact-meta-row">
                            <span class="compact-meta-label">التاريخ :</span>
                            <span class="compact-meta-value ltr">{{ $formattedDate }}</span>
                        </div>
                        <div class="compact-meta-row">
                            <span class="compact-meta-label">الوقت :</span>
                            <span class="compact-meta-value ltr">{{ $formattedTime }}</span>
                        </div>
                        <div class="compact-meta-row">
                            <span class="compact-meta-label">نوع السداد :</span>
                            <span class="compact-meta-value">{{ $paymentTypeLabel }}</span>
                        </div>
                        <div class="compact-meta-row">
                            <span class="compact-meta-label">المورد :</span>
                            <span class="compact-meta-value">{{ $invoice->customerName ?: '---' }}</span>
                        </div>
                        @if($invoice->customerPhone)
                            <div class="compact-meta-row">
                                <span class="compact-meta-label">الجوال :</span>
                                <span class="compact-meta-value ltr">{{ $invoice->customerPhone }}</span>
                            </div>
                        @endif
                        @if($invoice->supplier_bill_number)
                            <div class="compact-meta-row">
                                <span class="compact-meta-label">مرجع المورد :</span>
                                <span class="compact-meta-value ltr">{{ $invoice->supplier_bill_number }}</span>
                            </div>
                        @endif
                    </div>
                </section>

                <table class="reference-table">
                    <thead>
                        <tr>
                            <th style="width: 4.5%;">
                                <span class="head-main">م</span>
                            </th>
                            <th style="width: 23%;">
                                <span class="head-main">وصف الصنف</span>
                                <span class="head-sub">(Item)</span>
                            </th>
                            <th style="width: 7%;">
                                <span class="head-main">العيار</span>
                                <span class="head-sub">(Karat)</span>
                            </th>
                            <th style="width: 8.5%;">
                                <span class="head-main">وزن الذهب</span>
                                <span class="head-sub">(Weight)</span>
                            </th>
                            <th style="width: 5.5%;">
                                <span class="head-main">العدد</span>
                                <span class="head-sub">(Count)</span>
                            </th>
                            <th style="width: 8.5%;">
                                <span class="head-main">ما خلا من المعدن</span>
                                <span class="head-sub">(Non Metal)</span>
                            </th>
                            <th style="width: 9%;">
                                <span class="head-main">سعر الجرام</span>
                                <span class="head-sub">(Gram Price)</span>
                            </th>
                            <th style="width: 9.5%;">
                                <span class="head-main">الإجمالي</span>
                                <span class="head-sub">(Total)</span>
                            </th>
                            <th style="width: 7%;">
                                <span class="head-main">الضريبة</span>
                                <span class="head-sub">(Vat)</span>
                            </th>
                            <th style="width: 17.5%;">
                                <span class="head-main">الإجمالي شامل الضريبة</span>
                                <span class="head-sub">(Total With Vat)</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($firstPageDetails as $index => $detail)
                            @php
                                $weight = $isPurchase
                                    ? ($detail->in_weight ?: $detail->out_weight)
                                    : $detail->out_weight;
                                $quantity = $detail->in_quantity ?: $detail->out_quantity ?: 1;
                                $nonMetal = $detail->no_metal_type === 'fixed'
                                    ? (float) $detail->no_metal
                                    : ((float) $weight * ((float) $detail->no_metal / 100));
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="description-cell">
                                    <span class="description-main">{{ strip_tags((string) $detail->item->title) }}</span>
                                </td>
                                <td>{{ $detail->carat_display_label ?: '---' }}</td>
                                <td><span class="ltr">{{ $fmtWeight($weight) }}</span></td>
                                <td><span class="ltr">{{ $quantity }}</span></td>
                                <td><span class="ltr">{{ $fmtWeight($nonMetal) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->unit_price) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->line_total) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->line_tax) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->round_net_total) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <section class="summary-grid">
                    <table class="summary-table payment-table">
                        <thead>
                            <tr>
                                <th colspan="2">طرق السداد</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($paymentBreakdown as $paymentRow)
                                <tr>
                                    <td class="summary-label">{{ $paymentRow['label'] }}</td>
                                    <td class="summary-value"><span class="ltr">{{ $fmtMoney($paymentRow['value']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <table class="summary-table invoice-summary-table">
                        <thead>
                            <tr>
                                <th colspan="2">ملخص الفاتورة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summaryRows as $summaryRow)
                                <tr>
                                    <td class="summary-label">
                                        {{ $summaryRow['label'] }}
                                        <span class="summary-sub">{{ $summaryRow['label_en'] }}</span>
                                    </td>
                                    <td class="summary-value"><span class="ltr">{{ $fmtMoney($summaryRow['value']) }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                @if($showInvoiceTerms)
                    <section class="terms-box">
                        <div class="terms-title">ملاحظات</div>
                        <div class="terms-content">{{ $inlineInvoiceTerms }}</div>
                    </section>
                @endif

                <section class="signatures">
                    <div class="signature-box">
                        <span class="signature-label">اسم الموظف</span>
                        <span class="signature-line">{{ $invoice->user->name ?: '------' }}</span>
                    </div>
                    <div class="signature-box">
                        <span class="signature-label">المورد</span>
                        <span class="signature-line">{{ $invoice->customerName ?: '------' }}</span>
                    </div>
                </section>
            </div>
        </div>

        @if($showFooter)
            <footer class="micro-footer">
                <div>{{ $branchAddressAr }}</div>
                <div class="ltr">{{ $branch->phone ?: '---' }}</div>
            </footer>
        @endif
    </div>

    @foreach($continuationChunks as $chunkIndex => $chunk)
    <div class="page" style="page-break-before: always;">
        <div class="page-content">
            <div class="invoice-shell">
                <div style="font-size:7.5px; margin-bottom:2mm; padding-bottom:1.5mm; border-bottom:1px solid #d5d9df; display:flex; justify-content:space-between;">
                    <span>{{ $documentTitle }} — {{ $invoice->bill_number }}</span>
                    <span>صفحة {{ $chunkIndex + 2 }}</span>
                </div>
                <table class="reference-table">
                    <thead>
                        <tr>
                            <th style="width: 4.5%;"><span class="head-main">م</span></th>
                            <th style="width: 23%;"><span class="head-main">وصف الصنف</span><span class="head-sub">(Item)</span></th>
                            <th style="width: 7%;"><span class="head-main">العيار</span><span class="head-sub">(Karat)</span></th>
                            <th style="width: 8.5%;"><span class="head-main">وزن الذهب</span><span class="head-sub">(Weight)</span></th>
                            <th style="width: 5.5%;"><span class="head-main">العدد</span><span class="head-sub">(Count)</span></th>
                            <th style="width: 8.5%;"><span class="head-main">ما خلا من المعدن</span><span class="head-sub">(Non Metal)</span></th>
                            <th style="width: 9%;"><span class="head-main">سعر الجرام</span><span class="head-sub">(Gram Price)</span></th>
                            <th style="width: 9.5%;"><span class="head-main">الإجمالي</span><span class="head-sub">(Total)</span></th>
                            <th style="width: 7%;"><span class="head-main">الضريبة</span><span class="head-sub">(Vat)</span></th>
                            <th style="width: 17.5%;"><span class="head-main">الإجمالي شامل الضريبة</span><span class="head-sub">(Total With Vat)</span></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($chunk->values() as $chunkDetailIndex => $detail)
                            @php
                                $index = $itemsPerFirstPage + ($chunkIndex * 8) + $chunkDetailIndex;
                                $weight = $isPurchase
                                    ? ($detail->in_weight ?: $detail->out_weight)
                                    : $detail->out_weight;
                                $quantity = $detail->in_quantity ?: $detail->out_quantity ?: 1;
                                $nonMetal = $detail->no_metal_type === 'fixed'
                                    ? (float) $detail->no_metal
                                    : ((float) $weight * ((float) $detail->no_metal / 100));
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="description-cell">
                                    <span class="description-main">{{ strip_tags((string) $detail->item->title) }}</span>
                                </td>
                                <td>{{ $detail->carat_display_label ?: '---' }}</td>
                                <td><span class="ltr">{{ $fmtWeight($weight) }}</span></td>
                                <td><span class="ltr">{{ $quantity }}</span></td>
                                <td><span class="ltr">{{ $fmtWeight($nonMetal) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->unit_price) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->line_total) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->line_tax) }}</span></td>
                                <td><span class="ltr">{{ $fmtMoney($detail->round_net_total) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endforeach

    @include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl', 'previewNotice', 'bgImageUrl', 'bgScale'))
</body>
</html>
