<template>
    <div></div>
</template>

<script>
export default {
    name: "PopstateHandler",
    mounted() {
        window.addEventListener("popstate", this.handleBack);
    },
    beforeDestroy() {
        window.removeEventListener("popstate", this.handleBack);
    },
    methods: {
        handleBack(event) {
            if (event.state === null) {
                // 常にブラウザの1つ前の履歴に戻る
                window.history.back();
            }
        },
    },
};
</script>
