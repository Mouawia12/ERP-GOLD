@extends('admin.layouts.master')
@section('content')
@can('employee.purchase_invoices.add')  
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
        .invoice-create-page{
            --invoice-surface:#ffffff;
            --invoice-soft:#f7faff;
            --invoice-border:#e1eaf6;
            --invoice-border-strong:#d7e3f4;
            --invoice-title:#31496f;
            --invoice-muted:#5c6d89;
        }
        .invoice-create-page .invoice-primary-card,
        .invoice-create-page .invoice-summary-card{
            border:1px solid var(--invoice-border);
            border-radius:24px;
            overflow:hidden;
            box-shadow:0 20px 44px rgba(20, 49, 92, 0.08);
            background:var(--invoice-surface);
        }
        .invoice-create-page .invoice-primary-card .card-header,
        .invoice-create-page .invoice-summary-card .card-header{
            background:transparent;
            border-bottom:0;
            padding:22px 22px 0;
        }
        .invoice-create-page .invoice-panel-title{
            margin:0;
            padding:16px 18px;
            border-radius:18px;
            background:linear-gradient(135deg,#edf3ff 0%, #dfeafe 100%);
            color:var(--invoice-title);
            font-size:28px;
            font-weight:700;
        }
        .invoice-create-page .invoice-card-body{
            padding:22px;
        }
        .invoice-create-page .invoice-section-card{
            border:1px solid var(--invoice-border);
            background:var(--invoice-soft);
            border-radius:20px;
            padding:18px;
            margin-bottom:18px;
        }
        .invoice-create-page .invoice-section-title{
            display:flex;
            align-items:center;
            gap:8px;
            margin-bottom:16px;
            font-size:15px;
            font-weight:700;
            color:var(--invoice-title);
        }
        .invoice-create-page .invoice-section-title::before{
            content:"";
            width:10px;
            height:10px;
            border-radius:999px;
            background:#8fb4ef;
        }
        .invoice-create-page .form-group{
            margin-bottom:14px;
        }
        .invoice-create-page .form-group > label{
            display:block;
            margin-bottom:7px;
            font-size:13px;
            font-weight:700;
            color:var(--invoice-muted);
        }
        .invoice-create-page .form-control,
        .invoice-create-page .select2-container--default .select2-selection--single{
            min-height:44px;
            border:1px solid var(--invoice-border-strong) !important;
            border-radius:14px !important;
            background:#fff !important;
            box-shadow:none !important;
        }
        .invoice-create-page textarea.form-control{
            min-height:92px;
            padding-top:10px;
            resize:vertical;
        }
        .invoice-create-page input.form-control:not([readonly]):not([type="datetime-local"]):not([type="number"]),
        .invoice-create-page textarea.form-control{
            text-align:right;
        }
        .invoice-create-page input[readonly],
        .invoice-create-page input[type="datetime-local"]{
            text-align:center;
        }
        .invoice-create-page .select2-container{
            width:100% !important;
        }
        .invoice-create-page .select2-selection__rendered{
            line-height:42px !important;
            padding-right:12px !important;
            text-align:right !important;
        }
        .invoice-create-page .select2-selection__arrow{
            height:42px !important;
        }
        .invoice-create-page .custom-control.custom-switch{
            padding:10px 44px 10px 12px;
            border:1px dashed var(--invoice-border-strong);
            border-radius:14px;
            background:#fff;
            min-height:46px;
        }
        .invoice-create-page .invoice-quick-action{
            border-radius:12px;
            padding:10px 12px;
            font-size:13px;
            font-weight:700;
        }
        .invoice-create-page .invoice-search-card .well{
            background:transparent;
            border:0;
            padding:0;
            margin-bottom:0;
        }
        .invoice-create-page .invoice-search-card .input-group{
            border:1px solid var(--invoice-border-strong);
            border-radius:16px;
            overflow:hidden;
            background:#fff;
            box-shadow:0 12px 24px rgba(20, 60, 118, 0.06);
        }
        .invoice-create-page .invoice-search-card .input-group-addon{
            min-width:72px;
            display:flex;
            align-items:center;
            justify-content:center;
            background:linear-gradient(135deg,#eef4ff 0%, #dfeafe 100%);
            color:#40608f;
        }
        .invoice-create-page .invoice-search-card .input-group .form-control{
            border:0 !important;
        }
        .invoice-create-page ul#products_suggestions{
            margin-top:12px;
            border:1px solid var(--invoice-border-strong);
            border-radius:14px;
            background:#fff;
            box-shadow:0 18px 36px rgba(19, 58, 108, 0.08);
        }
        .invoice-create-page ul#products_suggestions:empty{
            display:none !important;
        }
        .invoice-create-page ul#products_suggestions li{
            padding:11px 14px;
            border-bottom:1px solid #eef3fb;
        }
        .invoice-create-page ul#products_suggestions li:last-child{
            border-bottom:0;
        }
        .invoice-create-page .invoice-table-card{
            margin-bottom:0;
            border-radius:18px;
            overflow:hidden;
            border:1px solid var(--invoice-border);
            box-shadow:none;
        }
        .invoice-create-page .invoice-table-card .card-header{
            padding:0;
            border-bottom:0;
            background:transparent;
        }
        .invoice-create-page .invoice-table-card .alert{
            margin:0;
            border-radius:0;
            background:#eef8fb;
            color:#31556a;
        }
        .invoice-create-page #sTable thead th{
            background:#f3f7ff;
            color:#4d6288;
            font-weight:700;
            vertical-align:middle;
        }
        .invoice-create-page .invoice-summary-card{
            position:sticky;
            top:78px;
        }
        .invoice-create-page .invoice-summary-card .card-body{
            padding:18px;
        }
        .invoice-create-page .invoice-summary-card .card-body > .row{
            margin:0 0 10px !important;
            align-items:center;
            padding:12px 14px;
            border-radius:16px;
            border:1px solid var(--invoice-border);
            background:var(--invoice-soft);
        }
        .invoice-create-page .invoice-summary-card .card-body > .row.invoice-summary-actions,
        .invoice-create-page .invoice-summary-card .card-body > .row[hidden]{
            padding:0;
            border:0;
            background:transparent;
        }
        .invoice-create-page .invoice-summary-card label{
            display:block;
            margin:0;
            font-size:13px;
            font-weight:700;
            color:var(--invoice-muted);
            float:none !important;
            text-align:right !important;
        }
        .invoice-create-page .invoice-summary-card .form-control{
            text-align:center;
        }
        .invoice-create-page .invoice-pay-button{
            min-height:48px;
            border-radius:14px;
            font-size:15px;
            font-weight:700;
        }
        @media (max-width: 1199.98px){
            .invoice-create-page .invoice-summary-card{
                position:static;
            }
        }
        @media (max-width: 991.98px){
            .invoice-create-page .invoice-card-body{
                padding:16px;
            }
            .invoice-create-page .invoice-section-card{
                padding:14px;
            }
            .invoice-create-page .invoice-panel-title{
                font-size:22px;
                padding:14px 16px;
            }
        }
    </style>

    <div class="row row-sm invoice-create-page invoice-purchases-page">
        <div class="col-xl-12"> 
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <form method="POST" action="{{ route('purchases.store') }}"
                                  enctype="multipart/form-data" id="purchases_form">
                                @csrf
                                @method('POST')
                                <input type="hidden" name="user_id" value="{{Auth::user()->id}}"/>
                                <input type="hidden" name="uuid" id="uuid" value=""/>
                                <div class="row">
                                    <div class="col-xl-9 col-lg-8 mb-4">
                                    <div class="card shadow invoice-primary-card h-100">
                                        <div class="card-header py-3">
                                            <div class="row">
                                               <div class="col-12"> 
                                                    <h4  class="alert alert-primary text-center invoice-panel-title">
                                                    {{__('main.purchases_add')}}
                                                    </h4> 
                                                </div> 
                                            </div>  
                                        </div>
                                        <div class="card-body invoice-card-body">
                                        <div class="response_container"></div>
                                        <div class="invoice-section-card">
                                        <div class="invoice-section-title">بيانات الفاتورة والمورد</div>
                                        <div class="row">
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.bill_no') }} <span style="color:red;">*</span> </label>
                                            <input type="text"  id="bill_number" name="bill_number"
                                                   class="form-control" placeholder="" readonly
                                            />
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.date') }} <span style="color:red;">*</span> </label>
                                            <input type="datetime-local"  id="date" name="bill_date"
                                                   class="form-control"/>     
                                        </div>
                                    </div>
                                    <div class="col-lg-6 col-md-12">
                                        <div class="form-group">
                                            <label class="d-block">
                                                 الفرع <span style="color:red;">*</span> 
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
                   
                                    <div class="col-lg-3 col-md-6">
                                       <div class="form-group">
                                           <label style="float: right;">{{ __('main.gold_carat_type') }} <span
                                                   style="color:red; ">*</span>
                                           </label>
                                           <select  required=""  class="form-control"
                                                   name="carat_type" id="carat_type">
                                                    @foreach($caratTypes as $caratType)
                                                        <option value="{{$caratType->key}}">{{$caratType->title}}</option>
                                                    @endforeach
                                                    <option value="non_gold">غير ذهبي</option>
                                           </select>
                                       </div>
                                    </div>

                                    <div class="col-lg-3 col-md-6" id="purchase_type_section">
                                       <div class="form-group">
                                           <label style="float: right;">{{ __('main.purchase_type') }} <span
                                                   style="color:red; ">*</span>
                                           </label>
                                           <select  required=""  class="form-control"
                                                   name="purchase_type" id="purchase_type">
                                               <option value="" selected>{{__('main.select')}}</option> 
                                               @foreach(config('settings.purchase_types') as $purchaseType)
                                                    <option value="{{$purchaseType}}" @if($loop->first) selected @endif>{{__('main.purchase_types.'.$purchaseType)}}</option>
                                               @endforeach
                                            
                                           </select>
                                       </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.supplier_bill_number') }}</label>
                                            <input type="text"  id="supplier_bill_number" name="supplier_bill_number"
                                                   class="form-control" placeholder="{{__('main.supplier_bill_number')}}"
                                            />
                                        </div>
                                    </div> 
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.supplier') }} <span style="color:red;">*</span> </label>
                                            <select id="supplier_id" name="supplier_id" class="js-example-basic-single w-100" required="">
                                                   <option value="">حدد الاختيار</option>
                                                @foreach($customers as $customer)
                                                    <option
                                                        value="{{$customer -> id}}"
                                                        data-name="{{ $customer->name }}"
                                                        data-phone="{{ $customer->phone }}"
                                                        data-identity-number="{{ $customer->identity_number }}"
                                                        data-cash-party="{{ $customer->is_cash_party ? 1 : 0 }}"
                                                    >
                                                        {{$customer -> name}}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <div class="custom-control custom-switch text-right mt-2">
                                                <input type="checkbox" class="custom-control-input" id="cash_supplier_only_toggle">
                                                <label class="custom-control-label" for="cash_supplier_only_toggle">
                                                    عرض الموردين النقديين فقط
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>اسم المورد في الفاتورة</label>
                                            <input
                                                type="text"
                                                name="bill_client_name"
                                                class="form-control text-right"
                                                autocomplete="off"
                                            >
                                            @can('employee.suppliers.add')
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary btn-block mt-2 invoice-quick-action"
                                                    id="quick_save_supplier_btn"
                                                    data-url="{{ route('customers.quick-store', ['type' => 'supplier']) }}"
                                                >
                                                    حفظ الاسم الحالي كمورد
                                                </button>
                                                <div class="custom-control custom-switch text-right mt-2">
                                                    <input type="checkbox" class="custom-control-input" id="quick_save_supplier_is_cash_party">
                                                    <label class="custom-control-label" for="quick_save_supplier_is_cash_party">
                                                        حفظه كطرف نقدي
                                                    </label>
                                                </div>
                                            @endcan
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-6">
                                        <div class="form-group">
                                            <label>رقم هاتف المورد</label>
                                            <input
                                                type="text"
                                                name="bill_client_phone"
                                                class="form-control text-right"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label>رقم هوية المورد</label>
                                            <input
                                                type="text"
                                                name="bill_client_identity_number"
                                                class="form-control text-right"
                                                autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-group">
                                            <label>{{ __('main.notes') }}</label>
                                            <textarea
                                                name="notes"
                                                id="notes"
                                                rows="2"
                                                class="form-control"
                                                placeholder="{{ __('main.notes') }}"
                                            >{{ old('notes') }}</textarea>
                                        </div>
                                    </div>
                                </div>
                                </div>
                                            <div class="invoice-section-card invoice-search-card">
                                            <div class="invoice-section-title">البحث عن الأصناف</div>
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
                                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="card mb-4 invoice-table-card">
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
                                                                                <th>{{__('main.quantity_balance')}}</th>
                                                                                <th id="cost_th">{{__('main.item_total_cost')}}</th>
                                                                                <th id="labor_cost_th">{{__('main.item_total_labor_cost')}}</th>
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
                                    </div>
                                    <div class="col-xl-3 col-lg-4 mb-4">
                                    <div class="card shadow invoice-summary-card">
                                        <div class="card-header py-3">
                                            <h5 class="alert alert-info text-center invoice-panel-title">{{__('main.purchase_invoice_total')}}</h6>
                                        </div>
                                        <div class="card-body ">
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
                                                        style="text-align: right;float: right;"> {{__('main.total_cost')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="total_cost">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_labor_cost')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="total_labor_cost">
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
                                                        style="text-align: right;float: right;"> {{__('main.total_tax')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="total_tax">
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">
                                            <div class="row invoice-summary-actions" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label
                                                            style="text-align: right;float: right;"> {{__('اجمالي الفاتورة')}} </label>
                                                        <input type="text" readonly  class="form-control" id="net_total" name="net_total" placeholder="0">
                                                    </div>
                                                </div>
                                                @canany(['employee.purchase_invoices.add'])
                                                <div class="col-md-12 text-center"> 
                                                    <button type="button" 
                                                        class="btn btn-md btn-info w-100 invoice-pay-button" 
                                                        id="purchase_btn" 
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
                                </div> 
                            </form>
                        </div>  
                        <!--purchase TAB-->
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
<script type="text/javascript">
    var suggestionItems = {};
    var sItems = [];
    document.title = "فاتورة شراء";
    var quickSupplierStoreUrl = $('#quick_save_supplier_btn').data('url');

    $(document).ready(function () {  
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        now.setMilliseconds(null);
        now.setSeconds(null);

        document.getElementById('date').value = now.toISOString().slice(0, -1);
        applyCashSupplierFilter();
        syncSelectedSupplierSnapshot();
        $(document).on('change', '#carat_type', function () {
            var carat_type = $(this).val();
            if(carat_type == 'crafted'){
                $('#purchase_type_section').show();
                $('#labor_cost_th').show();
            }else{
                $('#purchase_type_section').hide();
                $('#purchase_type').val('normal');
                $('#labor_cost_th').toggle(carat_type == 'non_gold');
            }
            $('#products_suggestions').empty();
            $('#sTable tbody').empty();
            suggestionItems = {};
            sItems = [];
            loadItems();
        });    
        $(document).on('change', '#purchase_type', function () {
            $('#products_suggestions').empty();
            $('#sTable tbody').empty();
            suggestionItems = {};
            sItems = [];
            loadItems();
        });    
        $(document).on('change', '#supplier_id', function () {
            syncSelectedSupplierSnapshot();
        });
        $(document).on('change', '#cash_supplier_only_toggle', function () {
            applyCashSupplierFilter();
            syncSelectedSupplierSnapshot();
        });
        $(document).on('click', '#quick_save_supplier_btn', function () {
            var button = $(this);
            var name = $.trim($('input[name="bill_client_name"]').val());
            var phone = $.trim($('input[name="bill_client_phone"]').val());
            var identityNumber = $.trim($('input[name="bill_client_identity_number"]').val());
            var isCashParty = $('#quick_save_supplier_is_cash_party').is(':checked') ? 1 : 0;

            if (!name.length) {
                alert('أدخل اسم المورد أولًا قبل الحفظ السريع.');
                return;
            }

            button.prop('disabled', true).text('جاري الحفظ...');

            $.ajax({
                url: quickSupplierStoreUrl,
                type: 'post',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    name: name,
                    phone: phone,
                    identity_number: identityNumber,
                    is_cash_party: isCashParty
                },
                success: function (response) {
                    upsertSupplierOption(
                        response.customer_id,
                        response.customer_name,
                        response.phone,
                        response.identity_number,
                        response.is_cash_party
                    );
                    syncSelectedSupplierSnapshot();
                    alert(response.message);
                },
                error: function (xhr) {
                    alert(extractSupplierErrors(xhr));
                },
                complete: function () {
                    button.prop('disabled', false).text('حفظ الاسم الحالي كمورد');
                }
            });
        });
        $(document).on('click', '#purchase_btn', function () {
            var button = $(this);
            var rows = $('#sTable tbody tr').length;
            var supplierId = $('#supplier_id').val();
            var netTotal = $('#net_total').val();

            if (!supplierId) {
                alert('حدد المورد أولًا.');
                return;
            }

            if (rows <= 0) {
                alert('{{ __('main.no_bill_details') }}');
                return;
            }

            if (Number(netTotal || 0) <= 0) {
                alert('إجمالي الفاتورة يجب أن يكون أكبر من صفر.');
                return;
            }

            button.prop('disabled', true).text('جاري تجهيز الدفع...');
            openPurchasePaymentModal(netTotal, $('#branch_id').val(), button);
        });
        $(document).on('click', '#purchase_payment_btn', function () {
            var thisme = $('#purchases_form');
            var total = Number($('#purchase_money').val() || 0);
            var cash = Number($('#purchase_cash').val() || 0);
            var nonCash = collectPurchasePaymentLines().reduce(function (sum, line) {
                return sum + Number(line.amount || 0);
            }, 0);

            if (Math.abs(total - (cash + nonCash)) >= 0.01) {
                alert('{{ __('main.paid_must_equal_net') }}');
                return;
            }

            let href = thisme.attr('action');
            let method = thisme.attr('method');
            $.ajax({
                url: href,
                type: method,
                data: thisme.serialize(),
                beforeSend: function() {
                    $('.response_container').html('');
                    $('#loader').show();
                },
                success: function(result) {
                    if (result.status === false) {
                        alert(result.message || 'تعذر حفظ الفاتورة.');
                        return;
                    }

                    alert(result.message);
                    setTimeout(function() {
                        suggestionItems = {};
                        thisme[0].reset();
                        window.location.href = "{{route('purchases.index')}}";
                    }, 1000);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR) {
                    var errors = "";
                    if (jqXHR.responseJSON && Array.isArray(jqXHR.responseJSON.errors)) {
                        jqXHR.responseJSON.errors.forEach(function(error) {
                            errors += error + "\n";
                        });
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errors = jqXHR.responseJSON.message;
                    } else {
                        errors = 'تعذر حفظ الفاتورة.';
                    }
                    alert(errors);
                },
                timeout: 8000
            });
        });
        $('#add_item').focus();
        $('#add_item').on('input', function (e) { 
            searchProduct($('#add_item').val());
        });
        $(document).on('change', '#branch_id', function () {
            $('#products_suggestions').empty();
            $('#sTable tbody').empty();
            suggestionItems = {};
            sItems = [];
        });

        $(document).on('click', '.deleteBtn', function (event) {
            var row = $(this).parent().parent().index();
            var row1 = $(this).closest('tr');
            var unit_id = row1.attr('data-item-id');
            sItems = sItems.filter(item => item.unit_id !== unit_id);
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

    function syncSelectedSupplierSnapshot() {
        var selectedOption = $('#supplier_id option:selected');
        $('input[name="bill_client_name"]').val(selectedOption.data('name') || '');
        $('input[name="bill_client_phone"]').val(selectedOption.data('phone') || '');
        $('input[name="bill_client_identity_number"]').val(selectedOption.data('identity-number') || '');
        $('#quick_save_supplier_is_cash_party').prop('checked', String(selectedOption.data('cash-party')) === '1');
    }

    function applyCashSupplierFilter() {
        var select = $('#supplier_id');
        var cashOnly = $('#cash_supplier_only_toggle').is(':checked');
        var firstEnabledValue = null;

        select.find('option').each(function () {
            var option = $(this);
            var isPlaceholder = option.val() === '';
            var isCashParty = String(option.data('cash-party')) === '1';
            var isEnabled = isPlaceholder || !cashOnly || isCashParty;

            option.prop('disabled', !isEnabled);

            if (!isPlaceholder && isEnabled && firstEnabledValue === null) {
                firstEnabledValue = option.val();
            }
        });

        var selectedOption = select.find('option:selected');
        if (!selectedOption.length || selectedOption.prop('disabled')) {
            select.val(firstEnabledValue || '');
        }

        select.trigger('change.select2');
    }

    function upsertSupplierOption(id, name, phone, identityNumber, isCashParty) {
        var select = $('#supplier_id');
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

    function extractSupplierErrors(xhr) {
        if (xhr.responseJSON && Array.isArray(xhr.responseJSON.errors) && xhr.responseJSON.errors.length) {
            return xhr.responseJSON.errors.join('\n');
        }

        return 'حدث خطأ أثناء حفظ بيانات المورد.';
    }

    function extractAjaxErrors(xhr, fallbackMessage) {
        if (xhr.responseJSON && Array.isArray(xhr.responseJSON.errors) && xhr.responseJSON.errors.length) {
            return xhr.responseJSON.errors.join('\n');
        }

        if (xhr.responseJSON && xhr.responseJSON.message) {
            return xhr.responseJSON.message;
        }

        if (xhr.responseText) {
            return xhr.responseText;
        }

        return fallbackMessage;
    }

    function openPurchasePaymentModal(net_total, branch_id, triggerButton) {
        let url = "{{ route('purchases.payments') }}";
        $.ajax({
            type: 'post',
            url: url,
            data: { document_type: 'purchase', net_after_discount: net_total, branch_id: branch_id },
            success: function(data) {
                $(".show_modal1").html(data);
                purchaseCashWasEditedManually = false;
                refreshPurchasePaymentSummary();
                $('#paymentsModal').modal({backdrop: 'static', keyboard: false}, 'show');
            },
            error: function(xhr) {
                alert(extractAjaxErrors(xhr, 'تعذر فتح نافذة الدفع.'));
            },
            complete: function() {
                if (triggerButton && triggerButton.length) {
                    triggerButton.prop('disabled', false).text('حفظ ودفع');
                }
            }
        });
    }

    var purchaseCashWasEditedManually = false;

    function buildPurchaseBankAccountOptions(methodType, selectedId) {
        var options = '<option value="">حدد الحساب البنكي</option>';
        var items = window.currentPurchaseBankAccounts || [];

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

    function refreshPurchasePaymentInputNames() {
        $('#purchase_payment_lines_table tbody tr').each(function (index) {
            var row = $(this);
            row.find('.purchase-payment-line-method').attr('name', 'payment_lines[' + index + '][method_type]');
            row.find('.purchase-payment-line-bank-account').attr('name', 'payment_lines[' + index + '][bank_account_id]');
            row.find('.purchase-payment-line-reference').attr('name', 'payment_lines[' + index + '][reference_no]');
            row.find('.purchase-payment-line-amount').attr('name', 'payment_lines[' + index + '][amount]');
        });
    }

    function appendPurchasePaymentLineRow(line) {
        var methodType = (line && line.method_type) ? line.method_type : 'credit_card';
        var bankAccountId = line && line.bank_account_id ? line.bank_account_id : '';
        var referenceNo = line && line.reference_no ? line.reference_no : '';
        var amount = line && line.amount ? line.amount : '';

        var rowHtml = ''
            + '<tr class="purchase-payment-line-row">'
            + '<td><select class="form-control purchase-payment-line-method"><option value="credit_card"' + (methodType === 'credit_card' ? ' selected' : '') + '>شبكة / بطاقة</option><option value="bank_transfer"' + (methodType === 'bank_transfer' ? ' selected' : '') + '>تحويل بنكي</option></select></td>'
            + '<td><select class="form-control purchase-payment-line-bank-account">' + buildPurchaseBankAccountOptions(methodType, bankAccountId) + '</select></td>'
            + '<td><input type="text" class="form-control purchase-payment-line-reference" value="' + referenceNo + '" placeholder="رقم المرجع"></td>'
            + '<td><input type="number" min="0" step="any" class="form-control purchase-payment-line-amount" value="' + amount + '" placeholder="0.00"></td>'
            + '<td><button type="button" class="btn btn-outline-danger btn-sm remove-purchase-payment-line">حذف</button></td>'
            + '</tr>';

        $('#purchase_payment_lines_table tbody').append(rowHtml);
        refreshPurchasePaymentInputNames();
        refreshPurchasePaymentSummary();
    }

    function collectPurchasePaymentLines() {
        var lines = [];

        $('#purchase_payment_lines_table tbody tr').each(function () {
            var row = $(this);
            var amount = Number(row.find('.purchase-payment-line-amount').val() || 0);

            if (amount <= 0) {
                return;
            }

            lines.push({
                method_type: row.find('.purchase-payment-line-method').val(),
                bank_account_id: row.find('.purchase-payment-line-bank-account').val(),
                reference_no: row.find('.purchase-payment-line-reference').val(),
                amount: amount.toFixed(2)
            });
        });

        return lines;
    }

    function refreshPurchasePaymentSummary() {
        var moneyInput = document.getElementById('purchase_money');
        if (!moneyInput) {
            return;
        }

        var paymentLines = collectPurchasePaymentLines();
        var nonCashTotal = paymentLines.reduce(function (sum, line) {
            return sum + Number(line.amount || 0);
        }, 0);
        var total = Number(moneyInput.value || 0);
        var suggestedCashValue = Math.max(total - nonCashTotal, 0);

        if (!purchaseCashWasEditedManually) {
            $('#purchase_cash').val(suggestedCashValue.toFixed(2));
        }

        var cashValue = Number($('#purchase_cash').val() || 0);
        var remaining = (total - (cashValue + nonCashTotal)).toFixed(2);

        $('#purchase_payment_lines_total').text(nonCashTotal.toFixed(2));
        $('#purchase_payment_remaining').val(remaining);
        refreshPurchasePaymentInputNames();
    }

    $(document).on('click', '#add_purchase_payment_line_btn', function () {
        appendPurchasePaymentLineRow();
    });

    $(document).on('click', '.remove-purchase-payment-line', function () {
        $(this).closest('tr').remove();
        refreshPurchasePaymentInputNames();
        refreshPurchasePaymentSummary();
    });

    $(document).on('change', '.purchase-payment-line-method', function () {
        var row = $(this).closest('tr');
        row.find('.purchase-payment-line-bank-account').html(buildPurchaseBankAccountOptions($(this).val(), ''));
        refreshPurchasePaymentInputNames();
        refreshPurchasePaymentSummary();
    });

    $(document).on('input', '#purchase_cash', function () {
        purchaseCashWasEditedManually = true;
        refreshPurchasePaymentSummary();
    });

    $(document).on('keyup change', '#purchase_cash, .purchase-payment-line-amount, .purchase-payment-line-bank-account, .purchase-payment-line-reference', function () {
        refreshPurchasePaymentSummary();
    });

    function searchProduct(code) {
        var carat_type = document.getElementById('carat_type').value;
        let branch_id = document.getElementById('branch_id').value; 
        let url = "{{route('items.purchases.search')}}";
        $.ajax({
            type: 'post',
            url: url,
            data: {
                code: code,
                branch_id: branch_id,
                carat_type: carat_type
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
        var findUnit = sItems.find(unit => unit.unit_id == item.unit_id);
        if (findUnit) {
            alert('هذا الصنف موجود');
            return;
        } else {
            sItems.push(item);
        }
        loadItems();

        document.getElementById('add_item').value = '';
        $('#add_item').focus();
    }


    $(document).on('change','.item_total_cost,.item_total_labor_cost,.unit_weight',function () {

        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        calcTotals();
    });
    $(document).on('keyup','.item_total_cost,.item_total_labor_cost,.unit_weight',function () {
        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        calcTotals();
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
        var carat_type = $('#carat_type').val();
        var purchase_type = $('#purchase_type').val();
        console.log(sItems);
        $.each(sItems, function (i, item) {
            No +=1;
            var newTr = $('<tr data-item-id="' + item.unit_id + '">'); 
            var tr_html= '<td class="text-center"><input type="hidden" name="unit_id[]" value="' + item.unit_id + '"> <strong>' + item.item_name + '</strong>' +'</td>';
            tr_html += '<td><input type="hidden" class="form-control iNewcarats" name="carats_id[]" value="' + item.carat_id + '"> <span>' + item.carat + '</span> </td>';
            tr_html += '<td><input type="text" class="form-control unit_weight" name="weight[]" value="' + item.weight + '" ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control" name="quantity_balance[]" value="' + item.quantity_balance.toFixed(2) + '" ></td>';
            if(purchase_type == 'normal'){
                tr_html += '<td><input type="text" class="form-control item_total_cost" name="item_total_cost[]" value=""    ></td>';
            }else{
                tr_html += '<td><input type="text" class="form-control item_total_cost" disabled name="item_total_cost[]" value=""    ></td>';
            
            }
            tr_html += '<td><input type="text" class="form-control item_total_labor_cost" name="item_total_labor_cost[]" value="" ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control unit_total" name="net_money[]" value="" ></td>';
            tr_html += '<td hidden><input type="text" class="form-control" name="unit_transform_factor[]" value="' + item.carat_transform_factor + '" ></td>';
            tr_html += '<td hidden><input type="text" class="form-control" name="unit_tax_rate[]" value="' + item.gram_tax_percentage + '" ></td>';
            tr_html += `<td>
                            <button type="button" class="btn btn-danger deleteBtn " value=" '+item.id+' ">
                                <i class="fa fa-close"></i>
                            </button>
                        </td>`;

            newTr.html(tr_html);
            newTr.appendTo('#sTable');
        });
        if(carat_type == 'crafted' || carat_type == 'non_gold'){
            $('.item_total_labor_cost').parent().show();
        }else{
            $('.item_total_labor_cost').parent().hide();
        }
        calcTotals();
        $('#products_suggestions').empty();
    }
 
    function calcTotals(){
        var total_weight = 0;
        var total_weight21 = 0;
        var total = 0;
        var total_cost = 0;
        var total_labor_cost = 0;
        var total_tax = 0;
        var net_total = 0;
        $( "#sTable tbody tr").each( function( index ) {
            var row = $(this).closest('tr');
            var line_weight = parseFloat(row[0].cells[2].firstChild.value) || 0;
            var line_weight21 = line_weight * (parseFloat(row[0].cells[7].firstChild.value) || 0);
            var line_cost = parseFloat(row[0].cells[4].firstChild.value) || 0;
            var line_labor_cost = parseFloat(row[0].cells[5].firstChild.value) || 0;
            var line_tax_rate = parseFloat(row[0].cells[8].firstChild.value) || 0;
            var line_total = parseFloat(line_cost + line_labor_cost) || 0;
            var line_tax = parseFloat(line_total * line_tax_rate / 100) || 0;
            row[0].cells[6].firstChild.value = line_total.toFixed(2);
            total_weight += line_weight;
            total_weight21 += line_weight21;
            total += line_total;
            total_cost += line_cost;
            total_labor_cost += line_labor_cost;
            total_tax += line_tax;
            net_total += line_total + line_tax;
        });
        $("#total").val(total.toFixed(2));
        $("#total_cost").val(total_cost.toFixed(2));
        $("#total_labor_cost").val(total_labor_cost.toFixed(2));
        $("#net_total").val(net_total.toFixed(2));
        $("#total_actual_weight").val(total_weight.toFixed(2));
        $("#total_weight21").val(total_weight21.toFixed(2));
        $("#total_tax").val(total_tax.toFixed(2));
        $("#net_total").val(net_total.toFixed(2));
    }
</script> 
@endsection 
 
