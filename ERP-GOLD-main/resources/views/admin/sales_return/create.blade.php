@extends('admin.layouts.master')
@section('content')
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
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                        [ {{__('main.return_sales')}}  {{__('main.sales_'.$type)}} ]
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div> 
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <form method="POST" action="{{ route('sales_return.store',['type'=>$type,'id'=>$invoice->id]) }}"
                                  enctype="multipart/form-data" id="pos_sales_form">
                                @csrf
                                <div class="row">
                                    <div class="card shadow mb-4 col-9"> 
                                        <div class="card-body">
                                            <div class="row"> 
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label style="float: right;">{{ __('main.bill_date') }} <span
                                                                style="color:red; font-size:20px; font-weight:bold;">*</span>
                                                        </label>
                                                        <input type="text"
                                                               class="form-control" value="{{$invoice -> date}}" readonly
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label style="float: right;">{{ __('main.bill_number') }} <span
                                                                style="color:red; font-size:20px; font-weight:bold;">*</span>
                                                        </label>
                                                        <input type="text" value="{{$invoice -> bill_number}}"
                                                               class="form-control" placeholder="bill_number" readonly
                                                        />
                                                        <input type="hidden" value="{{$invoice -> bill_number}}" id="bill_id" name="bill_id"
                                                               class="form-control" placeholder="bill_id" readonly
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label style="float: right;">{{ __('main.bill_client_name') }}
                                                            <span
                                                                style="color:red; font-size:20px; font-weight:bold;">*</span>
                                                        </label>
                                                        <input type="text" name="bill_client_name" id="bill_client_name"
                                                               class="form-control"
                                                               value="{{$invoice -> customer->name}}" readonly>


                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">


                                                    <div class="card mb-4">
                                                        <div class="card-header pb-0">
                                                            <h4 class="table-label text-center">{{__('main.items')}} </h4>

                                                            <div class="row">

                                                            </div>

                                                        </div>

                                                        <div class="card-body px-0 pt-0 pb-2">
                                                            <div class="table-responsive p-0">
                                                                <table id="sTable" class="table items table-striped table-bordered table-condensed table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>{{__('main.item_name')}}</th>
                                                                            <th>{{__('main.item_carats')}}</th>
                                                                            <th>{{__('main.item_weight')}}</th>
                                                                            <th>{{__('main.price_gram')}} </th>
                                                                            <th>{{__('main.item_amount')}}</th>
                                                                            <th>{{__('main.item_tax')}}</th>
                                                                            <th>{{__('main.item_total')}}</th>
                                                                            <th class="text-center">
                                                                                <input class="form-control" id="checkAll"
                                                                                        name="checkAll" type="checkbox">
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="tbody">
                                                                    @foreach($invoice -> details()->whereNotIn('id', $invoice->returnInvoicesDetailsIds)->get()??[] as $detail)
                                                                        <tr>
                                                                            <td class="text-center"> {{$detail -> item->title}} </td>
                                                                            <td class="text-center"> {{$detail -> carat->title}} </td>
                                                                            <td class="text-center"> {{$detail -> out_weight}} </td>
                                                                            <td class="text-center"> {{$detail -> unit_price}} </td>
                                                                            <td class="text-center"> {{$detail -> line_total}} </td>
                                                                            <td class="text-center"> {{$detail -> line_tax}} </td>
                                                                            <td class="text-center"> {{$detail -> net_total}} </td>
                                                                            <td class="text-center"><input
                                                                                    class="form-control checkDetail"
                                                                                    name="checkDetail[]" type="checkbox"
                                                                                    value="{{$detail -> id}}"
                                                                                    data-net-total="{{ round((float) $detail->net_total, 2) }}"></td>
                                                                        </tr>
                                                                    @endforeach
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
                                            <h6 class="m-0 font-weight-bold text-primary">{{__('main.sales_invoice_total')}}</h6>
                                        </div>
                                        <div class="card-body ">
                                            <div class="row document_type1"
                                                 style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.items_count')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="items_count"
                                                           value="{{count($invoice -> details) }}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;">العناصر المحددة</label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="selected_items_count"
                                                           value="0">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_weight21')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_weight21" name="total_weight21"
                                                           value="{{$invoice -> stock_carat_weight ?? 0}}">
                                                </div>
                                            </div>


                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.additional_tax')  }} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="tax" name="tax"
                                                           value="{{$invoice -> taxes_total ?? 0}}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-6">

                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.discount')}} </label>


                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="discount"
                                                           name="discount" placeholder="0"
                                                           value="{{$invoice -> discount_total ?? 0}}">
                                                </div>

                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"
                                                    > {{__('main.net')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="net_sales"
                                                           value="{{$invoice -> net_total}}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;">صافي المرتجع المحدد</label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="selected_return_net_total"
                                                           value="0.00">
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">

                                            <div class="alert alert-info py-2 px-3" style="font-size: 13px;">
                                                أدخل طريقة رد المبلغ بحيث يساوي إجماليها صافي المرتجع المحدد فقط.
                                            </div>

                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;">رد نقدي</label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="number" min="0" step="any" class="form-control" id="cash"
                                                           name="cash" value="0">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;">المتبقي</label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="payment_remaining"
                                                           value="0.00">
                                                </div>
                                            </div>

                                            @if ($bankAccounts->isEmpty())
                                                <div class="alert alert-warning py-2 px-3">
                                                    لا توجد حسابات بنكية نشطة على هذا الفرع. يمكن رد المرتجع نقديًا فقط حاليًا.
                                                </div>
                                            @endif

                                            <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                                                <h6 class="mb-0">أسطر الرد غير النقدي</h6>
                                                <button type="button" class="btn btn-outline-primary btn-sm" id="add_refund_line_btn" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                                                    إضافة
                                                </button>
                                            </div>

                                            <div class="table-responsive mb-2">
                                                <table class="table table-bordered text-center mb-0" id="refund_payment_lines_table">
                                                    <thead class="thead-light">
                                                        <tr>
                                                            <th>الطريقة</th>
                                                            <th>الحساب البنكي</th>
                                                            <th>المرجع</th>
                                                            <th>المبلغ</th>
                                                            <th>حذف</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody></tbody>
                                                </table>
                                            </div>
                                            <div class="text-muted mb-3">
                                                إجمالي غير نقدي: <strong id="refund_payment_lines_total">0.00</strong>
                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 text-center" style="display: block; margin: auto;">
                                                    <input type="button" class="btn btn-primary" id="return_btn"
                                                           tabindex="-1"
                                                           style="width: 150px;
                                                   margin: 30px auto;" value="{{__('main.return_bill')}}"></input>

                                                </div>
                                            </div>

                                        </div>


                                    </div>

                                </div>


                            </form>
                        </div>


                    </div>
                </div>


            </div>
            <!-- /.container-fluid -->
            <input id="local" value="{{Config::get('app.locale')}}" hidden>
        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
    

    </div>
    <!-- End of Content Wrapper -->


</div>
<!-- End of Page Wrapper -->
  
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script>

<script type="text/javascript">
    $(document).ready(function () {
        window.currentSalesReturnBankAccounts = @json($bankAccounts->map(function ($bankAccount) {
            return [
                'id' => $bankAccount->id,
                'name' => $bankAccount->display_name,
                'supports_credit_card' => (bool) $bankAccount->supports_credit_card,
                'supports_bank_transfer' => (bool) $bankAccount->supports_bank_transfer,
            ];
        })->values());

        var cashWasEditedManually = false;

        $('#checkAll').change(function () {
            $("input:checkbox.checkDetail").prop('checked', this.checked);
            syncSelectedRefundTotals();
        });

        $(document).on('change', '.checkDetail', function () {
            syncSelectedRefundTotals();
        });

        $(document).on('input', '#cash', function () {
            cashWasEditedManually = true;
            refreshRefundPaymentSummary();
        });

        $(document).on('click', '#add_refund_line_btn', function () {
            appendRefundPaymentLineRow();
        });

        $(document).on('change', '.refund-method-type', function () {
            syncRefundBankAccountOptions($(this).closest('tr'));
        });

        $(document).on('input', '.refund-line-amount, .refund-line-reference', function () {
            refreshRefundPaymentSummary();
        });

        $(document).on('click', '.remove-refund-line-btn', function () {
            $(this).closest('tr').remove();
            refreshRefundPaymentInputNames();
            refreshRefundPaymentSummary();
        });

        $(document).on('click', '#return_btn', function () {
            var selectedRefundTotal = selectedRefundNetTotal();
            var paymentTotal = totalRefundPayments();

            if (selectedRefundTotal <= 0) {
                alert('يجب اختيار صنف واحد على الأقل قبل حفظ المرتجع');
                return;
            }

            if (Math.abs(paymentTotal - selectedRefundTotal) > 0.01) {
                alert('إجمالي رد المبلغ يجب أن يساوي صافي المرتجع المحدد بالكامل');
                return;
            }

            document.getElementById('pos_sales_form').submit();
        });

        syncSelectedRefundTotals();
        refreshRefundPaymentSummary();
    });

    function selectedRefundNetTotal() {
        var total = 0;
        $('.checkDetail:checked').each(function () {
            total += parseFloat($(this).data('net-total') || 0);
        });

        return roundRefundValue(total);
    }

    function selectedRefundItemsCount() {
        return $('.checkDetail:checked').length;
    }

    function refundLinesTotal() {
        var total = 0;

        $('.refund-line-amount').each(function () {
            total += parseFloat($(this).val() || 0);
        });

        return roundRefundValue(total);
    }

    function totalRefundPayments() {
        return roundRefundValue(parseFloat($('#cash').val() || 0) + refundLinesTotal());
    }

    function roundRefundValue(value) {
        return Math.round((parseFloat(value || 0) + Number.EPSILON) * 100) / 100;
    }

    function formatRefundValue(value) {
        return roundRefundValue(value).toFixed(2);
    }

    function syncSelectedRefundTotals() {
        var selectedTotal = selectedRefundNetTotal();
        var selectedItems = selectedRefundItemsCount();

        $('#selected_items_count').val(selectedItems);
        $('#selected_return_net_total').val(formatRefundValue(selectedTotal));

        if (!cashWasEditedManually && $('#refund_payment_lines_table tbody tr').length === 0) {
            $('#cash').val(formatRefundValue(selectedTotal));
        }

        refreshRefundPaymentSummary();
    }

    function buildRefundBankAccountOptions(methodType, selectedBankAccountId) {
        var options = '<option value="">اختر الحساب البنكي</option>';
        var filteredAccounts = (window.currentSalesReturnBankAccounts || []).filter(function (bankAccount) {
            if (methodType === 'credit_card') {
                return bankAccount.supports_credit_card;
            }

            return bankAccount.supports_bank_transfer;
        });

        filteredAccounts.forEach(function (bankAccount) {
            var isSelected = String(selectedBankAccountId || '') === String(bankAccount.id) ? 'selected' : '';
            options += '<option value="' + bankAccount.id + '" ' + isSelected + '>' + bankAccount.name + '</option>';
        });

        return options;
    }

    function syncRefundBankAccountOptions(row) {
        var methodType = row.find('.refund-method-type').val() || 'credit_card';
        var bankAccountSelect = row.find('.refund-bank-account');
        var currentValue = bankAccountSelect.val();

        bankAccountSelect.html(buildRefundBankAccountOptions(methodType, currentValue));

        if (!bankAccountSelect.val()) {
            bankAccountSelect.prop('selectedIndex', bankAccountSelect.find('option').length > 1 ? 1 : 0);
        }
    }

    function refreshRefundPaymentInputNames() {
        $('#refund_payment_lines_table tbody tr').each(function (index) {
            $(this).find('.refund-method-type').attr('name', 'payment_lines[' + index + '][method_type]');
            $(this).find('.refund-bank-account').attr('name', 'payment_lines[' + index + '][bank_account_id]');
            $(this).find('.refund-line-reference').attr('name', 'payment_lines[' + index + '][reference_no]');
            $(this).find('.refund-line-amount').attr('name', 'payment_lines[' + index + '][amount]');
        });
    }

    function appendRefundPaymentLineRow() {
        var row = $('<tr>' +
            '<td><select class="form-control refund-method-type">' +
                '<option value="credit_card">شبكة / بطاقة</option>' +
                '<option value="bank_transfer">تحويل بنكي</option>' +
            '</select></td>' +
            '<td><select class="form-control refund-bank-account"></select></td>' +
            '<td><input type="text" class="form-control refund-line-reference" placeholder="مرجع العملية"></td>' +
            '<td><input type="number" min="0" step="any" class="form-control refund-line-amount" value="0"></td>' +
            '<td><button type="button" class="btn btn-danger btn-sm remove-refund-line-btn">حذف</button></td>' +
        '</tr>');

        $('#refund_payment_lines_table tbody').append(row);
        syncRefundBankAccountOptions(row);
        refreshRefundPaymentInputNames();
        refreshRefundPaymentSummary();
    }

    function refreshRefundPaymentSummary() {
        var selectedRefundTotal = selectedRefundNetTotal();
        var nonCashTotal = refundLinesTotal();
        var totalPayments = totalRefundPayments();
        var remaining = roundRefundValue(selectedRefundTotal - totalPayments);

        $('#refund_payment_lines_total').text(formatRefundValue(nonCashTotal));
        $('#payment_remaining').val(formatRefundValue(remaining));
    }

</script>
 
