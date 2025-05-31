<?php
session_start();
if (!isset($_SESSION['logged_in'])) {
    die("Not logged in!");
}

// Load configuration
function loadConfig() {
    if (!file_exists('config.json')) {
        return ['api_token' => '', 'domains' => []];
    }
    return json_decode(file_get_contents('config.json'), true);
}

// Save configuration
function saveConfig($config) {
    return file_put_contents('config.json', json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

// Get current IP
function getCurrentIP() {
    $ip = @file_get_contents("https://api.ipify.org");
    return $ip ?: false;
}

// Update DNS record
function updateDNSRecord($apiToken, $zoneId, $recordId, $name, $ip, $proxied = false) {
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records/$recordId";
    $data = [
        "type" => "A",
        "name" => $name,
        "content" => $ip,
        "ttl" => 120,
        "proxied" => $proxied
    ];

    $options = [
        "http" => [
            "header" => [
                "Authorization: Bearer $apiToken",
                "Content-Type: application/json"
            ],
            "method" => "PUT",
            "content" => json_encode($data)
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if ($result) {
        return json_decode($result, true);
    }
    
    return ['success' => false, 'errors' => [['message' => 'Network error']]];
}

// Log update
function logUpdate($domain, $ip, $success, $error = '') {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'domain' => $domain,
        'ip' => $ip,
        'success' => $success,
        'error' => $error
    ];
    
    $logFile = 'update_log.json';
    $logs = [];
    
    if (file_exists($logFile)) {
        $logs = json_decode(file_get_contents($logFile), true) ?: [];
    }
    
    array_unshift($logs, $logEntry);
    
    // Keep only last 100 entries
    $logs = array_slice($logs, 0, 100);
    
    file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT));
}

$config = loadConfig();
$results = [];
$currentIP = getCurrentIP();

if (!$currentIP) {
    die("❌ Error: Could not determine current IP address");
}

if (empty($config['api_token'])) {
    die("❌ Error: No API token configured");
}

// Check if specific domain should be updated
$specificDomain = isset($_GET['domain']) ? $_GET['domain'] : null;

if (isset($config['domains']) && !empty($config['domains'])) {
    foreach ($config['domains'] as $domain) {
        // Skip if specific domain requested and this isn't it
        if ($specificDomain && $domain['id'] !== $specificDomain) {
            continue;
        }
        
        // Skip disabled domains (unless specifically requested)
        if (!$domain['enabled'] && !$specificDomain) {
            continue;
        }
        
        $response = updateDNSRecord(
            $config['api_token'],
            $domain['zone_id'],
            $domain['record_id'],
            $domain['name'],
            $currentIP,
            $domain['proxied']
        );
        
        $success = $response['success'] ?? false;
        $error = '';
        
        if (!$success && isset($response['errors'])) {
            $error = implode(', ', array_column($response['errors'], 'message'));
        }
        
        $results[] = [
            'domain' => $domain['name'],
            'success' => $success,
            'error' => $error,
            'proxied' => $domain['proxied']
        ];
        
        // Log the update
        logUpdate($domain['name'], $currentIP, $success, $error);
    }
} else {
    die("❌ Error: No domains configured");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DNS Update Result</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 8px;
        }

        .ip-info {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
        }

        .ip-info h3 {
            color: #495057;
            margin-bottom: 10px;
        }

        .ip-info .ip {
            font-size: 24px;
            font-weight: bold;
            color: #667eea;
        }

        .result-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .result-success {
            border-left: 4px solid #28a745;
            background: #d4edda;
        }

        .result-error {
            border-left: 4px solid #dc3545;
            background: #f8d7da;
        }

        .result-card h4 {
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .result-card p {
            color: #666;
            margin-bottom: 5px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-proxied {
            background: #fff3cd;
            color: #856404;
        }

        .btn {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .actions {
            text-align: center;
            margin-top: 30px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔄 DNS Update Result</h1>
        </div>

        <div class="ip-info">
            <h3>Current IP Address</h3>
            <div class="ip"><?= htmlspecialchars($currentIP) ?></div>
        </div>

        <?php foreach ($results as $result): ?>
            <div class="result-card <?= $result['success'] ? 'result-success' : 'result-error' ?>">
                <h4>
                    <?= $result['success'] ? '✅' : '❌' ?>
                    <?= htmlspecialchars($result['domain']) ?>
                    <?php if ($result['proxied']): ?>
                        <span class="status-badge status-proxied">Proxied</span>
                    <?php endif; ?>
                </h4>
                
                <?php if ($result['success']): ?>
                    <p><strong>Update successful!</strong></p>
                    <p>IP address updated to <?= htmlspecialchars($currentIP) ?></p>
                <?php else: ?>
                    <p><strong>Update failed:</strong></p>
                    <p><?= htmlspecialchars($result['error']) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="actions">
            <a href="index.php" class="btn">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>
