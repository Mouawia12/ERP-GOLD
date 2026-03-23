@extends('admin.layouts.master')

@section('content')
@can('employee.stock_settlements.show')
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h4 class="alert alert-primary text-center mb-2">تفاصيل جرد المخزون</h4>
                            <p class="text-muted mb-0">
                                رقم الجرد: <strong>{{ $invoice->bill_number }}</strong>
                                <span class="mx-2">|</span>
                                الفرع: <strong>{{ $invoice->branch?->name ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('stock_settlements.index') }}" class="btn btn-sm btn-outline-primary">العودة إلى القائمة</a>
                            @can('employee.stock_settlements.add')
                                <a href="{{ route('stock_settlements.create') }}" class="btn btn-sm btn-primary">إنشاء جرد جديد</a>
                            @endcan
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
                                    <div class="text-muted mb-1">الوزن النظامي</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_actual_weight'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">الوزن المعدود</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_counted_weight'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">صافي الفرق</div>
                                    <div class="h3 mb-0">{{ number_format($summary['net_diff_weight'], 3) }}</div>
                                    <small class="text-muted">إجمالي الفروق المطلقة: {{ number_format($summary['absolute_diff_weight'], 3) }}</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">التاريخ</label>
                            <div class="font-weight-bold">{{ $invoice->date }} {{ $invoice->time }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">المستخدم</label>
                            <div class="font-weight-bold">{{ $invoice->user?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">حساب الجرد</label>
                            <div class="font-weight-bold">{{ $invoice->account?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">القيمة الإجمالية</label>
                            <div class="font-weight-bold">{{ number_format((float) $invoice->net_total, 2) }}</div>
                        </div>
                        @if(!empty($invoice->notes))
                            <div class="col-12">
                                <label class="text-muted d-block mb-1">ملاحظات</label>
                                <div class="border rounded p-3 bg-light">{{ $invoice->notes }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover text-center">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>الصنف / العيار</th>
                                    <th>التصنيف</th>
                                    <th>الوزن النظامي</th>
                                    <th>الوزن المعدود</th>
                                    <th>الفرق</th>
                                    <th>الحالة</th>
                                    <th>القيمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invoice->details as $index => $detail)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            {{ $detail->item?->title ?? ('عيار افتراضي ' . ($detail->carat?->title ?? '-')) }}
                                        </td>
                                        <td>
                                            {{ $detail->item?->inventory_classification_label ?? $detail->goldCaratType?->title ?? '-' }}
                                        </td>
                                        <td>{{ number_format($detail->settlementActualWeightValue, 3) }}</td>
                                        <td>{{ number_format($detail->settlementCountedWeightValue, 3) }}</td>
                                        <td>{{ number_format($detail->settlementDiffWeightValue, 3) }}</td>
                                        <td>
                                            <span class="badge badge-{{ $detail->settlementDiffWeightValue > 0 ? 'success' : ($detail->settlementDiffWeightValue < 0 ? 'danger' : 'secondary') }}">
                                                {{ $detail->settlementDiffDirectionLabel }}
                                            </span>
                                        </td>
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
