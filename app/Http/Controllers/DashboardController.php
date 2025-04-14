<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;

class DashboardController extends Controller
{
    public function index()
    {
        \Log::info("Starting dashboard data fetch");
        $routers = Router::all();
        \Log::info("Found {$routers->count()} routers in database");
        
        $routerData = null;
        $error = null;

        if ($routers->count() > 0) {
            $router = $routers->first();
            
            try {
                \Log::info("Attempting to connect to router: {$router->name} at {$router->ip_address}:{$router->port}");
                
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                ]);

                \Log::info("Connected to router successfully");

                // Get system resource first
                $query = (new Query('/system/resource/print'));
                $resource = $client->query($query)->read();
                \Log::debug("System resource data:", $resource);
                
                if (empty($resource)) {
                    throw new \Exception("Failed to get system resource data");
                }

                $routerData = [
                    'router_name' => $router->name,
                    'board_name' => $resource[0]['board-name'] ?? 'Unknown',
                    'version' => $resource[0]['version'] ?? 'Unknown',
                    'uptime' => $resource[0]['uptime'] ?? 'Unknown',
                    'cpu_load' => $resource[0]['cpu-load'] ?? 'Unknown',
                    'free_memory' => $resource[0]['free-memory'] ?? 0,
                    'total_memory' => $resource[0]['total-memory'] ?? 0,
                    'free_hdd_space' => $resource[0]['free-hdd-space'] ?? 0,
                    'total_hdd_space' => $resource[0]['total-hdd-space'] ?? 0,
                ];

                \Log::info("Basic router data collected", $routerData);

                // Get hotspot users count
                try {
                    $query = (new Query('/ip/hotspot/user/print'));
                    $users = $client->query($query)->read();
                    \Log::debug("Hotspot users data:", $users);
                    $routerData['total_hotspot_users'] = count($users);
                } catch (\Exception $e) {
                    \Log::warning("Failed to get hotspot users: " . $e->getMessage());
                    $routerData['total_hotspot_users'] = 0;
                }

                // Get active sessions count
                try {
                    $query = (new Query('/ip/hotspot/active/print'));
                    $sessions = $client->query($query)->read();
                    \Log::debug("Active sessions data:", $sessions);
                    $routerData['active_hotspot_users'] = count($sessions);
                } catch (\Exception $e) {
                    \Log::warning("Failed to get active sessions: " . $e->getMessage());
                    $routerData['active_hotspot_users'] = 0;
                }

                // Get interface traffic
                try {
                    $query = (new Query('/interface/monitor-traffic'))
                        ->equal('interface', 'ether1')
                        ->equal('once', '');
                    $traffic = $client->query($query)->read();
                    \Log::debug("Interface traffic data:", $traffic);
                    $routerData['tx_rate'] = $traffic[0]['tx-bits-per-second'] ?? 0;
                    $routerData['rx_rate'] = $traffic[0]['rx-bits-per-second'] ?? 0;
                } catch (\Exception $e) {
                    \Log::warning("Failed to get interface traffic: " . $e->getMessage());
                    $routerData['tx_rate'] = 0;
                    $routerData['rx_rate'] = 0;
                }

                \Log::info("Successfully collected all router data", $routerData);

            } catch (\Exception $e) {
                \Log::error("Failed to fetch router data for {$router->name}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $error = "Failed to connect to router: " . $e->getMessage();
            }
        } else {
            $error = "No routers configured";
            \Log::warning($error);
        }

        \Log::info("Returning dashboard data", [
            'has_router_data' => !is_null($routerData),
            'has_error' => !is_null($error)
        ]);

        return Inertia::render('Dashboard', [
            'router' => $routerData,
            'error' => $error,
        ]);
    }
} 