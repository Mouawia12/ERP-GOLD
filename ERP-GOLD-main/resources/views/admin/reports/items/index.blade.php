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
                                {{__('main.item_list_report')}}
                                </h4> 
                                @if(isset($branch))
                                <h5 class="text-center"> [ {{$branch->name}} ] </h5>
                                @else
                                <h5 class="text-center"> [ جميع الفروع ] </h5>
                                @endif
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
                                        <th >
                                            #
                                        </th>
                                        <th class="">{{__('main.code')}}</th>
                                        <th >{{__('main.name_ar')}}</th>
                                        <th > {{__('main.category')}} </th>
                                        <th > {{__('main.carats')}} </th>
                                        <th > {{__('main.gram_made_value')}} </th>
                                        <th > {{__('main.no_metal')}} </th>
                                        <th > {{__('main.actual_balance')}} </th>
                                        <th > {{__('main.state')}} </th>

                                    </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td class="text-center">{{$loop -> index + 1}}</td>
                                            <td class="text-center">{{$item -> code}}</td>
                                            <td class="text-center">{{$item -> title}}</td>
                                            <td class="text-center">{{$item -> category -> title}}</td>
                                            <td class="text-center">{{$item -> goldCarat -> title}}</td>
                                            <td class="text-center">{{$item -> labor_cost_per_gram}}</td>
                                            <td class="text-center">{{$item -> no_metal}}</td>
                                            <td class="text-center">{{$item -> actual_balance}}</td>
                                            <td class="text-center">{{$item -> status ? __('main.state1')  : __('main.state2')}}</td>

                                        </tr>
                                    @endforeach
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
<!-- End of Page Wrapper --> 
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script>

<script type="text/javascript">
    let id = 0;


    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            print();

        });

    });
</script>
 



