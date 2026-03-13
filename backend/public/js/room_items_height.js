document.addEventListener("DOMContentLoaded", function () {
    var scrollContainer = document.getElementById("room-items-scroll");
    var settlementLink = document.getElementById("settlement-view-link");
    if (!scrollContainer || !settlementLink) {
        return;
    }

    function updateHeight() {
        var viewportWidth =
            window.innerWidth || document.documentElement.clientWidth;

        // スマホ幅（Tailwind の md 未満）はコンポーネント内スクロールを無効化
        if (viewportWidth < 768) {
            scrollContainer.style.maxHeight = "";
            return;
        }

        // 清算リンクの位置（画面上からの距離）
        var linkRect = settlementLink.getBoundingClientRect();

        // items コンテナの上端位置
        var containerRect = scrollContainer.getBoundingClientRect();

        // コンテナ上端から清算リンクの「下端」までの高さをぴったり合わせる
        var availableHeight = linkRect.bottom - containerRect.top;

        // 最低 150px は確保
        if (availableHeight < 150) {
            availableHeight = 150;
        }

        scrollContainer.style.maxHeight = availableHeight + "px";
    }

    // 初期化とリサイズ時に更新
    updateHeight();
    window.addEventListener("resize", updateHeight);

    // 画面内のレイアウトが変わった場合に備えて、少し遅延して再計算
    setTimeout(updateHeight, 300);
    setTimeout(updateHeight, 1000);
});

