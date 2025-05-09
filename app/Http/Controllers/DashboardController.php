<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Router;
use App\Models\HotspotProfile;
use App\Models\HotspotSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;

class DashboardController extends Controller
{
    public function index()
    {
        // Cache the results for 1 minute to reduce router load
        $cacheKey = 'dashboard_stats_' . Router::first()?->id;
        $cachedData = \Cache::get($cacheKey);

        if ($cachedData) {
            return Inertia::render('Dashboard/Index', $cachedData);
        }

        // Fetch data in parallel using Laravel's async features
        $systemStats = \Cache::remember('system_stats', 60, function () {
            return $this->getSystemStats();
        });

        $hotspotStats = \Cache::remember('hotspot_stats', 60, function () {
            return $this->getHotspotStats();
        });

        $routerStats = \Cache::remember('router_stats', 60, function () {
            return $this->getRouterStats();
        });

        // Get recent sessions and users with optimized queries
        $recentSessions = HotspotSession::with('hotspotUser')
            ->select('id', 'hotspot_user_id', 'ip_address', 'mac_address', 'uptime', 'bytes_in', 'bytes_out', 'created_at')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'username' => $session->hotspotUser->username,
                    'ip_address' => $session->ip_address,
                    'mac_address' => $session->mac_address,
                    'uptime' => $session->uptime,
                    'bytes_in' => $session->bytes_in,
                    'bytes_out' => $session->bytes_out,
                    'created_at' => $session->created_at,
                ];
            });

        // Get recent users with proper status validation
        $recentUsers = HotspotUser::with('profile')
            ->select('id', 'username', 'profile_name', 'status', 'expires_at', 'created_at', 'disabled')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($user) {
                // Determine actual status based on multiple conditions
                $status = 'active';
                if ($user->disabled) {
                    $status = 'disabled';
                } elseif ($user->expires_at && $user->expires_at < now()) {
                    $status = 'expired';
                } elseif ($user->status === 'disabled') {
                    $status = 'disabled';
                }

                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'profile_name' => $user->profile_name,
                    'status' => $status,
                    'expires_at' => $user->expires_at,
                    'created_at' => $user->created_at,
                ];
            });

        $data = [
            'systemStats' => $systemStats,
            'hotspotStats' => $hotspotStats,
            'routerStats' => $routerStats,
            'recentSessions' => $recentSessions,
            'recentUsers' => $recentUsers,
            'router' => Router::first() ? [
                'id' => Router::first()->id,
                'router_name' => Router::first()->name,
                'board_name' => Router::first()->board_name ?? 'Unknown',
                'version' => Router::first()->version ?? 'Unknown',
            ] : null,
        ];

        // Cache the complete response
        \Cache::put($cacheKey, $data, 60);

        return Inertia::render('Dashboard/Index', $data);
    }

    private function getSystemStats()
    {
        $router = Router::first();
        if (!$router) {
            return [
                'cpu_usage' => 0,
                'memory_usage' => 0,
                'disk_usage' => 0,
                'uptime' => 0,
                'cpu_history' => [],
                'memory_history' => [],
                'traffic_history' => [],
                'router' => null
            ];
        }

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            // Get system resources with a single query
            $query = (new Query('/system/resource/print'));
            $resources = $client->query($query)->read()[0];

            // Get system identity
            $query = (new Query('/system/identity/print'));
            $identity = $client->query($query)->read()[0];

            // Get system routerboard
            $query = (new Query('/system/routerboard/print'));
            $routerboard = $client->query($query)->read()[0];

            // Calculate memory usage percentage
            $totalMemory = (int)($resources['total-memory'] ?? 0);
            $freeMemory = (int)($resources['free-memory'] ?? 0);
            $usedMemory = $totalMemory - $freeMemory;
            $memoryUsage = $totalMemory > 0 ? round(($usedMemory / $totalMemory) * 100) : 0;

            // Calculate disk usage percentage
            $totalHddSpace = (int)($resources['total-hdd-space'] ?? 0);
            $freeHddSpace = (int)($resources['free-hdd-space'] ?? 0);
            $usedHddSpace = $totalHddSpace - $freeHddSpace;
            $diskUsage = $totalHddSpace > 0 ? round(($usedHddSpace / $totalHddSpace) * 100) : 0;

            // Parse uptime to seconds
            $uptime = $this->parseUptimeToSeconds($resources['uptime'] ?? '0s');

            // Get system history with a single query
            $query = (new Query('/system/history/print'));
            $history = $client->query($query)->read();

            // Process history data
            $cpuHistory = [];
            $memoryHistory = [];
            $trafficHistory = [];

            foreach ($history as $point) {
                $cpuHistory[] = (int)($point['cpu-load'] ?? 0);
                $memoryHistory[] = (int)($point['memory-usage'] ?? 0);
                $trafficHistory[] = [
                    'download' => (int)($point['rx-byte'] ?? 0),
                    'upload' => (int)($point['tx-byte'] ?? 0),
                ];
            }

            return [
                'cpu_usage' => (int)($resources['cpu-load'] ?? 0),
                'memory_usage' => $memoryUsage,
                'disk_usage' => $diskUsage,
                'uptime' => $uptime,
                'cpu_history' => array_slice($cpuHistory, -10),
                'memory_history' => array_slice($memoryHistory, -10),
                'traffic_history' => array_slice($trafficHistory, -10),
                'router' => [
                    'id' => $router->id,
                    'router_name' => $identity['name'] ?? $router->name,
                    'board_name' => $routerboard['model'] ?? 'Unknown',
                    'version' => $routerboard['current-firmware'] ?? 'Unknown',
                    'cpu' => $resources['cpu'] ?? 'Unknown',
                    'cpu_frequency' => $resources['cpu-frequency'] ?? 'Unknown',
                    'free_memory' => $freeMemory,
                    'total_memory' => $totalMemory,
                    'free_hdd_space' => $freeHddSpace,
                    'total_hdd_space' => $totalHddSpace,
                    'uptime' => $resources['uptime'] ?? '0s',
                    'downtime' => $resources['downtime'] ?? '0s',
                    'cpu_load' => $resources['cpu-load'] ?? '0',
                    'tx_rate' => $resources['tx-bits-per-second'] ?? 0,
                    'rx_rate' => $resources['rx-bits-per-second'] ?? 0,
                    'active_hotspot_users' => $resources['active-hotspot-users'] ?? 0,
                    'total_hotspot_users' => $resources['total-hotspot-users'] ?? 0,
                    'update_available' => $routerboard['upgrade-firmware'] ?? false,
                    'latest_version' => $routerboard['new-firmware'] ?? null,
                    'traffic_history' => array_slice($trafficHistory, -24) // Last 24 hours
                ]
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get system stats: ' . $e->getMessage());
            return [
                'cpu_usage' => 0,
                'memory_usage' => 0,
                'disk_usage' => 0,
                'uptime' => 0,
                'cpu_history' => [],
                'memory_history' => [],
                'traffic_history' => [],
                'router' => null
            ];
        }
    }

    private function parseUptimeToSeconds(string $uptime): int
    {
        $seconds = 0;
        preg_match_all('/(\d+)([wdhms])/', $uptime, $matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $value = (int) $match[1];
            $unit = $match[2];
            if ($unit === 'w') $seconds += $value * 7 * 24 * 60 * 60;
            elseif ($unit === 'd') $seconds += $value * 24 * 60 * 60;
            elseif ($unit === 'h') $seconds += $value * 60 * 60;
            elseif ($unit === 'm') $seconds += $value * 60;
            elseif ($unit === 's') $seconds += $value;
        }
        return $seconds;
    }

    private function getHotspotStats()
    {
        $router = Router::first();
        if (!$router) {
            return [
                'active_users' => 0,
                'disabled_users' => 0,
                'expired_users' => 0,
                'active_sessions' => [],
                'peak_usage_time' => null,
                'most_used_profile' => null,
            ];
        }

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            // Get active sessions
            $query = (new Query('/ip/hotspot/active/print'));
            $activeSessions = $client->query($query)->read();

            // Get hotspot users
            $query = (new Query('/ip/hotspot/user/print'));
            $users = $client->query($query)->read();

            // Count users by status
            $activeUsers = 0;
            $disabledUsers = 0;
            $expiredUsers = 0;

            // Track profile usage
            $profileUsage = [];
            $hourlyUsage = array_fill(0, 24, 0);

            foreach ($users as $user) {
                if ($user['disabled'] === 'true') {
                    $disabledUsers++;
                } elseif (isset($user['limit-uptime']) && strtotime($user['limit-uptime']) < time()) {
                    $expiredUsers++;
                } else {
                    $activeUsers++;
                }

                // Track profile usage
                $profileName = $user['profile'] ?? 'default';
                $profileUsage[$profileName] = ($profileUsage[$profileName] ?? 0) + 1;

                // Track hourly usage
                if (isset($user['last-logged-out'])) {
                    $hour = (int)date('G', strtotime($user['last-logged-out']));
                    $hourlyUsage[$hour]++;
                }
            }

            // Find most used profile
            $mostUsedProfile = null;
            $maxUsage = 0;
            foreach ($profileUsage as $profile => $usage) {
                if ($usage > $maxUsage) {
                    $maxUsage = $usage;
                    $mostUsedProfile = $profile;
                }
            }

            // Find peak usage time
            $peakHour = array_search(max($hourlyUsage), $hourlyUsage);
            $peakUsageTime = $peakHour !== false ? sprintf('%02d:00', $peakHour) : null;

            // Process active sessions
            $processedSessions = [];
            foreach ($activeSessions as $session) {
                $processedSessions[] = [
                    'username' => $session['user'] ?? '',
                    'ip_address' => $session['address'] ?? '',
                    'mac_address' => $session['mac-address'] ?? '',
                    'uptime' => $session['uptime'] ?? 0,
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'profile' => $session['profile'] ?? '',
                    'login_time' => $session['login-time'] ?? '',
                ];
            }

            return [
                'active_users' => $activeUsers,
                'disabled_users' => $disabledUsers,
                'expired_users' => $expiredUsers,
                'active_sessions' => $processedSessions,
                'peak_usage_time' => $peakUsageTime,
                'most_used_profile' => $mostUsedProfile,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get hotspot stats: ' . $e->getMessage());
            return [
                'active_users' => 0,
                'disabled_users' => 0,
                'expired_users' => 0,
                'active_sessions' => [],
                'peak_usage_time' => null,
                'most_used_profile' => null,
            ];
        }
    }

    private function getRouterStats()
    {
        $router = Router::first();
        if (!$router) {
            return [
                'interface_traffic' => [],
                'dhcp_leases' => [
                    'active' => 0,
                    'expired' => 0,
                ],
                'bandwidth_history' => [],
            ];
        }

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            // Get interface traffic
            $query = new Query('/interface/monitor-traffic');
            $query->equal('interface', 'ether1');
            $query->equal('once');
            $interfaceTraffic = $client->query($query)->read();

            // Get DHCP leases with proper status checking
            $query = new Query('/ip/dhcp-server/lease/print');
            $leases = $client->query($query)->read();

            // Count active and expired leases
            $activeLeases = 0;
            $expiredLeases = 0;
            foreach ($leases as $lease) {
                if (isset($lease['status']) && $lease['status'] === 'bound') {
                    $activeLeases++;
                } else {
                    $expiredLeases++;
                }
            }

            // Get bandwidth history
            $query = new Query('/interface/monitor-traffic');
            $query->equal('interface', 'ether1');
            $query->equal('duration', '1h');
            $bandwidthHistory = $client->query($query)->read();

            // Process interface traffic
            $processedTraffic = [];
            foreach ($interfaceTraffic as $traffic) {
                $processedTraffic[] = [
                    'download' => (int)($traffic['rx-bits-per-second'] ?? 0),
                    'upload' => (int)($traffic['tx-bits-per-second'] ?? 0),
                ];
            }

            // Process bandwidth history
            $processedHistory = [];
            foreach ($bandwidthHistory as $point) {
                $processedHistory[] = [
                    'usage' => (int)($point['rx-bits-per-second'] ?? 0) + (int)($point['tx-bits-per-second'] ?? 0),
                ];
            }

            return [
                'interface_traffic' => array_slice($processedTraffic, -10),
                'dhcp_leases' => [
                    'active' => $activeLeases,
                    'expired' => $expiredLeases,
                ],
                'bandwidth_history' => array_slice($processedHistory, -10),
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get router stats: ' . $e->getMessage());
            return [
                'interface_traffic' => [],
                'dhcp_leases' => [
                    'active' => 0,
                    'expired' => 0,
                ],
                'bandwidth_history' => [],
            ];
        }
    }
} 