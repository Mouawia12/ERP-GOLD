@php
    if (empty($bgImageUrl)) {
        return;
    }
    $bgScale         = number_format((float)($bgScale         ?? 1.0), 2);
    $bgOffsetX       = number_format((float)($bgOffsetX       ?? 0.0), 1);
    $bgContentTop    = number_format((float)($bgContentTop    ?? 0.0), 1);
    $bgContentBottom = number_format((float)($bgContentBottom ?? 0.0), 1);
    $bgContentWidth  = number_format(max(50.0, min(100.0, (float)($bgContentWidth ?? 100.0))), 1);
    $bgContentScale  = number_format(max(0.5, min(1.5, (float)($bgContentScale ?? 1.0))), 2);
    $bgHideHeader    = (bool)($bgHideHeader ?? false);
    $bgHideFooter    = (bool)($bgHideFooter ?? $bgHideHeader);
    $bgService = app(\App\Services\Invoices\InvoiceBackgroundService::class);
    $bgPaperSize = $bgPaperSize ?? $bgService->currentPaperSize();
    $bgPaperOrientation = $bgPaperOrientation ?? $bgService->currentPaperOrientation();
    $bgRenderMode = $bgRenderMode ?? $bgService->currentRenderMode();
    [$paperW, $paperH] = $bgPaperSize === 'a5' ? [148, 210] : [210, 297];
    if ($bgPaperOrientation === 'landscape') {
        [$paperW, $paperH] = [$paperH, $paperW];
    }
    $bgSizeValue = $bgRenderMode === 'full_page'
        ? 'calc(100% * var(--invoice-bg-scale)) calc(100% * var(--invoice-bg-scale))'
        : 'calc(100% * var(--invoice-bg-scale)) auto';
@endphp
@if(! empty($bgImageUrl))
    <style>
    :root {
        --invoice-bg-scale:          {{ $bgScale }};
        --invoice-bg-offset-x:       {{ $bgOffsetX }}%;
        --invoice-bg-content-top:    {{ $bgContentTop }}mm;
        --invoice-bg-content-bottom: {{ $bgContentBottom }}mm;
        --invoice-bg-content-width:  {{ $bgContentWidth }}%;
        --invoice-bg-content-scale:  {{ $bgContentScale }};
        --invoice-bg-paper-width:    {{ $paperW }}mm;
        --invoice-bg-paper-height:   {{ $paperH }}mm;
    }

    /* ─────────────────────────────────────────────────────────────────
       SCREEN ONLY — paper-frame preview with background reference image
       The user aligns invoice content against the letterhead image here.
    ───────────────────────────────────────────────────────────────── */
    @media screen {
        html {
            background: #3f4550 !important;
            min-height: 100vh !important;
            overflow-y: auto !important;
        }

        body {
            width:      var(--invoice-bg-paper-width) !important;
            height:     var(--invoice-bg-paper-height) !important;
            margin:     28px auto 60px !important;
            padding-top:    var(--invoice-bg-content-top)    !important;
            padding-bottom: var(--invoice-bg-content-bottom) !important;
            background: #fff !important;
            box-shadow: 0 6px 40px rgba(0,0,0,.55) !important;
            position:   relative !important;
            overflow:   visible !important;
        }

        /* letterhead reference image — screen preview only */
        body::before {
            content:    '' !important;
            position:   absolute !important;
            top: 0 !important; left: var(--invoice-bg-offset-x) !important;
            width: 100% !important; height: 100% !important;
            background-image:    url('{{ $bgImageUrl }}') !important;
            background-size:     {{ $bgSizeValue }} !important;
            background-repeat:   no-repeat !important;
            background-position: center top !important;
            z-index:        0 !important;
            pointer-events: none !important;
            opacity:        .92 !important;
        }

        .page {
            position:        relative !important;
            z-index:         1 !important;
            background:      transparent !important;
            box-shadow:      none !important;
            transform-origin: top center !important;
            width:     var(--invoice-bg-content-width) !important;
            max-width: var(--invoice-bg-content-width) !important;
            min-height: calc(var(--invoice-bg-paper-height) - var(--invoice-bg-content-top) - var(--invoice-bg-content-bottom)) !important;
        }

        .page-content {
            zoom: var(--invoice-bg-content-scale) !important;
        }

        body.invoice-paper-ready {
            --safe-print-offset-top: 0mm !important;
            --safe-print-width: 100% !important;
            --safe-print-height: calc(var(--invoice-bg-paper-height) - var(--invoice-bg-content-top) - var(--invoice-bg-content-bottom)) !important;
        }

        body.invoice-paper-ready .page-content {
            margin-top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: var(--safe-print-height) !important;
        }

        body.invoice-paper-ready .invoice-shell {
            width: 100% !important;
            max-width: 100% !important;
        }

        /* faint red line marking the content-bottom boundary */
        body::after {
            content:  '' !important;
            position: absolute !important;
            bottom:   var(--invoice-bg-content-bottom) !important;
            left: 3% !important; right: 3% !important;
            height: 1px !important;
            background: rgba(239,68,68,.3) !important;
            z-index: 2 !important;
            pointer-events: none !important;
        }
    }

    /* ─────────────────────────────────────────────────────────────────
       PRINT — NO background image.
       The physical pre-printed letterhead paper is already in the printer.
       Only positioning (padding) and width are applied so content lands
       in the correct blank area of the physical paper.
    ───────────────────────────────────────────────────────────────── */
    @media print {
        /* no body::before — background image must NOT print */
        body {
            padding-top:    var(--invoice-bg-content-top)    !important;
            padding-bottom: var(--invoice-bg-content-bottom) !important;
        }

        .page {
            width:     var(--invoice-bg-content-width) !important;
            max-width: var(--invoice-bg-content-width) !important;
            transform:  none !important;
            height:     auto !important;
            min-height: calc(var(--invoice-bg-paper-height) - var(--invoice-bg-content-top) - var(--invoice-bg-content-bottom)) !important;
        }

        .page-content {
            zoom: var(--invoice-bg-content-scale) !important;
        }

        body.invoice-paper-ready {
            --safe-print-offset-top: 0mm !important;
            --safe-print-width: 100% !important;
            --safe-print-height: calc(var(--invoice-bg-paper-height) - var(--invoice-bg-content-top) - var(--invoice-bg-content-bottom)) !important;
        }

        body.invoice-paper-ready .page-content {
            margin-top: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            min-height: var(--safe-print-height) !important;
        }

        body.invoice-paper-ready .invoice-shell {
            width: 100% !important;
            max-width: 100% !important;
        }
    }

    @if($bgHideHeader)
    .invoice-header { display: none !important; }
    @endif
    @if($bgHideFooter)
    .page-footer { display: none !important; }
    @endif
</style>

    <script>
    (function () {
        'use strict';
        if (typeof window === 'undefined') return;

        window.addEventListener('beforeprint', function () {
            var page = document.querySelector('.page');
            if (!page) return;
            page.style.transform = '';
            page.style.marginBottom = '';
        });
    })();
    </script>
@endif
