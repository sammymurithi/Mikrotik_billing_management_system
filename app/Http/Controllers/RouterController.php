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
use Illuminate\Support\Facades\Hash;

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
        $routers = Router::all()->map(function ($router) {
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => (int) $router->port,
                ]);

                // Fetch interfaces
                $interfaces = $client->query('/interface/print')->read();

                // Fetch IP addresses
                $ipAddresses = $client->query('/ip/address/print')->read();

                // Fetch DHCP client configurations
                $dhcpClients = $client->query('/ip/dhcp-client/print')->read();

                // Fetch DHCP servers
                $dhcpServers = $client->query('/ip/dhcp-server/print')->read();

                // Fetch DNS settings
                $dnsSettings = $client->query('/ip/dns/print')->read();

                // Fetch Firewall rules
                $firewallRules = $client->query('/ip/firewall/filter/print')->read();

                // Fetch Hotspot settings
                $hotspotSettings = $client->query('/ip/hotspot/print')->read();

                // Fetch IP Pools
                $pools = $client->query('/ip/pool/print')->read();

                return [
                    'id' => $router->id,
                    'name' => $router->name,
                    'ip_address' => $router->ip_address,
                    'username' => $router->username,
                    'password' => $router->password,
                    'port' => $router->port,
                    'interfaces' => $interfaces,
                    'ipAddresses' => $ipAddresses,
                    'dhcpClients' => $dhcpClients,
                    'dhcpServers' => $dhcpServers,
                    'dnsSettings' => $dnsSettings[0] ?? [],
                    'firewallRules' => $firewallRules,
                    'hotspotSettings' => $hotspotSettings[0] ?? [],
                    'pools' => $pools,
                ];
            } catch (\Exception $e) {
                return [
                    'id' => $router->id,
                    'name' => $router->name,
                    'ip_address' => $router->ip_address,
                    'username' => $router->username,
                    'password' => $router->password,
                    'port' => $router->port,
                    'interfaces' => [],
                    'ipAddresses' => [],
                    'dhcpClients' => [],
                    'dhcpServers' => [],
                    'dnsSettings' => [],
                    'firewallRules' => [],
                    'hotspotSettings' => [],
                    'pools' => [],
                    'error' => $e->getMessage()
                ];
            }
        });

        return inertia('Router/Index', [
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
        
        // Add debug logging
        Log::debug('Router created successfully', [
            'router_id' => $router->id,
            'router_name' => $router->name,
            'identity' => $connectionTest['identity']
        ]);
        
        // For Inertia requests, return a proper Inertia response
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Router created successfully. Connected to: ' . $connectionTest['identity'],
                'router' => $router
            ]);
        } else {
            // Flash data to the session
            session()->flash('success', 'Router created successfully. Connected to: ' . $connectionTest['identity']);
            session()->flash('router_id', $router->id);
            
            // Return an Inertia response
            return Inertia::render('Router/Partials/Create', [
                'router' => $router,
                'message' => 'Router created successfully. Connected to: ' . $connectionTest['identity'],
                'flash' => [
                    'success' => 'Router created successfully. Connected to: ' . $connectionTest['identity'],
                    'router_id' => $router->id
                ]
            ]);
        }
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

    /**
     * Get all interfaces for a router
     */
    public function getInterfaces($id)
    {
        $router = Router::findOrFail($id);
        
        try {
            $client = $this->getMikroTikClient($router);
            $query = new Query('/interface/print');
            $interfaces = $client->query($query)->read();
            
            // Format the interfaces for the frontend
            $formattedInterfaces = [];
            foreach ($interfaces as $interface) {
                $formattedInterfaces[] = [
                    'id' => $interface['.id'] ?? '',
                    'name' => $interface['name'] ?? '',
                    'type' => $interface['type'] ?? '',
                    'mac_address' => $interface['mac-address'] ?? '',
                    'running' => isset($interface['running']) ? ($interface['running'] === 'true') : false,
                    'disabled' => isset($interface['disabled']) ? ($interface['disabled'] === 'true') : false,
                    'comment' => $interface['comment'] ?? ''
                ];
            }
            
            return response()->json([
                'success' => true,
                'interfaces' => $formattedInterfaces
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch interfaces: ' . $e->getMessage()
            ], 500);
        }
    }

    public function createInterface(Request $request, $id)
    {
        $router = Router::findOrFail($id);
        $validated = $request->validate([
            'type' => 'required|string',
            'name' => 'required|string',
            'enabled' => 'boolean',
            'mtu' => 'nullable|integer|min:68|max:9000',
            'mac_address' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/interface/add');
            $query->equal('type', $validated['type']);
            $query->equal('name', $validated['name']);
            $query->equal('disabled', $validated['enabled'] ? 'no' : 'yes');

            if (!empty($validated['mtu'])) {
                $query->equal('mtu', $validated['mtu']);
            }
            if (!empty($validated['mac_address'])) {
                $query->equal('mac-address', $validated['mac_address']);
            }
            if (!empty($validated['comment'])) {
                $query->equal('comment', $validated['comment']);
            }

            $response = $client->query($query)->read();

            if (isset($response['after']['ret'])) {
                return response()->json([
                    'success' => true,
                    'message' => 'Interface created successfully',
                    'interface_id' => $response['after']['ret']
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create interface'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create interface: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateInterface(Request $request, $id, $interfaceId)
    {
        $router = Router::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string',
            'enabled' => 'boolean',
            'mtu' => 'nullable|integer|min:68|max:9000',
            'mac_address' => 'nullable|string|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/interface/set');
            $query->equal('.id', $interfaceId);
            $query->equal('name', $validated['name']);
            $query->equal('disabled', $validated['enabled'] ? 'no' : 'yes');

            if (!empty($validated['mtu'])) {
                $query->equal('mtu', $validated['mtu']);
            }
            if (!empty($validated['mac_address'])) {
                $query->equal('mac-address', $validated['mac_address']);
            }
            if (!empty($validated['comment'])) {
                $query->equal('comment', $validated['comment']);
            }

            $client->query($query)->read();

            return response()->json([
                'success' => true,
                'message' => 'Interface updated successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update interface: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deleteInterface($id, $interfaceId)
    {
        $router = Router::findOrFail($id);

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/interface/remove');
            $query->equal('.id', $interfaceId);

            $client->query($query)->read();

            return response()->json([
                'success' => true,
                'message' => 'Interface deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete interface: ' . $e->getMessage()
            ], 500);
        }
    }

    public function toggleInterfaceStatus($id, $interfaceId)
    {
        $router = Router::findOrFail($id);

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            // First get the current status
            $query = new Query('/interface/print');
            $query->where('.id', $interfaceId);
            $interface = $client->query($query)->read();

            if (empty($interface)) {
                throw new \Exception('Interface not found');
            }

            $currentStatus = $interface[0]['disabled'] ?? 'yes';
            $newStatus = $currentStatus === 'yes' ? 'no' : 'yes';

            // Update the status
            $query = new Query('/interface/set');
            $query->equal('.id', $interfaceId);
            $query->equal('disabled', $newStatus);

            $client->query($query)->read();

            return response()->json([
                'success' => true,
                'message' => 'Interface status updated successfully',
                'enabled' => $newStatus === 'no'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update interface status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function configure(Request $request, Router $router, $tab)
    {
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port
            ]);

            $action = $request->input('action', 'add');
            $data = $request->all();

            switch ($tab) {
                case 'interfaces':
                    return $this->handleInterfaceConfiguration($client, $action, $data);
                case 'addresses':
                    return $this->handleAddressConfiguration($client, $action, $data);
                case 'dhcp-client':
                    return $this->handleDhcpClientConfiguration($client, $action, $data);
                case 'dhcp-server':
                    return $this->handleDhcpServerConfiguration($client, $action, $data);
                case 'dns-settings':
                    return $this->handleDnsConfiguration($client, $action, $data);
                case 'firewall':
                    return $this->handleFirewallConfiguration($client, $action, $data);
                case 'hotspot-server':
                    return $this->handleHotspotConfiguration($client, $action, $data);
                case 'pool':
                    return $this->handlePoolConfiguration($client, $action, $data);
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid tab specified'
                    ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    private function handleInterfaceConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/interface/add', [
                        'name' => $data['name'],
                        'type' => $data['type'],
                        'disabled' => !$data['enabled']
                    ]);
                    break;
                case 'update':
                    $client->write('/interface/set', [
                        '.id' => $data['selectedInterface'],
                        'name' => $data['newName'],
                        'disabled' => !$data['enabled']
                    ]);
                    break;
                case 'delete':
                    $client->write('/interface/remove', [
                        '.id' => $data['interface']
                    ]);
                    break;
                case 'enable':
                case 'disable':
                    $client->write('/interface/' . $action, [
                        '.id' => $data['interface']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating interface: ' . $e->getMessage()
            ]);
        }
    }

    private function handleAddressConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/ip/address/add', [
                        'interface' => $data['interface'],
                        'address' => $data['address'],
                        'network' => $data['network']
                    ]);
                    break;
                case 'update':
                    $client->write('/ip/address/set', [
                        '.id' => $data['id'],
                        'interface' => $data['interface'],
                        'address' => $data['address'],
                        'network' => $data['network']
                    ]);
                    break;
                case 'delete':
                    $client->write('/ip/address/remove', [
                        '.id' => $data['id']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Address configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating address: ' . $e->getMessage()
            ]);
        }
    }

    private function handleDhcpClientConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/ip/dhcp-client/add', [
                        'interface' => $data['interface'],
                        'use-peer-dns' => $data['use_peer_dns'],
                        'add-default-route' => $data['add_default_route']
                    ]);
                    break;
                case 'update':
                    $client->write('/ip/dhcp-client/set', [
                        '.id' => $data['id'],
                        'interface' => $data['interface'],
                        'use-peer-dns' => $data['use_peer_dns'],
                        'add-default-route' => $data['add_default_route']
                    ]);
                    break;
                case 'delete':
                    $client->write('/ip/dhcp-client/remove', [
                        '.id' => $data['id']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'DHCP client configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating DHCP client: ' . $e->getMessage()
            ]);
        }
    }

    private function handleDhcpServerConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/ip/dhcp-server/add', [
                        'interface' => $data['interface'],
                        'address-pool' => $data['addressPool'],
                        'gateway' => $data['gateway'],
                        'dns-server' => $data['dnsServers'],
                        'lease-time' => $data['leaseTime']
                    ]);
                    break;
                case 'update':
                    $client->write('/ip/dhcp-server/set', [
                        '.id' => $data['id'],
                        'interface' => $data['interface'],
                        'address-pool' => $data['addressPool'],
                        'gateway' => $data['gateway'],
                        'dns-server' => $data['dnsServers'],
                        'lease-time' => $data['leaseTime']
                    ]);
                    break;
                case 'delete':
                    $client->write('/ip/dhcp-server/remove', [
                        '.id' => $data['id']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'DHCP server configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating DHCP server: ' . $e->getMessage()
            ]);
        }
    }

    private function handleDnsConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'update':
                    $client->write('/ip/dns/set', [
                        'servers' => $data['servers'],
                        'allow-remote-requests' => $data['allowRemoteRequests']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'DNS configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating DNS settings: ' . $e->getMessage()
            ]);
        }
    }

    private function handleFirewallConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/ip/firewall/filter/add', [
                        'chain' => $data['chain'],
                        'action' => $data['action'],
                        'src-address' => $data['src_address'],
                        'dst-address' => $data['dst_address'],
                        'protocol' => $data['protocol'],
                        'comment' => $data['comment']
                    ]);
                    break;
                case 'update':
                    $client->write('/ip/firewall/filter/set', [
                        '.id' => $data['id'],
                        'chain' => $data['chain'],
                        'action' => $data['action'],
                        'src-address' => $data['src_address'],
                        'dst-address' => $data['dst_address'],
                        'protocol' => $data['protocol'],
                        'comment' => $data['comment']
                    ]);
                    break;
                case 'delete':
                    $client->write('/ip/firewall/filter/remove', [
                        '.id' => $data['id']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Firewall configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating firewall: ' . $e->getMessage()
            ]);
        }
    }

    private function handleHotspotConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/ip/hotspot/add', [
                        'interface' => $data['interface'],
                        'address-pool' => $data['addressPool'],
                        'dns-name' => $data['dnsName'],
                        'ssl-certificate' => $data['sslCertificate'],
                        'use-default-login-page' => $data['useDefaultLoginPage']
                    ]);
                    break;
                case 'update':
                    $client->write('/ip/hotspot/set', [
                        '.id' => $data['id'],
                        'interface' => $data['interface'],
                        'address-pool' => $data['addressPool'],
                        'dns-name' => $data['dnsName'],
                        'ssl-certificate' => $data['sslCertificate'],
                        'use-default-login-page' => $data['useDefaultLoginPage']
                    ]);
                    break;
                case 'delete':
                    $client->write('/ip/hotspot/remove', [
                        '.id' => $data['id']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Hotspot configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating hotspot: ' . $e->getMessage()
            ]);
        }
    }

    private function handlePoolConfiguration($client, $action, $data)
    {
        try {
            switch ($action) {
                case 'add':
                    $client->write('/ip/pool/add', [
                        'name' => $data['name'],
                        'ranges' => $data['ranges'],
                        'next-pool' => $data['next_pool']
                    ]);
                    break;
                case 'update':
                    $client->write('/ip/pool/set', [
                        '.id' => $data['id'],
                        'name' => $data['name'],
                        'ranges' => $data['ranges'],
                        'next-pool' => $data['next_pool']
                    ]);
                    break;
                case 'delete':
                    $client->write('/ip/pool/remove', [
                        '.id' => $data['id']
                    ]);
                    break;
            }

            return response()->json([
                'success' => true,
                'message' => 'Pool configuration updated successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating pool: ' . $e->getMessage()
            ]);
        }
    }

    public function resetConfiguration(Request $request, $id)
    {
        $router = Router::findOrFail($id);
        
        // Validate the request
        $validated = $request->validate([
            'password' => 'required|string'
        ]);

        // Verify the user's password
        if (!Hash::check($validated['password'], auth()->user()->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid password'
            ], 403);
        }
        
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port
            ]);

            // Reset DHCP client
            $dhcpClients = $client->query('/ip/dhcp-client/print')->read();
            foreach ($dhcpClients as $dhcpClient) {
                $client->write('/ip/dhcp-client/remove', ['.id' => $dhcpClient['.id']]);
            }

            // Reset DHCP server
            $dhcpServers = $client->query('/ip/dhcp-server/print')->read();
            foreach ($dhcpServers as $dhcpServer) {
                $client->write('/ip/dhcp-server/remove', ['.id' => $dhcpServer['.id']]);
            }

            // Reset DNS settings to default
            $client->write('/ip/dns/set', [
                'servers' => '8.8.8.8,8.8.4.4',
                'allow-remote-requests' => 'no'
            ]);

            // Reset Firewall rules
            $firewallRules = $client->query('/ip/firewall/filter/print')->read();
            foreach ($firewallRules as $rule) {
                $client->write('/ip/firewall/filter/remove', ['.id' => $rule['.id']]);
            }

            // Reset Hotspot
            $hotspots = $client->query('/ip/hotspot/print')->read();
            foreach ($hotspots as $hotspot) {
                $client->write('/ip/hotspot/remove', ['.id' => $hotspot['.id']]);
            }

            // Reset IP Pools
            $pools = $client->query('/ip/pool/print')->read();
            foreach ($pools as $pool) {
                $client->write('/ip/pool/remove', ['.id' => $pool['.id']]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Router configuration has been reset to default settings'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reset router configuration: ' . $e->getMessage()
            ], 500);
        }
    }
}