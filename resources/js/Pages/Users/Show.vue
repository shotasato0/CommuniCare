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

        // デバッグ用のログを追加
        console.log("User data:", props.user);
        console.log("Auth user data:", authUser);
        console.log("Units data:", props.units);

        // 部署名を取得
        const unitName =
            props.units.find((unit) => unit.id === props.user.unit_id)?.name ||
            "未所属";

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
        class="flex items-center justify-center inset-0 bg-black rounded-lg bg-opacity-50 z-50"
    >
        <div
            class="bg-white p-6 rounded-lg shadow-lg max-h-screen overflow-auto w-80"
        >
            <img
                :src="
                    user.icon
                        ? `/storage/${user.icon}`
                        : 'https://via.placeholder.com/100'
                "
                alt="ユーザーのプロフィール写真"
                class="w-24 h-24 rounded-full object-cover mx-auto mb-4"
            />

            <div class="info mb-4 text-left">
                <label class="font-bold">名前</label>
                <div class="border border-gray-300 p-2 rounded bg-gray-100">
                    {{ user.name }}
                </div>
            </div>
            <div class="info mb-4 text-left">
                <label class="font-bold">電話番号</label>
                <div class="border border-gray-300 p-2 rounded bg-gray-100">
                    {{ user.tel }}
                </div>
            </div>
            <div class="info mb-4 text-left">
                <label class="font-bold">メールアドレス</label>
                <div class="border border-gray-300 p-2 rounded bg-gray-100">
                    {{ user.email }}
                </div>
            </div>
            <div class="info mb-4 text-left">
                <label class="font-bold">所属部署</label>
                <div class="border border-gray-300 p-2 rounded bg-gray-100">
                    {{ unitName }}
                </div>
            </div>

            <!-- ログインユーザーと一致する場合にのみ表示 -->
            <button
                v-if="authUser && authUser.id === user.id"
                @click="profileEdit"
                class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 mx-auto block"
            >
                プロフィールを編集
            </button>
        </div>
    </div>
</template>
