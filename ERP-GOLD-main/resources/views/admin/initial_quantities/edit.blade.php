@extends('admin.layouts.master')
@section('content')
@can('employee.initial_quantities.edit')
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
                            <form method="POST" action="{{ route('initial_quantities.update', $invoice->id) }}"
                                  enctype="multipart/form-data" id="initial_quantities_form">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="user_id" value="{{Auth::user()->id}}"/>
                                <input type="hidden" name="uuid" id="uuid" value=""/>
                                <div class="row">
                                    <div class="card shadow mb-4 col-9">
                                        <div class="card-header py-3">
                                            <div class="row">
                                               <div class="col-12">
                                                    <h4  class="alert alert-primary text-center">
                                                    {{__('main.edit')}} - {{__('main.initial_quantities')}}
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
                                                   value="{{ $invoice->bill_number }}"
                                            />
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label>{{ __('main.date') }} <span style="color:red;">*</span> </label>
                                            <input type="datetime-local"  id="date" name="bill_date"
                                                   class="form-control"
                                                   value="{{ \Carbon\Carbon::parse($invoice->date.' '.$invoice->time)->format('Y-m-d\TH:i') }}"/>
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label class="d-block">
                                                 الفرع <span style="color:red;">*</span>
                                            </label>
                                            @if(empty(Auth::user()->branch_id))
                                                <select required  class="js-example-basic-single w-100" name="branch_id" id="branch_id">
                                                    @foreach($branches as $branch)
                                                        <option value="{{$branch->id}}" @selected($branch->id == $invoice->branch_id)>{{$branch->name}}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input class="form-control" type="text" readonly
                                                       value="{{ optional($invoice->branch)->name }}"/>
                                                <input required class="form-control" type="hidden" id="branch_id"
                                                       name="branch_id"
                                                       value="{{ $invoice->branch_id }}"/>
                                            @endif

                                        </div>
                                    </div>
                                    <div class="col-3">
                                    <div class="form-group">
                                            <label>{{ __('main.credit_account') }} </label>
                                            <select class="js-example-basic-single w-100" id="credit_account" name="credit_account">
                                                @foreach($accounts as $account)
                                                    <option value="{{$account->id}}" @selected($account->id == $invoice->account_id)>{{$account->name}}</option>
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
                                                                                <th>{{__('main.item_total_cost')}}</th>
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
                                            <hr class="sidebar-divider d-none d-md-block">
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-12">
                                                    <div class="form-group">
                                                        <label
                                                            style="text-align: right;float: right;"> {{__('اجمالي الفاتورة')}} </label>
                                                        <input type="text" readonly  class="form-control" id="net_total" name="net_total" placeholder="0">
                                                    </div>
                                                </div>
                                                <div class="col-md-12 text-center">
                                                    <button type="button"
                                                        class="btn btn-md btn-info w-100"
                                                        id="initial_quantities_btn"
                                                        value="update">
                                                            {{__('main.edit')}}
                                                    </button>
                                                </div>
                                                <div class="col-md-12 text-center mt-2">
                                                    <a href="{{ route('initial_quantities.index') }}" class="btn btn-md btn-secondary w-100">
                                                        {{__('main.cancel_btn')}}
                                                    </a>
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

@endcan
@endsection
@section('js')
<script type="text/javascript">
    var suggestionItems = {};
    var sItems = {};
    var count = 1;
    var seedItems = @json($lineSeeds);
    document.title = "{{__('main.initial_quantities')}}";

    $(document).ready(function () {
        $('#add_item').focus();

        // Pre-load the document's existing lines.
        if (Array.isArray(seedItems)) {
            seedItems.forEach(function (it) {
                sItems[it.unit_id] = {
                    unit_id: it.unit_id,
                    item_name: it.item_name,
                    carat: it.carat,
                    weight: it.weight,
                    quantity_balance: parseFloat(it.quantity_balance) || 0,
                    item_total_cost: it.item_total_cost,
                    carat_transform_factor: it.carat_transform_factor
                };
            });
            loadItems();
        }

        $(document).on('change', '#branch_id', function () {
            $('#products_suggestions').empty();
        });

        $(document).on('click', '#initial_quantities_btn', function () {

            var thisme = $('#initial_quantities_form');
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
                    sItems = {};
                    suggestionItems = {};
                    window.location.href = "{{route('initial_quantities.index')}}";
                  }, 1500);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    var errors = "";
                    if (jqXHR.responseJSON && jqXHR.responseJSON.errors) {
                        jqXHR.responseJSON.errors.forEach(function(error) {
                            errors += error + "\n";
                        });
                    } else if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                        errors = jqXHR.responseJSON.message;
                    } else {
                        errors = error;
                    }
                    alert(errors);
                },
                timeout: 8000
            })
        });

        $('#add_item').on('input', function (e) {
            searchProduct($('#add_item').val());
        });

        $(document).on('click', '.cancel-modal', function (event) {
            $('#deleteModal').modal("hide");
            $('#ItemMaterialModalDialog').modal("hide");
            id = 0;
        });

        $(document).on('click', '.deleteBtn', function (event) {
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
    function searchProduct(code) {
        let branch_id = document.getElementById('branch_id').value;
        let url = "{{route('items.initial_quantities.search')}}";
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
        document.getElementById('add_item').value = '';
    }

    function addItemToTable(item) {
        suggestionItems = [];
        $('#products_suggestions').empty();
        if (sItems[item.unit_id]) {
            alert('هذا الصنف موجود');
            return;
        } else {
            item.item_total_cost = 0;
            sItems[item.unit_id] = item;
        }
        count++;
        loadItems();

        document.getElementById('add_item').value = '';
        $('#add_item').focus();
    }


    $(document).on('change','.item_total_cost,.unit_weight',function () {

        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        calcTotals();
    });
    $(document).on('keyup','.item_total_cost,.unit_weight',function () {
        if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
            $(this).val(0);
            alert('wrong value');
            return;
        }
        calcTotals();
    });


    function is_numeric(mixed_var) {
        var whitespace = ' \n\r\t\f\x0b\xa0           ​  　';
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
            var tr_html= '<td class="text-center"><input type="hidden" name="unit_id[]" value="' + item.unit_id + '"> <strong>' + item.item_name + '</strong>' +'</td>';
            tr_html += '<td><input type="hidden" class="form-control iNewcarats" name="carats_id[]" value="' + item.unit_id + '"> <span>' + item.carat + '</span> </td>';
            tr_html += '<td><input type="text" class="form-control unit_weight" name="weight[]" value="' + item.weight + '" ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control" name="quantity_balance[]" value="' + (parseFloat(item.quantity_balance)||0).toFixed(2) + '" ></td>';
            tr_html += '<td><input type="text" class="form-control item_total_cost" name="item_total_cost[]" value="' + item.item_total_cost + '"    ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control unit_total" name="net_money[]" value="' + item.item_total_cost + '" ></td>';
            tr_html += '<td hidden><input type="text" class="form-control" name="unit_transform_factor[]" value="' + item.carat_transform_factor + '" ></td>';
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

    function calcTotals(){
        var total_weight = 0;
        var total_weight21 = 0;
        var total = 0;
        var total_cost = 0;
        var net_total = 0;
        $( "#sTable tbody tr").each( function( index ) {
            var row = $(this).closest('tr');
            var unit_id = row[0].cells[0].firstChild.value;
            var line_weight = parseFloat(row[0].cells[2].firstChild.value) || 0;
            var line_weight2_factor = parseFloat(row[0].cells[6].firstChild.value) || 0;

            var line_weight21 = line_weight * line_weight2_factor;
            var line_cost = parseFloat(row[0].cells[4].firstChild.value) || 0;
            var line_total = parseFloat(line_cost) || 0;
            row[0].cells[5].firstChild.value = line_total.toFixed(2);
            total_weight += line_weight;
            total_weight21 += line_weight21;
            total += line_total;
            total_cost += line_cost;
            net_total += line_total;
            sItems[unit_id].weight = line_weight;
            sItems[unit_id].item_total_cost = line_total;
        });
        $("#total").val(total.toFixed(2));
        $("#total_cost").val(total_cost.toFixed(2));
        $("#net_total").val(net_total.toFixed(2));
        $("#total_actual_weight").val(total_weight.toFixed(2));
        $("#total_weight21").val(total_weight21.toFixed(2));
        $("#net_total").val(net_total.toFixed(2));
    }
</script>
@endsection
