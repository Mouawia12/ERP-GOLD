<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>قيد يومية - {{ $journal->serial ?? $journal->id }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Segoe UI', Tahoma, Arial, sans-serif;
            direction: rtl;
            background: #fff;
            color: #111;
            font-size: 14px;
        }
        .page {
            width: 210mm;
            margin: 0 auto;
            padding: 15mm;
        }
        h2 {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 16px;
            border-bottom: 2px solid #333;
            padding-bottom: 8px;
        }
        .meta {
            display: flex;
            gap: 24px;
            margin-bottom: 16px;
            font-size: 13px;
        }
        .meta span { font-weight: bold; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }
        th {
            background: #1e3a5f;
            color: #fff;
            padding: 8px;
            font-size: 13px;
            text-align: center;
        }
        td {
            border: 1px solid #ccc;
            padding: 7px 10px;
            text-align: center;
            font-size: 13px;
        }
        tr:nth-child(even) td { background: #f8f9fa; }
        .total-row td {
            background: #1e3a5f !important;
            color: #fff;
            font-weight: bold;
        }
        .notes-section {
            margin-top: 12px;
            font-size: 13px;
        }
        .notes-section .label { font-weight: bold; }
        .print-controls {
            position: fixed;
            bottom: 18px;
            left: 18px;
            display: flex;
            gap: 8px;
        }
        .print-controls button, .print-controls a {
            padding: 8px 18px;
            border-radius: 8px;
            border: 0;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-print { background: #0ea5e9; color: #fff; }
        .btn-back  { background: #dc2626; color: #fff; }
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            .print-controls { display: none !important; }
        }
    </style>
</head>
<body>
<div class="page">
    <h2>قيد يومية</h2>

    <div class="meta">
        <div>رقم القيد: <span>{{ $journal->serial ?? $journal->id }}</span></div>
        <div>التاريخ: <span>{{ $journal->journal_date }}</span></div>
        @if($journal->branch)
        <div>الفرع: <span>{{ $journal->branch->name }}</span></div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>كود الحساب</th>
                <th>اسم الحساب</th>
                <th>مدين</th>
                <th>دائن</th>
                <th>بيان</th>
            </tr>
        </thead>
        <tbody>
            <?php $debit = 0; $credit = 0; $i = 1; ?>
            @foreach($journal->documents as $document)
            <tr>
                <td>{{ $i++ }}</td>
                <td>{{ $document->account?->code ?? '-' }}</td>
                <td>{{ $document->account?->name ?? '-' }}</td>
                <td>{{ $document->debit > 0 ? number_format($document->debit, 2) : '-' }}</td>
                <td>{{ $document->credit > 0 ? number_format($document->credit, 2) : '-' }}</td>
                <td>{{ $document->notes ?? '' }}</td>
            </tr>
            <?php $debit += $document->debit; $credit += $document->credit; ?>
            @endforeach
            <tr class="total-row">
                <td colspan="3">الإجمالي</td>
                <td>{{ number_format($debit, 2) }}</td>
                <td>{{ number_format($credit, 2) }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    @if($journal->notes)
    <div class="notes-section">
        <span class="label">الملاحظات:</span> {{ $journal->notes }}
    </div>
    @endif
</div>

<div class="print-controls">
    <button class="btn-print" onclick="window.print()">🖨 طباعة</button>
    <a class="btn-back" onclick="window.close()">إغلاق</a>
</div>

@if(request('auto_print') == '1')
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif
</body>
</html>
