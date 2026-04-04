<style type="text/css">
    @page {
        size: {{ strtoupper($printFormat) }} portrait;
        margin: {{ $printFormat === 'a5' ? '6mm' : '8mm' }};
    }

    @font-face {
        font-family: 'Almarai';
        src: url("{{ asset('assets/fonts/Almarai.ttf') }}");
    }

    :root {
        --print-ink: #18181b;
        --print-muted: #6b7280;
        --print-border: #d6d3d1;
        --print-panel: #fffdf8;
        --print-panel-strong: #f7f0df;
        --print-panel-soft: #faf7f2;
        --print-accent: #8c6a2d;
        --print-accent-strong: #5f4518;
        --print-danger: #9f1239;
        --sheet-width: {{ $sheetWidth }};
        --screen-font-size: {{ $screenFontSize }};
        --print-font-size: {{ $printFontSize }};
        --qr-size: {{ $qrWidth }};
        --brand-logo-size: {{ $printFormat === 'a5' ? '92px' : '116px' }};
        --panel-radius: {{ $printFormat === 'a5' ? '14px' : '18px' }};
        --panel-padding: {{ $printFormat === 'a5' ? '10px' : '14px' }};
        --sheet-gap: {{ $printFormat === 'a5' ? '10px' : '14px' }};
        --table-cell-padding: {{ $printFormat === 'a5' ? '7px 8px' : '9px 10px' }};
        --hero-title-size: {{ $printFormat === 'a5' ? '17px' : '21px' }};
        --meta-value-size: {{ $printFormat === 'a5' ? '12px' : '13px' }};
        --shadow-soft: 0 14px 34px rgba(15, 23, 42, 0.08);
    }

    * {
        box-sizing: border-box;
        color: var(--print-ink);
    }

    html,
    body {
        margin: 0;
        padding: 0;
        background: #fff;
        color: var(--print-ink);
        font-family: 'Almarai', sans-serif !important;
        font-size: var(--screen-font-size) !important;
        font-weight: 700;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    body.invoice-template-classic {
        --print-accent: #374151;
        --print-accent-strong: #111827;
        --print-panel: #ffffff;
        --print-panel-strong: #f8fafc;
        --print-panel-soft: #ffffff;
        --print-border: #d1d5db;
    }

    body.invoice-template-compact {
        --panel-padding: {{ $printFormat === 'a5' ? '9px' : '12px' }};
        --sheet-gap: {{ $printFormat === 'a5' ? '8px' : '10px' }};
        --table-cell-padding: {{ $printFormat === 'a5' ? '6px 7px' : '7px 8px' }};
        --panel-radius: {{ $printFormat === 'a5' ? '12px' : '14px' }};
        --shadow-soft: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    body.invoice-template-modern .print-header-section,
    body.invoice-template-modern .invoice-hero,
    body.invoice-template-modern .section-card,
    body.invoice-template-modern .meta-panel {
        border-color: #d9c9a2;
    }

    .invoice-print-sheet {
        width: 100%;
        max-width: var(--sheet-width);
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: var(--sheet-gap);
    }

    .pos_details {
        width: 100%;
        padding: {{ $printFormat === 'a5' ? '6px' : '10px' }};
    }

    .no-print {
        position: fixed;
        bottom: 0;
        left: 30px;
        z-index: 9999;
        width: 200px !important;
        height: 40px !important;
        padding-top: 10px;
        border-radius: 0;
        color: #fff !important;
    }

    .print-header-section,
    .invoice-hero,
    .section-card,
    .meta-panel {
        background: var(--print-panel);
        border: 1px solid var(--print-border);
        border-radius: var(--panel-radius);
        box-shadow: var(--shadow-soft);
        break-inside: avoid;
        page-break-inside: avoid;
    }

    .print-header-section {
        padding: var(--panel-padding);
        min-height: {{ $headerHeight }};
        display: flex;
        flex-direction: column;
        gap: 10px;
        background:
            radial-gradient(circle at top right, rgba(140, 106, 45, 0.12), transparent 38%),
            linear-gradient(180deg, var(--print-panel) 0%, var(--print-panel-soft) 100%);
    }

    .header-main {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }

    .header-copy {
        display: flex;
        flex-direction: column;
        gap: 4px;
        text-align: right;
        min-width: 0;
    }

    .header-kicker {
        font-size: 0.82em;
        color: var(--print-accent-strong);
    }

    .header-title {
        margin: 0;
        font-size: 1.2em;
        line-height: 1.4;
        color: var(--print-ink);
    }

    .header-subtitle {
        margin: 0;
        color: var(--print-muted);
        font-size: 0.84em;
        font-weight: 700;
    }

    .print-brand-logo {
        width: var(--brand-logo-size);
        max-width: 100%;
        height: auto;
        max-height: var(--brand-logo-size);
        object-fit: contain;
    }

    .header-meta-list {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
    }

    .header-meta-pill,
    .hero-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 5px 10px;
        border-radius: 999px;
        border: 1px solid rgba(140, 106, 45, 0.18);
        background: rgba(255, 255, 255, 0.75);
        font-size: 0.82em;
        line-height: 1.35;
    }

    .invoice-hero {
        display: grid;
        grid-template-columns: minmax(0, 1fr) auto;
        align-items: center;
        gap: 12px;
        padding: var(--panel-padding);
        background:
            linear-gradient(135deg, rgba(140, 106, 45, 0.08), transparent 36%),
            linear-gradient(180deg, #ffffff 0%, var(--print-panel-soft) 100%);
    }

    .hero-copy {
        display: flex;
        flex-direction: column;
        gap: 8px;
        min-width: 0;
        text-align: right;
    }

    .hero-overline {
        display: inline-flex;
        align-self: flex-start;
        padding: 4px 10px;
        border-radius: 999px;
        background: var(--print-panel-strong);
        color: var(--print-accent-strong);
        font-size: 0.82em;
        border: 1px solid rgba(140, 106, 45, 0.2);
    }

    .hero-title {
        margin: 0;
        font-size: var(--hero-title-size);
        line-height: 1.4;
    }

    .hero-subtitle {
        margin: 0;
        color: var(--print-muted);
        font-size: 0.86em;
        font-weight: 700;
    }

    .hero-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        justify-content: flex-start;
    }

    .hero-qr {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        width: calc(var(--qr-size) + 18px);
        min-width: calc(var(--qr-size) + 18px);
        padding: 8px;
        border-radius: calc(var(--panel-radius) - 4px);
        background: #fff;
        border: 1px solid var(--print-border);
    }

    .hero-qr-label {
        font-size: 0.78em;
        color: var(--print-muted);
    }

    .invoice-print-qr {
        width: var(--qr-size);
        max-width: 100%;
        display: block;
    }

    .meta-panels,
    .summary-grid {
        display: grid;
        gap: var(--sheet-gap);
    }

    .meta-panels {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .meta-panel,
    .section-card {
        padding: var(--panel-padding);
    }

    .panel-heading,
    .section-heading {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-bottom: 10px;
    }

    .panel-title,
    .section-title {
        margin: 0;
        font-size: 0.98em;
        color: var(--print-accent-strong);
    }

    .panel-hint,
    .section-hint {
        font-size: 0.8em;
        color: var(--print-muted);
    }

    .panel-list,
    .summary-list,
    .payment-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .panel-item,
    .summary-row,
    .payment-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 10px;
    }

    .panel-item {
        padding-bottom: 8px;
        border-bottom: 1px dashed rgba(140, 106, 45, 0.18);
    }

    .panel-item:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .panel-label,
    .summary-label,
    .payment-label {
        color: var(--print-muted);
        font-size: 0.84em;
        line-height: 1.5;
        font-weight: 800;
    }

    .panel-value,
    .summary-value,
    .payment-value {
        font-size: var(--meta-value-size);
        line-height: 1.5;
        text-align: left;
    }

    .section-card {
        background:
            linear-gradient(180deg, rgba(255, 255, 255, 0.95), rgba(250, 247, 242, 0.95));
    }

    .section-card.table-section {
        overflow: hidden;
    }

    .invoice-print-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin: 0;
        direction: rtl;
        font-size: {{ $printFormat === 'a5' ? '11px' : '12px' }};
    }

    .invoice-print-table thead th {
        padding: var(--table-cell-padding) !important;
        background: var(--print-panel-strong);
        border-bottom: 1px solid var(--print-border);
        text-align: center;
        font-size: 0.82em;
        line-height: 1.5;
    }

    .invoice-print-table tbody td {
        padding: var(--table-cell-padding) !important;
        border-bottom: 1px solid rgba(214, 211, 209, 0.9);
        vertical-align: top;
        line-height: 1.6;
        word-break: break-word;
    }

    .invoice-print-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .line-primary {
        display: block;
        font-size: 0.96em;
        line-height: 1.55;
    }

    .line-secondary {
        display: block;
        margin-top: 2px;
        color: var(--print-muted);
        font-size: 0.8em;
        line-height: 1.5;
        font-weight: 800;
    }

    .line-total {
        font-size: 1em;
        color: var(--print-accent-strong);
    }

    .summary-card {
        display: flex;
        flex-direction: column;
    }

    .summary-row,
    .payment-row {
        padding-bottom: 8px;
        border-bottom: 1px dashed rgba(140, 106, 45, 0.18);
    }

    .summary-row:last-child,
    .payment-row:last-child {
        padding-bottom: 0;
        border-bottom: 0;
    }

    .summary-row.total {
        margin-top: 4px;
        padding: 10px;
        border: 1px solid rgba(140, 106, 45, 0.24);
        border-radius: 12px;
        background: var(--print-panel-strong);
    }

    .summary-row.total .summary-label,
    .summary-row.total .summary-value {
        color: var(--print-accent-strong);
        font-size: 0.96em;
    }

    .empty-note {
        color: var(--print-muted);
        font-size: 0.84em;
        line-height: 1.7;
    }

    .terms-body {
        min-height: {{ $printFormat === 'a5' ? '92px' : '110px' }};
        padding: 10px;
        border-radius: 12px;
        background: rgba(255, 255, 255, 0.7);
        border: 1px dashed rgba(140, 106, 45, 0.24);
        white-space: pre-line;
        line-height: 1.8;
        font-size: 0.88em;
    }

    .print-footer-section {
        margin-top: 2px;
        padding-top: 4px;
        border-top: 1px dashed var(--print-border);
    }

    .signature-label {
        display: inline-block;
        margin-bottom: 6px;
        color: var(--print-muted);
    }

    .signature-line {
        min-height: 38px;
        padding-top: 8px;
        border-top: 1px solid rgba(140, 106, 45, 0.24);
    }

    body.invoice-print-format-a5 .meta-panels,
    body.invoice-print-format-a5 .summary-grid {
        grid-template-columns: 1fr;
    }

    body.invoice-print-format-a5 .header-main,
    body.invoice-print-format-a5 .invoice-hero {
        gap: 10px;
    }

    body.invoice-print-format-a5 .hero-pills {
        gap: 5px;
    }

    body.invoice-print-format-a5 .hero-qr {
        width: calc(var(--qr-size) + 14px);
        min-width: calc(var(--qr-size) + 14px);
        padding: 6px;
    }

    @media print {
        html,
        body {
            font-size: var(--print-font-size) !important;
        }

        .pos_details {
            padding: 0 !important;
        }

        .invoice-print-sheet {
            max-width: var(--sheet-width) !important;
            gap: {{ $printFormat === 'a5' ? '7px' : '10px' }};
        }

        .no-print {
            display: none !important;
        }

        .print-header-section,
        .invoice-hero,
        .section-card,
        .meta-panel {
            box-shadow: none !important;
        }

        .invoice-print-table thead th,
        .invoice-print-table tbody td {
            padding: {{ $printFormat === 'a5' ? '5px 6px' : '7px 8px' }} !important;
        }
    }
</style>
