@extends('admin.layouts.master')
@section('content')
@can('employee.branch_karat_transfers.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <style>
        body { direction: rtl; }
        .bkt-index .card-header { display: flex; align-items: center; justify-content: space-between; }
        .bkt-index table th, .bkt-index table td { text-align: center; vertical-align: middle; }
    </style>

    <div class="row row-sm bkt-index">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center w-100">
                        [ التحويلات بين الفروع ]
                    </h4>
                </div>
                <div class="card-body">
                    <div class="mb-3 d-flex flex-wrap gap-2" style="gap:8px;">
                        @can('employee.branch_karat_transfers.add')
                            <a href="{{ route('branch_karat_transfers.create') }}" class="btn btn-primary">
                                <i class="fa fa-plus"></i> تحويل جديد
                            </a>
                        @endcan
                        <a href="{{ route('branch_karat_transfers.report') }}" class="btn btn-secondary">
                            <i class="fa fa-chart-line"></i> تقرير التحويلات
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>رقم المستند</th>
                                    <th>التاريخ</th>
                                    <th>من فرع</th>
                                    <th>إلى فرع</th>
                                    <th>النوع</th>
                                    <th>المستخدم</th>
                                    <th>إجمالي الوزن (المصدر)</th>
                                    <th>إجمالي الوزن (الوجهة)</th>
                                    <th>إجمالي القيمة</th>
                                    <th class="no-print">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($transfers as $transfer)
                                    <tr>
                                        <td>{{ $transfer->id }}</td>
                                        <td>{{ $transfer->bill_number }}</td>
                                        <td>{{ optional($transfer->bill_date)->format('Y-m-d') }}</td>
                                        <td>{{ $transfer->fromBranch?->name ?? '-' }}</td>
                                        <td>{{ $transfer->toBranch?->name ?? '-' }}</td>
                                        <td>{{ $transfer->goldCaratType?->title ?? '-' }}</td>
                                        <td>{{ $transfer->user?->name ?? '-' }}</td>
                                        <td>{{ number_format($transfer->total_from_weight, 3) }}</td>
                                        <td>{{ number_format($transfer->total_to_weight, 3) }}</td>
                                        <td>{{ number_format($transfer->total_value, 2) }}</td>
                                        <td class="no-print">
                                            <a href="{{ route('branch_karat_transfers.show', $transfer->id) }}" class="btn btn-sm btn-success">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            @can('employee.branch_karat_transfers.delete')
                                                <form action="{{ route('branch_karat_transfers.destroy', $transfer->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('هل أنت متأكد من حذف هذا التحويل؟');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11">لا توجد تحويلات.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $transfers->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
