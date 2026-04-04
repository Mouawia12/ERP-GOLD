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
        $headerHeight = $printFormat === 'a5' ? '2.4cm' : '3cm';
        $qrWidth = $printFormat === 'a5' ? '110px' : '140px';
    @endphp
    <title>
         فاتورة ضريبية مبسطة رقم  {{$invoice -> bill_number}}
    </title>
    <meta charset="utf-8"/>
    <link href="{{asset('assets/css/bootstrap.min.css')}}" rel="stylesheet"/>
    <style type="text/css">
        @page {
            size: {{ strtoupper($printFormat) }} portrait;
            margin: 8mm;
        }

        @font-face {
            font-family: 'Almarai';
            src: url("{{asset('assets/fonts/Almarai.ttf')}}");
        }
        * {
            color: #000 !important;
        }

        body, html {
            color: #000;
            font-family: 'Almarai' !important;
            font-size: {{ $screenFontSize }} !important;
            font-weight: bold;
            margin: 0;
            padding: 6px;
            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .invoice-print-sheet {
            width: 100%;
            max-width: {{ $sheetWidth }};
            margin: 0 auto !important;
            page-break-inside: avoid;
        }

        .no-print {
            position: fixed;
            bottom: 0;
            color: #fff !important;
            left: 30px;
            width:200px !important;
            height: 40px !important;
            border-radius: 0;
            padding-top: 10px;
            z-index: 9999;
        }

        table thead tr, table tbody tr {
            border-bottom: 1px solid #aaa;
        }

        table {
            text-align: center;
            width: 100% !important;
            margin-top: 10px !important;
        }
        .print-brand-logo {
            width: {{ $printFormat === 'a5' ? '112px' : '140px' }};
            max-width: 100%;
            height: auto;
            max-height: {{ $printFormat === 'a5' ? '112px' : '140px' }};
            object-fit: contain;
        }
        .print-header-section {
            border: 2px solid #eee;
            border-radius: 10px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }

        .invoice-title-box {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 999px;
        }

        .invoice-print-qr {
            width: {{ $qrWidth }};
        }

        body.invoice-template-modern .print-header-section {
            border-color: #111827;
            background: linear-gradient(180deg, #f8fafc 0%, #eef2ff 100%);
        }

        body.invoice-template-modern .invoice-title-box {
            border: 2px solid #111827;
            background: #fff;
        }

        body.invoice-template-compact .print-header-section {
            padding: 8px 10px;
            border-radius: 6px;
        }

        body.invoice-template-compact table {
            margin-top: 6px !important;
        }

        body.invoice-template-compact .invoice-title-box {
            background: #f3f4f6;
        }

        body.invoice-template-classic .invoice-title-box {
            background: #f8fafc;
        }

        .print-footer-section {
            margin-top: 8px;
            padding-top: 8px;
            border-top: 1px dashed #cbd5e1;
        }

        .invoice-header-grid,
        .invoice-meta-grid {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }

        .invoice-header-grid {
            align-items: center;
        }

        .header-block,
        .meta-block {
            flex: 1 1 0;
        }

        .header-block.center,
        .meta-block.center {
            text-align: center;
        }

        .header-block.left,
        .meta-block.left {
            text-align: left;
        }

        .header-block.right,
        .meta-block.right {
            text-align: right;
        }

        .branch-meta-line,
        .invoice-meta-line {
            margin: 0 0 4px 0;
            line-height: 1.45;
        }

        .invoice-meta-line:last-child,
        .branch-meta-line:last-child {
            margin-bottom: 0;
        }

        .invoice-meta-section {
            margin-bottom: 8px;
        }

        .invoice-print-table {
            table-layout: fixed;
            margin-top: 6px !important;
            page-break-inside: avoid;
        }

        .invoice-print-table th,
        .invoice-print-table td {
            padding: 4px 5px !important;
            vertical-align: middle !important;
            line-height: 1.35;
            word-wrap: break-word;
        }

        .invoice-print-table thead th {
            font-size: 0.92em;
        }

        .invoice-payment-breakdown p {
            margin-bottom: 2px !important;
            line-height: 1.35;
        }

        .signature-line {
            min-height: 34px;
        }
    </style>
    <style type="text/css" media="print">
        table {
            text-align: center;
            width: 100% !important;
            margin-top: 10px !important;
        }
        .print-brand-logo {
            width: 128px;
            max-width: 100%;
            height: auto;
            max-height: 128px;
            object-fit: contain;
        }

        table thead tr, table tbody tr {
            border-bottom: 1px solid #aaa;
        }

        * {
            color: #000 !important;
        }

        body, html {
            color: #000;
            padding: 0px;
            margin: 0;
            font-family: 'Almarai' !important;
            font-size: {{ $printFontSize }} !important;
            font-weight: bold !important;
            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .invoice-print-sheet {
            width: 100% !important;
            max-width: {{ $sheetWidth }} !important;
            margin: 0 auto !important;
        }

        .pos_details {
            width: 100% !important;
            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .no-print {
            display: none;
        }

        .print-header-section {
            margin-bottom: 6px !important;
            break-inside: avoid;
        }

        .invoice-meta-section,
        .print-footer-section,
        .invoice-print-table {
            break-inside: avoid;
        }

        .invoice-print-table th,
        .invoice-print-table td {
            padding: 3px 4px !important;
        }
    </style>
</head>
<body
    dir="rtl"
    data-print-format="{{ $printFormat }}"
    data-print-template="{{ $printTemplate }}"
    data-show-header="{{ $printSettings['show_header'] ? '1' : '0' }}"
    data-show-footer="{{ $printSettings['show_footer'] ? '1' : '0' }}"
    class="text-center invoice-print-format-{{ $printFormat }} invoice-template-{{ $printTemplate }}"
    style="background: #fff;
    page-break-before: avoid;
    page-break-after: avoid;
    page-break-inside: avoid;"
>

<div class="pos_details  justify-content-center text-center">
    <div class="invoice-print-sheet text-center">
        @if($printSettings['show_header'])
        <header class="print-header-section" style="width: 100%; display: block; margin: auto;">
            <div class="invoice-header-grid">
                <div class="header-block right">
                    <p class="branch-meta-line">{{$invoice->branch->name}}</p>
                    <p class="branch-meta-line">س.ت : {{$invoice->branch->tax_number ?: '---'}}</p>
                    <p class="branch-meta-line">ر.ض : {{$invoice->branch->commercial_register ?: '---'}}</p>
                    <p class="branch-meta-line">تليفون : {{$invoice->branch->phone ?: '---'}}</p>
                </div>
                <div class="header-block center">
                    <img src="{{ $brandLogoUrl }}" class="print-brand-logo">
                </div>
                <div class="header-block left">
                </div>
            </div>
        </header>
        @endif
        <div class="invoice-meta-section" style="direction:rtl">
            <div class="invoice-meta-grid">
            <div class="meta-block right">
                <div class="invoice-meta-line">
                    رقم الفاتورة :
                    <span dir="ltr">
                        {{$invoice -> bill_number}}
                    </span>
                </div>
                <div class="invoice-meta-line">
                    التاريخ :
                    <span dir="ltr">
                         {{\Carbon\Carbon::parse($invoice -> date) -> format('d- m -Y') }}
                    </span>
                </div>
                <div class="invoice-meta-line">
                    الفرع :
                    <span dir="ltr">
                        {{$invoice -> branch->name}}
                    </span>
                </div>
                @if($invoice->customerName)
                <div class="invoice-meta-line">
                   {{__('main.bill_client_name')}} :
                    <span dir="ltr">
                        {{$invoice->customerName}}
                    </span>
                </div>
                @endif
                @if($invoice->customerPhone)
                <div class="invoice-meta-line">
                   {{__('main.bill_client_phone')}} :
                    <span dir="ltr">
                        {{$invoice->customerPhone}}
                    </span>
                </div>
                @endif
                @if($invoice->customerIdentityNumber)
                <div class="invoice-meta-line">
                   رقم الهوية :
                    <span dir="ltr">
                        {{$invoice->customerIdentityNumber}}
                    </span>
                </div>
                @endif
            </div>
            <div class="meta-block center">
                <h4 class="text-center mt-1 mb-0" style="font-weight: bold;">
                    <strong class="invoice-title-box">
                        @if($invoice->sale_type == 'standard')
                            @if($invoice->type == 'sale')
                                {{__('main.sales_standard')}}
                            @else
                                {{__('main.sales_standard_return')}}
                            @endif
                        @else
                            @if($invoice->type == 'sale')
                                {{__('main.sales_simplified')}}
                            @else
                                {{__('main.sales_simplified_return')}}
                            @endif
                        @endif
                    </strong>
                </h4>
            </div>
            <div class="meta-block left">
                <div class="visible-print text-left">
						 <img src="{{$invoice->zatcaQrCode}}" class="invoice-print-qr" alt="QR Code"/>

                </div>
            </div>
            </div>
        </div>

	        <table style="width: 100% ; direction: rtl" class="table-bordered invoice-print-table">
            <thead>
            <tr>
                <th class="text-center " >وصف الصنف
                    <br>(Item)
				</th>
                <th class="text-center " >كود الصنف
                    <br>(Item Code)
				</th>
                <th class="text-center " >العيار
                    <br>(Karat)</th>
                <th class="text-center " > وزن الذهب
                    <br>(Weight)</th>
				<th class="text-center " >العدد
                    <br>(Count Item)</th>
                <th class="text-center " >ما خلا من المعدن
                    <br>(Non Metal)</th>
                <th class="text-center " > سعر الجرام
                    <br>(Gram Price) </th>
                <th class="text-center " >الإجمالي
                    <br>(Total)</th>
                <th class="text-center " >الضريبة
                    <br> (Vat) </th>
                <th class="text-center " >الإجمالي شامل الضريبة
                    <br>(Total With Vat)</th>
            </tr>
            </thead>
            <tbody id="tbody">
            <?php $sum_total = 0 ?>
            <?php $sum_tax = 0 ?>
            <?php $sum_weight = 0 ?>
            @foreach($invoice->details as $detail)
            @php
                $weight = ($invoice->type == 'sale') ? $detail->out_weight : $detail->in_weight;
            @endphp
                <tr>
                    <td class="text-center" style="width: 65px;"> {!!$detail->item->title!!} </td>
                    <td class="text-center" style="width: 70px;"> {!!$detail->unit->barcode!!} </td>
                    <td class="text-center"> {{ $detail->carat_display_label }} </td>
                    <td class="text-center"> {{$weight}} </td>
					<td class="text-center"> {{$detail->out_quantity}} </td>
                    <td class="text-center"> {{ $detail->no_metal_type == 'fixed' ? $detail->no_metal : $weight * ($detail->no_metal / 100) }} </td>
                    <td class="text-center" > {{round($detail->unit_price, 2) }} </td>
                    <td class="text-center"> {{ round((float)$detail->line_total, 2) }} </td>
                    <td class="text-center"> {{round($detail->line_tax, 2) }} </td>
                    <td class="text-center"> {{round($detail->round_net_total, 2)}} </td>
                </tr>
            @endforeach
            <tr>
                <td class="text-center" colspan="2">{{round($invoice -> lines_total, 2)}}</td>
                <td class="text-center" colspan="4"> الاجمالي قبل الضريبة   (Total Without Vat)</td>
	                <td class="text-center invoice-payment-breakdown" colspan="3">
	                  <p class="mb-0">{{__('main.cash')}} :  {{ round((float) $invoice->cash_paid_total, 2) }}</p>
	                  <p class="mb-0">{{__('main.visa')}} :  {{ round((float) $invoice->credit_card_paid_total, 2) }}</p>
	                  <p class="mb-0">تحويل بنكي :  {{ round((float) $invoice->bank_transfer_paid_total, 2) }}</p>
	                  @foreach($invoice->payment_lines_breakdown as $paymentLine)
	                      @if($paymentLine['bank_account_name'])
	                          <p class="mb-0 small">
	                              {{ $paymentLine['method_label'] }} - {{ $paymentLine['bank_account_name'] }} :
	                              {{ number_format((float) $paymentLine['amount'], 2) }}
	                              @if($paymentLine['reference_no'])
                                  ({{ $paymentLine['reference_no'] }})
                              @endif
                          </p>
                      @endif
                  @endforeach
                </td>
            </tr>
            <tr>
                <td class="text-center" colspan="6"></td>
                <td class="text-center" colspan="3" rowspan="2">شروط الفاتورة</td>
            </tr>
            <tr>
                <td class="text-center" colspan="2">{{round($invoice -> taxes_total, 2)}}</td>
                <td class="text-center" colspan="4"> ضريبة القيمة المضافة  (Add Value Vat)</td>
                <td class="text-center" colspan="3" rowspan="3" style="white-space: pre-line; vertical-align: top; line-height: 1.7;">
                    {{ $invoice->invoice_terms ?: '---' }}
                </td>
            </tr>
            <tr>
                <td class="text-center"  colspan="2">{{round($invoice -> round_net_total, 2)}}</td>
                <td class="text-center"  colspan="4"> قيمة الفاتورة
                    <br>(Total)
                </td>
            </tr>
            <tr>
                <td class="text-center"  colspan="9">{{' '}}</td>
            </tr>
            </tbody>
        </table>
        @if($printSettings['show_footer'])
        <div class="row print-footer-section" style="direction:rtl">
            <div class="col-6 text-center">
                <span> اسم البائع</span> <br>
                <div class="signature-line">{{$invoice->user->name}}</div>
            </div>
            <div class="col-6 text-center">
                <span>  مدير الفرع</span> <br>
                <div class="signature-line">........</div>
            </div>
        </div>
        @endif
    </div>


</div>

@php
    $type = $invoice->sale_type;
    $route = $invoice->type == 'sale' ? 'sales.index' : 'sales_return.index';
    $backUrl = route($route, $type);
    $whatsappUrl = ! empty($invoice->client_phone)
        ? route('send.invoice.whatsapp', $invoice->id)
        : null;
@endphp
@include('admin.invoices.partials.print_controls', compact('printSettings', 'backUrl', 'whatsappUrl'))
</body>
</html>
