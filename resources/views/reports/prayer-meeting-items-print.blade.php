<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Prayer Meeting Items</title>

    <style>
        @page {
            size: A4;
            margin: 18mm;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
            font-size: 12pt;
            line-height: 1.35;
        }

        .no-print {
            margin-bottom: 16px;
        }

        @media print {
            .no-print {
                display: none;
            }
        }

        .header {
            text-align: center;
            margin-bottom: 28px;
        }

        .header .locality {
            font-weight: 700;
            text-transform: uppercase;
        }

        .header h1 {
            margin: 4px 0;
            font-size: 18pt;
            text-transform: uppercase;
        }

        .meta {
            font-size: 10pt;
        }

        .line {
            display: grid;
            grid-template-columns: 34px 1fr;
            column-gap: 6px;
            align-items: start;
            margin-bottom: 5px;
            white-space: normal;
            page-break-inside: avoid;
        }

        .marker {
            font-weight: 700;
            margin-right: 0;
            white-space: nowrap;
        }

        .content {
            white-space: pre-line;
        }

        .roman {
            margin-top: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .letter {
            margin-left: 16px;
            font-weight: 600;
        }

        .number {
            margin-left: 32px;
        }

        .lower_roman {
            margin-left: 48px;
        }

        .bullet {
            margin-left: 32px;
        }

        .plain {
            margin-left: 34px;
            grid-template-columns: 0 1fr;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()">
            Print / Save as PDF
        </button>
    </div>

    <div class="header">
        <div class="locality">
            {{ $localityLabel }}
        </div>

        <h1>
            Prayer Meeting Items
        </h1>

        <div class="meta">
            {{ $meetingSchedule }}
        </div>
    </div>

    @forelse ($lines as $line)
        <div class="line {{ $line->line_type }}">
            <span class="marker">
                {{ $line->marker }}
            </span>

            <span class="content">
                {{ $line->content }}
            </span>
        </div>
    @empty
        <p style="text-align: center; color: #777;">
            No prayer items encoded yet.
        </p>
    @endforelse

    <script>
        window.addEventListener('load', function () {
            setTimeout(function () {
                window.print();
            }, 300);
        });
    </script>
</body>
</html>
