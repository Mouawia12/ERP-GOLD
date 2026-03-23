@extends('admin.layouts.master')

@section('content')
@can('employee.manufacturing_orders.show')
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <div>
                            <h4 class="alert alert-primary text-center mb-2">{{ __('main.manufacturing_orders_show') }}</h4>
                            <p class="text-muted mb-0">
                                رقم المستند: <strong>{{ $invoice->bill_number }}</strong>
                                <span class="mx-2">|</span>
                                الفرع: <strong>{{ $invoice->branch?->name ?? '-' }}</strong>
                            </p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('manufacturing_orders.index') }}" class="btn btn-sm btn-outline-primary">العودة إلى القائمة</a>
                            @can('employee.manufacturing_orders.add')
                                @if(($receiptSummary['remaining_weight'] ?? 0) > 0)
                                    <a href="{{ route('manufacturing_receipts.create', $invoice->id) }}" class="btn btn-sm btn-warning">
                                        {{ __('main.manufacturing_receipt_add') }}
                                    </a>
                                    <a href="{{ route('manufacturing_loss_settlements.create', $invoice->id) }}" class="btn btn-sm btn-outline-danger">
                                        {{ __('main.manufacturing_loss_settlement_add') }}
                                    </a>
                                @endif
                                @if(($receiptSummary['remaining_weight'] ?? 0) > 0 || ($receiptSummary['available_for_return_weight'] ?? 0) > 0 || ($receiptSummary['received_weight'] ?? 0) > 0)
                                    <a href="{{ route('manufacturing_returns.create', $invoice->id) }}" class="btn btn-sm btn-info">
                                        {{ __('main.manufacturing_return_add') }}
                                    </a>
                                @endif
                            @endcan
                            @can('employee.manufacturing_orders.add')
                                <a href="{{ route('manufacturing_orders.create') }}" class="btn btn-sm btn-primary">إنشاء أمر جديد</a>
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
                                    <div class="text-muted mb-1">إجمالي الوزن</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_weight'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">إجمالي الكمية</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_quantity'], 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">القيمة الإجمالية</div>
                                    <div class="h3 mb-0">{{ number_format($summary['total_value'], 2) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">الوزن المستلم</div>
                                    <div class="h3 mb-0 text-success">{{ number_format((float) ($receiptSummary['received_weight'] ?? 0), 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">{{ __('main.manufacturing_returned_to_branch_weight') }}</div>
                                    <div class="h3 mb-0 text-info">{{ number_format((float) ($receiptSummary['returned_to_branch_weight'] ?? 0), 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">{{ __('main.manufacturing_returned_to_manufacturer_weight') }}</div>
                                    <div class="h3 mb-0 text-secondary">{{ number_format((float) ($receiptSummary['returned_to_manufacturer_weight'] ?? 0), 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">{{ __('main.manufacturing_settled_weight') }}</div>
                                    <div class="h3 mb-0 text-danger">{{ number_format((float) ($receiptSummary['settled_weight'] ?? 0), 3) }}</div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="card-body">
                                    <div class="text-muted mb-1">{{ __('main.manufacturing_remaining_weight') }}</div>
                                    <div class="h3 mb-0 text-warning">{{ number_format((float) ($receiptSummary['remaining_weight'] ?? 0), 3) }}</div>
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
                            <label class="text-muted d-block mb-1">{{ __('main.manufacturer') }}</label>
                            <div class="font-weight-bold">{{ $invoice->customer?->name ?? $invoice->bill_client_name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">المستخدم</label>
                            <div class="font-weight-bold">{{ $invoice->user?->name ?? '-' }}</div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="text-muted d-block mb-1">{{ __('main.manufacturing_wip_account') }}</label>
                            <div class="font-weight-bold">{{ $invoice->account?->name ?? '-' }}</div>
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
                                    <th>الصنف</th>
                                    <th>العيار</th>
                                    <th>نوع الذهب</th>
                                    <th>الرصيد قبل الإرسال</th>
                                    <th>الكمية المرسلة</th>
                                    <th>الوزن المرسل</th>
                                    <th>الكمية المستلمة</th>
                                    <th>{{ __('main.manufacturing_received_weight') }}</th>
                                    <th>الكمية المرتجعة من المصنع</th>
                                    <th>{{ __('main.manufacturing_returned_to_branch_weight') }}</th>
                                    <th>الكمية المرتجعة إلى المصنع</th>
                                    <th>{{ __('main.manufacturing_returned_to_manufacturer_weight') }}</th>
                                    <th>الكمية المسوّاة</th>
                                    <th>{{ __('main.manufacturing_settled_weight') }}</th>
                                    <th>الكمية المتاحة للإرجاع إلى المصنع</th>
                                    <th>{{ __('main.manufacturing_available_return_weight') }}</th>
                                    <th>الكمية المتبقية</th>
                                    <th>{{ __('main.manufacturing_remaining_weight') }}</th>
                                    <th>التكلفة/جرام</th>
                                    <th>القيمة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($lineProgress as $index => $detail)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $detail['item_title'] }}</td>
                                        <td>{{ $detail['carat_label'] }}</td>
                                        <td>{{ $detail['gold_carat_type_label'] }}</td>
                                        <td>{{ number_format((float) $detail['stock_actual_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['sent_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['sent_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['received_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['received_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['returned_to_branch_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['returned_to_branch_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['returned_to_manufacturer_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['returned_to_manufacturer_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['settled_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['settled_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['available_for_return_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['available_for_return_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['remaining_quantity'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['remaining_weight'], 3) }}</td>
                                        <td>{{ number_format((float) $detail['unit_cost'], 4) }}</td>
                                        <td>{{ number_format((float) $detail['line_value'], 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">سندات الاستلام من التصنيع</h5>
                            <span class="text-muted">عدد السندات: {{ $receiptSummaries->count() }}</span>
                        </div>

                        @if($receiptSummaries->isEmpty())
                            <div class="alert alert-light border mb-0">
                                لا توجد سندات استلام مسجلة لهذا الأمر حتى الآن.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>رقم المستند</th>
                                            <th>التاريخ</th>
                                            <th>المستخدم</th>
                                            <th>إجمالي الكمية</th>
                                            <th>إجمالي الوزن</th>
                                            <th>القيمة</th>
                                            <th>عرض</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($receiptSummaries as $index => $receipt)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $receipt['bill_number'] }}</td>
                                                <td>{{ $receipt['date'] }} {{ $receipt['time'] }}</td>
                                                <td>{{ $receipt['user_name'] }}</td>
                                                <td>{{ number_format((float) $receipt['total_quantity'], 3) }}</td>
                                                <td>{{ number_format((float) $receipt['total_weight'], 3) }}</td>
                                                <td>{{ number_format((float) $receipt['total_value'], 2) }}</td>
                                                <td>
                                                    <a href="{{ route('manufacturing_receipts.show', $receipt['id']) }}" class="btn btn-sm btn-success">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">سندات الإرجاع من / إلى التصنيع</h5>
                            <span class="text-muted">عدد المستندات: {{ $returnSummaries->count() }}</span>
                        </div>

                        @if($returnSummaries->isEmpty())
                            <div class="alert alert-light border mb-0">
                                لا توجد مستندات إرجاع مسجلة لهذا الأمر حتى الآن.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>رقم المستند</th>
                                            <th>{{ __('main.manufacturing_return_direction') }}</th>
                                            <th>التاريخ</th>
                                            <th>المستخدم</th>
                                            <th>إجمالي الكمية</th>
                                            <th>إجمالي الوزن</th>
                                            <th>القيمة</th>
                                            <th>عرض</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($returnSummaries as $index => $return)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $return['bill_number'] }}</td>
                                                <td>{{ $return['direction_label'] }}</td>
                                                <td>{{ $return['date'] }} {{ $return['time'] }}</td>
                                                <td>{{ $return['user_name'] }}</td>
                                                <td>{{ number_format((float) $return['total_quantity'], 3) }}</td>
                                                <td>{{ number_format((float) $return['total_weight'], 3) }}</td>
                                                <td>{{ number_format((float) $return['total_value'], 2) }}</td>
                                                <td>
                                                    <a href="{{ route('manufacturing_returns.show', $return['id']) }}" class="btn btn-sm btn-info">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>

                    <div class="mt-5">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">تسويات الفاقد والهالك</h5>
                            <span class="text-muted">عدد التسويات: {{ $settlementSummaries->count() }}</span>
                        </div>

                        @if($settlementSummaries->isEmpty())
                            <div class="alert alert-light border mb-0">
                                لا توجد تسويات فاقد/هالك مسجلة لهذا الأمر حتى الآن.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover text-center">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>#</th>
                                            <th>رقم المستند</th>
                                            <th>التاريخ</th>
                                            <th>المستخدم</th>
                                            <th>إجمالي الكمية</th>
                                            <th>إجمالي الوزن</th>
                                            <th>القيمة</th>
                                            <th>عرض</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settlementSummaries as $index => $settlement)
                                            <tr>
                                                <td>{{ $index + 1 }}</td>
                                                <td>{{ $settlement['bill_number'] }}</td>
                                                <td>{{ $settlement['date'] }} {{ $settlement['time'] }}</td>
                                                <td>{{ $settlement['user_name'] }}</td>
                                                <td>{{ number_format((float) $settlement['total_quantity'], 3) }}</td>
                                                <td>{{ number_format((float) $settlement['total_weight'], 3) }}</td>
                                                <td>{{ number_format((float) $settlement['total_value'], 2) }}</td>
                                                <td>
                                                    <a href="{{ route('manufacturing_loss_settlements.show', $settlement['id']) }}" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
