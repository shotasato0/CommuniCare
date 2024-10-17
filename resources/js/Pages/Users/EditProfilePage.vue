<script>
import Edit from "./Edit.vue";
import IconEdit from "./IconEdit.vue";
import { usePage } from "@inertiajs/vue3";
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
        const page = usePage();
        const isIconEditVisible = ref(false);

        const openIconEdit = () => {
            isIconEditVisible.value = true;
        };

        const closeIconEdit = () => {
            isIconEditVisible.value = false;
        };

        const units = page.props.units;

        return {
            isIconEditVisible,
            openIconEdit,
            closeIconEdit,
            units,
        };
    },
};
</script>

<template>
    <div class="relative">
        <!-- Edit.vue を表示 -->
        <Edit :user="user" :openIconEdit="openIconEdit" :units="units" />

        <!-- アイコン編集オーバーレイ -->
        <div
            v-if="isIconEditVisible"
            class="fixed inset-0 bg-black/50 flex justify-center items-center z-50"
        >
            <IconEdit :user="user" @close="closeIconEdit" />
        </div>
    </div>
</template>
