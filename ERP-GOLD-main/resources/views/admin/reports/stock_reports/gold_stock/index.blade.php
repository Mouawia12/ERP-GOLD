@extends('admin.layouts.master')
@section('content')
@can('employee.stock.show')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
    <!-- row opened -->
    <style>
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
                
                <div class="card shadow mb-4">
                    <div class="card-body px-0 pt-0 pb-2"> 
                        <div class="card-header py-3 " style="border:solid 1px gray">
                            <header>
                                <div class="row" style="direction: ltr;">
                                    <div class="col-4 text-left">   
                                        <br> 
                                        <button type="button" class="btn btn-primary btnPrint" id="btnPrint"><i class="fa fa-print"></i></button>
                                    </div>
                                    <div class="col-4 c"  id="card-header">
                                        <h4  class="alert alert-primary text-center"> 
                                            {{__('ميزان مراجعة مخزون ورصيد الذهب ')}} 
                                        </h4> 
                                        @if(isset($branch))
                                        <h5 class="text-center"> [ {{$branch->name}} ] </h5>
                                        @elseif(Auth::user()->is_admin)
                                        <h5 class="text-center"> [ {{__('main.all_branches')}} ] </h5>
                                        @else
                                        <h5 class="text-center">[<strong> {{Auth::user()->branch->name}} </strong> ]</h5>
                                        @endif
                                        
                                        <h5 class="text-center"> [ {{$periodFrom}} - {{$periodTo}} ]</h5>
                                    </div>
                                    <div class="col-4 c">
                                        <span style="text-align: right;">
                                            {{$company ? $company -> name_ar : ''}}
                                         <br>  س.ت : {{$company ? $company -> registrationNumber : ''}}
                                         <br>  ر.ض :  {{$company ? $company -> taxNumber : ''}}
                                         <br>  تليفون :   {{$company ? $company -> phone : ''}}
                                        </span>
                                    </div>
                                </div>
                            </header> 
                        </div>
                    </div> 
 
                        <div class="card-body"> 
                            <div class="table-responsive">
                                <h5 class="text-center"><b>[ {{__('main.gold_stock_by_carat')}} ]</b></h5>
                                <table class="display w-100  text-nowrap table-bordered" id="dataTable" 
                                   style="text-align: center;">
                                    <thead>
                                    <tr>
                                        @foreach($caratsTypes as $caratType)
                                            <th class="text-center text-white text-uppercase text-md-center font-weight-bolder opacity-7 ps-2 bg-info" colspan="{{count($carats->where('is_pure', ($caratType->key == 'pure' ? true : false))) * 2}}">{{$caratType->title}}</th>
                                        @endforeach
                                    </tr>
                                  
                                    <tr>
                                    @foreach($caratsTypes as $caratType)
                                        @foreach($carats->where('is_pure', ($caratType->key == 'pure' ? true : false))??[] as $carat)
                                            <th class="text-center" colspan="2">
                                                {{$carat->title}}
                                            </th>
                                        @endforeach
                                    @endforeach
                                    </tr>
                                    <tr>
                                    @foreach($caratsTypes as $caratType)
                                        @foreach($carats->where('is_pure', ($caratType->key == 'pure' ? true : false))??[] as $carat)
                                            <th  class="text-center  success">{{__('main.enter')}}</th>
                                            <th  class="text-center  danger">{{__('main.exit')}}</th>
                                        @endforeach
                                    @endforeach
                                    </tr> 
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach($caratsTypes as $caratType)
                                                @foreach($carats->where('is_pure', ($caratType->key == 'pure' ? true : false))??[] as $carat)
                                                    <td class="text-center" style="color: green">{{number_format($carat->getStock($periodFrom, $periodTo, $caratType->id,'in'), 3)}}</td>
                                                    <td class="text-center" style="color: red">{{number_format($carat->getStock($periodFrom, $periodTo, $caratType->id, 'out'), 3)}}</td> 
                                                @endforeach
                                            @endforeach
                                        </tr>
                                        <tr style="background: antiquewhite;">
                                            @foreach($caratsTypes as $caratType)
                                                @foreach($carats->where('is_pure', ($caratType->key == 'pure' ? true : false))??[] as $carat)
                                                    @php
                                                        $stockBalance = $carat->getStock($periodFrom, $periodTo, $caratType->id);
                                                        $stockType = $stockBalance < 0 ? __('main.exit') : __('main.enter');
                                                        $stockClass = $stockBalance < 0 ? 'text-danger' : 'text-success';
                                                    @endphp
                                                    <td class="text-center @if($stockBalance != 0) {{$stockClass}} @endif" colspan="2" style="font-size: 20px">{{number_format($stockBalance, 3) }}</td>
                                                @endforeach
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <hr>
                            <div class="table-responsive">
                                <h5 class="text-center"><b>[ {{__('main.gold_stock_by_21')}} ]</b></h5>
                                <table class="display w-100  text-nowrap table-bordered" id="dataTable" 
                                   style="text-align: center;">
                                    <thead>
                                    <tr>
                                        @foreach($caratsTypes as $caratType)
                                            <th class="text-center text-white text-uppercase text-md-center font-weight-bolder opacity-7 ps-2 bg-info" colspan="2">{{$caratType->title}}</th>
                                        @endforeach
                                    </tr>
                                  
                                    <tr>
                                    @foreach($caratsTypes as $caratType)
                                        <th class="text-center" colspan="2">
                                            {{$baseCarat->title}}
                                        </th>
                                    @endforeach
                                    </tr>
                                    <tr>
                                    @foreach($caratsTypes as $caratType)
                                        <th  class="text-center  success">{{__('main.enter')}}</th>
                                        <th  class="text-center  danger">{{__('main.exit')}}</th>
                                    @endforeach
                                    </tr> 
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach($caratsTypes as $caratType)
                                            <td class="text-center" style="color: green">{{number_format($baseCarat->getStockDependent($periodFrom, $periodTo, $caratType->id,'in'), 3)}}</td>
                                            <td class="text-center" style="color: red">{{number_format($baseCarat->getStockDependent($periodFrom, $periodTo, $caratType->id, 'out'), 3)}}</td> 
                                            @endforeach
                                        </tr>
                                        @php
                                            $total = 0;
                                        @endphp
                                        <tr style="background: antiquewhite;">
                                            @foreach($caratsTypes as $caratType)
                                                @php
                                                    $stockBalance = $baseCarat->getStockDependent($periodFrom, $periodTo, $caratType->id,null);
                                                    $total += $stockBalance;
                                                    $stockType = $stockBalance < 0 ? __('main.exit') : __('main.enter');
                                                    $stockClass = $stockBalance < 0 ? 'text-danger' : 'text-success';
                                                @endphp
                                                <td class="text-center @if($stockBalance != 0) {{$stockClass}} @endif" colspan="2" style="font-size: 20px">{{number_format($stockBalance, 3) }}</td>
                                            @endforeach
                                        </tr>
                                        <tr>
                                            <td colspan="6" style="font-size: 20px;text-align: center">
                                                <h2>الإجمالي</h2>
                                                <h2>{{number_format($total, 3)}}</h2>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div> 
        <!--/div-->

@endcan 
@endsection 
@section('js') 
<script type="text/javascript">
    let id = 0; 
    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        });

    });


    function printPage() {
        var css = '@page { size: landscape; }',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        style.media = 'print';

        if (style.styleSheet) {
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }

        head.appendChild(style);
        document.getElementById("main-header").style.display = 'none';
        document.getElementById("main-footer").style.display = 'none'; 
        document.getElementById("back-to-top").style.display = 'none';
        window.print();
        document.getElementById("main-header").style.display = 'block';
        document.getElementById("main-footer").style.display = 'block'; 
        document.getElementById("back-to-top").style.display = 'block';
    } 
</script>
@endsection 

 