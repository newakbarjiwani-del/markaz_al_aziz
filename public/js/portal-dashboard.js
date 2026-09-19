(function () {
    function formatPortalClock(date) {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        }).format(date);
    }

    function updatePortalClocks() {
        var now = new Date();
        document.querySelectorAll('[data-portal-clock]').forEach(function (el) {
            el.textContent = formatPortalClock(now);
        });
    }

    window.initPortalDashboard = function () {
        if (!document.querySelector('[data-portal-clock]')) {
            return;
        }

        updatePortalClocks();
        window.setInterval(updatePortalClocks, 30000);
    };

    document.addEventListener('DOMContentLoaded', window.initPortalDashboard);
})();
