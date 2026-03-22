@extends('admin.layouts.master')
@section('content')
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card shadow-sm">
            <div class="card-header pb-0">
                <div class="col-lg-12 margin-tb">
                    <h4 class="alert alert-primary text-center mb-0">
                        {{ __('main.account_movement_report') }}
                    </h4>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('account_statement.search') }}">
                    @csrf
                    <div class="row">
                        <div class="col-lg-4 col-md-6">
                            <div class="form-group">
                                <label>الحساب</label>
                                <select id="account_id" name="account_id" class="js-example-basic-single w-100 text-center">
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" @selected(old('account_id', $defaultFilters['account_id'] ?? '') == $account->id)>
                                            {{ $account->name . ' -- ' . $account->code }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>من تاريخ</label>
                                <input type="date" name="date_from" class="form-control" value="{{ old('date_from', $defaultFilters['date_from'] ?? '') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>إلى تاريخ</label>
                                <input type="date" name="date_to" class="form-control" value="{{ old('date_to', $defaultFilters['date_to'] ?? '') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>من وقت</label>
                                <input type="time" name="from_time" class="form-control" value="{{ old('from_time', $defaultFilters['from_time'] ?? '') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>إلى وقت</label>
                                <input type="time" name="to_time" class="form-control" value="{{ old('to_time', $defaultFilters['to_time'] ?? '') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>المرجع / رقم العملية</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $defaultFilters['invoice_number'] ?? '') }}" placeholder="فاتورة أو سند أو قيد">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>نوع المصدر</label>
                                <select name="source_type" class="form-control">
                                    <option value="">الكل</option>
                                    <option value="invoice" @selected(old('source_type', $defaultFilters['source_type'] ?? '') === 'invoice')>فاتورة</option>
                                    <option value="voucher" @selected(old('source_type', $defaultFilters['source_type'] ?? '') === 'voucher')>سند مالي</option>
                                    <option value="manual" @selected(old('source_type', $defaultFilters['source_type'] ?? '') === 'manual')>قيد يدوي</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>الفرع</label>
                                @if(auth('admin-web')->user()?->is_admin)
                                    <select class="form-control" name="branch_id">
                                        <option value="">الكل</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected(old('branch_id', $defaultFilters['branch_id'] ?? '') == $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <input class="form-control" type="text" readonly value="{{ auth('admin-web')->user()?->branch?->name }}"/>
                                    <input type="hidden" name="branch_id" value="{{ old('branch_id', $defaultFilters['branch_id'] ?? auth('admin-web')->user()?->branch_id) }}">
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>المستخدم</label>
                                <select name="user_id" class="form-control">
                                    <option value="">الكل</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" @selected(old('user_id', $defaultFilters['user_id'] ?? '') == $user->id)>
                                            {{ $user->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-3">
                        <button type="submit" class="btn btn-primary px-5">
                            {{ __('main.search_btn') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
