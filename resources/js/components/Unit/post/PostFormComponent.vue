<template>
    <div>
        <input v-model="title" placeholder="件名" required />
        <textarea v-model="message" placeholder="本文" required></textarea>
        <button @click="submitPost">投稿</button>
    </div>
</template>

<script>
export default {
    data() {
        return {
            title: "",
            message: "",
        };
    },
    methods: {
        submitPost() {
            this.$emit("add-post", {
                title: this.title,
                message: this.message,
            });
        },
    },
};
</script>
