@extends('admin.layouts.master')

@section('content')
@php
    $partyLabel  = $customer->type === 'customer' ? 'عميل' : 'مورد';
    $branch      = auth()->user()?->branch;
    $subscriber  = $branch?->subscriber;
    $fromDate    = $filters['from_date'] ?? null;
    $toDate      = $filters['to_date'] ?? null;
    $period      = 'الفترة : ' . ($fromDate ?? 'من البداية') . ' -- ' . ($toDate ?? 'حتى اليوم');

    // Use all system carats so columns are always complete
    $usedCarats = $carats->pluck('title');

    // Determine money debit/credit per transaction type
    $debitTypes  = ['sale', 'purchase_return', 'manufacturing_order', 'manufacturing_receipt'];
    $creditTypes = ['purchase', 'sale_return', 'receipt', 'payment', 'manufacturing_return', 'manufacturing_loss_settlement'];
@endphp

<style>
    body { direction: rtl; }
    .report-table th, .report-table td { text-align: center; vertical-align: middle; font-size: 12px; }
    @media print {
        @page { size: A4 landscape; margin: 8mm; }
        .no-print { display: none !important; }
        .report-table th, .report-table td { font-size: 10px; }
    }
</style>

<div class="row row-sm">
    <div class="col-12">
        <div class="card">
            <div class="card-body px-0 pt-0 pb-2">

                {{-- Filter Form (no-print) --}}
                <div class="card shadow mb-3 no-print">
                    <div class="card-body py-3">
                        <form method="GET" action="{{ route('customers.report', $customer->id) }}">
                            <div class="row align-items-end">
                                <div class="col-md-2">
                                    <label>من تاريخ</label>
                                    <input type="date" name="from_date" class="form-control" value="{{ $filters['from_date'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label>إلى تاريخ</label>
                                    <input type="date" name="to_date" class="form-control" value="{{ $filters['to_date'] ?? '' }}">
                                </div>
                                <div class="col-md-2">
                                    <label>الفرع</label>
                                    <select name="branch_id" class="form-control">
                                        <option value="">الكل</option>
                                        @foreach($branches as $b)
                                            <option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? null) == $b->id)>{{ $b->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>نوع العملية</label>
                                    <select name="operation_type" class="form-control">
                                        <option value="">الكل</option>
                                        <option value="sale" @selected(($filters['operation_type'] ?? null) === 'sale')>بيع</option>
                                        <option value="sale_return" @selected(($filters['operation_type'] ?? null) === 'sale_return')>مرتجع بيع</option>
                                        <option value="purchase" @selected(($filters['operation_type'] ?? null) === 'purchase')>شراء</option>
                                        <option value="purchase_return" @selected(($filters['operation_type'] ?? null) === 'purchase_return')>مرتجع شراء</option>
                                        <option value="receipt" @selected(($filters['operation_type'] ?? null) === 'receipt')>سند قبض</option>
                                        <option value="payment" @selected(($filters['operation_type'] ?? null) === 'payment')>سند صرف</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>رقم الفاتورة</label>
                                    <input type="text" name="invoice_number" class="form-control"
                                           value="{{ $filters['invoice_number'] ?? '' }}" placeholder="رقم الفاتورة">
                                </div>
                                <div class="col-md-2 mt-3 mt-md-0">
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fa fa-search"></i> بحث
                                    </button>
                                    <a href="{{ route('customers.report', $customer->id) }}"
                                       class="btn btn-outline-secondary btn-block mt-1">إعادة تعيين</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Print Header --}}
                <div class="card shadow mb-3">
                    <div class="card-header py-3" style="direction: rtl; border-bottom: solid 1px #ccc;">
                        <div class="row" style="direction: ltr;">
                            {{-- Logo --}}
                            <div class="col-3 text-left">
                                @php
                                    $logoUrl = null;
                                    try {
                                        $logoUrl = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings()->logoUrl ?? null;
                                    } catch(\Throwable $e) {}
                                @endphp
                                @if($logoUrl)
                                    <img src="{{ $logoUrl }}" height="70" alt="logo">
                                @endif
                            </div>
                            {{-- Title --}}
                            <div class="col-6 text-center">
                                <h4 class="alert alert-primary mb-1">تقرير حركة حساب تفصيلي</h4>
                                <h5>[ {{ $customer->name }} ]</h5>
                                @if($branch)
                                    <h6>[ {{ $branch->name }} ]</h6>
                                @endif
                                <h6>[ {{ $period }} ]</h6>
                            </div>
                            {{-- Company Info + Actions --}}
                            <div class="col-3 text-right">
                                <div>
                                    @if($subscriber?->name)
                                        <strong>{{ $subscriber->name }}</strong><br>
                                    @elseif($branch?->name)
                                        <strong>{{ $branch->name }}</strong><br>
                                    @endif
                                    @if($branch?->tax_number)
                                        س.ت : {{ $branch->tax_number }}<br>
                                    @endif
                                </div>
                                <div class="mt-2 no-print">
                                    <button class="btn btn-primary btn-sm" onclick="window.print()">
                                        <i class="fa fa-print"></i> طباعة
                                    </button>
                                    <a href="{{ route('customers', ['type' => $customer->type]) }}"
                                       class="btn btn-outline-secondary btn-sm mt-1 d-block">رجوع</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Report Table --}}
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered report-table w-100" style="direction: rtl;">
                            <thead class="thead-light">
                                <tr>
                                    <th rowspan="2">#</th>
                                    <th rowspan="2">تاريخ العملية</th>
                                    <th rowspan="2">السند</th>
                                    <th rowspan="2">رقمه</th>
                                    <th colspan="2">النقدية</th>
                                    @if($usedCarats->isNotEmpty())
                                    <th colspan="{{ $usedCarats->count() * 2 }}">الذهب</th>
                                    @endif
                                </tr>
                                <tr>
                                    <th>مبلغ مدين</th>
                                    <th>مبلغ دائن</th>
                                    @foreach($usedCarats as $carat)
                                        <th>{{ $carat }} مدين</th>
                                        <th>{{ $carat }} دائن</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $total_money_debit  = 0;
                                    $total_money_credit = 0;
                                    $carat_totals = [];
                                    foreach ($usedCarats as $c) {
                                        $carat_totals[$c] = ['debit' => 0, 'credit' => 0];
                                    }
                                @endphp

                                {{-- Opening balance row --}}
                                <tr style="background: #f8f9fa; font-weight: bold;">
                                    <td>1</td>
                                    <td>--</td>
                                    <td>رصيد اول المدة</td>
                                    <td></td>
                                    <td>0</td>
                                    <td>0</td>
                                    @foreach($usedCarats as $carat)
                                        <td>0</td><td>0</td>
                                    @endforeach
                                </tr>

                                @forelse($transactions as $transaction)
                                    @php
                                        $isDebit = in_array($transaction['type'], $debitTypes);
                                        $moneyDebit  = $isDebit ? $transaction['net_total'] : 0;
                                        $moneyCredit = !$isDebit ? $transaction['net_total'] : 0;
                                        $total_money_debit  += $moneyDebit;
                                        $total_money_credit += $moneyCredit;
                                        $caratMap = collect($transaction['carat_summary'])->keyBy('carat_title');
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration + 1 }}</td>
                                        <td>{{ $transaction['date'] }}</td>
                                        <td>{{ $transaction['operation_label'] }}</td>
                                        <td>{{ $transaction['bill_number'] }}</td>
                                        <td>{{ $moneyDebit > 0 ? number_format($moneyDebit, 2) : 0 }}</td>
                                        <td>{{ $moneyCredit > 0 ? number_format($moneyCredit, 2) : 0 }}</td>
                                        @foreach($usedCarats as $carat)
                                            @php
                                                $cd = $caratMap->get($carat);
                                                $inW  = $cd ? $cd['in_weight']  : 0;
                                                $outW = $cd ? $cd['out_weight'] : 0;
                                                $carat_totals[$carat]['debit']  += $inW;
                                                $carat_totals[$carat]['credit'] += $outW;
                                            @endphp
                                            <td>{{ $inW  > 0 ? number_format($inW,  3) : 0 }}</td>
                                            <td>{{ $outW > 0 ? number_format($outW, 3) : 0 }}</td>
                                        @endforeach
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ 6 + $usedCarats->count() * 2 }}" class="text-center text-muted">
                                            لا توجد حركات ضمن هذه الفترة.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr style="background: antiquewhite; font-weight: bold;">
                                    <td colspan="4" class="text-center">الإجمالي</td>
                                    <td>{{ number_format($total_money_debit, 2) }}</td>
                                    <td>{{ number_format($total_money_credit, 2) }}</td>
                                    @foreach($usedCarats as $carat)
                                        <td>{{ number_format($carat_totals[$carat]['debit'],  3) }}</td>
                                        <td>{{ number_format($carat_totals[$carat]['credit'], 3) }}</td>
                                    @endforeach
                                </tr>
                                <tr style="background: lightblue; font-weight: bold;">
                                    <td colspan="4" class="text-center">الرصيد</td>
                                    <td colspan="2">{{ number_format(abs($total_money_debit - $total_money_credit), 2) }}
                                        ({{ $total_money_debit >= $total_money_credit ? 'مدين' : 'دائن' }})
                                    </td>
                                    @foreach($usedCarats as $carat)
                                        @php $net = $carat_totals[$carat]['debit'] - $carat_totals[$carat]['credit']; @endphp
                                        <td colspan="2">{{ number_format(abs($net), 3) }}
                                            ({{ $net >= 0 ? 'مدين' : 'دائن' }})
                                        </td>
                                    @endforeach
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

@endsection

@section('js')
<script>
    document.title = "كشف {{ $partyLabel }} - {{ $customer->name }}";
</script>
@endsection
