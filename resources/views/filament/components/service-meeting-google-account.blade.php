@php $serviceAccountStatus = \App\Services\ServiceMeetingGoogleAccount::status(); @endphp
<div class="mb-6 border-b border-gray-200 pb-6 dark:border-gray-700" style="margin-top:1rem;">
    <h3 class="text-lg font-bold">Google Service Account</h3>
    <p style="margin-top:.5rem;overflow-wrap:anywhere;">{{ $serviceAccountStatus['email'] ?? $serviceAccountStatus['label'] }}</p>
    <p style="margin-top:.5rem;"><a href="#google-integrations" style="text-decoration:underline;">Manage accounts in Google Integrations Setup</a></p>
    <div style="margin-top:1rem;">
        <x-filament::button type="button" color="gray" wire:click="testServiceMeetingGoogleAccount" wire:loading.attr="disabled">Test Folder Access</x-filament::button>
    </div>
</div>
