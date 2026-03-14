function getLocationForNextItem() {
    if (!navigator.geolocation) {
        alert("このブラウザは位置情報をサポートしていません");
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function (position) {
            var latitudeInput = document.getElementById("latitude");
            var longitudeInput = document.getElementById("longitude");
            var addressInput = document.getElementById("location_address");

            if (!latitudeInput || !longitudeInput || !addressInput) {
                alert("位置情報用の入力欄が見つかりませんでした");
                return;
            }

            latitudeInput.value = position.coords.latitude;
            longitudeInput.value = position.coords.longitude;
            addressInput.value = "現在地";

            alert(
                "位置情報を取得しました！次に追加する品目に位置情報が付与されます。",
            );
        },
        function () {
            alert("位置情報の取得に失敗しました");
        },
    );
}
