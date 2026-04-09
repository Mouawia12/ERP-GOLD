<style>
    @page {
        size: A5 {{ $printOrientation ?? 'portrait' }};
        margin: {{ !empty($compactStandalonePrint) ? '0' : '5mm 5mm 12mm 5mm' }};
    }

    @font-face {
        font-family: 'Almarai';
        src: url("{{ asset('assets/fonts/Almarai.ttf') }}");
    }

    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        color: #111;
        background: #fff;
        font-family: 'Almarai', 'DejaVu Sans', sans-serif;
        font-size: 10.35px;
        line-height: 1.24;
    }

    body {
        --invoice-accent: #555;
        --invoice-surface: #fff;
        --invoice-table-head: #e0e0e0;
        --screen-background: #f3f4f6;
        --screen-outline: #d4d4d8;
        --page-width: 138mm;
        --page-min-height: 198mm;
        --company-font-size: 9.2px;
        --brand-logo-size: 78px;
        --header-center-width: 84px;
        --item-font-size: 8.8px;
        --summary-font-size: 8.9px;
        --note-max-height: 30px;
        --screen-page-padding: 4mm;
    }

    body.invoice-template-compact {
        --company-font-size: 8.5px;
        --brand-logo-size: 68px;
        --item-font-size: 8.3px;
        --summary-font-size: 8.4px;
        --note-max-height: 24px;
    }

    body.invoice-template-modern {
        --invoice-accent: #1f2937;
        --invoice-surface: #f8fafc;
        --invoice-table-head: #dbe4f0;
        --screen-background: #eef2ff;
        --screen-outline: #cbd5e1;
    }

    body.invoice-orientation-landscape {
        --page-width: 200mm;
        --page-min-height: 138mm;
        --company-font-size: 8.5px;
        --brand-logo-size: 68px;
        --header-center-width: 76px;
        --item-font-size: 8.25px;
        --summary-font-size: 8.4px;
        --note-max-height: 24px;
        --screen-page-padding: 5mm;
    }

    body.invoice-paper-ready {
        --screen-page-padding: 5mm;
    }

    .page {
        width: var(--page-width);
        min-height: var(--page-min-height);
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        background: var(--invoice-surface);
    }

    .page-content {
        flex: 1;
    }

    .ltr {
        direction: ltr;
        unicode-bidi: embed;
        display: inline-block;
    }

    .invoice-rule,
    .page-footer {
        border-top: 1px solid var(--invoice-accent);
    }

    .invoice-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) var(--header-center-width) minmax(0, 1fr);
        column-gap: 8px;
        align-items: start;
    }

    .company-block {
        min-height: 82px;
        font-size: var(--company-font-size);
        line-height: 1.32;
    }

    .company-block.company-en {
        direction: ltr;
        text-align: left;
    }

    .company-block.company-ar {
        text-align: right;
    }

    .company-line {
        margin: 0 0 2px;
        word-break: break-word;
    }

    .company-name {
        font-weight: 700;
    }

    .header-center {
        text-align: center;
    }

    .brand-logo {
        width: var(--brand-logo-size);
        height: var(--brand-logo-size);
        object-fit: contain;
        display: block;
        margin: 0 auto 4px;
    }

    .invoice-title {
        margin: 0;
        font-size: 14.8px;
        font-weight: 700;
        line-height: 1.16;
    }

    .invoice-title-en {
        margin: 2px 0 0;
        font-size: 10.4px;
        font-weight: 700;
        line-height: 1.12;
    }

    .invoice-rule {
        margin: 7px 0 8px;
    }

    .invoice-head-meta {
        display: grid;
        grid-template-columns: 34% 28% 38%;
        column-gap: 8px;
        align-items: start;
        direction: ltr;
        margin-bottom: 8px;
    }

    .items-table,
    .totals-table,
    .payment-table,
    .carat-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .items-table td,
    .items-table th,
    .totals-table td,
    .totals-table th,
    .payment-table td,
    .payment-table th,
    .carat-table td,
    .carat-table th {
        border: 1px solid #999;
        padding: 2px 3px;
        vertical-align: middle;
    }

    .items-table th,
    .totals-table th,
    .payment-table th,
    .carat-table th {
        background: var(--invoice-table-head);
        font-weight: 700;
    }

    .invoice-meta-list {
        direction: rtl;
        display: flex;
        flex-direction: column;
        gap: 5px;
        padding-top: 3px;
    }

    .invoice-meta-row {
        display: flex;
        align-items: flex-start;
        gap: 4px;
        font-size: 9.35px;
        line-height: 1.34;
        font-weight: 700;
    }

    .invoice-meta-label {
        white-space: nowrap;
    }

    .invoice-meta-value {
        min-width: 0;
        word-break: break-word;
    }

    .qr-box {
        width: 100%;
        min-height: 126px;
        border: 0;
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        overflow: hidden;
        padding: 0;
    }

    .qr-box.is-placeholder {
        border: 1px dashed #999;
        align-items: center;
        justify-content: center;
        padding: 4px;
    }

    .qr-box img {
        width: 122px;
        height: 122px;
        object-fit: contain;
    }

    .qr-placeholder {
        font-size: 8.3px;
        color: #666;
    }

    .items-table {
        margin-bottom: 6px;
        font-size: var(--item-font-size);
    }

    .items-table th,
    .items-table td {
        text-align: center;
        page-break-inside: avoid;
    }

    .items-table tbody tr {
        page-break-inside: avoid;
    }

    .description-cell {
        text-align: right !important;
    }

    .description-main {
        display: block;
        font-size: 9.05px;
        line-height: 1.14;
        font-weight: 700;
    }

    .sub-line {
        display: block;
        margin-top: 1px;
        font-size: 7.85px;
        line-height: 1.08;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 5px;
        margin-bottom: 5px;
    }

    .reference-summary-grid {
        direction: ltr;
        align-items: start;
    }

    .reference-summary-grid > div {
        direction: rtl;
    }

    .summary-stack {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .totals-table td:first-child,
    .payment-table td:first-child,
    .carat-table td:first-child {
        font-weight: 700;
    }

    .totals-table td:last-child,
    .payment-table td:last-child,
    .carat-table td:last-child {
        text-align: left;
    }

    .totals-table,
    .payment-table,
    .carat-table {
        font-size: var(--summary-font-size);
    }

    .seller-line {
        margin: 0 0 4px;
        font-size: 9.2px;
        font-weight: 700;
    }

    .notes-line {
        margin: 0 0 5px;
        padding-top: 3px;
        border-top: 1px solid #999;
        font-size: 8.3px;
        line-height: 1.2;
        max-height: var(--note-max-height);
        overflow: hidden;
        white-space: pre-line;
    }

    .page-footer {
        margin-top: auto;
        padding-top: 4px;
        display: flex;
        justify-content: space-between;
        gap: 6px;
        font-size: 8.3px;
        line-height: 1.16;
    }

    .page-footer .footer-left {
        direction: ltr;
        text-align: left;
    }

    body.invoice-orientation-landscape .invoice-head-meta {
        grid-template-columns: 27% 32% 41%;
    }

    body.invoice-orientation-landscape .qr-box {
        min-height: 96px;
    }

    body.invoice-orientation-landscape .qr-box img {
        width: 92px;
        height: 92px;
    }

    body.invoice-orientation-landscape .summary-grid {
        column-gap: 8px;
    }

    .no-print {
        display: none !important;
    }

    @media screen {
        body {
            padding: 8px 0 20px;
            background: var(--screen-background);
        }

        .page {
            padding: var(--screen-page-padding);
            box-shadow: 0 0 0 1px var(--screen-outline);
        }

        body.invoice-paper-ready .page {
            padding: 5mm;
        }
    }

    body.invoice-template-compact .items-table td,
    body.invoice-template-compact .items-table th,
    body.invoice-template-compact .totals-table td,
    body.invoice-template-compact .totals-table th,
    body.invoice-template-compact .payment-table td,
    body.invoice-template-compact .payment-table th,
    body.invoice-template-compact .carat-table td,
    body.invoice-template-compact .carat-table th {
        padding: 2px 2px;
    }

    body.invoice-template-modern .invoice-title,
    body.invoice-template-modern .invoice-title-en,
    body.invoice-template-modern .company-name {
        color: #0f172a;
    }

    @media print {
        html,
        body {
            font-size: 9.85px;
        }

        .page {
            width: auto;
            min-height: auto;
        }

        body.invoice-paper-ready .page {
            padding: 5mm;
        }
    }
</style>
