(() => {
    const field = document.getElementById('post-content');
    if (!field) return;
    const status = document.getElementById('post-editor-status');
    if (!window.ClassicEditor) {
        status.textContent = 'Editor could not load. Check your internet connection.';
        return;
    }
    class PostImageUploadAdapter {
        constructor(loader) {
            this.loader = loader;
            this.controller = new AbortController();
        }
        async upload() {
            const file = await this.loader.file;
            const data = new FormData();
            data.append('upload', file);
            const response = await fetch(field.dataset.uploadUrl, {
                method: 'POST', body: data, signal: this.controller.signal,
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
            });
            const result = await response.json();
            if (!response.ok || !result.url) throw new Error(result.errors?.upload?.[0] || result.message || 'Image upload failed. Please try again.');
            return { default: result.url };
        }
        abort() { this.controller.abort(); }
    }
    ClassicEditor.create(field, {
        toolbar: ['heading', '|', 'bold', 'italic', 'link', 'bulletedList', 'numberedList', '|', 'uploadImage', 'blockQuote', 'insertTable', '|', 'undo', 'redo'],
        extraPlugins: [editor => {
            editor.plugins.get('FileRepository').createUploadAdapter = loader => new PostImageUploadAdapter(loader);
        }]
    }).then(editor => {
        field.required = false;
        const submit = field.form.querySelector('button[type="submit"]');
        const pending = editor.plugins.get('PendingActions');
        pending.on('change:hasAny', () => {
            submit.disabled = pending.hasAny;
            status.textContent = pending.hasAny ? 'Image upload in progress…' : '';
        });
        field.form.addEventListener('submit', event => {
            field.value = editor.getData();
            if (pending.hasAny || !editor.getData().trim() || editor.getData().length > 100000) {
                event.preventDefault();
                status.textContent = pending.hasAny ? 'Please wait for the image upload.' : 'Enter content within 100,000 characters.';
                editor.editing.view.focus();
            }
        });
    }).catch(() => { status.textContent = 'Editor could not load. You can still enter text below.'; });
})();
