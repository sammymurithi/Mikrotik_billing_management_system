<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({
  profiles: {
    type: Array,
    default: () => [],
  },
  routers: {
    type: Array,
    default: () => [],
  },
});

const form = useForm({
  username: '',
  password: '',
  profile_name: '', // Changed to profile_name to match MikroTik's profile field
  router_id: '',
  mac_address: '',
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
      <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
        Create Hotspot User
      </h2>
    </template>

    <div class="py-12">
      <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
          <div class="p-6 text-gray-900 dark:text-gray-100">
            <form @submit.prevent="submit" class="space-y-6">
              <!-- Username -->
              <div>
                <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                <input
                  v-model="form.username"
                  type="text"
                  id="username"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <div v-if="form.errors.username" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.username }}</div>
              </div>

              <!-- Password -->
              <div>
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                <input
                  v-model="form.password"
                  type="password"
                  id="password"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                />
                <div v-if="form.errors.password" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.password }}</div>
              </div>

              <!-- Profile -->
              <div>
                <label for="profile_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Profile</label>
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
                <div v-if="form.errors.profile_name" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.profile_name }}</div>
              </div>

              <!-- Router -->
              <div>
                <label for="router_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Router</label>
                <select
                  v-model="form.router_id"
                  id="router_id"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  required
                >
                  <option value="">Select Router</option>
                  <option v-for="router in routers" :key="router.id" :value="router.id">{{ router.name }}</option>
                </select>
                <div v-if="form.errors.router_id" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.router_id }}</div>
              </div>

              <!-- MAC Address -->
              <div>
                <label for="mac_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300">MAC Address (Optional)</label>
                <input
                  v-model="form.mac_address"
                  type="text"
                  id="mac_address"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                  placeholder="e.g., 00:11:22:33:44:55"
                />
                <div v-if="form.errors.mac_address" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.mac_address }}</div>
              </div>

              <!-- Expires At -->
              <div>
                <label for="expires_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Expires At (Optional)</label>
                <input
                  v-model="form.expires_at"
                  type="datetime-local"
                  id="expires_at"
                  class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
                <div v-if="form.errors.expires_at" class="text-red-600 dark:text-red-400 text-sm mt-1">{{ form.errors.expires_at }}</div>
              </div>

              <!-- Submit Button -->
              <div>
                <button
                  type="submit"
                  class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                  :disabled="form.processing"
                >
                  Create User
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>