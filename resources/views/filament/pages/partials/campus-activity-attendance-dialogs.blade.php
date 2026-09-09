@if (
    blank($activity->attendance_sheet_id)
    && blank($activity->attendance_session_id)
)
    <dialog
        id="recurring-campus-attendance-{{ $activity->id }}"
        class="m-auto w-[calc(100%-2rem)]
               max-w-xl rounded-2xl border
               border-gray-200 bg-white p-0
               text-gray-900 shadow-2xl
               backdrop:bg-black/70
               dark:border-gray-700
               dark:bg-gray-900
               dark:text-white"
        style="z-index: 9999;"
    >
        <form
            method="POST"
            action="{{ route(
                'quezonprovinceactivities.campus-work.activities.attendance.recurring',
                $activity
            ) }}"
        >
            @csrf

            <div
                class="flex items-start justify-between
                       gap-4 border-b border-gray-200
                       px-5 py-4 dark:border-gray-700"
            >
                <div>
                    <p
                        class="text-xs font-bold uppercase
                               tracking-wide text-violet-600
                               dark:text-violet-400"
                    >
                        Recurring Attendance
                    </p>

                    <h3 class="mt-1 font-bold">
                        {{ $activity->display_title }}
                    </h3>
                </div>

                <button
                    type="button"
                    onclick="this.closest('dialog').close()"
                    class="rounded-lg px-3 py-1.5
                           font-bold text-gray-500
                           hover:bg-gray-100
                           dark:text-gray-300
                           dark:hover:bg-gray-800"
                >
                    ✕
                </button>
            </div>

            <div class="grid gap-4 p-5 md:grid-cols-2">
                <div>
                    <label class="text-sm font-bold">
                        Start Date
                    </label>

                    <input
                        type="date"
                        name="start_date"
                        required
                        value="{{ $activity->activity_date->format('Y-m-d') }}"
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-3 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                </div>

                <div>
                    <label class="text-sm font-bold">
                        End Date
                    </label>

                    <input
                        type="date"
                        name="end_date"
                        required
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-3 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                </div>

                <div>
                    <label class="text-sm font-bold">
                        Meeting Day
                    </label>

                    <select
                        name="meeting_day"
                        required
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-3 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                        @foreach ([
                            0 => 'Sunday',
                            1 => 'Monday',
                            2 => 'Tuesday',
                            3 => 'Wednesday',
                            4 => 'Thursday',
                            5 => 'Friday',
                            6 => 'Saturday',
                        ] as $day => $label)
                            <option
                                value="{{ $day }}"
                                @selected(
                                    $activity
                                        ->activity_date
                                        ->dayOfWeek
                                    === $day
                                )
                            >
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-bold">
                        Meeting Time
                    </label>

                    <input
                        type="time"
                        name="meeting_time"
                        value="{{ $activity->start_time
                            ? substr($activity->start_time, 0, 5)
                            : '' }}"
                        class="mt-2 block w-full rounded-xl
                               border border-gray-300 bg-white
                               px-4 py-3 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                </div>

                <div class="md:col-span-2">
                    <p
                        class="rounded-xl bg-violet-50
                               p-3 text-sm text-violet-800
                               dark:bg-violet-950
                               dark:text-violet-200"
                    >
                        A Session will be generated for every
                        selected weekday between the start and
                        end dates.
                    </p>
                </div>
            </div>

            <div
                class="flex justify-end gap-2
                       border-t border-gray-200
                       px-5 py-4 dark:border-gray-700"
            >
                <button
                    type="button"
                    onclick="this.closest('dialog').close()"
                    class="rounded-xl border border-gray-300
                           px-4 py-2 text-sm font-bold
                           dark:border-gray-700"
                >
                    Cancel
                </button>

                <button
                    type="submit"
                    class="rounded-xl bg-violet-600
                           px-4 py-2 text-sm font-bold
                           text-white hover:bg-violet-500"
                >
                    Generate Recurring
                </button>
            </div>
        </form>
    </dialog>

    <dialog
        id="link-campus-attendance-{{ $activity->id }}"
        class="m-auto w-[calc(100%-2rem)]
               max-w-xl rounded-2xl border
               border-gray-200 bg-white p-0
               text-gray-900 shadow-2xl
               backdrop:bg-black/70
               dark:border-gray-700
               dark:bg-gray-900
               dark:text-white"
        style="z-index: 9999;"
    >
        <form
            method="POST"
            action="{{ route(
                'quezonprovinceactivities.campus-work.activities.attendance.link',
                $activity
            ) }}"
        >
            @csrf

            <div
                class="flex items-start justify-between
                       gap-4 border-b border-gray-200
                       px-5 py-4 dark:border-gray-700"
            >
                <div>
                    <p
                        class="text-xs font-bold uppercase
                               tracking-wide text-primary-600
                               dark:text-primary-400"
                    >
                        Link Existing Attendance
                    </p>

                    <h3 class="mt-1 font-bold">
                        {{ $activity->display_title }}
                    </h3>
                </div>

                <button
                    type="button"
                    onclick="this.closest('dialog').close()"
                    class="rounded-lg px-3 py-1.5
                           font-bold text-gray-500
                           hover:bg-gray-100
                           dark:text-gray-300
                           dark:hover:bg-gray-800"
                >
                    ✕
                </button>
            </div>

            <div class="space-y-4 p-5">
                @if ($attendanceSheets->isEmpty())
                    <div
                        class="rounded-xl border
                               border-amber-200
                               bg-amber-50 p-4
                               text-sm text-amber-800
                               dark:border-amber-900
                               dark:bg-amber-950
                               dark:text-amber-100"
                    >
                        No unlinked Custom Attendance Sheets
                        are available.
                    </div>
                @else
                    <div>
                        <label class="text-sm font-bold">
                            Attendance Sheet
                        </label>

                        <select
                            name="attendance_sheet_id"
                            required
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950"
                        >
                            <option value="">
                                Select Attendance Sheet
                            </option>

                            @foreach ($attendanceSheets as $sheet)
                                <option value="{{ $sheet->id }}">
                                    {{ $sheet->title }}
                                    —
                                    {{ $sheet->is_one_time
                                        ? 'One-time'
                                        : 'Recurring' }}
                                    @if ($sheet->locality)
                                        — {{ $sheet->locality }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="text-sm font-bold">
                            Exact Session
                            <span class="font-normal text-gray-500">
                                (optional)
                            </span>
                        </label>

                        <select
                            name="attendance_session_id"
                            class="mt-2 block w-full rounded-xl
                                   border border-gray-300 bg-white
                                   px-4 py-3 text-sm
                                   dark:border-gray-700
                                   dark:bg-gray-950"
                        >
                            <option value="">
                                Series / no exact Session
                            </option>

                            @foreach ($attendanceSheets as $sheet)
                                @if ($sheet->sessions->isNotEmpty())
                                    <optgroup label="{{ $sheet->title }}">
                                        @foreach ($sheet->sessions as $session)
                                            <option value="{{ $session->id }}">
                                                {{ $session->session_date?->format('M d, Y') }}

                                                @if ($session->sessionTimeLabel())
                                                    · {{ $session->sessionTimeLabel() }}
                                                @endif
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>

                        <p
                            class="mt-2 text-xs text-gray-500
                                   dark:text-gray-400"
                        >
                            Leave blank for a recurring series.
                            One-time sheets automatically link
                            their single Session.
                        </p>
                    </div>
                @endif
            </div>

            <div
                class="flex justify-end gap-2
                       border-t border-gray-200
                       px-5 py-4 dark:border-gray-700"
            >
                <button
                    type="button"
                    onclick="this.closest('dialog').close()"
                    class="rounded-xl border border-gray-300
                           px-4 py-2 text-sm font-bold
                           dark:border-gray-700"
                >
                    Cancel
                </button>

                @if ($attendanceSheets->isNotEmpty())
                    <button
                        type="submit"
                        class="rounded-xl bg-primary-600
                               px-4 py-2 text-sm font-bold
                               text-white hover:bg-primary-500"
                    >
                        Link Attendance
                    </button>
                @endif
            </div>
        </form>
    </dialog>
@endif
