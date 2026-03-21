@extends('admin.layouts.master') 
@section('content') 
@can('employee.gold_prices.show')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
    <style>
        table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
            direction: rtl;
            text-align:center;
        }
        body{
            direction: rtl; 
        }
        th.text-center {
            border: 1px solid #eee !important;
        }
  
    </style>
    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                        {{__('main.prices')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row mt-1 mb-1 text-center justify-content-center align-content-center"> 
                    @can('تعديل اسعار الذهب')
                        <a href="{{route('updatePrices')}}" class="d-none d-sm-inline-block btn btn-sm btn-primary shadow-sm" style="border-radius: 10px; margin:5px;: 5px;">
                            <i style="margin: 5px ; padding: 5px;"class="fas fa-cloud fa-sm text-white-50"></i>
                            {{__('main.update_pricing')}}
                        </a> 
                     
                        <a  id="createButton" class="d-none d-sm-inline-block btn btn-sm btn-info shadow-sm" style="border-radius: 10px; margin:5px;: 5px;">
                            <i style="margin: 5px ; padding: 5px;" class="fas fa-plus-circle fa-sm text-white-50"></i>  {{__('main.update_pricing_manual')}}
                        </a>
                    @endcan
                  </div>
                <div class="card-body px-0 pt-0 pb-2"  id="head-right" > 
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <h4  class="alert alert-info text-center">
                                {{__(' اسعار البورصة العالمية(ذهب)')}}
                            </h4>
                            <div class="table-responsive hoverable-table">
                                <table class="display w-100 table-bordered" id="stock_market" 
                                   style="text-align: center;"> 
                                    <tbody> 
                                        <tr>
                                            <td class="text-center">الطابع الزمني (timestamp)</td>
                                            <td class="text-center">{{\Carbon\Carbon::parse($stock_market->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">المعدن (metal)</td>
                                            <td class="text-center">{{$stock_market->metal}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">العملة  (currency)</td>
                                            <td class="text-center">{{$stock_market->currency}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">سعر الاونصة   (Ounce price)</td>
                                            <td class="text-center">{{$stock_market->price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">اغلاق سابق  (prev close price)</td>
                                            <td class="text-center">{{$stock_market->prev_close_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">الفتح  (open price)</td>
                                            <td class="text-center">{{$stock_market->open_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">اقل سعر  (low price)</td>
                                            <td class="text-center">{{$stock_market->low_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">اعلى سعر  (high price)</td>
                                            <td class="text-center">{{$stock_market->high_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">طلب  (ask)</td>
                                            <td class="text-center">{{$stock_market->ask}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عرض  (bid)</td>
                                            <td class="text-center">{{$stock_market->bid}}</td>
                                        </tr>  
                                        <tr>
                                            <td class="text-center">عيار 24 (price gram 24k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_24k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 22 (price gram 22k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_22k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 21 (price gram 21k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_21k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 20 (price gram 20k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_20k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 18 (price gram 18k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_18k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 16 (price gram 16k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_16k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 14 (price gram 14k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_14k,2)}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عيار 10 (price gram 10k) </td>
                                            <td class="text-center">{{round($stock_market->price_gram_10k,2)}}</td>
                                        </tr> 
                                   </tbody>
                                </table>
                            </div>
                        </div>  
                    </div>
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <h4  class="alert alert-info text-center">
                                {{__(' الاسعار الحالية المحدثة بالنظام')}}
                            </h4>
                            <div class="table-responsive hoverable-table">
                                <table class="display w-100 table-bordered" id="example1" 
                                   style="text-align: center;">
                                    <thead>
                                    <tr>
                                        <th>{{__('main.ounce')}}</th>
                                        <th>{{__('main.k14')}}</th>
                                        <th> {{__('main.k18')}} </th>
                                        <th> {{__('main.k21')}} </th>
                                        <th> {{__('main.k22')}} </th>
                                        <th> {{__('main.k24')}} </th>
                                        <th> {{__('main.currency')}} </th>
                                        <th> {{__('main.last_update')}} </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($goldPrices as $goldPrice)
                                            <tr>
                                                <td class="text-center">{{number_format($goldPrice -> ounce_price  , 2)}}</td>
                                                <td class="text-center">{{number_format($goldPrice -> ounce_14_price  , 2)}}</td>
                                                <td class="text-center">{{number_format($goldPrice -> ounce_18_price  , 2)}}</td>
                                                <td class="text-center">{{number_format($goldPrice -> ounce_21_price  , 2)}}</td>
                                                <td class="text-center">{{number_format($goldPrice -> ounce_22_price  , 2)}}</td>
                                                <td class="text-center">{{number_format($goldPrice -> ounce_24_price  , 2)}}</td>
                                                <td class="text-center">{{$goldPrice -> currency}}</td>
                                        <td class="text-center">{{$goldPrice -> last_update}}</td>
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
     <!--/div-->
<!-- Logout Modal-->
<div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.update_pricing_manual')}}</label>
                <button type="button" class="close modal-close-btn close-create"  data-bs-dismiss="modal"  aria-label="Close" >
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="paymentBody">
                <form   method="POST" action="{{ route('updatePricesManual') }}"
                        enctype="multipart/form-data" >
                    @csrf

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>{{ __('main.k21') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                <input type="text"  id="price21" name="price21"
                                       class="form-control"
                                       placeholder="0"  />
                            </div>
                        </div>

                    </div>


                    <div class="row">
                        <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                            <button type="submit" class="btn btn-labeled btn-primary"  >
                                {{__('main.save_btn')}}</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>
                <button type="button" class="close"  data-bs-dismiss="modal"  aria-label="Close" style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody">
                <img src="../assets/img/warning.png" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label  class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                            <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-check"></i></span>{{__('main.confirm_btn')}}</button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal"  >
                            <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-close"></i></span>{{__('main.cancel_btn')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endcan 
@endsection 
@section('js') 
<script type="text/javascript">
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#profile-img-tag').attr('src', e.target.result);

            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#image_url").change(function(){
        readURL(this);
    });
</script>

<script type="text/javascript">
    let id = 0 ;
    $(document).ready(function()
    {
        id = 0 ;
        $(document).on('click', '#createButton', function(event) {
            console.log('clicked');
            id = 0 ;
            event.preventDefault();
            let href = $(this).attr('data-attr');
            $.ajax({
                url: href,
                beforeSend: function() {
                    $('#loader').show();
                },
                // return the result
                success: function(result) {
                    $('#createModal').modal("show");
                    $(".modal-body #name_ar").val( "" );
                    $(".modal-body #name_en").val( "" );
                    $(".modal-body #label").val( "" );
                    $(".modal-body #stamp_value").val("");
                    $(".modal-body #id").val( 0 );

                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    console.log(error);
                    alert("Page " + href + " cannot open. Error:" + error);
                    $('#loader').hide();
                },
                timeout: 8000
            })
        });



        $(document).on('click', '.editBtn', function(event) {

            id = event.currentTarget.value ;
            event.preventDefault();
            $.ajax({
                type:'get',
                url:'getcarats' + '/' + id,
                dataType: 'json',

                success:function(response){
                    console.log(response);
                    if(response){
                        let href = $(this).attr('data-attr');
                        $.ajax({
                            url: href,
                            beforeSend: function() {
                                $('#loader').show();
                            },
                            // return the result
                            success: function(result) {
                                $('#createModal').modal("show");
                                if(response.image_url){
                                    var img =  '../images/Category/' + response.image_url ;

                                    $(".modal-body #profile-img-tag").attr('src' , img );
                                }

                                $(".modal-body #name_ar").val( response.name_ar );
                                $(".modal-body #name_en").val( response.name_en );
                                $(".modal-body #label").val( response.label );
                                $(".modal-body #stamp_value").val(response.stamp_value);
                                $(".modal-body #id").val( response.id );

                            },
                            complete: function() {
                                $('#loader').hide();
                            },
                            error: function(jqXHR, testStatus, error) {
                                console.log(error);
                                alert("Page " + href + " cannot open. Error:" + error);
                                $('#loader').hide();
                            },
                            timeout: 8000
                        })
                    } else {

                    }
                }
            });

        });
        $(document).on('click', '.deleteBtn', function(event) {
            id = event.currentTarget.value ;
            event.preventDefault();
            let href = $(this).attr('data-attr');
            $.ajax({
                url: href,
                beforeSend: function() {
                    $('#loader').show();
                },
                // return the result
                success: function(result) {
                    $('#deleteModal').modal("show");
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    console.log(error);
                    alert("Page " + href + " cannot open. Error:" + error);
                    $('#loader').hide();
                },
                timeout: 8000
            })
        });

        $(document).on('click' , '.cancel-modal' , function (event) {
            $('#deleteModal').modal("hide");
            id = 0 ;
        });
        $(document).on('click' , '.close-create' , function (event) {
            $('#createModal').modal("hide");
            id = 0 ;
        });



    });
    function confirmDelete(){
        url = '';
        document.location.href=url;
    }
    function EditModal(id){
        $.ajax({
            type:'get',
            url:'getCategory' + '/' + id,
            dataType: 'json',

            success:function(response){
                console.log(response);
                if(response){
                    let href = $(this).attr('data-attr');
                    $.ajax({
                        url: href,
                        beforeSend: function() {
                            $('#loader').show();
                        },
                        // return the result
                        success: function(result) {
                            $('#createModal').modal("show");
                            var img =  '../images/Category/' + response.image_url ;
                            $(".modal-body #profile-img-tag").attr('src' , img );
                            $(".modal-body #name").val( response.name );
                            $(".modal-body #code").val( response.code );
                            $(".modal-body #slug").val(response.slug);
                            $(".modal-body #description").val(response.description);
                            $(".modal-body #parent_id").val(response.parent_id);
                            $(".modal-body #id").val( response.id );
                            $(".modal-body #isGold").prop('checked' , response.isGold);


                        },
                        complete: function() {
                            $('#loader').hide();
                        },
                        error: function(jqXHR, testStatus, error) {
                            console.log(error);
                            alert("Page " + href + " cannot open. Error:" + error);
                            $('#loader').hide();
                        },
                        timeout: 8000
                    })
                } 
            }
        });
    }
</script>
@endsection 
