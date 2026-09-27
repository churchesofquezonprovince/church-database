@once
<div id="coqp-report-widget">
    <style>
        #coqp-report-widget{font-family:ui-sans-serif,system-ui,sans-serif;font-size:16px;line-height:1.5}
        #coqp-report-open{position:fixed;right:var(--coqp-report-right,1.25rem);transition:right 160ms ease;bottom:1.25rem;z-index:30;min-height:44px;padding:10px 16px;border:1px solid #60a5fa;border-radius:24px;background:#1d4ed8;color:white;font-weight:600;box-shadow:0 4px 16px #0003;cursor:pointer}
        #coqp-report-dialog{position:fixed;inset:0;margin:auto;width:min(560px,calc(100vw - 24px));max-height:calc(100dvh - 32px);overflow:auto;border:1px solid #cbd5e1;border-radius:18px;padding:24px;background:#fff;color:#172033;box-shadow:0 20px 80px #0005}
        #coqp-report-dialog::backdrop{background:#0f172a99}
        #coqp-report-dialog h2{font-size:24px;font-weight:700;margin:0 0 8px}
        #coqp-report-dialog p{margin:8px 0 16px}
        #coqp-report-dialog label{display:block;margin:16px 0 6px;font-weight:600}
        #coqp-report-dialog :is(input,textarea,select){box-sizing:border-box;width:100%;border:1px solid #64748b;border-radius:8px;padding:10px;background:white;color:#172033;font:inherit;min-height:44px}
        #coqp-report-dialog textarea{resize:vertical}
        #coqp-report-dialog .report-buttons{display:flex;flex-wrap:wrap;justify-content:flex-end;gap:12px;margin-top:20px}
        #coqp-report-dialog button{min-height:44px;padding:10px 18px;border-radius:8px;border:1px solid #64748b;background:white;color:#172033;font:inherit;font-weight:600;cursor:pointer}
        #coqp-report-dialog button[type=submit]{background:#1d4ed8;color:white;border-color:#1d4ed8}
        #coqp-report-widget :is(button,input,select,textarea):focus-visible{outline:3px solid #2563eb;outline-offset:3px}
        #coqp-report-dialog button:disabled{opacity:.6;cursor:wait}
        #coqp-report-message{font-weight:600;white-space:pre-wrap}
        @media print{#coqp-report-widget,#global-back-to-top{display:none!important}}
    </style>
    <button id="coqp-report-open" type="button" aria-haspopup="dialog" aria-controls="coqp-report-dialog">Report a Problem</button>
    <dialog id="coqp-report-dialog" aria-labelledby="coqp-report-title" aria-describedby="coqp-report-help">
        <h2 id="coqp-report-title">Report a Problem</h2>
        <p id="coqp-report-help">Tell us what happened so we can check it. You do not need to know any technical terms.</p>
        <form id="coqp-report-form" action="{{ route('problem-reports.store', [], false) }}" method="post" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="page_path" value="/">
            <label for="coqp-report-category">What is the problem?</label>
            <select id="coqp-report-category" name="category" required>
                @foreach (\App\Models\ProblemReport::CATEGORIES as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <label for="coqp-report-description">What happened?</label>
            <textarea id="coqp-report-description" name="description" rows="4" minlength="10" maxlength="5000" required placeholder="What were you trying to do, and what happened instead?"></textarea>
            @guest
                <label for="coqp-report-name">Your name (optional)</label>
                <input id="coqp-report-name" name="reporter_name" autocomplete="name" maxlength="120">
            @endguest
            <label for="coqp-report-contact">Email or phone number (optional)</label>
            <input id="coqp-report-contact" name="contact" maxlength="200" placeholder="If you would like us to contact you">
            <label for="coqp-report-screenshot">Screenshot (optional)</label>
            <input id="coqp-report-screenshot" name="screenshot" type="file" accept="image/png,image/jpeg,image/webp" aria-describedby="coqp-report-file-help">
            <p id="coqp-report-file-help">JPG, PNG, or WebP, up to 4 MB. Please leave out passwords and private information. Only website administrators can read your report.</p>
            <p id="coqp-report-message" role="status" aria-live="polite"></p>
            <div class="report-buttons"><button type="button" id="coqp-report-close">Close</button><button type="submit">Send report</button></div>
        </form>
    </dialog>
</div>
<script>
(() => {
    const root = document.getElementById('coqp-report-widget');
    if (!root || root.dataset.ready) return;
    root.dataset.ready = 'true';
    const dialog = root.querySelector('dialog');
    const form = root.querySelector('form');
    const message = root.querySelector('#coqp-report-message');
    const send = form.querySelector('[type=submit]');
    const close = root.querySelector('#coqp-report-close');
    const feedback = (text, type) => {
        if (window.coqpToast) { message.textContent = ''; window.coqpToast(text, type); }
        else { message.textContent = text; }
    };
    let sending = false;
    root.querySelector('#coqp-report-open').addEventListener('click', () => {
        form.elements.page_path.value = location.pathname;
        dialog.showModal();
    });
    close.addEventListener('click', () => { if (!sending) dialog.close(); });
    dialog.addEventListener('cancel', event => { if (sending) event.preventDefault(); });
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (sending || !form.reportValidity()) return;
        const file = form.elements.screenshot.files[0];
        if (file && file.size > 4 * 1024 * 1024) {
            feedback('Please choose a screenshot smaller than 4 MB.', 'warning');
            return;
        }
        sending = true; send.disabled = true; close.disabled = true;
        message.textContent = 'Sending your report…';
        try {
            const response = await fetch(form.action, {method:'POST', body:new FormData(form), credentials:'same-origin', headers:{Accept:'application/json'}});
            const body = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(response.status === 429 ? 'You have sent several reports. Please wait 10 minutes before trying again.' : response.status === 419 ? 'Your session expired. Keep a copy of your message, refresh the page, and try again.' : body.message || 'Your report could not be sent. Please try again.');
            form.reset();
            form.elements.page_path.value = location.pathname;
            dialog.close();
            feedback((body.message || 'Your report was sent.') + ' Reference #' + body.reference, 'success');
        } catch (error) { feedback(error.message || 'Please check your connection and try again.', 'danger'); }
        finally { sending = false; send.disabled = false; close.disabled = false; }
    });
})();
</script>
@endonce
