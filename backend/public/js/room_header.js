document.addEventListener("DOMContentLoaded", function () {
    var copyButton = document.getElementById("copy-room-url");
    var urlInput = document.getElementById("room-url");

    if (!copyButton || !urlInput) {
        return;
    }

    function setCopiedText() {
        copyButton.textContent = "コピーしました";
        setTimeout(function () {
            copyButton.textContent = "URLをコピー";
        }, 2000);
    }

    copyButton.addEventListener("click", function () {
        var url = urlInput.value;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard
                .writeText(url)
                .then(function () {
                    setCopiedText();
                })
                .catch(function () {
                    urlInput.select();
                    document.execCommand("copy");
                    setCopiedText();
                });
        } else {
            urlInput.select();
            document.execCommand("copy");
            setCopiedText();
        }
    });
});
