<script>
import axios from "axios";

export default {
    data() {
        return {
            comments: [],
        };
    },
    mounted() {
        this.fetchLatestComments();
    },
    methods: {
        fetchLatestComments() {
            axios
                .get("/latest-comments")
                .then((response) => {
                    this.comments = response.data;
                })
                .catch((error) => {
                    console.error("最新コメントの取得に失敗しました:", error);
                });
        },
        scrollToPost(postId) {
            const postElement = document.getElementById(`post-${postId}`);
            if (postElement) {
                postElement.scrollIntoView({ behavior: "smooth" });
            }
        },
    },
};
</script>

<template>
    <div>
        <h2>最新のコメント</h2>
        <ul v-if="comments.length">
            <li v-for="comment in comments" :key="comment.id">
                <strong>{{ comment.user.name }}:</strong>
                <p>{{ comment.content }}</p>
                <small>投稿: {{ comment.post.title }}</small>
                <!-- 「元の投稿へ移動」リンク -->
                <button @click="scrollToPost(comment.post.id)">
                    元の投稿へ移動
                </button>
            </li>
        </ul>
        <p v-else>最新コメントはありません。</p>
    </div>
</template>
