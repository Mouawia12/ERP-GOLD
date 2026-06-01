@php
    $previewNotice = trim((string) ($previewNotice ?? ''));
    $curFormat = $printSettings['format'] ?? 'a5';
    $curWith = (bool) ($printSettings['show_header'] ?? true); // "مع ترويسة" إذا الترويسة ظاهرة
    // الخيار الفعّال حالياً من الأربعة
    $activeKey = $curFormat . ($curWith ? '-with' : '-without');

    $printOptions = [
        ['key' => 'a5-with',    'paper' => 'a5', 'flag' => 1, 'label' => 'A5 — مع ترويسة وتذييل'],
        ['key' => 'a5-without', 'paper' => 'a5', 'flag' => 0, 'label' => 'A5 — بدون (ورق الشركة)'],
        ['key' => 'a4-with',    'paper' => 'a4', 'flag' => 1, 'label' => 'A4 — مع ترويسة وتذييل'],
        ['key' => 'a4-without', 'paper' => 'a4', 'flag' => 0, 'label' => 'A4 — بدون (ورق الشركة)'],
    ];
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

    .print-options-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
    }

    .print-option {
        height: 34px;
        min-width: 168px;
        border: 1px solid #374151;
        border-radius: 9px;
        background: #1f2937;
        color: #e5e7eb !important;
        font-family: 'Almarai', sans-serif !important;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        padding: 0 12px;
        text-align: center;
        transition: background 0.15s ease, border-color 0.15s ease;
    }

    .print-option:hover { background: #374151; }

    .print-option.is-active {
        background: #0ea5e9;
        border-color: #0ea5e9;
        color: #fff !important;
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

    .print-control-sep {
        width: 1px;
        align-self: stretch;
        background: #374151;
        margin: 2px 0;
    }

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
    <div class="print-options-grid">
        @foreach($printOptions as $opt)
            <button type="button"
                class="print-option {{ $activeKey === $opt['key'] ? 'is-active' : '' }}"
                data-paper="{{ $opt['paper'] }}"
                data-flag="{{ $opt['flag'] }}">
                {{ $opt['label'] }}
            </button>
        @endforeach
    </div>

    <div class="print-control-sep"></div>

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

        // الأزرار الأربعة: تبديل المقاس + مع/بدون ترويسة عبر الرابط (بدون حفظ دائم)
        document.querySelectorAll('.print-option').forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (btn.classList.contains('is-active')) {
                    // الخيار مفعّل أصلاً → اطبع مباشرة
                    setPrintMode(true);
                    window.print();
                    return;
                }
                var url = new URL(window.location.href);
                url.searchParams.set('paper', btn.getAttribute('data-paper'));
                url.searchParams.set('header', btn.getAttribute('data-flag'));
                url.searchParams.set('footer', btn.getAttribute('data-flag'));
                url.searchParams.delete('orientation');
                window.location.href = url.toString();
            });
        });

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
