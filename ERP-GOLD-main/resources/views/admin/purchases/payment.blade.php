<div class="modal fade" id="paymentsModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel" aria-hidden="true" style="width: 100%;">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
                <label>تفاصيل سداد المشتريات</label>
            </div>
            <div class="modal-body" id="smallBody">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('main.net_after_discount') }}</label>
                            <input required type="text" id="purchase_money" class="form-control text-center" readonly value="{{ $money }}"/>
                            <input type="hidden" name="selected_branch_id" value="{{ $branchId }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>{{ __('main.cash') }}</label>
                            <input type="number" id="purchase_cash" name="cash" min="0" step="any" class="form-control" placeholder="0" value="{{ number_format((float) $money, 2, '.', '') }}"/>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>المتبقي حتى يطابق الإجمالي</label>
                            <input type="text" id="purchase_payment_remaining" class="form-control text-center" readonly value="0.00">
                        </div>
                    </div>
                </div>

                @if ($bankAccounts->isEmpty())
                    <div class="alert alert-warning">
                        لا توجد حسابات بنكية نشطة على هذا الفرع حاليًا. يمكنك حفظ العملية نقديًا فقط، أو تعريف حساب بنكي من إعدادات النظام أولًا.
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">أسطر السداد غير النقدي</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add_purchase_payment_line_btn" {{ $bankAccounts->isEmpty() ? 'disabled' : '' }}>
                        إضافة سطر بنكي
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered text-center mb-3" id="purchase_payment_lines_table">
                        <thead class="thead-light">
                            <tr>
                                <th style="min-width: 120px;">الطريقة</th>
                                <th style="min-width: 220px;">الحساب البنكي</th>
                                <th style="min-width: 160px;">المرجع</th>
                                <th style="min-width: 130px;">المبلغ</th>
                                <th style="width: 80px;">حذف</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <div class="text-muted mb-3">
                    إجمالي غير نقدي: <strong id="purchase_payment_lines_total">0.00</strong>
                </div>

                <div class="row">
                    <div class="col-12" style="display: block; margin: 20px auto; text-align: center;">
                        <button type="button" class="btn btn-labeled btn-primary" id="purchase_payment_btn">
                            {{ __('main.pay_print') }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@php
    $purchaseBankAccounts = $bankAccounts->map(function ($bankAccount) {
        return [
            'id' => $bankAccount->id,
            'name' => $bankAccount->display_name,
            'supports_credit_card' => (bool) $bankAccount->supports_credit_card,
            'supports_bank_transfer' => (bool) $bankAccount->supports_bank_transfer,
            'is_default' => (bool) $bankAccount->is_default,
        ];
    })->values();
@endphp
<script>
    window.currentPurchaseBankAccounts = @json($purchaseBankAccounts);
</script>
