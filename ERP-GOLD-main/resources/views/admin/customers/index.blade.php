@extends('admin.layouts.master') 
@section('content')
@canany(['employee.customers.show','employee.suppliers.show' ]) 
    @php
        $isReportDirectory = $reportDirectory ?? false;
        $isCashListing = $cashOnly ?? false;
        $isCashDirectory = $cashDirectory ?? false;
        $filterRouteName = $isReportDirectory
            ? ($isCashDirectory ? 'customers.reports.cash' : 'customers.reports.index')
            : ($isCashDirectory ? 'customers.cash' : 'customers');
        $filterRouteParams = ['type' => $type];

        if (! $isCashDirectory && $isCashListing && ! $isReportDirectory) {
            $filterRouteParams['cash_only'] = 1;
        }

        if ($isReportDirectory) {
            $directoryTitle = $type == 'customer' ? 'تقارير العملاء' : 'تقارير الموردين';
            $directoryHint = 'اختر التقرير المطلوب، وسيتم فتح نافذة الطباعة مباشرة بدون الانتقال إلى صفحة أخرى.';
        } elseif ($isCashListing) {
            $directoryTitle = $type == 'customer' ? 'العملاء النقديون' : 'الموردون النقديون';
            $directoryHint = 'هذه القائمة تعرض الأطراف النقدية فقط. هذه الصفحة تحفظ الطرف كنقدي تلقائيًا ليظهر في جدوله الصحيح.';
        } else {
            $directoryTitle = $type == 'customer' ? __('main.customers') : __('main.suppliers');
            $directoryHint = null;
        }

        $branch = auth()->user()?->branch;
        $subscriber = $branch?->subscriber;
        $logoUrl = null;

        try {
            $logoUrl = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings()->logoUrl ?? null;
        } catch (\Throwable $e) {
            $logoUrl = null;
        }
    @endphp
    @if (session('success'))
        <div class="alert alert-success fade show no-print">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
    @include('admin.reports.partials.result_print_styles')
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

    .customer-directory-print-header {
        display: none;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        .customer-directory-page {
            color: #172033 !important;
        }

        .customer-directory-screen-header,
        .customer-directory-actions,
        .customer-directory-actions *,
        #createModal,
        #deleteModal {
            display: none !important;
            visibility: hidden !important;
        }

        .customer-directory-print-header {
            display: grid !important;
            grid-template-columns: 1fr 2fr 1fr;
            align-items: center;
            gap: 8mm;
            margin: 0 0 5mm !important;
            padding: 4mm 5mm !important;
            border: 1px solid #aeb8cc !important;
            background: #fff !important;
            break-after: avoid;
            page-break-after: avoid;
        }

        .customer-directory-print-title {
            margin: 0 0 1.5mm !important;
            padding: 1.8mm 4mm !important;
            background: #dfe8ff !important;
            color: #243f78 !important;
            font-size: 15px !important;
            font-weight: 800 !important;
            text-align: center !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .customer-directory-print-meta {
            margin: 0.8mm 0 !important;
            font-size: 10px !important;
            line-height: 1.5 !important;
            color: #334155 !important;
        }

        .customer-directory-print-logo {
            max-width: 28mm !important;
            max-height: 22mm !important;
            object-fit: contain !important;
        }

        .customer-directory-page .card,
        .customer-directory-page .card-body,
        .customer-directory-page .table-responsive,
        .customer-directory-page .dataTables_wrapper {
            border: 0 !important;
            box-shadow: none !important;
            background: #fff !important;
        }

        .customer-directory-page .directory-table-card {
            margin: 0 !important;
        }

        .customer-directory-page table#example1 {
            table-layout: fixed !important;
            border: 1px solid #94a3b8 !important;
        }

        .customer-directory-page table#example1 th,
        .customer-directory-page table#example1 td {
            padding: 2mm 1.6mm !important;
            border: 1px solid #b8c2d4 !important;
            font-size: 10px !important;
            line-height: 1.45 !important;
            color: #111827 !important;
            background: #fff !important;
            white-space: normal !important;
            word-break: break-word !important;
            vertical-align: middle !important;
        }

        .customer-directory-page table#example1 thead th {
            background: #edf3ff !important;
            color: #1e3a6d !important;
            font-weight: 800 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .customer-directory-page table#example1 thead th::before,
        .customer-directory-page table#example1 thead th::after {
            display: none !important;
            content: "" !important;
        }

        .customer-directory-page table#example1 tbody tr:nth-child(even) td {
            background: #f8fafc !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .customer-directory-page table#example1 th:nth-child(1),
        .customer-directory-page table#example1 td:nth-child(1) {
            width: 9mm !important;
        }

        .customer-directory-page table#example1 th:nth-child(2),
        .customer-directory-page table#example1 td:nth-child(2) {
            width: 38mm !important;
            font-weight: 700 !important;
        }

        .customer-directory-page table#example1 th:nth-child(3),
        .customer-directory-page table#example1 td:nth-child(3) {
            width: 28mm !important;
        }

        .customer-directory-page table#example1 th:nth-child(4),
        .customer-directory-page table#example1 td:nth-child(4) {
            width: 20mm !important;
        }

        .customer-directory-page table#example1 th:nth-child(5),
        .customer-directory-page table#example1 td:nth-child(5) {
            width: 30mm !important;
        }

        .customer-directory-page table#example1 th:nth-child(6),
        .customer-directory-page table#example1 td:nth-child(6) {
            width: 44mm !important;
        }

        .customer-directory-page table#example1 th:nth-child(7),
        .customer-directory-page table#example1 td:nth-child(7) {
            width: 32mm !important;
        }

        .customer-directory-page table#example1 th:nth-child(8),
        .customer-directory-page table#example1 td:nth-child(8) {
            display: none !important;
        }

        .customer-directory-page .badge {
            display: inline-block !important;
            min-width: 14mm;
            padding: 1mm 2mm !important;
            border: 1px solid #c7d2fe !important;
            border-radius: 3px !important;
            background: #eef3ff !important;
            color: #1f2a44 !important;
            font-size: 9px !important;
            font-weight: 800 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
    </style>  

    <!-- row opened -->
    <div class="row row-sm customer-directory-page erp-print-report">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0 customer-directory-screen-header no-print" id="head-right" >
                    <div class="col-lg-12 margin-tb text-center">
                        <h4 class="alert alert-primary text-center">
                            [ {{ $directoryTitle }} ]
                        </h4>
                        @if($directoryHint)
                            <div class="alert alert-info py-2 mb-3">{{ $directoryHint }}</div>
                        @endif
                        @if(! $isReportDirectory)
                        @canany(['employee.customers.add','employee.suppliers.add'])
                            <button type="button" class="btn btn-labeled btn-info" id="createButton">
                                <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-plus"></i></span>
                                {{__('main.add_new')}}
                            </button>
                        @endcanany
                        @endif
                        <form method="GET" action="{{ route($filterRouteName, $filterRouteParams) }}" class="mt-3">
                            @if(! $isCashDirectory && $isCashListing && ! $isReportDirectory)
                                <input type="hidden" name="cash_only" value="1">
                            @endif
                            <div class="row justify-content-center">
                                <div class="col-lg-4 col-md-6">
                                    <div class="input-group">
                                        <input type="text" name="identity_number" class="form-control text-right"
                                            placeholder="بحث برقم الهوية"
                                            value="{{ $identityNumber ?? '' }}">
                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-outline-primary">بحث</button>
                                            <a href="{{ route($filterRouteName, $filterRouteParams) }}" class="btn btn-outline-secondary">مسح</a>
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

                    <div class="customer-directory-print-header">
                        <div class="text-right">
                            @if($subscriber?->name)
                                <p class="customer-directory-print-meta"><strong>{{ $subscriber->name }}</strong></p>
                            @endif
                            @if($branch?->name)
                                <p class="customer-directory-print-meta">الفرع: {{ $branch->name }}</p>
                            @endif
                            @if($branch?->tax_number)
                                <p class="customer-directory-print-meta">الرقم الضريبي: {{ $branch->tax_number }}</p>
                            @endif
                        </div>
                        <div class="text-center">
                            <h3 class="customer-directory-print-title">{{ $directoryTitle }}</h3>
                            <p class="customer-directory-print-meta">عدد السجلات: {{ $customers?->count() ?? 0 }}</p>
                            @if(!empty($identityNumber))
                                <p class="customer-directory-print-meta">تصفية رقم الهوية: {{ $identityNumber }}</p>
                            @endif
                        </div>
                        <div class="text-left">
                            @if($logoUrl)
                                <img src="{{ $logoUrl }}" class="customer-directory-print-logo" alt="logo">
                            @endif
                            <p class="customer-directory-print-meta">تاريخ الطباعة: {{ now()->format('Y-m-d H:i') }}</p>
                        </div>
                    </div>

                    <div class="card shadow mb-4 directory-table-card">
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
                                                <td class="text-center customer-directory-actions">
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
                                                    @can($type == 'customer' ? 'employee.customers.show' : 'employee.suppliers.show')
                                                    <button
                                                        type="button"
                                                        class="btn btn-success btn-sm"
                                                        data-print-open
                                                        data-print-url="{{ route('customers.report', $customer->id) }}"
                                                        data-print-target="_iframe"
                                                    >
                                                        <i class="fa fa-chart-bar"></i> تقرير تفصيلي
                                                    </button>
                                                    @endcan
                                                    @if($isReportDirectory)
                                                    @can($type == 'customer' ? 'employee.customers.show' : 'employee.suppliers.show')
                                                    <button
                                                        type="button"
                                                        class="btn btn-info btn-sm"
                                                        data-print-open
                                                        data-print-url="{{ route('customers.report.cash', $customer->id) }}"
                                                        data-print-target="_iframe"
                                                    >
                                                        <i class="fa fa-money-bill"></i> تقرير نقدي
                                                    </button>
                                                    @endcan
                                                    @endif
                                                    @if(! $isReportDirectory)
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
                                                    @endif
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
                    @if($isCashListing && ! $isReportDirectory)
                        <input type="hidden" name="force_cash_party" value="1">
                    @endif

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
        const isCashCreationDirectory = @json($isCashListing && ! $isReportDirectory);

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
 
