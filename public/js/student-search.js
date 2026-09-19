(function () {
    function hideResults(container) {
        var results = container.querySelector('[data-student-search-results]');
        if (results) {
            results.classList.add('hidden');
            results.innerHTML = '';
        }
    }

    function showSummary(container, student) {
        var summary = container.querySelector('[data-student-search-summary]');
        if (!summary) return;

        if (!student) {
            summary.classList.add('hidden');
            summary.innerHTML = '';
            return;
        }

        summary.innerHTML = '<p class="font-semibold text-primary-800 dark:text-primary-300">' + student.name + '</p>'
            + '<p class="mt-1 text-slate-600 dark:text-slate-400">NIS: ' + student.nis
            + (student.kelas ? ' · Kelas: ' + student.kelas : '') + '</p>';
        summary.classList.remove('hidden');
    }

    function selectStudent(container, student) {
        var input = container.querySelector('[data-student-search-input]');
        var hidden = container.querySelector('[data-student-search-id]');

        if (input) input.value = student.label;
        if (hidden) hidden.value = student.id;
        hideResults(container);
        showSummary(container, student);
        container.dispatchEvent(new CustomEvent('student-selected', { detail: student, bubbles: true }));
    }

    function clearStudent(container) {
        var input = container.querySelector('[data-student-search-input]');
        var hidden = container.querySelector('[data-student-search-id]');

        if (input) input.value = '';
        if (hidden) hidden.value = '';
        hideResults(container);
        showSummary(container, null);
        container.dispatchEvent(new CustomEvent('student-cleared', { bubbles: true }));
    }

    window.initStudentSearch = function () {
        document.querySelectorAll('[data-student-search]').forEach(function (container) {
            if (container.dataset.studentSearchBound) return;
            container.dataset.studentSearchBound = '1';

            var input = container.querySelector('[data-student-search-input]');
            var results = container.querySelector('[data-student-search-results]');
            var lookupUrl = container.dataset.lookupUrl;
            var debounceTimer = null;

            if (!input || !results || !lookupUrl) return;

            input.addEventListener('input', function () {
                var hidden = container.querySelector('[data-student-search-id]');
                if (hidden) hidden.value = '';

                showSummary(container, null);
                clearTimeout(debounceTimer);

                var q = input.value.trim();
                if (q.length < 3) {
                    hideResults(container);
                    return;
                }

                debounceTimer = setTimeout(function () {
                    fetch(lookupUrl + '?q=' + encodeURIComponent(q), {
                        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    })
                        .then(function (res) { return res.json(); })
                        .then(function (payload) {
                            var items = payload.data || (payload.results || []).map(function (item) {
                                return {
                                    id: item.id,
                                    label: item.text,
                                    name: item.text,
                                    nis: '',
                                };
                            });
                            if (!items.length) {
                                results.innerHTML = '<p class="px-3 py-2 text-sm text-slate-500">Siswa tidak ditemukan.</p>';
                                results.classList.remove('hidden');
                                return;
                            }

                            results.innerHTML = items.map(function (student) {
                                return '<button type="button" class="student-search-item block w-full px-3 py-2 text-left text-sm hover:bg-slate-50 dark:hover:bg-slate-800" data-student=\'' + JSON.stringify(student).replace(/'/g, '&#39;') + '\'>' + student.label + '</button>';
                            }).join('');
                            results.classList.remove('hidden');

                            results.querySelectorAll('.student-search-item').forEach(function (btn) {
                                btn.addEventListener('click', function () {
                                    selectStudent(container, JSON.parse(btn.dataset.student));
                                });
                            });
                        })
                        .catch(function () {
                            hideResults(container);
                        });
                }, 250);
            });

            input.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') hideResults(container);
            });

            document.addEventListener('click', function (e) {
                if (!container.contains(e.target)) hideResults(container);
            });
        });
    };

    window.clearStudentSearch = function (container) {
        if (!container) return;
        clearStudent(container);
    };

    document.addEventListener('DOMContentLoaded', function () {
        window.initStudentSearch?.();
    });
})();
