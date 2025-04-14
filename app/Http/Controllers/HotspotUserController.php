<?php

namespace App\Http\Controllers;

use App\Models\HotspotUser;
use App\Models\Router;
use App\Models\HotspotSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;

class HotspotUserController extends Controller
{
    public function index()
    {
        $routers = Router::all();
    
        if ($routers->isEmpty()) {
            return Inertia::render('Hotspot/User/Partials/Index', [
                'hotspotUsers' => [],
            ]);
        }
    
        $activeSessions = [];
    
        foreach ($routers as $router) {
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                ]);
    
                $testQuery = new Query('/system/identity/print');
                $identity = $client->query($testQuery)->read();
    
                $query = new Query('/ip/hotspot/user/print');
                $mikrotikUsers = $client->query($query)->read();
    
                $query = new Query('/ip/hotspot/active/print');
                $mikrotikSessions = $client->query($query)->read();
    
                foreach ($mikrotikSessions as $session) {
                    $activeSessions[$session['user']] = [
                        'uptime' => $session['uptime'] ?? '',
                        'bytes_in' => $session['bytes-in'] ?? 0,
                        'bytes_out' => $session['bytes-out'] ?? 0,
                        'packets_in' => $session['packets-in'] ?? 0,
                        'packets_out' => $session['packets-out'] ?? 0,
                    ];
                }
    
                $existingUsers = HotspotUser::where('router_id', $router->id)
                    ->pluck('mikrotik_id')
                    ->toArray();
    
                $mikrotikUserIds = [];
                foreach ($mikrotikUsers as $user) {
                    $username = $user['name'];
                    $isConnected = isset($activeSessions[$username]);
    
                    $macAddress = '';
                    if (isset($user['mac-address']) && !empty($user['mac-address'])) {
                        $macAddress = $user['mac-address'];
                    } elseif (isset($user['mac_address']) && !empty($user['mac_address'])) {
                        $macAddress = $user['mac_address'];
                    }
    
                    $isDisabled = false;
                    if (isset($user['disabled'])) {
                        $isDisabled = $user['disabled'] === 'true' || $user['disabled'] === true;
                    }
    
                    try {
                        $updateData = [
                            'username' => $username,
                            'password' => $user['password'] ?? '',
                            'profile_name' => $user['profile'] ?? null,
                            'mac_address' => $macAddress,
                            'disabled' => $isDisabled,
                            'status' => $isDisabled ? 'disabled' : 'active',
                            'expires_at' => isset($user['limit-uptime']) ? now()->addSeconds($this->parseUptimeToSeconds($user['limit-uptime'])) : null,
                        ];
    
                        $hotspotUser = HotspotUser::updateOrCreate(
                            [
                                'mikrotik_id' => $user['.id'],
                                'router_id' => $router->id,
                            ],
                            $updateData
                        );
    
                        if ($hotspotUser->expires_at && $hotspotUser->expires_at->isPast()) {
                            $hotspotUser->update([
                                'status' => 'expired',
                                'disabled' => true
                            ]);
                        }
                    } catch (\Exception $e) {
                        continue;
                    }
    
                    $mikrotikUserIds[] = $user['.id'];
                }
    
                $usersToDelete = array_diff($existingUsers, $mikrotikUserIds);
                if (!empty($usersToDelete)) {
                    HotspotUser::where('router_id', $router->id)
                        ->whereIn('mikrotik_id', $usersToDelete)
                        ->delete();
                }
    
            } catch (\Exception $e) {
                continue;
            }
        }
    
        $hotspotUsers = HotspotUser::with('router')
            ->get()
            ->map(function ($user) use ($activeSessions) {
                $isConnected = isset($activeSessions[$user->username]);
                
                return [
                    'id' => $user->id,
                    'username' => $user->username,
                    'password' => $user->password,
                    'mac_address' => $user->mac_address,
                    'profile_name' => $user->profile_name,
                    'router_id' => $user->router_id,
                    'router_name' => $user->router->name,
                    'disabled' => $user->disabled,
                    'status' => $user->status,
                    'expires_at' => $user->expires_at ? $user->expires_at->toDateTimeString() : null,
                    'is_connected' => $isConnected,
                    'session' => $isConnected ? $activeSessions[$user->username] : null,
                ];
            })
            ->toArray();
    
        return Inertia::render('Hotspot/User/Partials/Index', [
            'hotspotUsers' => $hotspotUsers,
        ]);
    }
    
    private function parseUptimeToSeconds($uptime)
    {
        $seconds = 0;
        if (preg_match('/(\d+)d/', $uptime, $days)) {
            $seconds += $days[1] * 86400;
        }
        if (preg_match('/(\d+)h/', $uptime, $hours)) {
            $seconds += $hours[1] * 3600;
        }
        if (preg_match('/(\d+)m/', $uptime, $minutes)) {
            $seconds += $minutes[1] * 60;
        }
        if (preg_match('/(\d+)s/', $uptime, $secs)) {
            $seconds += $secs[1];
        }
        return $seconds;
    }



    public function create()
    {
        return Inertia::render('Hotspot/User/Partials/Create');
    }




    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:hotspot_users',
            'password' => 'required|string|min:6',
            'profile_name' => 'required|string|exists:hotspot_profiles,name',
            'router_id' => 'required|exists:routers,id',
            'mac_address' => 'nullable|string',
            'expires_at' => 'nullable|date',
            'disabled' => 'boolean',
        ]);
    
        $hotspotUser = HotspotUser::create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'profile_name' => $validated['profile_name'],
            'router_id' => $validated['router_id'],
            'mac_address' => $validated['mac_address'] ?? null,
            'disabled' => $validated['disabled'] ?? false,
            'status' => ($validated['disabled'] ?? false) ? 'disabled' : 'active',
            'expires_at' => $validated['expires_at'] ?? null,
        ]);
    
        $router = Router::findOrFail($validated['router_id']);
    
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);
    
            $query = new Query('/ip/hotspot/user/add');
            $query->equal('name', $validated['username']);
            $query->equal('password', $validated['password']);
            $query->equal('profile', $validated['profile_name']);
            $query->equal('disabled', ($validated['disabled'] ?? false) ? 'no' : 'yes');
    
            if (!empty($validated['mac_address'])) {
                $query->equal('mac-address', $validated['mac_address']);
            }
    
            if (!empty($validated['expires_at'])) {
                $expiresAt = new \DateTime($validated['expires_at']);
                $now = new \DateTime();
                $interval = $now->diff($expiresAt);
                $seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
                $query->equal('limit-uptime', $seconds . 's');
            }
    
            $response = $client->query($query)->read();
    
            if (!isset($response['!done'])) {
                throw new \Exception('Failed to create user in MikroTik');
            }
    
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $validated['username']);
            $response = $client->query($query)->read();
            if (!empty($response)) {
                $hotspotUser->update(['mikrotik_id' => $response[0]['.id']]);
            }
        } catch (\Exception $e) {
            $hotspotUser->delete();
            \Log::error('Failed to create hotspot user on router: ' . $e->getMessage());
            return redirect()->route('hotspot.users.index')
                ->withErrors(['error' => 'Failed to create hotspot user on router: ' . $e->getMessage()]);
        }
    
        return redirect()->route('hotspot.users.index')
            ->with('success', 'Hotspot user created successfully.');
    }


    public function show($id)
    {
        $hotspotUser = HotspotUser::with('router')->findOrFail($id);
        $activeSessions = [];
        
        try {
            $client = new Client([
                'host' => $hotspotUser->router->ip_address,
                'user' => $hotspotUser->router->username,
                'pass' => $hotspotUser->router->password,
                'port' => $hotspotUser->router->port ?? 8728,
            ]);

            // Get user details from router
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $hotspotUser->username);
            $mikrotikUser = $client->query($query)->read();
            
            if (!empty($mikrotikUser)) {
                $mikrotikUser = $mikrotikUser[0];
                $hotspotUser->mikrotik_id = $mikrotikUser['.id'];
                $hotspotUser->save();
            }

            // Get active sessions
            $query = new Query('/ip/hotspot/active/print');
            $query->where('user', $hotspotUser->username);
            $sessions = $client->query($query)->read();

            foreach ($sessions as $session) {
                $activeSessions[] = [
                    'id' => $session['.id'],
                    'ip_address' => $session['address'] ?? '',
                    'mac_address' => $session['mac-address'] ?? '',
                    'uptime' => $session['uptime'] ?? '',
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'packets_in' => $session['packets-in'] ?? 0,
                    'packets_out' => $session['packets-out'] ?? 0,
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Failed to fetch user details from router', [
                'error' => $e->getMessage(),
                'user_id' => $id
            ]);
        }
        
        return Inertia::render('Hotspot/User/Partials/Show', [
            'hotspotUser' => [
                'id' => $hotspotUser->id,
                'username' => $hotspotUser->username,
                'password' => $hotspotUser->password,
                'mac_address' => $hotspotUser->mac_address,
                'profile_name' => $hotspotUser->profile_name,
                'router_id' => $hotspotUser->router_id,
                'router_name' => $hotspotUser->router->name,
                'disabled' => $hotspotUser->disabled,
                'status' => $hotspotUser->status,
                'expires_at' => $hotspotUser->expires_at ? $hotspotUser->expires_at->toDateTimeString() : null,
                'created_at' => $hotspotUser->created_at->toDateTimeString(),
                'updated_at' => $hotspotUser->updated_at->toDateTimeString(),
            ],
            'activeSessions' => $activeSessions
        ]);
    }




    public function edit($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        $routers = Router::all();
        $profiles = [];

        $router = $hotspotUser->router;
        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/user/profile/print');
            $mikrotikProfiles = $client->query($query)->read();

            foreach ($mikrotikProfiles as $profile) {
                $profiles[] = [
                    'name' => $profile['name'],
                    'rate_limit' => $profile['rate-limit'] ?? null,
                    'shared_users' => $profile['shared-users'] ?? null,
                ];
            }

            \Log::info("Fetched " . count($profiles) . " profiles from MikroTik router {$router->name} for editing user {$hotspotUser->username}");
        } catch (\Exception $e) {
            \Log::error("Failed to fetch profiles from router {$router->name}: " . $e->getMessage());
        }

        return Inertia::render('Hotspot/User/Partials/Edit', [
            'hotspotUser' => $hotspotUser,
            'routers' => $routers,
            'profiles' => $profiles
        ]);
    }





    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:hotspot_users,username,' . $id,
            'password' => 'nullable|string|min:6',
            'profile_name' => 'required|string',
            'router_id' => 'required|exists:routers,id',
            'mac_address' => 'nullable|string',
            'disabled' => 'boolean',
            'expires_at' => 'nullable|date',
        ]);
    
        $hotspotUser = HotspotUser::findOrFail($id);
        $oldRouter = $hotspotUser->router;
        $newRouter = Router::findOrFail($validated['router_id']);
        $routerChanged = $oldRouter->id !== $newRouter->id;
    
        try {
            $client = new Client([
                'host' => $newRouter->ip_address,
                'user' => $newRouter->username,
                'pass' => $newRouter->password,
                'port' => $newRouter->port ?? 8728,
            ]);

            // Verify user exists on router
            $query = new Query('/ip/hotspot/user/print');
            $query->where('.id', $hotspotUser->mikrotik_id);
            $response = $client->query($query)->read();
            
            if (empty($response)) {
                // If user doesn't exist, try to find by name
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $hotspotUser->username);
                $response = $client->query($query)->read();

                if (empty($response)) {
                    throw new \Exception('User not found on router');
                }
                
                $hotspotUser->mikrotik_id = $response[0]['.id'];
            }

            // Update existing user
            $query = new Query('/ip/hotspot/user/set');
            $query->equal('.id', $hotspotUser->mikrotik_id);
            $query->equal('name', $validated['username']);
            $query->equal('profile', $validated['profile_name']);
            $query->equal('disabled', $validated['disabled'] ? 'yes' : 'no');
    
            if (isset($validated['password']) && !empty($validated['password'])) {
                $query->equal('password', $validated['password']);
            }
    
            if (!empty($validated['mac_address'])) {
                $query->equal('mac-address', $validated['mac_address']);
            }
    
            if (!empty($validated['expires_at'])) {
                $expiresAt = new \DateTime($validated['expires_at']);
                $now = new \DateTime();
                $interval = $now->diff($expiresAt);
                $seconds = $interval->days * 86400 + $interval->h * 3600 + $interval->i * 60 + $interval->s;
                $query->equal('limit-uptime', $seconds . 's');
            }
    
            $response = $client->query($query)->read();
    
            if (!isset($response['!done'])) {
                throw new \Exception('Failed to update user in MikroTik. Response: ' . json_encode($response));
            }

            // Verify the update was successful
            $query = new Query('/ip/hotspot/user/print');
            $query->where('.id', $hotspotUser->mikrotik_id);
            $response = $client->query($query)->read();
            
            if (empty($response)) {
                throw new \Exception('User update verification failed - user not found after update');
            }

            $updatedUser = $response[0];
            if ($updatedUser['name'] !== $validated['username'] || 
                $updatedUser['profile'] !== $validated['profile_name'] ||
                $updatedUser['disabled'] !== ($validated['disabled'] ? 'yes' : 'no')) {
                throw new \Exception('User update verification failed - values not updated correctly');
            }
    
            // Update database after successful router update
            $hotspotUser->update([
                'username' => $validated['username'],
                'password' => $validated['password'] ?? $hotspotUser->password,
                'profile_name' => $validated['profile_name'],
                'router_id' => $validated['router_id'],
                'mac_address' => $validated['mac_address'] ?? null,
                'disabled' => $validated['disabled'],
                'status' => $validated['disabled'] ? 'disabled' : 'active',
                'expires_at' => $validated['expires_at'] ?? null,
            ]);
    
            return redirect()->route('hotspot.users.index')
                ->with('success', 'Hotspot user updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to update hotspot user on router', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'update_data' => $validated
            ]);
            return redirect()->route('hotspot.users.index')
                ->withErrors(['error' => 'Failed to update hotspot user on router: ' . $e->getMessage()]);
        }
    }







    public function destroy($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        $router = $hotspotUser->router;

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $hotspotUser->username);
            $response = $client->query($query)->read();

            if (!empty($response)) {
                $mikrotikUserId = $response[0]['.id'];

                $query = new Query('/ip/hotspot/user/remove');
                $query->equal('.id', $mikrotikUserId);
                $response = $client->query($query)->read();

                if (!isset($response['!done'])) {
                    throw new \Exception('Failed to delete user from MikroTik');
                }
            }

            // Delete from database after successful router deletion
            $hotspotUser->delete();

            // Refresh the page to get updated data from MikroTik
            return redirect()->route('hotspot.users.index')
                ->with('success', 'Hotspot user deleted successfully.');
        } catch (\Exception $e) {
            \Log::error('Failed to delete hotspot user from router: ' . $e->getMessage());
            return redirect()->route('hotspot.users.index')
                ->withErrors(['error' => 'Failed to delete hotspot user from router: ' . $e->getMessage()]);
        }
    }





    public function sessions($id)
    {
        $hotspotUser = HotspotUser::findOrFail($id);
        $router = $hotspotUser->router;
        $sessions = [];

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            // Get active sessions from router
            $query = new Query('/ip/hotspot/active/print');
            $query->where('user', $hotspotUser->username);
            $activeSessions = $client->query($query)->read();

            // Get existing sessions from database
            $existingSessions = HotspotSession::where('hotspot_user_id', $hotspotUser->id)
                ->whereNull('ended_at')
                ->get()
                ->keyBy('session_id');

            // Process active sessions
            foreach ($activeSessions as $session) {
                $sessionId = $session['.id'];
                $sessionData = [
                    'hotspot_user_id' => $hotspotUser->id,
                    'session_id' => $sessionId,
                    'mac_address' => $session['mac-address'] ?? '',
                    'ip_address' => $session['address'] ?? '',
                    'uptime' => $session['uptime'] ?? '',
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'packets_in' => $session['packets-in'] ?? 0,
                    'packets_out' => $session['packets-out'] ?? 0,
                    'started_at' => now(),
                ];

                if (isset($existingSessions[$sessionId])) {
                    // Update existing session
                    $existingSessions[$sessionId]->update($sessionData);
                } else {
                    // Create new session
                    HotspotSession::create($sessionData);
                }

                $sessions[] = [
                    'id' => $sessionId,
                    'ip_address' => $session['address'] ?? '',
                    'mac_address' => $session['mac-address'] ?? '',
                    'uptime' => $session['uptime'] ?? '',
                    'bytes_in' => $session['bytes-in'] ?? 0,
                    'bytes_out' => $session['bytes-out'] ?? 0,
                    'packets_in' => $session['packets-in'] ?? 0,
                    'packets_out' => $session['packets-out'] ?? 0,
                ];
            }

            // Mark sessions that are no longer active as ended
            $activeSessionIds = collect($activeSessions)->pluck('.id')->toArray();
            HotspotSession::where('hotspot_user_id', $hotspotUser->id)
                ->whereNull('ended_at')
                ->whereNotIn('session_id', $activeSessionIds)
                ->update(['ended_at' => now()]);

            // Get all sessions (active and historical) from database
            $allSessions = HotspotSession::where('hotspot_user_id', $hotspotUser->id)
                ->orderBy('started_at', 'desc')
                ->get()
                ->map(function ($session) {
                    return [
                        'id' => $session->session_id,
                        'ip_address' => $session->ip_address,
                        'mac_address' => $session->mac_address,
                        'uptime' => $session->uptime,
                        'bytes_in' => $session->bytes_in,
                        'bytes_out' => $session->bytes_out,
                        'packets_in' => $session->packets_in,
                        'packets_out' => $session->packets_out,
                        'started_at' => $session->started_at->toDateTimeString(),
                        'ended_at' => $session->ended_at ? $session->ended_at->toDateTimeString() : null,
                        'is_active' => is_null($session->ended_at),
                    ];
                })
                ->toArray();

        } catch (\Exception $e) {
            \Log::error("Failed to fetch sessions for user {$hotspotUser->username} from router {$router->name}: " . $e->getMessage());
            return redirect()->route('hotspot.users.index')
                ->withErrors(['error' => 'Failed to fetch user sessions: ' . $e->getMessage()]);
        }

        return Inertia::render('Hotspot/User/Partials/Sessions', [
            'hotspotUser' => [
                'id' => $hotspotUser->id,
                'username' => $hotspotUser->username,
                'router_id' => $hotspotUser->router_id,
            ],
            'sessions' => $allSessions,
        ]);
    }






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
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/active/remove');
            $query->equal('.id', $validated['session_id']);
            $response = $client->query($query)->read();

            if (!isset($response['!done'])) {
                throw new \Exception('Failed to disconnect session in MikroTik');
            }
        } catch (\Exception $e) {
            \Log::error('Failed to disconnect session: ' . $e->getMessage());
            return redirect()->route('hotspot.users.sessions', $validated['user_id'])
                ->withErrors(['error' => 'Failed to disconnect session: ' . $e->getMessage()]);
        }

        return redirect()->route('hotspot.users.sessions', $validated['user_id'])
            ->with('success', 'User session disconnected successfully.');
    }
}