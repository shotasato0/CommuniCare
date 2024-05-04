<template>
    <div>
        <input v-model="searchTerm" placeholder="検索キーワード" />
        <button @click="searchPosts">検索</button>
    </div>
</template>

<script>
export default {
    data() {
        return {
            searchTerm: "",
        };
    },
    methods: {
        searchPosts() {
            this.$emit("search-posts", this.searchTerm);
        },
    },
};
</script>
