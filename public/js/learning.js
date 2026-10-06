document.addEventListener('click', async event => {
    const button = event.target.closest('[data-bookmark], [data-report], [data-explanation]');
    if (!button) return;
    const tools = button.closest('.learning-tools');
    const message = tools.querySelector('[data-tool-message]');
    const endpoints = window.LEARNING_ENDPOINTS;
    if (!endpoints) return;
    const payload = {source: tools.dataset.source};
    let action;
    if (button.hasAttribute('data-bookmark')) {
        action = 'bookmark'; payload.bookmarked = button.dataset.bookmark === '1';
    } else if (button.hasAttribute('data-report')) {
        action = 'report'; payload.reason = tools.querySelector('[data-report-reason]').value.trim();
        if (payload.reason.length < 10) { message.textContent = 'Please describe the issue in at least 10 characters.'; return; }
    } else {
        action = 'explanation'; payload.language = button.dataset.explanation;
    }
    button.disabled = true; message.textContent = 'Saving…';
    try {
        const response = await fetch(endpoints[action], {method: 'POST', headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content}, body: JSON.stringify(payload)});
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Please sign in and try again.');
        if (action === 'bookmark') {
            message.textContent = result.bookmarked ? 'Question saved in My learning.' : 'Bookmark removed.';
            button.dataset.bookmark = result.bookmarked ? '0' : '1';
            button.textContent = result.bookmarked ? 'Remove bookmark' : 'Save question';
        } else if (action === 'explanation') {
            const translated = tools.querySelector('[data-translated]');
            translated.textContent = result.explanation || 'No explanation has been added yet.';
            translated.lang = payload.language; translated.hidden = false;
            message.textContent = payload.language === 'hi' ? 'Hindi translation · translated with AI' : 'English explanation';
        } else message.textContent = result.message;
    } catch (error) { message.textContent = error.message; }
    finally { button.disabled = false; }
});

document.querySelector('[data-copy-link]')?.addEventListener('click', async () => {
    const link = document.getElementById('challenge-link');
    const message = document.querySelector('[data-copy-message]');
    try { await navigator.clipboard.writeText(link.value); message.textContent = 'Link copied. Share it with a friend.'; }
    catch { link.select(); message.textContent = 'Select and copy the link above.'; }
});

const timer = document.querySelector('[data-timer]');
if (timer) {
    const form = document.getElementById('learning-quiz');
    const started = performance.now();
    const total = Number(timer.dataset.timer);
    let submitted = false;
    const tick = () => {
        const remaining = Math.max(0, total - Math.floor((performance.now() - started) / 1000));
        timer.querySelector('[data-time]').textContent = Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0');
        if (remaining === 0 && !submitted) { submitted = true; form.requestSubmit(); }
    };
    form.addEventListener('submit', () => { submitted = true; form.querySelector('button[type="submit"]').disabled = true; });
    tick(); setInterval(tick, 500);
}

function highlightPreferredExplanation() {
    document.querySelectorAll('[data-explanation]').forEach(button => {
        const preferred = button.dataset.explanation === window.LEARNING_LANGUAGE;
        if (button.classList.contains('preferred-language') !== preferred) {
            button.classList.toggle('preferred-language', preferred);
        }
    });
}
highlightPreferredExplanation();
new MutationObserver(highlightPreferredExplanation).observe(document.body, {childList: true, subtree: true});
