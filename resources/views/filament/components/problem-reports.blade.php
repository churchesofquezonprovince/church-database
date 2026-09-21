<details
    wire:key="developer-problem-reports"
    class="coqp-reports group rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900"
    aria-labelledby="problem-reports-heading"
>
    <style>
        .coqp-reports .report-filters{display:flex;flex-wrap:wrap;gap:16px;margin-block:20px}
        .coqp-reports input,.coqp-reports select,.coqp-reports button{min-height:44px;border:1px solid #64748b;border-radius:8px;padding:8px 12px;background:transparent;color:inherit}
        .coqp-reports select option{color:#172033;background:white}
        .coqp-reports label{display:grid;gap:6px}.coqp-reports .report-table{overflow-x:auto}
        .coqp-reports table{width:100%;text-align:left;border-collapse:collapse;min-width:660px}
        .coqp-reports th,.coqp-reports td{padding:14px 12px;border-bottom:1px solid #64748b55;vertical-align:top}
        .coqp-reports th{font-weight:700}.coqp-reports p{overflow-wrap:anywhere}
        .coqp-reports .report-table summary,.coqp-reports a{cursor:pointer;text-decoration:underline;text-underline-offset:3px}
        .coqp-reports .report-description{white-space:pre-wrap;max-width:65ch;margin-block:12px}
        .coqp-reports button:disabled{opacity:.45;cursor:default}
        .coqp-reports :is(input,select,button,a,summary):focus-visible{outline:3px solid #3b82f6;outline-offset:3px}
    </style>
    <summary
        class="flex cursor-pointer list-none items-center justify-between gap-4 px-6 py-5"
    >
        <div>
            <h2 id="problem-reports-heading"
                class="text-lg font-bold text-gray-950 dark:text-white">
                Problem Reports
            </h2>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Reports from the website and public pages.
                Newest reports appear first.
            </p>
        </div>
        <x-filament::icon
            icon="heroicon-m-chevron-down"
            class="h-5 w-5 shrink-0 text-gray-400 transition-transform group-open:rotate-180"
        />
    </summary>
    <div class="border-t border-gray-200 px-6 pb-6 pt-5 dark:border-gray-700">
    <div class="report-filters">
        <label>Search reports<input type="search" wire:model.live.debounce.350ms="problemSearch" placeholder="Description, page, or name" maxlength="200"></label>
        <label>Status<select wire:model.live="problemStatus"><option value="">All statuses</option>
            @foreach (\App\Models\ProblemReport::STATUSES as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select></label>
    </div>
    @php
        $problemReports = $this->problemReports();
    @endphp
    <div class="report-table"><table>
        <thead><tr><th scope="col">Submitted</th><th scope="col">Problem and details</th><th scope="col">Reported by</th><th scope="col">Status</th></tr></thead>
        <tbody>
        @forelse ($problemReports as $problem)
            <tr wire:key="problem-report-{{ $problem->id }}">
                <td>#{{ $problem->id }}<br>{{ $problem->created_at->format('M j, Y g:i A') }}</td>
                <td><strong>{{ \App\Models\ProblemReport::CATEGORIES[$problem->category] ?? $problem->category }}</strong>
                    <p>{{ $problem->page_path }}</p>
                    <details><summary>Read report</summary><p class="report-description">{{ $problem->description }}</p>
                        @if ($problem->screenshot_path)
                            <a href="{{ route('problem-reports.screenshot', ['id' => $problem->id], false) }}">Download screenshot</a>
                        @endif
                        <p>Last updated: {{ $problem->updated_at->format('M j, Y g:i A') }}</p>
                    </details>
                </td>
                <td>{{ $problem->reporter_name ?: 'Guest' }}<p>{{ $problem->contact }}</p>
                    @if ($problem->user_id)<p>Account #{{ $problem->user_id }}</p>@endif
                </td>
                <td><select aria-label="Status for report {{ $problem->id }}" wire:change="changeProblemStatus({{ $problem->id }}, $event.target.value)" wire:loading.attr="disabled">
                    @foreach (\App\Models\ProblemReport::STATUSES as $value => $label)
                        <option value="{{ $value }}" @selected($problem->status === $value)>{{ $label }}</option>
                    @endforeach
                </select></td>
            </tr>
        @empty
            <tr><td colspan="4">No reports match your search.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    <div class="report-filters">
        <button type="button" wire:click="changeProblemPage({{ $problemReports->currentPage() - 1 }})" @disabled($problemReports->onFirstPage())>Previous</button>
        <span>Page {{ $problemReports->currentPage() }} of {{ $problemReports->lastPage() }} · {{ $problemReports->total() }} reports</span>
        <button type="button" wire:click="changeProblemPage({{ $problemReports->currentPage() + 1 }})" @disabled(! $problemReports->hasMorePages())>Next</button>
    </div>
    </div>
</details>
