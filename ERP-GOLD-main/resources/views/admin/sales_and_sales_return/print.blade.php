<!DOCTYPE html>
<html>
<head>
    @php
        $printSettings = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
        $printFormat = $printSettings['format'];
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
    <style type="text/css" media="screen">
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
            padding: 10px;
            page-break-before: avoid;
            page-break-after: avoid;
            page-break-inside: avoid;
        }

        .invoice-print-sheet {
            width: 100%;
            max-width: {{ $sheetWidth }};
            margin: 10px auto !important;
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
            margin-bottom: 14px;
        } 

        .invoice-print-qr {
            width: {{ $qrWidth }};
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
    </style>
</head>
<body
    dir="rtl"
    data-print-format="{{ $printFormat }}"
    data-show-header="{{ $printSettings['show_header'] ? '1' : '0' }}"
    data-show-footer="{{ $printSettings['show_footer'] ? '1' : '0' }}"
    class="text-center invoice-print-format-{{ $printFormat }}"
    style="background: #fff;
    page-break-before: avoid;
    page-break-after: avoid;
    page-break-inside: avoid;"
>
          
<div class="pos_details  justify-content-center text-center"> 
    <div class="invoice-print-sheet text-center">
        @if($printSettings['show_header'])
        <header class="print-header-section" style="width: 100%; display: block; margin: auto; min-height: {{ $headerHeight }};">
            <div class="row">
                <div class="col-4 text-right">   
                    <br> {{$invoice->branch->name}} 
                    <br>  س.ت : {{$invoice->branch->tax_number}}
                    <br>  ر.ض :  {{$invoice->branch->commercial_register}}
                    <br>  تليفون :   {{$invoice->branch->phone}} 
                </div> 
                <div class="col-4 c"> 
                    <img src="{{ $brandLogoUrl }}" class="print-brand-logo">
                </div> 
                <div class="col-4 c">
                </div>
            </div>
        </header>
        @endif
        <div class="row" id="" style="direction:rtl">
            <div class="col-4 text-right">
                <h6 class="text-right mt-1" style="font-weight: bold;">
                    رقم الفاتورة :
                    <span dir="ltr">
                        {{$invoice -> bill_number}}
                    </span>
                </h6>
                <h6 class="text-right mt-1" style="font-weight: bold;">
                    التاريخ :
                    <span dir="ltr">
                         {{\Carbon\Carbon::parse($invoice -> date) -> format('d- m -Y') }}
                    </span>
                </h6>
                <h6 class="text-right mt-1" style="font-weight: bold;"> 
                    الفرع :
                    <span dir="ltr">
                        {{$invoice -> branch->name}}
                    </span>
                </h6>
                @if($invoice->customerName)
                <h6 class="text-right mt-1" style="font-weight: bold;">
                   {{__('main.bill_client_name')}} : 
                    <span dir="ltr">
                        {{$invoice->customerName}}
                    </span>
                </h6>
                @endif
                @if($invoice->customerPhone)
                <h6 class="text-right mt-1" style="font-weight: bold;">
                   {{__('main.bill_client_phone')}} : 
                    <span dir="ltr">
                        {{$invoice->customerPhone}}
                    </span>
                </h6>
                @endif
                @if($invoice->customerIdentityNumber)
                <h6 class="text-right mt-1" style="font-weight: bold;">
                   رقم الهوية :
                    <span dir="ltr">
                        {{$invoice->customerIdentityNumber}}
                    </span>
                </h6>
                @endif
            </div>
            <div class="col-4 text-center">
                <h4 class="text-center mt-1" style="font-weight: bold;">
                    <strong> 
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
            <div class="col-4 text-left">
                <div class="visible-print text-left mt-1">
					 <img src="{{$invoice->zatcaQrCode}}" class="invoice-print-qr" alt="QR Code"/>
                    
                </div>
            </div>
            <div class="clearfix"> </div> 
        </div>
         
        <table style="width: 100% ; direction: rtl" class="table-bordered">
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
                <td class="text-center" colspan="3">
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
                <span>{{$invoice->user->name}}</span>
            </div>
            <div class="col-6 text-center">
                <span>  مدير الفرع</span> <br>
                <span>........</span>
            </div>
        </div>
        @endif
    </div>


</div> 

@php
    $type = $invoice->sale_type;
    $route = $invoice->type == 'sale' ? 'sales.index' : 'sales_return.index';
@endphp
<a href="{{route($route,$type)}}" class="no-print btn btn-md btn-danger"
   style="left:20px!important;">
    العودة الى النظام
</a>
<button onclick="window.print();" class="no-print btn btn-md btn-info"
    style="left:230px!important;">
    اضغط للطباعة
</button>
@if(!empty($invoice ->client_phone))
<a href="{{route('send.invoice.whatsapp',$invoice ->id)}}" class="no-print btn btn-md btn-success"
   style="left:450px!important;">
    ارسال الفاتورة واتس اب
</a>
@endif


<script src="{{asset('assets/js/jquery.min.js')}}"></script>

   <script>
    $(document).ready(function () {
        window.print();
    });
</script> 
</body>
</html>
