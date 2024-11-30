<script>
import { router } from "@inertiajs/vue3";
import SlideUpDown from "vue-slide-up-down";
import Show from "../Users/Show.vue";

export default {
    components: {
        SlideUpDown,
        Show,
    },
    props: {
        units: {
            type: Array,
            required: true,
        },
        users: {
            type: Array,
            required: false,
            default: () => [],
        },
    },
    data() {
        return {
            isFetchingData: false, // データ取得中かどうかを判定するフラグ
        };
    },

    methods: {
        async handleUnitClick(unit) {
            if (this.isFetchingData) {
                console.log("Already fetching data, skipping...");
                return;
            }

            console.log("Unit clicked:", unit); // デバッグ用
            this.isFetchingData = true; // フラグをセット
            this.toggleUnit(unit.id);
            console.log("call fetchUnitData");

            // 選択されたユニットIDを親コンポーネントに伝えるイベントを発火
            this.$emit("forum-selected", unit.id);

            await this.fetchUnitData(unit.id);
            this.isFetchingData = false; // フラグをリセット
        },
        fetchUnitData(unitId) {
            console.log("Fetching data for unit ID:", unitId); // デバッグ用

            router.visit(route("forum.index"), {
                method: "get",
                only: ["units"], // 必要なプロパティを指定
                preserveState: true, // ページ遷移なし
                onSuccess: (page) => {
                    console.log("Received data:", page.props.units);
                    this.$emit("forum-selected", unitId);
                },
                onError: (errors) => {
                    console.error("Error fetching unit data:", errors);
                },
            });
        },
        toggleUnit(unitId) {
            console.log("Toggling unit:", unitId); // デバッグ用
            this.selectedUnitId =
                this.selectedUnitId === unitId ? null : unitId;
        },
        openUserProfile(user) {
            this.$emit("user-profile-clicked", user); // 親にイベントを伝播
        },
        resetDropdown() {
            this.selectedUnitId = null; // 選択されたユニットをリセット
            this.isFetchingData = false; // データ取得中フラグをリセット
        },
    },
};
</script>

<template>
    <div class="sidebar bg-gray-100 w-56 h-screen p-4 shadow-lg">
        <h2 class="text-xl font-bold mb-4">部署一覧</h2>
        <ul>
            <li
                v-for="unit in units"
                :key="unit.id"
                class="mb-2 p-2 rounded hover:bg-gray-200 cursor-pointer"
                @click="handleUnitClick(unit)"
            >
                <span class="font-bold">{{ unit.name }}</span>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 220px; /* デフォルトの幅を少し狭める */
    height: 100vh; /* 全画面高さ */
    background-color: #f7fafc;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    transition: width 0.3s ease-in-out; /* アニメーションを追加 */
}

/* モバイルサイズでの設定 */
@media (max-width: 767px) {
    .sidebar {
        width: 70%; /* モバイルでは画面の70%を占める */
        position: absolute; /* トグルで表示されるため絶対配置 */
        transform: translateX(-100%); /* 初期状態で非表示 */
        transition: transform 0.3s ease-in-out;
    }
    .sidebar.visible {
        transform: translateX(0); /* 表示状態に切り替え */
    }
}
</style>
