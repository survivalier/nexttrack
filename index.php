<?php
declare(strict_types=1);

require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';
require __DIR__ . '/includes/db.php';
require __DIR__ . '/includes/stats.php';

$config = tracker_load_config();

if ($config === null) {
    header('Location: install.php');
    exit;
}

date_default_timezone_set($config['timezone'] ?? 'UTC');

tracker_require_login();

if (isset($_GET['geo'])) {
    header('Content-Type: application/json; charset=utf-8');

    $ip = trim((string)$_GET['geo']);

    if (!filter_var($ip, FILTER_VALIDATE_IP)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Adresse IP invalide'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $context = stream_context_create([
        'http' => [
            'timeout' => 5,
            'ignore_errors' => true,
            'header' => "User-Agent: NextTrack/1.0\r\n"
        ]
    ]);

    $url = 'https://ipapi.co/' . rawurlencode($ip) . '/json/';
    $response = @file_get_contents($url, false, $context);

    if ($response === false) {
        echo json_encode([
            'success' => false,
            'error' => 'Service de géolocalisation indisponible'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $geo = json_decode($response, true);

    if (!is_array($geo) || empty($geo['country_name'])) {
        echo json_encode([
            'success' => false,
            'error' => 'Pays introuvable'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    echo json_encode([
        'success' => true,
        'country' => (string)($geo['country_name'] ?? 'Inconnu'),
        'country_code' => (string)($geo['country_code'] ?? ''),
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    exit;
}

$pdo = tracker_db();

$totals = tracker_stat_totals($pdo);
$daily = tracker_series_daily($pdo, 30);
$monthly = tracker_series_monthly($pdo, 12);
$yearly = tracker_series_yearly($pdo);

$topPages = tracker_top($pdo, 'path', 10);
$topSources = tracker_top($pdo, 'utm_source', 10);
$topCampaigns = tracker_top($pdo, 'utm_campaign', 10);
$topReferrers = tracker_top($pdo, 'referrer', 10);
$topBrowsers = tracker_top($pdo, 'browser', 8);
$topDevices = tracker_top($pdo, 'device_type', 5);
$connections = tracker_daily_connections($pdo, 100);

$siteName = htmlspecialchars(
    $config['site_name'] ?? 'NextTrack',
    ENT_QUOTES,
    'UTF-8'
);

function h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function svg_icon(string $name): string
{
    $icons = [
        'desktop' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8"/><path d="M12 17v4"/></svg>',
        'mobile' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="7" y="2" width="10" height="20" rx="2"/><path d="M10 5h4"/><path d="M10 19h4"/></svg>',
        'tablet' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 5h6"/><path d="M10 19h4"/></svg>',
        'sun' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.42 1.42M17.65 17.65l1.42 1.42M2 12h2M20 12h2M4.93 19.07l1.42-1.42M17.65 6.35l1.42-1.42"/></svg>',
        'moon' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.7 14.5a8.8 8.8 0 0 1-11.2-11.2A9.5 9.5 0 1 0 20.7 14.5Z"/></svg>',
        'close' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>',
        'arrow-right' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>',
        'globe' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3a14 14 0 0 1 0 18"/><path d="M12 3a14 14 0 0 0 0 18"/></svg>',
        'logout' => '<svg xmlns="http://www.w3.org/2000/svg" width="1em" height="1em" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 4H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h5"/><path d="M14 8l4 4-4 4"/><path d="M18 12H8"/></svg>',
    ];

    return $icons[$name] ?? '';
}

function device_icon(string $device): string
{
    return match (strtolower($device)) {
        'mobile' => svg_icon('mobile'),
        'tablet' => svg_icon('tablet'),
        default => svg_icon('desktop'),
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $siteName ?> — Panel</title>
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link rel="stylesheet" href="assets/style.php">
<script>
(function(){
    try {
        var theme = localStorage.getItem('nexttrack-theme') || 'dark';
        document.documentElement.setAttribute('data-theme', theme === 'light' ? 'light' : 'dark');
    } catch(e) {}
})();
</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
</head>

<body>

<header class="topbar">
    <h1 class="brand-title">
        <img src="assets/favicon.svg" alt="" class="brand-icon">
        <span><?= $siteName ?></span>
    </h1>

    <nav class="topbar-actions">
        <button type="button" class="theme-toggle" id="theme-toggle" aria-label="Changer de thème">
            <span class="theme-toggle-icon" id="theme-toggle-icon">
                <?= svg_icon('sun') ?>
            </span>
            <span id="theme-toggle-label">Thème clair</span>
        </button>

        <a href="logout.php" class="logout-link">
            <?= svg_icon('logout') ?>
            <span>Se déconnecter</span>
        </a>
    </nav>
</header>

<main class="container">

    <section class="stats-grid">
        <div class="stat-card">
            <span class="stat-value"><?= number_format((int)$totals['total']) ?></span>
            <span class="stat-label">Connexions enregistrées</span>
        </div>

        <div class="stat-card">
            <span class="stat-value"><?= number_format((int)$totals['unique_visitors']) ?></span>
            <span class="stat-label">Visiteurs</span>
        </div>

        <div class="stat-card">
            <span class="stat-value"><?= number_format((int)$totals['today']) ?></span>
            <span class="stat-label">Aujourd'hui</span>
        </div>

        <div class="stat-card">
            <span class="stat-value"><?= number_format((int)$totals['this_month']) ?></span>
            <span class="stat-label">Ce mois-ci</span>
        </div>

        <div class="stat-card">
            <span class="stat-value"><?= number_format((int)$totals['this_year']) ?></span>
            <span class="stat-label">Cette année</span>
        </div>
    </section>

    <section class="charts-grid">
        <div class="card chart-card">
            <div class="chart-header">
                <h2>Connexions</h2>

                <div class="chart-tabs">
                    <button type="button" data-range="daily" class="active">Jour</button>
                    <button type="button" data-range="monthly">Mois</button>
                    <button type="button" data-range="yearly">Année</button>
                </div>
            </div>

            <canvas id="chart-time" height="100"></canvas>
        </div>

        <div class="card chart-card">
            <h2>Appareils</h2>
            <canvas id="chart-devices" height="180"></canvas>
        </div>
    </section>

    <section class="card connections-card">
        <div class="section-header">
            <div>
                <h2>Connexions récentes</h2>
                <p>Une connexion maximum par jour et par IP.</p>
            </div>
        </div>

        <div class="connection-list">

            <?php foreach ($connections as $row): ?>

                <?php
                $details = [
                    'Date' => $row['created_at'] ?? '',
                    'IP' => $row['ip'] ?? 'Non disponible',
                    'Page' => $row['path'] ?? '',
                    'URL' => $row['full_url'] ?? '',
                    'Référent' => $row['referrer'] ?? 'Direct',
                    'Navigateur' => $row['browser'] ?? 'Inconnu',
                    'Système' => $row['os'] ?? 'Inconnu',
                    'Appareil' => $row['device_type'] ?? 'Inconnu',
                    'Langue' => $row['language'] ?? 'Inconnue',
                    'Résolution' => $row['screen_resolution'] ?? 'Inconnue',
                    'UTM Source' => $row['utm_source'] ?? '',
                    'UTM Medium' => $row['utm_medium'] ?? '',
                    'UTM Campagne' => $row['utm_campaign'] ?? '',
                    'UTM Terme' => $row['utm_term'] ?? '',
                    'UTM Contenu' => $row['utm_content'] ?? '',
                    'User-Agent' => $row['user_agent'] ?? ''
                ];
                ?>

                <button
                    type="button"
                    class="connection-item"
                    data-details="<?= h(json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>"
                >
                    <span class="connection-device">
                        <span class="device-icon">
                            <?= device_icon((string)($row['device_type'] ?? 'desktop')) ?>
                        </span>

                        <span class="device-main">
                            <strong><?= h($row['device_type'] ?? 'Appareil inconnu') ?></strong>

                            <small>
                                <?= h($row['os'] ?? 'OS inconnu') ?>
                                ·
                                <?= h($row['browser'] ?? 'Navigateur inconnu') ?>
                            </small>
                        </span>
                    </span>

                    <span class="connection-info">
                        <strong><?= h($row['ip'] ?? 'IP inconnue') ?></strong>
                        <small><?= h($row['path'] ?? '/') ?></small>
                    </span>

                    <span class="connection-date">
                        <?= h($row['created_at'] ?? '') ?>
                    </span>

                    <span class="connection-arrow">
                        <?= svg_icon('arrow-right') ?>
                    </span>
                </button>

            <?php endforeach; ?>

            <?php if (!$connections): ?>
                <div class="empty-state">
                    Aucune connexion enregistrée pour l'instant.
                </div>
            <?php endif; ?>

        </div>
    </section>

    <section class="tables-grid">

        <?php
        $tables = [
            ['Pages les plus visitées', 'Page', $topPages],
            ['Sources UTM', 'Source', $topSources],
            ['Campagnes UTM', 'Campagne', $topCampaigns],
            ['Referrers', 'Référent', $topReferrers],
            ['Navigateurs', 'Navigateur', $topBrowsers],
        ];
        ?>

        <?php foreach ($tables as [$heading, $label, $rows]): ?>
            <div class="card">
                <h2><?= h($heading) ?></h2>

                <table>
                    <thead>
                        <tr>
                            <th><?= h($label) ?></th>
                            <th>Connexions</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($rows as $row): ?>
                            <tr>
                                <td><?= h($row['label']) ?></td>
                                <td><?= (int)$row['c'] ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (!$rows): ?>
                            <tr>
                                <td colspan="2" class="muted">Aucune donnée</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>

    </section>

</main>

<div id="details-modal" class="details-modal" aria-hidden="true">
    <div class="details-backdrop" data-close-details></div>

    <div class="details-dialog" role="dialog" aria-modal="true" aria-labelledby="details-title">

        <div class="details-header">
            <div class="details-heading">
                <span id="details-icon" class="details-icon">
                    <?= svg_icon('desktop') ?>
                </span>

                <div>
                    <h2 id="details-title">Détails de la connexion</h2>
                    <span id="details-subtitle"></span>
                </div>
            </div>

            <button type="button" class="details-close" data-close-details aria-label="Fermer">
                <?= svg_icon('close') ?>
            </button>
        </div>

        <div id="details-country" class="details-country">
            <span class="country-icon"><?= svg_icon('globe') ?></span>
            <span>
                <strong>Pays</strong>
                <span id="country-value">Recherche en cours…</span>
            </span>
        </div>

        <div id="details-content" class="details-content"></div>

    </div>
</div>

<style>
.details-country {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 0 20px 14px;
    padding: 11px 13px;
    border: 1px solid rgba(127,127,127,.18);
    border-radius: 10px;
    background: rgba(127,127,127,.06);
}
.details-country .country-icon {
    width: 22px;
    height: 22px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex: 0 0 22px;
}
.details-country .country-icon svg {
    width: 20px;
    height: 20px;
    display: block;
}
.details-country strong {
    display: block;
    font-size: 12px;
    margin-bottom: 2px;
}
.details-country #country-value {
    display: block;
    font-size: 13px;
    opacity: .75;
}
.logout-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
}
.logout-link svg {
    width: 17px;
    height: 17px;
    display: block;
}
.details-close svg {
    width: 18px;
    height: 18px;
    display: block;
}
.connection-arrow svg {
    width: 18px;
    height: 18px;
    display: block;
}
.device-icon svg,
.theme-toggle-icon svg {
    width: 1em;
    height: 1em;
    display: block;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const themeToggle = document.getElementById('theme-toggle');
    const themeIcon = document.getElementById('theme-toggle-icon');
    const themeLabel = document.getElementById('theme-toggle-label');

    const sunIcon = <?= json_encode(svg_icon('sun'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const moonIcon = <?= json_encode(svg_icon('moon'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const desktopIcon = <?= json_encode(svg_icon('desktop'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const mobileIcon = <?= json_encode(svg_icon('mobile'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const tabletIcon = <?= json_encode(svg_icon('tablet'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
    const globeIcon = <?= json_encode(svg_icon('globe'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

    function updateThemeButton() {
        const light = document.documentElement.getAttribute('data-theme') === 'light';

        themeIcon.innerHTML = light ? moonIcon : sunIcon;
        themeLabel.textContent = light ? 'Thème sombre' : 'Thème clair';
    }

    updateThemeButton();

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            const light = document.documentElement.getAttribute('data-theme') === 'light';
            const nextTheme = light ? 'dark' : 'light';

            document.documentElement.setAttribute('data-theme', nextTheme);

            try {
                localStorage.setItem('nexttrack-theme', nextTheme);
            } catch (e) {}

            updateThemeButton();
        });
    }

    const seriesData = {
        daily: <?= json_encode([
            'labels' => array_keys($daily),
            'values' => array_values($daily)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,

        monthly: <?= json_encode([
            'labels' => array_keys($monthly),
            'values' => array_values($monthly)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,

        yearly: <?= json_encode([
            'labels' => array_keys($yearly),
            'values' => array_values($yearly)
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
    };

    const deviceData = <?= json_encode([
        'labels' => array_column($topDevices, 'label'),
        'values' => array_map('intval', array_column($topDevices, 'c'))
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

    if (typeof Chart !== 'undefined') {

        const ctxTime = document.getElementById('chart-time');

        if (ctxTime) {

            const timeChart = new Chart(ctxTime, {
                type: 'line',
                data: {
                    labels: seriesData.daily.labels,
                    datasets: [{
                        label: 'Connexions',
                        data: seriesData.daily.values,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.15)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0 }
                        }
                    }
                }
            });

            document.querySelectorAll('.chart-tabs button').forEach(function (btn) {
                btn.addEventListener('click', function () {

                    document.querySelectorAll('.chart-tabs button')
                        .forEach(function (button) {
                            button.classList.remove('active');
                        });

                    btn.classList.add('active');

                    const range = btn.dataset.range;

                    timeChart.data.labels = seriesData[range].labels;
                    timeChart.data.datasets[0].data = seriesData[range].values;
                    timeChart.update();
                });
            });
        }

        const ctxDevices = document.getElementById('chart-devices');

        if (ctxDevices) {
            new Chart(ctxDevices, {
                type: 'doughnut',
                data: {
                    labels: deviceData.labels.length
                        ? deviceData.labels
                        : ['Aucune donnée'],

                    datasets: [{
                        data: deviceData.values.length
                            ? deviceData.values
                            : [1],

                        backgroundColor: [
                            '#6366f1',
                            '#22c55e',
                            '#f59e0b',
                            '#ef4444',
                            '#06b6d4'
                        ]
                    }]
                },
                options: {
                    responsive: true
                }
            });
        }
    }

    const modal = document.getElementById('details-modal');
    const content = document.getElementById('details-content');
    const title = document.getElementById('details-title');
    const subtitle = document.getElementById('details-subtitle');
    const icon = document.getElementById('details-icon');
    const countryValue = document.getElementById('country-value');
    const countryIcon = document.querySelector('.country-icon');

    function loadCountry(ip) {
        countryIcon.innerHTML = globeIcon;
        countryValue.textContent = 'Recherche en cours…';

        if (!ip || ip === 'IP inconnue') {
            countryValue.textContent = 'Inconnu';
            return;
        }

        fetch('index.php?geo=' + encodeURIComponent(ip), {
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' }
        })
        .then(function(response) {
            if (!response.ok) {
                throw new Error('HTTP ' + response.status);
            }
            return response.json();
        })
        .then(function(data) {
            if (data && data.success && data.country) {
                countryValue.textContent = data.country;
            } else {
                countryValue.textContent = 'Pays introuvable';
            }
        })
        .catch(function() {
            countryValue.textContent = 'Indisponible';
        });
    }

    function openDetails(button) {

        let details;

        try {
            details = JSON.parse(button.dataset.details);
        } catch (error) {
            return;
        }

        title.textContent = details.Appareil || 'Détails de la connexion';

        subtitle.textContent =
            (details.IP || 'IP inconnue') +
            ' · ' +
            (details.Date || '');

        loadCountry(details.IP || '');

        const device = (details.Appareil || '').toLowerCase();

        if (device === 'mobile') {
            icon.innerHTML = mobileIcon;
        } else if (device === 'tablet') {
            icon.innerHTML = tabletIcon;
        } else {
            icon.innerHTML = desktopIcon;
        }

        content.innerHTML = '';

        Object.keys(details).forEach(function (label) {

            const value = details[label];

            if (value === null || value === undefined || value === '') {
                return;
            }

            const row = document.createElement('div');
            row.className = 'detail-row';

            const key = document.createElement('span');
            key.className = 'detail-label';
            key.textContent = label;

            const val = document.createElement('span');
            val.className = 'detail-value';
            val.textContent = value;

            row.appendChild(key);
            row.appendChild(val);

            content.appendChild(row);
        });

        modal.classList.add('open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeDetails() {
        modal.classList.remove('open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    document.querySelectorAll('.connection-item').forEach(function (button) {
        button.addEventListener('click', function () {
            openDetails(button);
        });
    });

    document.querySelectorAll('[data-close-details]').forEach(function (element) {
        element.addEventListener('click', closeDetails);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('open')) {
            closeDetails();
        }
    });

});
</script>

</body>
</html>
