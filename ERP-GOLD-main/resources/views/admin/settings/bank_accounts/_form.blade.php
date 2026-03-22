@csrf

<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>الفرع</label>
            <select name="branch_id" class="form-control" required>
                <option value="">حدد الفرع</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ (int) old('branch_id', $bankAccount->branch_id) === (int) $branch->id ? 'selected' : '' }}>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>الحساب المحاسبي</label>
            <select name="ledger_account_id" class="form-control" required>
                <option value="">حدد الحساب</option>
                @foreach($accounts as $account)
                    <option value="{{ $account->id }}" {{ (int) old('ledger_account_id', $bankAccount->ledger_account_id) === (int) $account->id ? 'selected' : '' }}>
                        {{ $account->code }} - {{ $account->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>اسم الحساب داخل النظام</label>
            <input type="text" name="account_name" class="form-control" value="{{ old('account_name', $bankAccount->account_name) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label>اسم البنك</label>
            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $bankAccount->bank_name) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>IBAN</label>
            <input type="text" name="iban" class="form-control" value="{{ old('iban', $bankAccount->iban) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>رقم الحساب</label>
            <input type="text" name="account_number" class="form-control" value="{{ old('account_number', $bankAccount->account_number) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>اسم الجهاز / الشبكة</label>
            <input type="text" name="terminal_name" class="form-control" value="{{ old('terminal_name', $bankAccount->terminal_name) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label>كود الجهاز</label>
            <input type="text" name="device_code" class="form-control" value="{{ old('device_code', $bankAccount->device_code) }}">
        </div>
    </div>
</div>

<div class="row mt-2">
    <div class="col-md-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="supports_credit_card" name="supports_credit_card" value="1" {{ old('supports_credit_card', $bankAccount->supports_credit_card ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="supports_credit_card">يدعم الشبكة</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="supports_bank_transfer" name="supports_bank_transfer" value="1" {{ old('supports_bank_transfer', $bankAccount->supports_bank_transfer ?? true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="supports_bank_transfer">يدعم التحويل</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="is_default" name="is_default" value="1" {{ old('is_default', $bankAccount->is_default) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_default">الحساب الافتراضي للفرع</label>
        </div>
    </div>
    <div class="col-md-3">
        <div class="custom-control custom-checkbox">
            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" {{ old('is_active', $bankAccount->exists ? $bankAccount->is_active : true) ? 'checked' : '' }}>
            <label class="custom-control-label" for="is_active">نشط</label>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <button type="submit" class="btn btn-info btn-md">{{ $submitLabel }}</button>
</div>
