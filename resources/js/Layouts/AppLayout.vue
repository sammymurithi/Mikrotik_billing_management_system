<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

// Import FontAwesome components
import { library } from '@fortawesome/fontawesome-svg-core';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
    faHome, 
    faUsers, 
    faNetworkWired, 
    faWifi, 
    faBoxes, 
    faTachometerAlt,
    faUserCircle,
    faSignOutAlt,
    faBars,
    faKey,
    faTicketAlt,
    faArrowLeft,
    faDownload,
    faCheck,
    faTrash,
    faFile,
    faFilePdf,
    faFilter
} from '@fortawesome/free-solid-svg-icons';

// Add icons to the library
library.add(
    faHome, 
    faUsers, 
    faNetworkWired, 
    faWifi, 
    faBoxes, 
    faTachometerAlt,
    faUserCircle,
    faSignOutAlt,
    faBars,
    faKey,
    faTicketAlt,
    faArrowLeft,
    faDownload,
    faCheck,
    faTrash,
    faFile,
    faFilePdf,
    faFilter
);

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div class="flex h-screen">
        <Head :title="title" />

        <Banner />

        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 z-30 w-64 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 shadow-lg overflow-y-auto">
            <!-- Logo -->
            <div class="flex items-center justify-center h-16 border-b border-gray-200 dark:border-gray-700">
                <Link :href="route('dashboard')" class="flex items-center">
                    <ApplicationMark class="block h-9 w-auto mr-2" />
                    <span class="text-gray-800 dark:text-white font-semibold text-lg">MikroTik Manager</span>
                </Link>
            </div>
            
            <!-- Navigation Links -->
            <nav class="mt-5 px-2">
                <Link 
                    :href="route('dashboard')" 
                    class="group flex items-center px-4 py-3 text-base font-medium rounded-md transition-colors"
                    :class="route().current('dashboard') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <font-awesome-icon 
                        icon="fa-tachometer-alt" 
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="route().current('dashboard') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                    />
                    Dashboard
                </Link>
                
                <Link 
                    :href="route('users.index')" 
                    class="mt-1 group flex items-center px-4 py-3 text-base font-medium rounded-md transition-colors"
                    :class="route().current('users.*') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <font-awesome-icon 
                        icon="fa-users" 
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="route().current('users.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                    />
                    System Users
                </Link>
                
                <Link 
                    :href="route('routers.index')" 
                    class="mt-1 group flex items-center px-4 py-3 text-base font-medium rounded-md transition-colors"
                    :class="route().current('routers.*') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <font-awesome-icon 
                        icon="fa-network-wired" 
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="route().current('routers.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                    />
                    Routers
                </Link>
                
                <Link 
                    :href="route('hotspot.users.index')" 
                    class="mt-1 group flex items-center px-4 py-3 text-base font-medium rounded-md transition-colors"
                    :class="route().current('hotspot.users.*') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <font-awesome-icon 
                        icon="fa-wifi" 
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="route().current('hotspot.users.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                    />
                    Hotspot Users
                </Link>
                
                <Link 
                    :href="route('hotspot.profiles.index')" 
                    class="mt-1 group flex items-center px-4 py-3 text-base font-medium rounded-md transition-colors"
                    :class="route().current('hotspot.profiles.*') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <font-awesome-icon 
                        icon="fa-boxes" 
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="route().current('hotspot.profiles.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                    />
                    Packages
                </Link>
                
                <Link 
                    :href="route('tickets.index')" 
                    class="mt-1 group flex items-center px-4 py-3 text-base font-medium rounded-md transition-colors"
                    :class="route().current('tickets.*') ? 'bg-gray-100 dark:bg-gray-700 text-indigo-600 dark:text-indigo-400' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'"
                >
                    <font-awesome-icon 
                        icon="fa-ticket-alt" 
                        class="mr-3 h-5 w-5 flex-shrink-0"
                        :class="route().current('tickets.*') ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-500 dark:text-gray-400 group-hover:text-gray-600 dark:group-hover:text-gray-300'"
                    />
                    Support Tickets
                </Link>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64 bg-gray-100 dark:bg-gray-900">
            <!-- Top Navigation Bar -->
            <nav class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-end h-16">
                        <!-- Mobile menu button -->
                        <div class="flex items-center sm:hidden">
                            <button @click="showingNavigationDropdown = !showingNavigationDropdown" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                                <font-awesome-icon icon="fa-bars" class="h-6 w-6" />
                            </button>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="size-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        </button>

                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:bg-gray-50 dark:focus:bg-gray-700 active:bg-gray-50 dark:active:bg-gray-700 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}

                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>

                                    <template #content>
                                        <!-- Account Management -->
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Account
                                        </div>

                                        <DropdownLink :href="route('profile.show')">
                                            Profile
                                        </DropdownLink>

                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                            API Tokens
                                        </DropdownLink>

                                        <div class="border-t border-gray-200 dark:border-gray-600" />

                                        <!-- Authentication -->
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Log Out
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg
                                    class="size-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="fixed inset-0 z-40 sm:hidden">
                    <!-- Overlay -->
                    <div class="fixed inset-0 bg-gray-600 bg-opacity-75" @click="showingNavigationDropdown = false"></div>
                    
                    <!-- Mobile menu -->
                    <div class="fixed inset-y-0 left-0 max-w-xs w-full bg-white dark:bg-gray-800 overflow-y-auto">
                        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-200 dark:border-gray-700">
                            <Link :href="route('dashboard')" class="flex items-center" @click="showingNavigationDropdown = false">
                                <ApplicationMark class="block h-8 w-auto mr-2" />
                                <span class="text-gray-800 dark:text-white font-semibold text-lg">MikroTik Manager</span>
                            </Link>
                            <button @click="showingNavigationDropdown = false" class="text-gray-500 hover:text-gray-600 focus:outline-none">
                                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                        
                        <div class="pt-2 pb-3 space-y-1 px-2">
                            <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')" class="flex items-center">
                                <font-awesome-icon icon="fa-tachometer-alt" class="mr-3 h-5 w-5" />
                                Dashboard
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('users.index')" :active="route().current('users.*')" class="flex items-center">
                                <font-awesome-icon icon="fa-users" class="mr-3 h-5 w-5" />
                                System Users
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('routers.index')" :active="route().current('routers.*')" class="flex items-center">
                                <font-awesome-icon icon="fa-network-wired" class="mr-3 h-5 w-5" />
                                Routers
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('hotspot.users.index')" :active="route().current('hotspot.users.*')" class="flex items-center">
                                <font-awesome-icon icon="fa-wifi" class="mr-3 h-5 w-5" />
                                Hotspot Users
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('hotspot.profiles.index')" :active="route().current('hotspot.profiles.*')" class="flex items-center">
                                <font-awesome-icon icon="fa-boxes" class="mr-3 h-5 w-5" />
                                Packages
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('tickets.index')" :active="route().current('tickets.*')" class="flex items-center">
                                <font-awesome-icon icon="fa-ticket-alt" class="mr-3 h-5 w-5" />
                                Support Tickets
                            </ResponsiveNavLink>
                        </div>

                        <!-- Responsive Settings Options -->
                        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
                            <div class="flex items-center px-4">
                                <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                    <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                </div>

                                <div>
                                    <div class="font-medium text-base text-gray-800 dark:text-gray-200">
                                        {{ $page.props.auth.user.name }}
                                    </div>
                                    <div class="font-medium text-sm text-gray-500">
                                        {{ $page.props.auth.user.email }}
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 space-y-1 px-2">
                                <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')" class="flex items-center">
                                    <font-awesome-icon icon="fa-user-circle" class="mr-3 h-5 w-5" />
                                    Profile
                                </ResponsiveNavLink>

                                <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')" class="flex items-center">
                                    <font-awesome-icon icon="fa-key" class="mr-3 h-5 w-5" />
                                    API Tokens
                                </ResponsiveNavLink>

                                <!-- Authentication -->
                                <form method="POST" @submit.prevent="logout">
                                    <ResponsiveNavLink as="button" class="flex items-center">
                                        <font-awesome-icon icon="fa-sign-out-alt" class="mr-3 h-5 w-5" />
                                        Log Out
                                    </ResponsiveNavLink>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white dark:bg-gray-800 shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 overflow-y-auto py-6 bg-gray-100 dark:bg-gray-900">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
