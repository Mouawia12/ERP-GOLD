@extends('admin.layouts.master') 
@section('content')
@canany(['employee.customers.show','employee.suppliers.show' ]) 
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
        select option {
        font-size: 15px !important;
    }

    .select2-container{
        width:100% !important;
    }

    span.select2-selection.select2-selection--single{
        padding:2px;
    }
    </style>  

    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0" id="head-right" >
                    <div class="col-lg-12 margin-tb text-center">
                        <h4  class="alert alert-primary text-center">
                         [ {{$type == 'customer' ? __('main.customers') : __('main.suppliers')}} ] 
                        </h4>
                        @canany(['employee.customers.add','employee.suppliers.add' ])     
                            <button type="button" class="btn btn-labeled btn-info " id="createButton">
                                <span class="btn-label" style="margin-right: 10px;">
                                <i class="fa fa-plus"></i></span>
                                {{__('main.add_new')}}
                            </button> 
                        @endcan 
                    </div>
                    <div class="clearfix"></div>
                </div> 
                <div class="card-body px-0 pt-0 pb-2">

                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('main.customer_name')}}</th> 
                                            <th>{{__('main.phone')}}</th>
                                            <th>{{__('main.email')}}</th> 
                                            <th>{{__('main.vat_no')}}</th>
                                            <th>{{__('main.actions')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($customers??[] as $customer)
                                            <tr>
                                                <td class="text-center">{{$loop -> index +1}}</td>
                                                <td class="text-center">{{$customer -> name}}</td> 
                                                <td class="text-center">{{$customer -> phone}}</td>
                                                <td class="text-center">{{$customer -> email}}</td> 
                                                <td class="text-center">{{$customer -> tax_number}}</td>
                                                <td class="text-center">

                                                @canany(['employee.customers.edit','employee.suppliers.edit'])    
                                                <button type="button" class="btn btn-labeled btn-info editBtn"
                                                    url="{{route('customers.get', $customer->id)}}" > 
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </button>
                                                @endcanany 
                                                @canany(['employee.customers.delete','employee.suppliers.delete'])
                                                <button type="button" class="btn btn-labeled btn-danger deleteBtn"  value="{{$customer -> id}}"
                                                    >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                                @endcanany 
                                            </td>
                                            </tr>
                                    @endforeach 
                                </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <!--/div-->

<div class="modal fade" id="createModal"  tabindex="-1"  role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{$type == 'customer' ? __('main.create_client') : __('main.create_supplier')}}</label>
                <button type="button" class="close modal-close-btn close-create"  data-bs-dismiss="modal"  aria-label="Close" >
                        <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="paymentBody">
                <div class="response_container">
                    
                </div>
                <form id="createForm"   method="POST" action="{{ route('customers.store' , $type) }}"
                        enctype="multipart/form-data" >
                    @csrf

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label>{{ $type == 'customer' ? __('main.customer_name') : __('main.supplier_name') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                <input type="text"  id="name" name="name"
                                       class="form-control"
                                       placeholder="{{ $type == 'customer' ? __('main.customer_name') : __('main.supplier_name') }}"  />
                                <input type="text"  id="id" name="id"
                                       class="form-control"
                                       placeholder="{{ __('main.code') }}"  hidden=""/>
                            </div>
                        </div>
                        <div class="col-6 " hidden>
                            <div class="form-group">
                                <input type="text"  id="type" name="type"
                                       class="form-control" value="{{$type}}"
                                       placeholder="{{ __('main.name') }}"  hidden />

                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.phone') }}</label>
                                <input type="text"  id="phone" name="phone"
                                       class="form-control"
                                       placeholder="{{ __('main.phone') }}"  />
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.email') }}</label>
                                <input type="text"  id="email" name="email"
                                       class="form-control"
                                       placeholder="{{ __('main.email') }}"  />
                            </div>
                        </div>
                    </div>
                    <div class="row" id="up-referral" style="display:none;">  
                        <div  class="col-12 " >
                            <div class="form-group">
                                <label>{{ __('main.account') }} </label>
                                <select class="js-example-basic-single w-100"
                                        name="account_id" id="account_id">
                                    <option selected value ="0">Choose...</option>
                                    @foreach ($accounts as $item)
                                        <option value="{{$item -> id}}"> {{ $item -> name}}</option> 
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.vat_no') }} </label>
                                <input type="text"  id="vat_no" name="vat_no"
                                       class="form-control"
                                       placeholder="{{ __('main.vat_no') }}"  />
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.opening_balance') }}</label>
                                <input type="number" step="any"  id="opening_balance" name="opening_balance"
                                       class="form-control" 
                                       value="0" />
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.region') }}</label>
                                <textarea type="text"  id="region" name="region" class="form-control" placeholder="{{ __('main.region') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.city') }}</label>
                                <textarea type="text"  id="city" name="city" class="form-control" placeholder="{{ __('main.city') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.district') }}</label>
                                <textarea type="text"  id="district" name="district" class="form-control" placeholder="{{ __('main.district') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.street_name') }}</label>
                                <textarea type="text"  id="street_name" name="street_name" class="form-control" placeholder="{{ __('main.street_name') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.building_number') }}</label>
                                <textarea type="text"  id="building_number" name="building_number" class="form-control" placeholder="{{ __('main.building_number') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.plot_identification') }}</label>
                                <textarea type="text"  id="plot_identification" name="plot_identification" class="form-control" placeholder="{{ __('main.plot_identification') }}"></textarea>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>{{ __('main.postal_code') }}</label>
                                <textarea type="text"  id="postal_code" name="postal_code" class="form-control" placeholder="{{ __('main.postal_code') }}"></textarea>
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


<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>

            </div>
            <div class="modal-body" id="smallBody">
                <img src="../../assets/img/warning.png" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-check"></i></span>{{__('main.confirm_btn')}}</button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-close"></i></span>{{__('main.cancel_btn')}}</button>
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
    let id = 0 ;
    document.title = "{{$type == 'customer' ? __('main.customers') : __('main.suppliers')}}";
    $(document).ready(function(){
        id = 0 ;
        $(document).on('submit', '#createForm', function(event) {
            id = 0 ;
            event.preventDefault();
            var thisme = $(this);
            let href = $(this).attr('action');
            let method = $(this).attr('method');
            $.ajax({
                url: href,
                type: method,
                data: $(this).serialize(),
                beforeSend: function() {
                    $('.response_container').html('');
                    $('#loader').show();
                },
                success: function(result) {
                    var message = "<div class='alert alert-success'><ul style='margin: 0;'>";
                    message += "<li>" + result.message + "</li>";
                    message += "</ul></div>";
                    $('.response_container').append(message);
                  setTimeout(function() {
                    $('#createModal').modal("hide");
                    thisme[0].reset();
                    $('.response_container').html('');
                    window.location.reload();
                  }, 2000);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    var errors = "<div class='alert alert-danger'><ul style='margin: 0;'>";
                    jqXHR.responseJSON.errors.forEach(function(error) {
                        errors += "<li>" + error + "</li>";
                    });
                    errors += "</ul></div>";
                    $('.response_container').append(errors);
                },
                timeout: 8000
            })
        });
        $(document).on('click', '#createButton', function(event) {
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
                    $(".modal-body #company").val( "" );
                    $(".modal-body #name").val( "" );
                    $(".modal-body #phone").val( "" );
                    $(".modal-body #email").val( "" );
                    $(".modal-body #account_id").val( "" ).trigger("change");;
                    $(".modal-body #vat_no").val( "" );
                    $(".modal-body #opening_balance").val(0);
                    try {
                        $(".modal-body #customer_group_id").val( "" );
                        $(".modal-body #credit_amount").val( "" );
                        $(".modal-body #stop_sale").prop('checked' ,0);
                    }catch (err){

                    }
                    $(".modal-body #address").val( "" );
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

        $(document).on('click', '.deleteBtn', function(event) {
            id = event.currentTarget.value ;
            console.log(id);
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
        let url = "{{ route('customers.delete', ':id') }}";
        url = url.replace(':id', id);
        $.ajax({
            url: url,
            type: 'POST',
            beforeSend: function() {
                $('#loader').show();
            },
            // return the result
            success: function(result) {
                $('#deleteModal').modal("hide");
                window.location.reload();
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
        });
    }

    $(document).on('click', '.editBtn', function (event) {
        event.preventDefault();
        var url = $(this).attr('url');
        $.ajax({
            type:'get', 
            url: url,
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
                            $(".modal-body #name").val( response.name );
                            $(".modal-body #phone").val( response.phone );
                            $(".modal-body #email").val( response.email );
                            $(".modal-body #id").val( response.id );
                            $(".modal-body #type").val( response.type );
                            $(".modal-body #account_id").val( response.account_id );
                            $(".modal-body #vat_no").val( response.tax_number );
                            $(".modal-body #region").val( response.region );
                            $(".modal-body #city").val( response.city );
                            $(".modal-body #district").val( response.district );
                            $(".modal-body #street_name").val( response.street_name );
                            $(".modal-body #building_number").val( response.building_number );
                            $(".modal-body #plot_identification").val( response.plot_identification );
                            $(".modal-body #postal_code").val( response.postal_code );
                            $('#createModal').modal("show");
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
        $('.js-example-basic-single').select2({
        placeholder: "اختر مما يلى",
        
    });
 
    });
</script>  
@endsection 
 