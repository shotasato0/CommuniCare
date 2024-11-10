<script setup>
import { ref } from "vue";
import axios from "axios";

const props = defineProps({
    postId: {
        type: Number,
        required: true,
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

const isLiked = ref(props.initialIsLiked); // 初期のいいね状態
const likeCount = ref(props.initialLikeCount); // 初期のいいね数

const toggleLike = async () => {
    // 状態を即時に切り替え
    isLiked.value = !isLiked.value;
    likeCount.value += isLiked.value ? 1 : -1;

    try {
        // リクエストを送信
        await axios.post("/like/toggle", {
            likeable_id: props.postId,
            likeable_type: "Post", // 他のモデルなら 'Comment' など
        });
    } catch (error) {
        // エラー時に状態を元に戻す
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
