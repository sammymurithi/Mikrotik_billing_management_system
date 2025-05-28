<?php

namespace App\Http\Controllers;

use App\Models\HotspotProfile;
use App\Models\Router;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;

class HotspotProfileController extends Controller
{
    public function index()
    {
        $routers = Router::all();
        $hotspotProfiles = HotspotProfile::with('router')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'mikrotik_id' => $profile->mikrotik_id,
                    'name' => $profile->name,
                    'rate_limit' => $profile->rate_limit,
                    'shared_users' => $profile->shared_users,
                    'mac_cookie_timeout' => $profile->mac_cookie_timeout,
                    'keepalive_timeout' => $profile->keepalive_timeout,
                    'session_timeout' => $profile->session_timeout,
                    'price' => $profile->price,
                    'router_id' => $profile->router_id,
                    'router_name' => $profile->router->name,
                    'synced' => $profile->synced,
                ];
            })
            ->toArray();

        return Inertia::render('Packages/Index', [
            'hotspotProfiles' => $hotspotProfiles,
            'routers' => $routers->map(function ($router) {
                return [
                    'id' => $router->id,
                    'name' => $router->name,
                ];
            })->toArray(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate_limit' => 'nullable|string|regex:/^\d+[KMG]\/\d+[KMG]$/',
            'shared_users' => 'nullable|string',
            'mac_cookie_timeout' => 'nullable|string',
            'keepalive_timeout' => 'nullable|string',
            'session_timeout' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'router_id' => 'required|exists:routers,id',
        ]);

        if (isset($validated['rate_limit']) && !preg_match('/^\d+[KMG]\/\d+[KMG]$/', $validated['rate_limit'])) {
            $validated['rate_limit'] = preg_replace('/^(\d+)\/(\d+)$/', '$1M/$2M', $validated['rate_limit']);
        }

        $price = isset($validated['price']) ? (float)$validated['price'] : 0.00;

        $profileData = [
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'] ?? null,
            'shared_users' => $validated['shared_users'] ?? null,
            'mac_cookie_timeout' => $validated['mac_cookie_timeout'] ?? null,
            'keepalive_timeout' => $validated['keepalive_timeout'] ?? null,
            'session_timeout' => $validated['session_timeout'] ?? null,
            'price' => $price,
            'router_id' => $validated['router_id'],
        ];

        try {
            $profile = HotspotProfile::create($profileData);

            $router = Router::findOrFail($validated['router_id']);
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/user/profile/add');
            $query->equal('name', $validated['name']);
            if (!empty($validated['rate_limit'])) {
                $query->equal('rate-limit', $validated['rate_limit']);
            }
            if (!empty($validated['shared_users'])) {
                $query->equal('shared-users', $validated['shared_users']);
            }
            if (!empty($validated['mac_cookie_timeout'])) {
                $macCookieTimeout = $this->formatTimeForMikrotik($validated['mac_cookie_timeout']);
                $query->equal('mac-cookie-timeout', $macCookieTimeout);
            }
            if (!empty($validated['session_timeout'])) {
                $sessionTimeout = $this->formatTimeForMikrotik($validated['session_timeout']);
                $query->equal('session-timeout', $sessionTimeout);
            }
            if (!empty($validated['keepalive_timeout'])) {
                $keepaliveTimeout = $this->formatTimeForMikrotik($validated['keepalive_timeout']);
                $query->equal('keepalive-timeout', $keepaliveTimeout);
            }

            $response = $client->query($query)->read();
            
            $query = new Query('/ip/hotspot/user/profile/print');
            $query->where('name', $validated['name']);
            $profileResponse = $client->query($query)->read();
            
            if (!empty($profileResponse)) {
                $profile->update(['mikrotik_id' => $profileResponse[0]['.id']]);
            } else {
                throw new \Exception('Profile created but could not be found afterwards');
            }

            return redirect()->route('hotspot.profiles.index')
                ->with('success', 'Hotspot profile created successfully.');
        } catch (\Exception $e) {
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to create hotspot profile: ' . $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        $hotspotProfile = HotspotProfile::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rate_limit' => 'nullable|string|regex:/^\d+[KMG]\/\d+[KMG]$/',
            'shared_users' => 'nullable|string',
            'mac_cookie_timeout' => 'nullable|string',
            'keepalive_timeout' => 'nullable|string',
            'session_timeout' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'router_id' => 'required|exists:routers,id',
        ]);

        if (isset($validated['rate_limit']) && !preg_match('/^\d+[KMG]\/\d+[KMG]$/', $validated['rate_limit'])) {
            $validated['rate_limit'] = preg_replace('/^(\d+)\/(\d+)$/', '$1M/$2M', $validated['rate_limit']);
        }

        $price = isset($validated['price']) ? (float)$validated['price'] : 0.00;

        $routerChanged = $hotspotProfile->router_id != $validated['router_id'];
        $oldRouter = null;
        $newRouter = Router::findOrFail($validated['router_id']);

        if ($routerChanged) {
            $oldRouter = $hotspotProfile->router;
        }

        $hotspotProfile->update([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'] ?? null,
            'shared_users' => $validated['shared_users'] ?? null,
            'mac_cookie_timeout' => $validated['mac_cookie_timeout'] ?? null,
            'keepalive_timeout' => $validated['keepalive_timeout'] ?? null,
            'session_timeout' => $validated['session_timeout'] ?? null,
            'price' => $price,
            'router_id' => $validated['router_id'],
        ]);

        try {
            if ($routerChanged && $oldRouter) {
                $oldClient = new Client([
                    'host' => $oldRouter->ip_address,
                    'user' => $oldRouter->username,
                    'pass' => $oldRouter->password,
                    'port' => $oldRouter->port ?? 8728,
                ]);

                $query = new Query('/ip/hotspot/user/profile/remove');
                $query->equal('.id', $hotspotProfile->mikrotik_id);
                $oldClient->query($query)->read();
            }

            $client = new Client([
                'host' => $newRouter->ip_address,
                'user' => $newRouter->username,
                'pass' => $newRouter->password,
                'port' => $newRouter->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/user/profile/print');
            $query->where('.id', $hotspotProfile->mikrotik_id);
            $profileExists = $client->query($query)->read();
            
            if (empty($profileExists) && !$routerChanged) {
                $query = new Query('/ip/hotspot/user/profile/add');
                $query->equal('name', $validated['name']);
            } else {
                $query = new Query('/ip/hotspot/user/profile/set');
                $query->equal('.id', $hotspotProfile->mikrotik_id);
                $query->equal('name', $validated['name']);
            }
            
            if (!empty($validated['rate_limit'])) {
                $query->equal('rate-limit', $validated['rate_limit']);
            }
            if (!empty($validated['shared_users'])) {
                $query->equal('shared-users', $validated['shared_users']);
            }
            if (!empty($validated['mac_cookie_timeout'])) {
                $macCookieTimeout = $this->formatTimeForMikrotik($validated['mac_cookie_timeout']);
                $query->equal('mac-cookie-timeout', $macCookieTimeout);
            }
            if (!empty($validated['session_timeout'])) {
                $sessionTimeout = $this->formatTimeForMikrotik($validated['session_timeout']);
                $query->equal('session-timeout', $sessionTimeout);
            }
            if (!empty($validated['keepalive_timeout'])) {
                $keepaliveTimeout = $this->formatTimeForMikrotik($validated['keepalive_timeout']);
                $query->equal('keepalive-timeout', $keepaliveTimeout);
            }

            $response = $client->query($query)->read();
            
            $query = new Query('/ip/hotspot/user/profile/print');
            $query->where('name', $validated['name']);
            $profileResponse = $client->query($query)->read();
            
            if (!empty($profileResponse)) {
                $hotspotProfile->update(['mikrotik_id' => $profileResponse[0]['.id']]);
            } else {
                throw new \Exception('Profile created/updated but could not be found afterwards');
            }

            return redirect()->route('hotspot.profiles.index')
                ->with('success', 'Hotspot profile updated successfully.');
        } catch (\Exception $e) {
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to update hotspot profile: ' . $e->getMessage()]);
        }
    }

    public function destroy($id)
    {
        $hotspotProfile = HotspotProfile::findOrFail($id);
        $router = $hotspotProfile->router;

        try {
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/user/profile/print');
            $query->where('.id', $hotspotProfile->mikrotik_id);
            $response = $client->query($query)->read();

            if (!empty($response)) {
                $query = new Query('/ip/hotspot/user/profile/remove');
                $query->equal('.id', $hotspotProfile->mikrotik_id);
                $client->query($query)->read();
            }

            $hotspotProfile->delete();
        } catch (\Exception $e) {
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to delete hotspot profile from router: ' . $e->getMessage()]);
        }

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile deleted successfully.');
    }
    
    public function syncProfiles(Request $request)
    {
        $routerId = $request->input('router_id');
        $syncAll = $request->input('sync_all', false);
        $syncResults = [];
        
        try {
            if ($routerId) {
                // Sync profiles for a specific router
                $routers = Router::where('id', $routerId)->get();
            } else if ($syncAll) {
                // Sync profiles for all routers
                $routers = Router::all();
            } else {
                return response()->json(['error' => 'No router specified for sync'], 400);
            }
            
            foreach ($routers as $router) {
                $routerResult = $this->syncRouterProfiles($router);
                $syncResults[$router->id] = $routerResult;
            }
            
            return redirect()->route('hotspot.profiles.index')
                ->with('success', 'Profiles synchronized successfully from MikroTik');
        } catch (\Exception $e) {
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to sync profiles: ' . $e->getMessage()]);
        }
    }
    
    private function syncRouterProfiles(Router $router)
    {
        $result = [
            'router_name' => $router->name,
            'added' => 0,
            'updated' => 0,
            'errors' => [],
            'profiles' => []
        ];
        
        try {
            // Connect to router
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
                'timeout' => 10,
            ]);
            
            // Get all hotspot user profiles from MikroTik
            $query = new Query('/ip/hotspot/user/profile/print');
            $profiles = $client->query($query)->read();
            
            foreach ($profiles as $profile) {
                // Skip the default profile
                if ($profile['name'] === 'default') {
                    continue;
                }
                
                // Check if profile already exists in database
                $existingProfile = HotspotProfile::where('name', $profile['name'])
                    ->where('router_id', $router->id)
                    ->first();
                
                $profileData = [
                    'name' => $profile['name'],
                    'mikrotik_id' => $profile['.id'] ?? null,
                    'rate_limit' => $profile['rate-limit'] ?? null,
                    'shared_users' => $profile['shared-users'] ?? null,
                    'mac_cookie_timeout' => $profile['mac-cookie-timeout'] ?? null,
                    'keepalive_timeout' => $profile['keepalive-timeout'] ?? null,
                    'session_timeout' => $profile['session-timeout'] ?? null,
                    'router_id' => $router->id,
                    'synced' => true,
                    'price' => 0.00, // Default price, can be updated later
                ];
                
                if ($existingProfile) {
                    // Update existing profile
                    $existingProfile->update($profileData);
                    $result['updated']++;
                    $result['profiles'][] = [
                        'name' => $profile['name'],
                        'action' => 'updated'
                    ];
                } else {
                    // Create new profile
                    HotspotProfile::create($profileData);
                    $result['added']++;
                    $result['profiles'][] = [
                        'name' => $profile['name'],
                        'action' => 'added'
                    ];
                }
            }
            
            return $result;
        } catch (\Exception $e) {
            $result['errors'][] = $e->getMessage();
            return $result;
        }
    }
    
    private function formatTimeForMikrotik($timeString)
    {
        // If already in correct format, return as is
        if (preg_match('/^\d+[smhdw]$/', $timeString)) {
            return $timeString;
        }

        // Convert numeric values to seconds
        if (is_numeric($timeString)) {
            return $timeString . 's';
        }

        // Default to seconds if no unit specified
        if (preg_match('/^\d+$/', $timeString)) {
            return $timeString . 's';
        }

        return $timeString;
    }
}
