@php
    $printShowHeader = isset($showHeader) ? (bool) $showHeader : (bool) ($printSettings['show_header'] ?? true);
    $printShowFooter = isset($showFooter) ? (bool) $showFooter : (bool) ($printSettings['show_footer'] ?? true);
    $previewNotice = trim((string) ($previewNotice ?? ''));
@endphp

<style type="text/css">
    .print-preview-notice {
        position: fixed;
        left: 18px;
        bottom: 96px;
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
        align-items: flex-end;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(17, 24, 39, 0.94);
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

    .print-control-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 110px;
    }

    .print-control-label {
        margin: 0;
        font-size: 12px;
        color: #e5e7eb !important;
        font-weight: 700;
    }

    .print-control-select,
    .print-control-button,
    .print-control-link {
        height: 38px;
        border: 0;
        border-radius: 10px;
        font-family: 'Almarai', sans-serif !important;
        font-size: 13px;
        font-weight: 700;
    }

    .print-control-select {
        min-width: 110px;
        padding: 0 12px;
        background: #fff;
        color: #111827 !important;
    }

    .print-control-button,
    .print-control-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 16px;
        text-decoration: none !important;
        cursor: pointer;
    }

    .print-control-button { background: #0ea5e9; color: #fff !important; }
    .print-control-link { background: #475569; color: #fff !important; }
    .print-control-link.is-danger { background: #dc2626; }
    .print-control-link.is-success { background: #16a34a; }

    .print-toggle-group {
        display: flex;
        flex-direction: column;
        gap: 4px;
        min-width: 130px;
    }

    .print-toggle-switch {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        height: 32px;
        padding: 0 10px;
        border-radius: 8px;
        background: #1f2937;
        cursor: pointer;
        user-select: none;
    }

    .print-toggle-switch input { display: none; }

    .print-toggle-switch .switch-track {
        width: 30px;
        height: 16px;
        border-radius: 999px;
        background: #4b5563;
        position: relative;
        transition: background 0.18s ease;
    }

    .print-toggle-switch .switch-track::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #fff;
        transition: transform 0.18s ease;
    }

    .print-toggle-switch input:checked + .switch-track { background: #16a34a; }
    .print-toggle-switch input:checked + .switch-track::after { transform: translateX(14px); }

    .print-toggle-switch .switch-text {
        font-size: 12px;
        color: #e5e7eb !important;
        font-weight: 700;
    }

    .print-toggle-switch[data-busy="1"] { opacity: 0.6; pointer-events: none; }

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
    <div class="print-control-group">
        <label for="paper-size-select" class="print-control-label">مقاس الورقة</label>
        <select id="paper-size-select" class="print-control-select">
            <option value="a4" {{ ($printSettings['format'] ?? 'a5') === 'a4' ? 'selected' : '' }}>A4</option>
            <option value="a5" {{ ($printSettings['format'] ?? 'a5') === 'a5' ? 'selected' : '' }}>A5</option>
        </select>
    </div>

    <div class="print-toggle-group">
        <span class="print-control-label">الترويسة والتذييل</span>
        <label class="print-toggle-switch" data-flag="show_header">
            <input type="checkbox" {{ $printShowHeader ? 'checked' : '' }}>
            <span class="switch-track"></span>
            <span class="switch-text">طباعة الترويسة</span>
        </label>
        <label class="print-toggle-switch" data-flag="show_footer">
            <input type="checkbox" {{ $printShowFooter ? 'checked' : '' }}>
            <span class="switch-track"></span>
            <span class="switch-text">طباعة التذييل</span>
        </label>
    </div>

    <button type="button" id="print-now-button" class="print-control-button">طباعة</button>

    <a href="{{ $backUrl }}" class="print-control-link is-danger">العودة</a>

    @if(! empty($whatsappUrl))
        <a href="{{ $whatsappUrl }}" class="print-control-link is-success">واتساب</a>
    @endif
</div>

<script>
    (function () {
        var sizeSelect = document.getElementById('paper-size-select');
        var printButton = document.getElementById('print-now-button');

        var setPrintMode = function (isPrinting) {
            if (document.body) {
                document.body.classList.toggle('print-mode-active', isPrinting);
            }
        };

        if (sizeSelect) {
            sizeSelect.addEventListener('change', function () {
                var url = new URL(window.location.href);
                url.searchParams.set('paper', sizeSelect.value);
                url.searchParams.delete('orientation');
                window.location.href = url.toString();
            });
        }

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

        var toggleSwitches = document.querySelectorAll('.print-toggle-switch');
        toggleSwitches.forEach(function (toggle) {
            var input = toggle.querySelector('input[type="checkbox"]');
            if (! input) return;

            input.addEventListener('change', function () {
                var flag = toggle.getAttribute('data-flag');
                var enabled = input.checked ? 1 : 0;
                toggle.setAttribute('data-busy', '1');

                fetch('{{ route("admin.system-settings.invoice-print.toggle-flag") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ flag: flag, enabled: enabled }),
                }).then(function (response) {
                    if (! response.ok) throw new Error('toggle failed');
                    return response.json();
                }).then(function () {
                    window.location.reload();
                }).catch(function () {
                    input.checked = ! input.checked;
                    toggle.removeAttribute('data-busy');
                    alert('تعذّر حفظ الإعداد، حاول مرة أخرى.');
                });
            });
        });
    })();
</script>
