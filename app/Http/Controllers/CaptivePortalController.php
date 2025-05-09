<?php

namespace App\Http\Controllers;

use App\Models\HotspotProfile;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RouterOS\Client;
use RouterOS\Query;

class CaptivePortalController extends Controller
{
    public function __construct()
    {
        \Log::info('CaptivePortalController constructed');
    }

    public function index()
    {
        \Log::info('Index method called');
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

    public function authenticate(Request $request)
    {
        \Log::info('Raw request data', [
            'all' => $request->all(),
            'method' => $request->method(),
            'url' => $request->url(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip()
        ]);

        \Log::info('Authentication attempt - Full request details', [
            'username' => $request->username,
            'password' => $request->password ? 'provided' : 'missing',
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'request_data' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => $request->header('User-Agent')
        ]);

        try {
            $request->validate([
                'username' => 'required|string',
                'password' => 'required|string',
            ]);
        } catch (\Exception $e) {
            \Log::error('Validation failed', [
                'error' => $e->getMessage(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 422);
        }

        $voucher = Voucher::where('username', $request->username)
            ->where('password', $request->password)
            ->first();

        \Log::info('Voucher lookup result', [
            'found' => (bool)$voucher,
            'username' => $request->username,
            'password_length' => strlen($request->password),
            'voucher_details' => $voucher ? [
                'id' => $voucher->id,
                'status' => $voucher->status,
                'profile' => $voucher->profile,
                'router_id' => $voucher->router_id,
                'created_at' => $voucher->created_at,
                'used_at' => $voucher->used_at
            ] : null,
            'sql_query' => Voucher::where('username', $request->username)
                ->where('password', $request->password)
                ->toSql()
        ]);

        if (!$voucher) {
            \Log::warning('Invalid credentials', [
                'username' => $request->username,
                'password_length' => strlen($request->password),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        try {
            $router = $voucher->router;
            \Log::info('Router details', [
                'router_id' => $router->id,
                'ip' => $router->ip_address,
                'port' => $router->port,
                'username' => $router->username,
                'password_length' => strlen($router->password),
                'router_name' => $router->name
            ]);

            // Test connection to router first
            try {
                $client = new Client([
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                    'timeout' => 5, // Add timeout
                ]);

                // Test connection with a simple query
                $testQuery = new Query('/system/resource/print');
                $testResponse = $client->query($testQuery)->read();
                \Log::info('Router connection test successful', [
                    'response' => $testResponse,
                    'router_ip' => $router->ip_address
                ]);
            } catch (\Exception $e) {
                \Log::error('Router connection test failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'router_ip' => $router->ip_address,
                    'router_port' => $router->port
                ]);
                throw new \Exception('Failed to connect to router: ' . $e->getMessage());
            }

            // Check if user already exists
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $request->username);
            $response = $client->query($query)->read();

            \Log::info('Existing user check', [
                'username' => $request->username,
                'exists' => !empty($response),
                'response' => $response,
                'query' => $query->getQuery()
            ]);

            if (empty($response)) {
                \Log::info('Creating new user', [
                    'username' => $request->username,
                    'profile' => $voucher->profile,
                    'password_length' => strlen($request->password),
                    'router_ip' => $router->ip_address
                ]);

                // Create new user
                $query = new Query('/ip/hotspot/user/add');
                $query->equal('name', $request->username);
                $query->equal('password', $request->password);
                $query->equal('profile', $voucher->profile);
                $addResponse = $client->query($query)->read();

                \Log::info('User creation response', [
                    'response' => $addResponse,
                    'query' => $query->getQuery(),
                    'router_ip' => $router->ip_address
                ]);

                // Verify user was created
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $request->username);
                $verifyResponse = $client->query($query)->read();

                \Log::info('User verification', [
                    'created' => !empty($verifyResponse),
                    'response' => $verifyResponse,
                    'query' => $query->getQuery(),
                    'router_ip' => $router->ip_address
                ]);

                if (!empty($verifyResponse)) {
                    // Mark voucher as used
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now()
                    ]);

                    \Log::info('Voucher marked as used', [
                        'voucher_id' => $voucher->id,
                        'status' => 'used',
                        'used_at' => now(),
                        'router_ip' => $router->ip_address
                    ]);
                } else {
                    throw new \Exception('Failed to verify user creation');
                }
            }

            // Test if user can authenticate
            try {
                $testQuery = new Query('/ip/hotspot/active/print');
                $testQuery->where('user', $request->username);
                $activeResponse = $client->query($testQuery)->read();
                
                \Log::info('User authentication test', [
                    'username' => $request->username,
                    'active' => !empty($activeResponse),
                    'response' => $activeResponse,
                    'router_ip' => $router->ip_address
                ]);
            } catch (\Exception $e) {
                \Log::warning('User authentication test failed', [
                    'error' => $e->getMessage(),
                    'router_ip' => $router->ip_address
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Authentication successful'
            ]);
        } catch (\Exception $e) {
            \Log::error('Authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'username' => $request->username,
                'router_id' => $voucher->router_id ?? null,
                'router_ip' => $router->ip_address ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with router: ' . $e->getMessage()
            ], 500);
        }
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
