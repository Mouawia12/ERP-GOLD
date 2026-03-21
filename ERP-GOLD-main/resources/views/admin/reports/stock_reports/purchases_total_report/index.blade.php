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
                                    {{__('main.purchases_total_report')}}
                                </h4>
                                @if(isset($branch))
                                <h5 class="text-center"> [ {{$branch->name}} ] </h5>
                                @else
                                <h5 class="text-center"> [ جميع الفروع ] </h5>
                                @endif
                                <h5 class="text-center">  {{ $periodFrom . ' - ' . $periodTo}} </h5>
                            </div>
                            <div class="col-3 text-left"> 
                               {{-- <img src="{{  $company ?  $company -> logo ?   asset('uploads/CompanyInfo' . '/' . $company -> logo)   : URL::asset('assets/img/logo.png') : URL::asset('assets/img/logo.png')}}"   id="profile-img-tag" width="70px" height="70px" class="profile-img"/>--}}
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
                                    @foreach($purchases??[] as $purchase)
                                        <tr>
                                            <td class="text-center">{{$loop -> index + 1}}</td>
                                            <td class="text-center">
                                            <a href="{{route('purchases.show' , $purchase -> id)}}" target="_blank">{{$purchase -> bill_number}}</a>
                                            </td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($purchase -> date) -> format('d-m-Y')  }}</td>
                                            <td class="text-center">{{$purchase -> customer_name}}</td>
                                            <td class="text-center">{{abs($purchase -> total_quantity)}}</td> 
                                            <td class="text-center">{{$purchase -> net_total}}</td>
                                            <td class="text-center">{{$purchase -> lines_total_after_discount}}</td> 
                                            <td class="text-center">{{$purchase -> taxes_total}}</td>  
                                        </tr>

                                        <?php $sum_weight += abs($purchase->total_quantity) ?>
                                        <?php
                                        $sum_total += ($purchase->lines_total_after_discount)
                                        ?>
                                        <?php $sum_tax += $purchase->taxes_total ?>
                                        <?php $sum_net += $purchase->net_total ?>
                                        <?php $sum_discount += $purchase->discount ?>
                                    @endforeach 
                                    <tfoot>  
                                        <tr class="text-white bg-primary">  
                                            <td colspan="3"></td> 
                                            <td class="text-center">الإجمالي</td> 
                                            <td class="text-center">{{$sum_weight}}</td> 
                                            <td class="text-center">{{$sum_net - $sum_discount}}</td> 
                                            <td class="text-center">{{$sum_total}}</td>  
                                            <td class="text-center">{{$sum_tax}}</td>  
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


