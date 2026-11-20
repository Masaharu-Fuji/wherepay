export default function settlementHandoff() {
    const root = document.querySelector("[data-settlement-handoff]");
    if (!(root instanceof HTMLElement)) {
        return;
    }

    const select = root.querySelector("[data-handoff-person]");
    const rows = [...root.querySelectorAll("[data-handoff-row]")];
    const empty = root.querySelector("[data-handoff-empty]");
    const copyButton = root.querySelector("[data-handoff-copy]");

    if (select instanceof HTMLSelectElement) {
        select.addEventListener("change", () => {
            const memberId = select.value;
            let visibleCount = 0;

            rows.forEach((row) => {
                if (!(row instanceof HTMLElement)) {
                    return;
                }

                const visible =
                    memberId === "" ||
                    row.dataset.from === memberId ||
                    row.dataset.to === memberId;
                row.classList.toggle("hidden", !visible);
                if (visible) {
                    visibleCount += 1;
                }
            });

            if (empty instanceof HTMLElement) {
                empty.classList.toggle("hidden", visibleCount !== 0);
            }
        });
    }

    if (copyButton instanceof HTMLButtonElement) {
        const defaultLabel = copyButton.textContent;
        copyButton.addEventListener("click", () => {
            const lines = rows
                .filter((row) => row instanceof HTMLElement)
                .map((row) => {
                    const amount = Number(row.dataset.amount || 0);
                    const formatted = amount.toLocaleString("ja-JP");
                    return `${row.dataset.fromName}が${row.dataset.toName}へ払う ¥${formatted}`;
                });
            const text = [`${root.dataset.roomName}の清算`, ...lines].join("\n");

            copyButton.textContent = "コピーしました";
            window.setTimeout(() => {
                copyButton.textContent = defaultLabel;
            }, 4000);
            copyDuringClick(text);
        });
    }
}

function copyDuringClick(text) {
    try {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            const pending = navigator.clipboard.writeText(text);
            if (pending && typeof pending.catch === "function") {
                pending.catch(() => fallbackCopy(text));
            }
            return;
        }
    } catch {
        fallbackCopy(text);
        return;
    }

    try {
        fallbackCopy(text);
    } catch {
        // このブラウザではコピーできない。ボタンの表示はすでに更新済み。
    }
}

function fallbackCopy(text) {
    const area = document.createElement("textarea");
    area.value = text;
    area.setAttribute("readonly", "");
    area.style.position = "fixed";
    area.style.left = "-9999px";
    document.body.appendChild(area);
    area.select();
    const copied = document.execCommand("copy");
    area.remove();
    return copied;
}
