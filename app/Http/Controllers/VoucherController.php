<?php

namespace App\Http\Controllers;

use App\Models\Voucher;
use App\Models\Router;
use App\Models\HotspotProfile;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;
use RouterOS\Client;
use RouterOS\Query;

class VoucherController extends Controller
{
    public function index(Request $request)
    {
        $selectedRouter = $request->router_id;
        $status = $request->status;
        $perPage = $request->per_page ?: 10;
        
        $query = Voucher::with('router')
            ->when($selectedRouter, function($q) use ($selectedRouter) {
                return $q->where('router_id', $selectedRouter);
            })
            ->when($status, function($q) use ($status) {
                return $q->where('status', $status);
            })
            ->latest();
        
        $vouchers = $query->paginate($perPage)->withQueryString();
        $routers = Router::all();
        $profiles = HotspotProfile::select('id', 'name', 'rate_limit', 'price', 'session_timeout')->get();

        return Inertia::render('Vouchers/Index', [
            'vouchers' => $vouchers,
            'routers' => $routers,
            'profiles' => $profiles,
            'selectedRouter' => $selectedRouter,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'count' => 'required|integer|min:1|max:100',
            'profile' => 'required|string',
            'router_id' => 'required|exists:routers,id',
            'limit_mb' => 'nullable|integer',
        ]);

        $router = Router::findOrFail($validated['router_id']);
        $profile = HotspotProfile::where('name', $validated['profile'])->firstOrFail();

        $vouchers = [];
        // Define allowed characters (avoiding confusing ones like O, 0, I, 1, l)
        $allowedChars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        
        for ($i = 0; $i < $validated['count']; $i++) {
            // Generate a random code using only allowed characters
            $code = '';
            for ($j = 0; $j < 6; $j++) {
                $code .= $allowedChars[rand(0, strlen($allowedChars) - 1)];
            }
            
            // Add a hyphen and 5 more characters
            $code .= '-';
            for ($j = 0; $j < 5; $j++) {
                $code .= $allowedChars[rand(0, strlen($allowedChars) - 1)];
            }
            
            // Use the same code for both username and password
            $username = $code;
            $password = $code;
            
            // Create the user directly in MikroTik router
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                ]);
                
                // Create user query
                $query = new Query('/ip/hotspot/user/add');
                $query->equal('name', $username);
                $query->equal('password', $password);
                $query->equal('profile', $validated['profile']);
                
                // Set the limit for the user based on voucher's limit_mb
                if ($validated['limit_mb'] > 0) {
                    $byteLimit = $validated['limit_mb'] * 1024 * 1024; // Convert MB to bytes
                    $query->equal('limit-bytes-total', $byteLimit);
                }
                
                // Execute the query
                $response = $client->query($query)->read();
                
                // Log success
                \Log::info('Created voucher user in MikroTik', [
                    'username' => $username,
                    'router' => $router->name,
                    'profile' => $validated['profile'],
                    'response' => $response
                ]);
            } catch (\Exception $e) {
                // Log error but continue - we'll still create the voucher in our database
                \Log::error('Failed to create voucher user in MikroTik', [
                    'username' => $username,
                    'router_id' => $validated['router_id'],
                    'error' => $e->getMessage()
                ]);
            }

            $voucher = Voucher::create([
                'username' => $username,
                'password' => $password,
                'profile' => $validated['profile'],
                'router_id' => $validated['router_id'],
                'limit_mb' => $validated['limit_mb'],
                'status' => 'active',
            ]);

            $vouchers[] = $voucher;
        }

        return redirect()->back()->with('success', 'Vouchers generated successfully.');
    }

    public function destroy(Voucher $voucher)
    {
        // Get router details
        $router = Router::findOrFail($voucher->router_id);
        
        try {
            // Connect to router
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);
            
            // Find the user in MikroTik
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $voucher->username);
            $response = $client->query($query)->read();
            
            if (!empty($response)) {
                // User exists, remove it
                $userId = $response[0]['.id'];
                $query = new Query('/ip/hotspot/user/remove');
                $query->equal('.id', $userId);
                $client->query($query)->read();
                
                \Log::info('Removed voucher user from MikroTik', [
                    'username' => $voucher->username,
                    'router' => $router->name
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to remove voucher user from MikroTik', [
                'username' => $voucher->username,
                'router_id' => $voucher->router_id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Delete the voucher from database
        $voucher->delete();

        return redirect()->route('vouchers.index');
    }

    public function disable(Voucher $voucher)
    {
        // Get router details
        $router = Router::findOrFail($voucher->router_id);
        
        try {
            // Connect to router
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
            ]);
            
            // Find the user in MikroTik
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $voucher->username);
            $response = $client->query($query)->read();
            
            if (!empty($response)) {
                // User exists, disable it
                $userId = $response[0]['.id'];
                $query = new Query('/ip/hotspot/user/set');
                $query->equal('.id', $userId);
                $query->equal('disabled', 'yes');
                $client->query($query)->read();
                
                \Log::info('Disabled voucher user in MikroTik', [
                    'username' => $voucher->username,
                    'router' => $router->name
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Failed to disable voucher user in MikroTik', [
                'username' => $voucher->username,
                'router_id' => $voucher->router_id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Update the voucher status in database
        $voucher->update([
            'status' => 'used',
            'used_at' => now()
        ]);
        return back()->with('success', 'Voucher marked as used.');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'No vouchers selected.');
        }
        
        // Get vouchers with their routers
        $vouchers = Voucher::whereIn('id', $ids)->with('router')->get();
        
        foreach ($vouchers as $voucher) {
            try {
                // Connect to router
                $client = new Client([
                    'host' => $voucher->router->ip_address,
                    'user' => $voucher->router->username,
                    'pass' => $voucher->router->password,
                    'port' => $voucher->router->port ?? 8728,
                ]);
                
                // Find the user in MikroTik
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $voucher->username);
                $response = $client->query($query)->read();
                
                if (!empty($response)) {
                    // User exists, remove it
                    $userId = $response[0]['.id'];
                    $query = new Query('/ip/hotspot/user/remove');
                    $query->equal('.id', $userId);
                    $client->query($query)->read();
                    
                    \Log::info('Removed voucher user from MikroTik', [
                        'username' => $voucher->username,
                        'router' => $voucher->router->name
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to remove voucher user from MikroTik', [
                    'username' => $voucher->username,
                    'router_id' => $voucher->router_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Delete vouchers from database
        Voucher::whereIn('id', $ids)->delete();
        
        return back()->with('success', count($ids) . ' vouchers deleted.');
    }

    public function batchDisable(Request $request)
    {
        $ids = $request->input('ids', []);
        
        if (empty($ids)) {
            return back()->with('error', 'No vouchers selected.');
        }
        
        // Get vouchers with their routers
        $vouchers = Voucher::whereIn('id', $ids)->with('router')->get();
        
        foreach ($vouchers as $voucher) {
            try {
                // Connect to router
                $client = new Client([
                    'host' => $voucher->router->ip_address,
                    'user' => $voucher->router->username,
                    'pass' => $voucher->router->password,
                    'port' => $voucher->router->port ?? 8728,
                ]);
                
                // Find the user in MikroTik
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $voucher->username);
                $response = $client->query($query)->read();
                
                if (!empty($response)) {
                    // User exists, disable it
                    $userId = $response[0]['.id'];
                    $query = new Query('/ip/hotspot/user/set');
                    $query->equal('.id', $userId);
                    $query->equal('disabled', 'yes');
                    $client->query($query)->read();
                    
                    \Log::info('Disabled voucher user in MikroTik', [
                        'username' => $voucher->username,
                        'router' => $voucher->router->name
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Failed to disable voucher user in MikroTik', [
                    'username' => $voucher->username,
                    'router_id' => $voucher->router_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Update vouchers in database
        Voucher::whereIn('id', $ids)->update([
            'status' => 'used',
            'used_at' => now()
        ]);
        
        return back()->with('success', count($ids) . ' vouchers disabled.');
    }

    // Add a method to handle voucher usage through captive portal
    public function markAsUsed($username)
    {
        $voucher = Voucher::where('username', $username)
            ->where('status', 'active')
            ->first();

        if ($voucher) {
            $voucher->update([
                'status' => 'used',
                'used_at' => now()
            ]);
            return true;
        }

        return false;
    }
}