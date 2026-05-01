<style>
    .erp-print-report {
        direction: rtl;
    }

    @media print {
        @page {
            size: A4 landscape;
            margin: 5mm;
        }

        html,
        body {
            width: auto !important;
            min-width: 0 !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
            background: #fff !important;
            direction: rtl !important;
        }

        #main-header,
        #main-footer,
        #back-to-top,
        #global-loader,
        .main-header-spacer,
        .main-header,
        .main-footer,
        .app-sidebar,
        .app-sidebar__overlay,
        .global-loader,
        .loader-img,
        .no-print,
        .modal,
        .modal-backdrop,
        .dropdown-menu,
        .tooltip,
        .popover,
        .dataTables_length,
        .dataTables_filter,
        .dataTables_info,
        .dataTables_paginate,
        .dt-buttons,
        .dataTables_wrapper > .row:first-child,
        .dataTables_wrapper > .row:last-child {
            display: none !important;
            visibility: hidden !important;
        }

        .main-content.app-content,
        .main-content.app-content > .container-fluid,
        .app-content,
        .content,
        .side-app,
        .container-fluid {
            width: 100% !important;
            max-width: none !important;
            min-width: 0 !important;
            height: auto !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            position: static !important;
            inset: auto !important;
            transform: none !important;
            overflow: visible !important;
        }

        .erp-print-report {
            position: absolute !important;
            top: 0 !important;
            right: 0 !important;
            left: 0 !important;
            display: block !important;
            width: 100% !important;
            max-width: 100% !important;
            min-width: 0 !important;
            margin: 0 !important;
            padding: 0 !important;
            transform: none !important;
            overflow: visible !important;
            direction: rtl !important;
        }

        .erp-print-report > [class*="col-"] {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            padding: 0 !important;
        }

        .row,
        .row-sm {
            width: 100% !important;
            margin: 0 !important;
        }

        .col-xl-12,
        .col-lg-12,
        .col-md-12,
        .col-12 {
            width: 100% !important;
            max-width: 100% !important;
            flex: 0 0 100% !important;
            padding: 0 !important;
        }

        .card,
        .card-body,
        .card-header,
        .shadow,
        .shadow-sm,
        .shadow-lg {
            box-shadow: none !important;
            background: #fff !important;
        }

        .card {
            width: 100% !important;
            margin: 0 0 3mm !important;
            border: 0 !important;
        }

        .card-body {
            padding: 0 !important;
        }

        .card-header {
            margin: 0 0 3mm !important;
            padding: 3mm !important;
            border: 1px solid #aeb8cc !important;
            break-after: avoid !important;
            page-break-after: avoid !important;
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        .card-header > .row {
            display: flex !important;
            flex-wrap: nowrap !important;
            align-items: flex-start !important;
        }

        .card-header > .row > .col-3 {
            flex: 0 0 25% !important;
            max-width: 25% !important;
            padding: 0 2mm !important;
        }

        .card-header > .row > .col-4 {
            flex: 0 0 33.333% !important;
            max-width: 33.333% !important;
            padding: 0 2mm !important;
        }

        .card-header > .row > .col-6 {
            flex: 0 0 50% !important;
            max-width: 50% !important;
            padding: 0 2mm !important;
        }

        .alert {
            margin: 0 0 2mm !important;
            padding: 1.5mm 3mm !important;
            border: 0 !important;
            border-radius: 0 !important;
            font-size: 12px !important;
            line-height: 1.35 !important;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6,
        p {
            margin-top: 1mm !important;
            margin-bottom: 1mm !important;
            line-height: 1.35 !important;
        }

        .table-responsive,
        .hoverable-table,
        .dataTables_wrapper,
        .dataTables_scroll,
        .dataTables_scrollBody {
            width: 100% !important;
            max-width: none !important;
            height: auto !important;
            max-height: none !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: visible !important;
        }

        table {
            width: 100% !important;
            max-width: none !important;
            margin: 0 !important;
            border-collapse: collapse !important;
            table-layout: auto !important;
            direction: rtl !important;
            page-break-inside: auto !important;
        }

        thead {
            display: table-header-group !important;
        }

        tfoot {
            display: table-footer-group !important;
        }

        tr {
            break-inside: avoid !important;
            page-break-inside: avoid !important;
        }

        th,
        td {
            padding: 1.4mm 1.1mm !important;
            border: 1px solid #c7cedb !important;
            color: #111827 !important;
            background: #fff !important;
            font-size: 8px !important;
            line-height: 1.25 !important;
            text-align: center !important;
            vertical-align: middle !important;
            white-space: normal !important;
            word-break: break-word !important;
        }

        table.dataTable th.dtr-hidden,
        table.dataTable td.dtr-hidden,
        table.dataTable th[style*="display: none"],
        table.dataTable td[style*="display: none"] {
            display: table-cell !important;
        }

        table.dataTable > tbody > tr.child,
        table.dataTable > tbody > tr.child ul.dtr-details,
        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > td:first-child::before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th:first-child::before {
            display: none !important;
            content: "" !important;
        }

        th,
        thead th {
            background: #eef3ff !important;
            font-weight: 700 !important;
        }

        tfoot td,
        tfoot th,
        tr.bg-primary td,
        tr.bg-primary th,
        .bg-primary,
        .bg-info,
        .bg-secondary,
        [class*="bg-"] {
            background: #dbe6ff !important;
            color: #111827 !important;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        img {
            max-width: 26mm !important;
            max-height: 22mm !important;
            object-fit: contain !important;
        }

        a {
            color: #111827 !important;
            text-decoration: none !important;
        }
    }
</style>
