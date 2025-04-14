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
    /**
     * Display a listing of hotspot profiles.
     *
     * @return \Inertia\Response
     */
    public function index()
    {
        $profiles = HotspotProfile::withCount('hotspotUsers')->paginate(15);
        
        return Inertia::render('Hotspot/Profiles/Index', [
            'profiles' => $profiles
        ]);
    }

    /**
     * Show the form for creating a new hotspot profile.
     *
     * @return \Inertia\Response
     */
    public function create()
    {
        $routers = Router::all();
        
        return Inertia::render('Hotspot/Profiles/Create', [
            'routers' => $routers
        ]);
    }

    /**
     * Store a newly created hotspot profile in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles',
            'rate_limit' => 'nullable|string',
            'session_timeout' => 'nullable|string',
            'idle_timeout' => 'nullable|string',
            'shared_users' => 'nullable|integer',
            'price' => 'required|numeric',
            'routers' => 'required|array',
            'routers.*' => 'exists:routers,id',
        ]);
        
        // Create profile in database
        $profile = HotspotProfile::create([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'],
            'session_timeout' => $validated['session_timeout'],
            'idle_timeout' => $validated['idle_timeout'],
            'shared_users' => $validated['shared_users'],
            'price' => $validated['price'],
        ]);
        
        // Create profile on each selected router
        foreach ($validated['routers'] as $routerId) {
            $router = Router::findOrFail($routerId);
            
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => 8728,
                ]);
                
                $query = new Query('/ip/hotspot/user/profile/add');
                $query->equal('name', $validated['name']);
                
                if (!empty($validated['rate_limit'])) {
                    $query->equal('rate-limit', $validated['rate_limit']);
                }
                
                if (!empty($validated['session_timeout'])) {
                    $query->equal('session-timeout', $validated['session_timeout']);
                }
                
                if (!empty($validated['idle_timeout'])) {
                    $query->equal('idle-timeout', $validated['idle_timeout']);
                }
                
                if (!empty($validated['shared_users'])) {
                    $query->equal('shared-users', $validated['shared_users']);
                }
                
                $client->query($query)->read();
                
            } catch (\Exception $e) {
                // Log error but continue - we'll handle sync later
                \Log::error('Failed to create hotspot profile on router: ' . $e->getMessage());
            }
        }

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile created successfully.');
    }

    /**
     * Display the specified hotspot profile.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function show($id)
    {
        $profile = HotspotProfile::with('hotspotUsers')->findOrFail($id);
        
        return Inertia::render('Hotspot/Profiles/Show', [
            'profile' => $profile
        ]);
    }

    /**
     * Show the form for editing the specified hotspot profile.
     *
     * @param  int  $id
     * @return \Inertia\Response
     */
    public function edit($id)
    {
        $profile = HotspotProfile::findOrFail($id);
        $routers = Router::all();
        
        return Inertia::render('Hotspot/Profiles/Edit', [
            'profile' => $profile,
            'routers' => $routers
        ]);
    }

    /**
     * Update the specified hotspot profile in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        $profile = HotspotProfile::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:hotspot_profiles,name,'.$id,
            'rate_limit' => 'nullable|string',
            'session_timeout' => 'nullable|string',
            'idle_timeout' => 'nullable|string',
            'shared_users' => 'nullable|integer',
            'price' => 'required|numeric',
            'routers' => 'required|array',
            'routers.*' => 'exists:routers,id',
        ]);
        
        // Update profile in database
        $profile->update([
            'name' => $validated['name'],
            'rate_limit' => $validated['rate_limit'],
            'session_timeout' => $validated['session_timeout'],
            'idle_timeout' => $validated['idle_timeout'],
            'shared_users' => $validated['shared_users'],
            'price' => $validated['price'],
        ]);
        
        // Update profile on each selected router
        foreach ($validated['routers'] as $routerId) {
            $router = Router::findOrFail($routerId);
            
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => 8728,
                ]);
                
                // Check if profile exists on router
                $query = new Query('/ip/hotspot/user/profile/print');
                $query->where('name', $profile->name);
                $response = $client->query($query)->read();
                
                if (count($response) > 0) {
                    // Update existing profile
                    $profileId = $response[0]['.id'];
                    
                    $query = new Query('/ip/hotspot/user/profile/set');
                    $query->equal('.id', $profileId);
                    
                    if (!empty($validated['rate_limit'])) {
                        $query->equal('rate-limit', $validated['rate_limit']);
                    }
                    
                    if (!empty($validated['session_timeout'])) {
                        $query->equal('session-timeout', $validated['session_timeout']);
                    }
                    
                    if (!empty($validated['idle_timeout'])) {
                        $query->equal('idle-timeout', $validated['idle_timeout']);
                    }
                    
                    if (!empty($validated['shared_users'])) {
                        $query->equal('shared-users', $validated['shared_users']);
                    }
                    
                    $client->query($query)->read();
                } else {
                    // Create new profile
                    $query = new Query('/ip/hotspot/user/profile/add');
                    $query->equal('name', $validated['name']);
                    
                    if (!empty($validated['rate_limit'])) {
                        $query->equal('rate-limit', $validated['rate_limit']);
                    }
                    
                    if (!empty($validated['session_timeout'])) {
                        $query->equal('session-timeout', $validated['session_timeout']);
                    }
                    
                    if (!empty($validated['idle_timeout'])) {
                        $query->equal('idle-timeout', $validated['idle_timeout']);
                    }
                    
                    if (!empty($validated['shared_users'])) {
                        $query->equal('shared-users', $validated['shared_users']);
                    }
                    
                    $client->query($query)->read();
                }
                
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error('Failed to update hotspot profile on router: ' . $e->getMessage());
            }
        }

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile updated successfully.');
    }

    /**
     * Remove the specified hotspot profile from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        $profile = HotspotProfile::findOrFail($id);
        
        // Check if profile has users
        if ($profile->hotspotUsers()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete profile with associated users.']);
        }
        
        // Get all routers
        $routers = Router::all();
        
        // Delete profile from each router
        foreach ($routers as $router) {
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => 8728,
                ]);
                
                // Check if profile exists on router
                $query = new Query('/ip/hotspot/user/profile/print');
                $query->where('name', $profile->name);
                $response = $client->query($query)->read();
                
                if (count($response) > 0) {
                    $profileId = $response[0]['.id'];
                    
                    // Remove profile
                    $query = new Query('/ip/hotspot/user/profile/remove');
                    $query->equal('.id', $profileId);
                    $client->query($query)->read();
                }
                
            } catch (\Exception $e) {
                // Log error but continue
                \Log::error('Failed to delete hotspot profile from router: ' . $e->getMessage());
            }
        }
        
        $profile->delete();

        return redirect()->route('hotspot.profiles.index')
            ->with('success', 'Hotspot profile deleted successfully.');
    }
}