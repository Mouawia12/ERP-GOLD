@php
    if (empty($bgImageUrl)) {
        return;
    }
    $bgScale         = number_format((float)($bgScale         ?? 1.0), 2);
    $bgOffsetX       = number_format((float)($bgOffsetX       ?? 0.0), 1);
    $bgContentTop    = number_format((float)($bgContentTop    ?? 0.0), 1);
    $bgContentBottom = number_format((float)($bgContentBottom ?? 0.0), 1);
    $bgContentWidth  = number_format(max(50.0, min(100.0, (float)($bgContentWidth ?? 100.0))), 1);
    $bgHideHeader    = (bool)($bgHideHeader ?? false);
    $bgPaperSize     = app(\App\Services\Invoices\InvoiceBackgroundService::class)->currentPaperSize();
    [$paperW, $paperH] = $bgPaperSize === 'a5' ? ['148mm', '210mm'] : ['210mm', '297mm'];
@endphp
@if(! empty($bgImageUrl))
<style>
    :root {
        --invoice-bg-scale:          {{ $bgScale }};
        --invoice-bg-offset-x:       {{ $bgOffsetX }}%;
        --invoice-bg-content-top:    {{ $bgContentTop }}mm;
        --invoice-bg-content-bottom: {{ $bgContentBottom }}mm;
        --invoice-bg-content-width:  {{ $bgContentWidth }}%;
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
            width:      {{ $paperW }} !important;
            height:     {{ $paperH }} !important;
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
            background-size:     calc(100% * var(--invoice-bg-scale)) auto !important;
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
            min-height: 267mm !important;
        }
    }

    @if($bgHideHeader)
    .invoice-header,
    .page-footer { display: none !important; }
    @endif
</style>

<script>
(function () {
    'use strict';
    if (typeof window === 'undefined') return;

    function fitToPage() {
        if (window.matchMedia && window.matchMedia('print').matches) return;

        var page = document.querySelector('.page');
        if (!page) return;

        page.style.transform    = '';
        page.style.marginBottom = '';

        var body   = document.body;
        var bodyH  = body.getBoundingClientRect().height;
        var cs     = getComputedStyle(body);
        var padTop = parseFloat(cs.paddingTop)    || 0;
        var padBot = parseFloat(cs.paddingBottom) || 0;
        var availH = bodyH - padTop - padBot;

        if (availH <= 10) return;

        var pageH = page.scrollHeight;
        if (pageH <= availH) return;

        var ratio = availH / pageH;
        page.style.transform    = 'scale(' + ratio + ')';
        page.style.marginBottom = '-' + Math.ceil(pageH * (1 - ratio)) + 'px';
    }

    if (document.readyState === 'complete') {
        fitToPage();
    } else {
        window.addEventListener('load', fitToPage);
    }

    window.addEventListener('beforeprint', function () {
        var page = document.querySelector('.page');
        if (!page) return;
        page.style.transform    = '';
        page.style.marginBottom = '';
    });

    window.addEventListener('afterprint', fitToPage);
})();
</script>
@endif
