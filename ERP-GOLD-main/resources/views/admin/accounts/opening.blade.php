@extends('admin.layouts.master')
@section('content')
@can('employee.accounts.add')   
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<!-- row opened -->
<div class="row row-sm">
    <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0 text-center">
                    <div class="col-lg-12 margin-tb ">
                        <h4  class="alert alert-primary text-center"> 
                          [ {{__('اضافة رصيد افتتاحي')}} ]
                        </h4>
                    </div> 
                </div>   
                <div class="card-body px-0 pt-0 pb-2">
                   <div class="card shadow mb-4">  
                   <div class="card-body">
                    <form   method="POST" action="{{ route('accounts.opening.store') }}"
                            enctype="multipart/form-data" id="journalForm">
                        @csrf
                        <div class="row"> 
                                <div class="col-md-12" id="sticker">
                                    <div class="well well-sm" @if(Config::get('app.locale') == 'ar')style="direction: rtl;" @endif>
                                        <div class="form-group" style="margin-bottom:0;">
                                            <div class="input-group wide-tip">
                                                <div class="input-group-addon">
                                                    <i class="fa fa-3x fa-barcode addIcon"></i>
                                                </div>
                                                <input style="border-radius: 0 !important;padding-left: 10px;padding-right: 10px;"
                                                       type="text" name="add_item" value="" class="form-control input-lg ui-autocomplete-input" id="add_item" placeholder="{{__('main.search_journal')}}" autocomplete="off">
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
                                        <h4  class="alert alert-info text-center">{{__('الحسابات')}} </h4>
                                    </div>   
                                    <div class="card-body px-0 pt-0 pb-2">
                                        <div class="table-responsive p-0"> 
                                            <table id="sTable" style="width:100%" class="display w-100 table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th class="text-center">{{__('م')}}</th>
                                                        <th class="text-center">{{__('main.account_name')}}</th>
                                                        <th class="text-center">{{__('main.Debit')}} </th>
                                                        <th class="text-center">{{__('main.Credit')}}</th> 
                                                        <th class="text-center">{{__('main.actions')}}</th> 
                                                      
                                                        <th class="text-center">
                                                            <i class="fa fa-trash-o" style="opacity:0.5; filter:alpha(opacity=50);"></i>
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tbody"></tbody>
                                                <tfoot class="text-white bg-secondary">
                                                    <tr>
                                                        <th class="text-center" colspan="2">اجمالي</th>
                                                        <th><input id="total_debit" name="total_debit" type="text" class="form-control text-center" readonly></th> 
                                                        <th><input id="total_credit" name="total_credit" type="text" class="form-control text-center" readonly></th>
                                                        <th class="text-center"></th>  
                                                    </tr>
                                                </tfoot>
                                            </table> 
                                        </div>
                                    </div> 
                                </div>
                            </div>
                        </div> 

                        <div class="row">
                            <div class="col-md-12 text-center"> 
                                <button type="submit" class="btn btn-md btn-info w-25" 
                                    id="primary" 
                                    value="{{__('main.save_btn')}}">
                                    اضافة وحفظ 
                                </button>  
                            </div>
                        </div> 
                    </form> 
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

@endcan 
@endsection 
@section('js') 
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript"> 
 

    var suggestionItems = {};
    var sItems = @json($openingBalances);
    loadItems();
    var count = 1; 

    $(document).ready(function() {
        $('#journalForm').submit(function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            var url = $(this).attr('action');
            $.ajax({
                type: 'POST',
                url: url,
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    Swal.fire({
                        title: "{{__('main.success')}}",
                        text: response.message,
                        icon: 'success',
                        confirmButtonText: 'موافق'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = "{{route('accounts.journals.index', 'manual')}}";
                        }
                    });
                },
                error: function(response) {
                    Swal.fire({
                        title: "{{__('main.error')}}",
                        text: response.responseJSON.errors,
                        icon: 'error',
                        confirmButtonText: 'موافق'
                    });
                }
            });
        });
    
        $('input[name=add_item]').change(function() {
            console.log($('#add_item').val());
        });
        $('#add_item').on('input',function(e){
            searchProduct($('#add_item').val());
        });

        $(document).on('click' , '.cancel-modal' , function (event) {
            $('#deleteModal').modal("hide");
            id = 0 ;
        });

        $(document).on('click' , '.deleteBtn' , function (event) {
            var row = $(this).parent().parent().index();

            var row1 = $(this).closest('tr');
            var item_id = row1.attr('data-item-id');
            sItems = sItems.filter(item => item.id != item_id);
            loadItems();
        });

        $(document).on('click', '.select_product', function () {
            var row = $(this).closest('li');
            var item_id = row.attr('data-item-id');
            addItemToTable(suggestionItems[item_id]);
            document.getElementById('products_suggestions').innerHTML = '';
            suggestionItems = {};
        });
 
        $(document).on('click', '.close-create', function (event) {
            $('#paymentsModal').modal("hide");
            
        });
    });

    function searchProduct(code){
        var url = "{{route('accounts.search')}}";
        $.ajax({
            type:'post',
            url:url,
            dataType: 'json',
            data: {
                search: code
            },
            success:function(response){
                document.getElementById('products_suggestions').innerHTML = '';
                if(response){
                    if(response.length == 1){
                        //addItemToTable
                        addItemToTable(response[0]);

                    }else if(response.length > 1){ 
                        showSuggestions(response);
                    } else if(response.id){
                        showSuggestions(response);
                    } else {
                        //showNotFoundAlert
                        openDialog();
                        document.getElementById('add_item').value = '' ;
                    }
                } else {
                    //showNotFoundAlert
                    openDialog();
                    document.getElementById('add_item').value = '' ;
                }
            }
        });
    }

    function showSuggestions(response) {

        $data = '';
        $.each(response,function (i,item) {
            suggestionItems[item.id] = item;
            $data +='<li class="select_product" data-item-id="'+item.id+'">'+item.name+'</li>';
        });
        document.getElementById('products_suggestions').innerHTML = $data;
    }

    function openDialog(){
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
    }
    function addItemToTable(item){
        item.credit = item.opening_balance_object.credit;
        item.debit = item.opening_balance_object.debit;
        sItems.push(item);
        loadItems();
        document.getElementById('add_item').value = '' ;
    }

    var old_row_qty=0;
    var old_row_price = 0;
    var old_row_w_price = 0;

    $(document)
        .on('focus','.iCredit',function () {
            old_row_qty = $(this).val();
        })
        .on('change','.iCredit',function () {
            var row = $(this).closest('tr');
            if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
                $(this).val(old_row_qty);
                alert('wrong value');
                return;
            }

            var newQty = parseFloat($(this).val()),
            item_id = row.attr('data-item-id');

            var checkItem = sItems.find(item => item.id == item_id);
            if(checkItem){
                checkItem.credit= newQty;
                checkItem.debit= 0;
            }
            loadItems();

        });

    $(document)
        .on('focus','.iDebit',function () {
            old_row_price = $(this).val();
        })
        .on('change','.iDebit',function () {
            var row = $(this).closest('tr');
            if(!is_numeric($(this).val()) || parseFloat($(this).val()) < 0){
                $(this).val(old_row_price);
                alert('wrong value');
                return;
            }

            var newQty = parseFloat($(this).val()),
                item_id = row.attr('data-item-id');

            var checkItem = sItems.find(item => item.id == item_id);
            if(checkItem){
                checkItem.credit= 0;
                checkItem.debit= newQty;
            }
            loadItems();

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
        let totalCredit = 0, totalDebit = 0;
        let tableBody = '';
    
        $('#sTable tbody').empty(); // Clear table before updating
    
        $.each(sItems, function (_, item) {
    
            totalCredit += item.credit;
            totalDebit += item.debit;
    
            tableBody += `
                <tr data-item-id="${item.id}">
                    <td class="text-center">
                        <input type="hidden" name="account_id[]" value="${item.id}">
                        <span>${item.code}</span>
                    </td>
                    <td class="text-center">${item.name}</td>
                    <td class="text-center">
                        <input type="text" class="form-control text-center iDebit" name="debit[]" value="${item.debit.toFixed(2)}">
                    </td>
                    <td class="text-center">
                        <input type="text" class="form-control text-center iCredit" name="credit[]" value="${item.credit.toFixed(2)}">
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-labeled btn-danger deleteBtn" value="${item.id}">
                            <i class="fa fa-close"></i>
                        </button>
                    </td>
                </tr>
            `;
        });
    
        $('#sTable tbody').append(tableBody); // Append rows in a single operation
        $("#total_debit").val(totalDebit);
        $("#total_credit").val(totalCredit);
    }

 
</script>
@endsection 
 
 





