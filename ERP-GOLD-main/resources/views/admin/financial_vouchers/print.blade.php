<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $voucher->type === 'receipt' ? 'سند قبض' : 'سند صرف' }} - {{ $voucher->bill_number }}</title>
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
            min-height: 148mm;
            margin: 0 auto;
            padding: 15mm;
        }
        .voucher-title {
            text-align: center;
            font-size: 20px;
            font-weight: bold;
            border: 2px solid #111;
            padding: 8px 20px;
            display: inline-block;
            margin-bottom: 20px;
        }
        .header-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
        }
        .field-row {
            display: flex;
            gap: 16px;
            margin-bottom: 10px;
        }
        .field {
            flex: 1;
        }
        .field label {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
            font-size: 12px;
            color: #555;
        }
        .field .value {
            border-bottom: 1px solid #999;
            padding: 4px 0;
            min-height: 26px;
            font-size: 14px;
        }
        .amount-box {
            border: 2px solid #111;
            padding: 10px 16px;
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            margin: 16px 0;
        }
        .notes-row {
            margin: 12px 0;
        }
        .notes-row label {
            font-weight: bold;
            font-size: 12px;
            color: #555;
        }
        .notes-row .value {
            border-bottom: 1px solid #999;
            padding: 4px 0;
            min-height: 30px;
        }
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        .signature-box {
            text-align: center;
            width: 28%;
        }
        .signature-box .sig-label {
            font-weight: bold;
            margin-bottom: 30px;
            font-size: 13px;
        }
        .signature-box .sig-line {
            border-top: 1px solid #555;
            padding-top: 4px;
            font-size: 11px;
            color: #666;
        }
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
            @page { size: A5 portrait; margin: 10mm; }
            .print-controls { display: none !important; }
        }
    </style>
</head>
<body>
<div class="page">
    <div style="text-align:center; margin-bottom:20px;">
        <div class="voucher-title">
            {{ $voucher->type === 'receipt' ? 'سند قبض' : 'سند صرف' }}
        </div>
    </div>

    <div class="header-row">
        <div class="field" style="max-width:200px;">
            <label>رقم السند</label>
            <div class="value">{{ $voucher->bill_number }}</div>
        </div>
        <div class="field" style="max-width:160px;">
            <label>التاريخ</label>
            <div class="value">{{ $voucher->date }}</div>
        </div>
        <div class="field" style="max-width:160px;">
            <label>الفرع</label>
            <div class="value">{{ $voucher->branch?->name ?? '-' }}</div>
        </div>
    </div>

    <div class="field-row">
        <div class="field">
            <label>من حساب</label>
            <div class="value">{{ $voucher->fromAccount?->name ?? '-' }}</div>
        </div>
        <div class="field">
            <label>إلى حساب</label>
            <div class="value">{{ $voucher->toAccount?->name ?? '-' }}</div>
        </div>
    </div>

    <div class="field-row">
        <div class="field" style="max-width:220px;">
            <label>قناة السند</label>
            <div class="value">{{ $voucher->payment_channel_label }}</div>
        </div>
        @if($voucher->bankAccount)
        <div class="field">
            <label>الحساب البنكي</label>
            <div class="value">{{ $voucher->bankAccount->display_name }}</div>
        </div>
        @endif
        @if($voucher->reference_no)
        <div class="field" style="max-width:200px;">
            <label>مرجع العملية</label>
            <div class="value">{{ $voucher->reference_no }}</div>
        </div>
        @endif
    </div>

    <div class="amount-box">
        المبلغ: {{ number_format($voucher->total_amount, 2) }} ريال
    </div>

    <div class="notes-row">
        <label>البيان / الملاحظات</label>
        <div class="value">{{ $voucher->description ?? '' }}</div>
    </div>

    <div class="signatures">
        <div class="signature-box">
            <div class="sig-label">المستلم</div>
            <div class="sig-line">التوقيع</div>
        </div>
        <div class="signature-box">
            <div class="sig-label">المحاسب</div>
            <div class="sig-line">التوقيع</div>
        </div>
        <div class="signature-box">
            <div class="sig-label">المدير</div>
            <div class="sig-line">التوقيع</div>
        </div>
    </div>
</div>

<div class="print-controls">
    <button class="btn-print" onclick="window.print()"><i>🖨</i> طباعة</button>
    <a class="btn-back" onclick="window.close()">إغلاق</a>
</div>

@if(request('auto_print') == '1')
<script>
    window.addEventListener('load', function () { window.print(); });
</script>
@endif
</body>
</html>
