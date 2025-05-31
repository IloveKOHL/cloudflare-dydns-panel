<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

// Load configuration
function loadConfig() {
    if (!file_exists('config.json')) {
        return ['api_token' => ''];
    }
    return json_decode(file_get_contents('config.json'), true);
}

$config = loadConfig();

if (empty($config['api_token'])) {
    echo json_encode(['success' => false, 'error' => 'No API token configured']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_zones':
        getZones($config['api_token']);
        break;
        
    case 'get_records':
        $zoneId = $_GET['zone_id'] ?? '';
        if (empty($zoneId)) {
            echo json_encode(['success' => false, 'error' => 'Zone ID missing']);
            exit;
        }
        getRecords($config['api_token'], $zoneId);
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action']);
        break;
}

function getZones($apiToken) {
    $url = "https://api.cloudflare.com/client/v4/zones";
    $options = [
        "http" => [
            "header" => [
                "Authorization: Bearer $apiToken",
                "Content-Type: application/json"
            ],
            "method" => "GET"
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Network error while fetching zones']);
        return;
    }
    
    $response = json_decode($result, true);
    
    if (!$response['success']) {
        $errors = isset($response['errors']) ? array_column($response['errors'], 'message') : ['Unknown error'];
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        return;
    }
    
    // Sort zones by name
    $zones = $response['result'];
    usort($zones, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'zones' => $zones
    ]);
}

function getRecords($apiToken, $zoneId) {
    $url = "https://api.cloudflare.com/client/v4/zones/$zoneId/dns_records?type=A";
    $options = [
        "http" => [
            "header" => [
                "Authorization: Bearer $apiToken",
                "Content-Type: application/json"
            ],
            "method" => "GET"
        ]
    ];
    
    $context = stream_context_create($options);
    $result = @file_get_contents($url, false, $context);
    
    if (!$result) {
        echo json_encode(['success' => false, 'error' => 'Network error while fetching records']);
        return;
    }
    
    $response = json_decode($result, true);
    
    if (!$response['success']) {
        $errors = isset($response['errors']) ? array_column($response['errors'], 'message') : ['Unknown error'];
        echo json_encode(['success' => false, 'error' => implode(', ', $errors)]);
        return;
    }
    
    // Sort records by name
    $records = $response['result'];
    usort($records, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'records' => $records
    ]);
}
?>
