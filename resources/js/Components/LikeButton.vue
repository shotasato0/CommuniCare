<script setup>
import axios from "axios";
import { ref } from "vue";

const props = defineProps({
    likeableId: {
        type: Number,
        required: true,
    },
    likeableType: {
        type: String,
        required: true, // 'Post'または'Comment'を指定
    },
    initialLikeCount: {
        type: Number,
        default: 0,
    },
    initialIsLiked: {
        type: Boolean,
        default: false,
    },
});

const isLiked = ref(props.initialIsLiked);
const likeCount = ref(props.initialLikeCount);

const toggleLike = async () => {
    isLiked.value = !isLiked.value;
    likeCount.value += isLiked.value ? 1 : -1;

    try {
        await axios.post("/like/toggle", {
            likeable_id: props.likeableId,
            likeable_type: props.likeableType, // 'Post' または 'Comment'を送信
        });
    } catch (error) {
        isLiked.value = !isLiked.value;
        likeCount.value += isLiked.value ? 1 : -1;
        console.error("いいねのトグルに失敗しました:", error);
    }
};
</script>

<template>
    <button @click="toggleLike" :class="{ 'text-red-500': isLiked }">
        <i v-if="isLiked" class="bi bi-heart-fill"></i>
        <i v-else class="bi bi-heart"></i>
        {{ likeCount }}
    </button>
</template>
