<?php

namespace App\Http\Controllers;

use App\Models\HotspotProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CaptivePortalController extends Controller
{
    public function index()
    {
        // Get all hotspot profiles with prices
        $hotspotProfiles = HotspotProfile::with('router')
            ->get()
            ->map(function ($profile) {
                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'rate_limit' => $profile->rate_limit,
                    'shared_users' => $profile->shared_users,
                    'session_timeout' => $profile->session_timeout,
                    'price' => $profile->price,
                    'router_name' => $profile->router->name,
                ];
            })
            ->toArray();

        return Inertia::render('Welcome', [
            'hotspotProfiles' => $hotspotProfiles,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'rate_limit' => 'required|string',
            'shared_users' => 'required|integer',
            'price' => 'required|numeric',
            // Other validation rules
        ]);
        // {{ ... }} (Note: You need to implement the logic here)
    }

    public function update(Request $request, HotspotProfile $profile)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string',
            'rate_limit' => 'sometimes|string',
            'shared_users' => 'sometimes|integer',
            'price' => 'sometimes|numeric',
            // Other validation rules
        ]);
        // {{ ... }} (Note: You need to implement the logic here)
    }
}
