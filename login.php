<?php
declare(strict_types=1);
require __DIR__ . '/includes/helpers.php';
require __DIR__ . '/includes/auth.php';

$config = tracker_load_config();
if ($config === null) {
    header('Location: install.php');
    exit;
}

if (tracker_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    if (tracker_login($password, $config)) {
        header('Location: index.php');
        exit;
    }
    $error = "Mot de passe incorrect.";
}

$siteName = htmlspecialchars($config['site_name'] ?? 'Tracker');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connexion — <?= $siteName ?></title>
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
  <h1 class="brand-title"><img src="assets/favicon.svg" alt="" class="brand-icon"> <span><?= $siteName ?></span></h1>
  <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <label>Mot de passe
      <input type="password" name="password" required autofocus>
    </label>
    <button class="btn" type="submit">Se connecter</button>
  </form>
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