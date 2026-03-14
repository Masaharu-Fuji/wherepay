export default function rankingBar() {
    const bar = document.getElementById("ranking-bar");
    const toggle = document.getElementById("ranking-bar-toggle");
    if (!bar || !toggle) {
        return;
    }

    const textSpan = toggle.querySelector("span[data-open-text]");
    let isOpen = false;

    function updateState() {
        if (isOpen) {
            bar.classList.remove("translate-y-[calc(100%-5rem)]");
            if (textSpan) {
                textSpan.textContent =
                    textSpan.getAttribute("data-open-text") || "閉じる";
            }
        } else {
            bar.classList.add("translate-y-[calc(100%-5rem)]");
            if (textSpan) {
                textSpan.textContent =
                    textSpan.getAttribute("data-closed-text") || "開く";
            }
        }
    }

    toggle.addEventListener("click", function () {
        isOpen = !isOpen;
        updateState();
    });
}
