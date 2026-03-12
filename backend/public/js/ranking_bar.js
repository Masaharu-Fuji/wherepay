document.addEventListener('DOMContentLoaded', function () {
    var bar = document.getElementById('ranking-bar');
    var toggle = document.getElementById('ranking-bar-toggle');
    if (!bar || !toggle) {
        return;
    }

    var textSpan = toggle.querySelector('span[data-open-text]');
    var isOpen = false;

    function updateState() {
        if (isOpen) {
            bar.classList.remove('translate-y-[calc(100%-3rem)]');
            if (textSpan) {
                textSpan.textContent = textSpan.getAttribute('data-open-text') || '閉じる';
            }
        } else {
            bar.classList.add('translate-y-[calc(100%-3rem)]');
            if (textSpan) {
                textSpan.textContent = textSpan.getAttribute('data-closed-text') || '開く';
            }
        }
    }

    toggle.addEventListener('click', function () {
        isOpen = !isOpen;
        updateState();
    });
});

