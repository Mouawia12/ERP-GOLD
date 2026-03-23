@extends('admin.layouts.master')

@section('content')
@can('employee.manufacturing_orders.show')
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h4 class="alert alert-primary text-center mb-2">{{ __('main.manufacturing_receipt_show') }}</h4>
                            <p class="text-muted mb-0">
                                رقم المستند: <strong>{{ $receipt->bill_number }}</strong>
                                <span class="mx-2">|</span>
                                أمر التصنيع: <strong>{{ $receipt->parent?->bill_number ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            @if($receipt->parent)
                                <a href="{{ route('manufacturing_orders.show', $receipt->parent->id) }}" class="btn btn-sm btn-outline-primary">العودة إلى الأمر</a>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">عدد السطور</div>
                                    <div class="h3 mb-0">{{ number_format($summary['lines_count']) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">إجمالي الكمية المستلمة</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_quantity'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">{{ __('main.manufacturing_received_weight') }}</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_weight'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">القيمة</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_value'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">التاريخ</label>
                            <div class="font-weight-bold">{{ $receipt->date }} {{ $receipt->time }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">{{ __('main.manufacturer') }}</label>
                            <div class="font-weight-bold">{{ $receipt->customer?->name ?? $receipt->bill_client_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">المستخدم</label>
                            <div class="font-weight-bold">{{ $receipt->user?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">{{ __('main.manufacturing_wip_account') }}</label>
                            <div class="font-weight-bold">{{ $receipt->account?->name ?? '-' }}</div>
                        </div>
                        @if(!empty($receipt->notes))
                            <div class="col-12">
                                <label class="text-muted d-block mb-1">ملاحظات</label>
                                <div class="border rounded p-3 bg-light">{{ $receipt->notes }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>الصنف</th>
                                    <th>العيار</th>
                                    <th>نوع الذهب</th>
                                    <th>الرصيد قبل الاستلام</th>
                                    <th>الكمية المستلمة</th>
                                    <th>الوزن المستلم</th>
                                    <th>التكلفة/جرام</th>
                                    <th>القيمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($receipt->details as $index => $detail)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $detail->item?->title ?? '-' }}</td>
                                        <td>{{ $detail->carat?->title ?? '-' }}</td>
                                        <td>{{ $detail->goldCaratType?->title ?? '-' }}</td>
                                        <td>{{ number_format((float) ($detail->stock_actual_weight ?? 0), 3) }}</td>
                                        <td>{{ number_format((float) $detail->in_quantity, 3) }}</td>
                                        <td>{{ number_format((float) $detail->in_weight, 3) }}</td>
                                        <td>{{ number_format((float) $detail->unit_cost, 4) }}</td>
                                        <td>{{ number_format((float) $detail->net_total, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
