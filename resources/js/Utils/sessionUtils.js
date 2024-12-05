const SESSION_KEYS = {
    SELECTED_UNIT_USERS: "selectedUnitUsers",
    SELECTED_UNIT_NAME: "selectedUnitName",
};

export const restoreSelectedUnit = (selectedUnitUsers, selectedUnitName) => {
    const storedUsers = sessionStorage.getItem(
        SESSION_KEYS.SELECTED_UNIT_USERS
    );

    if (storedUsers && storedUsers !== "undefined") {
        try {
            selectedUnitUsers.value = JSON.parse(storedUsers);
        } catch (error) {
            console.error("Error parsing selectedUnitUsers:", error);
            selectedUnitUsers.value = [];
        }
    } else {
        selectedUnitUsers.value = [];
    }

    const storedUnitName = sessionStorage.getItem(
        SESSION_KEYS.SELECTED_UNIT_NAME
    );
    selectedUnitName.value = storedUnitName || "";
};
