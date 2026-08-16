<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        AY {{ $term->academic_year }} {{ $term->semester }} - Student Nucleus
    </title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 24px;
            background: #ffffff;
            color: #111827;
            font-family: Arial, Helvetica, sans-serif;
        }

        .no-print {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            border: 0;
            border-radius: 8px;
            padding: 10px 16px;
            color: #ffffff;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .button-print {
            background: #111827;
        }

        .button-back {
            background: #4b5563;
        }

        .report {
            width: 100%;
            margin: 0 auto;
        }

        .main-title {
            margin: 0;
            border: 1px solid #9ca3af;
            background: #0b3c68;
            padding: 5px 10px;
            color: #ffffff;
            text-align: center;
            font-size: 22px;
            font-weight: 700;
        }

        .sub-title {
            margin: 0;
            border-right: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
            border-left: 1px solid #9ca3af;
            background: #ffe599;
            padding: 4px 10px;
            color: #7f6000;
            text-align: center;
            font-size: 16px;
            font-weight: 700;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 11px;
        }

        th,
        td {
            border: 1px solid #a6a6a6;
            padding: 4px 6px;
            vertical-align: middle;
            overflow-wrap: anywhere;
        }

        thead th {
            background: #ffffff;
            text-align: center;
            font-weight: 700;
        }

        .col-number {
            width: 4%;
            text-align: center;
        }

        .col-name {
            width: 19%;
        }

        .col-locality {
            width: 18%;
        }

        .col-course {
            width: 11%;
            text-align: center;
        }

        .col-grade {
            width: 10%;
            text-align: center;
        }

        .col-contact {
            width: 19%;
        }

        .col-spiritual {
            width: 19%;
        }

        .school-row td {
            background: #cfe2f3;
            padding: 3px 6px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .student-row td {
            min-height: 24px;
        }

        .student-name {
            font-weight: 500;
        }

        .center {
            text-align: center;
        }

        .contact-secondary {
            margin-top: 2px;
            color: #4b5563;
            font-size: 9px;
        }

        .spiritual-condition {
            white-space: pre-wrap;
        }

        .empty {
            padding: 30px;
            text-align: center;
            color: #6b7280;
        }

        .footer {
            margin-top: 12px;
            color: #6b7280;
            text-align: right;
            font-size: 9px;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }

        @media print {
            body {
                margin: 0;
            }

            .no-print {
                display: none !important;
            }

            .report {
                width: 100%;
            }

            thead {
                display: table-header-group;
            }

            tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .school-row {
                break-after: avoid;
                page-break-after: avoid;
            }

            .footer {
                display: none;
            }
        }
    </style>
</head>

<body>
    <div class="no-print">
        <button
            type="button"
            onclick="window.print()"
            class="button button-print"
        >
            Print Report
        </button>

        <a
            href="{{ \App\Filament\Pages\StudentNucleus::getUrl() . '?' . http_build_query(['termId' => $term->id]) }}"
            class="button button-back"
        >
            Back to Student Nucleus
        </a>
    </div>

    <main class="report">
        <h1 class="main-title">
            AY {{ str_replace('-', '–', $term->academic_year) }}
            {{ $term->semester }}
            Campus Work
        </h1>

        <h2 class="sub-title">
            Student Nucleus
        </h2>

        <table>
            <thead>
                <tr>
                    <th class="col-number">
                        No.
                    </th>

                    <th class="col-name">
                        Name of Student
                    </th>

                    <th class="col-locality">
                        Locality
                    </th>

                    <th class="col-course">
                        Course
                    </th>

                    <th class="col-grade">
                        Grade/Year<br>Level
                    </th>

                    <th class="col-contact">
                        Contact Information
                        <br>
                        <span style="font-weight: 400;">
                            (Phone Number/Email)
                        </span>
                    </th>

                    <th class="col-spiritual">
                        Spiritual Condition
                    </th>
                </tr>
            </thead>

            <tbody>
                @forelse ($groupedMembers as $school => $members)
                    <tr class="school-row">
                        <td colspan="7">
                            {{ $school }}
                        </td>
                    </tr>

                    @foreach ($members as $membership)
                        @php
                            $person = $membership->person;
                            $education = $person?->educationProfile;

                            $studentName = collect([
                                $person?->firstname,
                                $person?->middlename,
                                $person?->lastname,
                                $person?->suffix,
                            ])->filter()->implode(' ');

                            $gradeLevel = $education?->grade_level ?: '';

                            if (
                                $gradeLevel
                                && preg_match(
                                    '/(?:year|grade)\s*[-:]?\s*(\d+)/i',
                                    $gradeLevel,
                                    $matches
                                )
                            ) {
                                $gradeLevel = $matches[1];
                            }
                        @endphp

                        <tr class="student-row">
                            <td class="col-number">
                                {{ $loop->iteration }}
                            </td>

                            <td class="col-name student-name">
                                {{ $studentName }}
                            </td>

                            <td class="col-locality">
                                {{ $person?->locality ?: '' }}
                            </td>

                            <td class="col-course">
                                {{ $education?->course_strand ?: '' }}
                            </td>

                            <td class="col-grade">
                                {{ $gradeLevel }}
                            </td>

                            <td class="col-contact">
                                @if ($person?->contact_number)
                                    {{ $person->contact_number }}
                                @endif

                                @if ($person?->email)
                                    <div class="contact-secondary">
                                        {{ $person->email }}
                                    </div>
                                @endif
                            </td>

                            <td class="col-spiritual spiritual-condition">
                                {{ $membership->spiritual_condition ?: '' }}
                            </td>
                        </tr>
                    @endforeach
                @empty
                    <tr>
                        <td colspan="7" class="empty">
                            No students are assigned to this academic term.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="footer">
            Generated {{ now()->format('M d, Y h:i A') }}
            · {{ $memberships->count() }} student(s)
        </div>
    </main>
</body>
</html>
