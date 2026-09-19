(function () {
    function formatAdminDashboardDate(date) {
        return new Intl.DateTimeFormat('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        }).format(date);
    }

    function updateAdminDashboardDate() {
        document.querySelectorAll('[data-admin-dashboard-date]').forEach(function (el) {
            el.textContent = formatAdminDashboardDate(new Date());
        });
    }

    window.initAdminDashboard = function () {
        if (!document.querySelector('.admin-dashboard')) {
            return;
        }

        updateAdminDashboardDate();
    };

    document.addEventListener('DOMContentLoaded', window.initAdminDashboard);
})();
