<script setup>
import InputError from "@/Components/InputError.vue";
import InputLabel from "@/Components/InputLabel.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import TextInput from "@/Components/TextInput.vue";
import { useForm } from "@inertiajs/vue3";
import { defineEmits, computed } from "vue";

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
    units: {
        type: Array,
        required: true,
    },
    successMessage: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["openIconEdit"]);

// successMessage を computed に変更し、props から受け取る
const successMessage = computed(() => props.successMessage);

// ユーザー情報を取得
const user = props.user;
const units = props.units;

// フォームデータの設定
const form = useForm({
    name: user.name,
    username_id: user.username_id,
    tel: user.tel || "", // telに初期値を設定
    email: user.email || "", // emailに初期値を設定
    unit_id: user.unit_id ? String(user.unit_id) : "", // unit_idに初期値を設定
    _token: document
        .querySelector('meta[name="csrf-token"]')
        .getAttribute("content"),
});

// アイコン編集を開く関数
const handleOpenIconEdit = () => {
    emit("openIconEdit");
};
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900">
                {{ $t("Profile Information") }}
            </h2>

            <p class="mt-1 text-sm text-gray-600">
                {{ $t("Update your account's profile information.") }}
            </p>
        </header>

        <form
            @submit.prevent="form.patch(route('profile.update'))"
            class="mt-6 space-y-6"
        >
            <!-- プロフィール画像表示と編集ボタン -->
            <div class="relative w-24 h-24 group">
                <button
                    type="button"
                    @click="handleOpenIconEdit"
                    class="absolute inset-0 w-24 h-24 flex items-center justify-center bg-gray-800 text-white p-1 rounded-full hover:bg-gray-600 transition-opacity duration-300"
                    title="プロフィール画像を編集"
                >
                    <!-- プロフィール画像 -->
                    <img
                        :src="
                            user.icon &&
                            typeof user.icon === 'string' &&
                            user.icon.startsWith('/storage/')
                                ? user.icon
                                : user.icon
                                ? `/storage/${user.icon}`
                                : 'https://via.placeholder.com/100'
                        "
                        alt="ユーザーのプロフィール写真"
                        class="w-24 h-24 rounded-full object-cover transition-opacity duration-300 group-hover:opacity-70"
                    />

                    <!-- ペンのマーク -->
                    <div
                        class="absolute bottom-0 right-0 bg-gray-800 text-white p-1 rounded-full"
                        title="プロフィール画像を編集"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.5"
                            stroke="currentColor"
                            class="w-5 h-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M16.862 4.487a2.25 2.25 0 113.182 3.182L7.529 20.183a4.5 4.5 0 01-1.691 1.09l-4.013 1.337 1.337-4.013a4.5 4.5 0 011.09-1.691L16.862 4.487z"
                            />
                        </svg>
                    </div>
                </button>
            </div>

            <!-- 成功メッセージ表示 -->
            <div
                v-if="successMessage"
                class="bg-green-100 text-green-700 p-3 mb-6 rounded"
            >
                {{ successMessage }}
            </div>

            <!-- フォーム項目 -->
            <input type="hidden" name="_token" :value="form._token" />

            <div>
                <InputLabel for="name" :value="$t('Name')" />
                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autocomplete="name"
                />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div>
                <InputLabel for="username_id" :value="$t('Username_ID')" />
                <TextInput
                    id="username_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.username_id"
                    required
                />
                <InputError class="mt-2" :message="form.errors.username_id" />
                <!-- ゲストユーザー向けの説明文 -->
                <p
                    v-if="user.guest_session_id"
                    class="mt-2 text-sm text-red-600"
                >
                    ゲストユーザーではこのIDは使用されません。通常ログインで使用されるIDです。
                </p>
            </div>

            <div>
                <InputLabel for="tel" :value="$t('Tel')" />
                <TextInput
                    id="tel"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.tel"
                />
            </div>

            <div>
                <InputLabel for="email" :value="$t('Email')" />
                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                />
            </div>

            <div>
                <InputLabel for="unit_id" :value="$t('Unit')" />
                <select
                    v-model="form.unit_id"
                    id="unit_id"
                    class="w-full border border-gray-300 p-2 rounded"
                >
                    <option
                        v-for="unit in units"
                        :key="unit.id"
                        :value="unit.id"
                    >
                        {{ unit.name }}
                    </option>
                </select>
            </div>

            <div>
                <!-- 保存ボタン -->
                <div>
                    <PrimaryButton :disabled="form.processing">
                        {{ $t("Save") }}
                    </PrimaryButton>
                </div>

                <!-- フラッシュメッセージ -->
                <Transition
                    enter-active-class="transition ease-in-out"
                    enter-from-class="opacity-0"
                    leave-active-class="transition ease-in-out"
                    leave-to-class="opacity-0"
                >
                    <div
                        v-if="form.recentlySuccessful"
                        class="bg-green-100 text-green-700 p-3 mt-4 mb-6 rounded"
                    >
                        <p class="font-bold text-center">
                            {{ $t("Saved.") }}
                        </p>
                    </div>
                </Transition>
            </div>
        </form>
    </section>
</template>

<style scoped>
/* ホバー時に画像が薄暗くなる */
.group:hover img {
    opacity: 0.7;
}
</style>
