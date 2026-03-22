@php
    $showCarat = $showCarat ?? false;
    $showNetMoney = $showNetMoney ?? false;
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="form-group">
            <label>{{ __('main.branch') }}</label>
            @if(Auth::user()->is_admin)
                <select class="js-example-basic-single w-100" name="branch_id">
                    <option value="">{{ __('main.all_branches') }}</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}" @selected(old('branch_id', $defaultFilters['branch_id'] ?? '') == $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            @else
                <input class="form-control" type="text" readonly value="{{ Auth::user()->branch->name }}"/>
                <input type="hidden" name="branch_id" value="{{ Auth::user()->branch_id }}"/>
            @endif
        </div>
    </div>

    <div class="col-md-4">
        <div class="form-group">
            <label>المستخدم</label>
            <select class="js-example-basic-single w-100" name="user_id">
                <option value="">جميع المستخدمين</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $defaultFilters['user_id'] ?? '') == $user->id)>
                        {{ $user->name }}
                    </option>
                @endforeach
            </select>
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
    <div class="col-md-6">
        <div class="form-group">
            <label>رقم الفاتورة</label>
            <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $defaultFilters['invoice_number'] ?? '') }}">
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
