(() => {
    const protectCopy = document.currentScript?.dataset.protectCopy === 'true';
    const editable = target => target instanceof Element && Boolean(target.closest('input, textarea, select, [contenteditable]:not([contenteditable="false"])'));

    document.addEventListener('contextmenu', event => {
        if (!editable(event.target)) event.preventDefault();
    });

    for (const name of ['copy', 'cut', 'selectstart', 'dragstart']) {
        document.addEventListener(name, event => {
            if (protectCopy && !editable(event.target)) event.preventDefault();
        });
    }

    document.addEventListener('keydown', event => {
        const key = event.key.toLowerCase();
        const modifier = event.ctrlKey || event.metaKey;
        const developerShortcut = key === 'f12' || (modifier && key === 'u') || (modifier && event.shiftKey && ['i', 'j', 'c', 'k'].includes(key)) || (event.metaKey && event.altKey && ['i', 'j', 'c', 'u'].includes(key));
        const contentShortcut = protectCopy && modifier && ['c', 'x', 'a', 's', 'p'].includes(key) && !editable(event.target);

        if (developerShortcut || contentShortcut) event.preventDefault();
    }, true);

    const style = document.createElement('style');
    style.textContent = 'body{-webkit-user-select:none;user-select:none}input,textarea,select,[contenteditable]:not([contenteditable="false"]){-webkit-user-select:text;user-select:text}';
    if (protectCopy) document.head.appendChild(style);
})();
