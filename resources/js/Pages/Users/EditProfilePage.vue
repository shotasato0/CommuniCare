<template>
    <div class="relative">
        <!-- Edit.vue を表示 -->
        <Edit :user="user" />

        <!-- アイコン編集オーバーレイ -->
        <div
            v-if="isIconEditVisible"
            class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50"
        >
            <IconEdit :user="user" @close="closeIconEdit" />
        </div>
    </div>
</template>

<script>
import Edit from "./Edit.vue";
import IconEdit from "./IconEdit.vue";
import { ref } from "vue";

export default {
    components: {
        Edit,
        IconEdit,
    },
    props: {
        user: {
            type: Object,
            required: true,
        },
    },
    setup() {
        const isIconEditVisible = ref(false);

        const openIconEdit = () => {
            isIconEditVisible.value = true;
        };

        const closeIconEdit = () => {
            isIconEditVisible.value = false;
        };

        return {
            isIconEditVisible,
            openIconEdit,
            closeIconEdit,
        };
    },
};
</script>

<style scoped>
.bg-opacity-50 {
    backdrop-filter: blur(5px);
}
</style>
