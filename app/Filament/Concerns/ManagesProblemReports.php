<?php
namespace App\Filament\Concerns;
use App\Models\ProblemReport;
trait ManagesProblemReports {
    public string $problemSearch = '';
    public string $problemStatus = 'new';
    public int $problemPage = 1;
    public function updatedProblemSearch(): void { $this->problemPage = 1; }
    public function updatedProblemStatus(): void { $this->problemPage = 1; }
    public function problemReports() {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $query = ProblemReport::query();
        $search = mb_substr(trim($this->problemSearch), 0, 200);
        if ($search !== '') $query->where(function ($q) use ($search): void {
            foreach (['description', 'page_path', 'reporter_name', 'contact'] as $column) {
                $q->orWhere($column, 'like', '%'.$search.'%');
            }
        });
        if (array_key_exists($this->problemStatus, ProblemReport::STATUSES)) $query->where('status', $this->problemStatus);
        return $query->orderByDesc('created_at')->orderByDesc('id')->paginate(10, ['*'], 'problemPage', max(1, $this->problemPage));
    }
    public function changeProblemPage(int $page): void {
        abort_unless(auth()->user()?->isAdmin(), 403);
        $this->problemPage = max(1, min($page, $this->problemReports()->lastPage()));
    }
    public function changeProblemStatus(int $id, string $status): void {
        abort_unless(auth()->user()?->isAdmin(), 403);
        abort_unless(array_key_exists($status, ProblemReport::STATUSES), 422);
        ProblemReport::findOrFail($id)->update(['status' => $status]);
        \Filament\Notifications\Notification::make()->title('Report status updated')->success()->send();
    }
}
