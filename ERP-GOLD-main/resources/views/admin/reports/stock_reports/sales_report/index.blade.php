@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<!-- row opened -->
<style>
        table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
            direction: rtl;
            text-align:center;
        }
        body{
            direction: rtl; 
        }

@media print{
    @page {
        size: A4 landscape;
        margin: 10mm;
    }

    table {
        page-break-inside: auto;
    }
    thead {
        display: table-header-group;
    }
    tr {
        page-break-inside: avoid;
    }
}
.c{

    display: flex;
    justify-content: center;
    margin: 0;
    flex-direction: column;
    padding: 6px;
}
</style>

<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">   
            <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-3 ">
                    <div class="card-header py-3 " id="head-right"  style="direction: rtl;border:solid 1px gray"> 
                        <div class="row">
                            <div class="col-3"> 
                              
                            </div>   
                            <div class="col-6 title text-center"> 
                                <h4  class="alert alert-primary text-center">
                                    {{ $reportTitle ?? __('main.sales_report') }}
                                </h4>  
                                <h5 class="text-center"> [ {{ $branchLabel ?? ($branch?->name ?: __('main.all_branches')) }} ] </h5>
                                <h5 class="text-center"> {{$periodFrom}} - {{$periodTo}} </h5>
                                
                            </div> 
                            <div class="col-3 text-left">
                                <button type="button" class="btn btn-primary no-print" id="btnPrint" onclick="window.print()">
                                    <i class="fa fa-print"></i> طباعة
                                </button>
                            </div>
                        </div>
    
                        <div class="card-body"> 
                            <hr>
                            <div class="table-responsive hoverable-table" id="d-table"  style="direction: rtl;"> 
                                <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;direction: rtl;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('main.bill_no')}}</th>
                                            <th>{{__('main.date')}}</th>
                                            <th>{{__('main.client')}}</th>
                                            <th>{{__('main.item')}}</th>
                                            <th> {{__('main.carats')}} </th>
                                            <th> {{__('main.weight')}} </th>
                                            <th>{{__('main.price_gram')}}</th>
                                            <th> {{__('main.total_without_tax')}} </th>
                                            <th> {{__('main.tax')}} </th>
                                            <th> كاش </th>
                                            <th> شبكة </th>
                                            <th> {{__('main.net_money')}} </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $sum_weight = 0 ?>
                                    <?php $sum_total = 0 ?>
                                    <?php $sum_tax = 0 ?>
                                    <?php $sum_net = 0 ?>
                                    <?php $sum_cash = 0 ?>
                                    <?php $sum_network = 0 ?>
                                    <?php $seen_invoices = [] ?>
                                    @foreach($details??[] as $detail)
                                        <?php
                                            $inv_cash = $detail->invoice->cash_paid_total;
                                            $inv_network = $detail->invoice->credit_card_paid_total + $detail->invoice->bank_transfer_paid_total;
                                            if (!in_array($detail->invoice_id, $seen_invoices)) {
                                                $sum_cash += $inv_cash;
                                                $sum_network += $inv_network;
                                                $seen_invoices[] = $detail->invoice_id;
                                            }
                                        ?>
                                        <tr>
                                            <td class="text-center">{{$loop -> index + 1}}</td>
                                            <td class="text-center">
                                            <a href="{{route('sales.show' , $detail -> invoice_id)}}" target="_blank">{{$detail -> invoice -> bill_number}}</a>
                                            </td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($detail -> invoice -> date) -> format('d-m-Y')  }}</td>
                                            <td class="text-center">{{$detail -> invoice -> customer_name}}</td>
                                            <td class="text-center">{{ $detail -> item->title }}</td>
                                            <td class="text-center">{{$detail -> carat->title}}</td>
                                            <td class="text-center">{{number_format($detail->out_weight, 3)}}</td>
                                            <td class="text-center">{{number_format($detail->unit_price, 2)}}</td>
                                            <td class="text-center">{{number_format($detail->line_total, 2)}}</td>
                                            <td class="text-center">{{number_format($detail->line_tax, 2)}}</td>
                                            <td class="text-center">{{$inv_cash > 0 ? number_format($inv_cash, 2) : '-'}}</td>
                                            <td class="text-center">{{$inv_network > 0 ? number_format($inv_network, 2) : '-'}}</td>
                                            <td class="text-center">{{number_format($detail->net_total, 2)}}</td>
                                        </tr>
                                        <?php $sum_weight += $detail->out_weight ?>
                                        <?php $sum_total += ($detail->line_total) ?>
                                        <?php $sum_tax += $detail->line_tax ?>
                                        <?php $sum_net += $detail->net_total ?>

                                    @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="text-white bg-primary">
                                            <td colspan="5"></td>
                                            <td class="text-center">{{__('main.total')}}</td>
                                            <td class="text-center">{{number_format($sum_weight, 3)}}</td>
                                            <td class="text-center"></td>
                                            <td class="text-center">{{number_format($sum_total, 2)}}</td>
                                            <td class="text-center">{{number_format($sum_tax, 2)}}</td>
                                            <td class="text-center">{{number_format($sum_cash, 2)}}</td>
                                            <td class="text-center">{{number_format($sum_network, 2)}}</td>
                                            <td class="text-center">{{number_format($sum_net, 2)}}</td>
                                        </tr>
                                    </tfoot>  
                                </table>
                            </div>    
                            <div class="card">  
                                <div class="row"> 
                                    <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                                        <h2 class="text-center">الإجماليات حسب العيار</h2>
                                        <table class="table table-bordered"  width="100%" cellspacing="0">
                                            <thead>
                                            <tr>
                                                <th class="text-uppercase text-secondary text-md-center font-weight-bolder opacity-7">
                                                    #
                                                </th>
                                                <th> {{__('main.carats')}} </th>
                                                <th> {{__('main.quantity')}} </th>
                                                <th>{{__('main.weight')}}</th>
                                                <th> {{__('main.total_without_tax')}} </th>
                                                <th> {{__('main.gram_tax')}} </th>
                                                <th> {{__('main.made_Value')}} </th>
                                                <th> {{__('main.net_money')}} </th>
        
                                            </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($detailsByCarat ?? [] as $row)
                                                <tr>
                                                    <td class="text-center">{{$loop->iteration}}</td>
                                                    <td class="text-center">{{$row->carat_title}}</td>
                                                    <td class="text-center">{{$row->total_quantity}}</td>
                                                    <td class="text-center">{{number_format($row->total_weight, 3)}}</td>
                                                    <td class="text-center">{{number_format($row->total_line_total, 2)}}</td>
                                                    <td class="text-center">{{number_format($row->total_tax, 2)}}</td>
                                                    <td class="text-center">-</td>
                                                    <td class="text-center">{{number_format($row->total_net, 2)}}</td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- End of Main Content --> 
    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->
@endsection 
@section('js') 
<script type="text/javascript">
    let id = 0;
    document.title = "{{__('main.sales_report')}}";

    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        });

    });

    function printPage(){
        window.print();
    }
</script> 
        
@endsection 


