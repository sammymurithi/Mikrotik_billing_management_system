<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

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

// Debug hotspotProfiles
console.log('hotspotProfiles:', props.hotspotProfiles);

// Fallback data for testing card display
const fallbackProfiles = [
    { id: 1, name: 'Basic', price: '100', rate_limit: '10M/10M', session_timeout: '1d', shared_users: 1 },
    { id: 2, name: 'Pro', price: '200', rate_limit: '20M/20M', session_timeout: '7d', shared_users: 2 },
    { id: 3, name: 'Premium', price: '300', rate_limit: '50M/50M', session_timeout: '30d', shared_users: 3 },
];

// Sort profiles by price (lowest to highest)
const sortedProfiles = computed(() => {
    const profiles = props.hotspotProfiles.length > 0 ? props.hotspotProfiles : fallbackProfiles;
    return [...profiles].sort((a, b) => {
        const priceA = a.price ? parseFloat(a.price) : 0;
        const priceB = b.price ? parseFloat(b.price) : 0;
        return priceA - priceB;
    });
});

// Format speed for display
const formatSpeed = (rateLimit) => {
    if (!rateLimit) return 'Unlimited';
    return rateLimit.replace('/', ' / ');
};

// Format time for display
const formatTime = (time) => {
    if (!time) return 'Unlimited';
    return time
        .replace('d', ' days ')
        .replace('h', ' hours ')
        .replace('m', ' minutes')
        .trim();
};

// Selected package for login
const selectedPackage = ref(null);

// Form data
const username = ref('');
const password = ref('');
const loginError = ref('');

const selectPackage = (profile) => {
    selectedPackage.value = profile;
};

const login = () => {
    if (!username.value || !password.value) {
        loginError.value = 'Please enter both username and password';
        return;
    }
    if (!selectedPackage.value) {
        loginError.value = 'Please select a package';
        return;
    }
    loginError.value = '';
    alert(`Logging in with username: ${username.value} and package: ${selectedPackage.value.name}`);
};
</script>

<template>
    <Head title="WiFi Hotspot Portal" />

    <div class="min-h-screen bg-stone-50 dark:bg-slate-900 text-gray-900 dark:text-gray-100">
        <!-- Header -->
        <header class="sticky top-0 z-10 container mx-auto px-4 py-4 flex justify-between items-center bg-white/80 dark:bg-slate-800/80 backdrop-blur-md shadow-sm">
            <div class="flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mr-3 text-amber-500 dark:text-amber-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M5 12.55a11 11 0 0 1 14.08 0"></path>
                    <path d="M1.42 9a16 16 0 0 1 21.16 0"></path>
                    <path d="M8.53 16.11a6 6 0 0 1 6.95 0"></path>
                    <line x1="12" y1="20" x2="12.01" y2="20"></line>
                </svg>
                <h1 class="text-2xl font-bold">JTG Networks</h1>
            </div>
            <nav class="flex items-center space-x-6">
                <Link v-if="props.canLogin" :href="route('login')" class="text-gray-600 dark:text-gray-300 hover:text-amber-500 dark:hover:text-amber-400 transition duration-300 font-medium">Login</Link>
                <Link v-if="props.canRegister" :href="route('register')" class="bg-emerald-600 dark:bg-emerald-500 text-white px-4 py-2 rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition duration-300 font-medium">Register</Link>
                <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-amber-500 dark:hover:text-amber-400 transition duration-300">Help</a>
                <a href="#" class="text-gray-600 dark:text-gray-300 hover:text-amber-500 dark:hover:text-amber-400 transition duration-300">About</a>
            </nav>
        </header>

        <!-- Hero Section -->
        <section class="container mx-auto px-4 py-16 text-center bg-gradient-to-b from-stone-100 to-stone-50 dark:from-slate-800 dark:to-slate-900">
            <h2 class="text-4xl md:text-5xl font-extrabold mb-4 text-gray-900 dark:text-gray-100">Blazing-Fast WiFi Awaits</h2>
            <p class="text-xl md:text-2xl max-w-3xl mx-auto leading-relaxed text-gray-600 dark:text-gray-300">
                Connect with JTG Networks' premium internet packages. Choose your plan and experience seamless connectivity.
            </p>
        </section>

        <!-- Package Selection Section -->
        <section class="container mx-auto px-4 py-12">
            <h3 class="text-3xl font-bold mb-8 text-center text-gray-900 dark:text-gray-100">Explore Our Packages</h3>

            <!-- Packages Grid -->
            <div v-if="sortedProfiles.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <div 
                    v-for="profile in sortedProfiles" 
                    :key="profile.id"
                    class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg overflow-hidden text-emerald-500 dark:text-emerald-400 transition-all duration-300 hover:shadow-xl hover:scale-105 cursor-pointer"
                    :class="{ 'ring-4 ring-black-500 dark:ring-white-400': selectedPackage && selectedPackage.id === profile.id }"
                    @click="selectPackage(profile)"
                >
                    <div class="p-6">
                        <h4 class="text-xl font-semibold mb-3 text-gray-900 dark:text-gray-100">{{ profile.name }}</h4>
                        <div class="mb-4">
                            <span class="text-3xl font-bold text-grey-500 dark:text-grey-400">
                                {{ profile.price ? 'Kes ' + profile.price : 'Free' }}
                            </span>
                        </div>
                        <ul class="space-y-3 mb-6">
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-emerald-500 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">Speed: {{ formatSpeed(profile.rate_limit) }}</span>
                            </li>
                            <li class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-emerald-500 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">Time Limit: {{ formatTime(profile.session_timeout) }}</span>
                            </li>
                            <li v-if="profile.shared_users" class="flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2 text-emerald-500 dark:text-emerald-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                                <span class="text-gray-600 dark:text-gray-300">{{ profile.shared_users }} Simultaneous Devices</span>
                            </li>
                        </ul>
                        <button 
                            class="w-full py-2 px-4 bg-blue-600 dark:bg-blue-500 text-white rounded-lg hover:bg-blue-700 dark:hover:bg-blue-600 transition duration-300 font-medium"
                            @click.stop="selectPackage(profile)"
                        >
                            Select Package
                        </button>
                    </div>
                </div>
            </div>

            <!-- No Packages Message -->
            <div v-else class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-md rounded-xl shadow-lg p-8 text-center animate-pulse">
                <p class="text-xl text-gray-600 dark:text-gray-300">No packages available at the moment. Please try again later.</p>
            </div>
        </section>

        <!-- Login Form -->
        <section class="container mx-auto px-4 py-12">
            <div class="bg-white/80 dark:bg-slate-800/80 backdrop-blur-md rounded-2xl shadow-lg p-8 max-w-md mx-auto transition-all duration-300 hover:shadow-xl">
                <h3 class="text-2xl font-bold mb-6 text-center text-gray-900 dark:text-gray-100">Login to Connect</h3>
                <div v-if="selectedPackage" class="mb-4 p-3 bg-amber-100 dark:bg-amber-900/50 rounded-lg text-center">
                    Selected Package: <strong>{{ selectedPackage.name }}</strong>
                    <div v-if="selectedPackage.price" class="font-semibold text-amber-500 dark:text-amber-400">
                        Kes {{ selectedPackage.price }}
                    </div>
                </div>
                <form @submit.prevent="login" class="space-y-4">
                    <div>
                        <label for="username" class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Username</label>
                        <input 
                            id="username" 
                            v-model="username" 
                            type="text" 
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 focus:border-amber-500 dark:focus:border-amber-400 focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 focus:outline-none transition duration-300"
                            placeholder="Enter your username"
                        >
                    </div>
                    <div>
                        <label for="password" class="block mb-1 font-medium text-gray-700 dark:text-gray-200">Password</label>
                        <input 
                            id="password" 
                            v-model="password" 
                            type="password" 
                            class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-slate-600 focus:border-amber-500 dark:focus:border-amber-400 focus:ring-2 focus:ring-amber-500 dark:focus:ring-amber-400 focus:outline-none transition duration-300"
                            placeholder="Enter your password"
                        >
                    </div>
                    <div v-if="loginError" class="p-3 bg-red-100 dark:bg-red-900/50 text-red-700 dark:text-red-300 rounded-lg text-center transition-all duration-300 animate-pulse">
                        {{ loginError }}
                    </div>
                    <button 
                        type="submit" 
                        class="w-full py-3 px-4 bg-emerald-600 dark:bg-emerald-500 text-white rounded-lg hover:bg-emerald-700 dark:hover:bg-emerald-600 transition duration-300 font-semibold"
                    >
                        Connect Now
                    </button>
                </form>
            </div>
        </section>

        <!-- Footer -->
        <footer class="container mx-auto px-4 py-8 text-center text-gray-600 dark:text-gray-400 bg-stone-100 dark:bg-slate-800">
            <p>© {{ new Date().getFullYear() }} JTG Networks. All rights reserved.</p>
            <p class="mt-2">For assistance, contact <a href="mailto:support@jtgnetworks.com" class="text-amber-500 dark:text-amber-400 hover:underline">support@jtgnetworks.com</a></p>
        </footer>
    </div>
</template>

<style scoped>
/* Card transition animations */
.card-enter-active,
.card-leave-active {
    transition: all 0.5s ease;
}
.card-enter-from,
.card-leave-to {
    opacity: 0;
    transform: translateY(20px);
}
</style>