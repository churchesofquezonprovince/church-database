@php
    $pendingReferenceProposals =
        $this->pendingMeetingReferenceProposals();

    $pendingReferenceProposalsByResponse =
        $pendingReferenceProposals->groupBy(
            'attendance_meeting_response_id'
        );

    $referenceProvinceOptions =
        $this->meetingReferenceProposalProvinceOptions();
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
                database. Resolve the submitted Province to an
                existing Province before approval.
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
                                                Resolve Province
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
                                                <p
                                                    class="mt-1 text-xs
                                                           font-semibold
                                                           text-red-600
                                                           dark:text-red-400"
                                                >
                                                    {{ $message }}
                                                </p>
                                            @enderror

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
                                                    wire:confirm="Approve this reference proposal using the selected Province?"
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
