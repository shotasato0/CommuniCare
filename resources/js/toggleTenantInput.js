document.addEventListener("DOMContentLoaded", function () {
    const adminCheckbox = document.getElementById("is_admin");
    if (adminCheckbox) {
        adminCheckbox.addEventListener("change", function () {
            const container = document.getElementById("nursing_home_container");
            container.style.display = this.checked ? "block" : "none";
            document.getElementById("tenant_name").required = this.checked; // 施設名を必須にするかどうか
        });
    }
});
