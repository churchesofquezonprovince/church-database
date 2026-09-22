(() => {
    if (window.coqpToast) return;
    const clean = value => String(value ?? '').replace(/\s+/g, ' ').trim();
    const escape = value => String(value).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    const titles = {success:'Completed', info:'Information', warning:'Please note', danger:'Please check'};
    const delivered = new Map();
    let region, scheduled, lastValidation = '', invalidTimer;
    const openDialog = () => Array.from(document.querySelectorAll('dialog[open]')).at(-1);
    function placeRegion() {
        if (!region) return;
        const parent = openDialog() || document.body;
        if (region.parentElement !== parent) parent.append(region);
        if (region.children.length && region.showPopover && !region.matches(':popover-open')) {
            try { region.showPopover(); } catch (_) { /* Fixed-position fallback. */ }
        }
    }
    function localToast(message, type, persistent, title) {
        if (region && Array.from(region.querySelectorAll('.coqp-toast-text')).some(el => clean(el.textContent) === clean(message))) { placeRegion(); return; }
        if (!region) {
            region = document.createElement('div');
            region.id = 'coqp-toast-region';
            region.setAttribute('popover', 'manual');
            region.setAttribute('aria-label', 'Notifications');
        }
        const owner = region;
        const card = document.createElement('div');
        card.className = 'coqp-toast-card';
        card.dataset.type = type;
        card.setAttribute('role', type === 'danger' ? 'alert' : 'status');
        card.setAttribute('aria-atomic', 'true');
        const heading = document.createElement('strong'); heading.textContent = title;
        const content = document.createElement('div'); content.className = 'coqp-toast-text'; content.textContent = message;
        const close = document.createElement('button'); close.className = 'coqp-toast-close'; close.type = 'button'; close.textContent = '×'; close.setAttribute('aria-label', 'Dismiss notification');
        let timer;
        const dismiss = () => { clearTimeout(timer); card.remove(); if (!owner.children.length && owner.hidePopover && owner.matches(':popover-open')) owner.hidePopover(); };
        const start = () => { clearTimeout(timer); if (!persistent) timer = setTimeout(dismiss, 10000); };
        close.addEventListener('click', dismiss);
        card.addEventListener('mouseenter', () => clearTimeout(timer));
        card.addEventListener('mouseleave', start);
        card.addEventListener('focusin', () => clearTimeout(timer));
        card.addEventListener('focusout', start);
        const icon = document.createElement('span'); icon.className = 'coqp-toast-icon'; icon.setAttribute('aria-hidden', 'true');
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('viewBox', '0 0 24 24'); svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor'); svg.setAttribute('stroke-width', '2');
        svg.setAttribute('stroke-linecap', 'round'); svg.setAttribute('stroke-linejoin', 'round');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', type === 'success' ? 'M5 12l4 4L19 6' : type === 'info' ? 'M12 11v6M12 7h.01' : 'M12 5v9M12 18h.01');
        svg.append(path); icon.append(svg);
        card.append(icon, heading, content, close); region.append(card); placeRegion(); start();
    }
    window.coqpToast = (message, type = 'info', options = {}) => {
        message = String(message ?? '').trim();
        if (!message) return;
        if (!Object.hasOwn(titles, type)) type = 'info';
        const title = clean(options.title) || titles[type];
        const persistent = options.persistent ?? (type === 'danger' || type === 'warning' || message.length > 300);
        // Use the existing Filament toast stack in the panel. This does not write to the bell.
        if (!openDialog() && window.FilamentNotification && window.Livewire && document.querySelector('.fi-no')) {
            const normalized = clean(message);
            const duplicate = Array.from(document.querySelectorAll('.fi-no .fi-no-notification')).some(el => clean(el.textContent).includes(normalized));
            if (duplicate) return;
            try {
                const notification = new window.FilamentNotification().title(escape(title)).body(escape(message).replace(/\n/g, '<br>')).status(type);
                persistent ? notification.persistent() : notification.duration(10000);
                notification.send();
                return;
            } catch (_) { /* Public-style fallback keeps feedback visible. */ }
        }
        localToast(message, type, persistent, title);
    };
    // Preserve sentence/row boundaries, and use text only: user content never becomes HTML.
    function messageText(element) {
        const copy = element.cloneNode(true);
        copy.querySelectorAll('script,style,button,input,select,textarea').forEach(el => el.remove());
        copy.querySelectorAll('p,li,div,br').forEach(el => el.append(document.createTextNode('\u001e')));
        return copy.textContent.split('\u001e').map(clean).filter(Boolean).join('\n');
    }
    function scan() {
        clearTimeout(scheduled);
        const present = new Set(); let aggregateErrors = false;
        document.querySelectorAll('[data-coqp-flash]').forEach(el => {
            const id = el.dataset.coqpFlashId;
            const message = messageText(el);
            present.add(id);
            if (el.dataset.coqpFlash === 'danger' && el.dataset.coqpKeep === 'true') aggregateErrors = true;
            if (message && delivered.get(id) !== message) {
                delivered.set(id, message);
                window.coqpToast(message, el.dataset.coqpFlash);
            }
            if (el.dataset.coqpKeep !== 'true') el.classList.add('coqp-flash-delivered');
        });
        for (const id of delivered.keys()) if (!present.has(id)) delivered.delete(id);
        const errors = [...new Set(Array.from(document.querySelectorAll('[data-coqp-field-error], .fi-fo-field-wrp-error-message, .fi-fo-field-wrp-error-list')).map(el => clean(el.textContent)).filter(Boolean))];
        const signature = errors.join('\n');
        if (!aggregateErrors && signature && signature !== lastValidation) window.coqpToast(signature, 'danger');
        lastValidation = signature;
        placeRegion();
    }
    const schedule = () => { clearTimeout(scheduled); scheduled = setTimeout(scan, 180); };
    const observer = new MutationObserver(records => {
        // Ignore native toasts, our own cards, and the attributes used to hide delivered messages.
        if (records.some(r => {
            const el = r.target.nodeType === 1 ? r.target : r.target.parentElement;
            return !el?.closest('#coqp-toast-region,.fi-no');
        })) schedule();
    });
    function start() {
        observer.observe(document.body, {childList:true, subtree:true, characterData:true});
        schedule();
    }
    document.addEventListener('livewire:navigating', () => {
        delivered.clear(); lastValidation = '';
        if (region) { region.remove(); region = null; }
    });
    document.addEventListener('livewire:navigated', schedule);
    document.addEventListener('close', placeRegion, true);
    document.addEventListener('invalid', event => {
        clearTimeout(invalidTimer);
        const form = event.target.form;
        invalidTimer = setTimeout(() => {
            const field = form?.querySelector(':invalid') || event.target;
            const label = field.labels?.[0]?.textContent || field.getAttribute('aria-label') || field.name || 'This field';
            const name = clean(label).replace(/\s*\*\s*$/, '');
            if (field.validity.valueMissing) {
                window.coqpToast('Please fill in “' + name + '”.', 'warning', {title:'Complete the required field'});
            } else {
                window.coqpToast(name + ': ' + field.validationMessage, 'warning', {title:'Check this field'});
            }
        }, 20);
    }, true);
    document.addEventListener('submit', () => { lastValidation = ''; }, true);
    document.addEventListener('click', event => {
        if (event.composedPath().some(el => el instanceof Element && Array.from(el.attributes).some(attr => attr.name.startsWith('wire:click')))) lastValidation = '';
    }, true);
    document.addEventListener('coqp:toast', event => window.coqpToast(event.detail?.message, event.detail?.type, event.detail || {}));
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, {once:true}); else start();
})();
