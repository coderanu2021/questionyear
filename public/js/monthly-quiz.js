(() => {
    'use strict';

    const completedDraft = document.querySelector('[data-completed-draft]');
    if (completedDraft) {
        try { sessionStorage.removeItem(completedDraft.dataset.completedDraft); } catch {}
    }

    const form = document.querySelector('[data-monthly-quiz]');
    if (!form) return;

    const questions = [...form.querySelectorAll('[data-question-index]')];
    const batches = [...form.querySelectorAll('[data-question-batch]')];
    const navigation = [...form.querySelectorAll('[data-question-target]')];
    const radios = questions.map(question => [...question.querySelectorAll('input[type="radio"]')]);
    const range = form.querySelector('[data-range]');
    const answeredLabel = form.querySelector('[data-answered]');
    const progress = form.querySelector('[data-progress]');
    const pageNumber = form.querySelector('[data-page-number]');
    const previous = form.querySelector('[data-previous]');
    const next = form.querySelector('[data-next]');
    const draftStatus = form.querySelector('[data-draft-status]');
    const announcement = form.querySelector('[data-page-status]');
    const submitButton = form.querySelector('button[type="submit"]');
    const signature = form.dataset.questionSignature;
    const draftKey = form.dataset.draftKey;
    const answers = radios.map(options => options.findIndex(option => option.checked));
    let page = 0;
    let submitting = false;
    let storageAvailable = true;
    let answeredCount = answers.filter(answer => answer >= 0).length;

    try {
        const saved = JSON.parse(sessionStorage.getItem(draftKey));
        if (form.dataset.hasOldAnswers !== 'true' && saved?.signature === signature
            && Array.isArray(saved.answers) && saved.answers.length === questions.length
            && saved.answers.every((answer, index) => Number.isInteger(answer) && answer >= -1 && answer < radios[index].length)) {
            saved.answers.forEach((answer, index) => {
                answers[index] = answer;
                if (answer >= 0) radios[index][answer].checked = true;
            });
            page = Number.isInteger(saved.page) ? Math.max(0, Math.min(saved.page, batches.length - 1)) : 0;
            answeredCount = answers.filter(answer => answer >= 0).length;
            if (answeredCount) draftStatus.textContent = 'Your saved answers have been restored in this tab.';
        }
    } catch {
        storageAvailable = false;
    }

    const saveDraft = () => {
        try {
            sessionStorage.setItem(draftKey, JSON.stringify({ signature, answers, page }));
            storageAvailable = true;
        } catch {
            storageAvailable = false;
        }
        if (!storageAvailable) draftStatus.textContent = 'Answers are kept while this page is open. Browser storage is unavailable; avoid refreshing.';
    };

    const updateQuestion = index => {
        const answered = answers[index] >= 0;
        navigation[index].classList.toggle('is-answered', answered);
        navigation[index].setAttribute('aria-label', `Question ${index + 1}, ${answered ? 'answered' : 'unanswered'}`);
    };

    const updateProgress = () => {
        progress.value = answeredCount;
        answeredLabel.textContent = `${answeredCount} answered · ${questions.length - answeredCount} unanswered`;
    };

    const showPage = (requestedPage, focus = false, questionIndex = null) => {
        const oldPage = page;
        page = Math.max(0, Math.min(requestedPage, batches.length - 1));
        if (oldPage !== page) {
            batches[oldPage].hidden = true;
            batches[page].hidden = false;
        }
        const first = page * 20;
        const last = Math.min(first + 20, questions.length);
        range.textContent = `Questions ${first + 1}–${last} of ${questions.length}`;
        pageNumber.textContent = `Page ${page + 1} of ${batches.length}`;
        previous.disabled = page === 0;
        next.disabled = page === batches.length - 1;
        navigation.forEach((button, index) => {
            if (index >= first && index < last) button.setAttribute('aria-current', 'true');
            else button.removeAttribute('aria-current');
        });
        saveDraft();
        if (focus) {
            announcement.textContent = range.textContent;
            const target = questionIndex === null ? range : questions[questionIndex];
            target.focus({ preventScroll: true });
            target.scrollIntoView({ block: 'start', behavior: 'instant' });
        }
    };

    batches.forEach((batch, index) => { batch.hidden = index !== page; });
    questions.forEach((question, index) => updateQuestion(index));
    form.querySelectorAll('[data-monthly-controls]').forEach(control => { control.hidden = false; });
    updateProgress();
    showPage(page);

    form.addEventListener('change', event => {
        const input = event.target;
        if (!input.matches('input[type="radio"]')) return;
        const question = input.closest('[data-question-index]');
        if (!question) return;
        const index = Number(question.dataset.questionIndex);
        if (answers[index] < 0) answeredCount++;
        answers[index] = Number(input.value);
        updateQuestion(index);
        updateProgress();
        saveDraft();
    });

    previous.addEventListener('click', () => showPage(page - 1, true));
    next.addEventListener('click', () => showPage(page + 1, true));
    form.querySelector('.monthly-question-grid').addEventListener('click', event => {
        const button = event.target.closest('[data-question-target]');
        if (!button) return;
        const index = Number(button.dataset.questionTarget);
        showPage(Math.floor(index / 20), true, index);
    });

    form.addEventListener('submit', event => {
        if (submitting) {
            event.preventDefault();
            return;
        }
        const unanswered = questions.length - answeredCount;
        const message = `Submit your monthly quiz? You answered ${answeredCount} of ${questions.length} questions.`
            + (unanswered ? ` ${unanswered} unanswered questions will earn 0 marks.` : '')
            + ' You can submit this set only once.';
        if (!window.confirm(message)) {
            event.preventDefault();
            return;
        }
        saveDraft();
        submitting = true;
        submitButton.disabled = true;
        submitButton.textContent = 'Submitting…';
        form.setAttribute('aria-busy', 'true');
    });

    window.addEventListener('beforeunload', event => {
        if (!answeredCount || submitting) return;
        saveDraft();
        event.preventDefault();
        event.returnValue = '';
    });

    window.addEventListener('pageshow', event => {
        if (!event.persisted) return;
        submitting = false;
        submitButton.disabled = false;
        submitButton.textContent = 'Submit MCQ →';
        form.removeAttribute('aria-busy');
    });
})();
