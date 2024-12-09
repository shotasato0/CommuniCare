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
        activeUnitId: {
            type: Number,
            required: false,
            default: null,
        },
    },
    computed: {
        selectedUnitId() {
            return this.activeUnitId;
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

            this.isFetchingData = true;

            // toggleUnitを呼び出して掲示板の切り替えを行う
            this.toggleUnit(unit.id);

            // 親コンポーネントに選択された部署IDを通知
            this.$emit("forum-selected", unit.id);

            console.log("call fetchUnitData");
            await this.fetchUnitData(unit.id);
            this.isFetchingData = false;
        },

        // toggleUnitメソッドを復活
        toggleUnit(unitId) {
            console.log("Toggling unit:", unitId);
            // 親コンポーネントの状態を変更するためにイベントを発火
            this.$emit("forum-selected", unitId);
        },

        fetchUnitData(unitId) {
            console.log("Fetching data for unit ID:", unitId);

            router.visit(route("forum.index"), {
                method: "get",
                only: ["units"],
                preserveState: true,
                onSuccess: (page) => {
                    console.log("Received data:", page.props.units);
                },
                onError: (errors) => {
                    console.error("Error fetching unit data:", errors);
                },
            });
        },
        openUserProfile(user) {
            this.$emit("user-profile-clicked", user);
        },
    },
};
</script>

<template>
    <div class="sidebar bg-gray-100 w-56 h-screen p-4 shadow-lg">
        <h2 class="text-lg font-bold mb-4">部署一覧</h2>
        <ul>
            <li
                v-for="unit in units"
                :key="unit.id"
                class="mb-2 p-2 rounded cursor-pointer"
                :class="{
                    'text-gray-500 hover:text-black hover:bg-gray-200':
                        activeUnitId !== unit.id,
                    'bg-gray-200': activeUnitId === unit.id,
                }"
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
