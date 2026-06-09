@php
    $selectsAll = $selectsAll ?? true;
    $selectedBranchIds = $selectedBranchIds ?? [];
    $branchAssignmentMap = $branchAssignmentMap ?? [];
    $assignedBranchIds = $branchAssignmentMap[$account->id] ?? null;
    // Hidden only when filtering a branch and the account is linked exclusively to other branches.
    $hiddenForBranch = ! $selectsAll && $assignedBranchIds !== null && empty(array_intersect($assignedBranchIds, $selectedBranchIds));

    $metrics = $accountMetrics[$account->id] ?? null;
    $balance = $metrics['closing_net'] ?? $account->closingBalance($periodFrom, $periodTo);
    $font_percentage = 130 - (($account->level - 1) * 10);
@endphp

@unless ($hiddenForBranch)
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
            @include('admin.reports.income_statement.recursive', ['account' => $child])
        @endforeach
    @endif
@endunless
