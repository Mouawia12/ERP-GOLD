@php
    $availableOrientations = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->availableOrientations();
    $showOrientationSelector = ($printSettings['format'] ?? 'a4') === 'a5';
@endphp

<style type="text/css">
    .print-control-bar {
        position: fixed;
        left: 18px;
        bottom: 18px;
        width: auto !important;
        height: auto !important;
        z-index: 10000;
        display: flex;
        align-items: flex-end;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 14px;
        background: rgba(17, 24, 39, 0.94);
        box-shadow: 0 14px 30px rgba(15, 23, 42, 0.22);
    }

    .print-control-bar.no-print {
        display: flex !important;
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

    .print-control-button {
        background: #0ea5e9;
        color: #fff !important;
    }

    .print-control-link {
        background: #475569;
        color: #fff !important;
    }

    .print-control-link.is-danger {
        background: #dc2626;
    }

    .print-control-link.is-success {
        background: #16a34a;
    }

    @media print {
        .print-control-bar {
            display: none !important;
        }
    }
</style>

<div class="print-control-bar no-print">
    <div class="print-control-group">
        <label for="paper-size-select" class="print-control-label">مقاس الورقة</label>
        <select id="paper-size-select" class="print-control-select">
            <option value="a4" {{ $printSettings['format'] === 'a4' ? 'selected' : '' }}>A4</option>
            <option value="a5" {{ $printSettings['format'] === 'a5' ? 'selected' : '' }}>A5</option>
        </select>
    </div>

    @if($showOrientationSelector)
        <div class="print-control-group">
            <label for="paper-orientation-select" class="print-control-label">اتجاه الطباعة</label>
            <select id="paper-orientation-select" class="print-control-select">
                @foreach($availableOrientations as $orientationKey => $orientationLabel)
                    <option value="{{ $orientationKey }}" {{ ($printSettings['orientation'] ?? 'portrait') === $orientationKey ? 'selected' : '' }}>
                        {{ $orientationLabel }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <button type="button" id="print-now-button" class="print-control-button">طباعة</button>

    <a href="{{ $backUrl }}" class="print-control-link is-danger">العودة</a>

    @if(! empty($whatsappUrl))
        <a href="{{ $whatsappUrl }}" class="print-control-link is-success">واتساب</a>
    @endif
</div>

<script>
    (function () {
        var sizeSelect = document.getElementById('paper-size-select');
        var orientationSelect = document.getElementById('paper-orientation-select');
        var printButton = document.getElementById('print-now-button');
        var updatePreviewUrl = function () {
            var url = new URL(window.location.href);

            if (sizeSelect) {
                url.searchParams.set('paper', sizeSelect.value);
            }

            if (orientationSelect) {
                url.searchParams.set('orientation', orientationSelect.value);
            } else {
                url.searchParams.delete('orientation');
            }

            window.location.href = url.toString();
        };

        if (sizeSelect) {
            sizeSelect.addEventListener('change', function () {
                updatePreviewUrl();
            });
        }

        if (orientationSelect) {
            orientationSelect.addEventListener('change', function () {
                updatePreviewUrl();
            });
        }

        if (printButton) {
            printButton.addEventListener('click', function () {
                window.print();
            });
        }
    })();
</script>
