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
        margin: 0 !important;
    }

    table {
        page-break-inside: avoid;
    }
    thead {
        display: table-header-group;
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
                                    {{__('main.purchases_report')}}
                                </h4>  
                                @if(isset($branch))
                                <h5 class="text-center"> [ {{$branch->name}} ] </h5>
                                @else
                                <h5 class="text-center"> [ {{__('main.all_branches')}} ] </h5>
                                @endif
                                <h5 class="text-center"> {{$periodFrom}} - {{$periodTo}} </h5>
                                
                            </div> 
                            <div class="col-3 text-left"> 
{{--                                <img src="{{  $company ?  $company -> logo ?   asset('uploads/CompanyInfo' . '/' . $company -> logo)   : URL::asset('assets/img/logo.png') : URL::asset('assets/img/logo.png')}}"   id="profile-img-tag" width="70px" height="70px" class="profile-img"/>--}}
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
                                    @foreach($details??[] as $detail)
                                        <tr>
                                            <td class="text-center">{{$loop -> index + 1}}</td>
                                            <td class="text-center">
                                            <a href="{{route('purchases.show' , $detail -> invoice_id)}}" target="_blank">{{$detail -> invoice -> bill_number}}</a>
                                            </td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($detail -> invoice -> date) -> format('d-m-Y')  }}</td>
                                            <td class="text-center">{{$detail -> invoice -> customer_name}}</td> 
                                            <td class="text-center">{{ $detail -> item->title }}</td>
                                            <td class="text-center">{{$detail -> carat->title}}</td>
                                            <td class="text-center">{{$detail -> out_weight}}</td>
                                            <td class="text-center">{{$detail -> unit_price}}</td> 
                                            <td class="text-center">{{$detail -> net_total}}</td> 
                                            <td class="text-center">{{ $detail -> line_total }}</td>
                                            <td class="text-center">{{ $detail ->line_tax }}</td>
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
                                            <td class="text-center">{{$sum_weight}}</td>
                                            <td class="text-center"></td>
                                            <td class="text-center">{{$sum_net}}</td> 
                                            <td class="text-center">{{$sum_total}}</td>
                                            <td class="text-center">{{$sum_tax}}</td>  
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
        document.getElementById("main-header").style.display = 'none';
        document.getElementById("main-footer").style.display = 'none';
        document.getElementById("card-header").style.display = 'none';
        document.getElementById("back-to-top").style.display = 'none';
        document.getElementById("example1").style.display = 'none';
        document.getElementById("d-table").style.display = 'none';
        window.print();
        document.getElementById("main-header").style.display = 'block';
        document.getElementById("main-footer").style.display = 'block';
        document.getElementById("card-header").style.display = 'block';
        document.getElementById("back-to-top").style.display = 'block';
        document.getElementById("example1").style.display = 'block';
        document.getElementById("d-table").style.display = 'block';
    }
</script> 
        
@endsection 



