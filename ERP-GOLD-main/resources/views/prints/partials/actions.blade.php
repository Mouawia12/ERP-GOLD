<div class="print-actions no-print" dir="rtl">
    <button type="button" class="print-actions__button" onclick="window.print()">طباعة</button>

    @if(! empty($pdfUrl))
        <a class="print-actions__link print-actions__link--pdf" href="{{ $pdfUrl }}">حفظ PDF</a>
    @endif

    @if(! empty($backUrl))
        <a class="print-actions__link" href="{{ $backUrl }}">رجوع</a>
    @else
        <button type="button" class="print-actions__link" onclick="history.back()">رجوع</button>
    @endif

    <button type="button" class="print-actions__link print-actions__link--danger" onclick="erpClosePrintWindow()">إغلاق</button>
</div>

<script>
    function erpClosePrintWindow() {
        if (window.opener || window.history.length <= 1) {
            window.close();
            return;
        }

        window.history.back();
    }
</script>
