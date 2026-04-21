@php
    $printOrientation = in_array($orientation ?? 'portrait', ['portrait', 'landscape'], true) ? $orientation : 'portrait';
    $printMargin = $margin ?? '8mm';
@endphp

<style>
    .accounting-print-report {
        direction: rtl;
        max-width: 100%;
    }

    .accounting-print-report .card,
    .accounting-print-report .card-body {
        overflow: visible;
    }

    .accounting-print-header {
        direction: rtl;
        border: 1px solid #9aa6b2;
        border-radius: 4px;
        padding: 14px 18px;
    }

    .accounting-print-title {
        display: inline-block;
        min-width: 220px;
        margin: 0 auto 8px;
        padding: 9px 18px;
        background: #dfe7ff;
        color: #25375f;
        font-size: 16px;
        font-weight: 700;
    }

    .accounting-print-meta,
    .accounting-print-company {
        color: #34405a;
        font-size: 13px;
        line-height: 1.7;
    }

    .accounting-print-table-wrap {
        max-width: 100%;
        overflow-x: auto;
    }

    .accounting-print-table {
        direction: rtl;
        text-align: center;
    }

    @media print {
        @page {
            size: A4 {{ $printOrientation }};
            margin: {{ $printMargin }};
        }

        html,
        body {
            width: auto !important;
            min-width: 0 !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            direction: rtl !important;
            overflow: visible !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        #main-header,
        #main-footer,
        #back-to-top,
        .main-header,
        .main-header-spacer,
        .main-footer,
        .app-sidebar,
        .app-sidebar__overlay,
        .accounting-print-button,
        .no-print {
            display: none !important;
        }

        .main-content.app-content,
        .main-content.app-content > .container-fluid,
        .app-content,
        .content,
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        .accounting-print-report,
        .print-report {
            position: static !important;
            inset: auto !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            color: #111827 !important;
        }

        .accounting-print-report.row,
        .accounting-print-report .row,
        .print-report.row,
        .print-report .row {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
        }

        .accounting-print-report [class*="col-"],
        .print-report [class*="col-"] {
            padding-left: 3mm !important;
            padding-right: 3mm !important;
        }

        .accounting-print-report > [class*="col-"],
        .print-report > [class*="col-"],
        .accounting-print-report .card,
        .accounting-print-report .card-body,
        .print-report .card,
        .print-report .card-body {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            border: 0 !important;
            box-shadow: none !important;
            overflow: visible !important;
        }

        .accounting-print-header {
            display: block !important;
            border: 1px solid #9aa6b2 !important;
            border-radius: 4px !important;
            padding: 6mm 7mm 5mm !important;
            margin: 0 0 5mm !important;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .accounting-print-header .row {
            display: flex !important;
            align-items: center !important;
            flex-wrap: nowrap !important;
        }

        .accounting-print-title {
            min-width: 180px !important;
            padding: 5px 12px !important;
            margin: 0 auto 4px !important;
            font-size: 13px !important;
            line-height: 1.35 !important;
            background: #dfe7ff !important;
            color: #25375f !important;
        }

        .accounting-print-meta,
        .accounting-print-company {
            font-size: 9.5px !important;
            line-height: 1.55 !important;
        }

        .accounting-print-table-wrap,
        .table-responsive,
        .hoverable-table,
        .dataTables_wrapper,
        .dataTables_scroll,
        .dataTables_scrollBody {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            height: auto !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        table.accounting-print-table {
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            border-collapse: collapse !important;
            table-layout: fixed;
            direction: rtl !important;
            color: #1f2937 !important;
            font-size: 9.4px !important;
            line-height: 1.35 !important;
            page-break-inside: auto;
        }

        table.accounting-wide-table {
            font-size: 8px !important;
        }

        table.accounting-tax-table {
            font-size: 8.7px !important;
        }

        table.accounting-print-table > thead:first-of-type {
            display: table-header-group;
        }

        table.accounting-print-table > thead:not(:first-of-type),
        table.accounting-print-table tbody,
        table.accounting-print-table tfoot {
            display: table-row-group;
        }

        table.accounting-print-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        table.accounting-print-table th,
        table.accounting-print-table td {
            border: 1px solid #d6deea !important;
            padding: 3.5px 4px !important;
            white-space: normal !important;
            word-break: break-word;
            overflow-wrap: anywhere;
            vertical-align: middle !important;
        }

        table.accounting-wide-table th,
        table.accounting-wide-table td {
            padding: 3px 3.5px !important;
        }

        table.accounting-print-table th {
            background: #dceff4 !important;
            color: #253047 !important;
            font-weight: 700 !important;
        }

        table.accounting-summary-table th:first-child,
        table.accounting-summary-table td:first-child {
            width: 42% !important;
            text-align: right !important;
        }

        table.accounting-summary-table th:not(:first-child),
        table.accounting-summary-table td:not(:first-child) {
            width: 19.333% !important;
            text-align: center !important;
        }

        table.accounting-summary-table tbody tr:last-child td {
            background: #f4f7fb !important;
            font-weight: 700 !important;
        }

        table.accounting-print-table .bg-primary,
        table.accounting-print-table .btn-primary,
        table.accounting-print-table .bg-info,
        table.accounting-print-table .bg-secondary,
        table.accounting-print-table .btn-secondary,
        table.accounting-print-table .bg-success,
        table.accounting-print-table .alert-info,
        table.accounting-print-table [class*="bg-"] {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .caret::before,
        .caret-down::before {
            display: none !important;
        }
    }
</style>
