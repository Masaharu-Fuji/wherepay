document.addEventListener('DOMContentLoaded', function () {
    const button = document.querySelector('[data-settlement-confirm]');
    if (!button) return;

    button.addEventListener('click', function (event) {
        const isFullySettled = button.dataset.isFullySettled === '1';

        if (isFullySettled) {
            const form = button.closest('form');
            if (form) {
                form.submit();
            }
            return;
        }

        event.preventDefault();
        showToast('まだ清算が完了していないメンバーがいます。すべてのおつりを確認してください。');
    });
});

function showToast(message) {
    let container = document.getElementById('toast-container');

    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'fixed inset-x-0 top-4 flex justify-center z-50 pointer-events-none';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = [
        'pointer-events-auto',
        'px-4 py-3',
        'rounded-lg shadow-lg',
        'bg-red-600 text-white text-sm',
        'max-w-md w-full mx-4',
        'flex items-center justify-between gap-3',
        'animate-fade-in-down',
    ].join(' ');
    toast.innerHTML = `
        <span>${message}</span>
        <button type="button" class="text-white/80 hover:text-white text-xs underline">閉じる</button>
    `;

    const closeButton = toast.querySelector('button');
    closeButton.addEventListener('click', function () {
        hideToast(toast);
    });

    container.appendChild(toast);

    setTimeout(function () {
        hideToast(toast);
    }, 4000);
}

function hideToast(toast) {
    if (!toast) return;
    toast.classList.add('animate-fade-out-up');
    setTimeout(function () {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 200);
}

