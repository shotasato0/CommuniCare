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
            selectedUnitId: null,
            isFetchingData: false, // データ取得中かどうかを判定するフラグ
        };
    },
    computed: {
        filteredUsers() {
            if (this.selectedUnitId) {
                return this.users.filter(
                    (user) => user.unit_id === this.selectedUnitId
                );
            }
            return [];
        },
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
            this.selectedUnitId = null; // ドロップダウンをリセット
        },
    },
};
</script>

<template>
    <div class="sidebar bg-gray-100 w-60 h-screen p-4 shadow-lg">
        <h2 class="text-xl font-bold mb-4">部署一覧</h2>
        <ul>
            <li
                v-for="unit in units"
                :key="unit.id"
                class="mb-2 p-2 rounded hover:bg-gray-200 cursor-pointer"
                @click="handleUnitClick(unit)"
            >
                <div class="flex items-center justify-between">
                    <span class="font-bold">{{ unit.name }}</span>
                    <span v-if="selectedUnitId === unit.id">&#9660;</span>
                    <span v-else>&#9654;</span>
                </div>
            </li>
        </ul>
    </div>
</template>

<style scoped>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    background-color: #f7fafc;
}
</style>
