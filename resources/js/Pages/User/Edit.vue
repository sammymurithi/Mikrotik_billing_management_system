<template>
    <AppLayout title="Edit User">
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
          Edit User
        </h2>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Edit User: {{ user.name }}</h3>
              <Link
                :href="route('users.index')"
                class="inline-flex items-center px-4 py-2 bg-gray-200 dark:bg-gray-700 border border-transparent rounded-md font-semibold text-xs text-gray-800 dark:text-gray-200 uppercase tracking-widest hover:bg-gray-300 dark:hover:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-500 focus:outline-none focus:border-gray-900 dark:focus:border-gray-400 focus:ring focus:ring-gray-300 dark:focus:ring-gray-700 disabled:opacity-25 transition"
              >
                Back to Users
              </Link>
            </div>
            
            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="name" value="Name" />
                  <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                  />
                  <InputError class="mt-2" :message="form.errors.name" />
                </div>
  
                <div>
                  <InputLabel for="email" value="Email" />
                  <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.email" />
                </div>
  
                <div>
                  <InputLabel for="password" value="Password (leave blank to keep current)" />
                  <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    autocomplete="new-password"
                  />
                  <InputError class="mt-2" :message="form.errors.password" />
                </div>
  
                <div>
                  <InputLabel for="password_confirmation" value="Confirm Password" />
                  <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    autocomplete="new-password"
                  />
                  <InputError class="mt-2" :message="form.errors.password_confirmation" />
                </div>
  
                <div>
                  <InputLabel for="role" value="Role" />
                  <select
                    id="role"
                    v-model="form.role"
                    class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                    required
                  >
                    <option value="">Select Role</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                  </select>
                  <InputError class="mt-2" :message="form.errors.role" />
                </div>
              </div>
  
              <div class="flex items-center justify-end mt-6">
                <PrimaryButton class="ml-4" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                  Update User
                </PrimaryButton>
              </div>
            </form>
          </div>
        </div>
      </div>
    </AppLayout>
  </template>
  
  <script>
  import { useForm } from '@inertiajs/inertia-vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import InputError from '@/Components/InputError.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  import TextInput from '@/Components/TextInput.vue';
  import { Link } from '@inertiajs/inertia-vue3';
  
  export default {
    components: {
      AppLayout,
      InputError,
      InputLabel,
      PrimaryButton,
      TextInput,
      Link,
    },
    
    props: {
      user: Object,
    },
    
    setup(props) {
      const form = useForm({
        name: props.user.name,
        email: props.user.email,
        password: '',
        password_confirmation: '',
        role: props.user.roles && props.user.roles.length > 0 ? props.user.roles[0].name : '',
      });
  
      function submit() {
        form.put(route('users.update', props.user.id), {
          preserveScroll: true,
        });
      }
  
      return {
        form,
        submit,
      };
    },
  };
  </script>