<?php

namespace App\Http\Controllers;

use App\Models\Router;
use Illuminate\Http\Request;
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
        $routers = Router::all();
        return Inertia::render('Router/Index', [
            'routers' => $routers
        ]);
    }

    public function create()
    {
        return Inertia::render('Router/Partials/Create');
    }

    protected function validateRouterConnection($ip, $username, $password, $port)
    {
        try {
            // Ensure port is an integer
            $port = (int) $port;
            
            // Create client with timeout
            $client = new Client([
                'host' => $ip,
                'user' => $username,
                'pass' => $password,
                'port' => $port,
                'timeout' => 5, // 5 seconds timeout
            ]);

            // Test connection by getting router identity
            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();

            if (empty($response)) {
                throw new \Exception('Could not retrieve router identity');
            }

            return [
                'success' => true,
                'identity' => $response[0]['name'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|ip',
            'username' => 'required|string|max:255',
            'password' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        // Test connection to router
        $connectionTest = $this->validateRouterConnection(
            $validated['ip_address'],
            $validated['username'],
            $validated['password'],
            $validated['port']
        );

        if (!$connectionTest['success']) {
            return back()->withErrors([
                'error' => 'Failed to connect to router: ' . $connectionTest['error']
            ])->withInput();
        }

        // Create router in database
        $router = Router::create($validated);

        return redirect()->route('routers.index')
            ->with('success', 'Router created successfully. Connected to: ' . $connectionTest['identity']);
    }

    public function show($id)
    {
        $router = Router::findOrFail($id);
        return Inertia::render('Router/Partials/Show', [
            'router' => $router
        ]);
    }

    public function edit($id)
    {
        $router = Router::findOrFail($id);
        return Inertia::render('Router/Partials/Edit', [
            'router' => $router
        ]);
    }

    public function update(Request $request, $id)
    {
        $router = Router::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'ip_address' => 'required|string|ip',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
        ]);

        // If password is empty, use the existing password
        if (empty($validated['password'])) {
            $validated['password'] = $router->password;
        }

        // Test connection to router
        $connectionTest = $this->validateRouterConnection(
            $validated['ip_address'],
            $validated['username'],
            $validated['password'],
            $validated['port']
        );

        if (!$connectionTest['success']) {
            return back()->withErrors([
                'error' => 'Failed to connect to router: ' . $connectionTest['error']
            ])->withInput();
        }

        // Update router in database
        $router->update($validated);

        return redirect()->route('routers.index')
            ->with('success', 'Router updated successfully. Connected to: ' . $connectionTest['identity']);
    }

    public function destroy($id)
    {
        $router = Router::findOrFail($id);
        $router->delete();

        return redirect()->route('routers.index')
            ->with('success', 'Router deleted successfully.');
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

            return Inertia::render('Router/Partials/Overview', ['router' => $data]);
        } catch (Exception $e) {
            return Inertia::render('Router/Partials/Overview', [
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

    public function checkConnection($id)
    {
        $router = Router::findOrFail($id);
        
        $connectionTest = $this->validateRouterConnection(
            $router->ip_address,
            $router->username,
            $router->password,
            $router->port
        );

        return response()->json([
            'connected' => $connectionTest['success'],
            'message' => $connectionTest['success'] 
                ? 'Connected to: ' . $connectionTest['identity']
                : 'Failed to connect: ' . $connectionTest['error']
        ]);
    }
}