@php
    $showCarat = $showCarat ?? false;
    $showNetMoney = $showNetMoney ?? false;
    $userFilterLocked = $userFilterLocked ?? false;
    $selectedUserId = old('user_id', $defaultFilters['user_id'] ?? '');
    $legacyInvoiceNumber = old('invoice_number', $defaultFilters['invoice_number'] ?? '');
    $invoiceNumberFromValue = old('invoice_number_from', $defaultFilters['invoice_number_from'] ?? $legacyInvoiceNumber);
    $invoiceNumberToValue = old('invoice_number_to', $defaultFilters['invoice_number_to'] ?? $legacyInvoiceNumber);
    $invoiceRangeColumnClass = $showNetMoney ? 'col-md-3' : 'col-md-6';
@endphp

<div class="row">
    <div class="col-md-4">
        @include('admin.reports.partials.branch_filter', [
            'branches' => $branches,
            'defaultFilters' => $defaultFilters,
            'branchFieldId' => 'stock_report_branch_ids',
            'branchHiddenFieldId' => 'stock_report_branch_id',
            'branchLabelText' => __('main.branch'),
        ])
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>المستخدم</label>
            @if($userFilterLocked)
                <select class="js-example-basic-single w-100" disabled aria-disabled="true">
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($selectedUserId == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                <small class="text-muted d-block mt-2">يمكنك عرض تقارير المستخدم الحالي فقط.</small>
            @else
                <select class="js-example-basic-single w-100" name="user_id">
                    <option value="">جميع المستخدمين</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" @selected($selectedUserId == $user->id)>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    </div>

    @if($showCarat)
        <div class="col-md-4">
            <div class="form-group">
                <label>العيار</label>
                <select class="js-example-basic-single w-100" name="carat_id">
                    <option value="">جميع العيارات</option>
                    @foreach($carats as $carat)
                        <option value="{{ $carat->id }}" @selected(old('carat_id', $defaultFilters['carat_id'] ?? '') == $carat->id)>
                            {{ $carat->title }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    @endif
</div>

<div class="row">
    <div class="col-md-3">
        <div class="form-group">
            <label>من تاريخ</label>
            <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $defaultFilters['date_from'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>إلى تاريخ</label>
            <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $defaultFilters['date_to'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>من وقت</label>
            <input type="time" name="from_time" class="form-control" value="{{ old('from_time', $defaultFilters['from_time'] ?? '') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="form-group">
            <label>إلى وقت</label>
            <input type="time" name="to_time" class="form-control" value="{{ old('to_time', $defaultFilters['to_time'] ?? '') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="{{ $invoiceRangeColumnClass }}">
        <div class="form-group">
            <label>من رقم الفاتورة</label>
            <input type="text" name="invoice_number_from" class="form-control" value="{{ $invoiceNumberFromValue }}" placeholder="مثال: SALE-1001">
        </div>
    </div>

    <div class="{{ $invoiceRangeColumnClass }}">
        <div class="form-group">
            <label>إلى رقم الفاتورة</label>
            <input type="text" name="invoice_number_to" class="form-control" value="{{ $invoiceNumberToValue }}" placeholder="مثال: SALE-1099">
        </div>
    </div>

    @if($showNetMoney)
        <div class="col-md-6">
            <div class="form-group">
                <label>إجمالي الفاتورة</label>
                <input type="number" step="any" name="netMoney" class="form-control" value="{{ old('netMoney', $defaultFilters['netMoney'] ?? '') }}">
            </div>
        </div>
    @endif
</div>
