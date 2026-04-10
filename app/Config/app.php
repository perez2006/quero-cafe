<?php
declare(strict_types=1);

$rootPath = dirname(__DIR__, 2);
$baseUrl = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/'))), '/');
$storagePath = dirname($rootPath) . DIRECTORY_SEPARATOR . 'cafe-storage';
$secretsPath = $storagePath . DIRECTORY_SEPARATOR . 'webhook_url.txt';
if ($baseUrl === '.' || $baseUrl === '\\') {
    $baseUrl = '';
}

return [
    'app_name' => 'Quero Cafe',
    'root_path' => $rootPath,
    'base_url' => $baseUrl === '/' ? '' : $baseUrl,
    'environment' => getenv('CAFE_APP_ENV') ?: 'production',
    'timezone' => 'America/Sao_Paulo',
    'database' => [
        'path' => getenv('CAFE_DB_PATH') ?: $storagePath . DIRECTORY_SEPARATOR . 'cafe.db',
        'legacy_path' => $rootPath . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'cafe.db',
    ],
    'ldap' => [
        'host' => getenv('CAFE_LDAP_HOST') ?: 'ldap.example.local',
        'base_dn' => getenv('CAFE_LDAP_BASE_DN') ?: 'DC=example,DC=local',
        'upn_suffix' => getenv('CAFE_LDAP_UPN_SUFFIX') ?: 'example.local',
        'search_attrs' => ['cn', 'sAMAccountName', 'userPrincipalName', 'mail'],
    ],
    'notifications' => [
        'webhook_url' => getenv('CAFE_WEBHOOK_URL') ?: (is_file($secretsPath) ? trim((string) file_get_contents($secretsPath)) : ''),
    ],
    'records' => [
        'quantity_options' => ['250g' => 250, '500g' => 500],
        'event_type_options' => [
            'trouxe' => 'Trouxe cafe',
            'abriu' => 'Abriu cafe',
            'acabou' => 'Acabou cafe',
        ],
    ],
];
