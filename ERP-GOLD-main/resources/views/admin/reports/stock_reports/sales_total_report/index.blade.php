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
    @media print {
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
</style>
<div class="row row-sm">
    <div class="col-xl-12">  
            <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-3 ">
                        <div class="card-header py-3 " id="head-right"  style="direction: rtl;border:solid 1px gray"> 
                          <div class="row">
                            <div class="col-3"> 
                            
                            </div>   
                            <div class="col-6 title text-center">
                                <h4  class="alert alert-primary text-center">
                                    {{ $reportTitle ?? __('main.sales_total_report') }}
                                </h4>
                                <h5 class="text-center"> [ الفرع: {{ $branchLabel ?? ($branch?->name ?: 'جميع الفروع') }} ] </h5>
                                <h5 class="text-center">  {{ $periodFrom . ' - ' . $periodTo}} </h5>
                            </div>
                            <div class="col-3 text-left">
                                <button type="button" class="btn btn-primary no-print" id="btnPrint" onclick="window.print()">
                                    <i class="fa fa-print"></i> طباعة
                                </button>
                            </div>
                          </div>
                        </div>
                        <div class="card-body"> 
                            <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                                <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;direction: rtl;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('main.bill_no')}}</th> 
                                            <th>{{__('main.date')}}</th>
                                            <th>{{__('main.client')}}</th> 
                                            <th>{{__('main.total_weight')}}</th>
                                            <th> {{__('main.net_money')}} </th> 
                                            <th> {{__('main.total_without_tax')}} </th>
                                            <th> {{__('main.tax')}} </th>  
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $sum_weight = 0 ?>
                                    <?php $sum_total = 0 ?>
                                    <?php $sum_tax = 0 ?>
                                    <?php $sum_made = 0 ?>
                                    <?php $sum_net = 0 ?>
                                    <?php $sum_discount = 0 ?>
                                    @foreach($sales??[] as $sale)
                                        <tr>
                                            <td class="text-center">{{$loop -> index + 1}}</td>
                                            <td class="text-center">
                                            <a href="{{route('sales.show' , $sale -> id)}}" target="_blank">{{$sale -> bill_number}}</a>
                                            </td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($sale -> date) -> format('d-m-Y')  }}</td>
                                            <td class="text-center">{{$sale -> customer_name}}</td>
                                            <td class="text-center">{{number_format(abs($sale->total_quantity), 3)}}</td>
                                            <td class="text-center">{{number_format($sale->round_net_total, 2)}}</td>
                                            <td class="text-center">{{number_format(round($sale->lines_total_after_discount, 2), 2)}}</td>
                                            <td class="text-center">{{number_format(round($sale->taxes_total, 2), 2)}}</td>  
                                        </tr>

                                        <?php $sum_weight += abs($sale->total_quantity) ?>
                                        <?php
                                        $sum_total += ($sale->lines_total_after_discount)
                                        ?>
                                        <?php $sum_tax += $sale->taxes_total ?>
                                        <?php $sum_net += $sale->net_total ?>
                                        <?php $sum_discount += $sale->discount ?>
                                    @endforeach 
                                    <tfoot>  
                                        <tr class="text-white bg-primary">  
                                            <td colspan="3"></td> 
                                            <td class="text-center">الإجمالي</td> 
                                            <td class="text-center">{{number_format($sum_weight, 3)}}</td>
                                            <td class="text-center">{{number_format(round($sum_net - $sum_discount, 2), 2)}}</td>
                                            <td class="text-center">{{number_format(round($sum_total, 2), 2)}}</td>
                                            <td class="text-center">{{number_format(round($sum_tax, 2), 2)}}</td>  
                                        </tr>
                                    </tfoot> 

                                    </tbody> 
                                </table>

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

<!-- Scroll to Top Button-->
   
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script> 
  

<!-- Page level custom scripts -->

<script type="text/javascript">
    let id = 0;


    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        });

    });

    function printPage(){
        var css = '@page { size: landscape; }',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        style.media = 'print';

        if (style.styleSheet){
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }

        head.appendChild(style);

        window.print();
    }
</script>
<script>
    $(document).ready(function () {
        document.title = "{{__('main.sales_total_report')}}";
    });
</script>
