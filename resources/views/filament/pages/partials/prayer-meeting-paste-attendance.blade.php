<details
    class="mb-4 overflow-hidden rounded-xl
           border border-amber-200 bg-amber-50
           dark:border-amber-900 dark:bg-amber-950"
>
    <summary
        class="cursor-pointer px-4 py-3
               text-sm font-bold text-amber-900
               hover:bg-amber-100
               dark:text-amber-100
               dark:hover:bg-amber-900"
    >
        Paste Attendance List
    </summary>

    <div
        class="border-t border-amber-200 p-4
               dark:border-amber-900"
    >
        <p
            class="text-xs text-amber-700
                   dark:text-amber-300"
        >
            Paste a numbered or plain list of names.
            Matching People will be checked as Present.
            Review the checklist before saving attendance.
        </p>

        <textarea
            data-prayer-meeting-pasted-list
            data-storage-key="prayer-meeting-pasted-attendance-{{ \Illuminate\Support\Str::slug((string) ($selectedLocality ?: 'no-locality')) }}-{{ $selectedMeetingDate }}"
            rows="8"
            placeholder="1. Zedric Dalde&#10;2. Jhyrnol Cuaton&#10;3. Johnny Guyo"
            oninput="
                localStorage.setItem(
                    this.dataset.storageKey,
                    this.value
                )
            "
            class="mt-3 block w-full rounded-xl
                   border border-amber-200 bg-white
                   px-4 py-3 text-sm text-gray-900
                   shadow-sm
                   dark:border-amber-900
                   dark:bg-gray-950
                   dark:text-gray-100"
        ></textarea>

        <div class="mt-3 flex flex-wrap gap-2">
            <button
                type="button"
                onclick="window.matchPastedPrayerMeetingAttendance(this)"
                class="rounded-lg bg-amber-600
                       px-3 py-2 text-xs font-bold
                       text-white hover:bg-amber-500"
            >
                Parse & Check
            </button>

            <button
                type="button"
                onclick="window.clearPastedPrayerMeetingAttendance(this)"
                class="rounded-lg border border-amber-300
                       bg-white px-3 py-2 text-xs
                       font-bold text-amber-800
                       hover:bg-amber-100
                       dark:border-amber-800
                       dark:bg-amber-950
                       dark:text-amber-100"
            >
                Clear Parsed Checks
            </button>
        </div>

        <div
            data-prayer-meeting-paste-results
            class="mt-3 hidden"
        ></div>
    </div>
</details>

<div
    class="hidden"
    aria-hidden="true"
    data-prayer-meeting-other-candidates
>
    @foreach ($this->otherLocalityCandidates() as $person)
        <span
            data-prayer-meeting-other-person
            data-person-id="{{ $person->id }}"
            data-person-name="{{ $person->display_name }}"
            data-person-nickname="{{ $person->nickname ?? '' }}"
            data-person-locality="{{ $person->locality ?? '' }}"
            data-person-status="{{ $person->churchProfile?->status ?? '' }}"
        ></span>
    @endforeach
</div>

<script>
    (() => {
        const normalizeName = (value) => {
            return String(value ?? '')
                .toLowerCase()
                .normalize('NFD')
                .replace(/[\u0300-\u036f]/g, '')
                .replace(/\b(jr|sr|ii|iii|iv)\b/g, ' ')
                .replace(/[^a-z0-9]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        };

        const tokens = (value) =>
            normalizeName(value)
                .split(' ')
                .filter(Boolean);

        const editDistance = (a, b) => {
            const previous =
                Array.from(
                    { length: b.length + 1 },
                    (_, index) => index
                );

            for (let i = 1; i <= a.length; i++) {
                const current = [i];

                for (let j = 1; j <= b.length; j++) {
                    const cost =
                        a[i - 1] === b[j - 1]
                            ? 0
                            : 1;

                    current[j] =
                        Math.min(
                            current[j - 1] + 1,
                            previous[j] + 1,
                            previous[j - 1] + cost
                        );
                }

                previous.splice(
                    0,
                    previous.length,
                    ...current
                );
            }

            return previous[b.length];
        };

        const tokenMatches = (
            inputToken,
            candidateToken
        ) => {
            if (inputToken === candidateToken) {
                return true;
            }

            if (
                inputToken.length === 1
                &&
                candidateToken.startsWith(
                    inputToken
                )
            ) {
                return true;
            }

            return (
                inputToken.length >= 5
                &&
                candidateToken.length >= 5
                &&
                Math.abs(
                    inputToken.length
                    - candidateToken.length
                ) <= 1
                &&
                editDistance(
                    inputToken,
                    candidateToken
                ) <= 1
            );
        };

        const matchScore = (
            inputName,
            candidateName
        ) => {
            const inputTokens =
                tokens(inputName);

            const candidateTokens =
                tokens(candidateName);

            if (
                ! inputTokens.length
                ||
                ! candidateTokens.length
            ) {
                return 0;
            }

            if (
                normalizeName(inputName)
                ===
                normalizeName(candidateName)
            ) {
                return 1;
            }

            const used = new Set();
            let matched = 0;

            for (const inputToken of inputTokens) {
                const index =
                    candidateTokens.findIndex(
                        (
                            candidateToken,
                            candidateIndex
                        ) =>
                            ! used.has(
                                candidateIndex
                            )
                            &&
                            tokenMatches(
                                inputToken,
                                candidateToken
                            )
                    );

                if (index !== -1) {
                    used.add(index);
                    matched++;
                }
            }

            const coverage =
                matched / inputTokens.length;

            return coverage === 1
                ? 0.90
                : coverage * 0.80;
        };

        const bestPersonScore = (
            inputName,
            person
        ) => {
            const nameScore =
                matchScore(
                    inputName,
                    person.name
                );

            const nicknameScore =
                person.nickname
                    ? matchScore(
                        inputName,
                        person.nickname
                    )
                    : 0;

            const combinedScore =
                person.nickname
                    ? matchScore(
                        inputName,
                        `${person.name} ${person.nickname}`
                    )
                    : 0;

            return Math.max(
                nameScore,
                nicknameScore,
                combinedScore
            );
        };

        const escapeHtml = (value) => {
            const element =
                document.createElement('div');

            element.textContent =
                String(value ?? '');

            return element.innerHTML;
        };

        window.clearPastedPrayerMeetingAttendance =
            (button) => {
                const form =
                    button.closest('form');

                if (! form) {
                    return;
                }

                form.querySelectorAll(
                    'input[data-paste-attendance-selected="1"]'
                ).forEach((checkbox) => {
                    checkbox.checked = false;

                    delete checkbox.dataset
                        .pasteAttendanceSelected;

                    updatePermanentMeetingRowStatus(
                        checkbox
                    );
                });

                form.querySelectorAll(
                    'input[data-paste-other-attendee="1"]'
                ).forEach(
                    (input) => input.remove()
                );

                const results =
                    form.querySelector(
                        '[data-prayer-meeting-paste-results]'
                    );

                if (results) {
                    results.innerHTML = '';
                    results.classList.add(
                        'hidden'
                    );
                }
            };

        window.matchPastedPrayerMeetingAttendance =
            (button) => {
                const form =
                    button.closest('form');

                if (! form) {
                    return;
                }

                const textarea =
                    form.querySelector(
                        '[data-prayer-meeting-pasted-list]'
                    );

                const results =
                    form.querySelector(
                        '[data-prayer-meeting-paste-results]'
                    );

                if (
                    ! textarea
                    ||
                    ! results
                ) {
                    return;
                }

                form.querySelectorAll(
                    'input[data-paste-attendance-selected="1"]'
                ).forEach((checkbox) => {
                    checkbox.checked = false;

                    delete checkbox.dataset
                        .pasteAttendanceSelected;

                    updatePermanentMeetingRowStatus(
                        checkbox
                    );
                });

                form.querySelectorAll(
                    'input[data-paste-other-attendee="1"]'
                ).forEach(
                    (input) => input.remove()
                );

                const names =
                    textarea.value
                        .split(/\r?\n/)
                        .map(
                            (line) =>
                                line
                                    .replace(
                                        /^\s*\d+\s*[\.\)\-:]?\s*/,
                                        ''
                                    )
                                    .trim()
                        )
                        .filter(Boolean);

                if (! names.length) {
                    results.innerHTML =
                        '<div class="rounded-lg border border-amber-200 p-3 text-xs text-amber-800 dark:border-amber-900 dark:text-amber-200">Paste at least one name first.</div>';

                    results.classList.remove(
                        'hidden'
                    );

                    return;
                }

                const people =
                    Array.from(
                        form.querySelectorAll(
                            '[data-prayer-meeting-person]'
                        )
                    ).map((row) => ({
                        row,
                        name:
                            row.dataset.personName
                            ?? '',
                        nickname:
                            row.dataset.personNickname
                            ?? '',
                        checkbox:
                            row.querySelector(
                                '[data-attendance-checkbox]'
                            ),
                    })).filter(
                        (person) =>
                            person.checkbox
                    );

                /*
                 * Build the secondary matching pool from the
                 * existing Other Locality / Unmarked Attendee
                 * workflow.
                 *
                 * This includes:
                 * - people currently available in its Select box
                 * - people already added as other attendees
                 */
                const otherPeopleById =
                    new Map();

                document.querySelectorAll(
                    '[data-prayer-meeting-other-person]'
                ).forEach((element) => {
                    const personId =
                        String(
                            element.dataset.personId
                            ?? element.value
                            ?? ''
                        );

                    if (! personId) {
                        return;
                    }

                    otherPeopleById.set(
                        personId,
                        {
                            personId,
                            name:
                                element.dataset
                                    .personName
                                ?? '',
                            nickname:
                                element.dataset
                                    .personNickname
                                ?? '',
                            locality:
                                element.dataset
                                    .personLocality
                                ?? '',
                            status:
                                element.dataset
                                    .personStatus
                                ?? '',
                            alreadyPresent:
                                false,
                        }
                    );
                });

                document.querySelectorAll(
                    '[data-prayer-meeting-other-present]'
                ).forEach((element) => {
                    const personId =
                        String(
                            element.dataset.personId
                            ?? ''
                        );

                    if (! personId) {
                        return;
                    }

                    otherPeopleById.set(
                        personId,
                        {
                            personId,
                            name:
                                element.dataset
                                    .personName
                                ?? '',
                            nickname:
                                element.dataset
                                    .personNickname
                                ?? '',
                            locality:
                                element.dataset
                                    .personLocality
                                ?? '',
                            status:
                                element.dataset
                                    .personStatus
                                ?? '',
                            alreadyPresent:
                                true,
                        }
                    );
                });

                const otherPeople =
                    Array.from(
                        otherPeopleById.values()
                    );

                const existingOtherPersonIds =
                    new Set(
                        Array.from(
                            form.querySelectorAll(
                                'input[name="other_present_person_ids[]"]'
                            )
                        ).map(
                            (input) =>
                                String(input.value)
                        )
                    );

                const rankCandidates = (
                    inputName,
                    candidates
                ) =>
                    candidates
                        .map(
                            (person) => ({
                                ...person,
                                score:
                                    bestPersonScore(
                                        inputName,
                                        person
                                    ),
                            })
                        )
                        .filter(
                            (person) =>
                                person.score >= 0.55
                        )
                        .sort(
                            (a, b) =>
                                b.score - a.score
                        );

                const safeCandidate = (
                    inputName,
                    ranked
                ) => {
                    const best =
                        ranked[0];

                    const second =
                        ranked[1];

                    if (! best) {
                        return null;
                    }

                    const inputTokenCount =
                        tokens(inputName).length;

                    const exactAlias =
                        normalizeName(inputName)
                        ===
                        normalizeName(best.name)
                        ||
                        (
                            best.nickname
                            &&
                            normalizeName(inputName)
                            ===
                            normalizeName(
                                best.nickname
                            )
                        );

                    const uniqueEnough =
                        ! second
                        ||
                        (
                            best.score
                            - second.score
                        ) >= 0.08;

                    if (
                        ! uniqueEnough
                        ||
                        (
                            ! exactAlias
                            &&
                            ! (
                                inputTokenCount >= 2
                                &&
                                best.score >= 0.88
                            )
                        )
                    ) {
                        return null;
                    }

                    return best;
                };

                const matched = [];
                const otherMatched = [];
                const alreadyChecked = [];
                const alreadyOther = [];
                const review = [];
                const unmatched = [];

                for (const inputName of names) {
                    /*
                     * First preference:
                     * selected Locality / main checklist.
                     */
                    const mainRanked =
                        rankCandidates(
                            inputName,
                            people
                        );

                    const mainMatch =
                        safeCandidate(
                            inputName,
                            mainRanked
                        );

                    if (mainMatch) {
                        if (
                            mainMatch.checkbox.checked
                        ) {
                            alreadyChecked.push({
                                inputName,
                                candidate:
                                    mainMatch.name,
                            });

                            continue;
                        }

                        mainMatch.checkbox.checked =
                            true;

                        mainMatch.checkbox.dataset
                            .pasteAttendanceSelected =
                            '1';

                        updatePermanentMeetingRowStatus(
                            mainMatch.checkbox
                        );

                        matched.push({
                            inputName,
                            candidate:
                                mainMatch.name,
                        });

                        continue;
                    }

                    /*
                     * Second preference:
                     * Other Locality / Unmarked Attendee.
                     */
                    const otherRanked =
                        rankCandidates(
                            inputName,
                            otherPeople
                        );

                    const otherMatch =
                        safeCandidate(
                            inputName,
                            otherRanked
                        );

                    if (otherMatch) {
                        const personId =
                            String(
                                otherMatch.personId
                            );

                        if (
                            otherMatch.alreadyPresent
                            ||
                            existingOtherPersonIds
                                .has(personId)
                        ) {
                            alreadyOther.push({
                                inputName,
                                candidate:
                                    otherMatch.name,
                                locality:
                                    otherMatch.locality,
                            });

                            continue;
                        }

                        const hidden =
                            document.createElement(
                                'input'
                            );

                        hidden.type = 'hidden';

                        hidden.name =
                            'other_present_person_ids[]';

                        hidden.value =
                            personId;

                        hidden.dataset
                            .pasteOtherAttendee =
                            '1';

                        form.appendChild(hidden);

                        existingOtherPersonIds.add(
                            personId
                        );

                        otherMatched.push({
                            inputName,
                            candidate:
                                otherMatch.name,
                            locality:
                                otherMatch.locality,
                        });

                        continue;
                    }

                    /*
                     * No safe automatic match.
                     * Show the best combined suggestions.
                     */
                    const suggestions = [
                        ...mainRanked.map(
                            (candidate) => ({
                                score:
                                    candidate.score,
                                label:
                                    candidate.name,
                            })
                        ),

                        ...otherRanked.map(
                            (candidate) => ({
                                score:
                                    candidate.score,
                                label:
                                    `${candidate.name}`
                                    +
                                    (
                                        candidate.locality
                                            ? ` — ${candidate.locality}`
                                            : ''
                                    )
                                    +
                                    ' — Other Locality / Unmarked',
                            })
                        ),
                    ]
                        .sort(
                            (a, b) =>
                                b.score - a.score
                        )
                        .slice(0, 3);

                    if (suggestions.length) {
                        review.push({
                            inputName,
                            suggestions:
                                suggestions.map(
                                    (item) =>
                                        item.label
                                ),
                        });
                    } else {
                        unmatched.push(
                            inputName
                        );
                    }
                }

                filterPermanentMeetingChecklist(
                    form
                );

                const resultBlock = (
                    title,
                    items,
                    classes
                ) => {
                    if (! items.length) {
                        return '';
                    }

                    return `
                        <div class="${classes} rounded-lg border p-3">
                            <p class="text-xs font-bold">
                                ${title}: ${items.length}
                            </p>

                            <div class="mt-2 space-y-1 text-xs">
                                ${items.join('')}
                            </div>
                        </div>
                    `;
                };

                const matchedHtml =
                    resultBlock(
                        'Matched and checked Present',
                        matched.map(
                            (item) =>
                                `<p>✓ ${escapeHtml(item.inputName)} → <strong>${escapeHtml(item.candidate)}</strong></p>`
                        ),
                        'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200'
                    );

                const otherMatchedHtml =
                    resultBlock(
                        'Matched as Other Locality / Unmarked',
                        otherMatched.map(
                            (item) =>
                                `<p>✓ ${escapeHtml(item.inputName)} → <strong>${escapeHtml(item.candidate)}</strong>${item.locality ? ` · ${escapeHtml(item.locality)}` : ''}</p>`
                        ),
                        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200'
                    );

                const alreadyOtherHtml =
                    resultBlock(
                        'Already Present as Other Locality / Unmarked',
                        alreadyOther.map(
                            (item) =>
                                `<p>✓ ${escapeHtml(item.inputName)} → <strong>${escapeHtml(item.candidate)}</strong>${item.locality ? ` · ${escapeHtml(item.locality)}` : ''}</p>`
                        ),
                        'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200'
                    );

                const alreadyHtml =
                    resultBlock(
                        'Already checked Present',
                        alreadyChecked.map(
                            (item) =>
                                `<p>✓ ${escapeHtml(item.inputName)} → <strong>${escapeHtml(item.candidate)}</strong></p>`
                        ),
                        'border-sky-200 bg-sky-50 text-sky-800 dark:border-sky-900 dark:bg-sky-950 dark:text-sky-200'
                    );

                const reviewHtml =
                    resultBlock(
                        'Needs review',
                        review.map(
                            (item) =>
                                `<p>? <strong>${escapeHtml(item.inputName)}</strong> · Suggested: ${item.suggestions.map(escapeHtml).join(' · ')}</p>`
                        ),
                        'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200'
                    );

                const unmatchedHtml =
                    resultBlock(
                        'No safe match',
                        unmatched.map(
                            (name) =>
                                `<p>! ${escapeHtml(name)}</p>`
                        ),
                        'border-red-200 bg-red-50 text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200'
                    );

                results.innerHTML = `
                    <div class="space-y-2">
                        <p class="text-xs font-semibold text-amber-800 dark:text-amber-200">
                            Parsed ${names.length} name(s).
                            Nothing has been saved yet.
                            Review the Present checkboxes, then click
                            Save Prayer Meeting Attendance.
                        </p>

                        ${matchedHtml}
                        ${otherMatchedHtml}
                        ${alreadyHtml}
                        ${alreadyOtherHtml}
                        ${reviewHtml}
                        ${unmatchedHtml}
                    </div>
                `;

                results.classList.remove(
                    'hidden'
                );
            };

        const textarea =
            document.querySelector(
                '[data-prayer-meeting-pasted-list]'
            );

        if (
            textarea
            &&
            textarea.dataset.storageKey
        ) {
            const saved =
                localStorage.getItem(
                    textarea.dataset.storageKey
                );

            if (
                saved !== null
                &&
                ! textarea.value
            ) {
                textarea.value = saved;
            }
        }
    })();
</script>
