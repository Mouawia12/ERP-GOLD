@php
    $metrics = $accountMetrics[$account->id] ?? null;
    $balance = $metrics['closing_net'] ?? $account->closingBalance($periodFrom, $periodTo);
    $font_percentage = 130 - (($account->level - 1) * 10);
    $accountLevel = $accountLevel ?? null;
    $shouldDescend = $accountLevel === null || $account->level < $accountLevel;
@endphp

<tr>
    <td class="text-right"
        style="padding-right: {{$account->level}}rem !important; font-size:{{$font_percentage}}% !important">
        {{ $account->name }}
    </td>
    <td>{{ number_format($metrics['closing_debit'] ?? $account->closingBalance($periodFrom, $periodTo, 'debit'), 2) }}</td>
    <td>{{ number_format($metrics['closing_credit'] ?? $account->closingBalance($periodFrom, $periodTo, 'credit'), 2) }}</td>
    <td>
        {{ number_format(abs($balance), 2) }}
        {{ $balance != 0 ? ' / ' . ($balance > 0 ? __('main.debit') : __('main.credit')) : '' }}
    </td>
</tr>

@if ($shouldDescend && $account->childrens && $account->childrens->count())
    @foreach ($account->childrens as $child)
        @include('admin.reports.balance_sheet.recursive', ['account' => $child, 'accountLevel' => $accountLevel])
    @endforeach
@endif
