<x-filament-panels::page>
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>

    @php
        $terms = $this->terms();
        $selectedTerm = $this->selectedTerm();
        $selectedType = $this->selectedType();
        $activities = $this->activities();
        $typeOptions = $this->activityTypeOptions();
        $schoolOptions = $this->schoolOptions();
        $localityOptions = $this->localityOptions();
    @endphp

    <div class="min-w-0 space-y-6">

        {{-- Header --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-primary-200 bg-primary-50 p-5 shadow-sm dark:border-primary-900 dark:bg-primary-950 sm:p-6">
            <p class="text-sm font-bold uppercase tracking-wide text-primary-600 dark:text-primary-300">
                Campus Work
            </p>

            <h2 class="mt-2 break-words text-2xl font-bold text-gray-900 dark:text-white sm:text-3xl">
                Campus Activities
            </h2>

            <p class="mt-2 max-w-3xl text-sm text-gray-600 dark:text-gray-300">
                Record campus visitations, campus meeting schedules, Bible pursuits, and other campus-related activities.
            </p>
        </div>

        {{-- Success messages --}}
        @if (session('campus_activity_created'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Campus activity created successfully.</p>
            </div>
        @endif

        @if (session('campus_activity_updated'))
            <div class="rounded-2xl border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-900 dark:bg-green-950 dark:text-green-100">
                <p class="font-bold">Campus activity updated successfully.</p>
            </div>
        @endif

        @if (session('campus_activity_deleted'))
            <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                <p class="font-bold">Campus activity deleted.</p>
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-100">
                <p class="font-bold">Please fix the following:</p>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Create Activity --}}
        <details class="min-w-0 overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm dark:border-emerald-900 dark:bg-emerald-950">
            <summary class="cursor-pointer px-5 py-4 text-lg font-bold text-emerald-900 hover:bg-emerald-100 dark:text-emerald-100 dark:hover:bg-emerald-900">
                Add Campus Activity
            </summary>

            <form
                method="POST"
                action="{{ route('quezonprovinceactivities.campus-work.activities.store') }}"
                class="grid min-w-0 gap-4 border-t border-emerald-200 p-5 dark:border-emerald-900 md:grid-cols-2"
                x-data="{ activityType: @js(old('activity_type', 'Campus Visitation')) }"
            >
                @csrf

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Activity Type
                    </label>

                    <select
                        name="activity_type"
                        x-model="activityType"
                        required
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div
                    x-cloak
                    x-show="activityType === 'Other Activity'"
                >
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Other Activity Name
                    </label>

                    <input
                        type="text"
                        name="other_activity_name"
                        value="{{ old('other_activity_name') }}"
                        placeholder="Enter activity name..."
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Title <span class="font-normal opacity-70">(optional)</span>
                    </label>

                    <input
                        type="text"
                        name="title"
                        value="{{ old('title') }}"
                        placeholder="Example: SLSU Bible Pursuit with Students"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Academic Term
                    </label>

                    <select
                        name="campus_work_term_id"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">No academic term</option>

                        @foreach ($terms as $term)
                            <option
                                value="{{ $term->id }}"
                                @selected(
                                    (string) old(
                                        'campus_work_term_id',
                                        $selectedTerm?->id
                                    ) === (string) $term->id
                                )
                            >
                                AY {{ $term->academic_year }}
                                · {{ $term->semester }}
                                @if ($term->is_active)
                                    · Active
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Activity Date
                    </label>

                    <input
                        type="date"
                        name="activity_date"
                        value="{{ old('activity_date', now()->format('Y-m-d')) }}"
                        required
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Start Time
                    </label>

                    <input
                        type="time"
                        name="start_time"
                        value="{{ old('start_time') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        End Time
                    </label>

                    <input
                        type="time"
                        name="end_time"
                        value="{{ old('end_time') }}"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        School / Campus
                    </label>

                    <input
                        type="text"
                        name="school_campus"
                        value="{{ old('school_campus') }}"
                        list="campus-school-options"
                        placeholder="Enter or select school..."
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >

                    <datalist id="campus-school-options">
                        @foreach ($schoolOptions as $school)
                            <option value="{{ $school }}"></option>
                        @endforeach
                    </datalist>
                </div>

                <div>
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Locality
                    </label>

                    <select
                        name="locality_id"
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                        <option value="">Not specified</option>

                        @foreach ($localityOptions as $group => $options)
                            <optgroup label="{{ $group }}">
                                @foreach ($options as $localityId => $locality)
                                    <option
                                        value="{{ $localityId }}"
                                        @selected(
                                            (string) old('locality_id')
                                            === (string) $localityId
                                        )
                                    >
                                        {{ $locality }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Venue
                    </label>

                    <input
                        type="text"
                        name="venue"
                        value="{{ old('venue') }}"
                        placeholder="Room, building, meeting place, or address..."
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="block text-sm font-bold text-emerald-900 dark:text-emerald-100">
                        Description / Notes
                    </label>

                    <textarea
                        name="description"
                        rows="4"
                        placeholder="Optional notes about the activity..."
                        class="mt-2 block w-full rounded-xl border border-emerald-200 bg-white px-4 py-3 text-sm text-gray-900 dark:border-emerald-900 dark:bg-gray-950 dark:text-white"
                    >{{ old('description') }}</textarea>
                </div>

                <div class="md:col-span-2">
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-emerald-600 px-5 py-3 text-sm font-bold text-white hover:bg-emerald-500 sm:w-auto"
                    >
                        Create Campus Activity
                    </button>
                </div>
            </form>
        </details>

        {{-- Filters --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <h3 class="font-bold text-gray-900 dark:text-white">
                Activity Filters
            </h3>

            <div class="mt-4 space-y-4">
                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Academic Term
                    </p>

                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach ($terms as $term)
                            <a
                                href="{{ $this->termUrl($term) }}"
                                @class([
                                    'rounded-full px-4 py-2 text-sm font-bold',
                                    'bg-primary-600 text-white' => $selectedTerm?->id === $term->id,
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200' => $selectedTerm?->id !== $term->id,
                                ])
                            >
                                AY {{ $term->academic_year }}
                                · {{ $term->semester }}
                            </a>
                        @endforeach
                    </div>
                </div>

                <div>
                    <p class="text-xs font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                        Activity Type
                    </p>

                    <div class="mt-2 flex flex-wrap gap-2">
                        <a
                            href="{{ $this->typeUrl('') }}"
                            @class([
                                'rounded-full px-4 py-2 text-sm font-bold',
                                'bg-primary-600 text-white' => $selectedType === '',
                                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200' => $selectedType !== '',
                            ])
                        >
                            All Types
                        </a>

                        @foreach ($typeOptions as $value => $label)
                            <a
                                href="{{ $this->typeUrl($value) }}"
                                @class([
                                    'rounded-full px-4 py-2 text-sm font-bold',
                                    'bg-primary-600 text-white' => $selectedType === $value,
                                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200' => $selectedType !== $value,
                                ])
                            >
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity list --}}
        <div class="min-w-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        Activities
                    </h3>

                    <span class="rounded-full bg-primary-600 px-3 py-1 text-xs font-bold text-white">
                        {{ $activities->count() }} activity(s)
                    </span>
                </div>
            </div>

            {{-- Mobile --}}
            <div class="space-y-3 p-3 lg:hidden">
                @forelse ($activities as $activity)
                    <div class="min-w-0 overflow-hidden rounded-xl border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-950">
                        <p class="break-words text-base font-bold text-gray-900 dark:text-white">
                            {{ $activity->display_title }}
                        </p>

                        <p class="mt-1 text-sm font-semibold text-primary-600 dark:text-primary-400">
                            {{ $activity->activity_type_label }}
                        </p>

                        <div class="mt-3 space-y-1 text-sm text-gray-600 dark:text-gray-300">
                            <p>
                                <strong>Date:</strong>
                                {{ $activity->activity_date->format('M d, Y') }}
                            </p>

                            <p>
                                <strong>Time:</strong>
                                {{ $activity->time_label }}
                            </p>

                            <p>
                                <strong>School:</strong>
                                {{ $activity->school_campus ?: 'Not specified' }}
                            </p>

                            <p>
                                <strong>Locality:</strong>
                                {{ $activity->locality ?: 'Not specified' }}
                            </p>
                        </div>

                        <div class="mt-4 flex flex-wrap gap-2">
                            <button
                                type="button"
                                onclick="document.getElementById('edit-campus-activity-{{ $activity->id }}').showModal()"
                                class="rounded-lg bg-primary-600 px-3 py-2 text-xs font-bold text-white hover:bg-primary-500"
                            >
                                Edit
                            </button>

                            @if (auth()->user()?->canDeleteRecords())
                                <form
                                    method="POST"
                                    action="{{ route('quezonprovinceactivities.campus-work.activities.destroy', $activity) }}"
                                    onsubmit="return confirm('Delete this campus activity permanently?');"
                                >
                                    @csrf
                                    @method('DELETE')

                                    <button
                                        type="submit"
                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-bold text-white hover:bg-red-500"
                                    >
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        No campus activities found for the selected filters.
                    </div>
                @endforelse
            </div>

            {{-- Desktop --}}
            <div class="hidden overflow-x-auto lg:block">
                <table class="w-full min-w-[1050px] divide-y divide-gray-200 text-sm dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-950">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Date</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Activity</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Type</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Time</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">School / Campus</th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700 dark:text-gray-200">Locality</th>
                            <th class="px-4 py-3 text-right font-semibold text-gray-700 dark:text-gray-200">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-200 bg-white dark:divide-gray-700 dark:bg-gray-900">
                        @forelse ($activities as $activity)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $activity->activity_date->format('M d, Y') }}
                                </td>

                                <td class="max-w-[280px] break-words px-4 py-3 font-bold text-gray-900 dark:text-white">
                                    {{ $activity->display_title }}
                                </td>

                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $activity->activity_type_label }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $activity->time_label }}
                                </td>

                                <td class="max-w-[240px] break-words px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $activity->school_campus ?: 'Not specified' }}
                                </td>

                                <td class="px-4 py-3 text-gray-600 dark:text-gray-300">
                                    {{ $activity->locality ?: 'Not specified' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            type="button"
                                            onclick="document.getElementById('edit-campus-activity-{{ $activity->id }}').showModal()"
                                            class="rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-primary-500"
                                        >
                                            Edit
                                        </button>

                                        @if (auth()->user()?->canDeleteRecords())
                                            <form
                                                method="POST"
                                                action="{{ route('quezonprovinceactivities.campus-work.activities.destroy', $activity) }}"
                                                onsubmit="return confirm('Delete this campus activity permanently?');"
                                            >
                                                @csrf
                                                @method('DELETE')

                                                <button
                                                    type="submit"
                                                    class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-bold text-white hover:bg-red-500"
                                                >
                                                    Delete
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No campus activities found for the selected filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Edit dialogs --}}
        @foreach ($activities as $activity)
            <dialog
                id="edit-campus-activity-{{ $activity->id }}"
                class="m-auto w-[calc(100%-2rem)] max-w-2xl rounded-2xl border border-gray-200 bg-white p-0 text-gray-900 shadow-2xl backdrop:bg-black/70 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                style="z-index: 9999;"
            >
                <form
                    method="POST"
                    action="{{ route('quezonprovinceactivities.campus-work.activities.update', $activity) }}"
                    class="min-w-0"
                >
                    @csrf
                    @method('PATCH')

                    <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                        <div class="min-w-0">
                            <p class="text-xs font-bold uppercase tracking-wide text-primary-600 dark:text-primary-400">
                                Edit Campus Activity
                            </p>

                            <h3 class="mt-1 break-words text-lg font-bold">
                                {{ $activity->display_title }}
                            </h3>
                        </div>

                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="shrink-0 rounded-lg px-3 py-1.5 font-bold text-gray-500 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                        >
                            ✕
                        </button>
                    </div>

                    <div
                        class="grid max-h-[70vh] gap-4 overflow-y-auto p-5 md:grid-cols-2"
                        x-data="{ activityType: @js($activity->activity_type) }"
                    >
                        <div>
                            <label class="block text-sm font-bold">
                                Activity Type
                            </label>

                            <select
                                name="activity_type"
                                x-model="activityType"
                                required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                                @foreach ($typeOptions as $value => $label)
                                    <option value="{{ $value }}">
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div
                            x-cloak
                            x-show="activityType === 'Other Activity'"
                        >
                            <label class="block text-sm font-bold">
                                Other Activity Name
                            </label>

                            <input
                                type="text"
                                name="other_activity_name"
                                value="{{ $activity->other_activity_name }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold">
                                Title
                            </label>

                            <input
                                type="text"
                                name="title"
                                value="{{ $activity->title }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold">
                                Academic Term
                            </label>

                            <select
                                name="campus_work_term_id"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                                <option value="">No academic term</option>

                                @foreach ($terms as $term)
                                    <option
                                        value="{{ $term->id }}"
                                        @selected($activity->campus_work_term_id === $term->id)
                                    >
                                        AY {{ $term->academic_year }}
                                        · {{ $term->semester }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold">
                                Activity Date
                            </label>

                            <input
                                type="date"
                                name="activity_date"
                                value="{{ $activity->activity_date->format('Y-m-d') }}"
                                required
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold">
                                Start Time
                            </label>

                            <input
                                type="time"
                                name="start_time"
                                value="{{ $activity->start_time ? substr($activity->start_time, 0, 5) : '' }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold">
                                End Time
                            </label>

                            <input
                                type="time"
                                name="end_time"
                                value="{{ $activity->end_time ? substr($activity->end_time, 0, 5) : '' }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold">
                                School / Campus
                            </label>

                            <input
                                type="text"
                                name="school_campus"
                                value="{{ $activity->school_campus }}"
                                list="campus-school-options"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-bold">
                                Locality
                            </label>

                            <select
                                name="locality_id"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                                <option value="">Not specified</option>

                                @foreach ($localityOptions as $group => $options)
                                    <optgroup label="{{ $group }}">
                                        @foreach ($options as $localityId => $locality)
                                            <option
                                                value="{{ $localityId }}"
                                                @selected(
                                                    (int) $activity->locality_id
                                                    === (int) $localityId
                                                )
                                            >
                                                {{ $locality }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold">
                                Venue
                            </label>

                            <input
                                type="text"
                                name="venue"
                                value="{{ $activity->venue }}"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-sm font-bold">
                                Description / Notes
                            </label>

                            <textarea
                                name="description"
                                rows="5"
                                class="mt-2 block w-full rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm dark:border-gray-700 dark:bg-gray-950"
                            >{{ $activity->description }}</textarea>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-2 border-t border-gray-200 px-5 py-4 dark:border-gray-700 sm:flex-row sm:justify-end">
                        <button
                            type="button"
                            onclick="this.closest('dialog').close()"
                            class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-bold dark:border-gray-700"
                        >
                            Cancel
                        </button>

                        <button
                            type="submit"
                            class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-bold text-white hover:bg-primary-500"
                        >
                            Save Changes
                        </button>
                    </div>
                </form>
            </dialog>
        @endforeach
    </div>
</x-filament-panels::page>
