<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { update } from '@/routes/password';

defineProps<{
    token: string;
    email: string;
    copy: {
        reset_title: string;
        email: string;
        password: string;
        password_confirmation: string;
        save: string;
    };
}>();
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-stone-50 p-4 text-stone-900 dark:bg-stone-950 dark:text-stone-100"
    >
        <Head :title="copy.reset_title" />
        <main
            class="w-full max-w-sm rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
        >
            <h1 class="text-xl font-semibold">{{ copy.reset_title }}</h1>

            <Form
                v-bind="update.form()"
                class="mt-6 flex flex-col gap-4"
                :reset-on-error="['password', 'password_confirmation']"
                #default="{ errors, processing }"
            >
                <input type="hidden" name="token" :value="token" />

                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium">{{ copy.email }}</span>
                    <input
                        type="email"
                        name="email"
                        :value="email"
                        readonly
                        autocomplete="username"
                        class="rounded-lg border border-stone-300 bg-stone-100 px-3 py-2 dark:border-stone-700 dark:bg-stone-800"
                    />
                    <span
                        v-if="errors.email"
                        class="text-red-600 dark:text-red-400"
                        >{{ errors.email }}</span
                    >
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium">{{ copy.password }}</span>
                    <input
                        v-focus
                        type="password"
                        name="password"
                        required
                        autocomplete="new-password"
                        class="rounded-lg border border-stone-300 px-3 py-2 dark:border-stone-700 dark:bg-stone-950"
                    />
                    <span
                        v-if="errors.password"
                        class="text-red-600 dark:text-red-400"
                        >{{ errors.password }}</span
                    >
                </label>

                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium">{{
                        copy.password_confirmation
                    }}</span>
                    <input
                        type="password"
                        name="password_confirmation"
                        required
                        autocomplete="new-password"
                        class="rounded-lg border border-stone-300 px-3 py-2 dark:border-stone-700 dark:bg-stone-950"
                    />
                </label>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-stone-900 px-4 py-2 font-medium text-white disabled:opacity-50 dark:bg-white dark:text-stone-900"
                >
                    {{ copy.save }}
                </button>
            </Form>
        </main>
    </div>
</template>
