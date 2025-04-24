<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Line, Bar, Doughnut } from 'vue-chartjs';
import {
    Chart as ChartJS,
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    TimeScale,
} from 'chart.js';
import 'chartjs-adapter-date-fns';
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';
import { 
    faClock, 
    faMicrochip, 
    faServer, 
    faHardDrive, 
    faUsers, 
    faChartBar, 
    faChartPie, 
    faArrowUpRightDots, 
    faUser, 
    faShield, 
    faTriangleExclamation,
    faSync
} from '@fortawesome/free-solid-svg-icons';

// Register ChartJS components
ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    BarElement,
    Title,
    Tooltip,
    Legend,
    ArcElement,
    TimeScale
);

const props = defineProps({
    systemStats: {
        type: Object,
        required: true,
        default: () => ({
            uptime: 0,
            cpu_usage: 0,
            memory_usage: 0,
            disk_usage: 0,
            cpu_history: [],
            memory_history: [],
            traffic_history: []
        })
    },
    hotspotStats: {
        type: Object,
        required: true,
        default: () => ({
            active_users: 0,
            disabled_users: 0,
            expired_users: 0,
            active_sessions: [],
            peak_usage_time: null,
            most_used_profile: null
        })
    },
    routerStats: {
        type: Object,
        required: true,
        default: () => ({
            interface_traffic: [],
            dhcp_leases: {
                active: 0,
                expired: 0
            },
            bandwidth_history: []
        })
    },
    recentSessions: {
        type: Array,
        required: true,
        default: () => []
    },
    recentUsers: {
        type: Array,
        required: true,
        default: () => []
    },
});

const cpuChartData = ref({
    labels: [],
    datasets: [{
        label: 'CPU Usage (%)',
        data: [],
        borderColor: 'rgb(75, 192, 192)',
        tension: 0.1,
        fill: false,
    }],
});

const memoryChartData = ref({
    labels: [],
    datasets: [{
        label: 'Memory Usage (%)',
        data: [],
        borderColor: 'rgb(255, 99, 132)',
        tension: 0.1,
        fill: false,
    }],
});

const trafficChartData = ref({
    labels: [],
    datasets: [{
        label: 'Download (MB)',
        data: [],
        borderColor: 'rgb(54, 162, 235)',
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
    }, {
        label: 'Upload (MB)',
        data: [],
        borderColor: 'rgb(255, 159, 64)',
        backgroundColor: 'rgba(255, 159, 64, 0.5)',
    }],
});

const userDistributionChartData = ref({
    labels: ['Active', 'Disabled', 'Expired'],
    datasets: [{
        data: [0, 0, 0],
        backgroundColor: [
            'rgb(75, 192, 192)',
            'rgb(255, 99, 132)',
            'rgb(255, 205, 86)',
        ],
    }],
});

const interfaceTrafficChartData = ref({
    labels: [],
    datasets: [{
        label: 'Download (MB)',
        data: [],
        borderColor: 'rgb(54, 162, 235)',
        backgroundColor: 'rgba(54, 162, 235, 0.5)',
    }, {
        label: 'Upload (MB)',
        data: [],
        borderColor: 'rgb(255, 159, 64)',
        backgroundColor: 'rgba(255, 159, 64, 0.5)',
    }],
});

const dhcpLeasesChartData = ref({
    labels: ['Active', 'Expired'],
    datasets: [{
        data: [0, 0],
        backgroundColor: [
            'rgb(75, 192, 192)',
            'rgb(255, 99, 132)',
        ],
    }],
});

const bandwidthUsageChartData = ref({
    labels: [],
    datasets: [{
        label: 'Bandwidth Usage (Mbps)',
        data: [],
        borderColor: 'rgb(153, 102, 255)',
        tension: 0.1,
        fill: false,
    }],
});

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#6B7280',
            },
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    let label = context.dataset.label || '';
                    if (label) {
                        label += ': ';
                    }
                    if (context.parsed.y !== null) {
                        label += context.parsed.y.toFixed(2);
                    }
                    return label;
                }
            }
        }
    },
    scales: {
        y: {
            beginAtZero: true,
            ticks: {
                color: '#6B7280',
                callback: function(value) {
                    return value.toFixed(2);
                }
            },
            grid: {
                color: '#E5E7EB',
            },
        },
        x: {
            ticks: {
                color: '#6B7280',
            },
            grid: {
                color: '#E5E7EB',
            },
        },
    },
};

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom',
            labels: {
                color: '#6B7280',
            },
        },
        tooltip: {
            callbacks: {
                label: function(context) {
                    const label = context.label || '';
                    const value = context.raw || 0;
                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                    const percentage = Math.round((value / total) * 100);
                    return `${label}: ${value} (${percentage}%)`;
                }
            }
        }
    },
};

const formatBytes = (bytes) => {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

const formatUptime = (seconds) => {
    if (!seconds || isNaN(seconds)) return '0m';
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${days}d ${hours}h ${minutes}m`;
};

const formatDate = (date) => {
    return new Date(date).toLocaleString();
};

const calculateAverageSessionDuration = computed(() => {
    if (!props.hotspotStats.active_sessions?.length) return '0m';
    const totalDuration = props.hotspotStats.active_sessions.reduce((acc, session) => {
        return acc + (parseInt(session.uptime) || 0);
    }, 0);
    const averageSeconds = totalDuration / props.hotspotStats.active_sessions.length;
    const minutes = Math.floor(averageSeconds / 60);
    return `${minutes}m`;
});

const calculateAverageBandwidthUsage = computed(() => {
    if (!props.hotspotStats.active_sessions?.length) return '0 B/s';
    const totalBytes = props.hotspotStats.active_sessions.reduce((acc, session) => {
        return acc + (session.bytes_in || 0) + (session.bytes_out || 0);
    }, 0);
    const averageBytesPerSecond = totalBytes / props.hotspotStats.active_sessions.length;
    return formatBytes(averageBytesPerSecond) + '/s';
});

const calculatePeakUsageTime = computed(() => {
    if (!props.hotspotStats.active_sessions?.length) return 'N/A';
    const sessionsByHour = {};
    props.hotspotStats.active_sessions.forEach(session => {
        if (session.login_time) {
            const hour = new Date(session.login_time).getHours();
            sessionsByHour[hour] = (sessionsByHour[hour] || 0) + 1;
        }
    });
    if (Object.keys(sessionsByHour).length === 0) return 'N/A';
    const peakHour = Object.entries(sessionsByHour).reduce((a, b) => a[1] > b[1] ? a : b)[0];
    return `${peakHour}:00 - ${(parseInt(peakHour) + 1) % 24}:00`;
});

const calculateTotalBandwidthUsage = computed(() => {
    if (!props.hotspotStats.active_sessions?.length) return '0 B/s';
    const totalBytes = props.hotspotStats.active_sessions.reduce((acc, session) => {
        return acc + (parseInt(session.bytes_in) || 0) + (parseInt(session.bytes_out) || 0);
    }, 0);
    return formatBytes(totalBytes) + '/s';
});

const calculateMostUsedProfile = computed(() => {
    if (!props.hotspotStats.active_sessions?.length) return 'N/A';
    const profileCounts = {};
    props.hotspotStats.active_sessions.forEach(session => {
        if (session.profile) {
            profileCounts[session.profile] = (profileCounts[session.profile] || 0) + 1;
        }
    });
    if (Object.keys(profileCounts).length === 0) return 'N/A';
    return Object.entries(profileCounts).reduce((a, b) => a[1] > b[1] ? a : b)[0];
});

const calculateAverageConnectionTime = computed(() => {
    if (!props.hotspotStats.active_sessions?.length) return '0m';
    const totalTime = props.hotspotStats.active_sessions.reduce((acc, session) => {
        return acc + (session.uptime || 0);
    }, 0);
    const averageMinutes = Math.floor(totalTime / props.hotspotStats.active_sessions.length / 60);
    return `${averageMinutes}m`;
});

const calculateRouterHealth = computed(() => {
    const cpu = parseInt(props.systemStats.cpu_usage) || 0;
    const memory = parseInt(props.systemStats.memory_usage) || 0;
    const disk = parseInt(props.systemStats.disk_usage) || 0;
    
    if (cpu > 90 || memory > 90 || disk > 90) return 'Critical';
    if (cpu > 70 || memory > 70 || disk > 70) return 'Warning';
    return 'Healthy';
});

// Add polling interval
const POLLING_INTERVAL = 60000; // 1 minute

// Add loading states
const isLoading = ref(true);
const isRefreshing = ref(false);

// Add refresh function
const refreshData = () => {
    isRefreshing.value = true;
    router.reload({
        preserveScroll: true,
        preserveState: true,
        onFinish: () => {
            isRefreshing.value = false;
        }
    });
};

// Add auto-refresh
let refreshInterval;
onMounted(() => {
    // Initial data load
    updateChartData();
    isLoading.value = false;

    // Set up auto-refresh
    refreshInterval = setInterval(() => {
        refreshData();
    }, POLLING_INTERVAL);
});

// Clean up interval on component unmount
onUnmounted(() => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});

// Watch for data changes
watch(() => props.systemStats, () => {
    updateChartData();
}, { deep: true });

// Update chart data function
const updateChartData = () => {
    // Update CPU chart data
    if (props.systemStats.cpu_history?.length) {
        cpuChartData.value = {
            labels: props.systemStats.cpu_history.map((_, index) => `Point ${index + 1}`),
            datasets: [{
                label: 'CPU Usage (%)',
                data: props.systemStats.cpu_history,
                borderColor: 'rgb(75, 192, 192)',
                tension: 0.1,
                fill: false,
            }],
        };
    }

    // Update memory chart data
    if (props.systemStats.memory_history?.length) {
        memoryChartData.value = {
            labels: props.systemStats.memory_history.map((_, index) => `Point ${index + 1}`),
            datasets: [{
                label: 'Memory Usage (%)',
                data: props.systemStats.memory_history,
                borderColor: 'rgb(255, 99, 132)',
                tension: 0.1,
                fill: false,
            }],
        };
    }

    // Update traffic chart data
    if (props.systemStats.traffic_history?.length) {
        trafficChartData.value = {
            labels: props.systemStats.traffic_history.map((_, index) => `Point ${index + 1}`),
            datasets: [{
                label: 'Download (MB)',
                data: props.systemStats.traffic_history.map(t => (t.download || 0) / (1024 * 1024)),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
            }, {
                label: 'Upload (MB)',
                data: props.systemStats.traffic_history.map(t => (t.upload || 0) / (1024 * 1024)),
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
            }],
        };
    }

    // Update user distribution chart
    userDistributionChartData.value = {
        labels: ['Active', 'Disabled', 'Expired'],
        datasets: [{
            data: [
                parseInt(props.hotspotStats.active_users) || 0,
                parseInt(props.hotspotStats.disabled_users) || 0,
                parseInt(props.hotspotStats.expired_users) || 0,
            ],
            backgroundColor: [
                'rgb(75, 192, 192)',
                'rgb(255, 99, 132)',
                'rgb(255, 205, 86)',
            ],
        }],
    };

    // Update interface traffic chart
    if (props.routerStats.interface_traffic?.length) {
        interfaceTrafficChartData.value = {
            labels: props.routerStats.interface_traffic.map((_, index) => `Point ${index + 1}`),
            datasets: [{
                label: 'Download (Mbps)',
                data: props.routerStats.interface_traffic.map(t => (t.download || 0) / 1000000),
                borderColor: 'rgb(54, 162, 235)',
                backgroundColor: 'rgba(54, 162, 235, 0.5)',
            }, {
                label: 'Upload (Mbps)',
                data: props.routerStats.interface_traffic.map(t => (t.upload || 0) / 1000000),
                borderColor: 'rgb(255, 159, 64)',
                backgroundColor: 'rgba(255, 159, 64, 0.5)',
            }],
        };
    }

    // Update DHCP leases chart
    dhcpLeasesChartData.value = {
        labels: ['Active', 'Expired'],
        datasets: [{
            data: [
                parseInt(props.routerStats.dhcp_leases?.active) || 0,
                parseInt(props.routerStats.dhcp_leases?.expired) || 0,
            ],
            backgroundColor: [
                'rgb(75, 192, 192)',
                'rgb(255, 99, 132)',
            ],
        }],
    };

    // Update bandwidth usage chart
    if (props.routerStats.bandwidth_history?.length) {
        bandwidthUsageChartData.value = {
            labels: props.routerStats.bandwidth_history.map((_, index) => `Point ${index + 1}`),
            datasets: [{
                label: 'Bandwidth Usage (Mbps)',
                data: props.routerStats.bandwidth_history.map(b => (b.usage || 0) / 1000000),
                borderColor: 'rgb(153, 102, 255)',
                tension: 0.1,
                fill: false,
            }],
        };
    }
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">Dashboard</h2>
                <button 
                    @click="refreshData" 
                    :disabled="isRefreshing"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white focus:bg-gray-700 dark:focus:bg-white active:bg-gray-900 dark:active:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                >
                    <FontAwesomeIcon :icon="faSync" class="mr-2" :class="{ 'animate-spin': isRefreshing }" />
                    {{ isRefreshing ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <!-- Loading State -->
                <div v-if="isLoading" class="flex justify-center items-center h-64">
                    <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-gray-900 dark:border-gray-100"></div>
                </div>

                <!-- Content -->
                <div v-else>
                    <!-- System Overview Section -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">System Overview</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">System Uptime</h3>
                                    <FontAwesomeIcon :icon="faClock" class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ formatUptime(systemStats.uptime) }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">CPU Usage</h3>
                                    <FontAwesomeIcon :icon="faMicrochip" class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ systemStats.cpu_usage }}%</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Memory Usage</h3>
                                    <FontAwesomeIcon :icon="faServer" class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ systemStats.memory_usage }}%</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Disk Usage</h3>
                                    <FontAwesomeIcon :icon="faHardDrive" class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                                </div>
                                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ systemStats.disk_usage }}%</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Router Health</h3>
                                    <FontAwesomeIcon 
                                        :icon="calculateRouterHealth === 'Healthy' ? faShield : faTriangleExclamation" 
                                        class="h-6 w-6" 
                                        :class="{
                                            'text-green-600': calculateRouterHealth === 'Healthy',
                                            'text-yellow-600': calculateRouterHealth === 'Warning',
                                            'text-red-600': calculateRouterHealth === 'Critical'
                                        }" 
                                    />
                                </div>
                                <p class="text-2xl font-bold" :class="{
                                    'text-green-600': calculateRouterHealth === 'Healthy',
                                    'text-yellow-600': calculateRouterHealth === 'Warning',
                                    'text-red-600': calculateRouterHealth === 'Critical'
                                }">{{ calculateRouterHealth }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hotspot Overview Section -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Hotspot Overview</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Active Users</h3>
                                    <FontAwesomeIcon :icon="faUsers" class="h-6 w-6 text-green-600 dark:text-green-400" />
                                </div>
                                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ hotspotStats.active_users }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Disabled Users</h3>
                                    <FontAwesomeIcon :icon="faUser" class="h-6 w-6 text-red-600 dark:text-red-400" />
                                </div>
                                <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ hotspotStats.disabled_users }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Peak Usage Time</h3>
                                    <FontAwesomeIcon :icon="faClock" class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                                </div>
                                <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ hotspotStats.peak_usage_time || 'N/A' }}</p>
                            </div>
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <div class="flex items-center justify-between">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">Most Used Profile</h3>
                                    <FontAwesomeIcon :icon="faUser" class="h-6 w-6 text-purple-600 dark:text-purple-400" />
                                </div>
                                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ hotspotStats.most_used_profile || 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Performance Charts Section -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Performance Metrics</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- CPU Usage Chart -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">CPU Usage History</h3>
                                <div class="h-64">
                                    <Line :data="cpuChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <!-- Memory Usage Chart -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Memory Usage History</h3>
                                <div class="h-64">
                                    <Line :data="memoryChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Network Statistics Section -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Network Statistics</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Network Traffic Chart -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Network Traffic History</h3>
                                <div class="h-64">
                                    <Bar :data="trafficChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <!-- Interface Traffic Chart -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Interface Traffic</h3>
                                <div class="h-64">
                                    <Bar :data="interfaceTrafficChartData" :options="chartOptions" />
                                </div>
                            </div>

                            <!-- DHCP Leases Chart -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">DHCP Leases Distribution</h3>
                                <div class="h-64">
                                    <Doughnut :data="dhcpLeasesChartData" :options="doughnutOptions" />
                                </div>
                            </div>

                            <!-- Bandwidth Usage Chart -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Bandwidth Usage</h3>
                                <div class="h-64">
                                    <Line :data="bandwidthUsageChartData" :options="chartOptions" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activity Section -->
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">Recent Activity</h3>
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Recent Sessions -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Sessions</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">IP Address</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Duration</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Traffic</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr v-for="session in recentSessions" :key="session.id">
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ session.username }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ session.ip_address }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ formatUptime(session.uptime) }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">
                                                        ↓{{ formatBytes(session.bytes_in) }} ↑{{ formatBytes(session.bytes_out) }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Recent Users -->
                            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                                <div class="p-6">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Recent Users</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                            <thead class="bg-gray-50 dark:bg-gray-700">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Username</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Profile</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Expires</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                                <tr v-for="user in recentUsers" :key="user.id">
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ user.username }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ user.profile_name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span :class="{
                                                            'px-2 inline-flex text-xs leading-5 font-semibold rounded-full': true,
                                                            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200': user.status === 'active',
                                                            'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200': user.status === 'disabled',
                                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200': user.status === 'expired'
                                                        }">
                                                            {{ user.status }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-gray-900 dark:text-gray-100">{{ user.expires_at ? formatDate(user.expires_at) : 'Never' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template> 