<?php

require 'vendor/autoload.php';

use RouterOS\Client;
use RouterOS\Query;

try {
    $client = new Client([
        'host' => '172.16.0.1',
        'user' => 'jtgadmin',
        'pass' => 'jtg@2025',
        'port' => 8728
    ]);

    $query = new Query('/system/resource/print');
    $response = $client->query($query)->read();

    print_r($response);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}