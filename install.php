<?php
declare(strict_types=1);
require __DIR__ . '/includes/helpers.php';

$existingConfig = tracker_load_config();
$errors = [];
$success = false;
$allowReconfigure = ($existingConfig === null);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Si une config existe déjà, il faut d'abord vérifier le mot de passe actuel
    if ($existingConfig !== null && !$allowReconfigure) {
        $current = $_POST['current_password'] ?? '';
        if (password_verify($current, $existingConfig['password_hash'])) {
            $allowReconfigure = true;
        } else {
            $errors[] = "Mot de passe actuel incorrect.";
        }
    }

    if ($allowReconfigure && isset($_POST['password'])) {
        $password = (string)($_POST['password'] ?? '');
        $confirm = (string)($_POST['password_confirm'] ?? '');
        $siteName = trim((string)($_POST['site_name'] ?? 'Mon Tracker'));
        $timezone = trim((string)($_POST['timezone'] ?? 'Europe/Paris'));

        if (strlen($password) < 8) {
            $errors[] = "Le mot de passe doit contenir au moins 8 caractères.";
        }
        if ($password !== $confirm) {
            $errors[] = "Les mots de passe ne correspondent pas.";
        }
        try {
            new DateTimeZone($timezone);
        } catch (Exception $e) {
            $errors[] = "Fuseau horaire invalide (ex: Europe/Paris).";
        }

        if (empty($errors)) {
            $config = [
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'site_name' => $siteName !== '' ? $siteName : 'Mon Tracker',
                'timezone' => $timezone,
                'track_ip' => isset($_POST['track_ip']),
                'anonymize_ip' => isset($_POST['anonymize_ip']),
                'track_user_agent' => isset($_POST['track_user_agent']),
                'track_referrer' => isset($_POST['track_referrer']),
                'track_utm' => isset($_POST['track_utm']),
                'exclude_bots' => isset($_POST['exclude_bots']),
                'trust_proxy_headers' => isset($_POST['trust_proxy_headers']),
                'allowed_origin' => trim((string)($_POST['allowed_origin'] ?? '*')) ?: '*',
                'session_lifetime' => 3600 * 24 * 365,
                'installed_at' => $existingConfig['installed_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $exportCode = "<?php\n"
                . "// Fichier généré automatiquement par install.php — ne pas éditer à la main.\n"
                . "return " . var_export($config, true) . ";\n";

            if (file_put_contents(__DIR__ . '/includes/config.php', $exportCode) === false) {
                $errors[] = "Impossible d'écrire includes/config.php. Vérifiez les permissions d'écriture.";
            } else {
                require __DIR__ . '/includes/db.php';
                tracker_db(); // initialise le fichier sqlite + le schéma
                $success = true;
                $existingConfig = $config;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Installation — Tracker</title>
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
</head>
<body class="install-page">
<button type="button" class="theme-toggle theme-toggle-fixed" id="theme-toggle" aria-label="Changer de thème"><span class="theme-toggle-icon" id="theme-toggle-icon">☀️</span><span id="theme-toggle-label">Thème clair</span></button>
<div class="card install-card">
  <h1 class="brand-title"><img src="assets/favicon.svg" alt="" class="brand-icon"> <span>Assistant de configuration</span></h1>

  <?php if ($success): ?>
    <div class="alert alert-success">
      <p>Configuration enregistrée avec succès !</p>
      <p><a class="btn" href="index.php">Accéder au panel</a></p>
    </div>
    <p class="muted">Pensez à supprimer ou restreindre l'accès à <code>install.php</code> maintenant que tout est configuré (voir le README).</p>
  <?php else: ?>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-error"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <?php if ($existingConfig !== null && !$allowReconfigure): ?>
      <p class="muted">Une configuration existe déjà pour ce site. Entrez le mot de passe actuel pour la modifier.</p>
      <form method="post">
        <label>Mot de passe actuel
          <input type="password" name="current_password" required autofocus>
        </label>
        <button class="btn" type="submit">Continuer</button>
      </form>
    <?php else: ?>
      <form method="post">
        <fieldset>
          <legend>Général</legend>
          <label>Nom du site / panel
            <input type="text" name="site_name" value="<?= htmlspecialchars($existingConfig['site_name'] ?? 'Mon Tracker') ?>">
          </label>
          <label>Nouveau mot de passe (8 caractères min.)
            <input type="password" name="password" required minlength="8">
          </label>
          <label>Confirmer le mot de passe
            <input type="password" name="password_confirm" required minlength="8">
          </label>
          <label>Fuseau horaire
            <input type="text" name="timezone" value="<?= htmlspecialchars($existingConfig['timezone'] ?? 'Europe/Paris') ?>" placeholder="Europe/Paris">
          </label>
        </fieldset>

        <fieldset>
          <legend>Options de tracking</legend>
          <label class="checkbox"><input type="checkbox" name="track_ip" <?= ($existingConfig === null || !empty($existingConfig['track_ip'])) ? 'checked' : '' ?>> Enregistrer l'adresse IP</label>
          <label class="checkbox"><input type="checkbox" name="anonymize_ip" <?= !empty($existingConfig['anonymize_ip']) ? 'checked' : '' ?>> Anonymiser l'IP (masque le dernier octet / les derniers groupes IPv6)</label>
          <label class="checkbox"><input type="checkbox" name="track_user_agent" <?= ($existingConfig === null || !empty($existingConfig['track_user_agent'])) ? 'checked' : '' ?>> Enregistrer le User-Agent (navigateur / OS / appareil)</label>
          <label class="checkbox"><input type="checkbox" name="track_referrer" <?= ($existingConfig === null || !empty($existingConfig['track_referrer'])) ? 'checked' : '' ?>> Enregistrer le referrer</label>
          <label class="checkbox"><input type="checkbox" name="track_utm" <?= ($existingConfig === null || !empty($existingConfig['track_utm'])) ? 'checked' : '' ?>> Enregistrer les paramètres UTM (source, medium, campaign, term, content)</label>
          <label class="checkbox"><input type="checkbox" name="exclude_bots" <?= ($existingConfig === null || !empty($existingConfig['exclude_bots'])) ? 'checked' : '' ?>> Exclure les bots connus (crawlers, curl, etc.)</label>
          <label class="checkbox"><input type="checkbox" name="trust_proxy_headers" <?= !empty($existingConfig['trust_proxy_headers']) ? 'checked' : '' ?>> Site derrière un proxy/CDN (utiliser l'en-tête X-Forwarded-For)</label>
          <label>Origine autorisée pour l'endpoint de tracking (CORS) — <code>*</code> = toutes
            <input type="text" name="allowed_origin" value="<?= htmlspecialchars($existingConfig['allowed_origin'] ?? '*') ?>">
          </label>
        </fieldset>

        <button class="btn" type="submit">Enregistrer la configuration</button>
      </form>
    <?php endif; ?>
  <?php endif; ?>
</div>
<script>
document.addEventListener('DOMContentLoaded', function(){
    var button=document.getElementById('theme-toggle');
    var icon=document.getElementById('theme-toggle-icon');
    var label=document.getElementById('theme-toggle-label');
    function update(){
        var light=document.documentElement.getAttribute('data-theme')==='light';
        icon.textContent=light?'🌙':'☀️';
        label.textContent=light?'Thème sombre':'Thème clair';
    }
    update();
    button.addEventListener('click', function(){
        var light=document.documentElement.getAttribute('data-theme')==='light';
        var theme=light?'dark':'light';
        document.documentElement.setAttribute('data-theme',theme);
        try{localStorage.setItem('nexttrack-theme',theme);}catch(e){}
        update();
    });
});
</script>
</body>
</html>