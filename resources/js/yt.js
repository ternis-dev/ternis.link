/**
 * href.yt — Video & Creator Link Accelerator
 * Custom client-side enhancements for video URL detection, timestamp helpers,
 * and cinematic studio interactions.
 */

document.addEventListener('DOMContentLoaded', () => {
    initVideoUrlInspector();
    initTimestampHelpers();
    initSampleFillers();
    initCopyEnhancer();
});

/**
 * Live inspector: recognizes YouTube, Twitch, TikTok, Vimeo, etc.
 * and displays an animated platform tag next to the shortener input.
 */
function initVideoUrlInspector() {
    const input = document.querySelector('input[type="url"], input[name="destination_url"], input[wire\\:model\\.live="destination_url"], input[wire\\:model="destination_url"], input[data-yt-input]');
    const badgeContainer = document.getElementById('yt-detected-platform');
    
    if (!input) return;

    function inspect(url) {
        if (!badgeContainer) return;
        const val = (url || '').trim().toLowerCase();
        
        let platform = null;
        let icon = '';
        let detail = '';

        if (val.includes('youtube.com/shorts/') || val.includes('youtu.be/shorts/')) {
            platform = 'YouTube Shorts';
            icon = '⚡';
            detail = 'Vertical Video';
        } else if (val.includes('youtube.com') || val.includes('youtu.be')) {
            platform = 'YouTube';
            icon = '▶';
            detail = 'Video / Stream';
        } else if (val.includes('twitch.tv/videos/') || val.includes('clips.twitch.tv') || val.includes('twitch.tv/')) {
            platform = 'Twitch';
            icon = '🟣';
            detail = 'Stream / Clip';
        } else if (val.includes('tiktok.com/')) {
            platform = 'TikTok';
            icon = '🎵';
            detail = 'Short Form';
        } else if (val.includes('vimeo.com/')) {
            platform = 'Vimeo';
            icon = '🎬';
            detail = 'High Definition';
        } else if (val.includes('loom.com/')) {
            platform = 'Loom';
            icon = '🎥';
            detail = 'Screen Recording';
        }

        if (platform) {
            badgeContainer.innerHTML = `
                <div class="yt-platform-chip inline-flex items-center gap-1.5 rounded-full border border-red-500/30 bg-red-500/10 px-2.5 py-0.5 text-xs font-medium text-red-400">
                    <span>${icon}</span>
                    <span class="font-semibold">${platform}</span>
                    <span class="text-neutral-400 opacity-75">· ${detail}</span>
                </div>
            `;
            badgeContainer.classList.remove('hidden');
        } else {
            badgeContainer.innerHTML = '';
            badgeContainer.classList.add('hidden');
        }
    }

    input.addEventListener('input', (e) => inspect(e.target.value));
    inspect(input.value);
}

/**
 * Interactive timestamp helper: lets creators add or toggle ?t= parameters.
 */
function initTimestampHelpers() {
    const input = document.querySelector('input[type="url"], input[name="destination_url"], input[wire\\:model\\.live="destination_url"], input[wire\\:model="destination_url"], input[data-yt-input]');
    if (!input) return;

    document.querySelectorAll('[data-timestamp-add]').forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const seconds = parseInt(btn.getAttribute('data-timestamp-add'), 10);
            if (!seconds || !input.value) return;

            let url = input.value.trim();
            try {
                // If it doesn't have http, temporarily add it to parse cleanly
                const hasScheme = /^https?:\/\//i.test(url);
                const parseable = hasScheme ? url : 'https://' + url;
                const parsed = new URL(parseable);
                
                // Existing timestamp or clean
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
            } catch (err) {
                // Fallback string append
                if (url.includes('?')) {
                    url += `&t=${seconds}s`;
                } else {
                    url += `?t=${seconds}s`;
                }
                input.value = url;
                input.dispatchEvent(new Event('input', { bubbles: true }));
            }
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
                <span class="inline-flex items-center gap-1.5 text-emerald-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
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
