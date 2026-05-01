@extends('admin.layouts.master')
@section('content')
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card shadow-sm">
            <div class="card-header pb-0">
                <div class="col-lg-12 margin-tb">
                    <h4 class="alert alert-primary text-center mb-0">
                        الاقرار الضريبي
                    </h4>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="card-body">
                <form id="tax-declaration-form" method="POST" action="{{ route('tax.declaration.search') }}">
                    @csrf
                    <div class="row">
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
                                <label>رقم الفاتورة</label>
                                <input type="text" name="invoice_number" class="form-control" value="{{ old('invoice_number', $defaultFilters['invoice_number'] ?? '') }}" placeholder="مثال: TX-1001">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            @include('admin.reports.partials.branch_filter', [
                                'branches' => $branches,
                                'defaultFilters' => $defaultFilters,
                                'branchFieldId' => 'tax_declaration_branch_ids',
                                'branchHiddenFieldId' => 'tax_declaration_branch_id',
                                'branchLabelText' => 'الفرع',
                            ])
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
                        <button
                            type="button"
                            class="btn btn-primary px-5"
                            data-print-open
                            data-print-form="#tax-declaration-form"
                            data-print-url="{{ route('tax.declaration.print') }}"
                        >
                            عرض التقرير
                        </button>
                        <button
                            type="button"
                            class="btn btn-success px-5"
                            data-print-open
                            data-print-form="#tax-declaration-form"
                            data-print-url="{{ route('tax.declaration.print') }}"
                            data-auto-print="1"
                            data-print-target="_iframe"
                        >
                            طباعة
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
