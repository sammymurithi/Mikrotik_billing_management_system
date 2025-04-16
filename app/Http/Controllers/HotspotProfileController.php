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
        \Log::info('Fetching hotspot profiles from ' . $routers->count() . ' routers');

        $allProfiles = [];

        foreach ($routers as $router) {
            \Log::info("Processing router: {$router->name} ({$router->ip_address})");

            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                ]);

                $query = new Query('/ip/hotspot/user/profile/print');
                $mikrotikProfiles = $client->query($query)->read();
                \Log::info("Found " . count($mikrotikProfiles) . " hotspot profiles on router {$router->name}");

                if (empty($mikrotikProfiles)) {
                    \Log::warning("No profiles found on router {$router->name}. This might indicate a connection issue or no profiles exist.");
                    continue;
                }

                $existingProfiles = HotspotProfile::where('router_id', $router->id)
                    ->pluck('mikrotik_id')
                    ->toArray();

                $mikrotikProfileIds = [];
                foreach ($mikrotikProfiles as $profile) {
                    if (empty($profile['name'])) {
                        \Log::warning("Found profile with empty name on router {$router->name}, skipping");
                        continue;
                    }

                    $hotspotProfile = HotspotProfile::updateOrCreate(
                        [
                            'mikrotik_id' => $profile['.id'],
                            'router_id' => $router->id,
                        ],
                        [
                            'name' => $profile['name'],
                            'rate_limit' => $profile['rate-limit'] ?? null,
                            'shared_users' => $profile['shared-users'] ?? null,
                            'mac_cookie_timeout' => $profile['mac-cookie-timeout'] ?? null,
                            'keepalive_timeout' => $profile['keepalive-timeout'] ?? null,
                            'session_timeout' => $profile['session-timeout'] ?? null,
                        ]
                    );

                    $mikrotikProfileIds[] = $profile['.id'];
                    $allProfiles[] = $hotspotProfile;
                }

                $profilesToDelete = array_diff($existingProfiles, $mikrotikProfileIds);
                if (!empty($profilesToDelete)) {
                    HotspotProfile::where('router_id', $router->id)
                        ->whereIn('mikrotik_id', $profilesToDelete)
                        ->delete();
                    \Log::info("Deleted " . count($profilesToDelete) . " profiles from database for router {$router->name} that no longer exist in MikroTik");
                }

            } catch (\Exception $e) {
                \Log::error("Failed to fetch hotspot profiles from router {$router->name}", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                continue;
            }
        }

        if (empty($allProfiles)) {
            \Log::warning("No profiles were found in any router. This might indicate a connection issue or no profiles exist.");
        }

        $hotspotProfiles = collect($allProfiles)->map(function ($profile) {
            return [
                'id' => $profile->id,
                'name' => $profile->name,
                'rate_limit' => $profile->rate_limit,
                'shared_users' => $profile->shared_users,
                'mac_cookie_timeout' => $profile->mac_cookie_timeout,
                'keepalive_timeout' => $profile->keepalive_timeout,
                'session_timeout' => $profile->session_timeout,
                'router_id' => $profile->router_id,
                'router_name' => $profile->router->name,
            ];
        })->toArray();

        \Log::info("Returning " . count($hotspotProfiles) . " total hotspot profiles to view");

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
            'name' => 'required|string|max:255|unique:hotspot_profiles',
            'rate_limit' => 'nullable|string',
            'shared_users' => 'nullable|string',
            'mac_cookie_timeout' => 'nullable|string',
            'keepalive_timeout' => 'nullable|string',
            'session_timeout' => 'nullable|string',
            'router_id' => 'required|exists:routers,id',
        ]);

        $hotspotProfile = HotspotProfile::create([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'] ?? null,
            'shared_users' => $validated['shared_users'] ?? null,
            'mac_cookie_timeout' => $validated['mac_cookie_timeout'] ?? null,
            'keepalive_timeout' => $validated['keepalive_timeout'] ?? null,
            'session_timeout' => $validated['session_timeout'] ?? null,
            'router_id' => $validated['router_id'],
        ]);

        $router = Router::findOrFail($validated['router_id']);

        try {
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

            if (!isset($response['!done'])) {
                throw new \Exception('Failed to create profile in MikroTik');
            }

            $query = new Query('/ip/hotspot/user/profile/print');
            $query->where('name', $validated['name']);
            $response = $client->query($query)->read();
            if (!empty($response)) {
                $hotspotProfile->update(['mikrotik_id' => $response[0]['.id']]);
            }
        } catch (\Exception $e) {
            $hotspotProfile->delete();
            \Log::error('Failed to create hotspot profile on router: ' . $e->getMessage());
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to create hotspot profile on router: ' . $e->getMessage()]);
        }

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile created successfully.');
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles,name,' . $id,
            'rate_limit' => 'nullable|string',
            'shared_users' => 'nullable|string',
            'mac_cookie_timeout' => 'nullable|string',
            'keepalive_timeout' => 'nullable|string',
            'session_timeout' => 'nullable|string',
            'router_id' => 'required|exists:routers,id',
        ]);

        $hotspotProfile = HotspotProfile::findOrFail($id);
        $oldRouter = $hotspotProfile->router;
        $newRouter = Router::findOrFail($validated['router_id']);
        $routerChanged = $oldRouter->id !== $newRouter->id;

        $hotspotProfile->update([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'] ?? null,
            'shared_users' => $validated['shared_users'] ?? null,
            'mac_cookie_timeout' => $validated['mac_cookie_timeout'] ?? null,
            'keepalive_timeout' => $validated['keepalive_timeout'] ?? null,
            'session_timeout' => $validated['session_timeout'] ?? null,
            'router_id' => $validated['router_id'],
        ]);

        try {
            if ($routerChanged) {
                $oldClient = new Client([
                    'host' => $oldRouter->ip_address,
                    'user' => $oldRouter->username,
                    'pass' => $oldRouter->password,
                    'port' => $oldRouter->port ?? 8728,
                ]);

                $query = new Query('/ip/hotspot/user/profile/print');
                $query->where('.id', $hotspotProfile->mikrotik_id);
                $response = $oldClient->query($query)->read();

                if (!empty($response)) {
                    $query = new Query('/ip/hotspot/user/profile/remove');
                    $query->equal('.id', $hotspotProfile->mikrotik_id);
                    $oldClient->query($query)->read();
                }
            }

            $client = new Client([
                'host' => $newRouter->ip_address,
                'user' => $newRouter->username,
                'pass' => $newRouter->password,
                'port' => $newRouter->port ?? 8728,
            ]);

            $query = new Query('/ip/hotspot/user/profile/set');
            $query->equal('.id', $hotspotProfile->mikrotik_id);
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

            if (!isset($response['!done'])) {
                throw new \Exception('Failed to update profile in MikroTik');
            }

            if ($routerChanged || empty($response)) {
                $query = new Query('/ip/hotspot/user/profile/print');
                $query->where('name', $validated['name']);
                $response = $client->query($query)->read();
                if (!empty($response)) {
                    $hotspotProfile->update(['mikrotik_id' => $response[0]['.id']]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to update hotspot profile on router: ' . $e->getMessage());
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to update hotspot profile on router: ' . $e->getMessage()]);
        }

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile updated successfully.');
    }

    /**
     * Format time for Mikrotik
     * Validates and returns the time in Mikrotik's native format (e.g., "2h30m")
     */
    private function formatTimeForMikrotik($timeStr)
    {
        if (empty($timeStr)) {
            return null;
        }

        // If already in Mikrotik format (e.g., "2h30m"), return as is
        if (preg_match('/^(\d+d)?(\d+h)?(\d+m)?(\d+s)?$/', $timeStr)) {
            return $timeStr;
        }

        // If in HH:MM:SS format, convert to Mikrotik format
        if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $timeStr, $matches)) {
            $hours = (int)$matches[1];
            $minutes = (int)$matches[2];
            $seconds = (int)$matches[3];

            $result = '';
            if ($hours > 0) {
                $result .= $hours . 'h';
            }
            if ($minutes > 0) {
                $result .= $minutes . 'm';
            }
            if ($seconds > 0) {
                $result .= $seconds . 's';
            }
            return $result;
        }

        // If no valid format is found, return null
        return null;
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
            \Log::error('Failed to delete hotspot profile from router: ' . $e->getMessage());
            return redirect()->route('hotspot.profiles.index')
                ->withErrors(['error' => 'Failed to delete hotspot profile from router: ' . $e->getMessage()]);
        }

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile deleted successfully.');
    }
}