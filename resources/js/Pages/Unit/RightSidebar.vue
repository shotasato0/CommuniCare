<!-- RightSidebar.vue -->
<script setup>
import { defineProps } from "vue";

const props = defineProps({
    unitUsers: {
        type: Array,
        default: () => [],
    },
});
console.log("props.unitUsers", props.unitUsers);
</script>

<template>
    <div class="right-sidebar desktop-only">
        <h2>ユニットメンバー</h2>
        <ul v-if="unitUsers && unitUsers.length > 0">
            <li
                v-for="user in unitUsers"
                :key="user.id"
                class="p-1 cursor-pointer hover:bg-blue-300 flex items-center space-x-2"
            >
                <img
                    v-if="user.icon"
                    :src="
                        user.icon.startsWith('/storage/')
                            ? user.icon
                            : `/storage/${user.icon}`
                    "
                    alt="User Icon"
                    class="w-6 h-6 rounded-full"
                />
                <img
                    v-else
                    src="https://via.placeholder.com/40"
                    alt="Default Icon"
                    class="w-6 h-6 rounded-full"
                />
                <span>{{ user.name }}</span>
            </li>
        </ul>
        <p v-else>メンバーが見つかりません。</p>
    </div>
</template>

<style scoped>
.right-sidebar {
    padding: 16px;
    background-color: #f8f9fa;
}

/* デスクトップ画面にのみ表示 */
.desktop-only {
    display: block;
}

@media (max-width: 1024px) {
    /* モバイル画面では非表示 */
    .desktop-only {
        display: none;
    }
}
</style>
