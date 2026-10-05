<script>
(function () {
    if (typeof document === 'undefined') return;

    /* --- copy to clipboard (result + tray links) --- */
    if (!document.documentElement.hasAttribute('data-sk-copy-bound')) {
        document.documentElement.setAttribute('data-sk-copy-bound', '1');

        document.addEventListener('click', async function (event) {
            var button = event.target.closest('[data-copy-value]');
            if (!button) return;

            var value = button.getAttribute('data-copy-value') || '';
            var done = false;

            try {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    await navigator.clipboard.writeText(value);
                    done = true;
                }
            } catch (e) {
                done = false;
            }

            if (!done) {
                try {
                    var ta = document.createElement('textarea');
                    ta.value = value;
                    ta.setAttribute('readonly', '');
                    ta.style.position = 'fixed';
                    ta.style.opacity = '0';
                    document.body.appendChild(ta);
                    ta.select();
                    done = document.execCommand('copy');
                    document.body.removeChild(ta);
                } catch (e) {
                    done = false;
                }
            }

            var scope = button.closest('[role="status"]') || button.closest('[data-sk-tray]');
            var feedback = scope ? scope.querySelector('[data-copy-feedback]') : null;
            var label = button.querySelector('[data-copy-label]') || null;
            var form = button.closest('[data-sk-form]');
            var doneWord = (form && form.dataset.tCopied) || 'Copied';
            var failText = (form && form.dataset.tCopyfail) || 'Copy failed — select the link manually.';

            if (done) {
                if (label) {
                    var original = label.textContent;
                    label.textContent = doneWord;
                    if (feedback) feedback.hidden = false;
                    setTimeout(function () {
                        label.textContent = original;
                        if (feedback) feedback.hidden = true;
                    }, 2000);
                } else {
                    var originalHtml = button.innerHTML;
                    button.innerHTML = doneWord;
                    if (feedback) feedback.hidden = false;
                    setTimeout(function () {
                        button.innerHTML = originalHtml;
                        if (feedback) feedback.hidden = true;
                    }, 2000);
                }
            } else if (feedback) {
                feedback.textContent = failText;
                feedback.hidden = false;
            }
        });
    }

    /* --- paste + clear helpers (sync back to Livewire) --- */
    function wireInput(root) {
        return root ? root.querySelector('#public_destination_url') : null;
    }

    document.addEventListener('click', async function (event) {
        var paste = event.target.closest('[data-sk-paste]');
        var clear = event.target.closest('[data-sk-clear]');
        if (!paste && !clear) return;

        var root = (paste || clear).closest('[data-sk-form]');
        var input = wireInput(root || document);
        if (!input) return;

        if (paste) {
            try {
                var text = await navigator.clipboard.readText();
                /* Grab the first URL when clipboard holds prose. */
                var found = (text || '').match(/https?:\/\/[^\s<>"']+/);
                input.value = (found ? found[0] : (text || '').trim()).slice(0, {{ \App\Services\LinkService::PUBLIC_MAX_URL_LENGTH }});
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.focus();
            } catch (e) {
                input.focus();
            }
        } else {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.focus();
        }
    });

    /* --- recent-links tray (localStorage, this device only) --- */
    var TRAY_KEY = 'sk-recent-links';
    var TRAY_COLLAPSED_KEY = 'sk-recent-links-collapsed';
    var TRAY_MAX = 5;

    function readTray() {
        try {
            var list = JSON.parse(localStorage.getItem(TRAY_KEY) || '[]');
            return Array.isArray(list) ? list : [];
        } catch (e) {
            return [];
        }
    }

    function writeTray(list) {
        try {
            localStorage.setItem(TRAY_KEY, JSON.stringify(list.slice(0, TRAY_MAX)));
        } catch (e) { /* private mode */ }
    }

    function paintTray() {
        var tray = document.querySelector('[data-sk-tray]');
        var list = tray ? tray.querySelector('[data-sk-recent]') : null;
        if (!tray || !list) return;

        var items = readTray();
        tray.hidden = false;
        var empty = tray.querySelector('[data-sk-tray-empty]');
        if (empty) empty.hidden = items.length !== 0;
        list.innerHTML = '';

        items.forEach(function (item) {
            var li = document.createElement('li');

            var link = document.createElement('a');
            link.href = item.short;
            link.target = '_blank';
            link.rel = 'noopener';
            link.textContent = item.short.replace(/^https?:\/\//, '');

            var copy = document.createElement('button');
            copy.type = 'button';
            copy.setAttribute('data-copy-value', item.short);
            copy.setAttribute('aria-label', ((tray.dataset && tray.dataset.tCopy) || 'copy') + ' ' + item.short);
            copy.textContent = (tray.dataset && tray.dataset.tCopy) || 'copy';

            var qr = document.createElement('a');
            qr.className = 'sk-tray-qr';
            qr.href = item.short + '/qr.png';
            qr.target = '_blank';
            qr.rel = 'noopener';
            qr.textContent = 'qr';

            li.appendChild(link);
            li.appendChild(copy);
            li.appendChild(qr);
            list.appendChild(li);
        });
    }

    function setTrayCollapsed(collapsed) {
        var tray = document.querySelector('[data-sk-tray]');
        var content = tray ? tray.querySelector('[data-sk-tray-content]') : null;
        var toggle = tray ? tray.querySelector('[data-sk-tray-toggle]') : null;
        if (!tray || !content || !toggle) return;

        tray.classList.toggle('is-collapsed', collapsed);
        content.hidden = collapsed;
        toggle.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
        toggle.textContent = collapsed ? (toggle.dataset.tShow || 'show') : (toggle.dataset.tHide || 'hide');
        try {
            localStorage.setItem(TRAY_COLLAPSED_KEY, collapsed ? '1' : '0');
        } catch (e) { /* private mode */ }
    }

    function recordFromResult(scope) {
        var link = scope.querySelector('[data-sk-result-link]');
        if (!link) return;

        var short = link.href;
        var original = scope.querySelector('[data-sk-result-original]');

        var items = readTray().filter(function (item) { return item.short !== short; });
        items.unshift({
            short: short,
            original: original ? original.getAttribute('title') || '' : '',
            at: Date.now(),
        });
        writeTray(items);
        paintTray();
    }

    /* Livewire swaps the form/result via morphs — watch for fresh results. */
    var observed = false;
    function observe() {
        var zone = document.querySelector('[data-sk-form]');
        if (!zone || observed) return;
        observed = true;

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) return;
                    if (node.matches && node.matches('[role="status"]')) recordFromResult(node);
                    var nested = node.querySelector ? node.querySelector('[role="status"]') : null;
                    if (nested) recordFromResult(nested);
                });
            });
        }).observe(zone, { childList: true, subtree: true });

        var initial = zone.querySelector('[role="status"]');
        if (initial) recordFromResult(initial);
    }

    document.addEventListener('click', function (event) {
        if (event.target.closest('[data-sk-tray-toggle]')) {
            var tray = document.querySelector('[data-sk-tray]');
            setTrayCollapsed(!tray || !tray.classList.contains('is-collapsed'));
        }
        if (event.target.closest('[data-sk-tray-clear]')) {
            writeTray([]);
            paintTray();
        }
    });

    paintTray();
    try {
        setTrayCollapsed(localStorage.getItem(TRAY_COLLAPSED_KEY) === '1');
    } catch (e) { /* private mode */ }
    observe();
    document.addEventListener('livewire:navigated', function () {
        observed = false;
        paintTray();
        observe();
    });
})();
</script>
