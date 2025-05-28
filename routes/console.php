<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Models\Router;
use App\Models\Voucher;
use RouterOS\Client;
use RouterOS\Query;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('vouchers:sync-usage {router_id?}', function (?string $routerId = null) {
    // Get routers to check
    if ($routerId) {
        $routers = Router::where('id', $routerId)->get();
    } else {
        $routers = Router::all();
    }
    
    if ($routers->isEmpty()) {
        $this->error('No routers found to sync with.');
        return 1;
    }
    
    $this->info('Starting voucher usage sync...');
    
    $totalUpdated = 0;
    $totalDisabled = 0;
    $totalErrors = 0;
    
    foreach ($routers as $router) {
        $this->info("Checking router: {$router->name} ({$router->ip_address})");
        
        try {
            // Connect to router
            $client = new Client([
                'host' => $router->ip_address,
                'user' => $router->username,
                'pass' => $router->password,
                'port' => $router->port ?? 8728,
                'timeout' => 10,
            ]);
            
            // Get active sessions
            $query = new Query('/ip/hotspot/active/print');
            $activeSessions = $client->query($query)->read();
            
            $this->info("Found " . count($activeSessions) . " active sessions");
            
            // Get all active vouchers for this router
            $activeVouchers = Voucher::where('router_id', $router->id)
                ->where('status', 'active')
                ->get();
            
            $this->info("Found " . $activeVouchers->count() . " active vouchers in database");
            
            // Check usage statistics for each active voucher
            $query = new Query('/ip/hotspot/user/print');
            $hotspotUsers = $client->query($query)->read();
            
            // Create a map of username to usage data
            $usageMap = [];
            foreach ($hotspotUsers as $user) {
                if (isset($user['name'])) {
                    $usageMap[$user['name']] = [
                        'bytes_in' => $user['bytes-in'] ?? 0,
                        'bytes_out' => $user['bytes-out'] ?? 0,
                        'uptime' => $user['uptime'] ?? '0s',
                    ];
                }
            }
            
            // Check active sessions
            $activeSessionUsers = [];
            foreach ($activeSessions as $session) {
                if (isset($session['user'])) {
                    $activeSessionUsers[] = $session['user'];
                }
            }
            
            // Mark vouchers as used if they have usage or active sessions
            foreach ($activeVouchers as $voucher) {
                $username = $voucher->username;
                
                // Check if voucher has been used (has usage data or active session)
                if (
                    (isset($usageMap[$username]) && 
                     ($usageMap[$username]['bytes_in'] > 0 || $usageMap[$username]['bytes_out'] > 0 || $usageMap[$username]['uptime'] !== '0s')) ||
                    in_array($username, $activeSessionUsers)
                ) {
                    // Mark as used if not already
                    if ($voucher->status === 'active') {
                        $voucher->update([
                            'status' => 'used',
                            'used_at' => now()
                        ]);
                        $this->info("Marked voucher {$username} as used");
                        $totalUpdated++;
                    }
                }
            }
            
            // Also check for disabled/deleted vouchers and disable them in MikroTik
            $nonActiveVouchers = Voucher::where('router_id', $router->id)
                ->whereIn('status', ['used', 'expired'])
                ->get();
            
            foreach ($nonActiveVouchers as $voucher) {
                $username = $voucher->username;
                
                // Check if user exists in MikroTik
                $query = new Query('/ip/hotspot/user/print');
                $query->where('name', $username);
                $userResponse = $client->query($query)->read();
                
                if (!empty($userResponse)) {
                    // User exists, check if it's disabled
                    $userId = $userResponse[0]['.id'];
                    $isDisabled = $userResponse[0]['disabled'] ?? 'false';
                    
                    if ($isDisabled !== 'true') {
                        // Disable the user
                        $query = new Query('/ip/hotspot/user/set');
                        $query->equal('.id', $userId);
                        $query->equal('disabled', 'true');
                        $client->query($query)->read();
                        
                        $this->info("Disabled MikroTik user for voucher {$username}");
                        $totalDisabled++;
                    }
                }
            }
            
        } catch (\Exception $e) {
            $this->error("Error connecting to router {$router->name}: {$e->getMessage()}");
            $totalErrors++;
        }
    }
    
    $this->info("Sync completed. Updated: {$totalUpdated}, Disabled: {$totalDisabled}, Errors: {$totalErrors}");
})->purpose('Sync voucher usage status with MikroTik router');
