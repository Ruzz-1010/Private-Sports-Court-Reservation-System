{{-- Print layout for the generated paper.

     The layout deliberately reproduces the instructor's reference document
     (`SYSIN_Private-Sports-Court-Reservation-System.pdf`) rather than inventing
     a house style: a Times serif face, centred chapter openings, justified body
     paragraphs with a first-line indent, plain black-ruled tables with no
     shading, and no running page numbers.

     dompdf has no flexbox or grid, so spacing is produced with block margins
     only. The build runs offline and `setIsRemoteEnabled(false)` is set on the
     renderer, so only fonts already present locally are usable: the Times core
     faces for the running text and the bundled DejaVu faces for code. --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            size: A4;
            margin: 27mm 30mm 25mm 30mm;
        }

        body {
            font-family: "Times New Roman", Times, "DejaVu Serif", serif;
            font-size: 12pt;
            line-height: 1.55;
            color: #000000;
        }

        /* ---------------------------------------------------------------
           Title sheet. It is wrapped in its own block so it can be centred
           and spaced as one unit while the chapters underneath keep the
           running body style.
           --------------------------------------------------------------- */
        .title-page {
            text-align: center;
        }

        .title-page h1 {
            page-break-before: avoid;
            font-size: 14pt;
            line-height: 1.5;
            margin: 0 0 18px 0;
        }

        /* Indenting is a property of running body text only; the title sheet
           and every non-paragraph element must opt out explicitly. The
           alignment override matters just as much: `p` below sets justify,
           which would otherwise win over the inherited centring and leave the
           sheet flush left. */
        .title-page p {
            text-align: center;
            text-indent: 0;
            margin: 0 0 12px 0;
        }

        .title-page figure {
            margin: 10mm 0 22px 0;
        }

        /* The reference sheet groups Project Title / Course, then Prepared by /
           names, then Instructor, with a visibly larger gap before each new
           group than between the lines inside one. nth-of-type is stable here
           because the wrapper's children are fixed by the Markdown source. */
        .title-page > p:nth-of-type(4) {
            margin-top: 11mm;
        }

        .title-page > p:nth-of-type(10) {
            margin-top: 9mm;
        }

        /* The date belongs at the foot of the sheet. A single large top
           margin reproduces the reference document's spacing without
           needing an absolutely positioned element dompdf cannot place. */
        .title-page > p:last-child {
            margin-top: 58mm;
        }

        /* ---------------------------------------------------------------
           Chapter openings and section headings.
           --------------------------------------------------------------- */
        h1 {
            font-size: 13.5pt;
            font-weight: bold;
            text-align: center;
            margin: 0;
            line-height: 1.4;
            page-break-before: always;
            page-break-after: avoid;
        }

        /* Chapter headings in the reference are two centred lines: the
           chapter number, then the chapter name in capitals. */
        .chapter-label {
            display: block;
        }

        .chapter-name {
            display: block;
            text-transform: uppercase;
            margin-top: 22px;
        }

        h2 {
            font-size: 12pt;
            font-weight: bold;
            margin: 22px 0 9px 0;
            page-break-after: avoid;
        }

        h3 {
            font-size: 12pt;
            font-weight: bold;
            margin: 17px 0 7px 0;
            page-break-after: avoid;
        }

        h4 {
            font-size: 12pt;
            font-weight: bold;
            margin: 15px 0 6px 0;
            page-break-after: avoid;
        }

        /* ---------------------------------------------------------------
           Running text: justified, first line indented, generous leading.
           The indent is applied to `p` alone rather than inherited, so
           headings, table cells, and list items stay flush.
           --------------------------------------------------------------- */
        p {
            margin: 0 0 13px 0;
            text-align: justify;
            text-indent: 0.5in;
        }

        strong { font-weight: bold; }

        code {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 9pt;
        }

        pre {
            font-family: "DejaVu Sans Mono", monospace;
            font-size: 7pt;
            line-height: 1.28;
            border: 1px solid #000000;
            padding: 6px 8px;
            margin: 10px 0;
            white-space: pre;
            page-break-inside: avoid;
        }

        pre code {
            background: none;
            padding: 0;
            font-size: inherit;
        }

        /* ---------------------------------------------------------------
           Tables follow the reference exactly: solid black rules on every
           edge, a bold centred header, and no fill behind any cell.
           --------------------------------------------------------------- */
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 12px 0;
            font-size: 11pt;
        }

        th, td {
            border: 1px solid #000000;
            padding: 5px 7px;
            text-align: justify;
            vertical-align: top;
        }

        th {
            font-weight: bold;
            text-align: center;
        }

        tr { page-break-inside: avoid; }

        ul, ol {
            margin: 0 0 13px 0;
            padding-left: 26px;
        }

        li {
            margin-bottom: 5px;
            text-align: justify;
        }

        blockquote {
            margin: 10px 0;
            padding-left: 16px;
        }

        /* The reference document uses no horizontal rules; the separators in
           the Markdown source are structural only. */
        hr {
            display: none;
        }

        figure {
            margin: 14px 0;
            text-align: center;
            page-break-inside: avoid;
        }

        img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
{!! $body !!}
</body>
</html>
