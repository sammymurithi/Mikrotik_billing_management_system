<template>
    <AppLayout title="User Management">
      <template #header>
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
          User Management
        </h2>
      </template>
  
      <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
          <div class="bg-white overflow-hidden shadow-xl sm:rounded-lg p-6">
            <div class="flex justify-between items-center mb-6">
              <h3 class="text-lg font-medium text-gray-900">System Users</h3>
              <Link
                :href="route('users.create')"
                class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring focus:ring-gray-300 disabled:opacity-25 transition"
              >
                Add New User
              </Link>
            </div>
  
            <!-- Flash Message -->
            <div v-if="$page.props.flash?.success" class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
              {{ $page.props.flash.success }}
            </div>
  
            <div v-if="loading" class="text-center">
              <span>Loading...</span> <!-- You can replace this with a spinner -->
            </div>
  
            <div v-else>
              <div class="bg-white shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Name
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Email
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Role
                      </th>
                      <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Created
                      </th>
                      <th scope="col" class="relative px-6 py-3">
                        <span class="sr-only">Actions</span>
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200" v-if="users.data && users.data.length > 0">
                    <tr v-for="user in users.data" :key="user.id">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ user.name }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-500">{{ user.email }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <!-- Check if user.roles exists and has at least one role -->
                        <span 
                          class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                          :class="user.roles && user.roles.length > 0 && user.roles[0].name === 'admin' ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'"
                        >
                          <!-- Safely access user.roles[0].name -->
                          {{ user.roles && user.roles.length > 0 ? user.roles[0].name : 'No Role' }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ new Date(user.created_at).toLocaleDateString() }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <Link :href="route('users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900 mr-3">
                          Edit
                        </Link>
                        <button 
                          @click="confirmUserDeletion(user)" 
                          class="text-red-600 hover:text-red-900"
                        >
                          Delete
                        </button>
                      </td>
                    </tr>
                  </tbody>
                  <tbody v-if="users.data.length === 0">
                    <tr>
                      <td colspan="5" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                        No users found
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
  
              <!-- Pagination Component -->
              <Pagination :links="users.links" class="mt-6" />
            </div>
          </div>
        </div>
      </div>
  
      <!-- Delete User Modal -->
      <Dialog v-model:open="deleteModalOpen" as="div" class="relative z-10">
        <DialogOverlay class="fixed inset-0 bg-black opacity-30" />
  
        <div class="fixed inset-0 overflow-y-auto">
          <div class="flex min-h-full items-center justify-center p-4 text-center">
            <DialogPanel class="w-full max-w-md transform overflow-hidden rounded-2xl bg-white p-6 text-left align-middle shadow-xl transition-all">
              <DialogTitle as="h3" class="text-lg font-medium leading-6 text-gray-900">
                Delete User
              </DialogTitle>
              <div class="mt-2">
                <p class="text-sm text-gray-500">
                  Are you sure you want to delete this user? This action cannot be undone.
                </p>
              </div>
  
              <div class="mt-4 flex justify-end space-x-3">
                <button
                  type="button"
                  class="inline-flex justify-center rounded-md border border-transparent bg-gray-100 px-4 py-2 text-sm font-medium text-gray-900 hover:bg-gray-200 focus:outline-none"
                  @click="deleteModalOpen = false"
                >
                  Cancel
                </button>
                <button
                  type="button"
                  class="inline-flex justify-center rounded-md border border-transparent bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 focus:outline-none"
                  @click="deleteUser"
                  :disabled="deleteForm.processing"
                >
                  Delete
                </button>
              </div>
            </DialogPanel>
          </div>
        </div>
      </Dialog>
    </AppLayout>
  </template>
  
  <script>
  import { ref } from 'vue';
  import { Link, useForm } from '@inertiajs/inertia-vue3';
  import AppLayout from '@/Layouts/AppLayout.vue';
  import Pagination from '@/Components/Pagination.vue';
  import { Dialog, DialogOverlay, DialogTitle, DialogPanel } from '@headlessui/vue';
  
  export default {
    components: {
      AppLayout,
      Link,
      Pagination,
      Dialog,
      DialogOverlay,
      DialogTitle,
      DialogPanel
    },
    
    props: {
      users: Object,
    },
  
    setup() {
      const loading = ref(true);  // Track the loading state
      const deleteModalOpen = ref(false);
      const userToDelete = ref(null);
      
      const users = ref([]);
      const deleteForm = useForm({});
      
      // Fetch users and ensure roles is always an array
      const fetchUsers = async () => {
        const response = await fetch('/api/users');  // Replace with your actual API endpoint
        const data = await response.json();
        users.value = data.map(user => ({
          ...user,
          roles: Array.isArray(user.roles) ? user.roles : [], // Ensure roles is always an array
        }));
        loading.value = false;
      };
  
      fetchUsers(); // Initial data fetch
      
      const confirmUserDeletion = (user) => {
        userToDelete.value = user;
        deleteModalOpen.value = true;
      };
      
      const deleteUser = () => {
        deleteForm.delete(route('users.destroy', userToDelete.value.id), {
          preserveScroll: true,
          onSuccess: () => {
            deleteModalOpen.value = false;
            userToDelete.value = null;
          },
        });
      };
  
      return {
        loading,
        users,
        deleteModalOpen,
        confirmUserDeletion,
        deleteForm,
        deleteUser,
      };
    },
  };
  </script>
  