export const restoreSelectedUnitUsers = (
    selectedUnitUsers,
    selectedUnitName
) => {
    const storedUsers = sessionStorage.getItem("selectedUnitUsers");

    // `storedUsers`がnullや"undefined"ではなく、有効なJSONかをチェック
    if (storedUsers && storedUsers !== "undefined") {
        try {
            selectedUnitUsers.value = JSON.parse(storedUsers);
        } catch (error) {
            console.error("Error parsing selectedUnitUsers:", error);
            selectedUnitUsers.value = []; // パースエラー時には空配列を代入
        }
    } else {
        selectedUnitUsers.value = []; // nullまたは"undefined"の場合は空配列を代入
    }

    // 保存されたユニット名を復元
    const storedUnitName = sessionStorage.getItem("selectedUnitName");
    selectedUnitName.value = storedUnitName || ""; // nullの場合は空文字列を代入
};
