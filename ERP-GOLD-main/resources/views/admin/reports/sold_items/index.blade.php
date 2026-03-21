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
          <div class="card"> 
            <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-3 ">
                        <div class="card-header py-3 " id="head-right"  style="direction: rtl;border:solid 1px gray"> 
                          <div class="row">
                            <div class="col-3"> 
                                {{''}}
                               <br>  س.ت : {{''}}
                               <br>  ر.ض :  {{''}}
                               <br>  تليفون :   {{''}}  
                            </div>   
                            <div class="col-6 title text-center">
                                <h4  class="alert alert-primary text-center">
                                    {{__('main.sold_items_report')}}
                                </h4>
                                @if(isset($branch))
                                <h5 class="text-center"> [ {{$branch->name}} ] </h5>
                                @else
                                <h5 class="text-center"> [ جميع الفروع ] </h5>
                                @endif
                                <h5 class="text-center">  {{$periodFrom}} - {{$periodTo}} </h5>
                            </div>
                            <div class="col-3 text-left"> 
                                <img src="{{URL::asset('assets/img/logo.png')}}"   id="profile-img-tag" width="70px" height="70px" class="profile-img"/>
                            </div>   
                          </div>
                        </div>
                        <div class="card-body" style="direction: rtl;"> 
                            <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                                <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;direction: rtl;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('main.bill_no')}}</th>
                                            <th>{{__('main.date')}}</th> 
                                            <th>{{__('main.code')}}</th>
                                            <th>{{__('main.name_ar')}}</th>
                                            <th> {{__('main.carats')}} </th>
                                            <th> {{__('main.weight')}} </th> 
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $total = 0; ?>
                                    @foreach($itemsTransactions??[] as $transaction)
                                        <tr>
                                            <td class="text-center">{{$loop -> iteration}}</td>
                                            <td class="text-center">{{$transaction -> invoice -> id}}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($transaction -> invoice -> date) -> format('d-m-Y')  }}</td>
                                            <td class="text-center">{{$transaction -> item -> code}}</td>
                                            <td class="text-center">{{$transaction -> item -> title}}</td>
                                            <td class="text-center">{{ $transaction -> carat -> title }}</td>
                                            <td class="text-center">{{$transaction -> out_weight}}</td> 
                                        </tr>
                                        <?php $total += $transaction->out_weight; ?>
                                    @endforeach 
                                    </tbody> 
                                    <tfoot>  
                                        <tr class="text-white bg-primary">
                                            <td></td>
                                            <td>الإجمالي</td>
                                            <td colspan="4" class="text-center"></td>
                                            <td >{{$total}} </td>  
                                        </tr>
                                    </tfoot>   
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
 
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script> 
  
<!-- Page level custom scripts -->

<script type="text/javascript">
    let id = 0;


    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            print();

        });

    });
</script>
<script>
    $(document).ready(function () {
        document.title = "{{__('main.sold_items_report')}}";
    });
</script>
 

