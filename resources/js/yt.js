/**
 * href.yt — Video & Creator Link Accelerator
 * Custom client-side enhancements for video URL detection, timestamp helpers,
 * live preview simulator, and cinematic studio interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    initVideoUrlInspector();
    initTimestampHelpers();
    initSampleFillers();
    initSimulatorDeck();
    initApiSnippetTabs();
    initCopyEnhancer();

    // Re-bind listeners if Livewire updates DOM
    document.addEventListener('livewire:navigated', () => {
        initVideoUrlInspector();
        initTimestampHelpers();
        initSampleFillers();
    });
});

/**
 * Live inspector: recognizes YouTube, Twitch, TikTok, Vimeo, Loom, Kick, etc.
 * and displays an animated platform tag next to the shortener input.
 */
function initVideoUrlInspector() {
    const input = document.querySelector('input[type="url"], input[name="destination_url"], input[wire\\:model\\.live="destination_url"], input[wire\\:model="destination_url"], input[data-yt-input]');
    const badgeContainers = document.querySelectorAll('.yt-detected-platform, #yt-detected-platform');
    
    if (!input) return;

    function inspect(url) {
        if (!badgeContainers.length) return;
        const val = (url || '').trim().toLowerCase();
        
        let platform = null;
        let icon = '';
        let detail = '';
        let colorClass = 'border-red-500/30 bg-red-500/10 text-red-400';

        if (val.includes('youtube.com/shorts/') || val.includes('youtu.be/shorts/')) {
            platform = 'YouTube Shorts';
            icon = '⚡';
            detail = 'Vertical Video · High Retention';
            colorClass = 'border-red-500/30 bg-red-500/10 text-red-400';
        } else if (val.includes('youtube.com') || val.includes('youtu.be')) {
            platform = 'YouTube';
            icon = '▶';
            detail = 'Video / Stream · Deep-link Ready';
            colorClass = 'border-red-500/30 bg-red-500/10 text-red-400';
        } else if (val.includes('twitch.tv/videos/') || val.includes('clips.twitch.tv') || val.includes('twitch.tv/')) {
            platform = 'Twitch';
            icon = '🟣';
            detail = 'Stream / Clip · Chat Friendly';
            colorClass = 'border-purple-500/30 bg-purple-500/10 text-purple-400';
        } else if (val.includes('tiktok.com/')) {
            platform = 'TikTok';
            icon = '🎵';
            detail = 'Short Form · Bio Ready';
            colorClass = 'border-cyan-500/30 bg-cyan-500/10 text-cyan-400';
        } else if (val.includes('vimeo.com/')) {
            platform = 'Vimeo';
            icon = '🎬';
            detail = 'High Definition · Direct Route';
            colorClass = 'border-blue-500/30 bg-blue-500/10 text-blue-400';
        } else if (val.includes('loom.com/')) {
            platform = 'Loom';
            icon = '🎥';
            detail = 'Screen Recording · Fast Load';
            colorClass = 'border-amber-500/30 bg-amber-500/10 text-amber-400';
        } else if (val.includes('kick.com/')) {
            platform = 'Kick';
            icon = '🟢';
            detail = 'Live Stream · Zero Friction';
            colorClass = 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400';
        } else if (val.length > 5) {
            platform = 'Web Destination';
            icon = '🌐';
            detail = 'Direct TLS 1.3 Redirect';
            colorClass = 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300';
        }

        badgeContainers.forEach(container => {
            if (platform) {
                container.innerHTML = `
                    <div class="inline-flex items-center gap-2 rounded-full border ${colorClass} px-3 py-1 text-xs font-semibold shadow-xs transition-all">
                        <span>${icon}</span>
                        <span>${platform}</span>
                        <span class="opacity-60 text-[11px] font-normal">· ${detail}</span>
                    </div>
                `;
                container.classList.remove('hidden');
            } else {
                container.innerHTML = '';
                container.classList.add('hidden');
            }
        });
    }

    input.addEventListener('input', (e) => inspect(e.target.value));
    inspect(input.value);
}

/**
 * Interactive timestamp helper: lets creators add or adjust ?t= parameters.
 */
function initTimestampHelpers() {
    const input = document.querySelector('input[type="url"], input[name="destination_url"], input[wire\\:model\\.live="destination_url"], input[wire\\:model="destination_url"], input[data-yt-input]');
    if (!input) return;

    document.querySelectorAll('[data-timestamp-add]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const seconds = parseInt(btn.getAttribute('data-timestamp-add'), 10);
            if (!seconds) return;

            let url = (input.value || '').trim();
            if (!url) {
                url = 'https://youtube.com/watch?v=dQw4w9WgXcQ';
            }

            try {
                const hasScheme = /^https?:\/\//i.test(url);
                const parseable = hasScheme ? url : 'https://' + url;
                const parsed = new URL(parseable);
                
                let currentSec = 0;
                if (parsed.searchParams.has('t')) {
                    const existing = parsed.searchParams.get('t');
                    currentSec = parseTimestampSeconds(existing);
                }
                
                const newSec = currentSec + seconds;
                parsed.searchParams.set('t', `${newSec}s`);
                
                const result = hasScheme ? parsed.toString() : parsed.toString().replace(/^https?:\/\//, '');
                input.value = result;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
            } catch (err) {
                const separator = url.includes('?') ? '&' : '?';
                input.value = `${url}${separator}t=${seconds}s`;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
            }
        });
    });

    document.querySelectorAll('[data-timestamp-clear]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            let url = (input.value || '').trim();
            if (!url) return;

            try {
                const hasScheme = /^https?:\/\//i.test(url);
                const parseable = hasScheme ? url : 'https://' + url;
                const parsed = new URL(parseable);
                parsed.searchParams.delete('t');
                const result = hasScheme ? parsed.toString() : parsed.toString().replace(/^https?:\/\//, '');
                input.value = result;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                input.focus();
            } catch (err) {}
        });
    });
}

function parseTimestampSeconds(str) {
    if (!str) return 0;
    if (/^\d+s?$/.test(str)) {
        return parseInt(str.replace('s', ''), 10) || 0;
    }
    let total = 0;
    const hours = str.match(/(\d+)h/);
    const mins = str.match(/(\d+)m/);
    const secs = str.match(/(\d+)s/);
    if (hours) total += parseInt(hours[1], 10) * 3600;
    if (mins) total += parseInt(mins[1], 10) * 60;
    if (secs) total += parseInt(secs[1], 10);
    return total;
}

/**
 * Pre-populate sample video links for quick interactive testing.
 */
function initSampleFillers() {
    const input = document.querySelector('input[type="url"], input[name="destination_url"], input[wire\\:model\\.live="destination_url"], input[wire\\:model="destination_url"], input[data-yt-input]');
    if (!input) return;

    document.querySelectorAll('[data-sample-url]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const sample = btn.getAttribute('data-sample-url');
            if (!sample) return;

            input.value = sample;
            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();

            // Smooth scroll to shortening desk if not in view
            const desk = document.getElementById('make');
            if (desk) {
                desk.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    });
}

/**
 * Interactive preview simulator tabs (Chat, OBS, Bio Description).
 */
function initSimulatorDeck() {
    const tabs = document.querySelectorAll('[data-sim-tab]');
    const panes = document.querySelectorAll('[data-sim-pane]');

    tabs.forEach(tab => {
        tab.addEventListener('click', () => {
            const targetId = tab.getAttribute('data-sim-tab');
            
            tabs.forEach(t => t.classList.remove('is-active'));
            tab.classList.add('is-active');

            panes.forEach(pane => {
                if (pane.getAttribute('data-sim-pane') === targetId) {
                    pane.classList.remove('hidden');
                } else {
                    pane.classList.add('hidden');
                }
            });
        });
    });
}

/**
 * Code snippet language switch for Developer API docs.
 */
function initApiSnippetTabs() {
    const langBtns = document.querySelectorAll('[data-api-lang]');
    const snippets = document.querySelectorAll('[data-api-snippet]');

    langBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const lang = btn.getAttribute('data-api-lang');

            langBtns.forEach(b => {
                b.classList.remove('border-red-500', 'text-white', 'bg-red-500/10');
                b.classList.add('text-neutral-400');
            });
            btn.classList.add('border-red-500', 'text-white', 'bg-red-500/10');
            btn.classList.remove('text-neutral-400');

            snippets.forEach(snippet => {
                if (snippet.getAttribute('data-api-snippet') === lang) {
                    snippet.classList.remove('hidden');
                } else {
                    snippet.classList.add('hidden');
                }
            });
        });
    });
}

/**
 * Copy to clipboard with custom visual confirmation.
 */
function initCopyEnhancer() {
    document.addEventListener('click', (e) => {
        const copyBtn = e.target.closest('[data-yt-copy]');
        if (!copyBtn) return;

        const targetSelector = copyBtn.getAttribute('data-yt-copy');
        const targetEl = document.querySelector(targetSelector);
        const textToCopy = targetEl ? (targetEl.value || targetEl.innerText || targetEl.textContent) : copyBtn.getAttribute('data-copy-text');

        if (!textToCopy) return;

        navigator.clipboard.writeText(textToCopy.trim()).then(() => {
            const originalHTML = copyBtn.innerHTML;
            copyBtn.innerHTML = `
                <span class="inline-flex items-center gap-1.5 text-emerald-400 font-bold">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>COPIED!</span>
                </span>
            `;
            setTimeout(() => {
                copyBtn.innerHTML = originalHTML;
            }, 2000);
        }).catch(() => {});
    });
}
