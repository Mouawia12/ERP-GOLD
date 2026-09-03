@php
    $metrics = $accountMetrics[$account->id] ?? null;
    $balance = $metrics['closing_net'] ?? $account->closingBalance($periodFrom, $periodTo);
    // عند اختيار فرع ($hideEmpty) يُسقَط الحساب الذي لا أثر له في ذلك الفرع هو
    // وكل ما تحته، فتبقى حسابات الفروع الأخرى خارج القائمة. «لا أثر له» = لا
    // مدين ولا دائن، لا صافيًا صفرًا: حساب استوى طرفاه عامل في هذا الفرع.
    // القرار مبني على أرقام الفرع وحدها؛ وإن غابت الأرقام لا نخفي شيئًا.
    $subtreeEmpty = $metrics !== null
        && abs((float) ($metrics['closing_debit'] ?? 0)) < 0.005
        && abs((float) ($metrics['closing_credit'] ?? 0)) < 0.005;
    $hideEmpty = ($hideEmpty ?? false) === true;
    $skip = $hideEmpty && $subtreeEmpty;
    $font_percentage = 130 - (($account->level - 1) * 10);

    // «مستوى الحساب» يوقف النزول عند العمق المطلوب، فتُقرأ القائمة مجمّعة.
    $accountLevel = $accountLevel ?? null;
    $shouldDescend = $accountLevel === null || $account->level < $accountLevel;
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
            @include('admin.reports.income_statement.recursive', ['account' => $child, 'hideEmpty' => $hideEmpty, 'accountLevel' => $accountLevel])
        @endforeach
    @endif
@endunless
