<template>
    <div>
        <post-form-component @add-post="handleAddPost"></post-form-component>
        <search-form-component
            @search-posts="handleSearchPosts"
        ></search-form-component>
        <post-component
            v-for="post in posts"
            :key="post.id"
            :post="post"
        ></post-component>
    </div>
</template>

<script>
import PostFormComponent from "./post/PostFormComponent.vue";
import SearchFormComponent from "./search/SearchFormComponent.vue";
import PostComponent from "./post/PostComponent.vue";

export default {
    components: {
        PostFormComponent,
        SearchFormComponent,
        PostComponent,
    },
    data() {
        return {
            posts: [],
            searchResults: [],
        };
    },
    methods: {
        handleAddPost(newPost) {
            // API call to add post
        },
        handleSearchPosts(searchTerm) {
            // API call to search posts
        },
    },
};
</script>
