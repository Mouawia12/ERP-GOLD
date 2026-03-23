@extends('admin.layouts.master')

@section('content')
@can('employee.manufacturing_orders.add')
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h4 class="alert alert-primary text-center mb-2">{{ __('main.manufacturing_return_add') }}</h4>
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

                    <form method="POST" action="{{ route('manufacturing_returns.store', $order->id) }}">
                        @csrf

                        <div class="row mb-4">
                            <div class="col-md-4 mb-3">
                                <label>التاريخ والوقت</label>
                                <input type="datetime-local" name="bill_date" class="form-control" value="{{ old('bill_date', now()->format('Y-m-d\TH:i')) }}" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>{{ __('main.manufacturing_return_direction') }}</label>
                                <select name="return_direction" id="manufacturingReturnDirection" class="form-control" required>
                                    <option value="from_manufacturer" {{ $defaultDirection === 'from_manufacturer' ? 'selected' : '' }}>{{ __('main.manufacturing_return_from_manufacturer') }}</option>
                                    <option value="to_manufacturer" {{ $defaultDirection === 'to_manufacturer' ? 'selected' : '' }}>{{ __('main.manufacturing_return_to_manufacturer') }}</option>
                                </select>
                                <small id="manufacturingReturnHint" class="text-muted d-block mt-2"></small>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label>ملاحظات</label>
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
                                        <th>{{ __('main.manufacturing_remaining_weight') }}</th>
                                        <th>{{ __('main.manufacturing_available_return_weight') }}</th>
                                        <th>الرصيد الحالي في الفرع</th>
                                        <th>الكمية</th>
                                        <th>الوزن</th>
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
                                            <td>{{ number_format((float) $line['available_for_return_weight'], 3) }}</td>
                                            <td>{{ number_format((float) $line['current_branch_weight'], 3) }}</td>
                                            <td>
                                                <input type="hidden" name="parent_detail_id[]" value="{{ $line['detail_id'] }}">
                                                <input type="number" step="0.001" min="0" name="quantity[]" class="form-control" value="{{ old('quantity.' . $index) }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.001" min="0" name="weight[]" class="form-control" value="{{ old('weight.' . $index) }}">
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="btn btn-info px-5">
                                حفظ مستند الإرجاع
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
    (function () {
        var select = document.getElementById('manufacturingReturnDirection');
        var hint = document.getElementById('manufacturingReturnHint');

        if (!select || !hint) {
            return;
        }

        function updateHint() {
            hint.textContent = select.value === 'to_manufacturer'
                ? 'استخدم هذا الاتجاه لإرجاع وزن سبق استلامه إلى المصنع، ويشترط توفره فعليًا في رصيد الفرع.'
                : 'استخدم هذا الاتجاه عندما يعيد المصنع خامًا أو وزنًا زائدًا إلى الفرع قبل إغلاق الأمر.';
        }

        select.addEventListener('change', updateHint);
        updateHint();
    })();
</script>
@endsection
