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
    protected function getMikroTikClient($router = null)
    {
        try {
            $config = $router ? [
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => (int) $router->port,
            ] : [
                'host' => env('MIKROTIK_HOST', '172.16.0.1'),
                'user' => env('MIKROTIK_USER', 'jtgadmin'),
                'pass' => env('MIKROTIK_PASSWORD', 'jtg@2025'),
                'port' => (int) env('MIKROTIK_PORT', 8728),
            ];

            Log::debug('Initializing MikroTik client', [
                'host' => $config['host'],
                'user' => $config['user'],
                'port' => $config['port'],
                'password' => strlen($config['pass']) > 0 ? '****' : 'empty'
            ]);

            $client = new Client($config);

            // Disable Safe Mode
            $query = new Query('/system/safe-mode/disable');
            $response = $client->query($query)->read();
            Log::debug('Safe Mode disable response', ['response' => $response]);
            if (isset($response['!trap'])) {
                throw new \Exception('Failed to disable Safe Mode: ' . ($response['message'] ?? 'Unknown error'));
            }

            return $client;
        } catch (Exception $e) {
            Log::error('Failed to initialize MikroTik client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new Exception('Failed to initialize MikroTik client: ' . $e->getMessage());
        }
    }

    public function validateConfigurationRequest(Request $request)
    {
        $rules = [
            'action' => 'required|in:add,update,delete,rename',
            'tab' => 'required|string|in:interfaces,ip-addresses,dhcp-client,dhcp-server,dns,firewall,hotspot,pools',
            'data' => 'required|array'
        ];

        $request->validate($rules);
    }

    public function index()
    {
        $routers = Router::all()->map(function($router) {
            try {
                $client = $this->getMikroTikClient($router);

                $interfaces = $client->query('/interface/print')->read();
                $ipAddresses = $client->query('/ip/address/print')->read();
                $dhcpClients = $client->query('/ip/dhcp-client/print')->read();
                $dhcpServers = $client->query('/ip/dhcp-server/print')->read();
                $dnsSettings = $client->query('/ip/dns/print')->read();
                $firewallRules = $client->query('/ip/firewall/filter/print')->read();
                $hotspotSettings = $client->query('/ip/hotspot/print')->read();
                $pools = $client->query('/ip/pool/print')->read();

                Log::debug('Fetched router data', [
                    'router_id' => $id,
                    'interfaces_count' => count($interfaces),
                    'pools_count' => count($pools)
                ]);

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
                Log::error('Failed to fetch router data', [
                    'router_id' => $router->id,
                    'error' => $e->getMessage()
                ]);
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
        })->toArray();

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
            $port = (int)$port;
            $client = new Client([
                'host' => $ip,
                'user' => $username,
                'pass' => $password,
                'port' => $port,
                'timeout' => 5,
            ]);

            $query = new Query('/system/identity/print');
            $response = $client->query($query)->read();
            Log::debug('Connection test response', ['response' => $response]);

            if (!is_array($response)) {
                throw new \Exception('Invalid response format from router');
            }

            if (empty($response)) {
                throw new \Exception('Empty response from router');
            }

            if (isset($response[0]['!trap'])) {
                $errorMessage = isset($response[0]['!trap']['message']) ? $response[0]['!trap']['message'] : 'Unknown error';
                throw new \Exception('Could not retrieve router identity: ' . $errorMessage);
            }

            return [
                'success' => true,
                'identity' => $response[0]['name'] ?? 'Unknown'
            ];
        } catch (\Exception $e) {
            Log::error('Router connection validation failed', [
                'ip' => $ip,
                'port' => $port,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function store(Request $request)
    {
        try {
            Log::info('Starting router creation', [
                'request_data' => $request->except(['password'])
            ]);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'ip_address' => 'required|string|ip',
                'username' => 'required|string|max:255',
                'password' => 'required|string|max:255',
                'port' => 'required|integer|min:1|max:65535',
            ]);

            $connectionTest = $this->validateRouterConnection(
                $validated['ip_address'],
                $validated['username'],
                $validated['password'],
                $validated['port']
            );

            if (!$connectionTest['success']) {
                Log::error('Router connection failed', [
                    'ip' => $validated['ip_address'],
                    'port' => $validated['port'],
                    'error' => $connectionTest['error']
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to connect to router: ' . $connectionTest['error']
                ], 422);
            }

            $router = Router::create($validated);

            Log::info('Router created successfully', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'identity' => $connectionTest['identity']
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Router created successfully. Connected to: ' . $connectionTest['identity'],
                    'router' => $router
                ]);
            }

            session()->flash('success', 'Router created successfully. Connected to: ' . $connectionTest['identity']);
            session()->flash('router_id', $router->id);

            return Inertia::render('Router/Partials/Create', [
                'router' => $router,
                'message' => 'Router created successfully. Connected to: ' . $connectionTest['identity'],
                'flash' => [
                    'success' => 'Router created successfully. Connected to: ' . $connectionTest['identity'],
                    'router_id' => $router->id
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Router creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to create router: ' . $e->getMessage()
                ], 500);
            }

            return back()->withErrors([
                'error' => 'Failed to create router: ' . $e->getMessage()
            ])->withInput();
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

        if (empty($validated['password'])) {
            $validated['password'] = $router->password;
        }

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

            Log::debug('Router overview data', ['data' => $data]);

            return Inertia::render('Router/Partials/Overview', ['router' => $data]);
        } catch (Exception $e) {
            Log::error('Router overview failed', ['error' => $e->getMessage()]);
            return Inertia::render('Router/Partials/Overview', [
                'error' => 'Failed to fetch overview: ' . $e->getMessage()
            ]);
        }
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
                ? 'Connected to router: ' . $connectionTest['identity']
                : 'Failed to connect: ' . $connectionTest['error']
        ]);
    }

    public function getInterfaces($id)
    {
        $router = Router::findOrFail($id);
        try {
            $client = $this->getMikroTikClient($router);
            $query = new Query('/interface/print');
            $interfaces = $client->query($query)->read();
            Log::debug('Fetched interfaces', [
                'router_id' => $id,
                'interfaces' => $interfaces
            ]);

            $formattedInterfaces = array_map(function ($interface) {
                return [
                    'id' => $interface['.id'] ?? '',
                    'name' => $interface['name'] ?? '',
                    'type' => $interface['type'] ?? 'unknown',
                    'mac_address' => $interface['mac-address'] ?? '',
                    'running' => isset($interface['running']) ? ($interface['running'] === 'true') : false,
                    'disabled' => isset($interface['disabled']) ? ($interface['disabled'] === 'true') : false,
                    'comment' => $interface['comment'] ?? ''
                ];
            }, $interfaces);

            return response()->json([
                'success' => true,
                'interfaces' => $formattedInterfaces
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch interfaces', [
                'router_id' => $id,
                'error' => $e->getMessage()
            ]);
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
            'name' => 'required|string|max:255',
            'enabled' => 'boolean',
            'mtu' => 'nullable|integer|min:68|max:9000',
            'mac_address' => 'nullable|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $client = $this->getMikroTikClient($router);
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
            Log::debug('Create interface response', ['response' => $response]);

            if (isset($response['!trap'])) {
                throw new \Exception('RouterOS error: ' . ($response['message'] ?? 'Unknown error'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface created successfully',
                'interface_id' => $response['after']['ret'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create interface', [
                'router_id' => $id,
                'error' => $e->getMessage()
            ]);
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
            'name' => 'required|string|max:255',
            'enabled' => 'boolean',
            'mtu' => 'nullable|integer|min:68|max:9000',
            'mac_address' => 'nullable|regex:/^([0-9A-Fa-f]{2}[:-]){5}([0-9A-Fa-f]{2})$/',
            'comment' => 'nullable|string|max:255',
        ]);

        try {
            $client = $this->getMikroTikClient($router);
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

            $response = $client->query($query)->read();
            Log::debug('Update interface response', ['response' => $response]);

            if (isset($response['!trap'])) {
                throw new \Exception('RouterOS error: ' . ($response['message'] ?? 'Unknown error'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface updated successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update interface', [
                'router_id' => $id,
                'interface_id' => $interfaceId,
                'error' => $e->getMessage()
            ]);
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
            $client = $this->getMikroTikClient($router);
            $query = new Query('/interface/remove');
            $query->equal('.id', $interfaceId);

            $response = $client->query($query)->read();
            Log::debug('Delete interface response', ['response' => $response]);

            if (isset($response['!trap'])) {
                throw new \Exception('RouterOS error: ' . ($response['message'] ?? 'Unknown error'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete interface', [
                'router_id' => $id,
                'interface_id' => $interfaceId,
                'error' => $e->getMessage()
            ]);
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
            $client = $this->getMikroTikClient($router);
            $query = new Query('/interface/print');
            $query->where('.id', $interfaceId);
            $interface = $client->query($query)->read();

            if (empty($interface)) {
                throw new \Exception('Interface not found');
            }

            $currentStatus = $interface[0]['disabled'] ?? 'yes';
            $newStatus = $currentStatus === 'yes' ? 'no' : 'yes';

            $query = new Query('/interface/set');
            $query->equal('.id', $interfaceId);
            $query->equal('disabled', $newStatus);

            $response = $client->query($query)->read();
            Log::debug('Toggle interface status response', ['response' => $response]);

            if (isset($response['!trap'])) {
                throw new \Exception('RouterOS error: ' . ($response['message'] ?? 'Unknown error'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Interface status updated successfully',
                'enabled' => $newStatus === 'no'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to toggle interface status', [
                'router_id' => $id,
                'interface_id' => $interfaceId,
                'error' => $e->getMessage()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle interface status: ' . $e->getMessage()
            ], 500);
        }
    }

    public function configure(Request $request, $id)
    {
        try {
            Log::info('Starting router configuration', [
                'router_id' => $id,
                'request_data' => $request->all()
            ]);

            $router = Router::findOrFail($id);
            $client = $this->getMikroTikClient($router);

            // Test connection
            $testQuery = new Query('/system/identity/print');
            $identity = $client->query($testQuery)->read();
            Log::debug('Connection test response', ['response' => $identity]);
            if (empty($identity) || isset($identity['!trap'])) {
                throw new \Exception('Could not connect to router: ' . ($identity['!trap']['message'] ?? 'Unknown error'));
            }

            Log::info('Router connection verified', [
                'identity' => $identity[0]['name'] ?? 'Unknown'
            ]);

            $action = $request->input('action');
            $data = $request->input('data');
            $tab = $request->input('tab');

            $this->validateConfigurationRequest($request);

            Log::info('Processing configuration request', [
                'tab' => $tab,
                'action' => $action,
                'data' => $data
            ]);

            // Clear existing conflicting configurations
            $this->clearConflictingConfigurations($client, $tab, $data);

            $result = null;
            switch ($tab) {
                case 'interfaces':
                    $result = $this->handleInterfaceConfiguration($client, $action, $data);
                    break;
                case 'ip-addresses':
                    $result = $this->handleIpAddressConfiguration($client, $action, $data);
                    break;
                case 'dhcp-client':
                    $result = $this->handleDhcpClientConfiguration($client, $action, $data);
                    break;
                case 'dhcp-server':
                    $result = $this->handleDhcpServerConfiguration($client, $action, $data);
                    break;
                case 'dns':
                    $result = $this->handleDnsConfiguration($client, $action, $data);
                    break;
                case 'firewall':
                    $result = $this->handleFirewallConfiguration($client, $action, $data);
                    break;
                case 'hotspot':
                    $result = $this->handleHotspotConfiguration($client, $action, $data);
                    break;
                case 'pools':
                    $result = $this->handlePoolConfiguration($client, $action, $data);
                    break;
                default:
                    throw new \Exception("Invalid configuration tab: {$tab}");
            }

            Log::debug('Configuration result', ['result' => $result]);
            if (isset($result['data']['!trap'])) {
                throw new \Exception('RouterOS error: ' . ($result['data']['message'] ?? 'Unknown error'));
            }

            $verificationResult = $this->verifyConfiguration($client, $tab, $data);
            Log::info('Configuration verification result', [
                'tab' => $tab,
                'verification_result' => $verificationResult
            ]);

            if (!$verificationResult['success']) {
                throw new \Exception('Configuration verification failed: ' . $verificationResult['message']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuration applied and verified successfully',
                'data' => $result,
                'verification' => $verificationResult
            ]);

        } catch (\Exception $e) {
            Log::error('Router configuration failed', [
                'router_id' => $id,
                'tab' => $tab ?? 'unknown',
                'action' => $action ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Configuration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function clearConflictingConfigurations($client, $tab, $data)
    {
        try {
            Log::info('Clearing conflicting configurations', ['tab' => $tab, 'data' => $data]);

            switch ($tab) {
                case 'pools':
                    if (isset($data['name'])) {
                        $query = new Query('/ip/pool/print');
                        $query->where('name', $data['name']);
                        $existing = $client->query($query)->read();
                        if (!empty($existing)) {
                            $removeQuery = new Query('/ip/pool/remove');
                            $removeQuery->equal('.id', $existing[0]['.id']);
                            $response = $client->query($removeQuery)->read();
                            Log::debug('Removed existing pool', ['response' => $response]);
                            if (isset($response['!trap'])) {
                                throw new \Exception('Failed to remove existing pool: ' . ($response['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                    break;

                case 'ip-addresses':
                    if (isset($data['interface'])) {
                        $query = new Query('/ip/address/print');
                        $query->where('interface', $data['interface']);
                        $existing = $client->query($query)->read();
                        foreach ($existing as $addr) {
                            $removeQuery = new Query('/ip/address/remove');
                            $removeQuery->equal('.id', $addr['.id']);
                            $response = $client->query($removeQuery)->read();
                            Log::debug('Removed existing IP address', ['response' => $response]);
                            if (isset($response['!trap'])) {
                                throw new \Exception('Failed to remove existing IP address: ' . ($response['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                    break;

                case 'dhcp-client':
                    if (isset($data['interface'])) {
                        $query = new Query('/ip/dhcp-client/print');
                        $query->where('interface', $data['interface']);
                        $existing = $client->query($query)->read();
                        foreach ($existing as $client) {
                            $removeQuery = new Query('/ip/dhcp-client/remove');
                            $removeQuery->equal('.id', $client['.id']);
                            $response = $client->query($removeQuery)->read();
                            Log::debug('Removed existing DHCP client', ['response' => $response]);
                            if (isset($response['!trap'])) {
                                throw new \Exception('Failed to remove existing DHCP client: ' . ($response['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                    break;

                case 'dhcp-server':
                    if (isset($data['interface'])) {
                        $query = new Query('/ip/dhcp-server/print');
                        $query->where('interface', $data['interface']);
                        $existing = $client->query($query)->read();
                        foreach ($existing as $server) {
                            $removeQuery = new Query('/ip/dhcp-server/remove');
                            $removeQuery->equal('.id', $server['.id']);
                            $response = $client->query($removeQuery)->read();
                            Log::debug('Removed existing DHCP server', ['response' => $response]);
                            if (isset($response['!trap'])) {
                                throw new \Exception('Failed to remove existing DHCP server: ' . ($response['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                    break;

                case 'hotspot':
                    if (isset($data['interface'])) {
                        $query = new Query('/ip/hotspot/print');
                        $query->where('interface', $data['interface']);
                        $existing = $client->query($query)->read();
                        foreach ($existing as $hotspot) {
                            $removeQuery = new Query('/ip/hotspot/remove');
                            $removeQuery->equal('.id', $hotspot['.id']);
                            $response = $client->query($removeQuery)->read();
                            Log::debug('Removed existing hotspot', ['response' => $response]);
                            if (isset($response['!trap'])) {
                                throw new \Exception('Failed to remove existing hotspot: ' . ($response['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                    break;

                case 'firewall':
                    if (isset($data['chain']) && isset($data['out_interface'])) {
                        $query = new Query('/ip/firewall/nat/print');
                        $query->where('chain', $data['chain']);
                        $query->where('out-interface', $data['out_interface']);
                        $existing = $client->query($query)->read();
                        foreach ($existing as $rule) {
                            $removeQuery = new Query('/ip/firewall/nat/remove');
                            $removeQuery->equal('.id', $rule['.id']);
                            $response = $client->query($removeQuery)->read();
                            Log::debug('Removed existing NAT rule', ['response' => $response]);
                            if (isset($response['!trap'])) {
                                throw new \Exception('Failed to remove existing NAT rule: ' . ($response['message'] ?? 'Unknown error'));
                            }
                        }
                    }
                    break;

                case 'dns':
                    $query = new Query('/ip/dns/set');
                    $query->equal('servers', '');
                    $query->equal('allow-remote-requests', 'no');
                    $response = $client->query($query)->read();
                    Log::debug('Reset DNS settings', ['response' => $response]);
                    if (isset($response['!trap'])) {
                        throw new \Exception('Failed to reset DNS settings: ' . ($response['message'] ?? 'Unknown error'));
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Failed to clear conflicting configurations', [
                'tab' => $tab,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function verifyConfiguration($client, $tab, $data)
    {
        try {
            Log::info('Verifying configuration', ['tab' => $tab, 'data' => $data]);

            switch ($tab) {
                case 'pools':
                    $query = new Query('/ip/pool/print');
                    if (isset($data['name'])) {
                        $query->where('name', $data['name']);
                    }
                    $pools = $client->query($query)->read();
                    Log::debug('Pool verification response', ['response' => $pools]);

                    if (empty($pools)) {
                        return [
                            'success' => false,
                            'message' => 'Pool not found after configuration'
                        ];
                    }
                    if (isset($data['ranges']) && $pools[0]['ranges'] !== $data['ranges']) {
                        return [
                            'success' => false,
                            'message' => 'Pool ranges do not match expected values: ' . $pools[0]['ranges']
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'Pool configuration verified',
                        'data' => $pools[0]
                    ];

                case 'interfaces':
                    $query = new Query('/interface/print');
                    if (isset($data['newName'])) {
                        $query->where('name', $data['newName']);
                    }
                    $interfaces = $client->query($query)->read();
                    Log::debug('Interface verification response', ['response' => $interfaces]);

                    if (empty($interfaces)) {
                        return [
                            'success' => false,
                            'message' => 'Interface not found after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'Interface configuration verified',
                        'data' => $interfaces[0]
                    ];

                case 'ip-addresses':
                    $query = new Query('/ip/address/print');
                    if (isset($data['interface']) && isset($data['address'])) {
                        $query->where('interface', $data['interface']);
                        $query->where('address', $data['address']);
                    }
                    $addresses = $client->query($query)->read();
                    Log::debug('IP address verification response', ['response' => $addresses]);

                    if (empty($addresses)) {
                        return [
                            'success' => false,
                            'message' => 'IP address not found after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'IP address configuration verified',
                        'data' => $addresses[0]
                    ];

                case 'dhcp-client':
                    $query = new Query('/ip/dhcp-client/print');
                    if (isset($data['interface'])) {
                        $query->where('interface', $data['interface']);
                    }
                    $dhcpClients = $client->query($query)->read();
                    Log::debug('DHCP client verification response', ['response' => $dhcpClients]);

                    if (empty($dhcpClients)) {
                        return [
                            'success' => false,
                            'message' => 'DHCP client not found after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'DHCP client configuration verified',
                        'data' => $dhcpClients[0]
                    ];

                case 'dhcp-server':
                    $query = new Query('/ip/dhcp-server/print');
                    if (isset($data['name'])) {
                        $query->where('name', $data['name']);
                    }
                    $dhcpServers = $client->query($query)->read();
                    Log::debug('DHCP server verification response', ['response' => $dhcpServers]);

                    if (empty($dhcpServers)) {
                        return [
                            'success' => false,
                            'message' => 'DHCP server not found after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'DHCP server configuration verified',
                        'data' => $dhcpServers[0]
                    ];

                case 'dns':
                    $query = new Query('/ip/dns/print');
                    $dnsSettings = $client->query($query)->read();
                    Log::debug('DNS verification response', ['response' => $dnsSettings]);

                    if (empty($dnsSettings) || (isset($data['servers']) && $dnsSettings[0]['servers'] !== $data['servers'])) {
                        return [
                            'success' => false,
                            'message' => 'DNS settings not found or incorrect after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'DNS configuration verified',
                        'data' => $dnsSettings[0]
                    ];

                case 'firewall':
                    $query = new Query('/ip/firewall/filter/print');
                    $firewallRules = $client->query($query)->read();
                    Log::debug('Firewall verification response', ['response' => $firewallRules]);

                    if (empty($firewallRules)) {
                        return [
                            'success' => false,
                            'message' => 'Firewall rules not found after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'Firewall configuration verified',
                        'data' => $firewallRules
                    ];

                case 'hotspot':
                    $query = new Query('/ip/hotspot/print');
                    if (isset($data['name'])) {
                        $query->where('name', $data['name']);
                    }
                    $hotspots = $client->query($query)->read();
                    Log::debug('Hotspot verification response', ['response' => $hotspots]);

                    if (empty($hotspots)) {
                        return [
                            'success' => false,
                            'message' => 'Hotspot not found after configuration'
                        ];
                    }
                    return [
                        'success' => true,
                        'message' => 'Hotspot configuration verified',
                        'data' => $hotspots[0]
                    ];

                default:
                    return [
                        'success' => false,
                        'message' => 'Verification not implemented for this tab'
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Configuration verification failed', [
                'tab' => $tab,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => 'Verification failed: ' . $e->getMessage()
            ];
        }
    }

    private function handleInterfaceConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling interface configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'rename':
                    $query = new Query('/interface/print');
                    $query->where('name', $data['oldName']);
                    $interface = $client->query($query)->read();
                    Log::debug('Interface lookup response', ['response' => $interface]);

                    if (empty($interface)) {
                        throw new \Exception("Interface {$data['oldName']} not found");
                    }

                    $query = new Query('/interface/set');
                    $query->equal('.id', $interface[0]['.id']);
                    $query->equal('name', $data['newName']);

                    Log::debug('Executing interface rename command', [
                        'command' => '/interface/set',
                        'parameters' => [
                            '.id' => $interface[0]['.id'],
                            'name' => $data['newName']
                        ]
                    ]);

                    $result = $client->query($query)->read();
                    Log::debug('Interface rename response', ['response' => $result]);

                    if (isset($result['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'Interface renamed successfully',
                        'data' => $result
                    ];

                default:
                    throw new \Exception('Invalid interface action');
            }
        } catch (\Exception $e) {
            Log::error('Interface configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleIpAddressConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling IP address configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'add':
                    $query = new Query('/ip/address/add');
                    $query->equal('interface', $data['interface']);
                    $query->equal('address', $data['address']);

                    Log::debug('Executing IP address add command', [
                        'command' => '/ip/address/add',
                        'parameters' => [
                            'interface' => $data['interface'],
                            'address' => $data['address']
                        ]
                    ]);

                    $result = $client->query($query)->read();
                    Log::debug('IP address add response', ['response' => $result]);

                    if (isset($result['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'IP address configured successfully',
                        'data' => $result
                    ];

                default:
                    throw new \Exception('Invalid IP address action');
            }
        } catch (\Exception $e) {
            Log::error('IP address configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleDhcpClientConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling DHCP client configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'add':
                    $query = new Query('/ip/dhcp-client/add');
                    $query->equal('interface', $data['interface']);
                    $query->equal('disabled', isset($data['disabled']) && $data['disabled'] ? 'yes' : 'no');

                    Log::debug('Executing DHCP client add command', [
                        'command' => '/ip/dhcp-client/add',
                        'parameters' => $data
                    ]);

                    $result = $client->query($query)->read();
                    Log::debug('DHCP client add response', ['response' => $result]);

                    if (isset($result['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'DHCP client configured successfully',
                        'data' => $result
                    ];

                default:
                    throw new \Exception('Invalid DHCP client action');
            }
        } catch (\Exception $e) {
            Log::error('DHCP client configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleDhcpServerConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling DHCP server configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'add':
                    $query = new Query('/ip/dhcp-server/add');
                    $query->equal('name', $data['name']);
                    $query->equal('interface', $data['interface']);
                    $query->equal('address-pool', $data['address_pool']);
                    $query->equal('lease-time', $data['lease_time'] ?? '10m');
                    $query->equal('disabled', isset($data['disabled']) && $data['disabled'] ? 'yes' : 'no');

                    $result = $client->query($query)->read();
                    Log::debug('DHCP server add response', ['response' => $result]);

                    if (isset($result['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    $networkQuery = new Query('/ip/dhcp-server/network/add');
                    $networkQuery->equal('address', $data['network_address']);
                    $networkQuery->equal('gateway', $data['gateway']);
                    $networkQuery->equal('dns-server', $data['dns_server'] ?? $data['gateway']);
                    $networkResponse = $client->query($networkQuery)->read();
                    Log::debug('DHCP server network add response', ['response' => $networkResponse]);

                    if (isset($networkResponse['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($networkResponse['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'DHCP server configured successfully',
                        'data' => $result
                    ];

                default:
                    throw new \Exception('Invalid DHCP server action');
            }
        } catch (\Exception $e) {
            Log::error('DHCP server configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleDnsConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling DNS configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'update':
                    $query = new Query('/ip/dns/set');
                    $query->equal('servers', $data['servers'] ?? '8.8.8.8,8.8.4.4');
                    $query->equal('allow-remote-requests', isset($data['allow_remote_requests']) && $data['allow_remote_requests'] ? 'yes' : 'no');

                    Log::debug('Executing DNS configuration command', [
                        'command' => '/ip/dns/set',
                        'parameters' => $data
                    ]);

                    $result = $client->query($query)->read();
                    Log::debug('DNS configuration response', ['response' => $result]);

                    if (isset($result['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'DNS settings configured successfully',
                        'data' => $result
                    ];

                default:
                    throw new \Exception('Invalid DNS action');
            }
        } catch (\Exception $e) {
            Log::error('DNS configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleFirewallConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling firewall configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'add':
                    $natQuery = new Query('/ip/firewall/nat/add');
                    $natQuery->equal('chain', $data['chain'] ?? 'srcnat');
                    $natQuery->equal('action', 'masquerade');
                    $natQuery->equal('out-interface', $data['out_interface']);
                    $natResponse = $client->query($natQuery)->read();
                    Log::debug('NAT rule add response', ['response' => $natResponse]);

                    if (isset($natResponse['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($natResponse['message'] ?? 'Unknown error'));
                    }
                    $rules = [
                        [
                            'chain' => 'input',
                            'action' => 'accept',
                            'connection-state' => 'established,related',
                            'comment' => 'Accept established and related'
                        ],
                        [
                            'chain' => 'input',
                            'action' => 'drop',
                            'accept',
                            'comment' => 'Drop all other input'
                        ]
                    ];

                    foreach ($rules as $rule) {
                        $ruleQuery = new Query('/ip/firewall/filter/add');
                        foreach ($rule as $key => $value) {
                            $ruleQuery->equal($row, $value);
                            }
                        $response = $client->query($ruleQuery)->read();
                        Log::debug('Firewall rule add response', ['response' => $response]);
                        if (isset($response['!trap'])) {
                            throw new \Exception('RouterOS error: ' . ($response['message'] ?? 'Unknown error'));
                        }
                    }

                    return [
                        'success' => true,
                        'message' => 'Firewall rules configured successfully',
                        'data' => []
                    ];

                default:
                    throw new \Exception('Invalid firewall action');
            }
        } catch (\Exception $e) {
            Log::error('Firewall configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleHotspotConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling hotspot configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'add':
                    $hotspotQuery = new Query('/ip/hotspot/add');
                    $hotspotQuery->equal('name', $data['name']);
                    $hotspotQuery->equal('interface', $data['interface']);
                    $hotspotQuery->equal('address-pool', $data['address_pool']);
                    $hotspotQuery->equal('disabled', isset($data['disabled']) && $data['disabled'] ? 'yes' : 'no');
                    $hotspotResponse = $client->query($hotspotQuery)->read();
                    Log::debug('Hotspot add response', ['response' => $hotspotResponse]);

                    if (isset($hotspotResponse['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($hotspotResponse['message'] ?? 'Unknown error'));
                    }

                    $profileQuery = new Query('/ip/hotspot/profile/add');
                    $profileQuery->equal('name', $data['profile_name'] ?? $data['name']);
                    $profileQuery->equal('login-by', $data['login_by'] ?? 'http-chap');
                    $profileResponse = $client->query($profileQuery)->read();
                    Log::debug('Hotspot profile add response', ['response' => $profileResponse]);

                    if (isset($profileResponse['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($profileResponse['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'Hotspot configured successfully',
                        'data' => []
                    ];

                default:
                    throw new \Exception('Invalid hotspot action');
            }
        } catch (\Exception $e) {
            Log::error('Hotspot configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handlePoolConfiguration($client, $action, $data)
    {
        try {
            Log::info('Handling pool configuration', [
                'action' => $action,
                'data' => $data
            ]);

            switch ($action) {
                case 'add':
                    $query = new Query('/ip/pool/add');
                    $query->equal('name', $data['name']);
                    $query->equal('ranges', $data['ranges']);

                    Log::debug('Executing pool configuration command', [
                        'command' => '/ip/pool/add',
                        'parameters' => $data
                    ]);

                    $result = $client->query($query)->read();
                    Log::debug('Pool configuration response', ['response' => $result]);

                    if (isset($result['!trap'])) {
                        throw new \Exception('RouterOS error: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    return [
                        'success' => true,
                        'message' => 'Pool configured successfully',
                        'data' => $result
                    ];

                default:
                    throw new \Exception('Invalid pool action');
            }
        } catch (\Exception $e) {
            Log::error('Pool configuration failed', [
                'action' => $action,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function addPool(Request $request, $id)
    {
        try {
            Log::info('Starting addPool request', [
                'router_id' => $id,
                'request_data' => $request->all()
            ]);

            $router = Router::findOrFail($id);
            $client = $this->getMikroTikClient($router);

            // Test connection
            $testQuery = new Query('/system/identity/print');
            $identity = $client->query($testQuery)->read();
            Log::debug('Connection test response', ['response' => $identity]);
            if (isset($identity['!trap'])) {
                throw new \Exception('Connection test failed: ' . ($identity['message'] ?? 'Unknown error'));
            }
            Log::info('Router connection test successful', ['identity' => $identity[0]['name'] ?? 'Unknown']);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'ranges' => 'required|string|regex:/^([0-9]{1,3}\.){3}[0-9]{1,3}-([0-9]{1,3}\.){3}[0-9]{1,3}$/'
            ]);

            Log::debug('Validated pool data', ['validated_data' => $validated]);

            // Clear existing pool
            $this->clearConflictingConfigurations($client, 'pools', $validated);

            $result = $this->handlePoolConfiguration($client, 'add', $validated);

            // Verify configuration
            $verificationResult = $this->verifyConfiguration($client, 'pools', $validated);
            Log::info('Pool configuration verification result', ['verification_result' => $verificationResult]);

            if (!$verificationResult['success']) {
                throw new \Exception('Pool verification failed: ' . $verificationResult['message']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Address pool added successfully',
                'data' => $result,
                'verification' => $verificationResult
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add address pool', [
                'router_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add address pool: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updatePool(Request $request, $id, $poolId)
    {
        try {
            Log::info('Starting updatePool request', [
                'router_id' => $id,
                'pool_id' => $poolId,
                'request_data' => $request->all()
            ]);

            $router = Router::findOrFail($id);
            $client = $client = $this->getMikroTikClient($router);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'ranges' => 'required|string|regex:/^([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})-([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})$/'
            ]);

            $data = array_merge(['.id' => $poolId], $validated);

            // Clear existing pool
            $client->clearConflictingConfigurations($client, 'pools', $validated);

            $result = $this->handlePoolConfiguration($client, 'add', $data);

            $verificationResult = $this->verifyConfiguration($client, 'pools', $data);
            Log::info('Pool update verification result', [
                'verification_result' => $verificationResult
            ]);

            if (!verificationResult['success']) {
                throw new \Exception('Pool update verification failed: ' . $verificationResult['message']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Address pool updated successfully',
                'data' => $result,
                'verification' => $verificationResult
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update address pool', [
                'router_id' => $id,
                'pool_id' => $poolId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update address pool: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deletePool($id, $poolId)
    {
        try {
            Log::info('Starting deletePool request', [
                'router_id' => $id,
                'pool_id' => $poolId
            ]);

            $router = Router::findOrFail($id);
            $client = $this->getMikroTikClient($router);

            $query = new Query('/ip/pool/remove');
            $query->equal('.id', $poolId);
            $response = $client->query($query)->read();
            Log::debug('Delete pool response', ['response' => $response]);

            if (isset($response['!trap'])) {
                throw new \Exception('RouterOS error: ' . ($response['message'] ?? 'Unknown error'));
            }

            return response()->json([
                'success' => true,
                'message' => 'Address pool deleted successfully',
                'data' => []
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete address pool', [
                'router_id' => $id,
                'pool_id' => $poolId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete address pool: ' . $e->getMessage()
            ], 500);
        }
    }

    public function addDhcpServer(Request $request, $id)
    {
        try {
            Log::info('Adding DHCP server request', [
                'router_id' => $id,
                'request_data' => $request->all()
            ]);

            $router = Router::findOrFail($id);
            $client = $this->getMikroTikClient($router);

            // Test connection
            $testQuery = new Query('/system/identity/print');
            $identity = $client->query($testQuery)->read();
            Log::debug('Connection test response', ['response' => $identity]);
            if (isset($identity['!trap'])) {
                throw new \Exception('Connection test failed: ' . ($identity['message'] ?? 'Unknown error'));
            }
            Log::info('Router connection test successful', ['identity' => $identity[0]['name'] ?? 'Unknown']);

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'interface' => 'required|string|max:255',
                'address_pool' => 'required|string|max:255',
                'lease_time' => 'nullable|string|regex:/^[0-9]+[smh]$/',
                'network_address' => 'required|string|ip',
                'gateway' => 'required|string|ip',
                'dns_server' => 'nullable|string|ip'
            ]);

            Log::debug('Validated DHCP server data', ['validated_data' => $validated]);

            // Clear existing DHCP server
            $this->clearConflictingConfigurations($client, 'dhcp-server', $validated);

            $result = $this->handleDhcpServerConfiguration($client, 'add', $validated);

            // Verify configuration
            $verificationResult = $this->verifyConfiguration($client, 'dhcp-server', $validated);
            Log::info('DHCP server configuration verification result', ['verification_result' => $verificationResult]);

            if (!$verificationResult['success']) {
                throw new \Exception('DHCP server verification failed: ' . $verificationResult['message']);
            }

            return response()->json([
                'success' => true,
                'message' => 'DHCP server added successfully',
                'data' => $result,
                'verification' => $verificationResult
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add DHCP server', [
                'router_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add DHCP server: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getDhcpServers($id)
    {
        try {
            $router = Router::findOrFail($id);
            $client = $this->getMikroTikClient($router);
            $query = new Query('/ip/dhcp-server/print');
            $servers = $client->query($query)->read();
            Log::debug('Fetched DHCP servers', ['router_id' => $id, 'servers' => $servers]);

            return response()->json([
                'success' => true,
                'servers' => $servers
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fetch DHCP servers', [
                'router_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch DHCP servers: ' . $e->getMessage()
            ], 500);
        }
    }

    public function resetConfiguration(Request $request, $id)
    {
        try {
            Log::info('Starting resetConfiguration request', [
                'router_id' => $id
            ]);

            $router = Router::findOrFail($id);
            $validated = $request->validate([
                'password' => 'required'
            ]);

            if (!Hash::check($validated['password'], auth()->user()->password)) {
                Log::error('Invalid password for reset configuration', ['router_id' => $id]);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid password'
                ], 403);
            }

            $client = $this->getMikroTikClient($router);

            // Remove DHCP clients
            $dhcpClients = $client->query('/ip/dhcp-client/print')->read();
            foreach ($dhcpClients as $client) {
                $query = new Query('/ip/dhcp-client/remove');
                $query->equal('.id', $client['.id']);
                $response = $client->query($query)->read();
                Log::debug('Removed DHCP client', ['response' => $response]);
            }

            // Remove DHCP servers
            $dhcpServers = $client->query('/ip/dhcp-server/print')->read();
            foreach ($dhcpServers as $server) {
                $query = new Query('/ip/dhcp-server/remove');
                $query->equal('.id', $server['.id']);
                $response = $client->query($query)->read();
                Log::debug('Removed DHCP server', ['response' => $response]);
            }

            // Reset DNS
            $query = new Query('/ip/dns/set');
            $query->equal('servers', '8.8.8.8,8.8.4.4');
            $query->equal('allow-remote-requests', 'no');
            $response = $client->query($query)->read();
            Log::debug('Reset DNS', ['response' => $response]);

            // Remove firewall rules
            $firewallRules = $client->query('/ip/firewall/filter/print')->read();
            foreach ($firewallRules as $rule) {
                $query = new Query('/ip/firewall/filter/remove');
                $query->equal('.id', $rule['.id']);
                $response = $client->query($query)->read();
                Log::debug('Removed firewall rule', ['response' => $response]);
            }

            // Remove hotspots
            $hotspots = $client->query('/ip/hotspot/print')->read();
            foreach ($hotspots as $hotspot) {
                $query = new Query('/ip/hotspot/remove');
                $query->equal('.id', $hotspot['.id']);
                $response = $client->query($query)->read();
                Log::debug('Removed hotspot', ['response' => $response]);
            }

            // Remove pools
            $pools = $client->query('/ip/pool/print')->read();
            foreach ($pools as $pool) {
                $query = new Query('/ip/pool/remove');
                $query->equal('.id', $pool['.id']);
                $response = $client->query($query)->read();
                Log::debug('Removed pool', ['response' => $response]);
            }

            // Remove IP addresses
            $ipAddresses = $client->query('/ip/address/print')->read();
            foreach ($ipAddresses as $addr) {
                $query = new Query('/ip/address/remove');
                $query->equal('.id', $addr['.id']);
                $response = $client->query($query)->read();
                Log::debug('Removed IP address', ['response' => $response]);
            }

            Log::info('Router configuration reset successfully', ['router_id' => $id]);

            return response()->json([
                'success' => true,
                'message' => 'Router configuration reset successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reset router configuration', [
                'router_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to reset configuration: ' . $e->getMessage()
            ], 500);
        }
    }
}