@extends('admin.layouts.master')
@section('content')
@can('employee.simplified_tax_invoices.add')  
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<!-- row opened -->
    <style> 
        .nav-tabs .nav-item.show .nav-link, .nav-tabs .nav-link.active {
          color: #ffffff;
          background-color: #E5B80B;
          border-color: #E5B80B;
        }
        input#net_after_discount {
            font-weight: 700;
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
        input.form-control { 
            text-align:center;
        }
        th.text-center.NameProdect {
            padding: 0 5px;
        } 
        
        ul#products_suggestions li{
            padding:5px 10px;
            cursor:pointer;
        }
    </style>

    <div class="row row-sm">
        <div class="col-xl-12"> 
                <ul class="nav nav-tabs" id="myTab" role="tablist" hidden>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="home-tab" data-toggle="tab" data-target="#home"
                                type="button" role="tab" aria-controls="home"
                                aria-selected="true">
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="profile-tab" data-toggle="tab" data-target="#profile" type="button"
                                role="tab" aria-controls="profile" aria-selected="false">{{__('main.pos_purchase')}}
                        </button>
                    </li> 
                </ul> 
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <form method="POST" action="{{ route('sales.store', $type) }}"
                                  enctype="multipart/form-data" id="pos_sales_form">
                                @csrf
                                @method('POST')
                                <input type="hidden" name="user_id" value="{{Auth::user()->id}}"/>
                                <input type="hidden" name="uuid" id="uuid" value=""/>
                                <div class="row">
                                    <div class="card shadow mb-4 col-9">
                                        <div class="card-header py-3">
                                            <div class="row">
                                               <div class="col-12"> 
                                                    <h4  class="alert alert-primary text-center">
                                                        @if($type == 'standard')
                                                        {{__('main.sales_standard')}}
                                                        @else
                                                        {{__('main.sales_simplified')}}
                                                        @endif
                                                    </h4> 
                                                </div> 
                                            </div>  
                                        </div>
                                        <div class="card-body">
                                            <div class="row"> 
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label >{{ __('main.bill_number') }}  
                                                        </label>
                                                        <input type="text" id="bill_number" name="bill_number"
                                                               class="form-control" placeholder="" readonly
                                                        />
                                                    </div>
                                                </div> 
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label >{{ __('main.bill_date') }}  
                                                        </label>
                                                        <input type="datetime-local" id="bill_date" name="bill_date"
                                                               class="form-control" readonly/>
                                                        
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label class="d-block">
                                                             الفرع
                                                        </label>
                                                        @if(Auth::user()->is_admin)
                                                            <select required  class="js-example-basic-single w-100" name="branch_id" id="branch_id"> 
                                                                @foreach($branches as $branch)
                                                                    <option value="{{$branch->id}}">{{$branch->name}}</option>
                                                                @endforeach
                                                            </select>
                                                        @else
                                                            <input class="form-control" type="text" readonly
                                                                   value="{{Auth::user()->branch->name}}"/>
                                                            <input required class="form-control" type="hidden" id="branch_id"
                                                                   name="branch_id"
                                                                   value="{{Auth::user()->branch_id}}"/>
                                                        @endif
                    
                                                    </div>
                                                </div> 
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label >{{ __('main.customer') }} 
                                                        </label>
                                                        <select class="form-control mr-sm-2"
                                                                name="customer_id" id="customer_id"> 
                                                            @foreach ($customers as $customer)
                                                                <option
                                                                    value="{{$customer -> id}}"
                                                                    data-name="{{ $customer->name }}"
                                                                    data-phone="{{ $customer->phone }}"
                                                                    data-identity-number="{{ $customer->identity_number }}"
                                                                    data-cash-party="{{ $customer->is_cash_party ? 1 : 0 }}"
                                                                >
                                                                    {{ $customer -> name}}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <div class="custom-control custom-switch text-right mt-2">
                                                            <input type="checkbox" class="custom-control-input" id="cash_party_only_toggle">
                                                            <label class="custom-control-label" for="cash_party_only_toggle">
                                                                عرض العملاء النقديين فقط
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label > {{ __('main.bill_client_phone') }}  
                                                        </label>
                                                        <input class="form-control text-right" name="bill_client_phone">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label>رقم هوية العميل</label>
                                                        <input class="form-control text-right" name="bill_client_identity_number" autocomplete="off">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="form-group">
                                                        <label >
                                                            {{ __('main.bill_client_name') }} 
                                                        </label>
                                                      <input class="form-control text-right" name="bill_client_name" autocomplete="off">
                                                      @can('employee.customers.add')
                                                          <button
                                                              type="button"
                                                              class="btn btn-outline-primary btn-block mt-2"
                                                              id="quick_save_customer_btn"
                                                              data-url="{{ route('customers.quick-store', ['type' => 'customer']) }}"
                                                          >
                                                              حفظ الاسم الحالي كعميل
                                                          </button>
                                                          <div class="custom-control custom-switch text-right mt-2">
                                                              <input type="checkbox" class="custom-control-input" id="quick_save_customer_is_cash_party">
                                                              <label class="custom-control-label" for="quick_save_customer_is_cash_party">
                                                                  حفظه كطرف نقدي
                                                              </label>
                                                          </div>
                                                      @endcan
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>{{ __('main.notes') }}</label>
                                                        <textarea
                                                            name="notes"
                                                            id="notes"
                                                            rows="2"
                                                            placeholder="{{ __('main.notes') }}"
                                                            class="form-control"
                                                            style="width: 100%"
                                                        >{{ old('notes') }}</textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="form-group">
                                                        <label>قالب الشروط</label>
                                                        <select id="invoice_terms_template_selector" class="form-control mb-2">
                                                            @foreach($invoiceTermTemplates as $template)
                                                                <option
                                                                    value="{{ $template['key'] }}"
                                                                    @selected(($defaultInvoiceTermsTemplateKey ?? null) === $template['key'])
                                                                >
                                                                    {{ $template['title'] }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                        <label>شروط الفاتورة</label>
                                                        <textarea
                                                            name="invoice_terms"
                                                            id="invoice_terms"
                                                            rows="2"
                                                            placeholder="اكتب شروط الفاتورة"
                                                            class="form-control"
                                                            style="width: 100%"
                                                        >{{ old('invoice_terms', $defaultInvoiceTerms) }}</textarea>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row"> 
                                                    <div class="col-md-12 " id="sticker">
                                                        <div class="well well-sm" @if(Config::get('app.locale') == 'ar')style="direction: rtl;" @endif>
                                                            <div class="form-group">
                                                                <div class="input-group wide-tip">
                                                                    <div class="input-group-addon">
                                                                        <i class="fa fa-3x fa-barcode addIcon"></i>
                                                                    </div>
                                                                    <input type="text" name="add_item" id="add_item" value="" class="form-control text-right input-lg ui-autocomplete-input" placeholder="{{__('main.barcode.note')}}" autocomplete="off">
                                                                </div> 
                                                            </div>
                                                            <ul class="suggestions" id="products_suggestions" style="display: block">
                                                            </ul>
                                                            <div class="clearfix"></div>
                                                        </div>
                                                    </div>  
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="card mb-4">
                                                           <div class="card-header pb-0">
                                                                <h4   class="alert alert-info text-center">
                                                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i> 
                                                                    {{__('اصناف الفاتورة')}} 
                                                                </h4>
                                                            </div>
                                                            <div class="card-body px-0 pt-0 pb-2">
                                                                <div class="table-responsive hoverable-table">
                                                                    <table class="display w-100 table-bordered" id="sTable" 
                                                                           style="text-align: center;">
                                                                        <thead>
                                                                            <tr>
                                                                                
                                                                                <th class="col-md-3" >{{__('main.item_name')}}</th>
                                                                                <th class="col-md-1" >{{__('main.item_carats')}}</th>
                                                                                <th>{{__('main.item_weight')}}</th>
                                                                                <th>{{__('main.price_gram')}} </th>
                                                                                <th>{{__('main.quantity')}} </th>
                                                                                <th>{{__('main.no_metal')}} </th>
                                                                                <th>{{__('main.item_amount')}}</th>
                                                                                <th>{{__('main.item_tax')}}</th>
                                                                                <th class="col-md-2" >{{__('main.item_total')}}</th>
                                                                                <th hidden>weigh21</th>
                                                                                <th hidden>factor</th>
                                                                                <th></th> 
                                                                                
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="tbody"></tbody>
                                                                        <tfoot></tfoot>
                                                                    </table>
                                                                </div>
                                                            </div> 
                                                        </div>
                                                    </div>
                                                </div>
                                        </div>
                                    </div>
                                    <div class="card shadow mb-4 col-3">
                                        <div class="card-header py-3">
                                            <h5 class="alert alert-info text-center">{{__('main.sales_invoice_total')}}</h6>
                                        </div>
                                        <div class="card-body ">
                                            <div class="row document_type1" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.items_count')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="items_count">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_actual_weight')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_actual_weight">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_weight21')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_weight21" name="total_weight21">
                                                </div>
                                            </div>

                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_without_tax')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="total">
                                                </div>
                                            </div>
                                         
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.additional_tax')  }} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="total_tax" name="tax">
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label
                                                            style="text-align: right;float: right;"> {{__('اجمالي الفاتورة')}} </label>
                                                        <input type="text" readonly  class="form-control" id="net_total" name="net_total" placeholder="0">
                                                    </div>
                                                </div>
                                                @canany(['employee.simplified_tax_invoices.add','employee.tax_invoices.add'])
                                                <div class="col-md-12 text-center"> 
                                                    <button type="button" 
                                                        class="btn btn-md btn-info w-100" 
                                                        id="sales_btn" 
                                                        value="{{__('main.pay')}}">
                                                        حفظ ودفع
                                                    </button> 
                                                </div>
                                                @endcan 
                                            </div>
                                            <div class="row" hidden >
                                                <div class="form-group">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.paid')}} </label>
                                                    <input type="number" step="any"  class="form-control" id="paid" name="paid" placeholder="0">
                                                </div>
              
                                            </div> 

                                            <div class="show_modal1"> 
                                            </div> 
                                        </div>  
                                        <div class="row">
                                           
                                        </div>
                                    </div> 
                                </div> 
                            </form>
                        </div>  
                        <!--purchase TAB-->
                        <div class="tab-pane fade show " id="profile" role="tabpanel" aria-labelledby="profile-tab" hidden>
                           
                        </div>
                    </div>
                </div>


                <div class="modal fade" id="ItemMaterialModalDialog" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel"
                     aria-hidden="true">
                    <div class="modal-dialog modal-sm" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <label class="modelTitle"> {{__('main.warning')}}</label>
                                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"
                                        style="color: red; font-size: 20px; font-weight: bold;">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body" id="smallBody">
                                <img src="{{asset('assets/img/warning.png')}}" class="alertImage">
                                <label class="alertTitle">{{__('main.ItemMaterialModalDialog')}}</label>
                                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                                <div class="row">
                                    <div class="col-6 text-center">
                                        <button type="button" class="btn btn-labeled btn-primary" onclick="dealWithItemMaterial()">
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


            </div>
            <!-- /.container-fluid -->
            <input id="local" value="{{Config::get('app.locale')}}" hidden>
        </div>
        <!-- End of Main Content --> 
    </div>
    <!-- End of Content Wrapper --> 
</div>
<!-- End of Page Wrapper -->
<audio id="mysoundclip1" preload="auto">
    <source src="{{URL::asset('assets/sound/beep/beep-timber.mp3')}}"></source>
</audio>
<audio id="mysoundclip2" preload="auto">
    <source src="{{URL::asset('assets/sound/beep/beep-07.mp3')}}"></source>
</audio>

@endcan 
@endsection 
@section('js')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    var suggestionItems = {};
    var sItems = {};
    var count = 1; 
    document.title = "فاتورة مبيعات مبسطة";
    var quickCustomerStoreUrl = $('#quick_save_customer_btn').data('url');

    $(document).ready(function () {  

        $('#add_item').focus();
        applyCashPartyFilter();
        syncSelectedPartySnapshot();

        $(document).on('change', '#customer_id', function () {
            syncSelectedPartySnapshot();
        });
        $(document).on('change', '#cash_party_only_toggle', function () {
            applyCashPartyFilter();
            syncSelectedPartySnapshot();
        });

        $(document).on('click', '#quick_save_customer_btn', function () {
            var button = $(this);
            var name = $.trim($('input[name="bill_client_name"]').val());
            var phone = $.trim($('input[name="bill_client_phone"]').val());
            var identityNumber = $.trim($('input[name="bill_client_identity_number"]').val());
            var isCashParty = $('#quick_save_customer_is_cash_party').is(':checked') ? 1 : 0;

            if (!name.length) {
                Swal.fire({
                    title: 'بيانات ناقصة',
                    text: 'أدخل اسم العميل أولًا قبل الحفظ السريع.',
                    icon: 'warning',
                    confirmButtonText: 'موافق'
                });
                return;
            }

            button.prop('disabled', true).text('جاري الحفظ...');

            $.ajax({
                type: 'post',
                url: quickCustomerStoreUrl,
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    phone: phone,
                    identity_number: identityNumber,
                    is_cash_party: isCashParty
                },
                dataType: 'json',
                success: function (response) {
                    upsertPartyOption(
                        '#customer_id',
                        response.customer_id,
                        response.customer_name,
                        response.phone,
                        response.identity_number,
                        response.is_cash_party
                    );
                    syncSelectedPartySnapshot();
                    Swal.fire({
                        title: response.created ? 'تم الحفظ' : 'موجود مسبقًا',
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'موافق'
                    });
                },
                error: function (xhr) {
                    Swal.fire({
                        title: 'تعذر الحفظ',
                        text: extractPartyErrors(xhr),
                        icon: 'error',
                        confirmButtonText: 'موافق'
                    });
                },
                complete: function () {
                    button.prop('disabled', false).text('حفظ الاسم الحالي كعميل');
                }
            });
        });

        $(document).on('click', '#payment_btn', function (){
            const money = document.getElementById('money').value;
            const cash = document.getElementById('cash').value;
            const paymentLines = collectPaymentLines();
            const visa = paymentLines
                .filter(function (line) { return line.method_type === 'credit_card'; })
                .reduce(function (sum, line) { return sum + Number(line.amount || 0); }, 0)
                .toFixed(2);
            const type = document.getElementById('type').value;
            const nonCashTotal = paymentLines.reduce(function (sum, line) {
                return sum + Number(line.amount || 0);
            }, 0);

            if(Math.abs(Number(money) - (Number(cash) + Number(nonCashTotal))) < 0.01){

                var url = $('#pos_sales_form').attr('action');
                var user_id = $('input[name="user_id"]').val();
                var bill_date = $('input[name="bill_date"]').val();
                var customer_id = $('#customer_id').val();
                var branch_id = $('#branch_id').val();
                var bill_client_phone = $('input[name="bill_client_phone"]').val();
                var bill_client_name = $('input[name="bill_client_name"]').val();
                var bill_client_identity_number = $('input[name="bill_client_identity_number"]').val();
                var notes = $('textarea[name="notes"]').val();
                var invoice_terms = $('textarea[name="invoice_terms"]').val();
                $.ajax({
                    type: 'post',
                    url: url,
                    data: {
                        user_id: user_id,
                        bill_date: bill_date,
                        customer_id: customer_id,
                        branch_id: branch_id,
                        bill_client_phone: bill_client_phone,
                        bill_client_name: bill_client_name,
                        bill_client_identity_number: bill_client_identity_number,
                        type: type,
                        cash: cash,
                        visa: visa,
                        payment_lines: paymentLines,
                        notes: notes,
                        invoice_terms: invoice_terms,
                        unit_id: getFormValuesForKey('unit_id'),
                        carats_id: getFormValuesForKey('carats_id'),
                        weight: getFormValuesForKey('weight'),
                        gram_price: getFormValuesForKey('gram_price'),
                        quantity: getFormValuesForKey('quantity'),
                        no_metal: getFormValuesForKey('no_metal'),
                        item_tax: getFormValuesForKey('item_tax'),
                        net_money: getFormValuesForKey('net_money'),
                        unit_transform_factor: getFormValuesForKey('unit_transform_factor'),
                        unit_tax_rate: getFormValuesForKey('unit_tax_rate'),

                    },
                    dataType: 'json',

                    success: function (response) {
                        if(response.status){
                            $('#paymentsModal').modal("hide");
                            setTimeout(function() {
                                Swal.fire({
                                    title: "{{__('main.success')}}",
                                    text: response.message,
                                    icon: 'success',
                                    confirmButtonText: 'موافق'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        window.location.href = response.url;
                                    }
                                });
                            }, 1000);
                        }
                    },
                    error: function (err){
                        Swal.fire({
                            title: 'تعذر حفظ الفاتورة',
                            text: extractAjaxErrors(err, 'حدث خطأ أثناء حفظ الفاتورة.'),
                            icon: 'error',
                            confirmButtonText: 'موافق'
                        });
                    }
                });
            } else {
                Swal.fire({
                    title: 'بيانات دفع غير متطابقة',
                    text: $('<div>{{trans('main.paid_must_equal_net')}}</div>').text(),
                    icon: 'warning',
                    confirmButtonText: 'موافق'
                });
            } 
        });


        function getFormValuesForKey(key){
            var data = [];
            $('input[name="' + key + '[]"]').each(function() {
                let originalValue = $(this).data('original');
                data.push(originalValue);
            });
            return data;
        }

        function syncSelectedPartySnapshot() {
            var selectedOption = $('#customer_id option:selected');
            $('input[name="bill_client_name"]').val(selectedOption.data('name') || '');
            $('input[name="bill_client_phone"]').val(selectedOption.data('phone') || '');
            $('input[name="bill_client_identity_number"]').val(selectedOption.data('identity-number') || '');
            $('#quick_save_customer_is_cash_party').prop('checked', String(selectedOption.data('cash-party')) === '1');
        }

        function applyCashPartyFilter() {
            var select = $('#customer_id');
            var cashOnly = $('#cash_party_only_toggle').is(':checked');
            var firstEnabledValue = null;

            select.find('option').each(function () {
                var option = $(this);
                var isCashParty = String(option.data('cash-party')) === '1';
                var isEnabled = !cashOnly || isCashParty;

                option.prop('disabled', !isEnabled);

                if (isEnabled && firstEnabledValue === null) {
                    firstEnabledValue = option.val();
                }
            });

            var selectedOption = select.find('option:selected');
            if (!selectedOption.length || selectedOption.prop('disabled')) {
                select.val(firstEnabledValue || '');
            }

            select.trigger('change.select2');
        }

        function upsertPartyOption(selectSelector, id, name, phone, identityNumber, isCashParty) {
            var select = $(selectSelector);
            var option = select.find('option[value="' + id + '"]');

            if (!option.length) {
                option = $('<option></option>').val(id).appendTo(select);
            }

            option
                .text(name)
                .attr('data-name', name)
                .attr('data-phone', phone || '')
                .attr('data-identity-number', identityNumber || '')
                .attr('data-cash-party', isCashParty ? 1 : 0);

            select.val(String(id)).trigger('change');
        }

        function extractPartyErrors(xhr) {
            if (xhr.responseJSON && Array.isArray(xhr.responseJSON.errors) && xhr.responseJSON.errors.length) {
                return xhr.responseJSON.errors.join('\n');
            }

            return 'حدث خطأ أثناء حفظ بيانات العميل.';
        }

        function extractAjaxErrors(xhr, fallbackMessage) {
            if (xhr.responseJSON && Array.isArray(xhr.responseJSON.errors) && xhr.responseJSON.errors.length) {
                return xhr.responseJSON.errors.join('\n');
            }

            if (xhr.responseJSON && xhr.responseJSON.message) {
                return xhr.responseJSON.message;
            }

            return fallbackMessage;
        }
 

        $(document).on('click', '#sales_btn', function () {
            var rows =  0 ;
            var document_type = "{{ $type }}";
            rows = $('#sTable tbody tr').length;
            var net_total = document.getElementById('net_total').value;
            var client = document.getElementById('customer_id').value ;
            if(client > 0) {
                if (rows > 0){
                    if (true) {
                        openPaymentModal(document_type, net_total, $('#branch_id').val());
                        localStorage.setItem('openModal', net_total);
                    } else {
                        alert($('<div>{{trans('main.paid_must_equal_net')}}</div>').text());
                    }
            } else {
                    alert($('<div>{{trans('main.no_bill_details')}}</div>').text());
                }
            } else {
                alert($('<div>{{trans('main.select_client')}}</div>').text());
            }


        });

   
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        now.setMilliseconds(null);
        now.setSeconds(null);

        document.getElementById('bill_date').value = now.toISOString().slice(0, -1);

        $('#add_item').on('input', function (e) { 
            searchProduct($('#add_item').val());
        });

        $(document).on('click', '.cancel-modal', function (event) {
            $('#deleteModal').modal("hide");
            $('#ItemMaterialModalDialog').modal("hide");
            id = 0;
        });

        $(document).on('click', '.deleteBtn', function (event) {
            var row = $(this).parent().parent().index();
            var row1 = $(this).closest('tr');
            var item_id = row1.attr('data-item-id');
            delete sItems[item_id];
            loadItems();
            var audio = $("#mysoundclip2")[0];
            audio.play();
        });

        $(document).on('click', '.select_product', function () {
            var row = $(this).closest('li');
            var item_id = row.attr('data-item-id');
            if(suggestionItems[item_id]){
                addItemToTable(suggestionItems[item_id]);
                var audio = $("#mysoundclip1")[0];
                audio.play();
            }

        });

    });

    function is_numeric(mixed_var) {
        var whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
        return (
            (typeof mixed_var === 'number' || (typeof mixed_var === 'string' && whitespace.indexOf(mixed_var.slice(-1)) === -1)) &&
            mixed_var !== '' &&
            !isNaN(mixed_var)
        );
    }

    function searchProduct(code) {
        let branch_id = document.getElementById('branch_id').value; 
        let url = "{{route('items.search')}}";
        $.ajax({
            type: 'post',
            url: url,
            data: {
                code: code,
                branch_id: branch_id
            },
            dataType: 'json',

            success: function (response) {
             
                document.getElementById('products_suggestions').innerHTML = '';
                if (response) {
                    if (response.data.length == 1) {
                        if (response.data[0]) {
                            addItemToTable(response.data[0]);
                                var audio = $("#mysoundclip2")[0];
                                audio.play();
                        }
                    } else if (response.data.length > 1) { 
                        showSuggestions(response);
                    } else if (response.id) {
                        showSuggestions(response);
                    } else {
                        openDialog();
                        document.getElementById('add_item').value = '';
                    }
                } else {
                    
                    openDialog();
                    document.getElementById('add_item').value = '';
                }
            },
            error: function (err){
                console.log( JSON.parse(JSON.stringify(err.responseText)) );
            }
        });
    }

    function showSuggestions(response) {

        $data = '';
        $.each(response.data, function (i, item) {
            suggestionItems[item.unit_id] = item; 
            $data += '<li class="select_product" data-item-id="' + item.unit_id + '">'  + ' ( ' + item.item_name_without_break+ ' ) </li>';
        });
        document.getElementById('products_suggestions').innerHTML = $data;
    }


    var salesCashWasEditedManually = false;

    function openPaymentModal(document_type, net_total, branch_id){
        let url = "{{ route('sales.payments')}}";
        $.post( url,{document_type: document_type, net_after_discount: net_total, branch_id: branch_id}, function( data ) {
            $(".show_modal1").html( data ); 
            salesCashWasEditedManually = false;
            refreshPaymentSummary();

            $('#paymentsModal').modal({backdrop: 'static', keyboard: false} ,'show');
        });
    }

    function buildBankAccountOptions(methodType, selectedId) {
        var options = '<option value="">حدد الحساب البنكي</option>';
        var items = window.currentSalesBankAccounts || [];

        items.forEach(function (item) {
            var supported = methodType === 'credit_card' ? item.supports_credit_card : item.supports_bank_transfer;

            if (!supported) {
                return;
            }

            var selected = String(selectedId || '') === String(item.id) ? 'selected' : '';
            options += '<option value="' + item.id + '" ' + selected + '>' + item.name + '</option>';
        });

        return options;
    }

    function appendPaymentLineRow(line) {
        var methodType = (line && line.method_type) ? line.method_type : 'credit_card';
        var bankAccountId = line && line.bank_account_id ? line.bank_account_id : '';
        var referenceNo = line && line.reference_no ? line.reference_no : '';
        var amount = line && line.amount ? line.amount : '';

        var rowHtml = ''
            + '<tr class="payment-line-row">'
            + '<td>'
            + '<select class="form-control payment-line-method">'
            + '<option value="credit_card"' + (methodType === 'credit_card' ? ' selected' : '') + '>شبكة / بطاقة</option>'
            + '<option value="bank_transfer"' + (methodType === 'bank_transfer' ? ' selected' : '') + '>تحويل بنكي</option>'
            + '</select>'
            + '</td>'
            + '<td><select class="form-control payment-line-bank-account">' + buildBankAccountOptions(methodType, bankAccountId) + '</select></td>'
            + '<td><input type="text" class="form-control payment-line-reference" value="' + referenceNo + '" placeholder="رقم المرجع"></td>'
            + '<td><input type="number" min="0" step="any" class="form-control payment-line-amount" value="' + amount + '" placeholder="0.00"></td>'
            + '<td><button type="button" class="btn btn-outline-danger btn-sm remove-payment-line">حذف</button></td>'
            + '</tr>';

        $('#payment_lines_table tbody').append(rowHtml);
        refreshPaymentSummary();
    }

    function collectPaymentLines() {
        var lines = [];

        $('#payment_lines_table tbody tr').each(function () {
            var row = $(this);
            var amount = Number(row.find('.payment-line-amount').val() || 0);

            if (amount <= 0) {
                return;
            }

            lines.push({
                method_type: row.find('.payment-line-method').val(),
                bank_account_id: row.find('.payment-line-bank-account').val(),
                reference_no: row.find('.payment-line-reference').val(),
                amount: amount.toFixed(2)
            });
        });

        return lines;
    }

    function refreshPaymentSummary() {
        var moneyInput = document.getElementById('money');
        if (!moneyInput) {
            return;
        }

        var paymentLines = collectPaymentLines();
        var nonCashTotal = paymentLines.reduce(function (sum, line) {
            return sum + Number(line.amount || 0);
        }, 0);
        var total = Number(moneyInput.value || 0);
        var suggestedCashValue = Math.max(total - nonCashTotal, 0);

        if (!salesCashWasEditedManually) {
            $('#cash').val(suggestedCashValue.toFixed(2));
        }

        var cashValue = Number($('#cash').val() || 0);
        var remaining = (total - (cashValue + nonCashTotal)).toFixed(2);
        var cardTotal = paymentLines
            .filter(function (line) { return line.method_type === 'credit_card'; })
            .reduce(function (sum, line) { return sum + Number(line.amount || 0); }, 0)
            .toFixed(2);

        $('#payment_lines_total').text(nonCashTotal.toFixed(2));
        $('#payment_remaining').val(remaining);
        $('#visa').val(cardTotal);
    }

    $(document).on('click', '#add_payment_line_btn', function () {
        appendPaymentLineRow();
    });

    $(document).on('click', '.remove-payment-line', function () {
        $(this).closest('tr').remove();
        refreshPaymentSummary();
    });

    $(document).on('change', '.payment-line-method', function () {
        var row = $(this).closest('tr');
        row.find('.payment-line-bank-account').html(buildBankAccountOptions($(this).val(), ''));
        refreshPaymentSummary();
    });

    $(document).on('input', '#cash', function () {
        salesCashWasEditedManually = true;
        refreshPaymentSummary();
    });

    $(document).on('keyup change', '#cash, .payment-line-amount, .payment-line-bank-account, .payment-line-reference', function () {
        refreshPaymentSummary();
    });
		
    function openDialog() {
        let href = $(this).attr('data-attr');
        $.ajax({
            url: href,
            beforeSend: function () {
                $('#loader').show();
            },
            // return the result
            success: function (result) {
                $('#deleteModal').modal("show");
            },
            complete: function () {
                $('#loader').hide();
            },
            error: function (jqXHR, testStatus, error) {
                alert("Page " + href + " cannot open. Error:" + error);
                $('#loader').hide();
            },
            timeout: 8000
        });
    }

    function addItemToTable(item) {
        suggestionItems = [];
        $('#products_suggestions').empty();
        if (sItems[item.unit_id]) {
            alert('هذا الصنف موجود');
            return;
        } else {
            sItems[item.unit_id] = item;
        }
        count++;
        loadItems();

        document.getElementById('add_item').value = '';
        $('#add_item').focus();
    }

    $(document).on('change','.iQuantity',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        var quantity = parseFloat($(this).val()) || 0 ;
        var cell_quantity =  row[0].cells[4].firstChild;
        cell_quantity.setAttribute('data-original', quantity);
        calcTotals();
    });

    $(document).on('keyup','.iQuantity',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        var quantity = parseFloat($(this).val()) || 0 ;
        var cell_quantity =  row[0].cells[4].firstChild;
        cell_quantity.setAttribute('data-original', quantity);
        calcTotals();
    });

    $(document).on('change','.iNewWeight',function () {

        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }

        var weight = parseFloat($(this).val()) || 0 ;
        var cell_weigth =  row[0].cells[2].firstChild;
        cell_weigth.setAttribute('data-original', weight);
        calcTotals();
    });

    $(document).on('keyup','.iNewWeight',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        var weight = parseFloat($(this).val()) || 0 ;
        var cell_weigth =  row[0].cells[2].firstChild;
        cell_weigth.setAttribute('data-original', weight);
        calcTotals();
    });

    $(document).on('change','.iNewPrice',function () {

        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        var price = parseFloat($(this).val()) || 0 ;
        var cell_price =  row[0].cells[3].firstChild;
        cell_price.setAttribute('data-original', price);
        calcTotals();

    });
    $(document).on('keyup','.iNewPrice',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }

        var price = parseFloat($(this).val()) || 0 ;
        var cell_price =  row[0].cells[3].firstChild;
        cell_price.setAttribute('data-original', price);
        calcTotals();

    });

    $(document).on('change','.iNewTotalWithTax',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }

        const totalWithTax = parseFloat($(this).val()) || 0; 
        var cell_net_total = row[0].cells[8].firstChild;
        cell_net_total.setAttribute('data-original', totalWithTax);

        
        const tax_rate = parseFloat(row[0].cells[10].firstChild.value) || 0; 
        const total = totalWithTax /  (1 + (tax_rate / 100)) ;
        const weigth =  parseFloat(row[0].cells[2].firstChild.value) || 0; ;
        const price = total /  weigth;

        var cell_price = row[0].cells[3].firstChild;
        cell_price.value = price.toFixed(2) ;
        cell_price.setAttribute('data-original', price);

        var cell_total = row[0].cells[6].firstChild;
        cell_total.value = total.toFixed(2) ;
        cell_total.setAttribute('data-original', total);
        
        var tax = total * (tax_rate / 100);
        var cell_tax = row[0].cells[7].firstChild;
       
        cell_tax.value = tax.toFixed(2) ;
        cell_tax.setAttribute('data-original', tax);

        calcTotals(false);
    });

   $(document).on('keyup','.iNewTotalWithTax',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }

        const totalWithTax = parseFloat($(this).val()) || 0; 
        var cell_net_total = row[0].cells[8].firstChild;
        cell_net_total.setAttribute('data-original', totalWithTax);

        
        const tax_rate = parseFloat(row[0].cells[10].firstChild.value) || 0; 
        const total = totalWithTax /  (1 + (tax_rate / 100)) ;
        const weigth =  parseFloat(row[0].cells[2].firstChild.value) || 0; ;
        const price = total /  weigth;

        var cell_price = row[0].cells[3].firstChild;
        cell_price.value = price.toFixed(2) ;
        cell_price.setAttribute('data-original', price);

        var cell_total = row[0].cells[6].firstChild;
        cell_total.value = total.toFixed(2) ;
        cell_total.setAttribute('data-original', total);
        
        var tax = total * (tax_rate / 100);
        var cell_tax = row[0].cells[7].firstChild;
    
        cell_tax.value = tax.toFixed(2) ;
        cell_tax.setAttribute('data-original', tax);

        calcTotals(false);
});
 


    $(document).on('click', '.deleteBtn0', function (event) {
        var row = $(this).parent().parent().index();
        var table = document.getElementById('tbody0');
        table.deleteRow(row);
        calcTotals();
        var audio = $("#mysoundclip2")[0];
        audio.play();
    });

    function is_numeric(mixed_var) {
        var whitespace = ' \n\r\t\f\x0b\xa0\u2000\u2001\u2002\u2003\u2004\u2005\u2006\u2007\u2008\u2009\u200a\u200b\u2028\u2029\u3000';
        return (
            (typeof mixed_var === 'number' || (typeof mixed_var === 'string' && whitespace.indexOf(mixed_var.slice(-1)) === -1)) &&
            mixed_var !== '' &&
            !isNaN(mixed_var)
        );
    }


    function loadItems() {
        $('#sTable tbody').empty();
        var No = 0;
        $.each(sItems, function (i, item) {
            No +=1;
            var newTr = $('<tr data-item-id="' + item.unit_id + '">'); 
            var tr_html= '<td class="text-center"><input type="hidden" name="unit_id[]" data-original="' + item.unit_id + '" value="' + item.unit_id + '"> <strong>' + item.item_name + '</strong>' +'</td>';
            tr_html += '<td><input type="hidden" class="form-control iNewcarats" name="carats_id[]" data-original="' + item.unit_id + '" value="' + item.unit_id + '"> <span>' + item.carat + '</span> </td>';
            tr_html += '<td><input type="text" class="form-control iNewWeight" data-original="' + item.weight + '" name="weight[]" value="' + item.weight + '" ></td>';
            tr_html += '<td><input type="text" class="form-control iNewPrice" data-original="' + item.gram_price + '" name="gram_price[]" value="' + item.gram_price.toFixed(2) + '" ></td>';
            tr_html += '<td><input type="text" class="form-control iNewQuantity" data-original="' + item.quantity + '" name="quantity[]" value="' + item.quantity.toFixed(2) + '" ></td>';
            tr_html += '<td><input type="text" class="form-control iNewNoMetal" data-original="' + item.no_metal + '" name="no_metal[]" value="' + item.no_metal.toFixed(2) + '" ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control iNewTotal" data-original="' + item.item_price + '" name="item_price[]" value="' + (item.weight * item.gram_price).toFixed(2) +  '"    ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control iNewTax" data-original="' + item.item_tax + '" name="item_tax[]" value="' + (item.weight * item.gram_tax_amount ).toFixed(2)  +  '" ></td>';
            tr_html += '<td><input type="text"  class="form-control iNewTotalWithTax" data-original="' + item.net_money + '" name="net_money[]" value="' +  ((item.weight * item.gram_total_amount)).toFixed(2)  +' " ></td>';
            tr_html += '<td hidden><input type="text" class="form-control" name="unit_transform_factor[]" data-original="' + item.carat_transform_factor   +   '" value="' + item.carat_transform_factor   +   ' " ></td>';
            tr_html += '<td hidden><input type="text" class="form-control" name="unit_tax_rate[]" data-original="' + item.gram_tax_percentage   +   '" value="' + item.gram_tax_percentage   +   '" ></td>';
            tr_html += `<td>
                            <button type="button" class="btn btn-danger deleteBtn " value=" '+item.id+' ">
                                <i class="fa fa-close"></i>
                            </button>
                        </td>`;

            newTr.html(tr_html);
            newTr.appendTo('#sTable');
        });
        calcTotals();
        $('#products_suggestions').empty();
    }
 
function calcTotals(updateLineNetTotal = true){
    var items_count = 0;
    var total_weight = 0;
    var total_weight21 = 0;
    var total = 0;
    var total_tax = 0;
    var net_total = 0;

    $("#sTable tbody tr").each(function(index){
        var row = $(this).closest('tr');

        var unit_price = parseFloat(row[0].cells[3].firstChild.getAttribute('data-original')) || 0;
        var line_weight = parseFloat(row[0].cells[2].firstChild.getAttribute('data-original')) || 0;
        var line_weight21 = line_weight * (parseFloat(row[0].cells[9].firstChild.getAttribute('data-original')) || 0);
        var line_tax_rate = parseFloat(row[0].cells[10].firstChild.getAttribute('data-original')) || 0;

        var line_total = unit_price * line_weight;

        var line_tax = line_total * line_tax_rate / 100;

        var line_net_total = line_total + line_tax;

       
        var cell_line_total = row[0].cells[6].firstChild;
        cell_line_total.value = line_total.toFixed(2);
        cell_line_total.setAttribute('data-original', line_total);
        
        var cell_line_tax = row[0].cells[7].firstChild;
        cell_line_tax.value = line_tax.toFixed(2);
        cell_line_tax.setAttribute('data-original', line_tax);
        
        if(updateLineNetTotal){
            var cell_line_net_total = row[0].cells[8].firstChild;
            cell_line_net_total.value = line_net_total.toFixed(2);
            cell_line_net_total.setAttribute('data-original', line_net_total);
        }

        total_weight += line_weight;
        total_weight21 += line_weight21;
        total += line_total;
        total_tax += line_tax;
        net_total += line_net_total;
        items_count += 1;
    });

    
    $("#total").val(total.toFixed(2));
    $("#total_tax").val(total_tax.toFixed(2));
    $("#net_total").val(net_total.toFixed(2));
    $("#total_actual_weight").val(total_weight.toFixed(2));
    $("#total_weight21").val(total_weight21.toFixed(2));
    $("#items_count").val(items_count);
}


</script> 
<script>
    (function () {
        const selector = document.getElementById('invoice_terms_template_selector');
        const textarea = document.getElementById('invoice_terms');
        const templates = @json($invoiceTermTemplates);

        if (!selector || !textarea) {
            return;
        }

        selector.addEventListener('change', function () {
            const selected = templates.find((template) => template.key === this.value);
            if (selected) {
                textarea.value = selected.content;
            }
        });
    })();
</script>
@endsection 
 
