{{-- Print layout for the generated paper. dompdf has no flexbox or grid, so
     the document is laid out with tables and block elements only. Fonts are
     restricted to the DejaVu faces bundled with dompdf, because the build must
     work offline. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm 16mm 18mm 16mm;
        }

        @page {
            @bottom-center {
                content: counter(page) " of " counter(pages);
                font-family: "DejaVu Sans", sans-serif;
                font-size: 8pt;
                color: #64748b;
            }
        }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 9.8pt;
            line-height: 1.5;
            color: #111827;
        }

        h1 {
            font-size: 15.5pt;
            color: #000000;
            border-bottom: 2px solid #000000;
            padding-bottom: 5px;
            margin: 0 0 14px 0;
            page-break-before: always;
            page-break-after: avoid;
        }

        /* Without this the opening title would push itself onto page two and
           leave the first sheet blank. */
        h1:first-of-type {
            page-break-before: avoid;
        }

        body > *:last-child {
            page-break-after: avoid;
        }

        h2 {
            font-size: 12.5pt;
            color: #000000;
            border-bottom: 1px solid #b8c2cc;
            padding-bottom: 3px;
            margin: 16px 0 8px 0;
            page-break-after: avoid;
        }

        h3 {
            font-size: 10.8pt;
            margin: 12px 0 6px 0;
            page-break-after: avoid;
        }

        h4 {
            font-size: 9.8pt;
            margin: 10px 0 5px 0;
            page-break-after: avoid;
        }

        p {
            margin: 0 0 7px 0;
            text-align: justify;
        }

        strong { color: #000000; }

        code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 8.2pt;
            background: #f1f5f9;
            padding: 0 2px;
        }

        pre {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 6.4pt;
            line-height: 1.18;
            background: #f8fafc;
            border: 0.5px solid #cbd5e1;
            border-left: 3px solid #475569;
            padding: 7px 8px;
            margin: 9px 0;
            white-space: pre;
            page-break-inside: avoid;
        }

        pre code {
            background: none;
            padding: 0;
            font-size: inherit;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin: 9px 0;
            font-size: 8.2pt;
        }

        th, td {
            border: 0.5px solid #94a3b8;
            padding: 4px 5px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background: #e2e8f0;
            font-weight: bold;
        }

        tr { page-break-inside: avoid; }

        ul, ol {
            margin: 0 0 8px 0;
            padding-left: 17px;
        }

        li { margin-bottom: 2px; }

        blockquote {
            border-left: 3px solid #94a3b8;
            margin: 8px 0;
            padding: 2px 0 2px 10px;
            color: #334155;
        }

        hr {
            border: none;
            border-top: 0.5px solid #cbd5e1;
            margin: 13px 0;
        }

        figure {
            margin: 12px 0;
            text-align: center;
            page-break-inside: avoid;
        }

        img {
            max-width: 100%;
            height: auto;
            border: 0.5px solid #cbd5e1;
        }
    </style>
</head>
<body>
{!! $body !!}
</body>
</html>