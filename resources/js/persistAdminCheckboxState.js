document.addEventListener("DOMContentLoaded", function () {
    const adminCheckbox = document.getElementById("is_admin");
    const container = document.getElementById("nursing_home_container");
    const storedIsAdmin = localStorage.getItem("isAdmin");

    // ローカルストレージから状態を取得し、チェックボックスに適用
    if (storedIsAdmin === "true") {
        adminCheckbox.checked = true;
        container.style.display = "block";
        document.getElementById("nursing_home_name").required = true;
    } else {
        adminCheckbox.checked = false;
        container.style.display = "none";
        document.getElementById("nursing_home_name").required = false;
    }

    // チェックボックスの変更を監視し、ローカルストレージに保存
    adminCheckbox.addEventListener("change", function () {
        localStorage.setItem("isAdmin", adminCheckbox.checked);
        container.style.display = adminCheckbox.checked ? "block" : "none";
        document.getElementById("nursing_home_name").required =
            adminCheckbox.checked;
    });
});
