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
        text-align: center;
    }

    body {
        direction: rtl;
    }
</style>

<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0 text-center" id="head-right">
                <div class="col-lg-12 margin-tb text-center">
                    <h4 class="alert alert-primary text-center">
                        [ {{__('main.'.$type.'s')}} ]
                    </h4>

                </div>
            </div>
            <div class="col-lg-12 margin-tb text-center">
                <button type="button" class="btn btn-labeled btn-info " id="createButton">
                    <span class="btn-label" style="margin-right: 10px;">
                        <i class="fa fa-plus"></i></span>
                    {{__('main.add_new')}}
                </button>
            </div>
            <div class="card-body px-0 pt-0 pb-2">

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="display w-100  text-nowrap table-bordered" id="example1"
                                style="text-align: center;">
                                <thead>
                                    <tr>
                                        <th>{{__('main.date')}}</th>
                                        <th> {{__('main.basedon_no')}} </th>
                                        <th> {{__('main.from')}} </th>
                                        <th> {{__('main.to')}} </th>
                                        <th> {{__('main.total_money')}} </th>
                                        <th>{{__('main.actions')}}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($vouchers??[] as $voucher)
                                    <tr>
                                        <td class="text-center">{{$voucher -> date}}</td>
                                        <td class="text-center">{{$voucher -> bill_number}}</td>
                                        <td class="text-center">{{$voucher -> fromAccount->name??'-' }}</td>
                                        <td class="text-center">{{$voucher -> toAccount->name??'-' }}</td>
                                        <td class="text-center">{{$voucher -> total_amount}}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-labeled btn-secondary editBtn" value="">
                                                <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-eye"></i></span>{{__('main.preview')}}</button>
                                        </td>
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
</div>
<!-- End of Page Wrapper -->

<div class="modal fade" id="createModal" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.'.$type.'_create')}}</label>
                <button type="button" class="close modal-close-btn close-create" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="paymentBody">
                <form method="POST" action="{{route('financial_vouchers.store',$type)}}" id="voucherForm"
                    enctype="multipart/form-data">
                    @csrf
                    <div class="response_container"></div>
                    <div class="row">
                        <div class="col-3">
                            <div class="form-group">
                                <label>{{ __('main.date') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                <input type="date" id="date" name="date"
                                    class="form-control" />
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label class="d-block">
                                    الفرع <span style="color:red; font-size:20px; font-weight:bold;">*</span>
                                </label>
                                @if(empty(Auth::user()->branch_id))
                                <select required class="form-control select2" name="branch_id" id="branch_id">
                                    <option value="">حدد الاختيار</option>
                                    @foreach($branches as $branch)
                                    <option value="{{$branch->id}}">{{$branch->name}}</option>
                                    @endforeach
                                </select>
                                @else
                                <input class="form-control" type="text" readonly
                                    value="{{Auth::user()->branch->name}}" />
                                <input required class="form-control" type="hidden" id="branch_id"
                                    name="branch_id"
                                    value="{{Auth::user()->branch_id}}" />
                                @endif

                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 ">
                            <div class="form-group">
                                <label>{{ __('main.from') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                <select id="from_account" name="from_account_id" class="js-example-basic-single w-100">
                                    @foreach($accounts??[] as $account)
                                    <option value="{{$account -> id}}">{{$account -> name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="form-group">
                                <label>{{ __('main.to') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                <select id="to_account" name="to_account_id" class="js-example-basic-single w-100">
                                    @foreach($accounts??[] as $account)
                                    <option value="{{$account -> id}}">{{$account -> name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <div class="form-group">
                                <label>{{ __('main.money') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                <input class="form-control" id="amount" name="total_amount" type="number">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12 ">
                            <div class="form-group">
                                <label>{{ __('main.notes') }} </label>
                                <textarea type="text" id="notes" name="description" class="form-control" placeholder="{{ __('main.notes') }}"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                            <button type="submit" class="btn btn-labeled btn-primary" id="submitBtn">
                                {{__('main.save_btn')}}
                            </button>
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
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody">
                <img src="{{asset('assets/img/warning.png')}}" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                            <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-check"></i></span>{{__('main.confirm_btn')}}</button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal">
                            <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-close"></i></span>{{__('main.cancel_btn')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
<script type="text/javascript">
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#profile-img-tag').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#image_url").change(function() {
        readURL(this);
    });
</script>

<script type="text/javascript">
    let id = 0;
    $(document).ready(function() {
        var now = new Date();
        var day = ("0" + now.getDate()).slice(-2);
        var month = ("0" + (now.getMonth() + 1)).slice(-2);
        var today = now.getFullYear() + "-" + (month) + "-" + (day);

        document.title = "{{__('main.'.$type.'s')}}";

        $(document).on('click', '#createButton', function(event) {
            $('#createModal').modal("show");
        });


        $(document).on('submit', '#voucherForm', function(event) {
            event.preventDefault();
            let formData = new FormData(this);
            $.ajax({
                type: 'POST',
                url: '{{ route("financial_vouchers.store",$type) }}',
                data: formData,
                processData: false,
                contentType: false,
                beforeSend: function() {
                    $('#submitBtn').prop('disabled', true);
                    $('.response_container').html('');
                },
                success: function(result) {
                    $('#voucherForm')[0].reset();
                    $('#submitBtn').prop('disabled', false);
                    var message = "<div class='alert alert-success'><ul style='margin: 0;'>";
                    message += "<li>" + result.message + "</li>";
                    message += "</ul></div>";
                    $('.response_container').append(message);
                    setTimeout(function() {
                        $('#createModal').modal("hide");
                        $('.response_container').html('');
                        window.location.reload();
                    }, 2000);
                },
                error: function(jqXHR, testStatus, error) {
                    var errors = "<div class='alert alert-danger'><ul style='margin: 0;'>";
                    jqXHR.responseJSON.errors.forEach(function(error) {
                        errors += "<li>" + error + "</li>";
                    });
                    errors += "</ul></div>";
                    $('.response_container').append(errors);
                    $('#submitBtn').prop('disabled', false);
                }
            });
        });
        $(document).on('click', '.cancel-modal', function(event) {
            $('#deleteModal').modal("hide");
            id = 0;
        });
        $(document).on('click', '.close-create', function(event) {
            $('#createModal').modal("hide");
            id = 0;
        });

        $(document).on('click', '#printtBtn', function(event) {
            let url = "";
            let val = document.getElementById('id').value;
            url = "";
            url = url.replace(':id', val);
            document.location.href = url;
        });

    });

    function confirmDelete() {
        let url = "";
        url = url.replace(':id', id);
        document.location.href = url;
    }
</script>
@endsection