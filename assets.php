<?php
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['btn'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing btn']);
    exit;
}

$buttonType = $input['btn'];

function getUserIP() {
    $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
    foreach ($ipKeys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'Bilinmiyor';
}

function getLocationInfo($ip) {
    if ($ip === 'Bilinmiyor' || empty($ip) || $ip === '127.0.0.1' || $ip === '::1' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return [
            'country' => 'Bilinmiyor',
            'city' => 'Bilinmiyor',
            'region' => 'Bilinmiyor',
            'timezone' => 'Bilinmiyor',
            'isp' => 'Bilinmiyor',
            'latitude' => 'Bilinmiyor',
            'longitude' => 'Bilinmiyor'
        ];
    }
    
    $result = [
        'country' => 'Bilinmiyor',
        'city' => 'Bilinmiyor',
        'region' => 'Bilinmiyor',
        'timezone' => 'Bilinmiyor',
        'isp' => 'Bilinmiyor',
        'latitude' => 'Bilinmiyor',
        'longitude' => 'Bilinmiyor'
    ];
    
    $apis = [
        [
            'url' => "http://ip-api.com/json/{$ip}?fields=status,message,country,city,regionName,timezone,isp,lat,lon",
            'type' => 'ip-api'
        ],
        [
            'url' => "https://ipapi.co/{$ip}/json/",
            'type' => 'ipapi'
        ],
        [
            'url' => "https://ipwhois.app/json/{$ip}",
            'type' => 'ipwhois'
        ]
    ];
    
    foreach ($apis as $api) {
        try {
            $ch = curl_init($api['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 8);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($response === false || $httpCode !== 200) {
                continue;
            }
            
            $data = json_decode($response, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                continue;
            }
            
            if ($api['type'] === 'ip-api') {
                if (isset($data['status']) && $data['status'] === 'success') {
                    $result['country'] = !empty($data['country']) ? $data['country'] : 'Bilinmiyor';
                    $result['city'] = !empty($data['city']) ? $data['city'] : 'Bilinmiyor';
                    $result['region'] = !empty($data['regionName']) ? $data['regionName'] : 'Bilinmiyor';
                    $result['timezone'] = !empty($data['timezone']) ? $data['timezone'] : 'Bilinmiyor';
                    $result['isp'] = !empty($data['isp']) ? $data['isp'] : 'Bilinmiyor';
                    $result['latitude'] = isset($data['lat']) ? $data['lat'] : 'Bilinmiyor';
                    $result['longitude'] = isset($data['lon']) ? $data['lon'] : 'Bilinmiyor';
                    if ($result['country'] !== 'Bilinmiyor') break;
                }
            } elseif ($api['type'] === 'ipapi') {
                if (isset($data['country_name']) || isset($data['country'])) {
                    $result['country'] = !empty($data['country_name']) ? $data['country_name'] : (!empty($data['country']) ? $data['country'] : 'Bilinmiyor');
                    $result['city'] = !empty($data['city']) ? $data['city'] : 'Bilinmiyor';
                    $result['region'] = !empty($data['region']) ? $data['region'] : (!empty($data['region_name']) ? $data['region_name'] : 'Bilinmiyor');
                    $result['timezone'] = !empty($data['timezone']) ? $data['timezone'] : 'Bilinmiyor';
                    $result['isp'] = !empty($data['org']) ? $data['org'] : (!empty($data['isp']) ? $data['isp'] : 'Bilinmiyor');
                    $result['latitude'] = isset($data['latitude']) ? $data['latitude'] : (isset($data['lat']) ? $data['lat'] : 'Bilinmiyor');
                    $result['longitude'] = isset($data['longitude']) ? $data['longitude'] : (isset($data['lon']) ? $data['lon'] : 'Bilinmiyor');
                    if ($result['country'] !== 'Bilinmiyor') break;
                }
            } elseif ($api['type'] === 'ipwhois') {
                if (isset($data['success']) && $data['success'] === true) {
                    $result['country'] = !empty($data['country']) ? $data['country'] : 'Bilinmiyor';
                    $result['city'] = !empty($data['city']) ? $data['city'] : 'Bilinmiyor';
                    $result['region'] = !empty($data['region']) ? $data['region'] : 'Bilinmiyor';
                    $result['timezone'] = !empty($data['timezone']) ? $data['timezone'] : 'Bilinmiyor';
                    $result['isp'] = !empty($data['isp']) ? $data['isp'] : (!empty($data['org']) ? $data['org'] : 'Bilinmiyor');
                    $result['latitude'] = isset($data['latitude']) ? $data['latitude'] : 'Bilinmiyor';
                    $result['longitude'] = isset($data['longitude']) ? $data['longitude'] : 'Bilinmiyor';
                    if ($result['country'] !== 'Bilinmiyor') break;
                }
            }
        } catch (Exception $e) {
            continue;
        }
    }
    
    return $result;
}

function getConnectionInfo($userAgent) {
    $connection = [
        'effectiveType' => 'Bilinmiyor',
        'downlink' => 'Bilinmiyor',
        'rtt' => 'Bilinmiyor',
        'saveData' => 'Bilinmiyor',
        'type' => 'Bilinmiyor'
    ];
    
    if (preg_match('/Mobile|Android|iPhone|iPad/', $userAgent)) {
        $connection['type'] = 'cellular';
        $connection['effectiveType'] = '4g';
    } else {
        $connection['type'] = 'wifi';
        $connection['effectiveType'] = '4g';
    }
    
    return $connection;
}

$ip = getUserIP();
$location = getLocationInfo($ip);
$connection = getConnectionInfo($_SERVER['HTTP_USER_AGENT'] ?? '');

$locationText = 'Bilinmiyor';
if (isset($location['city']) && $location['city'] !== 'Bilinmiyor' && 
    isset($location['country']) && $location['country'] !== 'Bilinmiyor') {
    $locationText = $location['city'] . ', ' . $location['country'];
} elseif (isset($location['country']) && $location['country'] !== 'Bilinmiyor') {
    $locationText = $location['country'];
}

$connectionType = 'Sabit';
$connectionStatus = 'Normal';

if (isset($connection['type']) && $connection['type'] !== 'Bilinmiyor') {
    $type = strtolower($connection['type']);
    if (strpos($type, 'cellular') !== false || strpos($type, '4g') !== false || 
        strpos($type, '3g') !== false || strpos($type, '2g') !== false) {
        $connectionType = 'Mobil';
    }
} elseif (isset($connection['effectiveType']) && $connection['effectiveType'] !== 'Bilinmiyor') {
    $effType = strtolower($connection['effectiveType']);
    if (strpos($effType, '4g') !== false || strpos($effType, '3g') !== false || strpos($effType, '2g') !== false) {
        $connectionType = 'Mobil';
    }
}

$connectionText = $connectionType . ' | ' . $connectionStatus;

$DISCORD_WEBHOOK_URL = "https://discord.com/api/webhooks/1463916068455317558/B3MNd_cegJvPSU1vVPo_sMvWSaeCylJnCStw6g-lM2I1t1bOVrqvdZgqLLmO4B_RddOc";

$embed = [
    'title' => 'biri st indiriyo',
    'color' => 16711935,
    'fields' => [
        [
            'name' => 'IP Adresi',
            'value' => '`' . $ip . '`',
            'inline' => true
        ],
        [
            'name' => 'Konum',
            'value' => $locationText,
            'inline' => true
        ],
        [
            'name' => 'Sağlayıcı',
            'value' => (isset($location['isp']) && $location['isp'] !== 'Bilinmiyor') 
                ? '`' . $location['isp'] . '`' 
                : 'Bilinmiyor',
            'inline' => true
        ],
        [
            'name' => 'Bağlantı',
            'value' => $connectionText,
            'inline' => false
        ]
    ],
    'timestamp' => date('c')
];

$payload = [
    'embeds' => [$embed]
];

$ch = curl_init($DISCORD_WEBHOOK_URL);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['success' => true, 'message' => 'Webhook gönderildi']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Webhook gönderilemedi: ' . $curlError]);
}
?>

