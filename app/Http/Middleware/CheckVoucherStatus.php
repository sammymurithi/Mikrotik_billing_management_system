<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Voucher;
use App\Models\Router;
use RouterOS\Client;
use RouterOS\Query;

class CheckVoucherStatus
{
    public function handle(Request $request, Closure $next)
    {
        \Log::info('Middleware: Authentication attempt', [
            'username' => $request->input('username'),
            'ip' => $request->ip(),
            'headers' => $request->headers->all(),
            'request_method' => $request->method(),
            'request_path' => $request->path()
        ]);

        $username = $request->input('username');
        $password = $request->input('password');

        if (!$username || !$password) {
            \Log::warning('Middleware: Missing credentials', [
                'username' => $username,
                'password' => $password ? 'provided' : 'missing',
                'request_data' => $request->all()
            ]);
            return response()->json(['error' => 'Username and password are required'], 400);
        }

        // Find the voucher
        $voucher = Voucher::where('username', $username)
            ->where('password', $password)
            ->first();

        \Log::info('Middleware: Voucher lookup result', [
            'found' => (bool)$voucher,
            'username' => $username,
            'voucher_details' => $voucher ? [
                'id' => $voucher->id,
                'status' => $voucher->status,
                'profile' => $voucher->profile,
                'router_id' => $voucher->router_id,
                'created_at' => $voucher->created_at,
                'used_at' => $voucher->used_at
            ] : null
        ]);

        if (!$voucher) {
            \Log::warning('Middleware: Invalid credentials', [
                'username' => $username,
                'password_length' => strlen($password)
            ]);
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        // Check if voucher is active
        if ($voucher->status !== 'active') {
            \Log::warning('Middleware: Voucher already used', [
                'voucher_id' => $voucher->id,
                'status' => $voucher->status,
                'used_at' => $voucher->used_at
            ]);
            return response()->json(['error' => 'Voucher has already been used'], 401);
        }

        // Get the router
        $router = Router::find($voucher->router_id);
        if (!$router) {
            \Log::error('Middleware: Router not found', [
                'router_id' => $voucher->router_id,
                'voucher_id' => $voucher->id
            ]);
            return response()->json(['error' => 'Router not found'], 500);
        }

        try {
            \Log::info('Middleware: Router details', [
                'router_id' => $router->id,
                'ip' => $router->ip_address,
                'port' => $router->port,
                'username' => $router->username,
                'password_length' => strlen($router->password)
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
                \Log::info('Middleware: Router connection test successful', [
                    'response' => $testResponse
                ]);
            } catch (\Exception $e) {
                \Log::error('Middleware: Router connection test failed', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Failed to connect to router: ' . $e->getMessage());
            }

            // Check if user already exists
            $query = new Query('/ip/hotspot/user/print');
            $query->where('name', $username);
            $response = $client->query($query)->read();

            \Log::info('Middleware: Existing user check', [
                'username' => $username,
                'exists' => !empty($response),
                'response' => $response
            ]);

            if (empty($response)) {
                \Log::info('Middleware: Creating new user', [
                    'username' => $username,
                    'profile' => $voucher->profile,
                    'password_length' => strlen($password)
                ]);

                // Create new user
                $query = new Query('/ip/hotspot/user/add');
                $query->equal('name', $username);
                $query->equal('password', $password);
                $query->equal('profile', $voucher->profile);
                $addResponse = $client->query($query)->read();

                \Log::info('Middleware: User creation response', [
                    'response' => $addResponse,
                    'query' => $query->getQuery()
                ]);

                // Verify user was created
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $username);
                $verifyResponse = $client->query($query)->read();

                \Log::info('Middleware: User verification', [
                    'created' => !empty($verifyResponse),
                    'response' => $verifyResponse,
                    'query' => $query->getQuery()
                ]);

                if (!empty($verifyResponse)) {
                    // Mark voucher as used
                    $voucher->update([
                        'status' => 'used',
                        'used_at' => now()
                    ]);

                    \Log::info('Middleware: Voucher marked as used', [
                        'voucher_id' => $voucher->id,
                        'status' => 'used',
                        'used_at' => now()
                    ]);
                } else {
                    throw new \Exception('Failed to verify user creation');
                }
            }

            // Test if user can authenticate
            try {
                $testQuery = new Query('/ip/hotspot/active/print');
                $testQuery->where('user', $username);
                $activeResponse = $client->query($testQuery)->read();
                
                \Log::info('Middleware: User authentication test', [
                    'username' => $username,
                    'active' => !empty($activeResponse),
                    'response' => $activeResponse
                ]);
            } catch (\Exception $e) {
                \Log::warning('Middleware: User authentication test failed', [
                    'error' => $e->getMessage()
                ]);
            }

            return $next($request);
        } catch (\Exception $e) {
            \Log::error('Middleware: Authentication failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'username' => $username,
                'router_id' => $voucher->router_id ?? null
            ]);

            return response()->json(['error' => 'Failed to authenticate with router: ' . $e->getMessage()], 500);
        }
    }
} 