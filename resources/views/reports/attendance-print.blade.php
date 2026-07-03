<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $reportTypeLabel }} - {{ $sheetLabel }}</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            color: #111827;
            margin: 32px;
            background: #ffffff;
        }

        .no-print {
            margin-bottom: 24px;
        }

        .print-button {
            background: #111827;
            color: white;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            font-weight: bold;
            cursor: pointer;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 16px;
            margin-bottom: 20px;
        }

        .brand {
            font-size: 14px;
            font-weight: bold;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        h1 {
            margin: 8px 0 4px;
            font-size: 28px;
        }

        .meta {
            color: #4b5563;
            font-size: 14px;
            line-height: 1.5;
        }

        .summary {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 20px 0;
        }

        .card {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 12px;
        }

        .card-label {
            font-size: 12px;
            color: #6b7280;
            font-weight: bold;
        }

        .card-value {
            margin-top: 6px;
            font-size: 22px;
            font-weight: bold;
        }

        h2 {
            font-size: 18px;
            margin-top: 28px;
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 24px;
        }

        th, td {
            border: 1px solid #d1d5db;
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: #f3f4f6;
            text-align: left;
        }

        .right {
            text-align: right;
        }

        .footer {
            margin-top: 32px;
            font-size: 11px;
            color: #6b7280;
            border-top: 1px solid #d1d5db;
            padding-top: 12px;
        }

        @media print {
            body {
                margin: 12mm;
            }

            .no-print {
                display: none;
            }

            .summary {
                grid-template-columns: repeat(5, 1fr);
            }

            table {
                page-break-inside: auto;
            }

            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-button" onclick="window.print()">Print</button>
    </div>

    <div class="header">
        <div class="brand">Churches of Quezon Database</div>

        <h1>{{ $reportTypeLabel }}</h1>

        <div class="meta">
            <strong>{{ $sheetLabel }}</strong><br>
            Period: {{ $periodLabel }}<br>
            Category: {{ $categoryLabel }}<br>
            Meeting Day: {{ $meetingDayLabel }}<br>
            Generated: {{ now()->format('M d, Y h:i A') }}
        </div>
    </div>

    <div class="summary">
        <div class="card">
            <div class="card-label">Meetings</div>
            <div class="card-value">{{ $summary['meetings'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">Participants</div>
            <div class="card-value">{{ $summary['participants'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">Present</div>
            <div class="card-value">{{ $summary['present_total'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">Absent</div>
            <div class="card-value">{{ $summary['absent_total'] }}</div>
        </div>

        <div class="card">
            <div class="card-label">Rate</div>
            <div class="card-value">{{ $summary['overall_rate'] }}%</div>
        </div>
    </div>

    <h2>Report by Meeting Date</h2>

    <table>
        <thead>
            <tr>
                <th>Meeting Date</th>
                <th>Day</th>
                <th class="right">Expected</th>
                <th class="right">Present</th>
                <th class="right">Absent</th>
                <th class="right">Unmarked</th>
                <th class="right">Rate</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($meetingRows as $row)
                <tr>
                    <td>{{ $row['session']->session_date->format('M d, Y') }}</td>
                    <td>{{ $row['session']->session_date->format('l') }}</td>
                    <td class="right">{{ $row['active_participants'] }}</td>
                    <td class="right">{{ $row['present'] }}</td>
                    <td class="right">{{ $row['absent'] }}</td>
                    <td class="right">{{ $row['unmarked'] }}</td>
                    <td class="right">{{ $row['rate'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No meeting records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h2>Report by Person</h2>

    <table>
        <thead>
            <tr>
                <th>Name</th>
                <th>Locality</th>
                <th>Category</th>
                <th class="right">Expected</th>
                <th class="right">Present</th>
                <th class="right">Absent</th>
                <th class="right">Unmarked</th>
                <th class="right">Rate</th>
            </tr>
        </thead>

        <tbody>
            @forelse ($personRows as $row)
                <tr>
                    <td>{{ $row['person']?->display_name ?? 'Unknown person' }}</td>
                    <td>{{ $row['person']?->locality ?: 'No locality' }}</td>
                    <td>{{ $row['person']?->churchProfile?->category ?: 'No category' }}</td>
                    <td class="right">{{ $row['expected'] }}</td>
                    <td class="right">{{ $row['present'] }}</td>
                    <td class="right">{{ $row['absent'] }}</td>
                    <td class="right">{{ $row['unmarked'] }}</td>
                    <td class="right">{{ $row['rate'] }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">No person records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Printed from Churches of Quezon Database.
    </div>
</body>
</html>
