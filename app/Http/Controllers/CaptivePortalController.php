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
        // Create a debug log file specifically for this authentication attempt
        $debugId = uniqid();
        \Log::channel('daily')->info('===== AUTHENTICATION ATTEMPT START [$debugId] =====');
        \Log::channel('daily')->info('Raw request data', [
            'debug_id' => $debugId,
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

        // First check if the voucher exists at all, regardless of status
        $anyVoucher = Voucher::where('username', $request->username)
            ->where('password', $request->password)
            ->first();
            
        \Log::channel('daily')->info('Initial voucher lookup', [
            'debug_id' => $debugId,
            'exists' => (bool)$anyVoucher,
            'details' => $anyVoucher ? [
                'id' => $anyVoucher->id,
                'status' => $anyVoucher->status,
                'used_at' => $anyVoucher->used_at
            ] : null
        ]);
        
        // Now check for active vouchers only
        $voucher = Voucher::where('username', $request->username)
            ->where('password', $request->password)
            ->where(function($query) {
                // Allow any active voucher
                $query->where('status', 'active');
            })
            ->first();
            
        \Log::channel('daily')->info('Active voucher lookup', [
            'debug_id' => $debugId,
            'found_active' => (bool)$voucher,
            'username' => $request->username,
            'password_hint' => $request->password ? substr($request->password, 0, 2) . '***' : 'missing'
        ]);

        \Log::channel('daily')->info('Voucher lookup details', [
            'debug_id' => $debugId,
            'found' => (bool)$voucher,
            'username' => $request->username,
            'password_length' => strlen($request->password),
            'voucher_details' => $voucher ? [
                'id' => $voucher->id,
                'status' => $voucher->status,
                'profile' => $voucher->profile,
                'router_id' => $voucher->router_id,
                'created_at' => $voucher->created_at,
                'used_at' => $voucher->used_at,
                'limit_mb' => $voucher->limit_mb
            ] : null,
            'sql_query' => Voucher::where('username', $request->username)
                ->where('password', $request->password)
                ->where('status', 'active')
                ->toSql()
        ]);

        if (!$voucher) {
            // Check if the voucher exists but is not active
            if ($anyVoucher) {
                \Log::channel('daily')->warning('Voucher exists but is not active', [
                    'debug_id' => $debugId,
                    'username' => $request->username,
                    'status' => $anyVoucher->status,
                    'used_at' => $anyVoucher->used_at
                ]);
                
                return response()->json([
                    'success' => false,
                    'message' => 'Voucher has already been used',
                    'status' => $anyVoucher->status,
                    'used_at' => $anyVoucher->used_at ? $anyVoucher->used_at->format('Y-m-d H:i:s') : null
                ], 401);
            }
            
            \Log::channel('daily')->warning('Invalid credentials - voucher not found', [
                'debug_id' => $debugId,
                'username' => $request->username,
                'password_length' => strlen($request->password),
                'request_data' => $request->all()
            ]);
            
            \Log::channel('daily')->info('===== AUTHENTICATION ATTEMPT FAILED [$debugId] =====');
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials - voucher not found'
            ], 401);
        }

        try {
            $router = $voucher->router;
            
            if (!$router) {
                \Log::channel('daily')->error('Router not found for voucher', [
                    'debug_id' => $debugId,
                    'voucher_id' => $voucher->id,
                    'router_id' => $voucher->router_id
                ]);
                
                \Log::channel('daily')->info('===== AUTHENTICATION ATTEMPT FAILED [$debugId] =====');
                
                return response()->json([
                    'success' => false,
                    'message' => 'Router configuration not found'
                ], 500);
            }
            
            \Log::channel('daily')->info('Router details', [
                'debug_id' => $debugId,
                'router_id' => $router->id,
                'ip' => $router->ip_address,
                'port' => $router->port,
                'username' => $router->username,
                'password_length' => strlen($router->password),
                'router_name' => $router->name
            ]);

            // Test connection to router first
            try {
                // Explicitly set configuration for better debugging
                $clientConfig = [
                    'host' => $router->ip_address,
                    'user' => $router->username,
                    'pass' => $router->password,
                    'port' => $router->port ?? 8728,
                    'timeout' => 10, // Increase timeout for better reliability
                    'attempts' => 3, // Try multiple times
                ];
                
                \Log::channel('daily')->info('MikroTik client configuration', [
                    'debug_id' => $debugId,
                    'host' => $clientConfig['host'],
                    'user' => $clientConfig['user'],
                    'port' => $clientConfig['port'],
                    'timeout' => $clientConfig['timeout'],
                    'attempts' => $clientConfig['attempts']
                ]);
                
                $client = new Client($clientConfig);

                // Test connection with a simple query
                $testQuery = new Query('/system/resource/print');
                $testResponse = $client->query($testQuery)->read();
                
                \Log::channel('daily')->info('Router connection test successful', [
                    'debug_id' => $debugId,
                    'response' => $testResponse,
                    'router_ip' => $router->ip_address,
                    'version' => $testResponse[0]['version'] ?? 'unknown'
                ]);
            } catch (\Exception $e) {
                \Log::channel('daily')->error('Router connection test failed', [
                    'debug_id' => $debugId,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'router_ip' => $router->ip_address,
                    'router_port' => $router->port
                ]);
                
                \Log::channel('daily')->info('===== AUTHENTICATION ATTEMPT FAILED [$debugId] =====');
                
                throw new \Exception('Failed to connect to router: ' . $e->getMessage());
            }

            // Check if user already exists
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $request->username);
            
            try {
                $response = $client->query($query)->read();
                
                \Log::channel('daily')->info('Existing user check', [
                    'debug_id' => $debugId,
                    'username' => $request->username,
                    'exists' => !empty($response),
                    'response' => $response,
                    'query' => $query->getQuery()
                ]);
            } catch (\Exception $e) {
                \Log::channel('daily')->error('Failed to check if user exists', [
                    'debug_id' => $debugId,
                    'error' => $e->getMessage(),
                    'username' => $request->username
                ]);
                
                // Assume user doesn't exist and try to create
                $response = [];
            }

            if (empty($response)) {
                \Log::channel('daily')->info('Creating new user', [
                    'debug_id' => $debugId,
                    'username' => $request->username,
                    'profile' => is_string($voucher->profile) ? $voucher->profile : json_encode($voucher->profile),
                    'password_length' => strlen($request->password),
                    'router_ip' => $router->ip_address,
                    'limit_mb' => $voucher->limit_mb
                ]);

                // Create new user
                try {
                    $query = new Query('/ip/hotspot/user/add');
                    $query->equal('name', $request->username);
                    $query->equal('password', $request->password);
                    
                    // Handle profile which might be a string or an object
                    $profileName = is_string($voucher->profile) ? $voucher->profile : 
                        (is_object($voucher->profile) && isset($voucher->profile->name) ? $voucher->profile->name : 'default');
                    
                    // Log the exact profile name being used
                    \Log::channel('daily')->info('Using profile name', [
                        'debug_id' => $debugId,
                        'profile_name' => $profileName,
                        'original_profile' => is_string($voucher->profile) ? 'string' : 'object'
                    ]);
                    
                    // Set the limit for the user based on voucher's limit_mb
                    if ($voucher->limit_mb > 0) {
                        $byteLimit = $voucher->limit_mb * 1024 * 1024; // Convert MB to bytes
                        $query->equal('limit-bytes-total', $byteLimit);
                        
                        \Log::channel('daily')->info('Setting byte limit', [
                            'debug_id' => $debugId,
                            'limit_mb' => $voucher->limit_mb,
                            'limit_bytes' => $byteLimit
                        ]);
                    }
                    
                    $query->equal('profile', $profileName);
                    
                    // Log the full query before execution
                    \Log::channel('daily')->info('User creation query', [
                        'debug_id' => $debugId,
                        'query' => $query->getQuery()
                    ]);
                    
                    $addResponse = $client->query($query)->read();

                    \Log::channel('daily')->info('User creation successful', [
                        'debug_id' => $debugId,
                        'response' => $addResponse,
                        'router_ip' => $router->ip_address
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('daily')->error('Failed to create user', [
                        'debug_id' => $debugId,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'username' => $request->username,
                        'profile' => $profileName
                    ]);
                    
                    // Check if error is because user already exists
                    if (strpos($e->getMessage(), 'already have') !== false) {
                        \Log::channel('daily')->info('User already exists, continuing', [
                            'debug_id' => $debugId,
                            'username' => $request->username
                        ]);
                    } else {
                        throw $e; // Re-throw if it's a different error
                    }
                }

                // Verify user was created
                try {
                    $query = new Query('/ip/hotspot/user/print');
                    $query->where('name', $request->username);
                    $verifyResponse = $client->query($query)->read();

                    \Log::channel('daily')->info('User verification', [
                        'debug_id' => $debugId,
                        'created' => !empty($verifyResponse),
                        'response' => $verifyResponse,
                        'query' => $query->getQuery(),
                        'router_ip' => $router->ip_address
                    ]);
                } catch (\Exception $e) {
                    \Log::channel('daily')->error('Failed to verify user creation', [
                        'debug_id' => $debugId,
                        'error' => $e->getMessage(),
                        'username' => $request->username
                    ]);
                    
                    // Assume verification failed
                    $verifyResponse = [];
                }

                if (!empty($verifyResponse)) {
                    // Mark voucher as used
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now()
                    ]);

                    \Log::channel('daily')->info('Voucher marked as used', [
                        'debug_id' => $debugId,
                        'voucher_id' => $voucher->id,
                        'status' => 'used',
                        'used_at' => now()->format('Y-m-d H:i:s'),
                        'router_ip' => $router->ip_address
                    ]);
                } else {
                    \Log::channel('daily')->error('User creation verification failed', [
                        'debug_id' => $debugId,
                        'username' => $request->username
                    ]);
                    
                    throw new \Exception('Failed to verify user creation');
                }
            }

            // Try to log the user in by creating a login entry
            try {
                // First check if there's an active session
                $testQuery = new Query('/ip/hotspot/active/print');
                $testQuery->where('user', $request->username);
                $activeResponse = $client->query($testQuery)->read();
                
                \Log::info('User authentication test', [
                    'username' => $request->username,
                    'active' => !empty($activeResponse),
                    'response' => $activeResponse,
                    'router_ip' => $router->ip_address
                ]);
                
                // If no active session, try to force a login
                if (empty($activeResponse)) {
                    // Get the MAC address from the request if available
                    $mac = $request->input('mac') ?? $request->header('X-Forwarded-For') ?? $request->ip();
                    
                    // Try to create a login entry
                    $loginQuery = new Query('/ip/hotspot/active/login');
                    $loginQuery->equal('user', $request->username);
                    $loginQuery->equal('password', $request->password);
                    $loginQuery->equal('ip', $request->input('ip', $request->ip()));
                    if ($mac) {
                        $loginQuery->equal('mac-address', $mac);
                    }
                    
                    try {
                        $loginResponse = $client->query($loginQuery)->read();
                        \Log::info('Forced login attempt', [
                            'username' => $request->username,
                            'response' => $loginResponse
                        ]);
                    } catch (\Exception $e) {
                        // Login might fail, but that's okay - the user will be logged in when they connect
                        \Log::warning('Forced login failed, but user is created', [
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('User authentication test failed', [
                    'error' => $e->getMessage(),
                    'router_ip' => $router->ip_address
                ]);
            }

            \Log::channel('daily')->info('===== AUTHENTICATION ATTEMPT SUCCESSFUL [$debugId] =====');
            
            return response()->json([
                'success' => true,
                'message' => 'Authentication successful',
                'debug_id' => $debugId
            ]);
        } catch (\Exception $e) {
            \Log::channel('daily')->error('Authentication failed - exception', [
                'debug_id' => $debugId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'username' => $request->username,
                'router_id' => $voucher->router_id ?? null,
                'router_ip' => $router->ip_address ?? null
            ]);
            
            \Log::channel('daily')->info('===== AUTHENTICATION ATTEMPT FAILED [$debugId] =====');

            return response()->json([
                'success' => false,
                'message' => 'Failed to authenticate with router: ' . $e->getMessage(),
                'debug_id' => $debugId,
                'error_details' => $e->getMessage()
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
