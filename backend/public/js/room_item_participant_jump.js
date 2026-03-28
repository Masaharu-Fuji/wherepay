/**
 * 品目編集: 「対象者」チェックリスト内を名前で検索ジャンプする。
 * - NFKC 正規化 + 小文字化後の部分一致、または文字の順番に沿ったあいまい一致
 * - 内側スクロールと祖先スクロールの両方で表示領域へスクロール
 */

function normalizeForMatch(s) {
    return String(s ?? "")
        .normalize("NFKC")
        .toLowerCase()
        .trim();
}

/** 部分一致、または query の各文字が name に現れる順序で含まれる場合に一致 */
function fuzzyMatchName(name, query) {
    const n = normalizeForMatch(name);
    const q = normalizeForMatch(query);
    if (!q) {
        return false;
    }
    if (n.includes(q)) {
        return true;
    }
    let qi = 0;
    for (let i = 0; i < n.length && qi < q.length; i += 1) {
        if (n[i] === q[qi]) {
            qi += 1;
        }
    }
    return qi === q.length;
}

function initRoot(root) {
    const input = root.querySelector("[data-participant-jump-input]");
    const scrollEl = root.querySelector("[data-participant-jump-scroll]");
    if (!input || !scrollEl) {
        return;
    }

    const getRows = () =>
        [...root.querySelectorAll("[data-participant-jump-row]")];

    /** Enter 連打でマッチ間を循環するためのインデックス */
    let matchOrdinal = -1;

    function collectMatches() {
        const q = input.value;
        const hits = [];
        for (const row of getRows()) {
            const name = row.dataset.memberName ?? "";
            if (fuzzyMatchName(name, q)) {
                hits.push({ row });
            }
        }
        return hits;
    }

    function scrollRowIntoView(row) {
        row.scrollIntoView({
            behavior: "smooth",
            block: "center",
            inline: "nearest",
        });
    }

    function jumpNext() {
        const hits = collectMatches();
        if (hits.length === 0) {
            matchOrdinal = -1;
            return;
        }
        matchOrdinal = (matchOrdinal + 1) % hits.length;
        const { row } = hits[matchOrdinal];
        scrollRowIntoView(row);
        const cb = row.querySelector('input[type="checkbox"]');
        if (cb) {
            cb.focus({ preventScroll: true });
        }
        scrollRowIntoView(row);
    }

    input.addEventListener("input", () => {
        matchOrdinal = -1;
    });

    input.addEventListener("keydown", (e) => {
        if (e.key === "Enter") {
            e.preventDefault();
            jumpNext();
        }
    });
}

export default function roomItemParticipantJump() {
    document
        .querySelectorAll("[data-participant-jump-root]")
        .forEach((root) => initRoot(root));
}
