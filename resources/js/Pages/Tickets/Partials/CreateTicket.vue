<template>
    <Modal :show="show" @close="closeModal">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Create Support Ticket
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Please provide the details of your issue. Our support team will respond as soon as possible.
            </p>

            <form @submit.prevent="createTicket" class="mt-6">
                <div>
                    <InputLabel for="title" value="Title" />
                    <TextInput
                        id="title"
                        v-model="form.title"
                        type="text"
                        class="mt-1 block w-full"
                        required
                        autofocus
                    />
                    <InputError :message="form.errors.title" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="description" value="Description" />
                    <textarea
                        id="description"
                        v-model="form.description"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        rows="5"
                        required
                    ></textarea>
                    <InputError :message="form.errors.description" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="priority" value="Priority" />
                    <select
                        id="priority"
                        v-model="form.priority"
                        class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        required
                    >
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                    </select>
                    <InputError :message="form.errors.priority" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="attachment" value="Attachment (Optional)" />
                    <input
                        id="attachment"
                        type="file"
                        @input="form.attachment = $event.target.files[0]"
                        class="mt-1 block w-full text-gray-700 dark:text-gray-300"
                    />
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        Allowed file types: PDF, PNG, JPG, JPEG (Max: 5MB)
                    </p>
                    <InputError :message="form.errors.attachment" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <SecondaryButton @click="closeModal" class="mr-3">
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Create Ticket
                    </PrimaryButton>
                </div>
            </form>
        </div>
    </Modal>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';
import Modal from '@/Components/Modal.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const props = defineProps({
    show: Boolean,
});

const emit = defineEmits(['close']);

const form = useForm({
    title: '',
    description: '',
    priority: 'low',
    attachment: null,
}, {
    forceFormData: true, // Force form data for file uploads
});

const createTicket = () => {
    form.post(route('tickets.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
        },
    });
};

const closeModal = () => {
    form.reset();
    emit('close');
};
</script>
