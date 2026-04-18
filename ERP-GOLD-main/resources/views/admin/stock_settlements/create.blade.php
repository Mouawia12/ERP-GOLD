@extends('admin.layouts.master')
@section('content')
@can('employee.stock_settlements.add')  
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

        .stock-settlement-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .stock-settlement-barcode-meta {
            min-width: 180px;
            max-width: 280px;
            font-size: 12px;
            line-height: 1.7;
            white-space: normal;
            word-break: break-word;
        }

        .stock-settlement-page {
            overflow-x: hidden;
        }

        .stock-settlement-shell {
            width: 100%;
            max-width: 100%;
            overflow-x: hidden;
        }

        .stock-settlement-layout {
            margin-right: -10px;
            margin-left: -10px;
        }

        .stock-settlement-layout > [class*="col-"] {
            padding-right: 10px;
            padding-left: 10px;
            margin-bottom: 20px;
            min-width: 0;
        }

        .stock-settlement-main-card,
        .stock-settlement-summary-card {
            height: 100%;
            margin-bottom: 0;
        }

        .stock-settlement-main-card .card-body,
        .stock-settlement-summary-card .card-body {
            overflow-x: hidden;
        }

        .stock-settlement-form-row {
            margin-right: -8px;
            margin-left: -8px;
        }

        .stock-settlement-form-row > [class*="col-"] {
            padding-right: 8px;
            padding-left: 8px;
            margin-bottom: 12px;
        }

        .stock-settlement-table-wrap {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
            overflow-y: hidden;
        }

        .stock-settlement-table-wrap table {
            min-width: 760px;
            margin-bottom: 0;
        }

        #sticker,
        #sticker .well,
        #sticker .wide-tip {
            width: 100%;
            max-width: 100%;
        }

        @media (min-width: 1400px) {
            .stock-settlement-main-col {
                flex: 0 0 72%;
                max-width: 72%;
            }

            .stock-settlement-summary-col {
                flex: 0 0 28%;
                max-width: 28%;
            }
        }
    </style>

    <div class="row row-sm stock-settlement-page">
        <div class="col-12"> 
                <div class="stock-settlement-shell pt-0 pb-2">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <form method="POST" action="{{ route('stock_settlements.store') }}"
                                  enctype="multipart/form-data" id="stock_settlements_form">
                                @csrf
                                @method('POST')
                                <input type="hidden" name="user_id" value="{{Auth::user()->id}}"/>
                                <input type="hidden" name="uuid" id="uuid" value=""/>
                                <div class="row stock-settlement-layout">
                                    <div class="col-12 stock-settlement-main-col">
                                    <div class="card shadow stock-settlement-main-card">
                                        <div class="card-header py-3">
                                            <div class="stock-settlement-toolbar">
                                                <h4 class="alert alert-primary text-center mb-0 flex-grow-1">
                                                    {{__('main.stock_settlements_add')}}
                                                </h4>
                                                <button
                                                    type="button"
                                                    class="btn btn-outline-primary no-print"
                                                    id="print_current_settlement_btn"
                                                >
                                                    <i class="fa fa-print ml-1"></i>
                                                    طباعة قائمة الجرد
                                                </button>
                                            </div>  
                                        </div>
                                        <div class="card-body">
                                        <div class="response_container mb-3"></div>
                                        <div class="row stock-settlement-form-row">
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <div class="form-group">
                                            <label>{{ __('main.bill_no') }} <span style="color:red;">*</span> </label>
                                            <input type="text"  id="bill_number" name="bill_number"
                                                   class="form-control" placeholder="" readonly
                                            />
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <div class="form-group">
                                            <label>{{ __('main.date') }} <span style="color:red;">*</span> </label>
                                            <input type="datetime-local"  id="date" name="bill_date"
                                                   class="form-control"/>     
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-xl-3">
                                        <div class="form-group">
                                            <label class="d-block">
                                                 الفرع <span style="color:red;">*</span> 
                                            </label>
                                            @if(empty(Auth::user()->branch_id))
                                                <select required  class="js-example-basic-single w-100" name="branch_id" id="branch_id"> 
                                                    @foreach($branches as $branch)
                                                        <option value="{{$branch->id}}">{{$branch->name}}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input class="form-control" type="text" readonly id="branch_name_display"
                                                       value="{{Auth::user()->branch->name}}"/>
                                                <input required class="form-control" type="hidden" id="branch_id"
                                                       name="branch_id"
                                                       value="{{Auth::user()->branch_id}}"/>
                                            @endif
                    
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-6 col-xl-3">
                                    <div class="form-group">
                                            <label>{{ __('main.stock_settlements_account') }} </label>
                                            <select class="js-example-basic-single w-100" id="account_id" name="account_id">
                                                @foreach($accounts as $account)
                                                    <option value="{{$account->id}}" >{{$account->name}}</option>
                                                @endforeach
                                            </select>
                                            @error('account_id')
                                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                            @enderror
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
                                                <div class="row stock-settlement-form-row">
                                                    <div class="col-md-12">
                                                        <div class="card mb-4">
                                                           <div class="card-header pb-0">
                                                                <h4   class="alert alert-info text-center">
                                                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i> 
                                                                    {{__('اصناف الفاتورة')}} 
                                                                </h4>
                                                            </div>
                                                            <div class="card-body px-0 pt-0 pb-2">
                                                                <div class="table-responsive hoverable-table stock-settlement-table-wrap">
                                                                    <table class="display w-100 table-bordered" id="sTable" 
                                                                           style="text-align: center;">
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="col-md-3" >{{__('main.item_name')}}</th>
                                                                                <th class="col-md-1" >{{__('main.item_carats')}}</th>
                                                                                <th>{{__('main.barcode')}} / {{__('main.total_units')}}</th>
                                                                                <th>{{__('main.current_weight')}}</th>
                                                                                <th>{{__('main.entry_weight')}}</th>
                                                                                <th>{{__('main.diff_weight')}}</th>
                                                                                <th>الإجراء</th>
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
                                    <div class="col-12 stock-settlement-summary-col">
                                    <div class="card shadow stock-settlement-summary-card">
                                        <div class="card-header py-3">
                                            <h5 class="alert alert-info text-center">{{__('main.purchase_invoice_total')}}</h6>
                                        </div>
                                        <div class="card-body ">
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_items')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_items">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_units')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_units" name="total_units">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.show_uncounted_items')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <button type="button" 
                                                        class="btn btn-md btn-info w-100" 
                                                        id="show_uncounted_items_btn" 
                                                        value="show">
                                                            عرض
                                                    </button>
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-md-12 text-center mb-2">
                                                    <button type="button" 
                                                        class="btn btn-md btn-outline-primary w-100" 
                                                        id="print_current_settlement_btn_side" 
                                                        value="print">
                                                            طباعة القائمة الحالية
                                                    </button>
                                                </div>
                                                @canany(['employee.stock_settlements.add'])
                                                <div class="col-md-12 text-center"> 
                                                    <button type="button" 
                                                        class="btn btn-md btn-info w-100" 
                                                        id="stock_settlements_btn" 
                                                        value="save">
                                                            حفظ
                                                    </button> 
                                                </div>
                                                @endcan 
                                            </div>
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

<div class="modal fade" id="uncountedItemsModal" tabindex="-1" role="dialog" aria-labelledby="uncountedItemsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uncountedItemsModalLabel">{{__('main.show_uncounted_items')}}</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>{{__('main.item_name')}}</th>
                            <th>{{__('main.carats')}}</th>
                            <th>{{__('main.barcode')}}</th>
                            <th>{{__('main.weight')}}</th>
                        </tr>
                    </thead>
                    <tbody id="uncountedItemsTableBody">
                        
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{__('main.close')}}</button>
            </div>
        </div>
    </div>
</div>
@endcan 
@endsection 
@section('js')
<script type="text/javascript">
    var suggestionItems = {};
    var selectedItems = [];
    var selectedUnits = [];
    var barcodeSearchTimer = null;
    var count = 1; 
    document.title = "{{__('main.stock_settlements_add')}}";

    $(document).ready(function () { 
        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function queueSearchProduct(code, immediate) {
            clearTimeout(barcodeSearchTimer);

            if (!String(code || '').trim()) {
                $('#products_suggestions').empty();
                return;
            }

            if (immediate) {
                searchProduct(code);
                return;
            }

            barcodeSearchTimer = setTimeout(function () {
                searchProduct(code);
            }, 180);
        }
        
        $(document).on('click', '#show_uncounted_items_btn', function () {
            let href = "{{route('stock_settlements.show_uncounted_items')}}";
            let method = 'POST';
            $.ajax({
                url: href,
                type: method,
                data: {
                    units: selectedUnits,
                    branch_id: $('#branch_id').val(),
                },
                beforeSend: function() {
                    $('#loader').show();
                },
                success: function(result) {
                    $('#uncountedItemsTableBody').empty();
                    $('#uncountedItemsTableBody').html(result.data);
                    $('#uncountedItemsModal').modal('show');
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    if (typeof window.erpShowError === 'function') {
                        window.erpShowError('تعذر تحميل الأصناف غير المعدودة.');
                    }
                },
                timeout: 8000
            })
        });


        $('#add_item').focus();

        $(document).on('change', '#branch_id', function () {
            $('#products_suggestions').empty();
            $('#sTable tbody').empty();
            suggestionItems = {};
            selectedItems = [];
            selectedUnits = [];
            count = 1; 
            calcTotals();
        });

        $(document).on('click', '#stock_settlements_btn', function () {
            
            var thisme = $('#stock_settlements_form');
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
                    if (typeof window.erpShowSuccessToast === 'function') {
                        window.erpShowSuccessToast(result.message || 'تم حفظ الجرد بنجاح.', 'تم حفظ الجرد');
                    }

                    setTimeout(function() {
                        suggestionItems = {};
                        selectedItems = [];
                        selectedUnits = [];
                        thisme[0].reset();
                        window.location.href = "{{route('stock_settlements.index')}}";
                    }, 1200);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    var errors = [];

                    if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                        errors = jqXHR.responseJSON.errors;
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errors = [jqXHR.responseJSON.message];
                    } else {
                        errors = ['حدث خطأ غير متوقع أثناء حفظ الجرد.'];
                    }

                    $('.response_container').html(
                        "<div class='alert alert-danger'><ul style='margin: 0;'>" +
                        errors.map(function(errorText) {
                            return '<li>' + escapeHtml(errorText) + '</li>';
                        }).join('') +
                        "</ul></div>"
                    );
                },
                timeout: 8000
            })
        });

        $(document).on('click', '#print_current_settlement_btn, #print_current_settlement_btn_side', function () {
            printCurrentSettlementSheet();
        });
   
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        now.setMilliseconds(null);
        now.setSeconds(null);

        document.getElementById('date').value = now.toISOString().slice(0, -1);
        
        $('#add_item').on('input', function () { 
            queueSearchProduct($('#add_item').val(), false);
        });

        $('#add_item').on('keydown', function (event) {
            if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                queueSearchProduct($('#add_item').val(), true);
            }
        });

        $(document).on('click', '.cancel-modal', function (event) {
            $('#deleteModal').modal("hide");
            $('#ItemMaterialModalDialog').modal("hide");
            id = 0;
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

        calcTotals();
    });
    function searchProduct(code) {
        code = String(code || '').trim();

        if (!code) {
            return;
        }

        let branch_id = document.getElementById('branch_id').value; 

        if (!branch_id) {
            if (typeof window.erpShowWarning === 'function') {
                window.erpShowWarning('اختر الفرع أولًا قبل البدء بالجرد.');
            }

            return;
        }

        let url = "{{route('stock_settlements.search')}}";
        $.ajax({
            type: 'post',
            url: url,
            data: {
                code: code,
                branch_id: branch_id,
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
                        if (typeof window.erpShowWarning === 'function') {
                            window.erpShowWarning('لم يتم العثور على صنف مطابق للباركود أو الاسم المدخل.', 'لا توجد نتيجة');
                        }
                        document.getElementById('add_item').value = '';
                    }
                } else {
                    if (typeof window.erpShowWarning === 'function') {
                        window.erpShowWarning('لم يتم العثور على صنف مطابق للباركود أو الاسم المدخل.', 'لا توجد نتيجة');
                    }
                    document.getElementById('add_item').value = '';
                }
            },
            error: function (err){
                if (typeof window.erpShowError === 'function') {
                    window.erpShowError('تعذر البحث عن الصنف. حاول مرة أخرى.');
                }
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

    function addItemToTable(item) {
        suggestionItems = {};
        $('#products_suggestions').empty();
        addItems(item);
        loadItems();
        document.getElementById('add_item').value = '';
        $('#add_item').focus();
    }
    function addItems(selectedItem){
        var findUnit = selectedUnits.find(unit => unit.unit_id == selectedItem.unit_id);
        if(findUnit){
            if (typeof window.erpShowWarning === 'function') {
                window.erpShowWarning('تمت إضافة هذه القطعة مسبقًا داخل قائمة الجرد.');
            }
            return;
        }else{
            selectedUnits.push({item_id : selectedItem.item_id, unit_id: selectedItem.unit_id});
        }

        var findItem = selectedItems.find(item => item.item_id == selectedItem.item_id);
        if(findItem){
            findItem.weight = parseFloat(findItem.weight || 0) + parseFloat(selectedItem.weight || 0);
            findItem.diff_weight = findItem.weight - findItem.actual_balance;
            findItem.units_count = parseInt(findItem.units_count || 1, 10) + 1;
            findItem.barcodes = (findItem.barcodes || []).concat([selectedItem.barcode]).filter(Boolean);
        }else{
            var itemPayload = Object.assign({}, selectedItem);
            itemPayload.weight = parseFloat(itemPayload.weight || 0);
            itemPayload.actual_balance = parseFloat(itemPayload.actual_balance || 0);
            itemPayload.diff_weight = itemPayload.weight - itemPayload.actual_balance;
            itemPayload.units_count = 1;
            itemPayload.barcodes = itemPayload.barcode ? [itemPayload.barcode] : [];
            selectedItems.push(itemPayload);
        }
    }

    $(document).on('click', '.deleteBtn', function (event) {
        var row = $(this).closest('tr');
        var item_id = row[0].cells[0].firstChild.value;
        selectedItems = selectedItems.filter(item => item.item_id != item_id);
        selectedUnits = selectedUnits.filter(unit => unit.item_id != item_id);
        loadItems();
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

    $(document).on('change','.current_weight',function () {

        var row = $(this).closest('tr');
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        calcTotals();
    });

    $(document).on('keyup','.current_weight',function () {
        var row = $(this).closest('tr');
            if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
                $(this).val(0);
                alert('wrong value');
                return;
        }
        calcTotals();
    });

    function loadItems() {

        $('#sTable tbody').empty();
        $.each(selectedItems, function (i, item) {
            var newTr = $('<tr data-item-id="' + item.item_id + '">'); 
            var barcodeSummary = (item.barcodes || []).map(function (barcodeValue) {
                return escapeHtml(barcodeValue);
            }).join('<br>');
            var tr_html= '<td class="text-center"><input type="hidden" name="item_id[]" value="' + item.item_id + '"> <strong>' + item.item_name + '</strong>' +'</td>';
            tr_html += '<td><input type="hidden" class="form-control iNewcarats" name="carats_id[]" value="' + item.carat_id + '"> <span>' + item.carat + '</span> </td>';
            tr_html += '<td><div class="stock-settlement-barcode-meta"><strong>' + escapeHtml(item.units_count) + ' وحدة</strong><br>' + (barcodeSummary || '-') + '</div></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control" name="actual_balance[]" value="' + item.actual_balance.toFixed(2) + '" ></td>';
            tr_html += '<td><input type="text" class="form-control current_weight" name="weight[]" value="' + item.weight + '" ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control diff_weight" name="diff_weight[]" value="' + item.diff_weight.toFixed(2) + '" ></td>';
            tr_html += `<td>
                            <button type="button" class="btn btn-danger deleteBtn " value="${i}">
                                <i class="fa fa-close"></i>
                            </button>
                        </td>`;

            newTr.html(tr_html);
            newTr.appendTo('#sTable');
        });
        calcTotals();
        $('#products_suggestions').empty();
    }
 
    function calcTotals(){
        $( "#sTable tbody tr").each( function( index ) {
            var row = $(this).closest('tr');
            var line_actual_balance = parseFloat(row.find('input[name="actual_balance[]"]').val()) || 0;
            var line_current_weight = parseFloat(row.find('input[name="weight[]"]').val()) || 0;
            row.find('input[name="diff_weight[]"]').val((line_current_weight - line_actual_balance).toFixed(2));
        });

        syncSelectedItemsFromTable();

        var total_items = selectedItems.length;
        var total_units = selectedUnits.length;
        $("#total_items").val(total_items);
        $("#total_units").val(total_units);
        $('#print_current_settlement_btn, #print_current_settlement_btn_side').prop('disabled', total_items === 0);
    }

    function syncSelectedItemsFromTable() {
        $("#sTable tbody tr").each(function () {
            var row = $(this).closest('tr');
            var itemId = parseInt(row.find('input[name="item_id[]"]').val(), 10);
            var lineCurrentWeight = parseFloat(row.find('input[name="weight[]"]').val()) || 0;
            var lineDiffWeight = parseFloat(row.find('input[name="diff_weight[]"]').val()) || 0;
            var matchedItem = selectedItems.find(function (item) {
                return parseInt(item.item_id, 10) === itemId;
            });

            if (matchedItem) {
                matchedItem.weight = lineCurrentWeight;
                matchedItem.diff_weight = lineDiffWeight;
            }
        });
    }

    function printCurrentSettlementSheet() {
        if (!selectedItems.length) {
            if (typeof window.erpShowWarning === 'function') {
                window.erpShowWarning('أضف قطعة واحدة على الأقل قبل طباعة قائمة الجرد.');
            }

            return;
        }

        syncSelectedItemsFromTable();

        var branchName = $('#branch_id option:selected').text().trim() || $('#branch_name_display').val() || '';
        var accountName = $('#account_id option:selected').text().trim();
        var settlementDate = $('#date').val() || '';
        var tableRows = selectedItems.map(function (item, index) {
            var barcodeSummary = (item.barcodes || []).map(function (barcodeValue) {
                return escapeHtml(barcodeValue);
            }).join('<br>');
            var printableCarat = String(item.carat || '').replace(/<br\s*\/?>/gi, ' / ');

            return ''
                + '<tr>'
                + '<td>' + (index + 1) + '</td>'
                + '<td>' + escapeHtml(item.item_name_without_break || '') + '</td>'
                + '<td>' + escapeHtml(printableCarat) + '</td>'
                + '<td>' + escapeHtml(item.units_count || 0) + '</td>'
                + '<td>' + (barcodeSummary || '-') + '</td>'
                + '<td>' + Number(item.actual_balance || 0).toFixed(2) + '</td>'
                + '<td>' + Number(item.weight || 0).toFixed(2) + '</td>'
                + '<td>' + Number(item.diff_weight || 0).toFixed(2) + '</td>'
                + '</tr>';
        }).join('');

        var printWindow = window.open('', '_blank', 'width=1200,height=800');

        if (!printWindow) {
            if (typeof window.erpShowWarning === 'function') {
                window.erpShowWarning('تعذر فتح نافذة الطباعة. تأكد من السماح بالنوافذ المنبثقة.');
            }

            return;
        }

        printWindow.document.write(`
            <!DOCTYPE html>
            <html dir="rtl" lang="ar">
            <head>
                <meta charset="UTF-8">
                <title>قائمة الجرد</title>
                <style>
                    body { font-family: Tahoma, Arial, sans-serif; direction: rtl; margin: 24px; color: #1f2937; }
                    h1 { margin: 0 0 16px; font-size: 24px; text-align: center; }
                    .meta { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
                    .meta div { background: #f8fafc; border: 1px solid #dbe4f0; border-radius: 10px; padding: 10px 12px; min-width: 180px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
                    th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: center; vertical-align: top; font-size: 13px; }
                    th { background: #e8f1ff; }
                    .summary { margin-top: 16px; display: flex; gap: 12px; }
                    .summary div { border: 1px solid #dbe4f0; border-radius: 10px; padding: 10px 12px; background: #f8fafc; }
                </style>
            </head>
            <body>
                <h1>قائمة الجرد</h1>
                <div class="meta">
                    <div><strong>الفرع:</strong> ${escapeHtml(branchName || '-')}</div>
                    <div><strong>الحساب:</strong> ${escapeHtml(accountName || '-')}</div>
                    <div><strong>التاريخ:</strong> ${escapeHtml(settlementDate || '-')}</div>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>الصنف</th>
                            <th>العيار</th>
                            <th>عدد الوحدات</th>
                            <th>الباركودات</th>
                            <th>الوزن النظامي</th>
                            <th>الوزن المعدود</th>
                            <th>الفرق</th>
                        </tr>
                    </thead>
                    <tbody>${tableRows}</tbody>
                </table>
                <div class="summary">
                    <div><strong>عدد الأصناف:</strong> ${selectedItems.length}</div>
                    <div><strong>عدد الوحدات:</strong> ${selectedUnits.length}</div>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
    }
</script> 
@endsection 
 
