<template>
    <GuestLayout>
        <Head title="Tenant Registration" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="tenant_name" value="Tenant Name" />

                <TextInput
                    id="tenant_name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.tenant_name"
                    required
                    autofocus
                />

                <InputError class="mt-2" :message="form.errors.tenant_name" />
            </div>

            <div class="flex items-center justify-end mt-4">
                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Register Tenant
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>

<script setup>
import GuestLayout from "@/Layouts/GuestLayout.vue";
import InputLabel from "@/Components/InputLabel.vue";
import TextInput from "@/Components/TextInput.vue";
import InputError from "@/Components/InputError.vue";
import PrimaryButton from "@/Components/PrimaryButton.vue";
import { Head, useForm } from "@inertiajs/vue3";

const form = useForm({
    tenant_name: "",
});

const submit = () => {
    form.post(route("tenant.register"), {
        onFinish: () => form.reset(),
    });
};
</script>
