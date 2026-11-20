export default function rankingBar() {
    const bar = document.getElementById("ranking-bar");
    const toggle = document.getElementById("ranking-bar-toggle");
    if (!bar || !toggle) {
        return;
    }

    const textSpan = toggle.querySelector("span[data-open-text]");
    let isOpen = false;

    function updateState() {
        bar.classList.toggle("is-collapsed", !isOpen);
        if (!textSpan) {
            return;
        }
        const openLabel = textSpan.getAttribute("data-open-text") || "閉じる";
        const closedLabel = textSpan.getAttribute("data-closed-text") || "開く";
        textSpan.textContent = isOpen ? openLabel : closedLabel;
    }

    toggle.addEventListener("click", function () {
        isOpen = !isOpen;
        updateState();
    });
}
