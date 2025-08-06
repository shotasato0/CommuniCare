<script setup>
import axios from "axios";
import { ref } from "vue";

const props = defineProps({
    likeableId: {
        // モデルのID
        type: Number,
        required: true,
    },
    likeableType: {
        // モデル名
        type: String,
        required: true, // 'Post'または'Comment'を指定
    },
    initialLikeCount: {
        // いいねの数
        type: Number,
        default: 0,
    },
    initialIsLiked: {
        // いいねの状態
        type: Boolean,
        default: false,
    },
});

const isLiked = ref(props.initialIsLiked); // いいねの状態
const likeCount = ref(props.initialLikeCount); // いいねの数
const likedUsers = ref([]); // いいねしたユーザーの名前一覧
const hoverTimeout = ref(null); // マウスオーバー時のタイムアウト
const tooltipDelay = 500; // ツールチップ表示の遅延時間

// デバイスサイズを判断するための定数（SSR対応）
let mediaQuery = null;
const isMobile = ref(false); // 初期値はfalse（デスクトップ前提）

// ブラウザ環境でのみ初期化
if (typeof window !== 'undefined' && window.matchMedia) {
    mediaQuery = window.matchMedia("(max-width: 1024px)");
    isMobile.value = mediaQuery.matches;

    // ウィンドウの幅が変更された時にモバイルかどうかを再判定
    mediaQuery.addEventListener("change", (event) => {
        isMobile.value = event.matches;
    });
}

// ツールチップをクリア
const clearTooltip = () => {
    // マウスオーバー時のタイムアウトが存在する場合
    if (hoverTimeout.value) {
        clearTimeout(hoverTimeout.value); // タイムアウトをクリア
        hoverTimeout.value = null; // タイムアウトをnullに設定
    }
    likedUsers.value = []; // いいねしたユーザーの名前一覧を空にする
};

// いいねのトグル
const toggleLike = async () => {
    isLiked.value = !isLiked.value; // いいねの状態をトグル
    likeCount.value += isLiked.value ? 1 : -1; // いいねの数を更新

    try {
        await axios.post("/like/toggle", {
            likeable_id: props.likeableId,
            likeable_type: props.likeableType, // 'Post' または 'Comment'を送信
        });
    } catch (error) {
        isLiked.value = !isLiked.value; // いいねの状態をトグル
        likeCount.value += isLiked.value ? 1 : -1; // いいねの数を更新
        console.error("いいねのトグルに失敗しました:", error);
    }
};

// いいねしたユーザーの名前一覧を取得
const fetchLikedUsers = async () => {
    // デスクトップサイズではマウスオーバーで遅延して表示
    const fetchData = async () => {
        try {
            // いいねしたユーザーの名前一覧を取得
            const response = await axios.get(
                // モデル名を小文字に変換
                `/api/${props.likeableType.toLowerCase()}s/${
                    props.likeableId // モデルのID
                }/liked-users` // いいねしたユーザーの名前一覧を取得するためのエンドポイント
            );
            likedUsers.value = response.data; // 取得したユーザーの名前一覧を格納
            console.log(likedUsers.value); // いいねしたユーザーの名前一覧をコンソールに出力
        } catch (error) {
            console.error("いいねしたユーザーの取得に失敗しました:", error);
        }
    };
    // モバイルサイズでは遅延なしで表示
    if (isMobile.value) {
        await fetchData();
    } else {
        hoverTimeout.value = setTimeout(fetchData, tooltipDelay); // ツールチップ表示の遅延時間
    }
};
</script>

<template>
    <div class="relative" @mouseout="clearTooltip" style="touch-action: none">
        <!-- デスクトップ用のいいねボタン -->
        <button
            @click="toggleLike"
            @mouseover="!isMobile ? fetchLikedUsers() : null"
            :class="{ 'text-red-500 dark:text-red-400': isLiked, 'text-gray-600 dark:text-gray-300': !isLiked }"
            class="transition-colors duration-200 hover:text-red-500 dark:hover:text-red-400"
        >
            <i v-if="isLiked" class="bi bi-heart-fill"></i>
            <i v-else class="bi bi-heart"></i>
            {{ likeCount }}
        </button>

        <!-- モバイル用の「…」ボタン -->
        <button
            v-if="isMobile"
            @click="fetchLikedUsers"
            class="ml-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-colors duration-200"
        >
            ...
        </button>

        <!-- ツールチップ表示 -->
        <div
            v-if="likedUsers.length"
            class="absolute bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md shadow-lg p-2 max-h-40 overflow-y-auto z-50 mt-2 w-48 transition-opacity duration-200"
        >
            <ul class="text-sm text-gray-700 dark:text-gray-300">
                <li class="py-1 px-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md transition-colors duration-200">
                    {{ likedUsers.join(", ") }} がいいねしました！
                </li>
            </ul>
        </div>
    </div>
</template>
