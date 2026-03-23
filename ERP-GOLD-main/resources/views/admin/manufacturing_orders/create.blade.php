@extends('admin.layouts.master')

@section('content')
@can('employee.manufacturing_orders.add')
    @php
        $canChangeBranch = auth('admin-web')->user()?->is_admin || $branches->count() > 1;
    @endphp

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center mb-0">{{ __('main.manufacturing_orders_add') }}</h4>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($manufacturers->isEmpty())
                        <div class="alert alert-warning">
                            لا يوجد موردون مسجلون حاليًا لاستخدامهم كمصنع خارجي. أضف موردًا أولًا من دليل الموردين.
                        </div>
                    @endif

                    <form method="POST" action="{{ route('manufacturing_orders.store') }}" id="manufacturing_orders_form">
                        @csrf
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label>التاريخ والوقت</label>
                                <input type="datetime-local" name="bill_date" class="form-control" value="{{ old('bill_date', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>الفرع</label>
                                @if ($canChangeBranch)
                                    <select name="branch_id" id="branch_id" class="form-control" required>
                                        @foreach ($branches as $branch)
                                            <option value="{{ $branch->id }}" {{ (int) old('branch_id', $currentBranchId) === (int) $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input type="hidden" name="branch_id" id="branch_id" value="{{ old('branch_id', $currentBranchId) }}">
                                    <input type="text" class="form-control" value="{{ $branches->first()?->name ?? '-' }}" disabled>
                                @endif
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('main.manufacturer') }}</label>
                                <select name="manufacturer_id" class="form-control" required>
                                    <option value="">اختر المصنع الخارجي</option>
                                    @foreach ($manufacturers as $manufacturer)
                                        <option value="{{ $manufacturer->id }}" {{ (int) old('manufacturer_id') === (int) $manufacturer->id ? 'selected' : '' }}>
                                            {{ $manufacturer->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label>{{ __('main.manufacturing_wip_account') }}</label>
                                <select name="account_id" class="form-control" required>
                                    <option value="">اختر الحساب</option>
                                    @foreach ($accounts as $account)
                                        <option value="{{ $account->id }}" {{ (int) old('account_id') === (int) $account->id ? 'selected' : '' }}>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mb-3">
                                <label>ملاحظات</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">بنود الإرسال</h5>
                            <button type="button" class="btn btn-sm btn-primary" id="add-manufacturing-line">
                                <i class="fa fa-plus"></i> إضافة سطر
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 40%;">الصنف الذهبي</th>
                                        <th style="width: 15%;">الرصيد المتاح</th>
                                        <th style="width: 15%;">الكمية</th>
                                        <th style="width: 15%;">الوزن المرسل</th>
                                        <th style="width: 15%;">حذف</th>
                                    </tr>
                                </thead>
                                <tbody id="manufacturing-lines-body"></tbody>
                            </table>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">عدد السطور</div>
                                        <div class="h4 mb-0" id="manufacturing-lines-count">0</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">إجمالي الكمية</div>
                                        <div class="h4 mb-0" id="manufacturing-total-quantity">0.000</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">{{ __('main.manufacturer_total_weight') }}</div>
                                        <div class="h4 mb-0" id="manufacturing-total-weight">0.000</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-success px-5" {{ $manufacturers->isEmpty() ? 'disabled' : '' }}>
                                حفظ أمر التصنيع
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('js')
<script>
    document.title = "{{ __('main.manufacturing_orders_add') }}";

    const itemsByBranch = @json($itemsByBranch);
    const prefilledLines = @json($prefilledLines);
    const branchSelect = document.getElementById('branch_id');
    const linesBody = document.getElementById('manufacturing-lines-body');

    function currentBranchId() {
        return branchSelect ? String(branchSelect.value) : '';
    }

    function branchItems() {
        return itemsByBranch[currentBranchId()] || [];
    }

    function itemOptionLabel(item) {
        return `${item.title} | ${item.gold_carat_label} | ${item.gold_carat_type_label}`;
    }

    function findItem(itemId) {
        return branchItems().find((item) => Number(item.id) === Number(itemId)) || null;
    }

    function itemOptions(selectedId = null) {
        const options = ['<option value="">اختر الصنف</option>'];
        branchItems().forEach((item) => {
            const selected = Number(selectedId) === Number(item.id) ? 'selected' : '';
            options.push(`<option value="${item.id}" ${selected}>${itemOptionLabel(item)}</option>`);
        });
        return options.join('');
    }

    function addRow(line = {}) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <select name="item_id[]" class="form-control manufacturing-item-select">
                    ${itemOptions(line.item_id ?? null)}
                </select>
            </td>
            <td class="manufacturing-available-weight">0.000</td>
            <td>
                <input type="number" step="0.001" min="0.001" name="quantity[]" class="form-control manufacturing-quantity-input" value="${line.quantity ?? 1}">
            </td>
            <td>
                <input type="number" step="0.001" min="0.001" name="weight[]" class="form-control manufacturing-weight-input" value="${line.weight ?? ''}">
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger manufacturing-remove-line">
                    <i class="fa fa-trash"></i>
                </button>
            </td>
        `;

        linesBody.appendChild(row);
        updateRowAvailability(row);
        recalculateManufacturingSummary();
    }

    function updateRowAvailability(row) {
        const select = row.querySelector('.manufacturing-item-select');
        const cell = row.querySelector('.manufacturing-available-weight');
        const item = findItem(select.value);
        cell.textContent = item ? Number(item.available_weight).toFixed(3) : '0.000';
    }

    function rerenderItemSelects() {
        linesBody.querySelectorAll('tr').forEach((row) => {
            const select = row.querySelector('.manufacturing-item-select');
            const currentValue = select.value;
            select.innerHTML = itemOptions(currentValue);
            updateRowAvailability(row);
        });
    }

    function recalculateManufacturingSummary() {
        let totalQuantity = 0;
        let totalWeight = 0;

        linesBody.querySelectorAll('tr').forEach((row) => {
            totalQuantity += Number(row.querySelector('.manufacturing-quantity-input').value || 0);
            totalWeight += Number(row.querySelector('.manufacturing-weight-input').value || 0);
        });

        document.getElementById('manufacturing-lines-count').textContent = linesBody.querySelectorAll('tr').length;
        document.getElementById('manufacturing-total-quantity').textContent = totalQuantity.toFixed(3);
        document.getElementById('manufacturing-total-weight').textContent = totalWeight.toFixed(3);
    }

    document.getElementById('add-manufacturing-line').addEventListener('click', function () {
        addRow();
    });

    linesBody.addEventListener('change', function (event) {
        if (event.target.classList.contains('manufacturing-item-select')) {
            updateRowAvailability(event.target.closest('tr'));
        }

        recalculateManufacturingSummary();
    });

    linesBody.addEventListener('input', function (event) {
        if (event.target.classList.contains('manufacturing-quantity-input') || event.target.classList.contains('manufacturing-weight-input')) {
            recalculateManufacturingSummary();
        }
    });

    linesBody.addEventListener('click', function (event) {
        const removeButton = event.target.closest('.manufacturing-remove-line');
        if (!removeButton) {
            return;
        }

        removeButton.closest('tr').remove();

        if (!linesBody.querySelector('tr')) {
            addRow();
        } else {
            recalculateManufacturingSummary();
        }
    });

    if (branchSelect) {
        branchSelect.addEventListener('change', function () {
            rerenderItemSelects();
            recalculateManufacturingSummary();
        });
    }

    if (prefilledLines.length) {
        prefilledLines.forEach((line) => addRow(line));
    } else {
        addRow();
    }
</script>
@endsection
