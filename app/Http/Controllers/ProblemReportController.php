<?php
namespace App\Http\Controllers;
use App\Models\ProblemReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
class ProblemReportController extends Controller {
    public function store(Request $request) {
        $validator = Validator::make($request->all(), [
            'category' => ['required', Rule::in(array_keys(ProblemReport::CATEGORIES))],
            'description' => ['required', 'string', 'min:10', 'max:5000'],
            'reporter_name' => ['nullable', 'string', 'max:120'],
            'contact' => ['nullable', 'string', 'max:200'],
            'page_path' => ['required', 'string', 'max:1500', 'regex:~^/(?!/)[^\x00-\x20?#]*$~'],
            'screenshot' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }
        $data = $validator->validated();
        unset($data['screenshot']);
        $data['user_id'] = $request->user()?->id;
        if ($request->user()) $data['reporter_name'] = mb_substr($request->user()->name, 0, 120);
        $path = null;
        try {
            if ($request->hasFile('screenshot')) {
                $path = $request->file('screenshot')->store('problem-reports', 'local');
                if (! $path) throw new \RuntimeException('Screenshot storage failed.');
            }
            $data['screenshot_path'] = $path;
            $report = ProblemReport::create($data);
        } catch (\Throwable $e) {
            if ($path) Storage::disk('local')->delete($path);
            report($e);
            return response()->json(['message' => 'Your report could not be saved. Please try again.'], 503);
        }
        return response()->json(['message' => 'Thank you. Your report has been received.', 'reference' => $report->id], 201);
    }
    public function screenshot(Request $request, int $id) {
        abort_unless($request->user()?->isAdmin(), 403);
        $report = ProblemReport::findOrFail($id);
        abort_unless($report->screenshot_path && Storage::disk('local')->exists($report->screenshot_path), 404);
        return Storage::disk('local')->download($report->screenshot_path, 'report-'.$report->id.'.'.pathinfo($report->screenshot_path, PATHINFO_EXTENSION), ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }
}
