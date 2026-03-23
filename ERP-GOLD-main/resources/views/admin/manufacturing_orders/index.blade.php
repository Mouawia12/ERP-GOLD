@extends('admin.layouts.master')

@section('content')
@can('employee.manufacturing_orders.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4 class="alert alert-primary text-center">[ {{ __('main.manufacturing_orders') }} ]</h4>
                    </div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="px-4 pt-4">
                        <div class="row mb-4">
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">كل الأوامر</div>
                                        <div class="h4 mb-0">{{ number_format($statusSummary['all']) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">المفتوحة</div>
                                        <div class="h4 mb-0 text-warning">{{ number_format($statusSummary['open']) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">المكتملة</div>
                                        <div class="h4 mb-0 text-success">{{ number_format($statusSummary['completed']) }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-6 mb-3">
                                <div class="card border-0 shadow-sm h-100">
                                    <div class="card-body">
                                        <div class="text-muted mb-1">المتأخرة</div>
                                        <div class="h4 mb-0 text-danger">{{ number_format($statusSummary['late']) }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <a href="{{ route('manufacturing_orders.index', ['status' => 'all']) }}"
                               class="btn btn-sm {{ $selectedStatus === 'all' ? 'btn-primary' : 'btn-outline-primary' }}">
                                كل الأوامر
                            </a>
                            <a href="{{ route('manufacturing_orders.index', ['status' => 'open']) }}"
                               class="btn btn-sm {{ $selectedStatus === 'open' ? 'btn-warning text-white' : 'btn-outline-warning' }}">
                                المفتوحة
                            </a>
                            <a href="{{ route('manufacturing_orders.index', ['status' => 'completed']) }}"
                               class="btn btn-sm {{ $selectedStatus === 'completed' ? 'btn-success' : 'btn-outline-success' }}">
                                المكتملة
                            </a>
                            <a href="{{ route('manufacturing_orders.index', ['status' => 'late']) }}"
                               class="btn btn-sm {{ $selectedStatus === 'late' ? 'btn-danger' : 'btn-outline-danger' }}">
                                المتأخرة
                            </a>
                        </div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="display w-100 text-nowrap table-bordered" id="ManufacturingOrdersTable" style="text-align:center;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('main.bill_no') }}</th>
                                            <th>التاريخ</th>
                                            <th>الفرع</th>
                                            <th>{{ __('main.manufacturer') }}</th>
                                            <th>المستخدم</th>
                                            <th>{{ __('main.manufacturer_total_weight') }}</th>
                                            <th>{{ __('main.manufacturing_received_weight') }}</th>
                                            <th>{{ __('main.manufacturing_settled_weight') }}</th>
                                            <th>{{ __('main.manufacturing_remaining_weight') }}</th>
                                            <th>الحالة</th>
                                            <th>{{ __('main.total_cost') }}</th>
                                            <th>{{ __('main.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('js')
<script>
    document.title = "{{ __('main.manufacturing_orders') }}";

    $(document).ready(function () {
        $('#ManufacturingOrdersTable').DataTable({
            processing: true,
            serverSide: true,
            responsive: true,
            ajax: {
                url: "{{ route('manufacturing_orders.index') }}",
                data: function (d) {
                    d.status = @json($selectedStatus);
                }
            },
            columns: [
                {data: 'id', name: 'id'},
                {data: 'bill_number', name: 'bill_number'},
                {data: 'date', name: 'date'},
                {data: 'branch_name', name: 'branch_name', orderable: false},
                {data: 'manufacturer_name', name: 'manufacturer_name', orderable: false},
                {data: 'user_name', name: 'user_name', orderable: false},
                {data: 'total_weight', name: 'total_weight', orderable: false, searchable: false},
                {data: 'received_weight', name: 'received_weight', orderable: false, searchable: false},
                {data: 'settled_weight', name: 'settled_weight', orderable: false, searchable: false},
                {data: 'remaining_weight', name: 'remaining_weight', orderable: false, searchable: false},
                {data: 'status_badge', name: 'status_badge', orderable: false, searchable: false},
                {data: 'net_total', name: 'net_total'},
                {data: 'action', name: 'action', orderable: false, searchable: false},
            ],
            dom: 'lBfrtip',
            buttons: [
                {
                    text: ' @can("employee.manufacturing_orders.add") <a href="{{ route('manufacturing_orders.create') }}" class="text-white"><i class="fa fa-plus"></i></a> @endcan ',
                },
                {extend: 'excel', text: '<i title="export to excel" class="fa fa-file-excel"></i>'},
                {extend: 'print', text: '<i title="print" class="fa fa-print"></i>'},
                {extend: 'colvis', text: '<i title="column visibility" class="fa fa-eye"></i>'},
            ],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']],
        });
    });
</script>
@endsection
