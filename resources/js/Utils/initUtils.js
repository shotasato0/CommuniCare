import { usePage } from "@inertiajs/vue3";

export const initSelectedForumId = (selectedForumId) => {
    const pageProps = usePage().props;
    if (!selectedForumId) {
        console.warn("selectedForumId が無効です");
        return;
    }
    selectedForumId.value = pageProps?.selectedForumId || null; // デフォルト値を設定
};
