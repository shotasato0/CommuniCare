<template>
    <div>
        <div class="font-semibold text-xl text-gray-800 leading-tight">
            <span v-if="user && user.role === 'admin'">- 管理者</span>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        You're logged in!
                        <p v-if="user && user.role === 'admin'">
                            あなたは管理者です。
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="flex items-center pr-6 py-6 text-gray-900">
                        <a
                            href="/register"
                            class="ml-4 inline-flex text-white bg-indigo-500 border-0 py-2 px-6 focus:outline-none hover:bg-indigo-600 rounded text-lg"
                        >
                            新規登録
                        </a>
                    </div>
                </div>
                <div
                    class="bg-white overflow-hidden shadow-sm sm:rounded-lg mt-5"
                >
                    <div class="flex items-center pr-6 py-6 text-gray-900">
                        <a href="/users"
                            class="ml-4 inline-flex text-white bg-indigo-500 border-0 py-2 px-6 focus:outline-none hover:bg-indigo-600 rounded text-lg"
                        >
                            社員一覧
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    data() {
        return {
            user: null,
            successMessage: null,
        };
    },
    created() {
        this.fetchUser();
        this.successMessage = this.$route.query.success;
    },
    methods: {
        fetchUser() {
            // APIからユーザー情報を取得
            axios.get("/api/user").then((response) => {
                this.user = response.data;
            });
        },
    },
};
</script>

<style scoped>
/* ここにスタイルを定義 */
</style>
