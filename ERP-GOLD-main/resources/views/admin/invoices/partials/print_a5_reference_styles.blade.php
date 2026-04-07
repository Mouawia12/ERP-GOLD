<style>
    @page {
        size: A5 portrait;
        margin: 5mm;
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
        font-size: 9px;
        line-height: 1.35;
    }

    body {
        --line-color: #d5d9df;
        --line-strong: #9aa0a6;
        --head-bg: #f1f4f8;
        --page-bg: #fff;
        --screen-bg: #eef1f5;
        --screen-outline: #d8dce2;
        --shell-width: 108mm;
        --qr-size: 19mm;
        --terms-max-height: 13mm;
    }

    body.invoice-template-compact {
        --shell-width: 104mm;
        --qr-size: 17mm;
        --terms-max-height: 11mm;
    }

    body.invoice-template-modern {
        --line-color: #cbd5e1;
        --line-strong: #64748b;
        --head-bg: #e8eef7;
        --screen-bg: #e9eef8;
        --screen-outline: #cbd5e1;
    }

    .page {
        width: 138mm;
        min-height: 198mm;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        background: var(--page-bg);
        padding: 6mm 7mm 7mm;
    }

    .page-content {
        flex: 1;
    }

    .invoice-shell {
        width: 100%;
        max-width: var(--shell-width);
        margin: 0 auto;
    }

    .ltr {
        direction: ltr;
        unicode-bidi: embed;
        display: inline-block;
    }

    .micro-header {
        display: flex;
        justify-content: space-between;
        gap: 6mm;
        margin-bottom: 3mm;
        padding-bottom: 2mm;
        border-bottom: 1px solid var(--line-color);
        font-size: 6.6px;
        line-height: 1.5;
    }

    .micro-header-block {
        display: flex;
        flex-direction: column;
        gap: 0.4mm;
        min-width: 0;
    }

    .micro-header-title {
        font-weight: 700;
    }

    .compact-head {
        display: grid;
        grid-template-columns: var(--qr-size) minmax(0, 1fr) 42mm;
        gap: 5mm;
        align-items: start;
        direction: ltr;
        margin-bottom: 3.2mm;
    }

    .compact-head > * {
        min-width: 0;
    }

    .compact-qr {
        width: var(--qr-size);
        min-height: var(--qr-size);
        border: 1px solid var(--line-color);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fff;
    }

    .compact-qr.is-placeholder {
        border-style: dashed;
    }

    .compact-qr img {
        width: 100%;
        height: auto;
        aspect-ratio: 1 / 1;
        object-fit: contain;
        display: block;
    }

    .qr-placeholder {
        font-size: 6px;
        color: #666;
    }

    .compact-title-block {
        direction: rtl;
        text-align: center;
        padding-top: 1mm;
    }

    .compact-title {
        margin: 0;
        font-size: 10.4px;
        line-height: 1.25;
        font-weight: 700;
    }

    .compact-subtitle {
        margin: 0.5mm 0 0;
        font-size: 6px;
        line-height: 1.2;
        color: #4b5563;
    }

    .compact-meta {
        direction: rtl;
        font-size: 6.8px;
        line-height: 1.55;
        font-weight: 700;
    }

    .compact-meta-row {
        display: flex;
        align-items: flex-start;
        gap: 1.2mm;
    }

    .compact-meta-label {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .compact-meta-value {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .reference-table,
    .summary-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .reference-table {
        margin-bottom: 2.2mm;
        font-size: 6.7px;
    }

    .reference-table th,
    .reference-table td,
    .summary-table th,
    .summary-table td {
        border: 1px solid var(--line-color);
        padding: 1.3mm 1mm;
        vertical-align: middle;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .reference-table th,
    .summary-table th {
        background: var(--head-bg);
        font-weight: 700;
        text-align: center;
    }

    .reference-table td {
        text-align: center;
    }

    .head-main,
    .head-sub {
        display: block;
        line-height: 1.15;
    }

    .head-main {
        font-size: 6.6px;
        font-weight: 700;
    }

    .head-sub {
        margin-top: 0.4mm;
        font-size: 5.4px;
        color: #6b7280;
        direction: ltr;
    }

    .description-cell {
        text-align: right !important;
    }

    .description-main {
        display: block;
        font-size: 6.9px;
        font-weight: 700;
        line-height: 1.2;
    }

    .description-sub {
        display: block;
        margin-top: 0.4mm;
        font-size: 5.5px;
        line-height: 1.15;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
        gap: 2mm;
        margin-bottom: 2.2mm;
    }

    .summary-label {
        font-weight: 700;
    }

    .summary-sub {
        display: block;
        margin-top: 0.2mm;
        font-size: 5.4px;
        line-height: 1.1;
        color: #6b7280;
        direction: ltr;
    }

    .summary-value {
        width: 29%;
        text-align: center;
        white-space: nowrap;
    }

    .terms-box {
        margin-bottom: 2.3mm;
        border: 1px solid var(--line-color);
        padding: 1.4mm 1.7mm;
    }

    .terms-title {
        margin-bottom: 0.6mm;
        font-size: 6.6px;
        font-weight: 700;
    }

    .terms-content {
        font-size: 6.1px;
        line-height: 1.45;
        white-space: pre-line;
        max-height: var(--terms-max-height);
        overflow: hidden;
    }

    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8mm;
        margin-top: 3mm;
        font-size: 6.9px;
    }

    .signature-box {
        text-align: center;
    }

    .signature-label {
        display: block;
        margin-bottom: 2.2mm;
        font-weight: 700;
    }

    .signature-line {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        min-height: 8mm;
        padding-top: 4mm;
        border-top: 1px solid var(--line-strong);
        overflow-wrap: anywhere;
    }

    .micro-footer {
        margin-top: auto;
        padding-top: 2.5mm;
        border-top: 1px solid var(--line-color);
        display: flex;
        justify-content: space-between;
        gap: 3mm;
        font-size: 6.2px;
        line-height: 1.35;
    }

    .no-print {
        display: none !important;
    }

    @media screen {
        body {
            padding: 8px 0 20px;
            background: var(--screen-bg);
        }

        .page {
            padding: 7mm;
            box-shadow: 0 0 0 1px var(--screen-outline);
        }
    }

    @media print {
        html,
        body {
            font-size: 9px;
            background: #fff;
        }

        .page {
            width: auto;
            min-height: auto;
            padding: 0;
            box-shadow: none;
        }
    }
</style>
