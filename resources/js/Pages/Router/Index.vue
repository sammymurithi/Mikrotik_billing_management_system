<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { ref, onMounted } from 'vue';

const props = defineProps({
    routers: {
        type: Array,
        required: true
    }
});

const routerStatuses = ref({});
const expandedRouter = ref(null);
const expandedTab = ref('interfaces');
const alert = ref({
    show: false,
    type: 'success',
    message: ''
});

const formData = ref({});

const selectedRouter = ref(null);
const selectedRouterData = ref({
    interfaces: [],
    ipAddresses: [],
    dhcpClients: [],
    dhcpServers: [],
    dnsSettings: null,
    firewallRules: [],
    hotspotSettings: null,
    pools: []
});
const showNewInterfaceForm = ref(false);

const resetPassword = ref('');
const showResetModal = ref(false);
const resetRouterId = ref(null);

// MikroTik interface types
const interfaceTypes = [
    { value: 'ether', label: 'Ethernet' },
    { value: 'wlan', label: 'Wireless' },
    { value: 'bridge', label: 'Bridge' },
    { value: 'vlan', label: 'VLAN' },
    { value: 'pppoe', label: 'PPPoE' },
    { value: 'pptp', label: 'PPTP' },
    { value: 'l2tp', label: 'L2TP' },
    { value: 'sstp', label: 'SSTP' },
    { value: 'ovpn', label: 'OpenVPN' },
    { value: 'gre', label: 'GRE' },
    { value: 'ipip', label: 'IPIP' },
    { value: 'eoip', label: 'EoIP' },
    { value: 'ipsec', label: 'IPSec' },
    { value: 'bonding', label: 'Bonding' },
    { value: 'vrrp', label: 'VRRP' },
    { value: 'vxlan', label: 'VXLAN' }
];

const deleteRouter = (routerId) => {
    if (confirm('Are you sure you want to delete this router?')) {
        router.delete(route('routers.destroy', routerId), {
            preserveScroll: true,
            onSuccess: () => {
                const index = props.routers.findIndex(r => r.id === routerId);
                if (index !== -1) {
                    props.routers.splice(index, 1);
                }
            }
        });
    }
};

const checkRouterConnection = async (routerId) => {
    try {
        const response = await fetch(route('routers.check-connection', routerId));
        const data = await response.json();
        routerStatuses.value[routerId] = {
            connected: data.connected,
            message: data.message
        };
    } catch (error) {
        routerStatuses.value[routerId] = {
            connected: false,
            message: 'Error checking connection'
        };
    }
};

const checkAllRouters = () => {
    props.routers.forEach(router => {
        checkRouterConnection(router.id);
    });
};

// Initialize form data for each router
const initializeFormData = (routerId) => {
    const router = props.routers.find(r => r.id === routerId);
    if (!router) return;

    formData.value[routerId] = {
        interfaces: {
            type: '',
            name: '',
            enabled: true
        },
        addresses: {
            interface: '',
            address: '',
            network: ''
        },
        dhcpClient: {
            interface: '',
            enabled: true,
            use_peer_dns: true,
            add_default_route: true
        },
        dhcpServer: {
            interface: '',
            addressPool: '',
            gateway: '',
            dnsServers: '',
            leaseTime: '1d'
        },
        dnsSettings: {
            servers: router.dnsSettings?.servers || '',
            allowRemoteRequests: router.dnsSettings?.['allow-remote-requests'] === 'yes'
        },
        firewall: {
            chain: 'forward',
            action: 'accept',
            src_address: '',
            dst_address: '',
            protocol: 'tcp',
            comment: ''
        },
        hotspotServer: {
            interface: '',
            addressPool: '',
            dnsName: '',
            sslCertificate: '',
            useDefaultLoginPage: true
        },
        pool: {
            name: '',
            ranges: '',
            next_pool: ''
        }
    };

    // Prefill DHCP client settings if they exist
    if (router.dhcpClients && router.dhcpClients.length > 0) {
        const dhcpClient = router.dhcpClients[0];
        formData.value[routerId].dhcpClient = {
            interface: dhcpClient.interface || '',
            enabled: dhcpClient.disabled !== 'true',
            use_peer_dns: dhcpClient['use-peer-dns'] === 'yes',
            add_default_route: dhcpClient['add-default-route'] === 'yes'
        };
    }

    // Prefill DHCP server settings if they exist
    if (router.dhcpServers && router.dhcpServers.length > 0) {
        const dhcpServer = router.dhcpServers[0];
        formData.value[routerId].dhcpServer = {
            interface: dhcpServer.interface || '',
            addressPool: dhcpServer['address-pool'] || '',
            gateway: dhcpServer.gateway || '',
            dnsServers: dhcpServer['dns-server'] || '',
            leaseTime: dhcpServer['lease-time'] || '1d'
        };
    }

    // Prefill hotspot settings if they exist
    if (router.hotspotSettings) {
        formData.value[routerId].hotspotServer = {
            interface: router.hotspotSettings.interface || '',
            addressPool: router.hotspotSettings['address-pool'] || '',
            dnsName: router.hotspotSettings['dns-name'] || '',
            sslCertificate: router.hotspotSettings['ssl-certificate'] || '',
            useDefaultLoginPage: router.hotspotSettings['use-default-login-page'] === 'yes'
        };
    }

    // Prefill pool settings if they exist
    if (router.pools && router.pools.length > 0) {
        const pool = router.pools[0];
        formData.value[routerId].pool = {
            name: pool.name || '',
            ranges: pool.ranges || '',
            next_pool: pool['next-pool'] || ''
        };
    }
};

const selectRouter = async (routerId) => {
    selectedRouter.value = routerId;
    const router = props.routers.find(r => r.id === routerId);
    if (router) {
        selectedRouterData.value = {
            ...router,
            interfaces: router.interfaces || [],
            ipAddresses: router.ipAddresses || [],
            dhcpClients: router.dhcpClients || [],
            dhcpServers: router.dhcpServers || [],
            dnsSettings: router.dnsSettings || null,
            firewallRules: router.firewallRules || [],
            hotspotSettings: router.hotspotSettings || null,
            pools: router.pools || []
        };
        // Initialize form data for this router
        initializeFormData(routerId);
        // Set default tab to interfaces
        expandedTab.value = 'interfaces';
        // Check router connection
        checkRouterConnection(routerId);
    }
};

const onRouterSelect = (event) => {
    const routerId = event.target.value;
    if (routerId) {
        selectRouter(parseInt(routerId));
    } else {
        selectedRouter.value = null;
        selectedRouterData.value = {
            interfaces: [],
            ipAddresses: [],
            dhcpClients: [],
            dhcpServers: [],
            dnsSettings: null,
            firewallRules: [],
            hotspotSettings: null,
            pools: []
        };
        expandedTab.value = null;
    }
};

const toggleRouter = (routerId) => {
    expandedRouter.value = expandedRouter.value === routerId ? null : routerId;
    expandedTab.value = null;
    formData.value[routerId] = {
        interfaces: {
            selectedInterface: '',
            newName: '',
            enabled: true
        },
        ipAddresses: {
            interface: '',
            address: '',
            network: ''
        },
        dhcpServer: {
            interface: '',
            addressPool: '',
            gateway: '',
            dnsServers: '',
            leaseTime: '1d'
        },
        dnsSettings: {
            servers: '',
            allowRemoteRequests: false
        },
        hotspotServer: {
            interface: '',
            addressPool: '',
            dnsName: '',
            sslCertificate: '',
            useDefaultLoginPage: true
        }
    };
};

const toggleTab = (tab) => {
    expandedTab.value = expandedTab.value === tab ? null : tab;
};

const showAlert = (type, message) => {
    alert.value = {
        show: true,
        type,
        message
    };
    setTimeout(() => {
        alert.value.show = false;
    }, 5000);
};

const resetForm = (routerId, tab) => {
    formData.value[routerId][tab] = {
        ...formData.value[routerId][tab],
        ...getDefaultValues(tab)
    };
};

const getDefaultValues = (tab) => {
    const defaults = {
        interfaces: {
            selectedInterface: '',
            newName: '',
            enabled: true
        },
        ipAddresses: {
            interface: '',
            address: '',
            network: ''
        },
        dhcpServer: {
            interface: '',
            addressPool: '',
            gateway: '',
            dnsServers: '',
            leaseTime: '1d'
        },
        dnsSettings: {
            servers: '',
            allowRemoteRequests: false
        },
        hotspotServer: {
            interface: '',
            addressPool: '',
            dnsName: '',
            sslCertificate: '',
            useDefaultLoginPage: true
        }
    };
    return defaults[tab];
};

const applyChanges = async (routerId, tab) => {
    try {
        const response = await fetch(route('routers.configure', {
            router: routerId,
            tab,
            data: formData.value[routerId][tab]
        }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(formData.value[routerId][tab])
        });

        const data = await response.json();
        
        if (data.success) {
            showAlert('success', 'Changes applied successfully');
        } else {
            showAlert('error', data.message || 'Failed to apply changes');
        }
    } catch (error) {
        showAlert('error', 'An error occurred while applying changes');
    }
};

const toggleInterfaceStatus = async (iface) => {
    try {
        const response = await fetch(route('routers.configure', {
            router: selectedRouter.value,
            tab: 'interfaces',
            action: iface.running ? 'disable' : 'enable',
            interface: iface['.id']
        }), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });

        const data = await response.json();
        if (data.success) {
            showAlert('success', `Interface ${iface.running ? 'disabled' : 'enabled'} successfully`);
            // Refresh router data
            checkRouterConnection(selectedRouter.value);
        } else {
            showAlert('error', data.message || 'Failed to toggle interface status');
        }
    } catch (error) {
        showAlert('error', 'An error occurred while toggling interface status');
    }
};

const editInterface = (iface) => {
    formData.value[selectedRouter.value].interfaces = {
        selectedInterface: iface['.id'],
        newName: iface.name,
        enabled: iface.running,
        type: iface.type
    };
    showNewInterfaceForm.value = true;
};

const deleteInterface = async (iface) => {
    if (confirm('Are you sure you want to delete this interface?')) {
        try {
            const response = await fetch(route('routers.configure', {
                router: selectedRouter.value,
                tab: 'interfaces',
                action: 'delete',
                interface: iface['.id']
            }), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();
            if (data.success) {
                showAlert('success', 'Interface deleted successfully');
                // Refresh router data
                checkRouterConnection(selectedRouter.value);
            } else {
                showAlert('error', data.message || 'Failed to delete interface');
            }
        } catch (error) {
            showAlert('error', 'An error occurred while deleting interface');
        }
    }
};

const confirmResetRouter = (routerId) => {
    resetRouterId.value = routerId;
    showResetModal.value = true;
};

const resetRouterConfiguration = async () => {
    if (!resetPassword.value) {
        showAlert('error', 'Please enter your password');
        return;
    }

    try {
        const response = await fetch(route('routers.reset-configuration', resetRouterId.value), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                password: resetPassword.value
            })
        });

        const data = await response.json();
        if (data.success) {
            showAlert('success', data.message);
            // Refresh router data
            selectRouter(resetRouterId.value);
        } else {
            showAlert('error', data.message || 'Failed to reset router configuration');
        }
    } catch (error) {
        showAlert('error', 'An error occurred while resetting router configuration');
    } finally {
        // Reset the form and close modal
        resetPassword.value = '';
        showResetModal.value = false;
        resetRouterId.value = null;
    }
};

onMounted(() => {
    checkAllRouters();
});
</script>

<template>
    <Head title="Routers" />

    <AppLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                        Router Management
                    </h2>
                    <div class="flex items-center space-x-2">
                        <div class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></div>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Live Status</span>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center space-x-2">
                        <InputLabel for="selectedRouter" value="Select Router to Configure" class="text-sm" />
                        <select
                            id="selectedRouter"
                            v-model="selectedRouter"
                            @change="onRouterSelect"
                            class="mt-1 block w-64 border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                        >
                            <option value="">Select a router</option>
                            <option v-for="router in routers" :key="router.id" :value="router.id">
                                {{ router.name }} ({{ router.ip_address }})
                            </option>
                        </select>
                    </div>
                    <PrimaryButton @click="checkAllRouters" class="flex items-center space-x-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Test All Connections</span>
                    </PrimaryButton>
                    <PrimaryButton 
                        v-if="selectedRouter"
                        @click="confirmResetRouter(selectedRouter)" 
                        class="flex items-center space-x-2 bg-red-600 hover:bg-red-700"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <span>Reset Router</span>
                    </PrimaryButton>
                    <Link
                        :href="route('routers.create')"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Router
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">


                <!-- Configuration Panel -->
                <div v-if="selectedRouter" class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <div class="border-b border-gray-200 dark:border-gray-600">
                            <nav class="flex -mb-px space-x-8">
                                <button
                                    v-for="tab in ['interfaces', 'addresses', 'dhcp-client', 'dhcp-server', 'dns-settings', 'firewall', 'hotspot-server', 'pool']"
                                    :key="tab"
                                    @click="toggleTab(tab)"
                                    :class="{
                                        'border-indigo-500 text-indigo-600 dark:text-indigo-400': expandedTab === tab,
                                        'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:border-gray-300 dark:hover:border-gray-700': expandedTab !== tab
                                    }"
                                    class="group inline-flex items-center py-4 px-1 border-b-2 font-medium text-sm"
                                >
                                    <svg v-if="tab === 'interfaces'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                    </svg>
                                    <svg v-else-if="tab === 'addresses'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <svg v-else-if="tab === 'dhcp-client'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <svg v-else-if="tab === 'dhcp-server'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                                    </svg>
                                    <svg v-else-if="tab === 'dns-settings'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                    </svg>
                                    <svg v-else-if="tab === 'firewall'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <svg v-else-if="tab === 'hotspot-server'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <svg v-else-if="tab === 'pool'" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                    {{ tab.split('-').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ') }}
                                </button>
                            </nav>
                        </div>

                        <!-- Tab Content -->
                        <div class="mt-6">
                            <!-- Interfaces Tab -->
                            <div v-if="expandedTab === 'interfaces'" class="space-y-6">
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center space-x-2">
                                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                        </svg>
                                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Network Interfaces</h3>
                                    </div>
                                    <PrimaryButton @click="showNewInterfaceForm = !showNewInterfaceForm" class="flex items-center space-x-2">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                        </svg>
                                        <span>{{ showNewInterfaceForm ? 'Cancel' : 'Add New Interface' }}</span>
                                    </PrimaryButton>
                                </div>

                                <!-- Interface List -->
                                <div v-if="selectedRouterData.interfaces.length > 0" class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                        <thead class="bg-gray-50 dark:bg-gray-800">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                                            <tr v-for="iface in selectedRouterData.interfaces" :key="iface['.id']" class="hover:bg-gray-50 dark:hover:bg-gray-800">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <svg class="w-5 h-5 mr-2 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                                        </svg>
                                                        <span class="font-medium text-gray-900 dark:text-gray-100">{{ iface.name }}</span>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                                        {{ iface.type }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span :class="{
                                                        'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300': iface.running,
                                                        'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300': !iface.running
                                                    }" class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full">
                                                        {{ iface.running ? 'Running' : 'Disabled' }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                    <div class="flex space-x-2">
                                                        <button
                                                            @click="toggleInterfaceStatus(iface)"
                                                            class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 flex items-center"
                                                        >
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            {{ iface.running ? 'Disable' : 'Enable' }}
                                                        </button>
                                                        <button
                                                            @click="editInterface(iface)"
                                                            class="text-yellow-600 dark:text-yellow-400 hover:text-yellow-900 dark:hover:text-yellow-300 flex items-center"
                                                        >
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                            </svg>
                                                            Edit
                                                        </button>
                                                        <button
                                                            @click="deleteInterface(iface)"
                                                            class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 flex items-center"
                                                        >
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                            </svg>
                                                            Delete
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div v-else class="text-center text-gray-500 dark:text-gray-400 py-4">
                                    No interfaces found
                                </div>

                                <!-- New/Edit Interface Form -->
                                <div v-if="showNewInterfaceForm" class="mt-6 space-y-6 bg-gray-50 dark:bg-gray-800 p-6 rounded-lg">
                                    <div class="grid grid-cols-1 gap-6">
                                        <div>
                                            <InputLabel for="interfaceType" value="Interface Type" class="text-gray-700 dark:text-gray-300" />
                                            <select
                                                id="interfaceType"
                                                v-model="formData[selectedRouter].interfaces.type"
                                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                            >
                                                <option value="">Select type</option>
                                                <option v-for="type in interfaceTypes" :key="type.value" :value="type.value">
                                                    {{ type.label }}
                                                </option>
                                            </select>
                                        </div>

                                        <div>
                                            <InputLabel for="interfaceName" value="Interface Name" class="text-gray-700 dark:text-gray-300" />
                                            <TextInput
                                                id="interfaceName"
                                                v-model="formData[selectedRouter].interfaces.name"
                                                type="text"
                                                class="mt-1 block w-full"
                                                placeholder="e.g., ether1"
                                            />
                                        </div>

                                        <div class="flex items-center">
                                            <input
                                                id="enabled"
                                                v-model="formData[selectedRouter].interfaces.enabled"
                                                type="checkbox"
                                                class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                            />
                                            <InputLabel for="enabled" value="Enable Interface" class="ml-2 text-gray-700 dark:text-gray-300" />
                                        </div>

                                        <div class="flex justify-end space-x-4">
                                            <PrimaryButton @click="resetForm(selectedRouter, 'interfaces')" class="flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Reset</span>
                                            </PrimaryButton>
                                            <PrimaryButton @click="applyChanges(selectedRouter, 'interfaces')" class="flex items-center space-x-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                                <span>{{ formData[selectedRouter].interfaces.selectedInterface ? 'Update' : 'Create' }} Interface</span>
                                            </PrimaryButton>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- IP Addresses Tab -->
                            <div v-if="expandedTab === 'addresses'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="ipInterface" value="Select Interface" />
                                        <select
                                            id="ipInterface"
                                            v-model="formData[selectedRouter].addresses.interface"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select an interface</option>
                                            <option v-for="iface in selectedRouterData.interfaces" :key="iface['.id']" :value="iface['.id']">
                                                {{ iface.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="address" value="IP Address" />
                                        <TextInput
                                            id="address"
                                            v-model="formData[selectedRouter].addresses.address"
                                            type="text"
                                            placeholder="e.g., 192.168.88.1/24"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'addresses')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'addresses')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- DHCP Client Tab -->
                            <div v-if="expandedTab === 'dhcp-client'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="dhcpClientInterface" value="Select Interface" />
                                        <select
                                            id="dhcpClientInterface"
                                            v-model="formData[selectedRouter].dhcpClient.interface"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select an interface</option>
                                            <option v-for="iface in selectedRouterData.interfaces" :key="iface['.id']" :value="iface['.id']">
                                                {{ iface.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="usePeerDns" value="Use Peer DNS" />
                                        <input
                                            id="usePeerDns"
                                            v-model="formData[selectedRouter].dhcpClient.use_peer_dns"
                                            type="checkbox"
                                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="addDefaultRoute" value="Add Default Route" />
                                        <input
                                            id="addDefaultRoute"
                                            v-model="formData[selectedRouter].dhcpClient.add_default_route"
                                            type="checkbox"
                                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'dhcpClient')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'dhcpClient')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- DHCP Server Tab -->
                            <div v-if="expandedTab === 'dhcp-server'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="dhcpInterface" value="Select Interface" />
                                        <select
                                            id="dhcpInterface"
                                            v-model="formData[selectedRouter].dhcpServer.interface"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select an interface</option>
                                            <option v-for="iface in selectedRouterData.interfaces" :key="iface['.id']" :value="iface['.id']">
                                                {{ iface.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="addressPool" value="Address Pool Range" />
                                        <TextInput
                                            id="addressPool"
                                            v-model="formData[selectedRouter].dhcpServer.addressPool"
                                            type="text"
                                            placeholder="e.g., 192.168.88.2-192.168.88.254"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="gateway" value="Gateway" />
                                        <TextInput
                                            id="gateway"
                                            v-model="formData[selectedRouter].dhcpServer.gateway"
                                            type="text"
                                            placeholder="e.g., 192.168.88.1"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="dnsServers" value="DNS Servers" />
                                        <TextInput
                                            id="dnsServers"
                                            v-model="formData[selectedRouter].dhcpServer.dnsServers"
                                            type="text"
                                            placeholder="e.g., 8.8.8.8,8.8.4.4"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="leaseTime" value="Lease Time" />
                                        <TextInput
                                            id="leaseTime"
                                            v-model="formData[selectedRouter].dhcpServer.leaseTime"
                                            type="text"
                                            placeholder="e.g., 1d"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'dhcpServer')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'dhcpServer')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- DNS Settings Tab -->
                            <div v-if="expandedTab === 'dns-settings'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="dnsServers" value="DNS Servers" />
                                        <TextInput
                                            id="dnsServers"
                                            v-model="formData[selectedRouter].dnsSettings.servers"
                                            type="text"
                                            placeholder="e.g., 8.8.8.8,8.8.4.4"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            id="allowRemoteRequests"
                                            v-model="formData[selectedRouter].dnsSettings.allowRemoteRequests"
                                            type="checkbox"
                                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                        />
                                        <InputLabel for="allowRemoteRequests" value="Allow Remote Requests" class="ml-2" />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'dnsSettings')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'dnsSettings')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- Firewall Tab -->
                            <div v-if="expandedTab === 'firewall'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="chain" value="Chain" />
                                        <select
                                            id="chain"
                                            v-model="formData[selectedRouter].firewall.chain"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select a chain</option>
                                            <option v-for="chain in ['forward', 'input', 'output']" :key="chain" :value="chain">
                                                {{ chain.charAt(0).toUpperCase() + chain.slice(1) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="action" value="Action" />
                                        <select
                                            id="action"
                                            v-model="formData[selectedRouter].firewall.action"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select an action</option>
                                            <option v-for="action in ['accept', 'drop', 'reject']" :key="action" :value="action">
                                                {{ action.charAt(0).toUpperCase() + action.slice(1) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="srcAddress" value="Source Address" />
                                        <TextInput
                                            id="srcAddress"
                                            v-model="formData[selectedRouter].firewall.src_address"
                                            type="text"
                                            placeholder="e.g., 192.168.88.0/24"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="dstAddress" value="Destination Address" />
                                        <TextInput
                                            id="dstAddress"
                                            v-model="formData[selectedRouter].firewall.dst_address"
                                            type="text"
                                            placeholder="e.g., 192.168.88.0/24"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="protocol" value="Protocol" />
                                        <select
                                            id="protocol"
                                            v-model="formData[selectedRouter].firewall.protocol"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select a protocol</option>
                                            <option v-for="protocol in ['tcp', 'udp', 'icmp']" :key="protocol" :value="protocol">
                                                {{ protocol.charAt(0).toUpperCase() + protocol.slice(1) }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="comment" value="Comment" />
                                        <TextInput
                                            id="comment"
                                            v-model="formData[selectedRouter].firewall.comment"
                                            type="text"
                                            placeholder="e.g., Allow SSH traffic"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'firewall')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'firewall')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- Hotspot Server Tab -->
                            <div v-if="expandedTab === 'hotspot-server'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="hotspotInterface" value="Select Interface" />
                                        <select
                                            id="hotspotInterface"
                                            v-model="formData[selectedRouter].hotspotServer.interface"
                                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
                                        >
                                            <option value="">Select an interface</option>
                                            <option v-for="iface in selectedRouterData.interfaces" :key="iface['.id']" :value="iface['.id']">
                                                {{ iface.name }}
                                            </option>
                                        </select>
                                    </div>

                                    <div>
                                        <InputLabel for="hotspotAddressPool" value="Hotspot Address Pool" />
                                        <TextInput
                                            id="hotspotAddressPool"
                                            v-model="formData[selectedRouter].hotspotServer.addressPool"
                                            type="text"
                                            placeholder="e.g., 192.168.88.2-192.168.88.254"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="dnsName" value="Hotspot DNS Name" />
                                        <TextInput
                                            id="dnsName"
                                            v-model="formData[selectedRouter].hotspotServer.dnsName"
                                            type="text"
                                            placeholder="e.g., hotspot.example.com"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="sslCertificate" value="SSL Certificate (Optional)" />
                                        <TextInput
                                            id="sslCertificate"
                                            v-model="formData[selectedRouter].hotspotServer.sslCertificate"
                                            type="text"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div class="flex items-center">
                                        <input
                                            id="useDefaultLoginPage"
                                            v-model="formData[selectedRouter].hotspotServer.useDefaultLoginPage"
                                            type="checkbox"
                                            class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600"
                                        />
                                        <InputLabel for="useDefaultLoginPage" value="Use Default Login Page" class="ml-2" />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'hotspotServer')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'hotspotServer')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>

                            <!-- Pool Tab -->
                            <div v-if="expandedTab === 'pool'" class="space-y-6">
                                <div class="grid grid-cols-1 gap-6">
                                    <div>
                                        <InputLabel for="poolName" value="Pool Name" />
                                        <TextInput
                                            id="poolName"
                                            v-model="formData[selectedRouter].pool.name"
                                            type="text"
                                            placeholder="e.g., Office Network"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="ranges" value="Ranges" />
                                        <TextInput
                                            id="ranges"
                                            v-model="formData[selectedRouter].pool.ranges"
                                            type="text"
                                            placeholder="e.g., 192.168.88.2-192.168.88.254"
                                            class="mt-1 block w-full"
                                        />
                                    </div>

                                    <div>
                                        <InputLabel for="nextPool" value="Next Pool" />
                                        <TextInput
                                            id="nextPool"
                                            v-model="formData[selectedRouter].pool.next_pool"
                                            type="text"
                                            placeholder="e.g., 192.168.88.255"
                                            class="mt-1 block w-full"
                                        />
                                    </div>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <PrimaryButton @click="resetForm(selectedRouter, 'pool')">
                                        Reset
                                    </PrimaryButton>
                                    <PrimaryButton @click="applyChanges(selectedRouter, 'pool')">
                                        Apply Changes
                                    </PrimaryButton>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Routers Table -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <div class="flex items-center space-x-2 mb-4">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <h3 class="text-lg font-medium">Router List</h3>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Address</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    <tr v-for="router in routers" :key="router.id" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <svg class="w-5 h-5 mr-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
                                                </svg>
                                                <span class="font-medium">{{ router.name }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-gray-500 dark:text-gray-400">{{ router.ip_address }}</span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div
                                                    :class="{
                                                        'bg-green-500': routerStatuses[router.id]?.connected,
                                                        'bg-red-500': !routerStatuses[router.id]?.connected,
                                                        'animate-pulse': !routerStatuses[router.id]
                                                    }"
                                                    class="w-3 h-3 rounded-full mr-2"
                                                ></div>
                                                <span class="text-sm">{{ routerStatuses[router.id]?.message || 'Checking...' }}</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-4">
                                                <button
                                                    @click="selectRouter(router.id)"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 flex items-center"
                                                >
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                    </svg>
                                                    Configure
                                                </button>
                                                <Link
                                                    :href="route('routers.edit', router.id)"
                                                    class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 flex items-center"
                                                >
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                    </svg>
                                                    Edit
                                                </Link>
                                                <button
                                                    @click.prevent="deleteRouter(router.id)"
                                                    class="text-red-600 dark:text-red-400 hover:text-red-900 dark:hover:text-red-300 flex items-center"
                                                >
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                    Delete
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>

    <!-- Alert Banner -->
    <div v-if="alert.show" 
         :class="{
             'bg-green-100 border-green-400 text-green-700': alert.type === 'success',
             'bg-red-100 border-red-400 text-red-700': alert.type === 'error',
             'bg-yellow-100 border-yellow-400 text-yellow-700': alert.type === 'warning'
         }"
         class="fixed top-4 right-4 px-4 py-3 rounded border z-50">
        {{ alert.message }}
    </div>

    <!-- Add the password confirmation modal -->
    <div v-if="showResetModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                Confirm Router Reset
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">
                This action will reset all DHCP, DNS, Firewall, Hotspot, and Pool settings to their default values. This action cannot be undone.
            </p>
            <div class="mb-4">
                <InputLabel for="resetPassword" value="Enter your password to confirm" />
                <TextInput
                    id="resetPassword"
                    v-model="resetPassword"
                    type="password"
                    class="mt-1 block w-full"
                    placeholder="Enter your password"
                />
            </div>
            <div class="flex justify-end space-x-4">
                <button
                    @click="showResetModal = false"
                    class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                >
                    Cancel
                </button>
                <button
                    @click="resetRouterConfiguration"
                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-md"
                >
                    Confirm Reset
                </button>
            </div>
        </div>
    </div>
</template> 