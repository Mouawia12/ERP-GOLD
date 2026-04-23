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

                                    {{-- Panel الجانبي للملخص --}}
                                    <div class="card shadow mb-4 col-3">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">{{__('main.sales_invoice_total')}}</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"> {{__('main.items_count')}} </label>
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
                                                    <input type="text" readonly class="form-control" id="selected_items_count" value="0">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"> {{__('main.total_weight21')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_weight21" name="total_weight21"
                                                           value="{{$invoice -> stock_carat_weight ?? 0}}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"> {{__('main.additional_tax')  }} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="tax" name="tax"
                                                           value="{{$invoice -> taxes_total ?? 0}}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"> {{__('main.discount')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="discount"
                                                           name="discount" placeholder="0"
                                                           value="{{$invoice -> discount_total ?? 0}}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"> {{__('main.net')}} </label>
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
                                                    <input type="text" readonly class="form-control" id="selected_return_net_total" value="0.00">
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">
                                            <div class="row">
                                                <div class="col-md-12 text-center">
                                                    <button type="button" class="btn btn-primary btn-block" id="open_payment_modal_btn" style="margin: 10px auto;">
                                                        <i class="fa fa-money"></i> إتمام المرتجع
                                                    </button>
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
            <input id="local" value="{{Config::get('app.locale')}}" hidden>
        </div>
    </div>

    {{-- Modal الدفع --}}
    <div class="modal fade" id="refundPaymentModal" tabindex="-1" role="dialog" aria-labelledby="refundPaymentModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="refundPaymentModalLabel">
                        <i class="fa fa-money"></i> طريقة رد المبلغ
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-3 mb-3">
                        صافي المرتجع: <strong id="modal_return_total">0.00</strong> ريال — أدخل طريقة الرد بحيث يساوي الإجمالي صافي المرتجع.
                    </div>

                    {{-- كاش --}}
                    <div class="form-group row align-items-center">
                        <label class="col-4 col-form-label text-right font-weight-bold">رد نقدي (كاش)</label>
                        <div class="col-8">
                            <input type="number" min="0" step="any" class="form-control" id="modal_cash" name="cash" value="0">
                        </div>
                    </div>

                    {{-- متبقي --}}
                    <div class="form-group row align-items-center">
                        <label class="col-4 col-form-label text-right">المتبقي</label>
                        <div class="col-8">
                            <input type="text" readonly class="form-control bg-light" id="modal_payment_remaining" value="0.00">
                        </div>
                    </div>

                    @if ($bankAccounts->isEmpty())
                        <div class="alert alert-warning py-2 px-3">
                            لا توجد حسابات بنكية نشطة على هذا الفرع. يمكن رد المرتجع نقديًا فقط.
                        </div>
                    @endif

                    {{-- شبكة وتحويل --}}
                    <div class="d-flex justify-content-between align-items-center mb-2 mt-3">
                        <h6 class="mb-0 font-weight-bold">رد شبكة / تحويل بنكي</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="add_refund_line_btn" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                            <i class="fa fa-plus"></i> إضافة سطر
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
                    <div class="text-muted mb-2">
                        إجمالي غير نقدي: <strong id="refund_payment_lines_total">0.00</strong> ريال
                    </div>

                    {{-- شريط التقدم --}}
                    <div class="progress mt-2" style="height: 8px;">
                        <div class="progress-bar bg-success" role="progressbar" id="refund_progress_bar" style="width: 0%"></div>
                    </div>
                    <div class="text-center mt-1" id="refund_progress_label" style="font-size:12px;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="button" class="btn btn-success" id="confirm_return_btn">
                        <i class="fa fa-check"></i> تأكيد المرتجع
                    </button>
                </div>
            </div>
        </div>
    </div>

@php
    $salesReturnBankAccountsPayload = $bankAccounts->map(function ($bankAccount) {
        return [
            'id' => $bankAccount->id,
            'name' => $bankAccount->display_name,
            'supports_credit_card' => (bool) $bankAccount->supports_credit_card,
            'supports_bank_transfer' => (bool) $bankAccount->supports_bank_transfer,
        ];
    })->values();
@endphp
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script>

<script type="text/javascript">
    $(document).ready(function () {
        window.currentSalesReturnBankAccounts = {{ Illuminate\Support\Js::from($salesReturnBankAccountsPayload) }};

        // ---- اختيار الأصناف ----
        $('#checkAll').change(function () {
            $("input:checkbox.checkDetail").prop('checked', this.checked);
            syncSelectedRefundTotals();
        });

        $(document).on('change', '.checkDetail', function () {
            syncSelectedRefundTotals();
        });

        // ---- فتح Modal ----
        $(document).on('click', '#open_payment_modal_btn', function () {
            var selectedTotal = selectedRefundNetTotal();
            if (selectedTotal <= 0) {
                alert('يجب اختيار صنف واحد على الأقل قبل إتمام المرتجع');
                return;
            }
            // ضبط القيم في الـ modal
            $('#modal_return_total').text(formatRefundValue(selectedTotal));
            $('#modal_cash').val(formatRefundValue(selectedTotal));
            refreshModalSummary();
            $('#refundPaymentModal').modal('show');
        });

        // ---- تغيير الكاش في الـ modal ----
        $(document).on('input', '#modal_cash', function () {
            refreshModalSummary();
        });

        // ---- إضافة سطر شبكة/تحويل ----
        $(document).on('click', '#add_refund_line_btn', function () {
            appendRefundPaymentLineRow();
        });

        $(document).on('change', '.refund-method-type', function () {
            syncRefundBankAccountOptions($(this).closest('tr'));
        });

        $(document).on('input', '.refund-line-amount, .refund-line-reference', function () {
            refreshModalSummary();
        });

        $(document).on('click', '.remove-refund-line-btn', function () {
            $(this).closest('tr').remove();
            refreshRefundPaymentInputNames();
            refreshModalSummary();
        });

        // ---- تأكيد المرتجع ----
        $(document).on('click', '#confirm_return_btn', function () {
            var selectedRefundTotal = selectedRefundNetTotal();
            var paymentTotal = totalRefundPayments();

            if (Math.abs(paymentTotal - selectedRefundTotal) > 0.01) {
                alert('إجمالي رد المبلغ (' + formatRefundValue(paymentTotal) + ') يجب أن يساوي صافي المرتجع (' + formatRefundValue(selectedRefundTotal) + ')');
                return;
            }

            // نقل قيمة الكاش من الـ modal إلى حقل مخفي في الـ form
            var cashInput = document.getElementById('modal_cash');
            var existingCash = document.getElementById('hidden_cash');
            if (!existingCash) {
                existingCash = document.createElement('input');
                existingCash.type = 'hidden';
                existingCash.id = 'hidden_cash';
                existingCash.name = 'cash';
                document.getElementById('pos_sales_form').appendChild(existingCash);
            }
            existingCash.value = cashInput.value;

            document.getElementById('pos_sales_form').submit();
        });

        syncSelectedRefundTotals();
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
        return roundRefundValue(parseFloat($('#modal_cash').val() || 0) + refundLinesTotal());
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
    }

    function refreshModalSummary() {
        var selectedRefundTotal = selectedRefundNetTotal();
        var nonCashTotal = refundLinesTotal();
        var totalPayments = totalRefundPayments();
        var remaining = roundRefundValue(selectedRefundTotal - totalPayments);

        $('#refund_payment_lines_total').text(formatRefundValue(nonCashTotal));
        $('#modal_payment_remaining').val(formatRefundValue(remaining));

        // شريط التقدم
        var pct = selectedRefundTotal > 0 ? Math.min(100, (totalPayments / selectedRefundTotal) * 100) : 0;
        $('#refund_progress_bar').css('width', pct + '%');
        if (Math.abs(remaining) <= 0.01) {
            $('#refund_progress_bar').removeClass('bg-warning bg-danger').addClass('bg-success');
            $('#refund_progress_label').text('✓ المبالغ متطابقة').css('color', 'green');
        } else if (remaining < 0) {
            $('#refund_progress_bar').removeClass('bg-success bg-warning').addClass('bg-danger');
            $('#refund_progress_label').text('تجاوز المبلغ بـ ' + formatRefundValue(Math.abs(remaining)) + ' ريال').css('color', 'red');
        } else {
            $('#refund_progress_bar').removeClass('bg-success bg-danger').addClass('bg-warning');
            $('#refund_progress_label').text('متبقي ' + formatRefundValue(remaining) + ' ريال').css('color', '#856404');
        }
    }

    function buildRefundBankAccountOptions(methodType, selectedBankAccountId) {
        var options = '<option value="">اختر الحساب البنكي</option>';
        var filteredAccounts = (window.currentSalesReturnBankAccounts || []).filter(function (bankAccount) {
            if (methodType === 'credit_card') return bankAccount.supports_credit_card;
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
        refreshModalSummary();
    }
</script>
