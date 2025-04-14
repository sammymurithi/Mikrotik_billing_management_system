<template>
    <app-layout title="Dashboard">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
        <div v-if="error" class="p-4 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 rounded-lg mb-6">
          <div class="flex items-center">
            <ExclamationCircleIcon class="w-5 h-5 mr-2" />
            <span>Error: {{ error }}</span>
          </div>
        </div>
        <div v-else-if="router" class="space-y-6">
          <!-- Overview Cards -->
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex items-center space-x-4">
              <div class="p-3 bg-blue-100 dark:bg-blue-900/50 rounded-full">
                <ServerIcon class="w-6 h-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Router</h3>
                <p class="text-gray-600 dark:text-gray-400">{{ router.router_name }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">{{ router.board_name }} (v{{ router.version }})</p>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex items-center space-x-4">
              <div class="p-3 bg-green-100 dark:bg-green-900/50 rounded-full">
                <ClockIcon class="w-6 h-6 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Uptime</h3>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400">{{ router.uptime }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">Last Reboot: {{ router.downtime }}</p>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex items-center space-x-4">
              <div class="p-3 bg-yellow-100 dark:bg-yellow-900/50 rounded-full">
                <CpuChipIcon class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">CPU Load</h3>
                <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ router.cpu_load }}%</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">{{ router.cpu }} @ {{ router.cpu_frequency }} MHz</p>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700 flex items-center space-x-4">
              <div class="p-3 bg-purple-100 dark:bg-purple-900/50 rounded-full">
                <CpuChipIcon class="w-6 h-6 text-purple-600 dark:text-purple-400" />
              </div>
              <div>
                <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100">Memory</h3>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ formatMemory(router.free_memory) }}</p>
                <p class="text-sm text-gray-500 dark:text-gray-500">Total: {{ formatMemory(router.total_memory) }}</p>
              </div>
            </div>
          </div>
    
          <!-- Hotspot and Traffic Cards -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                <UserGroupIcon class="w-5 h-5 mr-2 text-blue-600 dark:text-blue-400" />
                Hotspot Users
              </h3>
              <div class="flex justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Active Users</p>
                  <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ router.active_hotspot_users }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Total Users</p>
                  <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">{{ router.total_hotspot_users }}</p>
                </div>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                <ArrowTrendingUpIcon class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" />
                Current Traffic (ether1)
              </h3>
              <div class="flex justify-between">
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Upload (TX)</p>
                  <p class="text-xl font-bold text-green-600 dark:text-green-400">{{ formatBitsPerSecond(router.tx_rate) }}</p>
                </div>
                <div>
                  <p class="text-sm text-gray-600 dark:text-gray-400">Download (RX)</p>
                  <p class="text-xl font-bold text-blue-600 dark:text-blue-400">{{ formatBitsPerSecond(router.rx_rate) }}</p>
                </div>
              </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
              <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
                <DocumentDuplicateIcon class="w-5 h-5 mr-2 text-purple-600 dark:text-purple-400" />
                Storage
              </h3>
              <p class="text-sm text-gray-600 dark:text-gray-400">Free Space</p>
              <p class="text-xl font-bold text-purple-600 dark:text-purple-400">{{ formatBytes(router.free_hdd_space) }}</p>
              <p class="text-sm text-gray-500 dark:text-gray-500">Total: {{ formatBytes(router.total_hdd_space) }}</p>
            </div>
          </div>
    
          <!-- Traffic Chart -->
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
              <ChartBarIcon class="w-5 h-5 mr-2 text-indigo-600 dark:text-indigo-400" />
              Traffic History (Last 24 Hours)
            </h3>
            <div class="h-80">
              <Line v-if="router.traffic_history && router.traffic_history.length > 0"
                :data="trafficChartData"
                :options="chartOptions"
              />
              <div v-else class="h-full flex items-center justify-center text-gray-500 dark:text-gray-400">
                No traffic data available
              </div>
            </div>
            <div class="flex justify-center space-x-6 mt-4">
              <div class="flex items-center">
                <span class="w-4 h-4 bg-blue-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Download (RX)</span>
              </div>
              <div class="flex items-center">
                <span class="w-4 h-4 bg-green-500 rounded mr-2"></span>
                <span class="text-sm text-gray-600 dark:text-gray-400">Upload (TX)</span>
              </div>
            </div>
          </div>
    
          <!-- System Updates -->
          <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-sm border border-gray-100 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-100 mb-4 flex items-center">
              <ArrowPathIcon class="w-5 h-5 mr-2 text-yellow-600 dark:text-yellow-400" />
              System Updates
            </h3>
            <div class="flex items-center">
              <div v-if="router.update_available" class="flex items-center text-yellow-600 dark:text-yellow-400">
                <ExclamationTriangleIcon class="w-5 h-5 mr-2" />
                <span>New version available: {{ router.latest_version }}</span>
              </div>
              <div v-else class="flex items-center text-green-600 dark:text-green-400">
                <CheckCircleIcon class="w-5 h-5 mr-2" />
                <span>System is up to date</span>
              </div>
            </div>
          </div>
        </div>
        
        <div v-else class="flex items-center justify-center h-64">
          <div class="text-center">
            <div class="inline-block animate-spin rounded-full h-8 w-8 border-t-2 border-b-2 border-blue-500 dark:border-blue-400 mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">Loading router data...</p>
          </div>
        </div>
      </div>
    </app-layout>
  </template>
  <script>
  import AppLayout from '@/Layouts/AppLayout.vue';
  import { Line } from 'vue-chartjs';
  import { Filler } from 'chart.js';

  import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    LineElement,
    PointElement,
    LinearScale,
    CategoryScale,
  } from 'chart.js';
  import {
    ClockIcon,
    CpuChipIcon,
    ServerIcon,
    UserGroupIcon,
    ArrowTrendingUpIcon,
    DocumentDuplicateIcon,
    ArrowPathIcon,
    ChartBarIcon,
    ExclamationCircleIcon,
    ExclamationTriangleIcon,
    CheckCircleIcon,
  } from '@heroicons/vue/24/outline';
  
  ChartJS.register(
    CategoryScale,
    LinearScale,
    PointElement,
    LineElement,
    Title,
    Tooltip,
    Legend
  );
  
  export default {
    components: {
      AppLayout,
      Line,
      ClockIcon,
      CpuChipIcon,
      ServerIcon,
      UserGroupIcon,
      ArrowTrendingUpIcon,
      DocumentDuplicateIcon,
      ArrowPathIcon,
      ChartBarIcon,
      ExclamationCircleIcon,
      ExclamationTriangleIcon,
      CheckCircleIcon,
      Filler
    },
    props: ['router', 'error'],
    computed: {
      trafficChartData() {
        if (!this.router || !this.router.traffic_history) {
          return {
            labels: [],
            datasets: [],
          };
        }
        
        return {
          labels: this.router.traffic_history.map(item => item.time),
          datasets: [
            {
              label: 'Download (RX)',
              borderColor: 'rgba(59, 130, 246, 1)', // blue-500
              backgroundColor: 'rgba(59, 130, 246, 0.1)',
              borderWidth: 2,
              pointRadius: 3,
              pointBackgroundColor: 'rgba(59, 130, 246, 1)',
              data: this.router.traffic_history.map(item => item.rx),
              fill: true,
              tension: 0.4,
            },
            {
              label: 'Upload (TX)',
              borderColor: 'rgba(34, 197, 94, 1)', // green-500
              backgroundColor: 'rgba(34, 197, 94, 0.1)',
              borderWidth: 2,
              pointRadius: 3,
              pointBackgroundColor: 'rgba(34, 197, 94, 1)',
              data: this.router.traffic_history.map(item => item.tx),
              fill: true,
              tension: 0.4,
            },
          ],
        };
      },
      chartOptions() {
        const isDarkMode = this.$page && this.$page.props && this.$page.props.darkMode;
        const textColor = isDarkMode ? '#D1D5DB' : '#1F2937';
        const gridColor = isDarkMode ? 'rgba(255, 255, 255, 0.1)' : 'rgba(0, 0, 0, 0.1)';
        
        return {
          responsive: true,
          maintainAspectRatio: false,
          interaction: {
            mode: 'index',
            intersect: false,
          },
          scales: {
            y: {
              beginAtZero: true,
              title: {
                display: true,
                text: 'Traffic (Mbps)',
                color: textColor,
                font: {
                  size: 12,
                  weight: 'normal',
                },
              },
              ticks: {
                color: textColor,
                font: {
                  size: 11,
                },
                callback: (value) => value.toFixed(1),
              },
              grid: {
                color: gridColor,
                drawBorder: false,
              },
            },
            x: {
              ticks: {
                color: textColor,
                font: {
                  size: 11,
                },
                maxRotation: 0,
                autoSkipPadding: 15,
              },
              grid: {
                display: false,
              },
            },
          },
          plugins: {
            legend: {
              display: false,
            },
            tooltip: {
              backgroundColor: isDarkMode ? 'rgba(30, 41, 59, 0.8)' : 'rgba(255, 255, 255, 0.8)',
              titleColor: isDarkMode ? '#fff' : '#000',
              bodyColor: isDarkMode ? '#D1D5DB' : '#1F2937',
              borderColor: isDarkMode ? 'rgba(255, 255, 255, 0.2)' : 'rgba(0, 0, 0, 0.1)',
              borderWidth: 1,
              padding: 10,
              displayColors: true,
              callbacks: {
                label: function(context) {
                  let label = context.dataset.label || '';
                  if (label) {
                    label += ': ';
                  }
                  if (context.parsed.y !== null) {
                    label += context.parsed.y.toFixed(2) + ' Mbps';
                  }
                  return label;
                }
              }
            },
          },
        };
      },
    },
    methods: {
      formatMemory(bytes) {
        const mb = bytes / (1024 * 1024);
        return `${mb.toFixed(2)} MB`;
      },
      formatBytes(bytes) {
        const gb = bytes / (1024 * 1024 * 1024);
        if (gb >= 1) return `${gb.toFixed(2)} GB`;
        const mb = bytes / (1024 * 1024);
        return `${mb.toFixed(2)} MB`;
      },
      formatBitsPerSecond(bits) {
        const mbps = bits / (1000 * 1000);
        if (mbps >= 1) return `${mbps.toFixed(2)} Mbps`;
        const kbps = bits / 1000;
        return `${kbps.toFixed(2)} Kbps`;
      },
    },
  };
  </script>
  