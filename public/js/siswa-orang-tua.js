/**
 * Data Siswa — modal list / add / remove orang tua (siswa as child of parent).
 */
(function () {
    'use strict';

    var state = {
        listUrl: null,
        assignUrl: null,
        reloadPage: false,
    };

    function modal() {
        return document.getElementById('siswa-orang-tua-modal');
    }

    function form() {
        return document.getElementById('siswa-orang-tua-form');
    }

    function setText(selector, value) {
        var el = document.querySelector(selector);
        if (el) {
            el.textContent = value || '—';
        }
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function contactLine(label, name, phone) {
        if (!name && !phone) {
            return '';
        }

        return '<p class="text-muted text-sm">'
            + escapeHtml(label) + ': '
            + escapeHtml(name || '-')
            + (phone ? ' · ' + escapeHtml(phone) : '')
            + '</p>';
    }

    function renderList(items) {
        var list = document.querySelector('[data-siswa-orang-tua-list]');
        var empty = document.querySelector('[data-siswa-orang-tua-empty]');
        var count = document.querySelector('[data-siswa-orang-tua-count]');
        if (!list) {
            return;
        }

        list.querySelectorAll('[data-siswa-orang-tua-item]').forEach(function (el) {
            el.remove();
        });

        if (count) {
            count.textContent = (items.length || 0) + ' terhubung';
        }

        if (!items.length) {
            if (empty) {
                empty.classList.remove('hidden');
            }
            return;
        }

        if (empty) {
            empty.classList.add('hidden');
        }

        items.forEach(function (item) {
            var row = document.createElement('div');
            row.className = 'flex flex-wrap items-start justify-between gap-3 py-3';
            row.setAttribute('data-siswa-orang-tua-item', '1');

            var actions = '';
            if (item.show_url) {
                actions += '<a href="' + escapeHtml(item.show_url) + '" class="btn-secondary btn-sm" title="Detail orang tua">'
                    + '<i class="ti ti-eye text-sm"></i></a>';
            }
            if (item.remove_url) {
                actions += '<button type="button" class="btn-danger btn-sm" title="Hapus keterkaitan"'
                    + ' data-fetch-delete="' + escapeHtml(item.remove_url) + '"'
                    + ' data-confirm-title="Hapus Keterkaitan"'
                    + ' data-confirm-message="Orang tua ini akan dilepas dari siswa."'
                    + ' data-confirm-detail="' + escapeHtml(JSON.stringify([
                        { label: 'Orang tua', value: item.name || '-' },
                    ])) + '"'
                    + ' data-confirm-text="Ya, Hapus"'
                    + ' data-confirm-tone="danger"'
                    + ' data-confirm-icon="ti-unlink"'
                    + ' data-confirm-header-icon="ti-unlink"'
                    + '>'
                    + '<i class="ti ti-unlink text-sm"></i></button>';
            }

            row.innerHTML = ''
                + '<div class="min-w-0 flex-1">'
                +   '<p class="font-medium text-slate-900 dark:text-white">' + escapeHtml(item.name) + '</p>'
                +   contactLine('Ayah', item.nama_ayah, item.telepon_ayah)
                +   contactLine('Ibu', item.nama_ibu, item.telepon_ibu)
                +   contactLine('Wali', item.nama_wali, item.telepon_wali)
                + '</div>'
                + '<div class="flex items-center gap-2">' + actions + '</div>';
            list.appendChild(row);
        });
    }

    async function loadList() {
        if (!state.listUrl) {
            return;
        }

        try {
            var response = await fetch(state.listUrl, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            var payload = await response.json();
            if (!response.ok) {
                await window.showAlert?.({
                    title: 'Gagal',
                    message: payload.message || 'Tidak dapat memuat data orang tua.',
                    variant: 'danger',
                });
                return;
            }

            var data = payload.data || {};
            var siswa = data.siswa || {};
            state.assignUrl = data.assign_url || null;

            setText('[data-siswa-orang-tua-name]', siswa.name || '—');
            setText(
                '[data-siswa-orang-tua-meta]',
                [siswa.nis ? 'NIS ' + siswa.nis : null, siswa.kelas || null].filter(Boolean).join(' · ') || '—'
            );

            var assignForm = form();
            if (assignForm && state.assignUrl) {
                assignForm.action = state.assignUrl;
                assignForm.dataset.defaultAction = state.assignUrl;
            }

            renderList(Array.isArray(data.items) ? data.items : []);
            setAssignVisibility((data.items || []).length > 0);
            window.initAjaxSelects?.(modal());
        } catch (err) {
            await window.showAlert?.({
                title: 'Koneksi Gagal',
                message: 'Tidak dapat memuat data orang tua.',
                variant: 'danger',
            });
        }
    }

    function setAssignVisibility(hasParent) {
        var assignForm = form();
        var warning = document.querySelector('[data-siswa-orang-tua-limit-warning]');
        var closeOnly = document.querySelector('[data-siswa-orang-tua-close-only]');

        if (warning) {
            warning.classList.toggle('hidden', !hasParent);
        }

        if (assignForm) {
            assignForm.classList.toggle('hidden', hasParent);
        }

        if (closeOnly) {
            closeOnly.classList.toggle('hidden', !hasParent);
            closeOnly.classList.toggle('flex', hasParent);
        }
    }

    async function openForSiswa(trigger) {
        state.listUrl = trigger.getAttribute('data-siswa-orang-tua-url') || null;
        state.reloadPage = trigger.hasAttribute('data-reload-page')
            || trigger.getAttribute('data-siswa-orang-tua-reload') === 'page';

        var assignForm = form();
        if (assignForm) {
            assignForm.reset();
            window.resetAjaxSelects?.(assignForm);
            if (state.reloadPage) {
                assignForm.setAttribute('data-reload-page', '');
                assignForm.removeAttribute('data-reload-table');
            } else {
                assignForm.removeAttribute('data-reload-page');
                assignForm.setAttribute('data-reload-table', '');
            }
        }

        modal()?.classList.remove('hidden');
        window.applyModalTitle?.('siswa-orang-tua-modal', 'create', 'Orang Tua / Wali');
        await loadList();
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-siswa-orang-tua]');
        if (!trigger) {
            return;
        }
        event.preventDefault();
        openForSiswa(trigger);
    });

    document.addEventListener('DOMContentLoaded', function () {
        var assignForm = form();
        if (assignForm) {
            assignForm.addEventListener('fetch-success', function () {
                if (state.reloadPage) {
                    return;
                }
                loadList();
            });
        }
    });

    document.addEventListener('fetch-delete-success', function (event) {
        var btn = event.target.closest?.('#siswa-orang-tua-modal [data-fetch-delete]');
        if (!btn) {
            return;
        }

        if (state.reloadPage) {
            setTimeout(function () { window.location.reload(); }, 300);
            return;
        }

        loadList();
    });
})();
