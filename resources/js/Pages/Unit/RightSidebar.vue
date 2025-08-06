<!-- RightSidebar.vue -->
<script setup>
import { computed } from "vue";

const props = defineProps({
    unitUsers: {
        type: Array,
        default: () => [],
    },
    unitName: {
        type: String,
        default: "",
    },
    users: {
        type: Array,
        default: () => [],
    },
    activeUnitId: {
        type: Number,
        default: null,
    },
});

// 現在の部署のユーザーを動的に計算
const currentUnitUsers = computed(() => {
    if (!props.activeUnitId) return props.unitUsers;

    return props.users.filter((user) => user.unit_id === props.activeUnitId);
});
</script>

<template>
    <div
        class="right-sidebar desktop-only bg-gray-100 dark:bg-gray-800 w-56 h-screen p-4 shadow-lg"
    >
        <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">
            {{ unitName + "職員" || "部署メンバー" }}
        </h2>
        <ul
            v-if="currentUnitUsers.length > 0"
            :key="currentUnitUsers.map((u) => u.id).join(',')"
        >
            <li
                v-for="user in currentUnitUsers"
                :key="user.id + user.icon"
                class="mb-2 p-2 rounded hover:bg-gray-200 dark:hover:bg-gray-700 cursor-pointer flex items-center space-x-4 font-bold text-gray-900 dark:text-gray-100"
                @click="$emit('user-selected', user)"
            >
                <img
                    v-if="user.icon"
                    :src="
                        user.icon.startsWith('/storage/')
                            ? user.icon
                            : `/storage/${user.icon}`
                    "
                    alt="User Icon"
                    class="w-12 h-12 rounded-full"
                />
                <img
                    v-else
                    src="/images/default_user_icon.png"
                    alt="Default Icon"
                    class="w-12 h-12 rounded-full"
                />
                <span>{{ user.name }}</span>
            </li>
        </ul>
        <p v-else class="text-gray-500 dark:text-gray-400">メンバーが見つかりません。</p>
    </div>
</template>

<style scoped>
.right-sidebar {
    padding: 16px;
    background-color: #f8f9fa;
    position: fixed;
    right: 0;
    top: 0;
    background-color: #f7fafc;
}

.dark .right-sidebar {
    background-color: #1f2937;
}

/* デスクトップ画面にのみ表示 */
.desktop-only {
    display: block;
}

@media (max-width: 1366px) {
    /* モバイル画面では非表示 */
    .desktop-only {
        display: none;
    }
}
</style>
