@extends('admin.layouts.master')
@section('content')
@can('employee.branch_karat_transfers.show')
    <style>
        body { direction: rtl; }
        .bkt-show .info-box { background: #f8f9fb; border: 1px solid #e6e9ef; border-radius: 6px; padding: 14px; margin-bottom: 12px; }
        .bkt-show .info-box label { color: #6c757d; font-size: 12px; margin-bottom: 2px; display: block; }
        .bkt-show .info-box .value { font-size: 15px; font-weight: 600; }
        .bkt-show table th, .bkt-show table td { text-align: center; vertical-align: middle; }
        .bkt-show .journal-card { background: #fbfbfd; border: 1px solid #e6e9ef; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
        .bkt-show .journal-card h6 { font-weight: 700; margin-bottom: 8px; }

        @media print {
            .no-print { display: none !important; }
            body { background: #fff; }
        }
    </style>

    <div class="row row-sm bkt-show">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0 d-flex align-items-center justify-content-between flex-wrap">
                    <h4 class="alert alert-primary text-center" style="flex:1; margin:0;">
                        تحويل بين الفروع — {{ $transfer->bill_number }}
                    </h4>
                    <div class="no-print">
                        <button type="button" class="btn btn-primary" onclick="window.print()">
                            <i class="fa fa-print"></i> طباعة
                        </button>
                        <a href="{{ route('branch_karat_transfers.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-right"></i> رجوع
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="info-box">
                                <label>رقم المستند</label>
                                <div class="value">{{ $transfer->bill_number }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <label>تاريخ المستند</label>
                                <div class="value">{{ optional($transfer->bill_date)->format('Y-m-d') }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <label>المستخدم</label>
                                <div class="value">{{ $transfer->user?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="info-box">
                                <label>نوع المخزون</label>
                                <div class="value">{{ $transfer->goldCaratType?->title ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="info-box">
                                <label>من فرع</label>
                                <div class="value">{{ $transfer->fromBranch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <label>إلى فرع</label>
                                <div class="value">{{ $transfer->toBranch?->name ?? '-' }}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="info-box">
                                <label>حساب التسوية</label>
                                <div class="value">{{ $transfer->account?->name ?? '-' }}</div>
                            </div>
                        </div>
                    </div>

                    @if($transfer->notes)
                        <div class="info-box">
                            <label>ملاحظات</label>
                            <div class="value">{{ $transfer->notes }}</div>
                        </div>
                    @endif

                    <h5 class="mt-3">العيارات المُحَوَّلة</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>العيار</th>
                                    <th>الوزن (المصدر)</th>
                                    <th>العيار الجديد</th>
                                    <th>الوزن (الوجهة)</th>
                                    <th>سعر الجرام</th>
                                    <th>القيمة</th>
                                    <th>ملاحظة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($transfer->lines as $i => $line)
                                    <tr>
                                        <td>{{ $i + 1 }}</td>
                                        <td>{{ $line->fromCarat?->getTranslation('title', 'ar') ?? $line->fromCarat?->title }}</td>
                                        <td>{{ number_format($line->from_weight, 3) }}</td>
                                        <td>{{ $line->toCarat?->getTranslation('title', 'ar') ?? $line->toCarat?->title }}</td>
                                        <td>{{ number_format($line->to_weight, 3) }}</td>
                                        <td>{{ number_format($line->unit_cost, 4) }}</td>
                                        <td>{{ number_format($line->line_value, 2) }}</td>
                                        <td>{{ $line->line_notes ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-light">
                                    <th colspan="2" class="text-right">الإجمالي</th>
                                    <th>{{ number_format($transfer->total_from_weight, 3) }}</th>
                                    <th></th>
                                    <th>{{ number_format($transfer->total_to_weight, 3) }}</th>
                                    <th></th>
                                    <th>{{ number_format($transfer->total_value, 2) }}</th>
                                    <th></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    @php
                        $journalSides = [
                            ['title' => 'قيد فرع المصدر (' . ($transfer->fromBranch?->name ?? '') . ')', 'invoice' => $transfer->outInvoice],
                            ['title' => 'قيد فرع الوجهة (' . ($transfer->toBranch?->name ?? '') . ')', 'invoice' => $transfer->inInvoice],
                        ];
                    @endphp

                    @foreach($journalSides as $side)
                        @if($side['invoice']?->journalEntry)
                            <div class="journal-card">
                                <h6>{{ $side['title'] }}</h6>
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>الحساب</th>
                                            <th>مدين</th>
                                            <th>دائن</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($side['invoice']->journalEntry->documents as $doc)
                                            <tr>
                                                <td>{{ $doc->account?->name ?? '-' }}</td>
                                                <td>{{ number_format($doc->debit, 2) }}</td>
                                                <td>{{ number_format($doc->credit, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
