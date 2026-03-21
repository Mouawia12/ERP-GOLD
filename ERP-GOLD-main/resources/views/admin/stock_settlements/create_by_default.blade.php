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
    </style>

    <div class="row row-sm">
        <div class="col-xl-12"> 
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <form method="POST" action="{{ route('stock_settlements.store_by_default') }}"
                                  enctype="multipart/form-data" id="stock_settlements_form">
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
                                                    {{__('main.stock_settlements_add')}}
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
                                    <div class="col-3">
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
                                </div>
                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="card mb-4">
                                                           <div class="card-header pb-0">
                                                                <h4   class="alert alert-info text-center">
                                                                    <i class="fa fa-shopping-cart" aria-hidden="true"></i> 
                                                                    {{__('main.default_carat')}} 
                                                                </h4>
                                                            </div>
                                                            <div class="card-body px-0 pt-0 pb-2">
                                                                <div class="table-responsive hoverable-table">
                                                                    <table class="display w-100 table-bordered" id="sTable" 
                                                                           style="text-align: center;">
                                                                        <thead>
                                                                            <tr>
                                                                                <th class="col-md-1" >{{__('main.item_carats')}}</th>
                                                                                <th>{{__('main.current_weight')}}</th>
                                                                                <th>{{__('main.entry_weight')}}</th>
                                                                                <th>{{__('main.diff_weight')}}</th>
                                                                            </tr>
                                                                        </thead>
                                                                        <tbody id="tbody">
                                                                        </tbody>
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
                                                        style="text-align: right;float: right;"> {{__('main.total_actual_balance')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_actual_balance">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_weight')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_weight" name="total_weight">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_diff_weight')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_diff_weight" name="total_diff_weight">
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
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
    var defaultRow = @json($row);
    var suggestionItems = {};
    var selectedItems = [];
    selectedItems.push(defaultRow);
    loadItems();
    document.title = "{{__('main.stock_settlements_add')}}";

    $(document).ready(function () { 
        
        $('#add_item').focus();

        $(document).on('change', '#branch_id', function () {
            $('#products_suggestions').empty();
            $('#sTable tbody').empty();
            suggestionItems = {};
            count = 1; 
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
                    var message = "";
                    message += result.message;
                    alert(message);
                  setTimeout(function() {
                    suggestionItems = {};
                    selectedItems = [];
                    thisme[0].reset();
                    window.location.href = "{{route('stock_settlements.index')}}";
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

        $(document).on('change', '#carat_type', function () {
            var carat_type = $(this).val();
            let href = "{{route('stock_settlements.get_carat_type_stock')}}";
            let method = 'GET';
            $.ajax({
                url: href,
                type: method,
                data: {
                    carat_type: carat_type
                },
                beforeSend: function() {
                    $('#loader').show();
                },
                success: function(result) {
                    selectedItems = [];
                    selectedItems.push(result.row);
                    loadItems();
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
   
        var now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        now.setMilliseconds(null);
        now.setSeconds(null);

        document.getElementById('date').value = now.toISOString().slice(0, -1);
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
        var No = 0;
        $.each(selectedItems, function (i, item) {
            No +=1;
            var newTr = $('<tr data-item-id="' + item.carat_id + '">'); 
            var tr_html= '<td class="text-center"><input type="hidden" name="carat_id" value="' + item.carat_id + '"> <strong>' + item.title + '</strong>' +'</td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control" name="actual_balance" value="' + item.actual_balance.toFixed(2) + '" ></td>';
            tr_html += '<td><input type="text" class="form-control current_weight" name="weight" value="' + item.weight + '" ></td>';
            tr_html += '<td><input type="text" readonly="readonly" class="form-control diff_weight" name="diff_weight" value="' + item.diff_weight.toFixed(2) + '" ></td>';

            newTr.html(tr_html);
            newTr.appendTo('#sTable');
        });
        calcTotals();
        $('#products_suggestions').empty();
    }
 
    function calcTotals(){
        var total_actual_balance = 0;
        var total_weight = 0;
        var total_diff_weight = 0;
        $( "#sTable tbody tr").each( function( index ) {
            var row = $(this).closest('tr');
            var line_actual_balance = parseFloat(row[0].cells[1].firstChild.value) || 0;
            var line_current_weight = parseFloat(row[0].cells[2].firstChild.value) || 0;
            row[0].cells[3].firstChild.value = line_current_weight - line_actual_balance;
            total_actual_balance += line_actual_balance;
            total_weight += line_current_weight;
            total_diff_weight += line_current_weight - line_actual_balance;
        });

        $("#total_actual_balance").val(total_actual_balance.toFixed(2));
        $("#total_weight").val(total_weight.toFixed(2));
        $("#total_diff_weight").val(total_diff_weight.toFixed(2));
        var total_items = selectedItems.length;
        $("#total_items").val(total_items.toFixed(2));
    }
</script> 
@endsection 
 