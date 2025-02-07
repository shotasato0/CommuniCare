export const handleImageChange = (event, imageRef, imagePreviewRef, errorMessageRef) => {
    const file = event.target.files[0];
    if (!file) return;

    const validImageTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];

    if (!validImageTypes.includes(file.type)) {
        errorMessageRef.value = "対応していないファイル形式です。png, jpg, gif, webpのいずれかを選択してください。";
        setTimeout(() => {
            errorMessageRef.value = null;
        }, 8000);
        return;
    }

    imageRef.value = file;
    imagePreviewRef.value = URL.createObjectURL(file);
};
