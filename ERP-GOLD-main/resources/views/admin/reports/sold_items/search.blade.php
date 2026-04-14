@extends('admin.layouts.master')
@section('content')
@php
    $userFilterLocked = $userFilterLocked ?? false;
    $selectedUserId = old('user_id', $defaultFilters['user_id'] ?? '');
    $legacyInvoiceNumber = old('invoice_number', $defaultFilters['invoice_number'] ?? '');
    $invoiceNumberFromValue = old('invoice_number_from', $defaultFilters['invoice_number_from'] ?? $legacyInvoiceNumber);
    $invoiceNumberToValue = old('invoice_number_to', $defaultFilters['invoice_number_to'] ?? $legacyInvoiceNumber);
@endphp
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card shadow-sm">
            <div class="card-header pb-0">
                <div class="col-lg-12 margin-tb">
                    <h4 class="alert alert-primary text-center mb-0">
                        {{ $pageTitle ?? __('main.sold_items_report') }}
                    </h4>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ $formAction ?? route('reports.sold_items_report.search') }}">
                    @csrf
                    @if(!empty($presetClassification))
                        <input type="hidden" name="inventory_classification" value="{{ $presetClassification }}">
                    @endif
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
                                <label>من رقم الفاتورة</label>
                                <input type="text" name="invoice_number_from" class="form-control" value="{{ $invoiceNumberFromValue }}" placeholder="مثال: SALE-1001">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>إلى رقم الفاتورة</label>
                                <input type="text" name="invoice_number_to" class="form-control" value="{{ $invoiceNumberToValue }}" placeholder="مثال: SALE-1099">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            @include('admin.reports.partials.branch_filter', [
                                'branches' => $branches,
                                'defaultFilters' => $defaultFilters,
                                'branchFieldId' => 'sold_items_branch_ids',
                                'branchHiddenFieldId' => 'sold_items_branch_id',
                                'branchLabelText' => 'الفرع',
                            ])
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>المستخدم</label>
                                @if($userFilterLocked)
                                    <select class="form-control" disabled aria-disabled="true">
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected($selectedUserId == $user->id)>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <input type="hidden" name="user_id" value="{{ $selectedUserId }}">
                                    <small class="text-muted d-block mt-2">يمكنك عرض تقارير المستخدم الحالي فقط.</small>
                                @else
                                    <select name="user_id" class="form-control">
                                        <option value="">الكل</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected($selectedUserId == $user->id)>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>تصنيف الصنف</label>
                                <select name="inventory_classification" class="form-control">
                                    <option value="">الكل</option>
                                    @foreach($inventoryClassifications as $value => $label)
                                        <option value="{{ $value }}" @selected(old('inventory_classification', $defaultFilters['inventory_classification'] ?? '') === $value)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>{{ __('main.carats') }}</label>
                                <select id="karat" name="carat" class="form-control">
                                    <option value="">الكل</option>
                                    @foreach($carats as $carat)
                                        <option value="{{ $carat->id }}" @selected(old('carat', $defaultFilters['carat'] ?? '') == $carat->id)>
                                            {{ $carat->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>{{ __('main.category') }}</label>
                                <select id="category" name="category" class="form-control">
                                    <option value="">الكل</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" @selected(old('category', $defaultFilters['category'] ?? '') == $category->id)>
                                            {{ $category->title }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>{{ __('main.code') }}</label>
                                <input type="text" id="code" name="code" placeholder="كود الصنف" class="form-control" value="{{ old('code', $defaultFilters['code'] ?? '') }}">
                            </div>
                        </div>
                        <div class="col-lg-2 col-md-3">
                            <div class="form-group">
                                <label>{{ __('main.name') }}</label>
                                <input type="text" id="name" name="name" placeholder="اسم الصنف" class="form-control" value="{{ old('name', $defaultFilters['name'] ?? '') }}">
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
