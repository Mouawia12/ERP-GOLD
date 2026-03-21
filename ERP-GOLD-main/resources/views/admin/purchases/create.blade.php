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
    </style>

    <div class="row row-sm">
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
                                    <div class="card shadow mb-4 col-9">
                                        <div class="card-header py-3">
                                            <div class="row">
                                               <div class="col-12"> 
                                                    <h4  class="alert alert-primary text-center">
                                                    {{__('main.purchases_add')}}
                                                    </h4> 
                                                </div> 
                                            </div>  
                                        </div>
                                        <div class="card-body">
                                        <div class="row">
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label>{{ __('main.bill_no') }} <span style="color:red;">*</span> </label>
                                            <input type="text"  id="bill_number" name="bill_number"
                                                   class="form-control" placeholder="" readonly
                                            />
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label>{{ __('main.date') }} <span style="color:red;">*</span> </label>
                                            <input type="datetime-local"  id="date" name="bill_date"
                                                   class="form-control"/>     
                                        </div>
                                    </div>
                                    <div class="col-6">
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
                                                <input class="form-control" type="text" readonly
                                                       value="{{Auth::user()->branch->name}}"/>
                                                <input required class="form-control" type="hidden" id="branch_id"
                                                       name="branch_id"
                                                       value="{{Auth::user()->branch_id}}"/>
                                            @endif
                    
                                        </div>
                                    </div>
                   
                                    <div class="col-3">
                                       <div class="form-group">
                                           <label style="float: right;">{{ __('main.gold_carat_type') }} <span
                                                   style="color:red; ">*</span>
                                           </label>
                                           <select  required=""  class="form-control"
                                                   name="carat_type" id="carat_type">
                                                    @foreach($caratTypes as $caratType)
                                                        <option value="{{$caratType->key}}">{{$caratType->title}}</option>
                                                    @endforeach
                                           </select>
                                       </div>
                                    </div>

                                    <div class="col-3" id="purchase_type_section">
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
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label>{{ __('main.supplier_bill_number') }}</label>
                                            <input type="text"  id="supplier_bill_number" name="supplier_bill_number"
                                                   class="form-control" placeholder="{{__('main.supplier_bill_number')}}"
                                            />
                                        </div>
                                    </div> 
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label>{{ __('main.supplier') }} <span style="color:red;">*</span> </label>
                                            <select id="supplier_id" name="supplier_id" class="js-example-basic-single w-100" required="">
                                                   <option value="">حدد الاختيار</option>
                                                @foreach($customers as $customer)
                                                    <option value="{{$customer -> id}}">{{$customer -> name}}</option>
                                                @endforeach
                                            </select>
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
                                    <div class="card shadow mb-4 col-3">
                                        <div class="card-header py-3">
                                            <h5 class="alert alert-info text-center">{{__('main.purchase_invoice_total')}}</h6>
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
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
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
                                                        class="btn btn-md btn-info w-100" 
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

    $(document).ready(function () {  
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        now.setMilliseconds(null);
        now.setSeconds(null);

        document.getElementById('date').value = now.toISOString().slice(0, -1);
        $(document).on('change', '#carat_type', function () {
            var carat_type = $(this).val();
            if(carat_type == 'crafted'){
                $('#purchase_type_section').show();
                $('#labor_cost_th').show();
            }else{
                $('#purchase_type_section').hide();
                $('#labor_cost_th').hide();
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
        $(document).on('click', '#purchase_btn', function () {
            
            var thisme = $('#purchases_form');
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
                    var message = "";
                    message += result.message;
                    alert(message);
                  setTimeout(function() {
                    suggestionItems = {};
                    thisme[0].reset();
                    window.location.href = "{{route('purchases.index')}}";
                  }, 2000);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    var errors = "";
                    jqXHR.responseJSON.errors.forEach(function(error) {
                        errors += error + "\n";
                    });
                    alert(errors);
                },
                timeout: 8000
            })
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
        if(carat_type == 'crafted'){
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
 



