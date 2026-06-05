@php
    // المقاس ومع/بدون ترويسة يُضبطان من صفحة "إعدادات طباعة الفواتير" — لا أزرار هنا
    $previewNotice = trim((string) ($previewNotice ?? ''));
@endphp

<style type="text/css">
    .print-preview-notice {
        position: fixed;
        left: 18px;
        bottom: 150px;
        z-index: 10000;
        width: min(460px, calc(100vw - 36px));
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(30, 41, 59, 0.96);
        color: #fff !important;
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.2);
        font-family: 'Almarai', sans-serif !important;
        font-size: 13px;
        line-height: 1.7;
    }

    .print-control-bar {
        position: fixed;
        left: 18px;
        bottom: 18px;
        z-index: 10000;
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(17, 24, 39, 0.95);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
    }

    @media screen {
        .print-control-bar.no-print { display: flex !important; }
    }

    body.print-mode-active .print-control-bar,
    body.print-mode-active .print-preview-notice {
        display: none !important;
        visibility: hidden !important;
        opacity: 0 !important;
    }

    .print-control-button,
    .print-control-link {
        height: 40px;
        border: 0;
        border-radius: 10px;
        font-family: 'Almarai', sans-serif !important;
        font-size: 14px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 18px;
        text-decoration: none !important;
        cursor: pointer;
    }

    .print-control-button { background: #16a34a; color: #fff !important; }
    .print-control-link { background: #475569; color: #fff !important; }
    .print-control-link.is-danger { background: #dc2626; }
    .print-control-link.is-success { background: #16a34a; }

    @media print {
        .no-print,
        .print-preview-notice,
        .print-control-bar {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
    }
</style>

@if($previewNotice !== '')
    <div class="print-preview-notice no-print">{{ $previewNotice }}</div>
@endif

<div class="print-control-bar no-print">
    <button type="button" id="print-now-button" class="print-control-button">🖨 طباعة</button>

    <a href="{{ $backUrl }}" class="print-control-link is-danger">العودة</a>

    @if(! empty($whatsappUrl))
        <a href="{{ $whatsappUrl }}" class="print-control-link is-success">واتساب</a>
    @endif
</div>

<script>
    (function () {
        var printButton = document.getElementById('print-now-button');

        var setPrintMode = function (isPrinting) {
            if (document.body) {
                document.body.classList.toggle('print-mode-active', isPrinting);
            }
        };

        window.addEventListener('beforeprint', function () { setPrintMode(true); });
        window.addEventListener('afterprint', function () { setPrintMode(false); });

        if (printButton) {
            printButton.addEventListener('click', function () {
                setPrintMode(true);
                window.print();
            });
        }

        if (window.location.search.indexOf('auto_print=1') !== -1) {
            window.addEventListener('load', function () { window.print(); });
        }
    })();
</script>
