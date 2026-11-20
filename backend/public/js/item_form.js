export default function itemForm() {
    document.querySelectorAll("[data-participant-editor]").forEach(setupEditor);

    const form = document.querySelector("[data-item-form]");
    if (form instanceof HTMLFormElement) {
        rememberPayer(form);
    }
}

function setupEditor(root) {
    const preview = root.querySelector("[data-per-person]");
    const payerSelect = payerSelectFor(root);
    const amountInput = amountInputFor(root);

    function selectedBoxes() {
        return [...root.querySelectorAll('input[name="participant_ids[]"]')].filter(
            (box) => box instanceof HTMLInputElement,
        );
    }

    function amount() {
        if (amountInput instanceof HTMLInputElement) {
            const value = Number(amountInput.value);
            return Number.isFinite(value) ? value : 0;
        }

        const fixed = Number(root.dataset.amount || 0);
        return Number.isFinite(fixed) ? fixed : 0;
    }

    function render() {
        if (!(preview instanceof HTMLElement)) {
            return;
        }

        const selected = selectedBoxes().filter((box) => box.checked);
        if (selected.length === 0 || amount() < 1) {
            preview.textContent = "対象者を選ぶと、1人あたりの金額が出ます";
            return;
        }

        const perPerson = Math.round(amount() / selected.length);
        preview.textContent = `${selected.length}人で割ると 1人 ¥${perPerson.toLocaleString("ja-JP")}`;
    }

    root.querySelector("[data-participant-all]")?.addEventListener("click", () => {
        selectedBoxes().forEach((box) => {
            box.checked = true;
        });
        render();
    });

    root.querySelector("[data-participant-except-payer]")?.addEventListener("click", () => {
        const payerId = currentPayerId(payerSelect, root);
        if (payerId === "") {
            if (preview instanceof HTMLElement) {
                preview.textContent = "先に支払者を選んでください";
            }
            return;
        }

        selectedBoxes().forEach((box) => {
            box.checked = box.value !== payerId;
        });
        render();
    });

    root.querySelector("[data-participant-clear]")?.addEventListener("click", () => {
        selectedBoxes().forEach((box) => {
            box.checked = false;
        });
        render();
    });

    root.addEventListener("change", render);
    amountInput?.addEventListener("input", render);
    render();
}

function payerSelectFor(root) {
    const selectId = root.dataset.payerSelect;
    if (!selectId) {
        return null;
    }

    const select = document.getElementById(selectId);
    return select instanceof HTMLSelectElement ? select : null;
}

function amountInputFor(root) {
    const inputId = root.dataset.amountInput;
    if (!inputId) {
        return null;
    }

    const input = document.getElementById(inputId);
    return input instanceof HTMLInputElement ? input : null;
}

function currentPayerId(payerSelect, root) {
    if (payerSelect instanceof HTMLSelectElement && payerSelect.value !== "") {
        return payerSelect.value;
    }

    return root.dataset.payerId || "";
}

function rememberPayer(form) {
    const roomId = form.dataset.roomId;
    const select = form.querySelector("#payer_id");
    if (!roomId || !(select instanceof HTMLSelectElement)) {
        return;
    }

    const key = `wherepay:payer:${roomId}`;
    if (select.value === "") {
        const saved = sessionStorage.getItem(key);
        const hasOption = [...select.options].some((option) => option.value === saved);
        if (saved && hasOption) {
            select.value = saved;
        }
    }

    form.addEventListener("submit", () => {
        if (select.value !== "") {
            sessionStorage.setItem(key, select.value);
        }
    });
}
