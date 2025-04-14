<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class RouterController extends Controller
{
    protected function getMikroTikClient()
    {
        try {
            $config = [
                'host' => env('MIKROTIK_HOST', '172.16.0.1'),
                'user' => env('MIKROTIK_USER', 'jtgadmin'),
                'pass' => env('MIKROTIK_PASS', 'jtg@2025'),
                'port' => (int) env('MIKROTIK_PORT', 8728),
            ];
            Log::debug('MikroTik client config', [
                'host' => $config['host'],
                'user' => $config['user'],
                'port' => $config['port'],
                'pass' => strlen($config['pass']) > 0 ? '****' : 'empty'
            ]);
            return new Client($config);
        } catch (Exception $e) {
            Log::error('Failed to initialize MikroTik client', ['error' => $e->getMessage()]);
            throw new Exception('Failed to initialize MikroTik client: ' . $e->getMessage());
        }
    }

    public function index()
    {
        try {
            $client = $this->getMikroTikClient();

            // Fetch system resources
            $resourceQuery = new Query('/system/resource/print');
            $resource = $client->query($resourceQuery)->read()[0];

            // Fetch system identity (router name)
            $identityQuery = new Query('/system/identity/print');
            $identity = $client->query($identityQuery)->read()[0];

            // Fetch package update status
            $updateQuery = new Query('/system/package/update/check-for-updates');
            $client->query($updateQuery)->read();
            $updateStatusQuery = new Query('/system/package/update/print');
            $updateStatus = $client->query($updateStatusQuery)->read()[0];

            // Fetch interface stats (for main interface, e.g., ether1)
            $interfaceQuery = new Query('/interface/print');
            $interfaces = $client->query($interfaceQuery)->read();
            $ether1 = collect($interfaces)->firstWhere('name', 'ether1') ?? [];

            // Fetch hotspot active users
            $hotspotActiveQuery = new Query('/ip/hotspot/active/print');
            $hotspotActiveUsers = $client->query($hotspotActiveQuery)->read();

            // Fetch hotspot users (total)
            $hotspotUsersQuery = new Query('/ip/hotspot/user/print');
            $hotspotUsers = $client->query($hotspotUsersQuery)->read();

            // Calculate traffic rates (bytes per second) using caching
            $interfaceKey = 'interface_ether1_stats';
            $previousStats = Cache::get($interfaceKey, ['tx-byte' => 0, 'rx-byte' => 0, 'timestamp' => now()->timestamp]);
            $currentTxBytes = (int) ($ether1['tx-byte'] ?? 0);
            $currentRxBytes = (int) ($ether1['rx-byte'] ?? 0);
            $timeDiff = now()->timestamp - $previousStats['timestamp'];
            $txRate = $timeDiff > 0 ? (($currentTxBytes - $previousStats['tx-byte']) * 8 / $timeDiff) : 0; // bits per second
            $rxRate = $timeDiff > 0 ? (($currentRxBytes - $previousStats['rx-byte']) * 8 / $timeDiff) : 0; // bits per second
            Cache::put($interfaceKey, [
                'tx-byte' => $currentTxBytes,
                'rx-byte' => $currentRxBytes,
                'timestamp' => now()->timestamp
            ], now()->addMinutes(5));

            // Simulate traffic history for charts (last 24 hours, 1-hour intervals)
            $trafficHistory = $this->generateTrafficHistory($txRate, $rxRate);

            // Calculate downtime (approximation based on last reboot)
            $uptimeStr = $resource['uptime'] ?? '0s';
            $uptimeSeconds = $this->parseUptimeToSeconds($uptimeStr);
            $lastReboot = now()->subSeconds($uptimeSeconds);
            $downtime = $uptimeSeconds > 0 ? $lastReboot->diffForHumans() : 'Unknown';

            $data = [
                'router_name' => $identity['name'] ?? 'MikroTik',
                'uptime' => $uptimeStr,
                'downtime' => $downtime,
                'version' => $resource['version'] ?? 'N/A',
                'board_name' => $resource['board-name'] ?? 'N/A',
                'cpu' => $resource['cpu'] ?? 'N/A',
                'cpu_load' => $resource['cpu-load'] ?? '0',
                'cpu_frequency' => $resource['cpu-frequency'] ?? '0',
                'free_memory' => $resource['free-memory'] ?? '0',
                'total_memory' => $resource['total-memory'] ?? '0',
                'free_hdd_space' => $resource['free-hdd-space'] ?? '0',
                'total_hdd_space' => $resource['total-hdd-space'] ?? '0',
                'update_available' => ($updateStatus['status'] ?? '') === 'New version is available',
                'latest_version' => $updateStatus['latest-version'] ?? 'N/A',
                'tx_rate' => $txRate,
                'rx_rate' => $rxRate,
                'tx_bytes' => $currentTxBytes,
                'rx_bytes' => $currentRxBytes,
                'active_hotspot_users' => count($hotspotActiveUsers),
                'total_hotspot_users' => count($hotspotUsers),
                'traffic_history' => $trafficHistory,
            ];

            Log::info('Router stats fetched for dashboard', $data);
            return Inertia::render('Dashboard', ['router' => $data]);
        } catch (Exception $e) {
            Log::error('Dashboard error', ['error' => $e->getMessage()]);
            return Inertia::render('Dashboard', [
                'error' => 'Failed to connect to MikroTik: ' . $e->getMessage()
            ]);
        }
    }

    public function overview()
    {
        try {
            $client = $this->getMikroTikClient();
            $query = new Query('/system/resource/print');
            $response = $client->query($query)->read()[0];

            $data = [
                'uptime' => $response['uptime'] ?? 'N/A',
                'cpu_load' => $response['cpu-load'] ?? '0',
                'free_memory' => $response['free-memory'] ?? '0',
                'total_memory' => $response['total-memory'] ?? '0',
            ];

            Log::info('Router stats fetched for overview', $data);
            return Inertia::render('Router/Overview', ['router' => $data]);
        } catch (Exception $e) {
            Log::error('Overview error', ['error' => $e->getMessage()]);
            return Inertia::render('Router/Overview', [
                'error' => 'Failed to connect to MikroTik: ' . $e->getMessage()
            ]);
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

    private function generateTrafficHistory(float $currentTxRate, float $currentRxRate): array
    {
        $history = [];
        $hours = 24; // Last 24 hours
        $baseTime = now()->subHours($hours - 1);

        for ($i = 0; $i < $hours; $i++) {
            $time = $baseTime->addHour();
            $variation = rand(80, 120) / 100; // Random variation between 80% and 120%
            $history[] = [
                'time' => $time->format('H:i'),
                'tx' => round($currentTxRate * $variation / (1000 * 1000), 2), // Mbps
                'rx' => round($currentRxRate * $variation / (1000 * 1000), 2), // Mbps
            ];
        }

        return $history;
    }
}