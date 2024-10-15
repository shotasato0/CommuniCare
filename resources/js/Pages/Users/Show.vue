<script>
import { router, usePage } from "@inertiajs/vue3";

export default {
    props: {
        user: {
            type: Object,
            required: true,
        },
    },
    setup() {
        const page = usePage();
        return { page };
    },
    methods: {
        editUser() {
            // 編集ボタンを押したときの処理をここに書く
            router.visit(`/users/${this.user.id}/edit`);
        },
    },
};
</script>

<template>
    <div class="flex justify-center items-center min-h-screen bg-gray-100">
        <div class="w-80 bg-white p-6 rounded-lg shadow-lg text-center">
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
                    {{ user.unit ? user.unit.name : "未所属" }}
                </div>
            </div>

            <button
                @click="editUser"
                class="bg-blue-400 text-white px-4 py-2 rounded mb-6 hover:bg-blue-500"
            >
                プロフィールを編集
            </button>
        </div>
    </div>
</template>
