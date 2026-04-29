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

    .response_container .alert {
        margin-bottom: 1rem;
    }

    #createForm .invalid-feedback {
        text-align: right;
    }
    </style>  

    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0" id="head-right" >
                    <div class="col-lg-12 margin-tb text-center">
                        <h4 class="alert alert-primary text-center">
                            [ {{ $type == 'customer' ? __('main.customers') : __('main.suppliers') }} ]
                        </h4>
                        @canany(['employee.customers.add','employee.suppliers.add'])
                            <button type="button" class="btn btn-labeled btn-info" id="createButton">
                                <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-plus"></i></span>
                                {{__('main.add_new')}}
                            </button>
                        @endcanany
                        <form method="GET" action="{{ route('customers', ['type' => $type]) }}" class="mt-3">
                            <div class="row justify-content-center">
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-group">
                                        <input type="text" name="identity_number" class="form-control text-right"
                                            placeholder="بحث برقم الهوية"
                                            value="{{ $identityNumber ?? '' }}">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-outline-primary">بحث</button>
                                            <a href="{{ route('customers', ['type' => $type]) }}" class="btn btn-outline-secondary">مسح</a>
                                        </div>
                                    </div>
                                    @if(!empty($identityNumber))
                                        <small class="text-muted d-block mt-2">التصفية الحالية على رقم الهوية: {{ $identityNumber }}</small>
                                    @endif
                                </div>
                            </div>
                        </form>
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
                                            <th>طرف نقدي</th>
                                            <th>رقم الهوية</th>
                                            <th>{{__('main.email')}}</th> 
                                            <th>{{__('main.vat_no')}}</th>
                                            <th>{{ __('main.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($customers??[] as $customer)
                                            <tr>
                                                <td class="text-center">{{$loop -> index +1}}</td>
                                                <td class="text-center">{{$customer -> name}}</td> 
                                                <td class="text-center">{{$customer -> phone}}</td>
                                                <td class="text-center">
                                                    @if($customer->is_cash_party)
                                                        <span class="badge badge-success">نقدي</span>
                                                    @else
                                                        <span class="badge badge-light">عادي</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">{{$customer -> identity_number}}</td>
                                                <td class="text-center">{{$customer -> email}}</td> 
                                                <td class="text-center">{{$customer -> tax_number}}</td>
                                                <td class="text-center">
                                                    @can('employee.suppliers.show')
                                                    <a href="{{ route('customers.report', $customer->id) }}"
                                                        class="btn btn-success btn-sm">
                                                        <i class="fa fa-chart-bar"></i> تقرير تفصيلي
                                                    </a>
                                                    @endcan
                                                    @canany(['employee.customers.edit','employee.suppliers.edit'])
                                                    <button type="button" class="btn btn-labeled btn-info editBtn"
                                                        url="{{route('customers.get', $customer->id)}}">
                                                        <i class="fa-regular fa-pen-to-square"></i>
                                                    </button>
                                                    @endcanany
                                                    @canany(['employee.customers.delete','employee.suppliers.delete'])
                                                    <button type="button" class="btn btn-labeled btn-danger deleteBtn"
                                                        value="{{$customer->id}}">
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
                <div class="response_container mb-3">
                    
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
                        <div class="col-6">
                            <div class="form-group text-right pt-4">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="is_cash_party" name="is_cash_party" value="1">
                                    <label class="custom-control-label" for="is_cash_party">تصنيف كطرف نقدي</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 " >
                            <div class="form-group">
                                <label>رقم الهوية</label>
                                <input type="text"  id="identity_number" name="identity_number"
                                       class="form-control"
                                       placeholder="رقم الهوية"  />
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
    let id = 0;
    document.title = @json($type == 'customer' ? __('main.customers') : __('main.suppliers'));

    $(document).ready(function(){
        const $createModal = $('#createModal');
        const $createForm = $('#createForm');
        const $responseContainer = $('.response_container');
        const createTitle = @json($type == 'customer' ? __('main.create_client') : __('main.create_supplier'));
        const editTitle = @json($type == 'customer' ? 'تعديل بيانات العميل' : 'تعديل بيانات المورد');
        const genericSaveError = @json($type == 'customer' ? 'تعذر حفظ العميل. حاول مرة أخرى.' : 'تعذر حفظ المورد. حاول مرة أخرى.');
        const genericLoadError = @json($type == 'customer' ? 'تعذر تحميل بيانات العميل.' : 'تعذر تحميل بيانات المورد.');
        const isCashCreationDirectory = false;

        $('.js-example-basic-single').select2({
            placeholder: "اختر مما يلى",
        });

        function clearValidationState() {
            $responseContainer.empty();
            $createForm.find('.is-invalid').removeClass('is-invalid');
            $createForm.find('.dynamic-invalid-feedback').remove();
        }

        function renderAlert(type, messages) {
            const normalizedMessages = Array.isArray(messages) ? messages.filter(Boolean) : [messages];

            if (!normalizedMessages.length) {
                return;
            }

            const $alert = $('<div>', {
                class: 'alert alert-' + type,
                role: 'alert'
            });
            const $list = $('<ul>', { class: 'mb-0 pr-3' });

            normalizedMessages.forEach(function(message) {
                $list.append($('<li>').text(message));
            });

            $alert.append($list);
            $responseContainer.html($alert);
        }

        function appendFieldError(fieldName, messages) {
            const normalizedMessages = Array.isArray(messages) ? messages.filter(Boolean) : [messages];
            const $field = $createForm.find('[name="' + fieldName + '"]').first();

            if (!$field.length || !normalizedMessages.length || $field.attr('type') === 'hidden') {
                return;
            }

            $field.addClass('is-invalid');

            const $feedback = $('<span>', {
                class: 'invalid-feedback d-block dynamic-invalid-feedback',
                role: 'alert'
            }).text(normalizedMessages[0]);

            const $inputGroup = $field.closest('.input-group');

            if ($inputGroup.length) {
                $inputGroup.after($feedback);
                return;
            }

            $field.after($feedback);
        }

        function resetCreateForm() {
            clearValidationState();
            $createForm[0].reset();
            $createForm.find('#id').val('');
            $createForm.find('#type').val(@json($type));
            $createForm.find('#opening_balance').val(0);
            $createForm.find('#account_id').val('').trigger('change');
            $createForm.find('#is_cash_party')
                .prop('checked', isCashCreationDirectory)
                .prop('disabled', isCashCreationDirectory);
            $('.modelTitle').text(createTitle);
        }

        function populateEditForm(response) {
            clearValidationState();
            $createForm[0].reset();
            $('.modelTitle').text(editTitle);
            $(".modal-body #name").val(response.name || '');
            $(".modal-body #phone").val(response.phone || '');
            $(".modal-body #is_cash_party")
                .prop('checked', isCashCreationDirectory || response.is_cash_party == 1 || response.is_cash_party === true)
                .prop('disabled', isCashCreationDirectory);
            $(".modal-body #identity_number").val(response.identity_number || '');
            $(".modal-body #email").val(response.email || '');
            $(".modal-body #id").val(response.id || '');
            $(".modal-body #type").val(response.type || @json($type));
            $(".modal-body #account_id").val(response.account_id || '').trigger('change');
            $(".modal-body #vat_no").val(response.tax_number || '');
            $(".modal-body #opening_balance").val(0);
            $(".modal-body #region").val(response.region || '');
            $(".modal-body #city").val(response.city || '');
            $(".modal-body #district").val(response.district || '');
            $(".modal-body #street_name").val(response.street_name || '');
            $(".modal-body #building_number").val(response.building_number || '');
            $(".modal-body #plot_identification").val(response.plot_identification || '');
            $(".modal-body #postal_code").val(response.postal_code || '');
        }

        $(document).on('submit', '#createForm', function(event) {
            id = 0;
            event.preventDefault();
            const href = $(this).attr('action');
            const method = $(this).attr('method');

            $.ajax({
                url: href,
                type: method,
                data: $(this).serialize(),
                beforeSend: function() {
                    clearValidationState();
                    $('#loader').show();
                },
                success: function(result) {
                    renderAlert('success', [result.message || '{{ __('main.saved') }}']);

                    setTimeout(function() {
                        $createModal.modal("hide");
                        resetCreateForm();
                        window.location.reload();
                    }, 1200);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR) {
                    const response = jqXHR.responseJSON || {};
                    const messages = Array.isArray(response.errors) && response.errors.length
                        ? response.errors
                        : [response.message || genericSaveError];
                    const fieldErrors = response.field_errors || {};

                    clearValidationState();
                    renderAlert('danger', messages);

                    Object.keys(fieldErrors).forEach(function(fieldName) {
                        appendFieldError(fieldName, fieldErrors[fieldName]);
                    });
                },
                timeout: 8000
            });
        });

        $(document).on('click', '#createButton', function(event) {
            id = 0;
            event.preventDefault();
            resetCreateForm();
            $createModal.modal("show");
        });

        $(document).on('click', '.deleteBtn', function(event) {
            id = event.currentTarget.value;
            event.preventDefault();
            $('#deleteModal').modal("show");
        });

        $(document).on('click', '.cancel-modal', function () {
            $('#deleteModal').modal("hide");
            id = 0;
        });

        $(document).on('click', '.close-create', function () {
            $('#createModal').modal("hide");
            clearValidationState();
            id = 0;
        });

        $(document).on('click', '.editBtn', function (event) {
            event.preventDefault();
            const url = $(this).attr('url');

            $.ajax({
                type:'get',
                url: url,
                dataType: 'json',
                beforeSend: function() {
                    $('#loader').show();
                },
                success:function(response){
                    if(response){
                        populateEditForm(response);
                        $('#createModal').modal("show");
                    }
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function() {
                    window.erpShowError(genericLoadError, 'تعذر تحميل البيانات');
                },
                timeout: 8000
            });
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
            success: function() {
                $('#deleteModal').modal("hide");
                window.location.reload();
            },
            complete: function() {
                $('#loader').hide();
            },
            error: function() {
                window.erpShowError('تعذر حذف الطرف حاليًا. حاول مرة أخرى.', 'تعذر الحذف');
            },
            timeout: 8000
        });
    }
</script>  
@endsection 
 
