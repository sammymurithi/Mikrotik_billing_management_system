<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\HotspotProfile;
use App\Models\Router;
use App\Models\HotspotSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;

class HotspotUserController extends Controller
{
    /**
     * Display a listing of hotspot users.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $routers = Router::all();
        \Log::info('Fetching hotspot users from ' . $routers->count() . ' routers');
        
        $hotspotUsers = [];
        $activeSessions = [];

        foreach ($routers as $router) {
            \Log::info("Processing router: {$router->name} ({$router->ip_address})");
            
            try {
                \Log::info("Attempting to connect to router {$router->name} at {$router->ip_address}:{$router->port}");
                
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                ]);

                // Test connection first
                $testQuery = new Query('/system/identity/print');
                $identity = $client->query($testQuery)->read();
                \Log::info("Successfully connected to router. Identity: " . json_encode($identity));

                // Get all hotspot users
                $query = new Query('/ip/hotspot/user/print');
                $users = $client->query($query)->read();
                \Log::info("Found " . count($users) . " total hotspot users on router {$router->name}");
                \Log::debug("Users data:", $users);

                // Get active sessions
                $query = new Query('/ip/hotspot/active/print');
                $sessions = $client->query($query)->read();
                \Log::info("Found " . count($sessions) . " active sessions on router {$router->name}");
                \Log::debug("Active sessions data:", $sessions);

                // Store active sessions
                foreach ($sessions as $session) {
                    \Log::debug("Processing session for user: " . ($session['user'] ?? 'unknown'), [
                        'session_data' => $session
                    ]);
                    
                    $activeSessions[$session['user']] = [
                        'uptime' => $session['uptime'] ?? '',
                        'bytes_in' => $session['bytes-in'] ?? 0,
                        'bytes_out' => $session['bytes-out'] ?? 0,
                        'packets_in' => $session['packets-in'] ?? 0,
                        'packets_out' => $session['packets-out'] ?? 0,
                    ];
                }

                foreach ($users as $user) {
                    $username = $user['name'];
                    $isConnected = isset($activeSessions[$username]);
                    
                    \Log::debug("Processing user: {$username}", [
                        'user_data' => $user,
                        'is_connected' => $isConnected,
                        'session_data' => $isConnected ? $activeSessions[$username] : null
                    ]);

                    // Get MAC address with proper fallback
                    $macAddress = '';
                    if (isset($user['mac-address']) && !empty($user['mac-address'])) {
                        $macAddress = $user['mac-address'];
                    } elseif (isset($user['mac_address']) && !empty($user['mac_address'])) {
                        $macAddress = $user['mac_address'];
                    }

                    $hotspotUsers[] = [
                        'id' => $user['.id'],
                        'username' => $username,
                        'password' => $user['password'] ?? '',
                        'mac_address' => $macAddress,
                        'profile' => $user['profile'] ?? '',
                        'router_id' => $router->id,
                        'router_name' => $router->name,
                        'disabled' => isset($user['disabled']) ? ($user['disabled'] === 'true' || $user['disabled'] === true) : false,
                        'expires_at' => $user['limit-uptime'] ?? null,
                        'is_connected' => $isConnected,
                        'session' => $activeSessions[$username] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                \Log::error("Failed to fetch hotspot users from router {$router->name}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'router_details' => [
                        'ip' => $router->ip_address,
                        'port' => $router->port,
                        'username' => $router->username
                    ]
                ]);
                continue;
            }
        }

        \Log::info("Returning " . count($hotspotUsers) . " total hotspot users to view");
        \Log::debug("Final hotspot users data:", $hotspotUsers);

        return Inertia::render('Hotspot/User/Index', [
            'hotspotUsers' => $hotspotUsers,
        ]);
    }

    /**
     * Show the form for creating a new hotspot user.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $profiles = HotspotProfile::all();
        $routers = Router::all();
        
        return Inertia::render('Hotspot/User/Create', [
            'profiles' => $profiles,
            'routers' => $routers
        ]);
    }

    /**
     * Store a newly created hotspot user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:hotspot_users',
            'password' => 'required|string|min:6',
            'profile_id' => 'required|exists:hotspot_profiles,id',
            'router_id' => 'required|exists:routers,id',
            'mac_address' => 'nullable|string',
            'expires_at' => 'nullable|date',
        ]);
        
        // Create user in database
        $hotspotUser = HotspotUser::create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'profile_id' => $validated['profile_id'],
            'router_id' => $validated['router_id'],
            'mac_address' => $validated['mac_address'] ?? null,
            'is_active' => true,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);
        
        // Connect to router and create hotspot user
        $router = Router::findOrFail($validated['router_id']);
        $profile = HotspotProfile::findOrFail($validated['profile_id']);
        
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => 8728,
            ]);
            
            $query = new Query('/ip/hotspot/user/add');
            $query->equal('name', $validated['username']);
            $query->equal('password', $validated['password']);
            $query->equal('profile', $profile->name);
            
            if (!empty($validated['mac_address'])) {
                $query->equal('mac-address', $validated['mac_address']);
            }
            
            if (!empty($validated['expires_at'])) {
                $query->equal('limit-uptime', '30d');
            }
            
            $client->query($query)->read();
            
        } catch (\Exception $e) {
            // Log error but continue - we'll handle sync later
            \Log::error('Failed to create hotspot user on router: ' . $e->getMessage());
        }

        return redirect()->route('hotspot.users.index')
            ->with('success', 'Hotspot user created successfully.');
    }

    /**
     * Display the specified hotspot user.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        
        return Inertia::render('Hotspot/User/Show', [
            'hotspotUser' => $hotspotUser
        ]);
    }

    /**
     * Show the form for editing the specified hotspot user.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        $profiles = HotspotProfile::all();
        $routers = Router::all();
        
        return Inertia::render('Hotspot/User/Edit', [
            'hotspotUser' => $hotspotUser,
            'profiles' => $profiles,
            'routers' => $routers
        ]);
    }

    /**
     * Update the specified hotspot user in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'nullable|string|min:8',
            'mac_address' => 'nullable|string|max:255',
            'profile' => 'required|string|max:255',
            'router_id' => 'required|exists:routers,id',
            'disabled' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);

        $hotspotUser = HotspotUser::findOrFail($id);
        
        $data = $request->only([
            'username',
            'mac_address',
            'profile',
            'router_id',
            'disabled',
            'expires_at',
        ]);

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $hotspotUser->update($data);

        return redirect()->route('hotspot.users.show', $hotspotUser)
            ->with('success', 'Hotspot user updated successfully.');
    }

    /**
     * Remove the specified hotspot user from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        $router = $hotspotUser->router;
        
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => 8728,
            ]);
            
            // Get user ID from MikroTik
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $hotspotUser->username);
            $response = $client->query($query)->read();
            
            if (count($response) > 0) {
                $userId = $response[0]['.id'];
                
                // Remove user
                $query = new Query('/ip/hotspot/user/remove');
                $query->equal('.id', $userId);
                $client->query($query)->read();
            }
            
        } catch (\Exception $e) {
            // Log error but continue
            \Log::error('Failed to delete hotspot user from router: ' . $e->getMessage());
        }
        
        $hotspotUser->delete();

        return redirect()->route('hotspot.users.index')
            ->with('success', 'Hotspot user deleted successfully.');
    }
    
    /**
     * Get active sessions for a hotspot user
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function sessions($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        $sessions = $hotspotUser->sessions()
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        
        return Inertia::render('Hotspot/User/Sessions', [
            'hotspotUser' => $hotspotUser,
            'sessions' => $sessions,
        ]);
    }
    
    /**
     * Disconnect a user session
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|string',
            'router_id' => 'required|exists:routers,id',
            'user_id' => 'required|exists:hotspot_users,id',
        ]);
        
        $router = Router::findOrFail($validated['router_id']);
        
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => 8728,
            ]);
            
            $query = new Query('/ip/hotspot/active/remove');
            $query->equal('.id', $validated['session_id']);
            $client->query($query)->read();
            
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to disconnect session: ' . $e->getMessage()]);
        }
        
        return redirect()->route('hotspot.users.sessions', $validated['user_id'])
            ->with('success', 'User session disconnected successfully.');
    }
}