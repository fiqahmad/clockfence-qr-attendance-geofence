<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineProps<{
    copy: {
        forgot_title: string;
        forgot_intro: string;
        email: string;
        send: string;
        back_to_login: string;
    };
}>();

const status = computed(() => usePage().props.status as string | null);
</script>

<template>
    <div
        class="flex min-h-screen items-center justify-center bg-stone-50 p-4 text-stone-900 dark:bg-stone-950 dark:text-stone-100"
    >
        <Head :title="copy.forgot_title" />
        <main
            class="w-full max-w-sm rounded-xl bg-white p-6 shadow-sm ring-1 ring-stone-200 dark:bg-stone-900 dark:ring-stone-800"
        >
            <h1 class="text-xl font-semibold">{{ copy.forgot_title }}</h1>
            <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                {{ copy.forgot_intro }}
            </p>

            <p
                v-if="status"
                class="mt-4 rounded-lg bg-green-50 p-3 text-sm text-green-800 dark:bg-green-950 dark:text-green-300"
            >
                {{ status }}
            </p>

            <Form
                v-bind="email.form()"
                class="mt-6 flex flex-col gap-4"
                #default="{ errors, processing }"
            >
                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium">{{ copy.email }}</span>
                    <input
                        v-focus
                        type="email"
                        name="email"
                        required
                        autocomplete="username"
                        inputmode="email"
                        class="rounded-lg border border-stone-300 px-3 py-2 dark:border-stone-700 dark:bg-stone-950"
                    />
                    <span
                        v-if="errors.email"
                        class="text-red-600 dark:text-red-400"
                        >{{ errors.email }}</span
                    >
                </label>

                <button
                    type="submit"
                    :disabled="processing"
                    class="rounded-lg bg-stone-900 px-4 py-2 font-medium text-white disabled:opacity-50 dark:bg-white dark:text-stone-900"
                >
                    {{ copy.send }}
                </button>
            </Form>

            <Link
                :href="login()"
                class="mt-4 block text-center text-sm text-stone-600 underline dark:text-stone-400"
                >{{ copy.back_to_login }}</Link
            >
        </main>
    </div>
</template>
