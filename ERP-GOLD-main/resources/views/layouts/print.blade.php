<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <style>
        :root {
            --print-page-width: {{ ($printFormat ?? 'a4') === 'pos' ? '80mm' : '100%' }};
            --print-font: "Tahoma", "Arial", sans-serif;
            --print-border: #cbd5e1;
            --print-muted: #64748b;
            --print-ink: #111827;
        }

        * { box-sizing: border-box; }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #f3f4f6;
            color: var(--print-ink);
            font-family: var(--print-font);
            font-size: 12px;
            direction: rtl;
        }

        body {
            min-height: 100vh;
        }

        .print-shell {
            width: var(--print-page-width);
            max-width: 100%;
            margin: 0 auto;
            padding: 12px;
        }

        .print-page {
            width: 100%;
            margin: 0 auto;
            background: #fff;
            padding: 10mm;
            box-shadow: 0 12px 36px rgba(15, 23, 42, 0.12);
        }

        .print-report-header {
            display: grid;
            grid-template-columns: minmax(150px, 1fr) minmax(260px, 1.2fr) minmax(150px, 1fr);
            gap: 12px;
            align-items: start;
            border: 1px solid var(--print-border);
            border-radius: 6px;
            padding: 12px 14px;
            margin-bottom: 12px;
        }

        .print-report-title {
            margin: 0 0 8px;
            text-align: center;
            font-size: 18px;
            font-weight: 700;
        }

        .print-report-meta,
        .print-company-block {
            color: #334155;
            line-height: 1.7;
        }

        .print-company-block {
            text-align: right;
        }

        .print-generated-at {
            text-align: left;
            color: var(--print-muted);
            font-size: 11px;
        }

        .print-table-wrap {
            width: 100%;
            overflow-x: auto;
        }

        .print-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            direction: rtl;
        }

        .print-table th,
        .print-table td {
            border: 1px solid var(--print-border);
            padding: 6px 7px;
            text-align: center;
            vertical-align: middle;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .print-table th {
            background: #e2e8f0;
            color: #1f2937;
            font-weight: 700;
        }

        .print-table tfoot td {
            background: #1f2937;
            color: #fff;
            font-weight: 700;
        }

        .print-actions {
            position: fixed;
            left: 16px;
            bottom: 16px;
            z-index: 1000;
            display: flex;
            gap: 8px;
            padding: 10px;
            border-radius: 8px;
            background: rgba(15, 23, 42, 0.94);
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.2);
        }

        .print-actions__button,
        .print-actions__link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 82px;
            height: 36px;
            padding: 0 12px;
            border: 0;
            border-radius: 6px;
            color: #fff !important;
            font: 700 12px var(--print-font);
            text-decoration: none;
            cursor: pointer;
        }

        .print-actions__button { background: #0f766e; }
        .print-actions__link { background: #475569; }
        .print-actions__link--danger { background: #b91c1c; }
        .print-actions__link--pdf { background: #1d4ed8; }

        @media print {
            @page {
                size: {{ ($printFormat ?? 'a4') === 'pos' ? '80mm auto' : strtoupper($printFormat ?? 'a4') . ' ' . ($printOrientation ?? 'portrait') }};
                margin: {{ $pageMargin ?? '10mm' }};
            }

            html,
            body {
                width: auto !important;
                min-width: 0 !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                color: #111827 !important;
                overflow: visible !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .no-print,
            .print-actions {
                display: none !important;
                visibility: hidden !important;
            }

            .print-shell,
            .print-page {
                width: 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
                background: #fff !important;
                overflow: visible !important;
            }

            .print-report-header {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            .print-table-wrap {
                overflow: visible !important;
            }

            .print-table {
                page-break-inside: auto;
            }

            .print-table thead {
                display: table-header-group;
            }

            .print-table tfoot {
                display: table-footer-group;
            }

            .print-table tr,
            .print-table td,
            .print-table th {
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
    @stack('styles')
</head>
<body class="{{ $bodyClass ?? '' }}">
    <main class="print-shell">
        @yield('content')
    </main>

    @unless($hidePrintActions ?? false)
        @include('prints.partials.actions', [
            'backUrl' => $backUrl ?? null,
            'pdfUrl' => $pdfUrl ?? null,
        ])
    @endunless

    @include('prints.partials.auto_print')
    @stack('scripts')
</body>
</html>
