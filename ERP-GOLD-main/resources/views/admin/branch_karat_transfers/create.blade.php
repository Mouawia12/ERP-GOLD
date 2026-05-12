@extends('admin.layouts.master')
@section('content')
@can('employee.branch_karat_transfers.add')
    <style>
        body { direction: rtl; }
        .bkt-create .card-header h4 { margin: 0; }
        .bkt-create label { font-weight: 600; }
        .bkt-create .lines-table th, .bkt-create .lines-table td { vertical-align: middle; text-align: center; }
        .bkt-create .lines-table input.form-control, .bkt-create .lines-table select.form-control { text-align: center; }
        .bkt-create .lines-table td { padding: 6px; }
        .bkt-create .totals-box { background: #f4f7fb; padding: 12px; border-radius: 6px; }
        .bkt-create .helper-note { font-size: 12px; color: #6c757d; }
    </style>

    <div class="row row-sm bkt-create">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center w-100">
                        تحويل بين الفروع
                    </h4>
                </div>
                <div class="card-body">
                    <div id="bkt-alert"></div>

                    <form id="bkt-form">
                        @csrf
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>رقم المستند</label>
                                    <input type="text" class="form-control" value="{{ $billNumber }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>تاريخ المستند <span class="text-danger">*</span></label>
                                    <input type="date" name="bill_date" class="form-control" value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>المستخدم</label>
                                    <input type="text" class="form-control" value="{{ auth('admin-web')->user()?->name }}" readonly>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>نوع المخزون <span class="text-danger">*</span></label>
                                    <select name="gold_carat_type_id" class="form-control" required>
                                        @foreach($caratTypes as $type)
                                            <option value="{{ $type->id }}" {{ $type->key === 'crafted' ? 'selected' : '' }}>
                                                {{ $type->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="helper-note">مشغول / كسر / سبائك</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>من فرع <span class="text-danger">*</span></label>
                                    <select name="from_branch_id" class="form-control" required>
                                        <option value="">-- اختر الفرع المصدر --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>إلى فرع <span class="text-danger">*</span></label>
                                    <select name="to_branch_id" class="form-control" required>
                                        <option value="">-- اختر الفرع الوجهة --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>حساب التسوية (الوسيط) <span class="text-danger">*</span></label>
                                    <select name="account_id" class="form-control" required>
                                        <option value="">-- اختر الحساب --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">{{ $acc->code }} - {{ $acc->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="helper-note">حساب يستخدم كوسيط بين الفرعين في القيد المحاسبي.</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label>ملاحظات</label>
                                    <input type="text" name="notes" class="form-control" maxlength="1000" placeholder="ملاحظات">
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h5>عيارات التحويل</h5>
                        <p class="text-muted small">
                            عند اختيار العيارات يتم احتساب الوزن الجديد تلقائيًا بناءً على معامل التحويل. سعر الجرام الافتراضي مأخوذ من آخر تحديث لأسعار الذهب، ويمكن تعديله يدويًا.
                        </p>

                        <div class="table-responsive">
                            <table class="table table-bordered lines-table" id="lines-table">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 60px;">#</th>
                                        <th>العيار</th>
                                        <th>الوزن (المصدر)</th>
                                        <th>العيار الجديد</th>
                                        <th>الوزن (الوجهة)</th>
                                        <th>سعر الجرام</th>
                                        <th>القيمة</th>
                                        <th>ملاحظة</th>
                                        <th style="width: 50px;"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>

                        <button type="button" class="btn btn-outline-primary" id="add-line-btn">
                            <i class="fa fa-plus"></i> إضافة سطر
                        </button>

                        <div class="row mt-3">
                            <div class="col-md-6 offset-md-6">
                                <div class="totals-box">
                                    <div class="d-flex justify-content-between">
                                        <span>إجمالي وزن المصدر:</span>
                                        <strong id="total-from">0.000</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span>إجمالي وزن الوجهة:</span>
                                        <strong id="total-to">0.000</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mt-2">
                                        <span>إجمالي القيمة:</span>
                                        <strong id="total-value">0.00</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-save"></i> حفظ التحويل
                            </button>
                            <a href="{{ route('branch_karat_transfers.index') }}" class="btn btn-secondary btn-lg">
                                إلغاء
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script type="text/template" id="line-template">
        <tr class="line-row">
            <td class="row-index"></td>
            <td>
                <select name="from_carat_id" class="form-control carat-select from-carat" required>
                    <option value="">--</option>
                    @foreach($carats as $carat)
                        <option value="{{ $carat->id }}" data-factor="{{ $carat->transform_factor }}">
                            {{ $carat->getTranslation('title', 'ar') ?? $carat->title }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="from_weight" step="0.001" min="0.001" class="form-control from-weight" required>
            </td>
            <td>
                <select name="to_carat_id" class="form-control carat-select to-carat" required>
                    <option value="">--</option>
                    @foreach($carats as $carat)
                        <option value="{{ $carat->id }}" data-factor="{{ $carat->transform_factor }}">
                            {{ $carat->getTranslation('title', 'ar') ?? $carat->title }}
                        </option>
                    @endforeach
                </select>
            </td>
            <td>
                <input type="number" name="to_weight" step="0.001" min="0.001" class="form-control to-weight" required>
            </td>
            <td>
                <input type="number" name="unit_cost" step="0.0001" min="0" class="form-control unit-cost" value="{{ $defaultPrice }}" required>
            </td>
            <td>
                <input type="text" class="form-control line-value-display" readonly value="0.00">
            </td>
            <td>
                <input type="text" name="line_notes" class="form-control line-notes" maxlength="255">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-line-btn">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        </tr>
    </script>
@endcan
@endsection

@section('js')
<script>
$(function () {
    var tbody = $('#lines-table tbody');
    var template = document.getElementById('line-template').innerHTML;

    function reindex() {
        tbody.find('tr.line-row').each(function (i) {
            $(this).find('.row-index').text(i + 1);
        });
    }

    function recalcTotals() {
        var totalFrom = 0;
        var totalTo = 0;
        var totalValue = 0;
        tbody.find('tr.line-row').each(function () {
            totalFrom += parseFloat($(this).find('.from-weight').val()) || 0;
            totalTo += parseFloat($(this).find('.to-weight').val()) || 0;
            var fromW = parseFloat($(this).find('.from-weight').val()) || 0;
            var cost = parseFloat($(this).find('.unit-cost').val()) || 0;
            var lineValue = fromW * cost;
            $(this).find('.line-value-display').val(lineValue.toFixed(2));
            totalValue += lineValue;
        });
        $('#total-from').text(totalFrom.toFixed(3));
        $('#total-to').text(totalTo.toFixed(3));
        $('#total-value').text(totalValue.toFixed(2));
    }

    function recomputeRow($row) {
        var fromFactor = parseFloat($row.find('.from-carat option:selected').data('factor'));
        var toFactor = parseFloat($row.find('.to-carat option:selected').data('factor'));
        var fromWeight = parseFloat($row.find('.from-weight').val());

        if (!isNaN(fromFactor) && !isNaN(toFactor) && toFactor > 0 && !isNaN(fromWeight)) {
            var toWeight = fromWeight * (fromFactor / toFactor);
            $row.find('.to-weight').val(toWeight.toFixed(3));
        }
        recalcTotals();
    }

    function addLine() {
        tbody.append(template);
        reindex();
        recalcTotals();
    }

    $('#add-line-btn').on('click', addLine);
    addLine();

    tbody.on('click', '.remove-line-btn', function () {
        $(this).closest('tr').remove();
        reindex();
        recalcTotals();
    });

    tbody.on('change', '.from-carat, .to-carat', function () {
        recomputeRow($(this).closest('tr'));
    });

    tbody.on('input', '.from-weight', function () {
        recomputeRow($(this).closest('tr'));
    });

    tbody.on('input', '.to-weight, .unit-cost', recalcTotals);

    $('#bkt-form').on('submit', function (e) {
        e.preventDefault();

        var lines = [];
        tbody.find('tr.line-row').each(function () {
            var $row = $(this);
            lines.push({
                from_carat_id: $row.find('.from-carat').val(),
                to_carat_id: $row.find('.to-carat').val(),
                from_weight: $row.find('.from-weight').val(),
                to_weight: $row.find('.to-weight').val(),
                unit_cost: $row.find('.unit-cost').val(),
                line_notes: $row.find('.line-notes').val(),
            });
        });

        if (lines.length === 0) {
            $('#bkt-alert').html('<div class="alert alert-danger">يجب إضافة سطر واحد على الأقل.</div>');
            return;
        }

        var payload = {
            _token: $('input[name="_token"]').val(),
            bill_date: $('input[name="bill_date"]').val(),
            from_branch_id: $('select[name="from_branch_id"]').val(),
            to_branch_id: $('select[name="to_branch_id"]').val(),
            gold_carat_type_id: $('select[name="gold_carat_type_id"]').val(),
            account_id: $('select[name="account_id"]').val(),
            notes: $('input[name="notes"]').val(),
            lines: lines,
        };

        $('#bkt-alert').html('');
        $.ajax({
            url: "{{ route('branch_karat_transfers.store') }}",
            method: 'POST',
            data: payload,
            success: function (res) {
                if (res.status && res.redirect) {
                    window.location.href = res.redirect;
                }
            },
            error: function (xhr) {
                var msg = 'حدث خطأ.';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.errors) {
                        msg = xhr.responseJSON.errors.join('<br>');
                    } else if (xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                }
                $('#bkt-alert').html('<div class="alert alert-danger">' + msg + '</div>');
            },
        });
    });
});
</script>
@endsection
