@php
    $metrics = $accountMetrics[$account->id] ?? null;
    $balance = $metrics['closing_net'] ?? $account->closingBalance($periodFrom, $periodTo);
    // When a specific branch is selected ($hideEmpty), drop accounts whose whole
    // subtree has no movement — that's how other branches' accounts (0.00) are
    // kept out. Decision uses branch-aware metrics only; if metrics are missing
    // we never hide, to stay safe.
    $subtreeEmpty = $metrics !== null
        && abs((float) ($metrics['closing_debit'] ?? 0)) < 0.005
        && abs((float) ($metrics['closing_credit'] ?? 0)) < 0.005;
    $hideEmpty = ($hideEmpty ?? false) === true;
    $skip = $hideEmpty && $subtreeEmpty;
    $font_percentage = 130 - (($account->level - 1) * 10);
@endphp

@unless ($skip)
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

    @if ($account->childrens && $account->childrens->count())
        @foreach ($account->childrens as $child)
            @include('admin.reports.income_statement.recursive', ['account' => $child, 'hideEmpty' => $hideEmpty])
        @endforeach
    @endif
@endunless
