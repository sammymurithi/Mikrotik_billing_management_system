<template>
    <Head :title="`Ticket #${ticket.id}`" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Ticket #{{ ticket.id }}</h2>
                <Link 
                    :href="route('tickets.index')" 
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-800"
                >
                    <font-awesome-icon icon="fa-arrow-left" class="mr-2" />
                    Back to Tickets
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                    <!-- Success Message -->
                    <div v-if="$page.props.flash && $page.props.flash.success" class="mb-6 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 px-4 py-3 rounded relative" role="alert">
                        <span class="block sm:inline">{{ $page.props.flash.success }}</span>
                    </div>

                    <!-- Ticket Details -->
                    <div class="mb-6">
                        <div class="flex justify-between items-start">
                            <h3 class="text-2xl font-bold text-gray-800 dark:text-gray-200 mb-4">{{ ticket.title }}</h3>
                            <div class="flex space-x-2">
                                <span 
                                    class="px-3 py-1 text-sm font-semibold rounded-full" 
                                    :class="{
                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': ticket.priority === 'low',
                                        'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300': ticket.priority === 'medium',
                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': ticket.priority === 'high'
                                    }"
                                >
                                    {{ ticket.priority.charAt(0).toUpperCase() + ticket.priority.slice(1) }} Priority
                                </span>
                                <span 
                                    class="px-3 py-1 text-sm font-semibold rounded-full" 
                                    :class="{
                                        'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300': ticket.status === 'open',
                                        'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300': ticket.status === 'closed'
                                    }"
                                >
                                    {{ ticket.status.charAt(0).toUpperCase() + ticket.status.slice(1) }}
                                </span>
                            </div>
                        </div>
                        
                        <div class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                            Created by {{ ticket.user.name }} on {{ new Date(ticket.created_at).toLocaleString() }}
                        </div>

                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg mb-6">
                            <p class="text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ ticket.description }}</p>
                        </div>

                        <div v-if="ticket.attachment" class="mt-4">
                            <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-2">Attachment</h4>
                            
                            <!-- Attachment Preview -->
                            <div class="mb-4">
                                <div v-if="isImageAttachment" class="border dark:border-gray-700 rounded-lg overflow-hidden max-w-lg">
                                    <img 
                                        :src="'/storage/' + ticket.attachment" 
                                        alt="Attachment Preview" 
                                        class="w-full h-auto object-contain"
                                    />
                                </div>
                                <div v-else-if="isPdfAttachment" class="border dark:border-gray-700 rounded-lg overflow-hidden p-4 bg-gray-50 dark:bg-gray-700 max-w-lg">
                                    <div class="flex items-center justify-center">
                                        <font-awesome-icon icon="fa-file-pdf" class="text-red-500 text-5xl" />
                                    </div>
                                    <p class="text-center mt-2 text-gray-600 dark:text-gray-300">PDF Document</p>
                                </div>
                                <div v-else class="border dark:border-gray-700 rounded-lg overflow-hidden p-4 bg-gray-50 dark:bg-gray-700 max-w-lg">
                                    <div class="flex items-center justify-center">
                                        <font-awesome-icon icon="fa-file" class="text-gray-500 text-5xl" />
                                    </div>
                                    <p class="text-center mt-2 text-gray-600 dark:text-gray-300">File Attachment</p>
                                </div>
                            </div>
                            
                            <a 
                                :href="route('tickets.download-attachment', ticket.id)" 
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 dark:bg-indigo-700 text-white rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                                target="_blank"
                            >
                                <font-awesome-icon icon="fa-download" class="mr-2" />
                                Download Attachment
                            </a>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 border-t dark:border-gray-700 pt-4">
                        <button 
                            v-if="ticket.status === 'open'"
                            @click="confirmClose" 
                            class="px-4 py-2 bg-yellow-500 dark:bg-yellow-600 text-white rounded-md hover:bg-yellow-600 dark:hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 dark:focus:ring-offset-gray-800"
                        >
                            <font-awesome-icon icon="fa-check" class="mr-2" />
                            Close Ticket
                        </button>
                        <button 
                            @click="confirmDelete" 
                            class="px-4 py-2 bg-red-600 dark:bg-red-700 text-white rounded-md hover:bg-red-700 dark:hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 dark:focus:ring-offset-gray-800"
                        >
                            <font-awesome-icon icon="fa-trash" class="mr-2" />
                            Delete Ticket
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delete Confirmation Modal -->
        <ConfirmationModal :show="confirmingDeletion" @close="confirmingDeletion = false">
            <template #title>
                Delete Ticket
            </template>
            <template #content>
                Are you sure you want to delete this ticket? This action cannot be undone.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingDeletion = false">
                    Cancel
                </SecondaryButton>

                <DangerButton
                    class="ml-3"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="deleteTicket"
                >
                    Delete Ticket
                </DangerButton>
            </template>
        </ConfirmationModal>
        
        <!-- Close Ticket Confirmation Modal -->
        <ConfirmationModal :show="confirmingClosure" @close="confirmingClosure = false">
            <template #title>
                Close Ticket
            </template>
            <template #content>
                Are you sure you want to close this ticket? This will mark the issue as resolved.
            </template>
            <template #footer>
                <SecondaryButton @click="confirmingClosure = false">
                    Cancel
                </SecondaryButton>

                <button
                    class="ml-3 inline-flex items-center px-4 py-2 bg-yellow-600 dark:bg-yellow-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-yellow-700 dark:hover:bg-yellow-600 focus:bg-yellow-700 dark:focus:bg-yellow-600 active:bg-yellow-900 dark:active:bg-yellow-800 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                    @click="closeTicket"
                >
                    Close Ticket
                </button>
            </template>
        </ConfirmationModal>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ConfirmationModal from '@/Components/ConfirmationModal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';

const props = defineProps({
    ticket: Object,
});

const confirmingDeletion = ref(false);
const confirmingClosure = ref(false);
const form = useForm({
    status: 'closed',
});

// Determine the type of attachment for preview
const isImageAttachment = computed(() => {
    if (!props.ticket.attachment) return false;
    const attachment = props.ticket.attachment.toLowerCase();
    return attachment.endsWith('.jpg') || attachment.endsWith('.jpeg') || attachment.endsWith('.png');
});

const isPdfAttachment = computed(() => {
    if (!props.ticket.attachment) return false;
    return props.ticket.attachment.toLowerCase().endsWith('.pdf');
});

const confirmDelete = () => {
    confirmingDeletion.value = true;
};

const deleteTicket = () => {
    form.delete(route('tickets.destroy', props.ticket.id), {
        onSuccess: () => {
            confirmingDeletion.value = false;
        },
    });
};

const confirmClose = () => {
    confirmingClosure.value = true;
};

const closeTicket = () => {
    // Reset the form to ensure clean data
    form.reset();
    form.status = 'closed';
    
    form.put(route('tickets.update', props.ticket.id), {
        onSuccess: () => {
            confirmingClosure.value = false;
            // Update the ticket status in the UI immediately
            props.ticket.status = 'closed';
        },
    });
};
</script>
