<script>
import { Link } from "@inertiajs/vue3";

export default {
    name: "Footer",
    components: {
        Link,
    },
};
</script>

<template>
    <!-- フッター -->
    <footer class="bg-white border-t">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex justify-center items-center space-x-6">
                <!-- コピーライト -->
                <div class="text-sm text-gray-500">
                    © 2025 CommuniCare. All rights reserved.
                </div>
                <!-- プライバシーポリシーリンク -->
                <div class="text-sm text-gray-500">
                    <Link
                        href="/legal/privacy-policy"
                        class="hover:underline"
                    >
                        プライバシーポリシー
                    </Link>
                </div>
                <!-- ソーシャルリンク -->
                <div class="flex space-x-4">
                    <a
                        href="https://twitter.com/shoprogramming"
                        rel="noopener noreferrer"
                        class="text-gray-600 hover:text-gray-900"
                        target="_blank"
                    >
                        <svg
                            fill="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            class="w-5 h-5"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M14.258 10.152L23.176 0h-2.113l-7.747 8.813L7.133 0H0l9.352 13.328L0 23.973h2.113l8.176-9.309 6.531 9.309h7.133zm-2.895 3.293l-.949-1.328L2.875 1.56h3.246l6.086 8.523.945 1.328 7.91 11.078h-3.246zm0 0"
                            />
                        </svg>
                    </a>
                    <a
                        href="https://github.com/shotasato0"
                        rel="noopener noreferrer"
                        class="text-gray-600 hover:text-gray-900"
                        target="_blank"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="currentColor"
                            class="bi bi-github w-5 h-5"
                            viewBox="0 0 16 16"
                        >
                            <path
                                d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.012 8.012 0 0 0 16 8c0-4.42-3.58-8-8-8z"
                            />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </footer>
</template>

<style scoped>
footer {
    bottom: 0;
    width: 100%;
}
</style>
