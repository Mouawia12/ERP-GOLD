<style>
    @page {
        size: A5 portrait;
        margin: 5mm 5mm 12mm 5mm;
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
        font-size: 10px;
        line-height: 1.3;
    }

    .page {
        width: 138mm;
        min-height: 198mm;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
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
        border-top: 1px solid #555;
    }

    .invoice-header {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 58px minmax(0, 1fr);
        column-gap: 6px;
        align-items: start;
    }

    .company-block {
        min-height: 58px;
        font-size: 8.5px;
        line-height: 1.35;
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
        width: 54px;
        height: 54px;
        object-fit: contain;
        display: block;
        margin: 0 auto 4px;
    }

    .invoice-title {
        margin: 0;
        font-size: 12px;
        font-weight: 700;
        line-height: 1.25;
    }

    .invoice-title-en {
        margin: 2px 0 0;
        font-size: 9px;
        font-weight: 700;
        line-height: 1.2;
    }

    .invoice-rule {
        margin: 5px 0 6px;
    }

    .invoice-head-meta {
        display: grid;
        grid-template-columns: 64% 36%;
        column-gap: 6px;
        align-items: start;
        margin-bottom: 6px;
    }

    .meta-table,
    .items-table,
    .totals-table,
    .payment-table,
    .carat-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    .meta-table td,
    .meta-table th,
    .items-table td,
    .items-table th,
    .totals-table td,
    .totals-table th,
    .payment-table td,
    .payment-table th,
    .carat-table td,
    .carat-table th {
        border: 1px solid #999;
        padding: 3px 4px;
        vertical-align: middle;
    }

    .meta-table th,
    .items-table th,
    .totals-table th,
    .payment-table th,
    .carat-table th {
        background: #e0e0e0;
        font-weight: 700;
    }

    .meta-table {
        font-size: 8.5px;
    }

    .meta-table th {
        width: 31%;
        text-align: right;
    }

    .meta-table td {
        text-align: left;
    }

    .qr-box {
        width: 100%;
        min-height: 108px;
        border: 1px solid #999;
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        padding: 4px;
    }

    .qr-box img {
        width: 92px;
        height: 92px;
        object-fit: contain;
    }

    .qr-placeholder {
        font-size: 8px;
        color: #666;
    }

    .items-table {
        margin-bottom: 6px;
        font-size: 8.4px;
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
        font-size: 8.6px;
        line-height: 1.25;
        font-weight: 700;
    }

    .sub-line {
        display: block;
        margin-top: 1px;
        font-size: 7.5px;
        line-height: 1.2;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        column-gap: 6px;
        margin-bottom: 6px;
    }

    .summary-stack {
        display: flex;
        flex-direction: column;
        gap: 5px;
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
        font-size: 8.5px;
    }

    .seller-line {
        margin: 0 0 4px;
        font-size: 8.8px;
        font-weight: 700;
    }

    .notes-line {
        margin: 0 0 5px;
        padding-top: 4px;
        border-top: 1px solid #999;
        font-size: 8px;
        line-height: 1.35;
        max-height: 30px;
        overflow: hidden;
        white-space: pre-line;
    }

    .page-footer {
        margin-top: auto;
        padding-top: 5px;
        display: flex;
        justify-content: space-between;
        gap: 6px;
        font-size: 8px;
        line-height: 1.25;
    }

    .page-footer .footer-left {
        direction: ltr;
        text-align: left;
    }

    .no-print {
        display: none !important;
    }

    @media screen {
        body {
            padding: 8px 0 20px;
            background: #f3f4f6;
        }

        .page {
            background: #fff;
            padding: 4mm;
            box-shadow: 0 0 0 1px #d4d4d8;
        }
    }

    @media print {
        html,
        body {
            font-size: 9.4px;
        }

        .page {
            width: auto;
            min-height: auto;
        }
    }
</style>
