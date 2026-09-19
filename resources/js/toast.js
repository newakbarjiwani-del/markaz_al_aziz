export function initToast() {
    window.showToast = showToast;
}

export function showToast(message, type = 'success', duration = 5000) {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const colors = {
        success: 'border-emerald-500 bg-emerald-50 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-200',
        error: 'border-red-500 bg-red-50 text-red-800 dark:bg-red-950 dark:text-red-200',
        info: 'border-blue-500 bg-blue-50 text-blue-800 dark:bg-blue-950 dark:text-blue-200',
    };

    const toast = document.createElement('div');
    toast.className = `pointer-events-auto flex w-80 items-start gap-3 rounded-lg border-l-4 p-4 shadow-lg transition-all ${colors[type] || colors.info}`;
    toast.innerHTML = `
        <div class="flex-1 text-sm font-medium">${message}</div>
        <button type="button" class="shrink-0 rounded p-1 opacity-70 hover:opacity-100" aria-label="Tutup">&times;</button>
    `;

    const dismiss = () => {
        toast.classList.add('opacity-0', 'translate-x-4');
        setTimeout(() => toast.remove(), 200);
    };

    toast.querySelector('button').addEventListener('click', dismiss);
    container.appendChild(toast);

    if (duration > 0) {
        setTimeout(dismiss, duration);
    }
}
