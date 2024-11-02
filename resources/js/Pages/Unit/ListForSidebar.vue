<script>
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
        toggleUnit(unitId) {
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
                @click="toggleUnit(unit.id)"
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
