<script>
import { router, usePage } from "@inertiajs/vue3";

export default {
    props: {
        user: {
            type: Object,
            required: true,
        },
        units: {
            type: Array,
            required: true,
        },
    },
    setup(props) {
        const page = usePage();
        const authUser = page.props.auth.user; // ログインユーザー情報を取得

        // 部署名を取得
        const unitName =
            Array.isArray(props.units) && props.units.length > 0
                ? props.units.find((unit) => unit.id === props.user.unit_id)
                      ?.name || "未所属"
                : "未所属";

        return { page, authUser, unitName };
    },
    methods: {
        profileEdit() {
            // 編集ボタンを押したときの処理
            router.visit(route("profile.edit"));
        },
    },
};
</script>

<template>
    <div
        class="flex items-center justify-center inset-0 bg-black dark:bg-black rounded-lg bg-opacity-50 dark:bg-opacity-70 z-50"
    >
        <div
            class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg max-h-screen overflow-auto w-80"
        >
            <img
                :src="user.iconUrl || '/images/default_user_icon.png'"
                alt="ユーザーのプロフィール写真"
                class="w-24 h-24 rounded-full object-cover mx-auto mb-4"
                @error="$event.target.src='/images/default_user_icon.png'"
            />

            <div class="info mb-4 text-left">
                <label class="font-bold text-gray-900 dark:text-gray-100">名前</label>
                <div class="border border-gray-300 dark:border-gray-600 p-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {{ user.name }}
                </div>
            </div>
            <div class="info mb-4 text-left">
                <label class="font-bold text-gray-900 dark:text-gray-100">電話番号</label>
                <div class="border border-gray-300 dark:border-gray-600 p-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {{ user.tel }}
                </div>
            </div>
            <div class="info mb-4 text-left">
                <label class="font-bold text-gray-900 dark:text-gray-100">メールアドレス</label>
                <div class="border border-gray-300 dark:border-gray-600 p-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {{ user.email }}
                </div>
            </div>
            <div class="info mb-4 text-left">
                <label class="font-bold text-gray-900 dark:text-gray-100">所属部署</label>
                <div class="border border-gray-300 dark:border-gray-600 p-2 rounded bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    {{ unitName }}
                </div>
            </div>

            <!-- ログインユーザーと一致する場合にのみ表示 -->
            <button
                v-if="authUser && authUser.id === user.id"
                @click="profileEdit"
                class="w-full sm:w-auto px-4 py-2 bg-blue-100 dark:bg-blue-800 text-blue-700 dark:text-blue-300 rounded-md transition hover:bg-blue-300 dark:hover:bg-blue-600 hover:text-white text-center mx-auto block"
            >
                プロフィールを編集
            </button>
        </div>
    </div>
</template>
