<script>
import { router, usePage } from "@inertiajs/vue3";
import SlideUpDown from "vue-slide-up-down";
import Show from "../Users/Show.vue";
import { Container, Draggable } from "vue3-smooth-dnd";

export default {
    components: {
        SlideUpDown,
        Show,
        Container, // コンテナコンポーネント
        Draggable, // ドラッグ可能なコンポーネント
    },
    props: {
        units: {
            type: Array,
            required: true,
        },
        users: {
            type: Array,
            required: false,
            default: () => [],
        },
        activeUnitId: {
            type: Number,
            required: false,
            default: null,
        },
    },
    computed: {
        selectedUnitId() {
            return this.activeUnitId; // 選択された部署IDを取得
        },
    },
    data() {
        return {
            isFetchingData: false, // データ取得中かどうかを判定するフラグ
        };
    },
    setup() {
        // usePage で props を取得
        const { auth } = usePage().props;

        // auth を返して template で使えるようにする
        return {
            auth,
        };
    },
    methods: {
        // 並び替えのイベントデータを取得
        handleDrop(event) {
            // 管理者だけ並び替えを可能にする
            if (this.auth.isAdmin) {
                this.updateOrder(event);
            }
        },
        // 部署をクリックしたときの処理
        async handleUnitClick(unit) {
            if (this.isFetchingData) {
                return;
            }

            this.isFetchingData = true;

            // toggleUnitを呼び出して掲示板の切り替えを行う
            this.toggleUnit(unit.id);

            // 親コンポーネントに選択された部署IDを通知
            this.$emit("forum-selected", unit.id);

            await this.fetchUnitData(unit.id);
            this.isFetchingData = false;
        },

        // toggleUnitメソッド
        toggleUnit(unitId) {
            // 親コンポーネントの状態を変更するためにイベントを発火
            this.$emit("forum-selected", unitId);
        },

        // 部署データを取得
        fetchUnitData(unitId) {
            router.visit(route("forum.index"), {
                method: "get",
                only: ["units"],
                preserveState: true,
                onSuccess: (page) => {},
                onError: (errors) => {
                    console.error("Error fetching unit data:", errors);
                },
            });
        },

        // 並び順を保存
        async updateOrder(event) {
            // 並び替えのイベントデータを取得
            const { removedIndex, addedIndex } = event;

            // エラーチェック
            if (removedIndex === null || addedIndex === null) {
                // 並び替えのイベントデータがnullの場合エラーメッセージを出力
                console.error("Invalid drop event data:", event);
                return;
            }

            // フロントエンドで並び替え
            const movedItem = this.units.splice(removedIndex, 1)[0]; // 並び替えのイベントデータから削除されたアイテムを取得
            this.units.splice(addedIndex, 0, movedItem); // 並び替えのイベントデータから追加されたアイテムを取得

            // 並び替えのイベントデータを取得
            const sortedUnits = this.units.map((unit, index) => ({
                id: unit.id, // 並び替えのイベントデータからアイテムのidを取得
                sort_order: index, // 並び替えのイベントデータからアイテムの並び順を取得
            }));

            try {
                await router.post(route("units.sort"), { units: sortedUnits }); // 並び替えのイベントデータを取得
            } catch (error) {
                console.error("並び順の保存に失敗しました", error);
            }
        },

        // 最新の部署データを取得
        async fetchLatestUnits() {
            try {
                // 最新の部署データを取得するメソッド。awaitは非同期処理を待つためのキーワード。
                return await router.get(route("units.index"), {
                    preserveState: true,
                    onSuccess: (page) => {
                        this.units = page.props.units ?? [];
                    },
                });
            } catch (error) {
                console.error("最新の部署データの取得に失敗しました", error);
            }
        },

        // ユーザー（職員）のプロフィールを開く
        openUserProfile(user) {
            this.$emit("user-profile-clicked", user); // ユーザー（職員）のプロフィールを開くメソッド。$emitはVueのコンポーネント間でメッセージを送信するメソッド。
        },
    },
};
</script>

<template>
    <div class="sidebar bg-gray-100 dark:bg-gray-800 w-56 h-screen p-4 shadow-lg">
        <h2 class="text-lg font-bold mb-4 text-gray-900 dark:text-gray-100">{{ $t("Unit List") }}</h2>
        <Container
            :items="units"
            @drop="handleDrop"
            :behaviour="auth.isAdmin ? 'move' : 'copy'"
            class="list-none"
        >
            <Draggable v-for="unit in units" :key="unit.id">
                <li
                    class="mb-2 p-2 rounded cursor-pointer"
                    :class="{
                        'text-gray-500 dark:text-gray-400 hover:text-black dark:hover:text-white hover:bg-gray-200 dark:hover:bg-gray-700':
                            activeUnitId !== unit.id,
                        'bg-gray-200 dark:bg-gray-700': activeUnitId === unit.id,
                    }"
                    @click="handleUnitClick(unit)"
                >
                    <span class="font-bold text-gray-900 dark:text-gray-100">{{ unit.name }}</span>
                </li>
            </Draggable>
        </Container>
    </div>
</template>

<style scoped>
.sidebar {
    position: fixed;
    left: 0;
    top: 0;
    width: 220px; /* デフォルトの幅を少し狭める */
    height: 100vh; /* 全画面高さ */
    background-color: #f7fafc;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    transition: width 0.3s ease-in-out; /* アニメーションを追加 */
}

.dark .sidebar {
    background-color: #1f2937;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
}

/* モバイルサイズでの設定 */
@media (max-width: 767px) {
    .sidebar {
        width: 70%; /* モバイルでは画面の70%を占める */
        position: absolute; /* トグルで表示されるため絶対配置 */
        transform: translateX(-100%); /* 初期状態で非表示 */
        transition: transform 0.3s ease-in-out;
    }
    .sidebar.visible {
        transform: translateX(0); /* 表示状態に切り替え */
    }
}
</style>
