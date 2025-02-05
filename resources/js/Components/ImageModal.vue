<script setup>
const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(["close"]);

const close = () => {
    emit("close");
};
</script>

<template>
    <div
        v-if="isOpen"
        class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50"
        @click.self="close"
    >
        <slot></slot>
    </div>
</template>

<style scoped>
/* 必要に応じてスタイルを追加 */
</style>
