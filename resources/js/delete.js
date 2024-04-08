function Check(event) {
    const checked = confirm("本当に削除しますか？");
    if (checked == true) {
        return true;
    } else {
        event.preventDefault();
        return false;
    }
}
window.Check = Check;
