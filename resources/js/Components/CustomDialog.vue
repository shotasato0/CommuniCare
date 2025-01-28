<template>
    <div
        v-if="isVisible"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50"
        style="z-index: 1000"
    >
        <div class="bg-white p-6 rounded shadow-lg">
            <p>{{ message }}</p>
            <div class="flex justify-end space-x-2 mt-4">
                <button
                    @click="confirm"
                    class="w-24 py-2 text-center bg-blue-500 text-white rounded"
                >
                    OK
                </button>
                <button
                    @click="cancel"
                    class="w-24 py-2 text-center bg-gray-300 rounded"
                >
                    キャンセル
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
const props = defineProps({
    isVisible: Boolean,
    message: String,
});

const emit = defineEmits(["confirm", "cancel"]);

const confirm = () => {
    emit("confirm");
};

const cancel = () => {
    emit("cancel");
};
</script>
