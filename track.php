<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/db.php';

header('Content-Type: application/json; charset=utf-8');

$config = tracker_load_config();

if ($config === null) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'not_configured']);
    exit;
}

date_default_timezone_set($config['timezone'] ?? 'UTC');

$allowedOrigin = $config['allowed_origin'] ?? '*';

if ($allowedOrigin === '*') {
    header('Access-Control-Allow-Origin: *');
} else {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    if ($origin !== '' && $origin === $allowedOrigin) {
        header('Access-Control-Allow-Origin: ' . $origin);
        header('Vary: Origin');
    }
}

header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$input = [];
$raw = file_get_contents('php://input');

if ($raw) {
    $decoded = json_decode($raw, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}

$input = $input + $_GET + $_POST;

$rawPath = isset($input['path'])
    ? (string)$input['path']
    : (string)(parse_url($_SERVER['HTTP_REFERER'] ?? '', PHP_URL_PATH) ?: '/');

$path = '/' . ltrim(substr($rawPath, 0, 500), '/');

$fullUrl = isset($input['url'])
    ? substr((string)$input['url'], 0, 1000)
    : null;

$referrer = null;

if (!empty($config['track_referrer'])) {
    $referrer = substr(
        (string)($input['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? ''),
        0,
        1000
    ) ?: null;
}

$utmSource = $utmMedium = $utmCampaign = $utmTerm = $utmContent = null;

if (!empty($config['track_utm'])) {
    $utmSource = isset($input['utm_source'])
        ? substr((string)$input['utm_source'], 0, 255)
        : null;

    $utmMedium = isset($input['utm_medium'])
        ? substr((string)$input['utm_medium'], 0, 255)
        : null;

    $utmCampaign = isset($input['utm_campaign'])
        ? substr((string)$input['utm_campaign'], 0, 255)
        : null;

    $utmTerm = isset($input['utm_term'])
        ? substr((string)$input['utm_term'], 0, 255)
        : null;

    $utmContent = isset($input['utm_content'])
        ? substr((string)$input['utm_content'], 0, 255)
        : null;
}

$userAgentRaw = $_SERVER['HTTP_USER_AGENT'] ?? '';

if (!empty($config['exclude_bots']) && tracker_is_bot($userAgentRaw)) {
    echo json_encode(['status' => 'ignored', 'reason' => 'bot']);
    exit;
}

$browser = $os = $deviceType = null;
$userAgentToStore = null;

if (!empty($config['track_user_agent'])) {
    $userAgentToStore = substr($userAgentRaw, 0, 500);
    [$browser, $os, $deviceType] = tracker_parse_user_agent($userAgentRaw);
}

$ip = tracker_get_client_ip($config);

$visitorId = $_COOKIE['tracker_vid'] ?? null;

if (!$visitorId) {
    $visitorId = tracker_uuid4();

    setcookie('tracker_vid', $visitorId, [
        'expires' => time() + ($config['session_lifetime'] ?? (3600 * 24 * 365)),
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

$language = isset($input['lang'])
    ? substr((string)$input['lang'], 0, 20)
    : null;

$screen = isset($input['screen'])
    ? substr((string)$input['screen'], 0, 20)
    : null;

$createdAt = date('Y-m-d H:i:s');

$pdo = tracker_db();

/*
 * Une seule connexion par jour et par IP.
 *
 * Si le suivi IP est désactivé, le cookie visiteur est utilisé
 * comme solution de remplacement.
 */
$connectionKey = $ip ?: $visitorId;

$stmt = $pdo->prepare("
    SELECT id
    FROM visits
    WHERE date(created_at) = :day
      AND COALESCE(ip, visitor_id) = :connection_key
    ORDER BY id ASC
    LIMIT 1
");

$stmt->execute([
    ':day' => date('Y-m-d'),
    ':connection_key' => $connectionKey
]);

if ($stmt->fetchColumn() !== false) {
    echo json_encode([
        'status' => 'already_tracked',
        'message' => 'Une connexion pour cette IP est déjà enregistrée aujourd’hui.'
    ]);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO visits (
        created_at,
        visitor_id,
        path,
        full_url,
        referrer,
        utm_source,
        utm_medium,
        utm_campaign,
        utm_term,
        utm_content,
        ip,
        user_agent,
        browser,
        os,
        device_type,
        language,
        screen_resolution
    )
    VALUES (
        :created_at,
        :visitor_id,
        :path,
        :full_url,
        :referrer,
        :utm_source,
        :utm_medium,
        :utm_campaign,
        :utm_term,
        :utm_content,
        :ip,
        :user_agent,
        :browser,
        :os,
        :device_type,
        :language,
        :screen_resolution
    )
");

$stmt->execute([
    ':created_at' => $createdAt,
    ':visitor_id' => $visitorId,
    ':path' => $path,
    ':full_url' => $fullUrl,
    ':referrer' => $referrer,
    ':utm_source' => $utmSource,
    ':utm_medium' => $utmMedium,
    ':utm_campaign' => $utmCampaign,
    ':utm_term' => $utmTerm,
    ':utm_content' => $utmContent,
    ':ip' => $ip,
    ':user_agent' => $userAgentToStore,
    ':browser' => $browser,
    ':os' => $os,
    ':device_type' => $deviceType,
    ':language' => $language,
    ':screen_resolution' => $screen,
]);

echo json_encode([
    'status' => 'ok',
    'tracked' => true
]);
