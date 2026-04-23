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
        $branchNameAr = method_exists($branch, 'getTranslation') ? ($branch->getTranslation('name', 'ar') ?: $branch->name) : $branch->name;
        $formattedDate = \Carbon\Carbon::parse($invoice->date)->format('d-m-Y');
        $formattedTime = $invoice->time ? \Carbon\Carbon::parse($invoice->time)->format('H:i') : now()->format('H:i');
        $fmtMoney = fn ($value) => number_format((float) $value, 2);
        $fmtWeight = fn ($value) => number_format((float) $value, 3);
        $printTemplate = $printSettings['template'] ?? 'classic';
        $showHeader = $printSettings['show_header'] ?? true;
        $showFooter = $printSettings['show_footer'] ?? true;
        $compactStandalonePrint = ! $showHeader && ! $showFooter;
        $printOrientation = $printSettings['orientation'] ?? ($compactStandalonePrint ? 'landscape' : 'portrait');
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

        $returnedTotal = $isSale ? $invoice->returned_total : 0;
        $hasReturns = $isSale && $returnedTotal > 0;
        $netAfterReturns = $isSale ? $invoice->net_after_returns : 0;

        $summaryRows = [
            ['label' => 'الإجمالي قبل الضريبة', 'label_en' => '(Total Without Vat)', 'value' => $invoice->lines_total],
            ['label' => 'الخصم', 'label_en' => '(Discount Value)', 'value' => $invoice->discount_total],
            ['label' => 'ضريبة القيمة المضافة', 'label_en' => '(Add Value Vat)', 'value' => $invoice->taxes_total],
            ['label' => 'قيمة الفاتورة', 'label_en' => '(Total)', 'value' => $invoice->round_net_total ?: $invoice->net_total],
        ];

        if ($hasReturns) {
            $summaryRows[] = ['label' => 'إجمالي المرتجعات (-)', 'label_en' => '(Returns)', 'value' => $returnedTotal, 'is_return' => true];
            $summaryRows[] = ['label' => 'الصافي بعد المرتجع', 'label_en' => '(Net After Returns)', 'value' => $netAfterReturns, 'is_net_after_return' => true];
        }

        $invoiceTerms = trim((string) ($invoice->invoice_terms ?? ''));
        $inlineInvoiceTerms = $invoiceTermsService->formatTermsForPrint($invoiceTerms);
        $showInvoiceTerms = $invoiceTermsService->shouldShowInvoiceTermsForInvoice($invoice);

        // Pagination: 3 items per first page if terms exist, 5 if no terms
        $itemsPerFirstPage = $showInvoiceTerms ? 3 : 5;
        $allDetails = $invoice->details->values();
        $firstPageDetails = $allDetails->take($itemsPerFirstPage);
        $remainingDetails = $allDetails->skip($itemsPerFirstPage);
        $continuationChunks = $remainingDetails->chunk(8)->values();

        $previewNotice = $invoiceTermsService->currentDefaultDiffersFromInvoiceSnapshot($invoice)
            ? 'هذه الفاتورة تعرض نسخة الشروط المحفوظة وقت الإنشاء. أي تعديل جديد على الشروط يطبق على الفواتير الجديدة فقط.'
            : null;
        $backUrl = route($isSale ? 'sales.index' : 'sales_return.index', $invoice->sale_type);
        $whatsappUrl = ! empty($invoice->client_phone)
            ? route('send.invoice.whatsapp', $invoice->id)
            : null;
        $branchAddressAr = $branch->short_address ?: $branch->full_address ?: '---';
    @endphp
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>{{ $documentTitle }} {{ $invoice->bill_number }}</title>
    @include('admin.invoices.partials.print_a5_reference_styles')
</head>
<body
    data-print-format="a5"
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $showHeader ? '1' : '0' }}"
    data-show-footer="{{ $showFooter ? '1' : '0' }}"
    data-paper-orientation="{{ $printOrientation }}"
    class="invoice-print-format-a5 invoice-template-{{ $printTemplate }} invoice-orientation-{{ $printOrientation }}{{ $compactStandalonePrint ? ' invoice-paper-ready' : '' }}"
>
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
                        <h1 class="compact-title">{{ $invoice->sale_type === 'standard' ? 'فاتورة ضريبية' : 'فاتورة ضريبية مبسطة' }}</h1>
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
                            <span class="compact-meta-label">العميل :</span>
                            <span class="compact-meta-value">{{ $invoice->customerName ?: 'عميل افتراضي' }}</span>
                        </div>
                        @if($invoice->customerPhone)
                            <div class="compact-meta-row">
                                <span class="compact-meta-label">الجوال :</span>
                                <span class="compact-meta-value ltr">{{ $invoice->customerPhone }}</span>
                            </div>
                        @endif
                        @if($invoice->customerIdentityNumber)
                            <div class="compact-meta-row">
                                <span class="compact-meta-label">الهوية :</span>
                                <span class="compact-meta-value ltr">{{ $invoice->customerIdentityNumber }}</span>
                            </div>
                        @endif
                        <div class="compact-meta-row">
                            <span class="compact-meta-label">رقم أمر البيع :</span>
                            <span class="compact-meta-value ltr">{{ $saleOrderNumber }}</span>
                        </div>
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
                                $weight = $isSale ? $detail->out_weight : $detail->in_weight;
                                $nonMetal = $detail->no_metal_type === 'fixed'
                                    ? (float) $detail->no_metal
                                    : ((float) $weight * ((float) $detail->no_metal / 100));
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="description-cell">
                                    <span class="description-main">{{ strip_tags((string) $detail->item->title) }}</span>
                                    @if(in_array($detail->item->inventory_classification, ['collectible', 'silver']))
                                        @if($detail->item->stone_size_1 || $detail->item->stone_size_2)
                                            <span class="description-sub">مقاس: {{ $detail->item->stone_size_1 }}{{ $detail->item->stone_size_2 ? '/' . $detail->item->stone_size_2 : '' }}</span>
                                        @endif
                                        @if($detail->item->stone_clarity || $detail->item->stone_color)
                                            <span class="description-sub">{{ $detail->item->stone_clarity }} {{ $detail->item->stone_color }}</span>
                                        @endif
                                        @if($detail->item->brand)
                                            <span class="description-sub">{{ $detail->item->brand }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $detail->carat_display_label ?: '---' }}</td>
                                <td><span class="ltr">{{ $fmtWeight($weight) }}</span></td>
                                <td><span class="ltr">{{ $detail->out_quantity ?: 0 }}</span></td>
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
                                <th colspan="2">طرق الدفع</th>
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
                                @php
                                    $isReturn = $summaryRow['is_return'] ?? false;
                                    $isNetAfterReturn = $summaryRow['is_net_after_return'] ?? false;
                                    $rowStyle = $isReturn ? 'color:#c0392b;font-weight:bold;' : ($isNetAfterReturn ? 'color:#27ae60;font-weight:bold;border-top:2px solid #27ae60;' : '');
                                @endphp
                                <tr style="{{ $rowStyle }}">
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
                        <div class="terms-title">شروط الفاتورة</div>
                        <div class="terms-content">{{ $inlineInvoiceTerms }}</div>
                    </section>
                @endif

                <section class="signatures">
                    <div class="signature-box">
                        <span class="signature-label">اسم البائع</span>
                        <span class="signature-line">{{ $invoice->user->name ?: '------' }}</span>
                    </div>
                    <div class="signature-box">
                        <span class="signature-label">مدير الفرع</span>
                        <span class="signature-line">------</span>
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
                                $weight = $isSale ? $detail->out_weight : $detail->in_weight;
                                $nonMetal = $detail->no_metal_type === 'fixed'
                                    ? (float) $detail->no_metal
                                    : ((float) $weight * ((float) $detail->no_metal / 100));
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td class="description-cell">
                                    <span class="description-main">{{ strip_tags((string) $detail->item->title) }}</span>
                                    @if(in_array($detail->item->inventory_classification, ['collectible', 'silver']))
                                        @if($detail->item->stone_size_1 || $detail->item->stone_size_2)
                                            <span class="description-sub">مقاس: {{ $detail->item->stone_size_1 }}{{ $detail->item->stone_size_2 ? '/' . $detail->item->stone_size_2 : '' }}</span>
                                        @endif
                                        @if($detail->item->stone_clarity || $detail->item->stone_color)
                                            <span class="description-sub">{{ $detail->item->stone_clarity }} {{ $detail->item->stone_color }}</span>
                                        @endif
                                        @if($detail->item->brand)
                                            <span class="description-sub">{{ $detail->item->brand }}</span>
                                        @endif
                                    @endif
                                </td>
                                <td>{{ $detail->carat_display_label ?: '---' }}</td>
                                <td><span class="ltr">{{ $fmtWeight($weight) }}</span></td>
                                <td><span class="ltr">{{ $detail->out_quantity ?: 0 }}</span></td>
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

    @include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl', 'previewNotice'))
</body>
</html>
