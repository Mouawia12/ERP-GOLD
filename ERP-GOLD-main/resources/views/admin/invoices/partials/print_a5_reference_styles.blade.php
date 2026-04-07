<style>
    @page {
        size: {{ !empty($compactStandalonePrint) ? 'A5 landscape' : 'A5 portrait' }};
        margin: {{ !empty($compactStandalonePrint) ? '0' : '5mm' }};
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
        --page-width: 138mm;
        --page-min-height: 198mm;
        --shell-width: 108mm;
        --qr-size: 19mm;
        --meta-width: 42mm;
        --terms-max-height: 13mm;
        --page-padding-top: 6mm;
        --page-padding-inline: 7mm;
        --page-padding-bottom: 7mm;
        --head-gap: 5mm;
        --head-margin-bottom: 3.2mm;
        --table-cell-padding-block: 1.3mm;
        --table-cell-padding-inline: 1mm;
        --summary-gap: 2mm;
        --signature-gap: 8mm;
        --signature-margin-top: 3mm;
    }

    body.invoice-template-compact {
        --shell-width: 104mm;
        --qr-size: 17mm;
        --meta-width: 40mm;
        --terms-max-height: 11mm;
    }

    body.invoice-paper-ready {
        --page-width: 200mm;
        --page-min-height: 150mm;
        --shell-width: 188mm;
        --qr-size: 20mm;
        --meta-width: 60mm;
        --terms-max-height: 10mm;
        --page-padding-top: 5mm;
        --page-padding-inline: 5mm;
        --page-padding-bottom: 4mm;
        --head-gap: 4.2mm;
        --head-margin-bottom: 2.2mm;
        --table-cell-padding-block: 0.9mm;
        --table-cell-padding-inline: 0.9mm;
        --summary-gap: 1.6mm;
        --signature-gap: 7mm;
        --signature-margin-top: 2mm;
    }

    body.invoice-template-modern {
        --line-color: #cbd5e1;
        --line-strong: #64748b;
        --head-bg: #e8eef7;
        --screen-bg: #e9eef8;
        --screen-outline: #cbd5e1;
    }

    .page {
        width: var(--page-width);
        min-height: var(--page-min-height);
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        background: var(--page-bg);
        padding: var(--page-padding-top) var(--page-padding-inline) var(--page-padding-bottom);
    }

    .page-content {
        flex: 1;
    }

    body.invoice-paper-ready .page {
        justify-content: center;
    }

    body.invoice-paper-ready .page-content {
        flex: 0 0 auto;
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
        grid-template-columns: var(--qr-size) minmax(0, 1fr) var(--meta-width);
        gap: var(--head-gap);
        align-items: start;
        direction: ltr;
        margin-bottom: var(--head-margin-bottom);
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
    }

    .reference-table {
        table-layout: fixed;
    }

    .summary-table {
        table-layout: auto;
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
        padding: var(--table-cell-padding-block) var(--table-cell-padding-inline);
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
        grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
        gap: var(--summary-gap);
        margin-bottom: 1.7mm;
    }

    .summary-label {
        font-weight: 700;
        line-height: 1.18;
    }

    .summary-sub {
        display: inline;
        margin-inline-start: 0.45mm;
        font-size: 5.1px;
        line-height: 1;
        color: #6b7280;
        direction: ltr;
    }

    .summary-value {
        width: 25%;
        text-align: center;
        white-space: nowrap;
        line-height: 1.1;
    }

    .payment-table .summary-label {
        width: 56%;
        font-size: 6.2px;
    }

    .payment-table .summary-value {
        width: 44%;
    }

    .invoice-summary-table .summary-label {
        width: 75%;
    }

    .invoice-summary-table .summary-value {
        width: 25%;
    }

    .terms-box {
        margin-bottom: 1.7mm;
        border: 1px solid var(--line-color);
        padding: 1.05mm 1.45mm;
    }

    .terms-title {
        margin-bottom: 0.35mm;
        font-size: 6.6px;
        font-weight: 700;
    }

    .terms-content {
        font-size: 5.85px;
        line-height: 1.28;
        white-space: pre-line;
        max-height: var(--terms-max-height);
        overflow: hidden;
    }

    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--signature-gap);
        margin-top: var(--signature-margin-top);
        font-size: 6.9px;
    }

    .signature-box {
        text-align: center;
    }

    .signature-label {
        display: block;
        margin-bottom: 1.1mm;
        font-weight: 700;
    }

    .signature-line {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        min-height: 5.9mm;
        padding-top: 2.3mm;
        border-top: 1px solid var(--line-strong);
        overflow-wrap: anywhere;
    }

    body.invoice-paper-ready .compact-title-block {
        padding-top: 0.8mm;
    }

    body.invoice-paper-ready .compact-title {
        font-size: 10.9px;
        line-height: 1.16;
    }

    body.invoice-paper-ready .compact-subtitle {
        margin-top: 0.45mm;
        font-size: 6px;
    }

    body.invoice-paper-ready .compact-meta {
        font-size: 6.8px;
        line-height: 1.46;
    }

    body.invoice-paper-ready .reference-table {
        margin-bottom: 1.9mm;
        font-size: 7px;
    }

    body.invoice-paper-ready .description-main {
        font-size: 7px;
        line-height: 1.14;
    }

    body.invoice-paper-ready .description-sub {
        margin-top: 0.3mm;
        font-size: 5.5px;
        line-height: 1.1;
    }

    body.invoice-paper-ready .summary-grid {
        grid-template-columns: minmax(0, 0.82fr) minmax(0, 1.18fr);
        margin-bottom: 1.75mm;
    }

    body.invoice-paper-ready .summary-table th,
    body.invoice-paper-ready .summary-table td {
        line-height: 1.14;
    }

    body.invoice-paper-ready .payment-table .summary-label {
        width: 56%;
        font-size: 6.45px;
    }

    body.invoice-paper-ready .payment-table .summary-value {
        width: 44%;
    }

    body.invoice-paper-ready .invoice-summary-table .summary-label {
        width: 79%;
    }

    body.invoice-paper-ready .invoice-summary-table .summary-value {
        width: 21%;
    }

    body.invoice-paper-ready .invoice-summary-table .summary-sub {
        margin-inline-start: 0.4mm;
        font-size: 5px;
    }

    body.invoice-paper-ready .terms-box {
        margin-bottom: 1.6mm;
        padding: 1mm 1.35mm;
    }

    body.invoice-paper-ready .terms-title {
        margin-bottom: 0.35mm;
        font-size: 6.6px;
    }

    body.invoice-paper-ready .terms-content {
        font-size: 5.9px;
        line-height: 1.24;
    }

    body.invoice-paper-ready .signatures {
        font-size: 6.9px;
    }

    body.invoice-paper-ready .signature-label {
        margin-bottom: 1.2mm;
    }

    body.invoice-paper-ready .signature-line {
        min-height: 6.2mm;
        padding-top: 2.4mm;
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

        body.invoice-paper-ready .page {
            padding: 5mm;
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

        body.invoice-paper-ready .page {
            padding: 5mm;
        }
    }
</style>
