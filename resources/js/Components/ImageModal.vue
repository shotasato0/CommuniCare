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
        class="fixed inset-0 bg-black/50 flex items-center justify-center z-50"
        @click.self="close"
    >
        <slot></slot>
    </div>
</template>
