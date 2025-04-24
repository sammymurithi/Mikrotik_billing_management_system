<script setup>
import { Head, useForm, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Banner from '@/Components/Banner.vue';
import { ref, onMounted } from 'vue';

const showPassword = ref(false);
const showPasswordConfirmation = ref(false);
const nameInput = ref(null);
const errorMessage = ref('');

const form = useForm({
    username: '',
    password: '',
    password_confirmation: '',
    profile_name: '',
    router_id: '',
    expires_at: '',
});

const submit = () => {
  form.post(route('hotspot.users.store'), {
    onSuccess: () => form.reset(),
  });
};
</script>

<template>
    <Head title="Create Hotspot User" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                    Create New Router
                </h2>
                <Link
                    :href="route('routers.index')"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                >
                    Back to Routers
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Display error banner if there are any errors -->
                <Banner
                    v-if="Object.keys(form.errors).length > 0 || errorMessage"
                    type="danger"
                    class="mb-4"
                >
                    <template #title>Error Creating Router</template>
                    <template #content>
                        <div class="space-y-2">
                            <p class="text-sm font-medium">Please fix the following errors:</p>
                            <ul class="list-disc list-inside text-sm">
                                <li v-if="errorMessage" class="text-red-600">
                                    {{ errorMessage }}
                                </li>
                                <li v-for="(error, field) in form.errors" :key="field">
                                    <span class="font-medium">{{ field }}:</span> {{ error }}
                                </li>
                            </ul>
                        </div>
                    </template>
                </Banner>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <form @submit.prevent="submit" class="space-y-6">
                            <div>
                                <InputLabel for="username" value="Username" />
                                <TextInput
                                    ref="nameInput"
                                    id="username"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.username"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.username" />
                            </div>

                            <div>
                                <InputLabel for="password" value="Password" />
                                <div class="relative">
                                    <TextInput
                                        id="password"
                                        :type="showPassword ? 'text' : 'password'"
                                        class="mt-1 block w-full pr-10"
                                        v-model="form.password"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition-colors duration-200"
                                        @click="showPassword = !showPassword"
                                        :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                    >
                                        <svg
                                            v-if="showPassword"
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-2" :message="form.errors.password" />
                            </div>

                            <div>
                                <InputLabel for="password_confirmation" value="Confirm Password" />
                                <div class="relative">
                                    <TextInput
                                        id="password_confirmation"
                                        :type="showPasswordConfirmation ? 'text' : 'password'"
                                        class="mt-1 block w-full pr-10"
                                        v-model="form.password_confirmation"
                                        required
                                        autocomplete="new-password"
                                    />
                                    <button
                                        type="button"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500 transition-colors duration-200"
                                        @click="showPasswordConfirmation = !showPasswordConfirmation"
                                        :aria-label="showPasswordConfirmation ? 'Hide password' : 'Show password'"
                                    >
                                        <svg
                                            v-if="showPasswordConfirmation"
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"
                                            />
                                        </svg>
                                        <svg
                                            v-else
                                            class="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                            aria-hidden="true"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                                            />
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                                            />
                                        </svg>
                                    </button>
                                </div>
                                <InputError class="mt-2" :message="form.errors.password_confirmation" />
                            </div>

                            <div>
                                <InputLabel for="profile_name" value="Profile" />
                                <select
                                    v-model="form.profile_name"
                                    id="profile_name"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select Profile</option>
                                    <option v-for="profile in profiles" :key="profile.name" :value="profile.name">
                                        {{ profile.name }}
                                        <span v-if="profile.rate_limit" class="text-xs text-gray-500 dark:text-gray-400">
                                            ({{ profile.rate_limit }})
                                        </span>
                                        <span v-if="profile.shared_users" class="text-xs text-gray-500 dark:text-gray-400">
                                            - {{ profile.shared_users }} users
                                        </span>
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.profile_name" />
                            </div>

                            <div>
                                <InputLabel for="router_id" value="Router" />
                                <select
                                    v-model="form.router_id"
                                    id="router_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                    required
                                >
                                    <option value="">Select Router</option>
                                    <option v-for="router in routers" :key="router.id" :value="router.id">
                                        {{ router.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.router_id" />
                            </div>

                            <div>
                                <InputLabel for="expires_at" value="Expires At (Optional)" />
                                <input
                                    v-model="form.expires_at"
                                    type="datetime-local"
                                    id="expires_at"
                                    class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                />
                                <InputError class="mt-2" :message="form.errors.expires_at" />
                            </div>

                            <div class="flex items-center justify-end">
                                <PrimaryButton
                                    class="ml-4"
                                    :class="{ 'opacity-25': form.processing }"
                                    :disabled="form.processing"
                                >
                                    Create User
                                </PrimaryButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>