<?php
declare(strict_types=1);

/**
 * Génère un UUID v4 (utilisé comme identifiant visiteur anonyme, stocké en cookie).
 */
function tracker_uuid4(): string
{
    $data = random_bytes(16);
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Récupère l'IP du client selon la configuration (respect du toggle track_ip,
 * du mode proxy/CDN et de l'anonymisation).
 */
function tracker_get_client_ip(array $config): ?string
{
    if (empty($config['track_ip'])) {
        return null;
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? null;

    if (!empty($config['trust_proxy_headers']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $parts = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $ip = trim($parts[0]);
    }

    if ($ip && !empty($config['anonymize_ip'])) {
        $ip = tracker_anonymize_ip($ip);
    }

    return $ip;
}

/**
 * Masque le dernier octet (IPv4) ou les 4 derniers groupes (IPv6).
 */
function tracker_anonymize_ip(string $ip): string
{
    if (str_contains($ip, '.')) {
        $parts = explode('.', $ip);
        if (count($parts) === 4) {
            $parts[3] = '0';
            return implode('.', $parts);
        }
    } elseif (str_contains($ip, ':')) {
        $parts = explode(':', $ip);
        $keep = array_slice($parts, 0, 4);
        while (count($keep) < 8) {
            $keep[] = '0';
        }
        return implode(':', $keep);
    }

    return $ip;
}

/**
 * Détection simple de bots via User-Agent (liste non exhaustive mais couvre les cas courants).
 */
function tracker_is_bot(string $userAgent): bool
{
    if ($userAgent === '') {
        return true;
    }

    $bots = [
        'bot', 'spider', 'crawl', 'slurp', 'mediapartners', 'facebookexternalhit',
        'curl/', 'wget/', 'python-requests', 'headlesschrome', 'pingdom',
        'uptimerobot', 'ahrefsbot', 'semrushbot', 'bingpreview', 'yandexbot',
    ];

    $ua = strtolower($userAgent);
    foreach ($bots as $needle) {
        if (str_contains($ua, $needle)) {
            return true;
        }
    }

    return false;
}

/**
 * Parsing basique du User-Agent : renvoie [navigateur, OS, type d'appareil].
 * Volontairement simple et sans dépendance externe.
 */
function tracker_parse_user_agent(string $ua): array
{
    $browser = 'Autre';
    $os = 'Autre';
    $device = 'desktop';

    $browserPatterns = [
        'Edg/'             => 'Edge',
        'OPR/'             => 'Opera',
        'Opera'            => 'Opera',
        'CriOS/'           => 'Chrome',
        'Chrome/'          => 'Chrome',
        'FxiOS/'           => 'Firefox',
        'Firefox/'         => 'Firefox',
        'Version/.*Safari' => 'Safari',
        'Safari/'          => 'Safari',
        'MSIE '            => 'Internet Explorer',
        'Trident/'         => 'Internet Explorer',
    ];

    foreach ($browserPatterns as $pattern => $name) {
        if (preg_match('/' . $pattern . '/i', $ua)) {
            $browser = $name;
            break;
        }
    }

    if (preg_match('/Windows NT 10/i', $ua)) {
        $os = 'Windows 10/11';
    } elseif (preg_match('/Windows NT/i', $ua)) {
        $os = 'Windows';
    } elseif (preg_match('/Mac OS X/i', $ua)) {
        $os = 'macOS';
    } elseif (preg_match('/Android/i', $ua)) {
        $os = 'Android';
    } elseif (preg_match('/iPhone|iPad|iPod/i', $ua)) {
        $os = 'iOS';
    } elseif (preg_match('/Linux/i', $ua)) {
        $os = 'Linux';
    }

    if (preg_match('/iPad|Tablet/i', $ua)) {
        $device = 'tablet';
    } elseif (preg_match('/Mobi|Android.*Mobile|iPhone|iPod/i', $ua)) {
        $device = 'mobile';
    } else {
        $device = 'desktop';
    }

    return [$browser, $os, $device];
}

/**
 * Charge la configuration (includes/config.php), ou null si l'appli n'est pas encore installée.
 */
function tracker_load_config(): ?array
{
    $path = __DIR__ . '/config.php';
    if (!file_exists($path)) {
        return null;
    }
    $config = include $path;
    return is_array($config) ? $config : null;
}