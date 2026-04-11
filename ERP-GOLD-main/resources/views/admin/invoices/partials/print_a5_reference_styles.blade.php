<style>
    @page {
        size: A5 {{ $printOrientation ?? 'portrait' }};
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
        font-size: 9.35px;
        line-height: 1.3;
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
        --safe-print-width: auto;
        --safe-print-height: auto;
        --safe-print-offset-top: 0;
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

    body.invoice-orientation-landscape {
        --page-width: 200mm;
        --page-min-height: 138mm;
        --shell-width: 176mm;
        --qr-size: 19mm;
        --meta-width: 58mm;
        --page-padding-top: 5mm;
        --page-padding-inline: 5mm;
        --page-padding-bottom: 5mm;
        --head-gap: 4mm;
        --head-margin-bottom: 2mm;
        --summary-gap: 1.5mm;
        --signature-gap: 6.5mm;
        --signature-margin-top: 2mm;
    }

    body.invoice-paper-ready {
        --terms-max-height: 9mm;
        --page-padding-top: 7mm;
        --page-padding-inline: 5mm;
        --page-padding-bottom: 7mm;
        --head-gap: 2.7mm;
        --head-margin-bottom: 1.45mm;
        --table-cell-padding-block: 0.72mm;
        --table-cell-padding-inline: 0.74mm;
        --summary-gap: 1.05mm;
        --signature-gap: 4.8mm;
        --signature-margin-top: 1.25mm;
    }

    body.invoice-paper-ready.invoice-orientation-portrait {
        --shell-width: 124mm;
        --qr-size: 17mm;
        --meta-width: 44mm;
    }

    body.invoice-paper-ready.invoice-orientation-landscape {
        --page-width: 210mm;
        --page-min-height: 148mm;
        --safe-print-width: 194mm;
        --safe-print-height: 100mm;
        --safe-print-offset-top: 24mm;
        --shell-width: 188mm;
        --qr-size: 22mm;
        --meta-width: 61mm;
        --terms-max-height: 12.5mm;
        --page-padding-top: 0;
        --page-padding-inline: 0;
        --page-padding-bottom: 0;
        --head-gap: 4.5mm;
        --head-margin-bottom: 2.2mm;
        --table-cell-padding-block: 0.94mm;
        --table-cell-padding-inline: 0.86mm;
        --summary-gap: 1.65mm;
        --signature-gap: 7.2mm;
        --signature-margin-top: 2.05mm;
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

    body.invoice-paper-ready.invoice-orientation-landscape .page {
        justify-content: flex-start;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .page-content {
        width: min(100%, var(--safe-print-width));
        max-width: var(--safe-print-width);
        min-height: var(--safe-print-height);
        margin: var(--safe-print-offset-top) auto 0;
        display: flex;
        align-items: center;
        justify-content: center;
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
        font-size: 7px;
        line-height: 1.38;
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
        font-size: 6.2px;
        color: #666;
    }

    .compact-title-block {
        direction: rtl;
        text-align: center;
        padding-top: 1mm;
    }

    .compact-title {
        margin: 0;
        font-size: 10.95px;
        line-height: 1.18;
        font-weight: 700;
    }

    .compact-subtitle {
        margin: 0.5mm 0 0;
        font-size: 6.25px;
        line-height: 1.2;
        color: #4b5563;
    }

    .compact-meta {
        direction: rtl;
        font-size: 7.15px;
        line-height: 1.42;
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
        font-size: 7.05px;
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
        font-size: 6.95px;
        font-weight: 700;
    }

    .head-sub {
        margin-top: 0.4mm;
        font-size: 5.6px;
        color: #6b7280;
        direction: ltr;
    }

    .description-cell {
        text-align: right !important;
    }

    .description-main {
        display: block;
        font-size: 7.25px;
        font-weight: 700;
        line-height: 1.14;
    }

    .description-sub {
        display: block;
        margin-top: 0.4mm;
        font-size: 5.75px;
        line-height: 1.08;
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
        font-size: 5.3px;
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
        font-size: 6.45px;
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
        font-size: 6.95px;
        font-weight: 700;
    }

    .terms-content {
        font-size: 6.1px;
        line-height: 1.28;
        white-space: normal;
        max-height: none;
        overflow: visible;
        overflow-wrap: anywhere;
    }

    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--signature-gap);
        margin-top: var(--signature-margin-top);
        font-size: 7.15px;
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
        padding-top: 2.1mm;
        border-top: 1px solid var(--line-strong);
        overflow-wrap: anywhere;
    }

    body.invoice-paper-ready .compact-title-block {
        padding-top: 0.55mm;
    }

    body.invoice-paper-ready .compact-title {
        font-size: 10.45px;
        line-height: 1.08;
    }

    body.invoice-paper-ready .compact-subtitle {
        margin-top: 0.3mm;
        font-size: 5.95px;
    }

    body.invoice-paper-ready .compact-meta {
        font-size: 6.6px;
        line-height: 1.28;
    }

    body.invoice-paper-ready .reference-table {
        margin-bottom: 1.6mm;
        font-size: 6.8px;
    }

    body.invoice-paper-ready .description-main {
        font-size: 6.95px;
        line-height: 1.04;
    }

    body.invoice-paper-ready .description-sub {
        margin-top: 0.2mm;
        font-size: 5.45px;
        line-height: 1.02;
    }

    body.invoice-paper-ready .summary-grid {
        grid-template-columns: minmax(0, 0.75fr) minmax(0, 1.25fr);
        margin-bottom: 1.45mm;
    }

    body.invoice-paper-ready .summary-table th,
    body.invoice-paper-ready .summary-table td {
        line-height: 1.1;
    }

    body.invoice-paper-ready .payment-table .summary-label {
        width: 54%;
        font-size: 6.3px;
    }

    body.invoice-paper-ready .payment-table .summary-value {
        width: 46%;
    }

    body.invoice-paper-ready .invoice-summary-table .summary-label {
        width: 77%;
    }

    body.invoice-paper-ready .invoice-summary-table .summary-value {
        width: 23%;
    }

    body.invoice-paper-ready .invoice-summary-table .summary-sub {
        margin-inline-start: 0.3mm;
        font-size: 4.9px;
    }

    body.invoice-paper-ready .terms-box {
        margin-bottom: 1.4mm;
        padding: 0.95mm 1.25mm;
    }

    body.invoice-paper-ready .terms-title {
        margin-bottom: 0.25mm;
        font-size: 6.45px;
    }

    body.invoice-paper-ready .terms-content {
        font-size: 5.8px;
        line-height: 1.22;
    }

    body.invoice-paper-ready .signatures {
        font-size: 6.8px;
    }

    body.invoice-paper-ready .signature-label {
        margin-bottom: 1mm;
    }

    body.invoice-paper-ready .signature-line {
        min-height: 5.6mm;
        padding-top: 1.85mm;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .compact-title-block {
        padding-top: 1mm;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .compact-title {
        font-size: 11.7px;
        line-height: 1.08;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .compact-subtitle {
        margin-top: 0.45mm;
        font-size: 6.25px;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .compact-meta {
        font-size: 7.3px;
        line-height: 1.28;
        font-weight: 700;
        color: #000;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .reference-table {
        margin-bottom: 2.1mm;
        font-size: 7.45px;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .description-main {
        font-size: 7.45px;
        line-height: 1.07;
        font-weight: 700;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .description-sub {
        margin-top: 0.25mm;
        font-size: 5.8px;
        line-height: 1.02;
        font-weight: 600;
        color: #000;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .summary-grid {
        grid-template-columns: minmax(0, 0.72fr) minmax(0, 1.28fr);
        margin-bottom: 1.9mm;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .summary-table th,
    body.invoice-paper-ready.invoice-orientation-landscape .summary-table td {
        line-height: 1.12;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .reference-table td,
    body.invoice-paper-ready.invoice-orientation-landscape .summary-table td,
    body.invoice-paper-ready.invoice-orientation-landscape .summary-label,
    body.invoice-paper-ready.invoice-orientation-landscape .summary-value,
    body.invoice-paper-ready.invoice-orientation-landscape .terms-content,
    body.invoice-paper-ready.invoice-orientation-landscape .signature-line {
        font-weight: 700;
        color: #000;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .payment-table .summary-label {
        width: 52%;
        font-size: 6.7px;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .payment-table .summary-value {
        width: 48%;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .invoice-summary-table .summary-label {
        width: 81%;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .invoice-summary-table .summary-value {
        width: 19%;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .invoice-summary-table .summary-sub {
        margin-inline-start: 0.4mm;
        font-size: 5.1px;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .terms-box {
        margin-bottom: 1.75mm;
        padding: 1.05mm 1.35mm;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .terms-title {
        margin-bottom: 0.3mm;
        font-size: 6.8px;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .terms-content {
        font-size: 6.05px;
        line-height: 1.2;
        overflow-wrap: anywhere;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .signatures {
        font-size: 7.25px;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .signature-label {
        margin-bottom: 1.15mm;
    }

    body.invoice-paper-ready.invoice-orientation-landscape .signature-line {
        min-height: 6.1mm;
        padding-top: 2mm;
    }

    .micro-footer {
        margin-top: auto;
        padding-top: 2.5mm;
        border-top: 1px solid var(--line-color);
        display: flex;
        justify-content: space-between;
        gap: 3mm;
        font-size: 6.45px;
        line-height: 1.26;
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

        body.invoice-paper-ready.invoice-orientation-landscape .page {
            padding: 0;
        }
    }

    @media print {
        html,
        body {
            font-size: 9.35px;
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

        body.invoice-paper-ready.invoice-orientation-landscape .page {
            padding: 0;
        }
    }
</style>
