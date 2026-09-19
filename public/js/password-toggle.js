(function () {
    window.initPasswordToggle = function (root) {
        (root || document).querySelectorAll('[data-password-toggle]').forEach(function (btn) {
            if (btn.dataset.passwordToggleBound) {
                return;
            }

            btn.dataset.passwordToggleBound = '1';

            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.passwordToggle);
                if (!input) {
                    return;
                }

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';
                btn.setAttribute('aria-label', isPassword ? 'Sembunyikan password' : 'Tampilkan password');

                var showIcon = btn.querySelector('[data-icon-show]');
                var hideIcon = btn.querySelector('[data-icon-hide]');
                if (showIcon) {
                    showIcon.classList.toggle('hidden', isPassword);
                }
                if (hideIcon) {
                    hideIcon.classList.toggle('hidden', !isPassword);
                }
            });
        });
    };
})();
