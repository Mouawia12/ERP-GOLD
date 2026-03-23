@extends('admin.layouts.master')

@section('content')
@can('employee.manufacturing_orders.add')
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h4 class="alert alert-primary text-center mb-2">{{ __('main.manufacturing_loss_settlement_add') }}</h4>
                            <p class="text-muted mb-0">
                                أمر التصنيع: <strong>{{ $order->bill_number }}</strong>
                                <span class="mx-2">|</span>
                                المصنع: <strong>{{ $order->customer?->name ?? $order->bill_client_name ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('manufacturing_orders.show', $order->id) }}" class="btn btn-sm btn-outline-primary">العودة إلى الأمر</a>
                        </div>
                    </div>
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

                    <form method="POST" action="{{ route('manufacturing_loss_settlements.store', $order->id) }}">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label>التاريخ والوقت</label>
                                <input type="datetime-local" name="bill_date" class="form-control" value="{{ old('bill_date', now()->format('Y-m-d\\TH:i')) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ __('main.manufacturing_loss_account') }}</label>
                                <select name="account_id" class="form-control" required>
                                    <option value="">اختر الحساب</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" {{ (int) old('account_id') === (int) $account->id ? 'selected' : '' }}>
                                            {{ $account->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>ملاحظات عامة</label>
                                <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered text-center align-middle">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#</th>
                                        <th>الصنف</th>
                                        <th>العيار</th>
                                        <th>نوع الذهب</th>
                                        <th>الوزن المتبقي</th>
                                        <th>{{ __('main.manufacturing_settlement_type') }}</th>
                                        <th>الكمية المسوّاة</th>
                                        <th>{{ __('main.manufacturing_settled_weight') }}</th>
                                        <th>ملاحظات السطر</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($lineProgress as $index => $line)
                                        <tr>
                                            <td>{{ $index + 1 }}</td>
                                            <td>{{ $line['item_title'] }}</td>
                                            <td>{{ $line['carat_label'] }}</td>
                                            <td>{{ $line['gold_carat_type_label'] }}</td>
                                            <td>{{ number_format((float) $line['remaining_weight'], 3) }}</td>
                                            <td>
                                                <input type="hidden" name="parent_detail_id[]" value="{{ $line['detail_id'] }}">
                                                <select name="settlement_type[]" class="form-control">
                                                    <option value="natural_loss" {{ old('settlement_type.' . $index, 'natural_loss') === 'natural_loss' ? 'selected' : '' }}>{{ __('main.natural_loss') }}</option>
                                                    <option value="final_damage" {{ old('settlement_type.' . $index) === 'final_damage' ? 'selected' : '' }}>{{ __('main.final_damage') }}</option>
                                                    <option value="review_difference" {{ old('settlement_type.' . $index) === 'review_difference' ? 'selected' : '' }}>{{ __('main.review_difference') }}</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    step="0.001"
                                                    min="0"
                                                    max="{{ $line['remaining_quantity'] }}"
                                                    name="quantity[]"
                                                    class="form-control"
                                                    value="{{ old('quantity.' . $index, $line['remaining_quantity']) }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="number"
                                                    step="0.001"
                                                    min="0.001"
                                                    max="{{ $line['remaining_weight'] }}"
                                                    name="weight[]"
                                                    class="form-control"
                                                    value="{{ old('weight.' . $index, $line['remaining_weight']) }}"
                                                >
                                            </td>
                                            <td>
                                                <input
                                                    type="text"
                                                    name="line_notes[]"
                                                    class="form-control"
                                                    value="{{ old('line_notes.' . $index) }}"
                                                    placeholder="ملاحظات اختيارية"
                                                >
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-danger px-5">
                                حفظ التسوية
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
