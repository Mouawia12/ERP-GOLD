@php
    $metrics = $accountMetrics[$account->id] ?? null;
    $balance = $metrics['closing_net'] ?? $account->closingBalance($periodFrom, $periodTo);
    $font_percentage = 130 - (($account->level - 1) * 10);
    $accountLevel = $accountLevel ?? null;
    $shouldDescend = $accountLevel === null || $account->level < $accountLevel;

    // عند تحديد فرع، يُسقَط الحساب الذي لا أثر له في ذلك الفرع هو وكل ما تحته —
    // بهذا تختفي حسابات الفروع الأخرى بدل أن تظهر بأصفار. الأقسام الرئيسية تبقى
    // دائمًا حتى لا تظهر ميزانية ناقصة ركنًا من أركانها. القرار مبني على أرقام
    // الفرع وحده؛ وإن غابت الأرقام لا نخفي شيئًا.
    // «لا أثر له» = لا مدين ولا دائن، لا صافيًا صفرًا. فحساب مرّت عليه حركة
    // الفرع ثم قابلها ما يعادلها هو حساب عامل في هذا الفرع وإن استوى طرفاه،
    // وإخفاؤه يترك قسمًا كاملًا بلا حسابات تحته.
    $hideEmpty = ($hideEmpty ?? false) === true;
    $subtreeEmpty = $metrics !== null
        && abs((float) ($metrics['closing_debit'] ?? 0)) < 0.005
        && abs((float) ($metrics['closing_credit'] ?? 0)) < 0.005;
    $skip = $hideEmpty && $subtreeEmpty && (int) $account->level > 1;
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

@if ($shouldDescend && $account->childrens && $account->childrens->count())
    @foreach ($account->childrens as $child)
        @include('admin.reports.balance_sheet.recursive', ['account' => $child, 'accountLevel' => $accountLevel, 'hideEmpty' => $hideEmpty])
    @endforeach
@endif
@endunless
