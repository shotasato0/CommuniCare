<template>
    <div>
        <h1>{{ unit.name }}の掲示板</h1>
        <div v-for="post in posts" :key="post.id">
            <h3>{{ post.title }}</h3>
            <p>{{ post.message }}</p>
            <!-- コメントと削除ボタンなど -->
        </div>
    </div>
</template>

<script>
import axios from "axios";

export default {
    props: ["unitId"],
    data() {
        return {
            posts: [],
            unit: {},
        };
    },
    mounted() {
        this.fetchPosts();
    },
    methods: {
        fetchPosts() {
            axios
                .get(`/api/units/${this.unitId}/posts`)
                .then((response) => {
                    this.posts = response.data;
                })
                .catch((error) => console.error("Error:", error));
        },
    },
};
</script>
