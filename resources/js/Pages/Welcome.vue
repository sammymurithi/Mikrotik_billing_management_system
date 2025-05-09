<script setup>
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ref, computed } from 'vue';

defineOptions({
    layout: AppLayout,
});

// Define props
const props = defineProps({
    hotspotProfiles: {
        type: Array,
        default: () => [],
    },
    canLogin: {
        type: Boolean,
        default: false
    },
    canRegister: {
        type: Boolean,
        default: false
    },
});

// Package categories
const categories = ref([
    { id: 'daily', name: 'Daily Packages', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
    { id: 'monthly', name: 'Monthly Packages', icon: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { id: 'session', name: 'Session Packages', icon: 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z' },
]);

// Selected category
const selectedCategory = ref('daily');

// Fallback data for testing card display
const fallbackProfiles = [
    { 
        id: 1, 
        name: 'Basic Daily', 
        price: '100', 
        rate_limit: '10M/10M', 
        session_timeout: '1d', 
        shared_users: 1,
        category: 'daily',
        features: ['10Mbps Download', '10Mbps Upload', '24 Hours Access', '1 Device']
    },
    { 
        id: 2, 
        name: 'Premium Daily', 
        price: '200', 
        rate_limit: '20M/20M', 
        session_timeout: '1d', 
        shared_users: 2,
        category: 'daily',
        features: ['20Mbps Download', '20Mbps Upload', '24 Hours Access', '2 Devices']
    },
    { 
        id: 3, 
        name: 'Basic Monthly', 
        price: '2000', 
        rate_limit: '10M/10M', 
        session_timeout: '30d', 
        shared_users: 1,
        category: 'monthly',
        features: ['10Mbps Download', '10Mbps Upload', '30 Days Access', '1 Device']
    },
    { 
        id: 4, 
        name: 'Premium Monthly', 
        price: '4000', 
        rate_limit: '20M/20M', 
        session_timeout: '30d', 
        shared_users: 2,
        category: 'monthly',
        features: ['20Mbps Download', '20Mbps Upload', '30 Days Access', '2 Devices']
    },
    { 
        id: 5, 
        name: '2 Hour Session', 
        price: '50', 
        rate_limit: '10M/10M', 
        session_timeout: '2h', 
        shared_users: 1,
        category: 'session',
        features: ['10Mbps Download', '10Mbps Upload', '2 Hours Access', '1 Device']
    },
    { 
        id: 6, 
        name: '4 Hour Session', 
        price: '80', 
        rate_limit: '10M/10M', 
        session_timeout: '4h', 
        shared_users: 1,
        category: 'session',
        features: ['10Mbps Download', '10Mbps Upload', '4 Hours Access', '1 Device']
    },
];

// Filter profiles by selected category
const filteredProfiles = computed(() => {
    const profiles = props.hotspotProfiles.length > 0 ? props.hotspotProfiles : fallbackProfiles;
    return profiles
        .filter(profile => profile.category === selectedCategory.value)
        .sort((a, b) => parseFloat(a.price) - parseFloat(b.price));
});
</script>

<template>
    <Head title="Welcome to JTG Networks" />

    <div class="min-h-screen bg-gradient-to-br from-stone-50 to-stone-100 dark:from-slate-900 dark:to-slate-800">
        <!-- Hero Section -->
        <section class="relative py-20 overflow-hidden">
            <div class="absolute inset-0 bg-gradient-to-r from-amber-500/10 to-emerald-500/10 dark:from-amber-500/5 dark:to-emerald-500/5"></div>
            <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center">
                    <h1 class="text-4xl md:text-6xl font-extrabold text-gray-900 dark:text-white mb-6 animate-fade-in">
                        Welcome to JTG Networks
                    </h1>
                    <p class="text-xl md:text-2xl text-gray-600 dark:text-gray-300 max-w-3xl mx-auto mb-8 animate-fade-in-up">
                        Experience lightning-fast internet with our premium packages. Choose your perfect plan and stay connected.
                    </p>
                    <div class="flex justify-center space-x-4">
                        <a 
                            v-if="props.canLogin" 
                            :href="route('login')" 
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-amber-500 hover:bg-amber-600 transition duration-300"
                        >
                            Login
                        </a>
                        <a 
                            v-if="props.canRegister" 
                            :href="route('register')" 
                            class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-amber-500 bg-amber-100 hover:bg-amber-200 dark:bg-amber-900/50 dark:hover:bg-amber-900/70 transition duration-300"
                        >
                            Register
                        </a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 bg-white dark:bg-gray-800">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div class="text-center p-6 rounded-xl bg-amber-50 dark:bg-amber-900/30">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Lightning Fast</h3>
                        <p class="text-gray-600 dark:text-gray-300">Experience blazing-fast internet speeds with our optimized network infrastructure.</p>
                    </div>
                    <div class="text-center p-6 rounded-xl bg-amber-50 dark:bg-amber-900/30">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">Secure Connection</h3>
                        <p class="text-gray-600 dark:text-gray-300">Your data is protected with enterprise-grade security measures.</p>
                    </div>
                    <div class="text-center p-6 rounded-xl bg-amber-50 dark:bg-amber-900/30">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-amber-100 dark:bg-amber-900/50 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">24/7 Support</h3>
                        <p class="text-gray-600 dark:text-gray-300">Our dedicated support team is always ready to help you with any issues.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Package Categories -->
        <section class="py-20 bg-gray-50 dark:bg-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-extrabold text-gray-900 dark:text-white mb-4">Choose Your Package</h2>
                    <p class="text-xl text-gray-600 dark:text-gray-300">Select the perfect plan for your needs</p>
                    </div>

                <div class="flex justify-center space-x-4 mb-12">
                    <button 
                        v-for="category in categories"
                        :key="category.id"
                        @click="selectedCategory = category.id"
                        class="flex items-center px-6 py-3 rounded-lg transition-all duration-300"
                        :class="[
                            selectedCategory === category.id
                                ? 'bg-amber-500 text-white shadow-lg'
                                : 'bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-amber-100 dark:hover:bg-slate-700'
                        ]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="category.icon" />
                        </svg>
                        {{ category.name }}
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div 
                        v-for="profile in filteredProfiles" 
                        :key="profile.id"
                        class="bg-white dark:bg-slate-800 rounded-xl shadow-lg overflow-hidden transition-all duration-300 hover:shadow-xl hover:scale-105"
                    >
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h4 class="text-xl font-semibold text-gray-900 dark:text-white">{{ profile.name }}</h4>
                                <span class="px-3 py-1 bg-amber-100 dark:bg-amber-900/50 text-amber-600 dark:text-amber-400 rounded-full text-sm font-medium">
                                    {{ profile.category }}
                                </span>
                            </div>
                            <div class="mb-6">
                                <span class="text-3xl font-bold text-gray-900 dark:text-white">
                                    Kes {{ profile.price }}
                                </span>
                            </div>
                            <ul class="space-y-3 mb-6">
                                <li v-for="feature in profile.features" :key="feature" class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-emerald-500" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                    </svg>
                                    <span class="text-gray-600 dark:text-gray-300">{{ feature }}</span>
                                </li>
                            </ul>
                            <a 
                                v-if="props.canLogin"
                                :href="route('login')"
                                class="block w-full py-3 px-4 bg-amber-500 text-white text-center rounded-lg hover:bg-amber-600 transition duration-300 font-medium"
                            >
                                Get Started
                            </a>
                            <a 
                                v-else
                                :href="route('register')"
                                class="block w-full py-3 px-4 bg-amber-500 text-white text-center rounded-lg hover:bg-amber-600 transition duration-300 font-medium"
                            >
                                Sign Up Now
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-amber-500">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl font-extrabold text-white mb-4">Ready to Get Started?</h2>
                <p class="text-xl text-amber-100 mb-8">Join thousands of satisfied customers enjoying our premium internet service.</p>
                <div class="flex justify-center space-x-4">
                    <a 
                        v-if="props.canLogin"
                        :href="route('login')"
                        class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-amber-500 bg-white hover:bg-amber-50 transition duration-300"
                    >
                        Login to Your Account
                    </a>
                    <a 
                        v-if="props.canRegister"
                        :href="route('register')"
                        class="inline-flex items-center px-6 py-3 border-2 border-white text-base font-medium rounded-md text-white hover:bg-amber-600 transition duration-300"
                    >
                        Create New Account
                    </a>
                </div>
            </div>
        </section>
    </div>
</template>

<style scoped>
/* Animations */
@keyframes fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes fade-in-up {
    from {
    opacity: 0;
    transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.animate-fade-in {
    animation: fade-in 1s ease-out;
}

.animate-fade-in-up {
    animation: fade-in-up 1s ease-out;
}

/* Card hover effects */
.package-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.package-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}
</style>