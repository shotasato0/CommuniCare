<template>
    <GuestLayout>
        <Head :title="$t('Tenant Login')" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="business_name" :value="$t('Business Name')" />
                <TextInput
                    id="business_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.business_name"
                    required
                    autofocus
                />
                <InputError class="mt-2" :message="form.errors.business_name" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="tenant_domain_id"
                    :value="$t('Tenant Domain ID')"
                />
                <TextInput
                    id="tenant_domain_id"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.tenant_domain_id"
                    required
                />
                <InputError
                    class="mt-2"
                    :message="form.errors.tenant_domain_id"
                />
            </div>

            <div class="block mt-4">
                <label class="flex items-center">
                    <Checkbox name="remember" v-model:checked="form.remember" />
                    <span class="ms-2 text-sm text-gray-600">{{
                        $t("Remember Me")
                    }}</span>
                </label>
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    {{ $t("Login") }}
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from "@/Layouts/TenantGuestLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({
    business_name: "",
    tenant_domain_id: "",
    remember: false,
});

const submit = () => {
    form.post(route("tenant.login"), {
        onFinish: () => form.reset("password"),
    });
};
</script>
