@extends('admin.layouts.master')
@section('content')
@can('employee.branch_karat_transfers.show')
    <style>
        body { direction: rtl; }
        .bkt-report table th, .bkt-report table td { text-align: center; vertical-align: middle; }
        .bkt-report .filters-card { background: #f8f9fb; }
        @media print {
            .no-print { display: none !important; }
        }
    </style>

    <div class="row row-sm bkt-report">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center w-100">
                        تقرير التحويلات بين الفروع
                    </h4>
                </div>
                <div class="card-body">
                    <div class="card filters-card mb-3 no-print">
                        <div class="card-body">
                            <form method="POST" action="{{ route('branch_karat_transfers.report.search') }}">
                                @csrf
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>من تاريخ</label>
                                            <input type="date" name="date_from" class="form-control" value="{{ $filters['date_from'] }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>إلى تاريخ</label>
                                            <input type="date" name="date_to" class="form-control" value="{{ $filters['date_to'] }}">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>من فرع</label>
                                            <select name="from_branch_id" class="form-control">
                                                <option value="">-- الكل --</option>
                                                @foreach($branches as $b)
                                                    <option value="{{ $b->id }}" {{ (string) $filters['from_branch_id'] === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>إلى فرع</label>
                                            <select name="to_branch_id" class="form-control">
                                                <option value="">-- الكل --</option>
                                                @foreach($branches as $b)
                                                    <option value="{{ $b->id }}" {{ (string) $filters['to_branch_id'] === (string) $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>العيار (المصدر)</label>
                                            <select name="from_carat_id" class="form-control">
                                                <option value="">-- الكل --</option>
                                                @foreach($carats as $c)
                                                    <option value="{{ $c->id }}" {{ (string) $filters['from_carat_id'] === (string) $c->id ? 'selected' : '' }}>{{ $c->getTranslation('title', 'ar') ?? $c->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>العيار (الوجهة)</label>
                                            <select name="to_carat_id" class="form-control">
                                                <option value="">-- الكل --</option>
                                                @foreach($carats as $c)
                                                    <option value="{{ $c->id }}" {{ (string) $filters['to_carat_id'] === (string) $c->id ? 'selected' : '' }}>{{ $c->getTranslation('title', 'ar') ?? $c->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fa fa-search"></i> عرض التقرير
                                        </button>
                                        <button type="button" class="btn btn-success ml-2" onclick="window.print()">
                                            <i class="fa fa-print"></i> طباعة
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>رقم المستند</th>
                                    <th>التاريخ</th>
                                    <th>المستخدم</th>
                                    <th>من فرع</th>
                                    <th>إلى فرع</th>
                                    <th>العيار</th>
                                    <th>الوزن</th>
                                    <th>العيار الجديد</th>
                                    <th>الوزن</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php($totalFrom = 0)
                                @php($totalTo = 0)
                                @forelse($lines as $i => $line)
                                    @php($totalFrom += $line->from_weight)
                                    @php($totalTo += $line->to_weight)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>
                                            <a href="{{ route('branch_karat_transfers.show', $line->transfer_id) }}">
                                                {{ $line->transfer?->bill_number }}
                                            </a>
                                        </td>
                                        <td>{{ optional($line->transfer?->bill_date)->format('Y-m-d') }}</td>
                                        <td>{{ $line->transfer?->user?->name ?? '-' }}</td>
                                        <td>{{ $line->transfer?->fromBranch?->name ?? '-' }}</td>
                                        <td>{{ $line->transfer?->toBranch?->name ?? '-' }}</td>
                                        <td>{{ $line->fromCarat?->getTranslation('title', 'ar') ?? $line->fromCarat?->title }}</td>
                                        <td>{{ number_format($line->from_weight, 3) }}</td>
                                        <td>{{ $line->toCarat?->getTranslation('title', 'ar') ?? $line->toCarat?->title }}</td>
                                        <td>{{ number_format($line->to_weight, 3) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10">لا توجد نتائج. حدد الفلاتر واضغط "عرض التقرير".</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($lines->isNotEmpty())
                                <tfoot>
                                    <tr class="bg-light">
                                        <th colspan="7" class="text-right">الإجمالي</th>
                                        <th>{{ number_format($totalFrom, 3) }}</th>
                                        <th></th>
                                        <th>{{ number_format($totalTo, 3) }}</th>
                                    </tr>
                                </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
