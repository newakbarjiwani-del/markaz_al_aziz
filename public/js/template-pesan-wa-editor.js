(function () {
    var FORM_ID = 'template-pesan-tagihan-form';
    var MOUNT_ID = 'template-pesan-wa-editor';
    var TEXTAREA_NAME = 'isi_pesan';

    /** @type {object | null} */
    var editor = null;

    function getForm() {
        return document.getElementById(FORM_ID);
    }

    function getTextarea(form) {
        return form?.querySelector('[name="' + TEXTAREA_NAME + '"]') ?? null;
    }

    function showPlainTextarea() {
        var mount = document.getElementById(MOUNT_ID);
        var textarea = document.getElementById('template-pesan-isi');

        mount?.classList.add('hidden');
        if (textarea) {
            textarea.classList.remove('hidden');
            textarea.classList.add('form-input', 'min-h-[240px]', 'font-mono', 'text-sm');
            textarea.removeAttribute('aria-hidden');
            textarea.removeAttribute('tabindex');
        }
    }

    function setEditorPlainText(text) {
        if (!editor) {
            return;
        }

        var state = editor.state;
        var dispatch = editor.dispatch;
        var lines = String(text ?? '').split('\n');
        var paragraphs = lines.map(function (line) {
            var content = line ? state.schema.text(line) : null;
            return state.schema.nodes.paragraph.create(null, content);
        });

        if (!paragraphs.length) {
            paragraphs.push(state.schema.nodes.paragraph.create());
        }

        var fragment = state.schema.nodes.doc.create(null, paragraphs).content;
        dispatch(state.tr.replaceWith(0, state.doc.content.size, fragment));
    }

    function clearEditor() {
        var textarea = getTextarea(getForm());
        if (textarea) {
            textarea.value = '';
        }
        setEditorPlainText('');
    }

    function insertAtCursor(text) {
        if (!text) {
            return;
        }

        if (!editor) {
            var textarea = getTextarea(getForm());
            if (!textarea) {
                return;
            }

            var start = textarea.selectionStart ?? textarea.value.length;
            var end = textarea.selectionEnd ?? start;
            textarea.value = textarea.value.slice(0, start) + text + textarea.value.slice(end);
            textarea.selectionStart = textarea.selectionEnd = start + text.length;
            textarea.focus();
            return;
        }

        var state = editor.state;
        var from = state.selection.from;
        var to = state.selection.to;
        editor.dispatch(state.tr.insertText(text, from, to).scrollIntoView());
        editor.focus();
    }

    function bindInsertButtons() {
        document.addEventListener('mousedown', function (event) {
            var button = event.target.closest('[data-wa-insert]');
            if (!button || !button.closest('#template-pesan-tagihan-modal')) {
                return;
            }

            event.preventDefault();
        });

        document.addEventListener('click', function (event) {
            var button = event.target.closest('[data-wa-insert]');
            if (!button || !button.closest('#template-pesan-tagihan-modal')) {
                return;
            }

            event.preventDefault();
            insertAtCursor(button.dataset.waInsert || '');
        });
    }

    function syncTextareaFromEditor(form) {
        var textarea = getTextarea(form);
        if (!textarea) {
            return;
        }

        if (editor) {
            textarea.value = editor.getWhatsappMarkdown() ?? '';
        }
    }

    function initEditor() {
        var mount = document.getElementById(MOUNT_ID);
        var WhatsAppEditor = window.WhatsAppEditor;

        if (!mount || editor) {
            return;
        }

        if (typeof WhatsAppEditor !== 'function') {
            showPlainTextarea();
            return;
        }

        try {
            editor = new WhatsAppEditor(
                { position: 'BOTTOM', distance: 10 },
                mount
            );
        } catch (error) {
            console.error(error);
            showPlainTextarea();
            return;
        }

        var proseMirror = mount.querySelector('.ProseMirror');
        if (proseMirror) {
            proseMirror.setAttribute(
                'data-placeholder',
                'Contoh: Assalamualaikum… Ketik {nama_anak} atau klik placeholder di bawah.'
            );
        }
    }

    function bindForm(form) {
        form.addEventListener('submit', function (event) {
            syncTextareaFromEditor(form);

            var markdown = getTextarea(form)?.value?.trim();
            if (markdown) {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            window.showAlert?.({
                title: 'Periksa Data',
                message: 'Isi pesan wajib diisi.',
                variant: 'warning',
            });
        }, true);
    }

    function boot() {
        initEditor();
        bindInsertButtons();

        var form = getForm();
        if (form) {
            bindForm(form);
        }

        document.addEventListener('modalReset', function (e) {
            if (e.detail?.formId === FORM_ID) {
                clearEditor();
            }
        });

        document.addEventListener('edit-record-populated', function (e) {
            if (e.detail?.form?.id !== FORM_ID) {
                return;
            }

            setTimeout(function () {
                var textarea = getTextarea(e.detail.form);
                if (editor) {
                    setEditorPlainText(textarea?.value ?? '');
                }
            }, 0);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
