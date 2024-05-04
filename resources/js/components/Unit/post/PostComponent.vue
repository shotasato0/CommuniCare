<template>
    <div>
        <h3>{{ post.title }}</h3>
        <p>{{ post.message }}</p>
    </div>
</template>

<script>
export default {
    props: ["post"],
};
</script>
