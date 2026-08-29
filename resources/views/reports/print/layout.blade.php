<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} - Tambak Pro</title>
    <style>
        @page {
            size: A4 landscape;
            margin: 10mm 12mm;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html,
        body {
            padding: 0;
            background: #e5e5e5;
            color: #171717;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            line-height: 1.4;
        }

        body {
            margin: 0;
        }

        .screen-actions {
            display: flex;
            width: 100%;
            max-width: 1120px;
            margin: 18px auto 0;
            gap: 8px;
        }

        .screen-actions a,
        .screen-actions button {
            display: inline-block;
            border: 1px solid #a3a3a3;
            border-radius: 5px;
            background: #fff;
            color: #171717;
            padding: 8px 14px;
            font: inherit;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .screen-actions button {
            background: #171717;
            color: #fff;
        }

        .document {
            width: calc(100% - 32px);
            max-width: 1120px;
            min-height: 720px;
            margin: 12px auto 24px;
            padding: 38px;
            background: #fff;
        }

        .identity {
            margin: 0 0 3px;
            color: #404040;
            font-size: 8pt;
            font-weight: 700;
            letter-spacing: 1.2px;
        }

        h1 {
            margin: 0;
            font-size: 18pt;
            line-height: 1.15;
        }

        .description {
            margin: 5px 0 0;
            color: #525252;
        }

        .generated {
            margin: 12px 0 0;
            font-size: 8pt;
        }

        .section {
            margin-top: 17px;
        }

        .section-title {
            margin: 0 0 7px;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .filter-table,
        .summary-table,
        .report-table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
        }

        .filter-table {
            border-top: 1px solid #d4d4d4;
            border-bottom: 1px solid #d4d4d4;
        }

        .filter-table th,
        .filter-table td {
            padding: 4px 8px 4px 0;
            vertical-align: top;
            text-align: left;
        }

        .filter-table th {
            width: 105px;
            color: #525252;
            font-weight: 400;
        }

        .summary-table {
            table-layout: fixed;
        }

        .summary-table td {
            border: 1px solid #d4d4d4;
            padding: 8px 10px;
            vertical-align: top;
        }

        .summary-label {
            display: block;
            color: #525252;
            font-size: 7.5pt;
        }

        .summary-value {
            display: block;
            margin-top: 2px;
            font-size: 11pt;
            font-weight: 700;
        }

        .notice {
            margin: 15px 0 0;
            border-left: 3px solid #737373;
            padding: 6px 9px;
            background: #f5f5f5;
            color: #404040;
            font-size: 8pt;
        }

        .table-description {
            margin: -3px 0 7px;
            color: #525252;
            font-size: 8pt;
        }

        .report-table {
            table-layout: fixed;
            font-size: 7pt;
            line-height: 1.25;
        }

        .report-scroll {
            width: 100%;
            max-width: 100%;
            overflow-x: auto;
        }

        .report-table thead {
            display: table-header-group;
        }

        .report-table tfoot {
            display: table-footer-group;
        }

        .report-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .report-table th,
        .report-table td {
            box-sizing: border-box;
            border: 1px solid #bdbdbd;
            padding: 3px 4px;
            vertical-align: top;
            white-space: normal;
            overflow-wrap: anywhere;
            word-wrap: break-word;
            word-break: break-word;
        }

        .report-table th {
            background: #eeeeee;
            font-size: 6.6pt;
            font-weight: 700;
            text-align: left;
        }

        .report-table--feeding {
            font-size: 6.6pt;
        }

        .report-table--stock {
            font-size: 6.2pt;
            line-height: 1.15;
        }

        .report-table--stock th,
        .report-table--stock td {
            padding: 2px 3px;
        }

        .report-table--stock th {
            font-size: 5.9pt;
        }

        .report-table--feeding th,
        .report-table--feeding td {
            padding-right: 3px;
            padding-left: 3px;
        }

        .report-table--feeding th {
            font-size: 6.2pt;
        }

        .align-right {
            text-align: right;
        }

        .align-center {
            text-align: center;
        }

        .empty-state {
            border: 1px solid #bdbdbd;
            padding: 20px;
            text-align: center;
            color: #525252;
        }

        .document-footer {
            margin-top: 16px;
            padding-top: 7px;
            border-top: 1px solid #d4d4d4;
            color: #737373;
            font-size: 7pt;
        }

        @media screen and (max-width: 640px) {
            .screen-actions {
                width: calc(100% - 24px);
                margin-top: 12px;
            }

            .document {
                width: calc(100% - 24px);
                margin: 12px auto;
                padding: 24px;
            }

            .summary-table tbody,
            .summary-table tr {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .summary-table td {
                display: block;
                min-width: 0;
            }

            .report-table {
                min-width: 920px;
            }
        }

        @media print {
            html,
            body {
                width: auto;
                max-width: none;
                padding: 0;
                background: #fff;
            }

            body {
                margin: 0;
            }

            .screen-actions {
                display: none !important;
            }

            .document {
                width: auto;
                max-width: none;
                min-height: 0;
                margin: 0;
                padding: 0;
            }

            .report-scroll {
                width: 100%;
                max-width: 100%;
                overflow: visible;
            }

            .report-table {
                width: 100%;
                max-width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body>
    @unless ($isPdf)
        <nav class="screen-actions" aria-label="Aksi cetak">
            <button type="button" onclick="window.print()">Cetak</button>
            <a href="{{ $backUrl }}">Kembali</a>
        </nav>
    @endunless

    <main class="document">
        @include('reports.print.report')
    </main>
</body>
</html>
