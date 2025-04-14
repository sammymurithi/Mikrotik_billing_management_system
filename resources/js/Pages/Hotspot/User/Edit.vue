<template>
    <AppLayout title="Edit Hotspot User">
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          Edit Hotspot User
        </h2>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900">Edit Hotspot User</h3>
              <div class="flex space-x-2">
                <Link
                  :href="route('hotspot.users.show', user.id)"
                  class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
                >
                  View Details
                </Link>
                <Link
                  :href="route('hotspot.users.index')"
                  class="inline-flex items-center px-4 py-2 bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-gray-800 uppercase tracking-widest hover:bg-gray-300 active:bg-gray-400 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
                >
                  Back to Users
                </Link>
              </div>
            </div>
            
            <form @submit.prevent="submit">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <InputLabel for="username" value="Username" />
                  <TextInput
                    id="username"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.username"
                    required
                  />
                  <InputError class="mt-2" :message="form.errors.username" />
                </div>
  
                <div>
                  <InputLabel for="password" value="Password (leave blank to keep unchanged)" />
                  <TextInput
                    id="password"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.password"
                  />
                  <InputError class="mt-2" :message="form.errors.password" />
                  <button type="button" @click="generatePassword" class="text-sm text-blue-600 hover:text-blue-800 mt-1">
                    Generate Password
                  </button>
                </div>
  
                <div>
                  <InputLabel for="profile_id" value="Profile" />
                  <SelectInput
                    id="profile_id"
                    class="mt-1 block w-full"
                    v-model="form.profile_id"
                    required
                  >
                    <option value="">Select a profile</option>
                    <option v-for="profile in profiles" :key="profile.id" :value="profile.id">
                      {{ profile.name }}
                    </option>
                  </SelectInput>
                  <InputError class="mt-2" :message="form.errors.profile_id" />
                </div>
  
                <div>
                  <InputLabel for="mac_address" value="MAC Address (optional)" />
                  <TextInput
                    id="mac_address"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.mac_address"
                    placeholder="AA:BB:CC:DD:EE:FF"
                  />
                  <InputError class="mt-2" :message="form.errors.mac_address" />
                </div>
  
                <div>
                  <InputLabel for="expires_at" value="Expiration Date (optional)" />
                  <TextInput
                    id="expires_at"
                    type="date"
                    class="mt-1 block w-full"
                    v-model="form.expires_at"
                  />
                  <InputError class="mt-2" :message="form.errors.expires_at" />
                </div>
  
                <div>
                  <div class="flex items-center mt-4">
                    <Checkbox id="is_active" v-model:checked="form.is_active" />
                    <InputLabel for="is_active" value="Active" class="ml-2" />
                  </div>
                  <InputError class="mt-2" :message="form.errors.is_active" />
                </div>
              </div>
  
              <div class="flex items-center justify-end mt-8">
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
  import { Link } from '@inertiajs/inertia-vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import InputLabel from '@/Components/InputLabel.vue';
  import TextInput from '@/Components/TextInput.vue';
  import InputError from '@/Components/InputError.vue';
  import SelectInput from '@/Components/SelectInput.vue';
  import Checkbox from '@/Components/Checkbox.vue';
  import PrimaryButton from '@/Components/PrimaryButton.vue';
  
  export default {
    components: {
      AppLayout,
      InputLabel,
      TextInput,
      InputError,
      SelectInput,
      Checkbox, 
      PrimaryButton,
      Link
    },
    
    props: {
      user: Object,
      profiles: Array,
    },
    
    setup(props) {
      const form = useForm({
        username: props.user.username,
        password: '',
        profile_id: props.user.profile_id,
        mac_address: props.user.mac_address || '',
        expires_at: props.user.expires_at ? new Date(props.user.expires_at).toISOString().split('T')[0] : '',
        is_active: props.user.is_active,
      });
      
      const generatePassword = () => {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let result = '';
        for (let i = 0; i < 8; i++) {
          result += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        form.password = result;
      };
      
      const submit = () => {
        form.put(route('hotspot.users.update', props.user.id), {
          preserveScroll: true,
        });
      };
      
      return {
        form,
        generatePassword,
        submit,
      };
    },
  };
  </script>