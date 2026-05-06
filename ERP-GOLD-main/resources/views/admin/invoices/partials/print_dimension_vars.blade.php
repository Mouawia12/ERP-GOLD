@php
    $printSettings = $printSettings ?? app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
    $dimensionFormat = $dimensionFormat ?? ($printSettings['format'] ?? 'a4');
    $dim = $printSettings['dimensions'][$dimensionFormat] ?? [];
    $fontScale = (float) ($printSettings['dimensions']['font_scale'] ?? 1.0);
    $marginTop = $dim['margin_top'] ?? 8;
    $marginRight = $dim['margin_right'] ?? 8;
    $marginBottom = $dim['margin_bottom'] ?? 8;
    $marginLeft = $dim['margin_left'] ?? 8;
    $headerHeight = (float) ($dim['header_height'] ?? 0);
    $footerHeight = (float) ($dim['footer_height'] ?? 0);
    $contentOffsetTop = (float) ($dim['content_offset_top'] ?? 0);
@endphp
<style>
    :root {
        --invoice-margin-top: {{ $marginTop }}mm;
        --invoice-margin-right: {{ $marginRight }}mm;
        --invoice-margin-bottom: {{ $marginBottom }}mm;
        --invoice-margin-left: {{ $marginLeft }}mm;
        --invoice-header-height: {{ $headerHeight }}mm;
        --invoice-footer-height: {{ $footerHeight }}mm;
        --invoice-content-offset-top: {{ $contentOffsetTop }}mm;
        --invoice-user-font-scale: {{ number_format($fontScale, 2) }};
    }

    @page {
        margin: var(--invoice-margin-top) var(--invoice-margin-right) var(--invoice-margin-bottom) var(--invoice-margin-left);
    }

    @if(abs($fontScale - 1.0) > 0.001)
        .page-content { zoom: var(--invoice-user-font-scale); }
    @endif

    @if($headerHeight > 0)
        .page-content { padding-top: var(--invoice-header-height); }
    @endif
    @if($footerHeight > 0)
        .page-content { padding-bottom: var(--invoice-footer-height); }
    @endif
    @if($contentOffsetTop > 0)
        .page-content > :first-child { margin-top: var(--invoice-content-offset-top) !important; }
    @endif
</style>
