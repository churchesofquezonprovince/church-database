<?php

namespace App\Http\Controllers;

use App\Filament\Pages\DeveloperOptions;
use App\Models\DeveloperSetting;
use App\Models\DriveMeetingDocument;
use App\Services\GoogleIntegrationSettings as Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class GoogleIntegrationSettingsController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()?->isAdmin(), 403);
        $feature = $request->input('feature');
        abort_unless(in_array($feature, ['account', 'minutes', 'calendar', 'children'], true), 422);
        if ($feature === 'account') {
            $validator = validator($request->all(), [
                'label' => ['required', 'string', 'max:120'],
                'credentials' => ['required', 'file', 'max:64'],
            ]);
            if ($validator->fails()) {
                return $this->feedback(false, 'Enter an account name and choose a JSON key no larger than 64 KB.');
            }
            $temporary = $request->file('credentials')->getRealPath();
            try {
                $json = file_get_contents($temporary);
                if ($json === false) {
                    throw new \RuntimeException('Cannot read upload.');
                }
                Settings::addAccount(trim($request->input('label')), $json);
            } catch (\Throwable) {
                return $this->feedback(false, 'Account could not be added. Check that this is the original service-account JSON key and that application storage is writable.');
            } finally {
                if ($temporary && is_file($temporary)) {
                    @unlink($temporary);
                }
            }
            return $this->feedback(true, 'Account added. Select it for an integration below. No active account was changed.');
        }

        $rules = ['account' => ['nullable', 'string', Rule::in(array_keys(Settings::accounts()))]];
        if ($feature === 'calendar') {
            $rules += [
                'enabled' => ['required', 'boolean'],
                'calendars' => ['nullable', 'array', 'max:20'],
                'calendars.*.name' => ['nullable', 'string', 'max:120'],
                'calendars.*.id' => ['nullable', 'string', 'max:300', 'regex:/^[^\s\x00-\x1F]+$/u'],
                'calendars.*.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ];
        } elseif ($feature === 'children') {
            $rules += [
                'enabled' => ['required', 'boolean'],
                'spreadsheet_id' => ['nullable', 'string', 'max:200', 'regex:/^[A-Za-z0-9_-]+$/'],
                'sheet_name' => ['nullable', 'string', 'max:100'],
                'header_row' => ['required', 'integer', 'min:1', 'max:10000'],
            ];
        }
        $validator = validator($request->all(), $rules);
        if ($validator->fails()) {
            return $this->feedback(false, $validator->errors()->first());
        }
        $data = $validator->validated();
        $settings = ['account' => (string) ($data['account'] ?? '')];
        if ($feature === 'calendar') {
            $calendars = [];
            foreach ($data['calendars'] ?? [] as $row) {
                $id = trim((string) ($row['id'] ?? ''));
                $name = trim((string) ($row['name'] ?? ''));
                if ($id === '' && $name === '') {
                    continue;
                }
                if ($id === '' || $name === '') {
                    return $this->feedback(false, 'Each calendar needs both a name and its Calendar ID.');
                }
                if (in_array($id, array_column($calendars, 'id'), true)) {
                    return $this->feedback(false, 'Each Calendar ID must appear only once.');
                }
                $calendars['calendar_'.count($calendars)] = ['name' => $name, 'id' => $id, 'color' => $row['color']];
            }
            if ($data['enabled'] && $calendars === []) {
                return $this->feedback(false, 'Add at least one calendar before enabling sync.');
            }
            $settings += ['enabled' => (bool) $data['enabled'], 'calendars' => $calendars, 'calendar_id' => null];
        } elseif ($feature === 'children') {
            if ($data['enabled'] && empty($data['spreadsheet_id'])) {
                return $this->feedback(false, 'Enter the Spreadsheet ID before enabling sync.');
            }
            $settings += [
                'enabled' => (bool) $data['enabled'],
                'spreadsheet_id' => (string) ($data['spreadsheet_id'] ?? ''),
                'sheet_name' => trim((string) ($data['sheet_name'] ?? '')),
                'header_row' => (int) $data['header_row'],
            ];
        }
        $shouldVerify = $feature === 'minutes' || $settings['enabled'];
        if ($shouldVerify) {
            try {
                Settings::verify($feature, $settings);
            } catch (\Throwable) {
                return $this->feedback(false, 'Access could not be verified. Check the selected account, resource IDs, sharing permissions, and enabled Google APIs. Saved settings were kept.');
            }
        }
        DeveloperSetting::putValue('google_integration_'.$feature, json_encode($settings, JSON_THROW_ON_ERROR));
        if ($feature === 'minutes') {
            Cache::forget(DriveMeetingDocument::documentCacheKey());
            Cache::forget('service_meeting_docs');
        }
        return $this->feedback(true, $shouldVerify
            ? 'Settings saved and read access verified. Calendar and Sheets editing also require write permission in Google. Existing schedules and lessons were kept.'
            : 'Settings saved with sync disabled. Access was not tested.');
    }

    private function feedback(bool $success, string $message)
    {
        // Never flash request input: credential uploads contain private keys.
        return redirect(DeveloperOptions::getUrl().'#google-integrations')->with(
            $success ? 'google_setup_success' : 'google_setup_error', $message
        );
    }
}
