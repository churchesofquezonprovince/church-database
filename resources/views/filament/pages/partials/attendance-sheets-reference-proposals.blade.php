@php
    $pendingReferenceProposals =
        $this->pendingMeetingReferenceProposals();

    $pendingReferenceProposalsByResponse =
        $pendingReferenceProposals->groupBy(
            'attendance_meeting_response_id'
        );

    $referenceProvinceOptions =
        $this->meetingReferenceProposalProvinceOptions();

    $referenceCountryOptions =
        $this->meetingReferenceProposalCountryOptions();
@endphp

@if ($pendingReferenceProposals->isNotEmpty())
    <details
        open
        class="min-w-0 overflow-hidden rounded-2xl
               border border-violet-200 bg-violet-50
               shadow-sm
               dark:border-violet-900
               dark:bg-violet-950"
    >
        <summary
            class="cursor-pointer px-4 py-4
                   hover:bg-violet-100
                   dark:hover:bg-violet-900
                   sm:px-6"
        >
            <span
                class="ml-2 inline-flex
                       w-[calc(100%-2rem)]
                       items-center justify-between
                       gap-3 align-middle"
            >
                <span
                    class="text-lg font-bold
                           text-violet-950
                           dark:text-violet-100"
                >
                    Pending Reference Proposals
                </span>

                <span
                    class="rounded-full bg-violet-600
                           px-2.5 py-1 text-xs
                           font-bold text-white"
                >
                    {{ $pendingReferenceProposals->count() }}
                </span>
            </span>
        </summary>

        <div
            class="border-t border-violet-200
                   p-4 dark:border-violet-900
                   sm:p-6"
        >
            <p
                class="text-sm leading-6
                       text-violet-800
                       dark:text-violet-200"
            >
                Participants submitted a School / Campus or
                Locality that was not available in the configured
                database. Use an existing Province when appropriate,
                or create the submitted Province during admin review.
                Public submissions never create Provinces directly.
            </p>

            <div class="mt-5 space-y-5">
                @foreach (
                    $pendingReferenceProposalsByResponse
                    as $responseProposals
                )
                    @php
                        $response =
                            $responseProposals
                                ->first()
                                ?->response;
                    @endphp

                    <div
                        class="overflow-hidden rounded-xl
                               border border-violet-200
                               bg-white
                               dark:border-violet-900
                               dark:bg-gray-950"
                    >
                        <div
                            class="border-b border-violet-100
                                   px-4 py-3
                                   dark:border-violet-900"
                        >
                            <div
                                class="font-bold text-gray-900
                                       dark:text-white"
                            >
                                {{ $response?->respondent_name
                                    ?? $response?->guest_name
                                    ?? 'Unknown Respondent' }}
                            </div>

                            <div
                                class="mt-1 text-xs
                                       text-gray-500
                                       dark:text-gray-400"
                            >
                                {{ $responseProposals->count() }}
                                pending reference
                                {{ $responseProposals->count() === 1
                                    ? 'proposal'
                                    : 'proposals' }}
                            </div>
                        </div>

                        <div
                            class="divide-y divide-violet-100
                                   dark:divide-violet-900"
                        >
                            @foreach ($responseProposals as $proposal)
                                @php
                                    $identityLabel =
                                        filled($proposal->person_id)
                                            ? 'People Database'
                                            : (
                                                filled(
                                                    $proposal
                                                        ->campus_contact_id
                                                )
                                                    ? 'Campus Contact'
                                                    : (
                                                        filled(
                                                            $proposal
                                                                ->gospel_contact_id
                                                        )
                                                            ? 'Gospel Contact'
                                                            : 'Guest / no linked database identity'
                                                    )
                                            );
                                @endphp

                                <div
                                    wire:key="meeting-reference-proposal-{{ $proposal->id }}"
                                    class="p-4"
                                >
                                    <div
                                        class="grid gap-5
                                               lg:grid-cols-[minmax(0,1fr)_20rem]"
                                    >
                                        <div class="min-w-0">
                                            <div
                                                class="flex flex-wrap
                                                       items-center gap-2"
                                            >
                                                <span
                                                    class="rounded-full
                                                           bg-violet-100
                                                           px-2.5 py-1
                                                           text-xs font-bold
                                                           text-violet-800
                                                           dark:bg-violet-900
                                                           dark:text-violet-100"
                                                >
                                                    {{ $proposal->fieldLabel() }}
                                                </span>

                                                <span
                                                    class="rounded-full
                                                           bg-amber-100
                                                           px-2.5 py-1
                                                           text-xs font-bold
                                                           text-amber-800
                                                           dark:bg-amber-900
                                                           dark:text-amber-100"
                                                >
                                                    Pending
                                                </span>
                                            </div>

                                            <div
                                                class="mt-3 text-lg
                                                       font-bold
                                                       text-gray-950
                                                       dark:text-white"
                                            >
                                                {{ $proposal->proposed_label }}
                                            </div>

                                            <dl
                                                class="mt-3 grid gap-x-5
                                                       gap-y-2 text-sm
                                                       sm:grid-cols-2"
                                            >
                                                <div>
                                                    <dt
                                                        class="font-semibold
                                                               text-gray-700
                                                               dark:text-gray-300"
                                                    >
                                                        Submitted Province
                                                    </dt>

                                                    <dd
                                                        class="text-gray-600
                                                               dark:text-gray-400"
                                                    >
                                                        {{ $proposal->proposed_province_name
                                                            ?: 'Not provided' }}
                                                    </dd>
                                                </div>

                                                @if (
                                                    filled(
                                                        $proposal
                                                            ->proposed_city_municipality
                                                    )
                                                )
                                                    <div>
                                                        <dt
                                                            class="font-semibold
                                                                   text-gray-700
                                                                   dark:text-gray-300"
                                                        >
                                                            City / Municipality
                                                        </dt>

                                                        <dd
                                                            class="text-gray-600
                                                                   dark:text-gray-400"
                                                        >
                                                            {{ $proposal
                                                                ->proposed_city_municipality }}
                                                        </dd>
                                                    </div>
                                                @endif

                                                <div>
                                                    <dt
                                                        class="font-semibold
                                                               text-gray-700
                                                               dark:text-gray-300"
                                                    >
                                                        Identity
                                                    </dt>

                                                    <dd
                                                        class="text-gray-600
                                                               dark:text-gray-400"
                                                    >
                                                        {{ $identityLabel }}
                                                    </dd>
                                                </div>

                                                <div>
                                                    <dt
                                                        class="font-semibold
                                                               text-gray-700
                                                               dark:text-gray-300"
                                                    >
                                                        Proposal
                                                    </dt>

                                                    <dd
                                                        class="text-gray-600
                                                               dark:text-gray-400"
                                                    >
                                                        #{{ $proposal->id }}
                                                    </dd>
                                                </div>
                                            </dl>
                                        </div>

                                        <div>
                                            <label
                                                class="block text-sm
                                                       font-semibold
                                                       text-gray-700
                                                       dark:text-gray-200"
                                            >
                                                Province Resolution
                                            </label>

                                            <div
                                                class="mt-2 grid gap-2"
                                            >
                                                <label
                                                    class="flex cursor-pointer
                                                           items-start gap-3
                                                           rounded-xl border
                                                           border-gray-200
                                                           bg-gray-50 p-3
                                                           dark:border-gray-700
                                                           dark:bg-gray-900"
                                                >
                                                    <input
                                                        type="radio"
                                                        value="existing"
                                                        wire:model.live="referenceProposalProvinceModes.{{ $proposal->id }}"
                                                        class="mt-1"
                                                    >

                                                    <span>
                                                        <span
                                                            class="block
                                                                   text-sm
                                                                   font-bold
                                                                   text-gray-900
                                                                   dark:text-white"
                                                        >
                                                            Use existing Province
                                                        </span>

                                                        <span
                                                            class="mt-1 block
                                                                   text-xs
                                                                   text-gray-500
                                                                   dark:text-gray-400"
                                                        >
                                                            Use this when the
                                                            submitted name refers
                                                            to an already configured
                                                            Province.
                                                        </span>
                                                    </span>
                                                </label>

                                                <label
                                                    class="flex cursor-pointer
                                                           items-start gap-3
                                                           rounded-xl border
                                                           border-gray-200
                                                           bg-gray-50 p-3
                                                           dark:border-gray-700
                                                           dark:bg-gray-900"
                                                >
                                                    <input
                                                        type="radio"
                                                        value="create"
                                                        wire:model.live="referenceProposalProvinceModes.{{ $proposal->id }}"
                                                        class="mt-1"
                                                    >

                                                    <span>
                                                        <span
                                                            class="block
                                                                   text-sm
                                                                   font-bold
                                                                   text-gray-900
                                                                   dark:text-white"
                                                        >
                                                            Create new Province
                                                        </span>

                                                        <span
                                                            class="mt-1 block
                                                                   text-xs
                                                                   text-gray-500
                                                                   dark:text-gray-400"
                                                        >
                                                            Admin approval will
                                                            create or restore the
                                                            Province before the
                                                            School / Locality is
                                                            resolved.
                                                        </span>
                                                    </span>
                                                </label>
                                            </div>

                                            @error(
                                                'referenceProposalProvinceModes.'
                                                . $proposal->id
                                            )
                                                <p data-coqp-field-error
                                                    class="mt-1 text-xs
                                                           font-semibold
                                                           text-red-600
                                                           dark:text-red-400"
                                                >
                                                    {{ $message }}
                                                </p>
                                            @enderror

                                            @if (
                                                (
                                                    $this
                                                        ->referenceProposalProvinceModes[
                                                            $proposal->id
                                                        ]
                                                    ?? 'existing'
                                                )
                                                === 'existing'
                                            )
                                                <label
                                                    class="mt-4 block
                                                           text-sm font-semibold
                                                           text-gray-700
                                                           dark:text-gray-200"
                                                >
                                                    Existing Province
                                                </label>

                                                <select
                                                    wire:model="referenceProposalProvinceSelections.{{ $proposal->id }}"
                                                    class="mt-2 block w-full
                                                           rounded-xl border
                                                           border-gray-300
                                                           bg-white px-3 py-2.5
                                                           text-sm text-gray-900
                                                           dark:border-gray-700
                                                           dark:bg-gray-900
                                                           dark:text-gray-100"
                                                >
                                                    <option value="">
                                                        Select Province...
                                                    </option>

                                                    @foreach (
                                                        $referenceProvinceOptions
                                                        as $provinceId => $provinceName
                                                    )
                                                        <option
                                                            value="{{ $provinceId }}"
                                                        >
                                                            {{ $provinceName }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                @error(
                                                    'referenceProposalProvinceSelections.'
                                                    . $proposal->id
                                                )
                                                    <p data-coqp-field-error
                                                        class="mt-1 text-xs
                                                               font-semibold
                                                               text-red-600
                                                               dark:text-red-400"
                                                    >
                                                        {{ $message }}
                                                    </p>
                                                @enderror
                                            @else
                                                <div
                                                    class="mt-4 rounded-xl
                                                           border border-emerald-200
                                                           bg-emerald-50 p-3
                                                           dark:border-emerald-900
                                                           dark:bg-emerald-950"
                                                >
                                                    <label
                                                        class="block text-sm
                                                               font-semibold
                                                               text-emerald-900
                                                               dark:text-emerald-100"
                                                    >
                                                        Country Resolution
                                                    </label>

                                                    <div
                                                        class="mt-2 flex
                                                               flex-wrap gap-4"
                                                    >
                                                        <label
                                                            class="flex
                                                                   cursor-pointer
                                                                   items-center gap-2
                                                                   text-sm font-semibold
                                                                   text-emerald-900
                                                                   dark:text-emerald-100"
                                                        >
                                                            <input
                                                                type="radio"
                                                                value="existing"
                                                                wire:model.live="referenceProposalCountryModes.{{ $proposal->id }}"
                                                            >
                                                            Use existing Country
                                                        </label>

                                                        <label
                                                            class="flex
                                                                   cursor-pointer
                                                                   items-center gap-2
                                                                   text-sm font-semibold
                                                                   text-emerald-900
                                                                   dark:text-emerald-100"
                                                        >
                                                            <input
                                                                type="radio"
                                                                value="create"
                                                                wire:model.live="referenceProposalCountryModes.{{ $proposal->id }}"
                                                            >
                                                            Create new Country
                                                        </label>
                                                    </div>

                                                    @error(
                                                        'referenceProposalCountryModes.'
                                                        . $proposal->id
                                                    )
                                                        <p data-coqp-field-error
                                                            class="mt-1 text-xs
                                                                   font-semibold
                                                                   text-red-600
                                                                   dark:text-red-400"
                                                        >
                                                            {{ $message }}
                                                        </p>
                                                    @enderror

                                                    @if (
                                                        (
                                                            $this
                                                                ->referenceProposalCountryModes[
                                                                    $proposal->id
                                                                ]
                                                            ?? 'existing'
                                                        )
                                                        === 'existing'
                                                    )
                                                    <label
                                                        class="mt-3 block
                                                               text-sm font-semibold
                                                               text-emerald-900
                                                               dark:text-emerald-100"
                                                    >
                                                        Country
                                                    </label>

                                                    <select
                                                        wire:model="referenceProposalCountrySelections.{{ $proposal->id }}"
                                                        class="mt-2 block w-full
                                                               rounded-xl border
                                                               border-emerald-300
                                                               bg-white px-3 py-2.5
                                                               text-sm text-gray-900
                                                               dark:border-emerald-800
                                                               dark:bg-gray-900
                                                               dark:text-gray-100"
                                                    >
                                                        <option value="">
                                                            Select Country...
                                                        </option>

                                                        @foreach (
                                                            $referenceCountryOptions
                                                            as $countryId => $countryName
                                                        )
                                                            <option
                                                                value="{{ $countryId }}"
                                                            >
                                                                {{ $countryName }}
                                                            </option>
                                                        @endforeach
                                                    </select>

                                                    @error(
                                                        'referenceProposalCountrySelections.'
                                                        . $proposal->id
                                                    )
                                                        <p data-coqp-field-error
                                                            class="mt-1 text-xs
                                                                   font-semibold
                                                                   text-red-600
                                                                   dark:text-red-400"
                                                        >
                                                            {{ $message }}
                                                        </p>
                                                    @enderror
                                                    @else
                                                        <label
                                                            class="mt-3 block
                                                                   text-sm font-semibold
                                                                   text-emerald-900
                                                                   dark:text-emerald-100"
                                                        >
                                                            Country Name
                                                        </label>

                                                        <input
                                                            type="text"
                                                            maxlength="150"
                                                            wire:model="referenceProposalNewCountryNames.{{ $proposal->id }}"
                                                            placeholder="Country name"
                                                            class="mt-2 block w-full
                                                                   rounded-xl border
                                                                   border-emerald-300
                                                                   bg-white px-3 py-2.5
                                                                   text-sm text-gray-900
                                                                   dark:border-emerald-800
                                                                   dark:bg-gray-900
                                                                   dark:text-gray-100"
                                                        >

                                                        @error(
                                                            'referenceProposalNewCountryNames.'
                                                            . $proposal->id
                                                        )
                                                            <p data-coqp-field-error
                                                                class="mt-1 text-xs
                                                                       font-semibold
                                                                       text-red-600
                                                                       dark:text-red-400"
                                                            >
                                                                {{ $message }}
                                                            </p>
                                                        @enderror

                                                        <label
                                                            class="mt-3 block
                                                                   text-sm font-semibold
                                                                   text-emerald-900
                                                                   dark:text-emerald-100"
                                                        >
                                                            Country Code
                                                            <span
                                                                class="font-normal"
                                                            >
                                                                (optional)
                                                            </span>
                                                        </label>

                                                        <input
                                                            type="text"
                                                            maxlength="3"
                                                            wire:model="referenceProposalNewCountryCodes.{{ $proposal->id }}"
                                                            placeholder="e.g. PH"
                                                            class="mt-2 block w-full
                                                                   rounded-xl border
                                                                   border-emerald-300
                                                                   bg-white px-3 py-2.5
                                                                   text-sm uppercase
                                                                   text-gray-900
                                                                   dark:border-emerald-800
                                                                   dark:bg-gray-900
                                                                   dark:text-gray-100"
                                                        >

                                                        @error(
                                                            'referenceProposalNewCountryCodes.'
                                                            . $proposal->id
                                                        )
                                                            <p data-coqp-field-error
                                                                class="mt-1 text-xs
                                                                       font-semibold
                                                                       text-red-600
                                                                       dark:text-red-400"
                                                            >
                                                                {{ $message }}
                                                            </p>
                                                        @enderror
                                                    @endif

                                                    <label
                                                        class="mt-3 block
                                                               text-sm font-semibold
                                                               text-emerald-900
                                                               dark:text-emerald-100"
                                                    >
                                                        Province / Region
                                                    </label>

                                                    <input
                                                        type="text"
                                                        maxlength="150"
                                                        wire:model="referenceProposalNewProvinceNames.{{ $proposal->id }}"
                                                        class="mt-2 block w-full
                                                               rounded-xl border
                                                               border-emerald-300
                                                               bg-white px-3 py-2.5
                                                               text-sm text-gray-900
                                                               dark:border-emerald-800
                                                               dark:bg-gray-900
                                                               dark:text-gray-100"
                                                    >

                                                    @error(
                                                        'referenceProposalNewProvinceNames.'
                                                        . $proposal->id
                                                    )
                                                        <p data-coqp-field-error
                                                            class="mt-1 text-xs
                                                                   font-semibold
                                                                   text-red-600
                                                                   dark:text-red-400"
                                                        >
                                                            {{ $message }}
                                                        </p>
                                                    @enderror

                                                    <label
                                                        class="mt-3 block
                                                               text-sm font-semibold
                                                               text-emerald-900
                                                               dark:text-emerald-100"
                                                    >
                                                        Province Code
                                                        <span
                                                            class="font-normal
                                                                   text-emerald-700
                                                                   dark:text-emerald-300"
                                                        >
                                                            (optional)
                                                        </span>
                                                    </label>

                                                    <input
                                                        type="text"
                                                        maxlength="30"
                                                        wire:model="referenceProposalNewProvinceCodes.{{ $proposal->id }}"
                                                        placeholder="Optional"
                                                        class="mt-2 block w-full
                                                               rounded-xl border
                                                               border-emerald-300
                                                               bg-white px-3 py-2.5
                                                               text-sm uppercase
                                                               text-gray-900
                                                               dark:border-emerald-800
                                                               dark:bg-gray-900
                                                               dark:text-gray-100"
                                                    >

                                                    @error(
                                                        'referenceProposalNewProvinceCodes.'
                                                        . $proposal->id
                                                    )
                                                        <p data-coqp-field-error
                                                            class="mt-1 text-xs
                                                                   font-semibold
                                                                   text-red-600
                                                                   dark:text-red-400"
                                                        >
                                                            {{ $message }}
                                                        </p>
                                                    @enderror
                                                </div>
                                            @endif

                                            <div
                                                class="mt-4 flex
                                                       flex-wrap
                                                       justify-end gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    wire:click="rejectMeetingReferenceProposal({{ $proposal->id }})"
                                                    wire:confirm="Reject this reference proposal? The canonical database will remain unchanged."
                                                    class="rounded-xl border
                                                           border-red-300
                                                           px-4 py-2
                                                           text-sm font-bold
                                                           text-red-700
                                                           hover:bg-red-50
                                                           dark:border-red-800
                                                           dark:text-red-300
                                                           dark:hover:bg-red-950"
                                                >
                                                    Reject
                                                </button>

                                                <button
                                                    type="button"
                                                    wire:click="approveMeetingReferenceProposal({{ $proposal->id }})"
                                                    wire:confirm="Approve this reference proposal using this Province resolution?"
                                                    class="rounded-xl
                                                           bg-emerald-600
                                                           px-4 py-2
                                                           text-sm font-bold
                                                           text-white
                                                           hover:bg-emerald-500"
                                                >
                                                    Approve
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </details>
@endif
