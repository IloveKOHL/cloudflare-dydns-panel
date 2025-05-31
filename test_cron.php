<?php
// Test script to verify cron setup and configuration
// Run this manually to test your setup: php test_cron.php

// Prevent web access
if (isset($_SERVER['HTTP_HOST'])) {
    http_response_code(403);
    die("This script can only be executed from command line.\n");
}

echo "=== Cloudflare DynDNS Cron Test ===\n\n";

// Change to script directory
chdir(dirname(__FILE__));

// Test 1: Check PHP version
echo "1. PHP Version Check:\n";
echo "   PHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '7.4.0', '>=')) {
    echo "   ✅ PHP version is compatible\n";
} else {
    echo "   ❌ PHP version too old (requires 7.4+)\n";
}
echo "\n";

// Test 2: Check required extensions
echo "2. PHP Extensions Check:\n";
$required_extensions = ['json', 'curl', 'openssl'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✅ $ext extension loaded\n";
    } else {
        echo "   ❌ $ext extension missing\n";
    }
}
echo "\n";

// Test 3: Check file permissions
echo "3. File Permissions Check:\n";
$files_to_check = [
    'config.json' => 'Configuration file',
    'auto_update.php' => 'Auto update script',
    '.' => 'Current directory'
];

foreach ($files_to_check as $file => $description) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "   📁 $description ($file): $perms\n";
        
        if ($file === 'config.json') {
            if (is_readable($file) && is_writable($file)) {
                echo "      ✅ Readable and writable\n";
            } else {
                echo "      ❌ Permission issues\n";
            }
        }
    } else {
        echo "   ❌ $description ($file): Not found\n";
    }
}
echo "\n";

// Test 4: Check configuration
echo "4. Configuration Check:\n";
if (file_exists('config.json')) {
    $config = json_decode(file_get_contents('config.json'), true);
    
    if ($config) {
        echo "   ✅ Configuration file is valid JSON\n";
        
        // Check required fields
        $required_fields = ['api_token', 'domains', 'update_interval'];
        foreach ($required_fields as $field) {
            if (isset($config[$field])) {
                echo "   ✅ $field is configured\n";
            } else {
                echo "   ❌ $field is missing\n";
            }
        }
        
        // Check API token
        if (!empty($config['api_token'])) {
            echo "   ✅ API token is set\n";
        } else {
            echo "   ❌ API token is empty\n";
        }
        
        // Check domains
        if (isset($config['domains']) && is_array($config['domains'])) {
            $domain_count = count($config['domains']);
            echo "   📊 $domain_count domain(s) configured\n";
            
            $enabled_count = 0;
            foreach ($config['domains'] as $domain) {
                if (isset($domain['enabled']) && $domain['enabled']) {
                    $enabled_count++;
                }
            }
            echo "   📊 $enabled_count domain(s) enabled\n";
        }
        
        // Check update interval
        if (isset($config['update_interval'])) {
            echo "   ⏰ Update interval: {$config['update_interval']}\n";
        }
        
    } else {
        echo "   ❌ Configuration file contains invalid JSON\n";
    }
} else {
    echo "   ❌ Configuration file not found\n";
}
echo "\n";

// Test 5: Check internet connectivity
echo "5. Internet Connectivity Check:\n";
$test_urls = [
    'https://api.ipify.org' => 'IP detection service',
    'https://api.cloudflare.com/client/v4/zones' => 'Cloudflare API'
];

foreach ($test_urls as $url => $description) {
    $context = stream_context_create([
        'http' => [
            'timeout' => 10,
            'user_agent' => 'CloudflareDynDNS-Test/1.0'
        ]
    ]);
    
    $result = @file_get_contents($url, false, $context);
    if ($result !== false) {
        echo "   ✅ $description accessible\n";
    } else {
        echo "   ❌ $description not accessible\n";
    }
}
echo "\n";

// Test 6: Test IP detection
echo "6. IP Detection Test:\n";
$ip_services = [
    'https://api.ipify.org',
    'https://ipv4.icanhazip.com',
    'https://checkip.amazonaws.com'
];

$detected_ips = [];
foreach ($ip_services as $service) {
    $ip = @file_get_contents($service);
    if ($ip && filter_var(trim($ip), FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $ip = trim($ip);
        $detected_ips[] = $ip;
        echo "   ✅ $service: $ip\n";
    } else {
        echo "   ❌ $service: Failed\n";
    }
}

if (!empty($detected_ips)) {
    $unique_ips = array_unique($detected_ips);
    if (count($unique_ips) === 1) {
        echo "   ✅ All services report same IP: {$unique_ips[0]}\n";
    } else {
        echo "   ⚠️  Services report different IPs: " . implode(', ', $unique_ips) . "\n";
    }
}
echo "\n";

// Test 7: Simulate cron execution
echo "7. Cron Simulation Test:\n";
if (file_exists('auto_update.php')) {
    echo "   🔄 Testing auto_update.php execution...\n";
    
    // Capture output
    ob_start();
    $exit_code = 0;
    
    try {
        // Include the auto_update script
        include 'auto_update.php';
    } catch (Exception $e) {
        echo "   ❌ Exception: " . $e->getMessage() . "\n";
        $exit_code = 1;
    }
    
    $output = ob_get_clean();
    
    if ($output) {
        echo "   📝 Script output:\n";
        $lines = explode("\n", trim($output));
        foreach ($lines as $line) {
            if (!empty($line)) {
                echo "      $line\n";
            }
        }
    }
    
    if ($exit_code === 0) {
        echo "   ✅ Script executed successfully\n";
    } else {
        echo "   ❌ Script execution failed\n";
    }
} else {
    echo "   ❌ auto_update.php not found\n";
}
echo "\n";

// Test 8: Cron command generation
echo "8. Cron Command Test:\n";
if (file_exists('config.json')) {
    $config = json_decode(file_get_contents('config.json'), true);
    if ($config && isset($config['update_interval']) && $config['update_interval'] !== 'manual') {
        $php_path = PHP_BINARY;
        $script_path = realpath('auto_update.php');
        
        $intervals = [
            '5min' => '*/5 * * * *',
            '15min' => '*/15 * * * *',
            '30min' => '*/30 * * * *',
            '1hour' => '0 * * * *',
            '6hours' => '0 */6 * * *',
            '12hours' => '0 */12 * * *',
            'daily' => '0 0 * * *'
        ];
        
        $interval = $config['update_interval'];
        if (isset($intervals[$interval])) {
            $cron_schedule = $intervals[$interval];
            echo "   📋 Suggested cron command:\n";
            echo "   $cron_schedule $php_path $script_path\n";
            echo "\n";
            echo "   💡 To install:\n";
            echo "   1. Run: crontab -e\n";
            echo "   2. Add the line above\n";
            echo "   3. Save and exit\n";
        } else {
            echo "   ❌ Unknown update interval: $interval\n";
        }
    } else {
        echo "   ℹ️  Automatic updates disabled (manual mode)\n";
    }
} else {
    echo "   ❌ Cannot generate cron command without configuration\n";
}

echo "\n=== Test Complete ===\n";
echo "If all tests pass, your cron setup should work correctly!\n";
?>
