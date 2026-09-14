@if (
    array_key_exists(
        $hymnIndex,
        $hymnRequestForms
    )
)
    @php
        $titleError = $errors->first(
            "hymnRequestForms.{$hymnIndex}.title"
        );

        $sourceError = $errors->first(
            "hymnRequestForms.{$hymnIndex}.source_url"
        );
    @endphp

    <div
        class="mt-3 rounded-xl border
               border-sky-200 bg-sky-50/60 p-4
               dark:border-sky-900
               dark:bg-sky-950/30"
    >
        <div
            class="flex items-start
                   justify-between gap-3"
        >
            <div>
                <p
                    class="text-sm font-bold
                           text-gray-900
                           dark:text-white"
                >
                    Request Hymn Addition
                </p>

                <p
                    class="mt-1 text-xs
                           text-gray-500
                           dark:text-gray-400"
                >
                    Title is the only required field.
                    An Admin must approve the request
                    before the hymn is added.
                </p>
            </div>

            <button
                type="button"
                wire:click="closeHymnRequest({{ $hymnIndex }})"
                class="text-xs font-semibold
                       text-gray-400
                       hover:text-red-600"
            >
                Cancel
            </button>
        </div>

        <div class="mt-4 space-y-3">
            <div>
                <label
                    class="text-xs font-semibold
                           text-gray-700
                           dark:text-gray-300"
                >
                    Title *
                </label>

                <input
                    type="text"
                    wire:model="hymnRequestForms.{{ $hymnIndex }}.title"
                    class="mt-1 block w-full
                           rounded-lg border
                           border-gray-300 bg-white
                           px-3 py-2 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950"
                >

                @if ($titleError)
                    <p class="mt-1 text-xs text-red-600">
                        {{ $titleError }}
                    </p>
                @endif
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label
                        class="text-xs font-semibold
                               text-gray-700
                               dark:text-gray-300"
                    >
                        Language
                    </label>

                    <input
                        type="text"
                        wire:model="hymnRequestForms.{{ $hymnIndex }}.language"
                        placeholder="English"
                        class="mt-1 block w-full
                               rounded-lg border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                </div>

                <div>
                    <label
                        class="text-xs font-semibold
                               text-gray-700
                               dark:text-gray-300"
                    >
                        Source Link
                    </label>

                    <input
                        type="url"
                        wire:model="hymnRequestForms.{{ $hymnIndex }}.source_url"
                        placeholder="https://..."
                        class="mt-1 block w-full
                               rounded-lg border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >

                    @if ($sourceError)
                        <p class="mt-1 text-xs text-red-600">
                            {{ $sourceError }}
                        </p>
                    @endif
                </div>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <div>
                    <label
                        class="text-xs font-semibold
                               text-gray-700
                               dark:text-gray-300"
                    >
                        Book
                    </label>

                    <input
                        type="text"
                        wire:model="hymnRequestForms.{{ $hymnIndex }}.book_name"
                        placeholder="Hymnal"
                        class="mt-1 block w-full
                               rounded-lg border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                </div>

                <div>
                    <label
                        class="text-xs font-semibold
                               text-gray-700
                               dark:text-gray-300"
                    >
                        Hymn Number
                    </label>

                    <input
                        type="text"
                        wire:model="hymnRequestForms.{{ $hymnIndex }}.hymn_number"
                        class="mt-1 block w-full
                               rounded-lg border
                               border-gray-300 bg-white
                               px-3 py-2 text-sm
                               dark:border-gray-700
                               dark:bg-gray-950"
                    >
                </div>
            </div>

            <div>
                <label
                    class="text-xs font-semibold
                           text-gray-700
                           dark:text-gray-300"
                >
                    Lyrics / Identifying Words
                </label>

                <textarea
                    wire:model="hymnRequestForms.{{ $hymnIndex }}.lyrics"
                    rows="3"
                    class="mt-1 block w-full
                           rounded-lg border
                           border-gray-300 bg-white
                           px-3 py-2 text-sm
                           dark:border-gray-700
                           dark:bg-gray-950"
                ></textarea>
            </div>

            <div class="flex justify-end">
                <button
                    type="button"
                    wire:click="submitHymnRequest({{ $hymnIndex }})"
                    class="rounded-lg bg-sky-600
                           px-4 py-2 text-sm
                           font-bold text-white
                           hover:bg-sky-500"
                >
                    Submit for Admin Approval
                </button>
            </div>
        </div>
    </div>
@endif
