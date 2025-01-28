<template>
    <div
        v-if="isVisible"
        class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50"
        style="z-index: 1000"
    >
        <div class="bg-white p-6 rounded-md shadow-lg">
            <p>{{ message }}</p>
            <div class="flex justify-end space-x-2 mt-4">
                <button
                    @click="confirm"
                    class="w-32 bg-blue-100 text-blue-700 font-medium py-2 px-4 rounded-md transition hover:bg-blue-300 hover:text-white focus:outline-none focus:shadow-outline"
                >
                    OK
                </button>
                <button
                    @click="cancel"
                    class="w-32 bg-gray-300 text-gray-700 font-medium py-2 px-4 rounded-md transition hover:bg-gray-500 hover:text-white focus:outline-none focus:shadow-outline"
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
