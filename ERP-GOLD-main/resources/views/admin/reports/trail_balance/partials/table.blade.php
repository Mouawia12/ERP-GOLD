@php
    $formatMoney = fn ($value) => number_format(abs((float) $value), 2);
    $balanceLabel = function ($value) {
        $value = (float) $value;
        if ($value === 0.0) {
            return '';
        }

        return ' / ' . ($value > 0 ? __('main.debit') : __('main.credit'));
    };
    $totals = $totals ?? [
        'opening_debit' => 0,
        'opening_credit' => 0,
        'period_debit' => 0,
        'period_credit' => 0,
        'closing_debit' => 0,
        'closing_credit' => 0,
        'closing_net' => 0,
    ];
@endphp

<table class="print-table trial-balance-table {{ $tableClass ?? '' }}" id="{{ $tableId ?? 'trial-balance-table' }}">
    <thead>
        <tr>
            <th rowspan="2">{{ __('main.account_name') }}</th>
            <th colspan="2">{{ __('main.Before_Debit') }}</th>
            <th colspan="2">{{ __('main.movement') }}</th>
            <th colspan="3">{{ __('الاغلاق') }}</th>
        </tr>
        <tr>
            <th>{{ __('main.Debit') }}</th>
            <th>{{ __('main.Credit') }}</th>
            <th>{{ __('main.Debit') }}</th>
            <th>{{ __('main.Credit') }}</th>
            <th>{{ __('main.Debit') }}</th>
            <th>{{ __('main.Credit') }}</th>
            <th>{{ __('الرصيد') }}</th>
        </tr>
    </thead>
    <tbody>
        @forelse($accounts as $account)
            @php($metrics = $accountMetrics[$account->id] ?? [])
            <tr>
                <td>{{ $account->name . ' - ' . $account->code }}</td>
                <td>{{ $formatMoney($metrics['opening_debit'] ?? 0) }}</td>
                <td>{{ $formatMoney($metrics['opening_credit'] ?? 0) }}</td>
                <td>{{ $formatMoney($metrics['period_debit'] ?? 0) }}</td>
                <td>{{ $formatMoney($metrics['period_credit'] ?? 0) }}</td>
                <td>{{ $formatMoney($metrics['closing_debit'] ?? 0) }}</td>
                <td>{{ $formatMoney($metrics['closing_credit'] ?? 0) }}</td>
                <td>{{ $formatMoney($metrics['closing_net'] ?? 0) }}{{ $balanceLabel($metrics['closing_net'] ?? 0) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="8">لا توجد بيانات مطابقة للفلاتر المحددة</td>
            </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr class="{{ $footerClass ?? '' }}">
            <td>اجمالي الميزان</td>
            <td>{{ $formatMoney($totals['opening_debit'] ?? 0) }}</td>
            <td>{{ $formatMoney($totals['opening_credit'] ?? 0) }}</td>
            <td>{{ $formatMoney($totals['period_debit'] ?? 0) }}</td>
            <td>{{ $formatMoney($totals['period_credit'] ?? 0) }}</td>
            <td>{{ $formatMoney($totals['closing_debit'] ?? 0) }}</td>
            <td>{{ $formatMoney($totals['closing_credit'] ?? 0) }}</td>
            <td>{{ $formatMoney($totals['closing_net'] ?? 0) }}{{ $balanceLabel($totals['closing_net'] ?? 0) }}</td>
        </tr>
    </tfoot>
</table>
