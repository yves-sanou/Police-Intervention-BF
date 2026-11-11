<?php
require_once __DIR__ . '/config/config.php';
$title = 'Police Intervention BF V4';
?>
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Police Intervention BF V4 — plateforme de coordination, intervention et suivi GPS des équipes.">
<title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></title>
<link rel="manifest" href="<?= e(url('manifest.json')) ?>">
<meta name="theme-color" content="#123c2d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="apple-touch-icon" href="<?= e(asset('icons/icon-192.png')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
</head>
<body>

<div id="loader" class="loader">
    <div class="radar">
        <div class="radar-ring ring-1"></div>
        <div class="radar-ring ring-2"></div>
        <div class="radar-ring ring-3"></div>
        <div class="radar-sweep"></div>
        <div class="radar-dot dot-1"></div>
        <div class="radar-dot dot-2"></div>
        <div class="radar-dot dot-3"></div>
        <div class="radar-center">🇧🇫</div>
    </div>
    <div class="loader-title">POLICE BF</div>
    <div class="loader-text">Initialisation du système...</div>
    <div class="loader-progress"><span></span></div>
</div>

<header class="navbar">
    <a href="#" class="logo">
        <span class="logo-mark">🇧🇫</span>
        <span><strong>Police BF</strong><small>Intervention V4</small></span>
    </a>
    <nav class="nav-links">
        <a href="#fonctionnalites">Fonctionnalités</a>
        <a href="#securite">Sécurité</a>
        <a href="#apropos">À propos</a>
    </nav>
    <a class="nav-login" href="<?= e(url('login.php')) ?>">Connexion</a>
</header>

<main>
<section class="hero">
    <div class="hero-grid"></div>
    <div class="hero-glow glow-one"></div>
    <div class="hero-glow glow-two"></div>

    <div class="hero-content">
        <div class="eyebrow"><span class="status-dot"></span>Système opérationnel</div>
        <h1>Coordonnez les <span>interventions</span> en temps réel.</h1>
        <p class="hero-text">
            Une plateforme centralisée pour suivre les équipes, gérer les interventions,
            transmettre les positions GPS et réagir rapidement aux situations d'urgence.
        </p>
        <div class="hero-actions">
            <a class="btn btn-primary" href="<?= e(url('login.php')) ?>">Accéder à la plateforme <span>→</span></a>
            <a class="btn btn-ghost" href="#fonctionnalites">Découvrir le système</a>
        </div>
        <div class="hero-stats">
            <div><strong>24/7</strong><span>Surveillance</span></div>
            <div><strong>GPS</strong><span>Position en temps réel</span></div>
            <div><strong>SOS</strong><span>Alerte prioritaire</span></div>
        </div>
    </div>

    <div class="hero-radar">
        <div class="radar-panel">
            <div class="panel-top"><span>LIVE MONITORING</span><i></i></div>
            <div class="large-radar">
                <div class="radar-circle c1"></div><div class="radar-circle c2"></div><div class="radar-circle c3"></div>
                <div class="radar-line"></div>
                <div class="map-cross horizontal"></div><div class="map-cross vertical"></div>
                <div class="map-point p1"></div><div class="map-point p2"></div><div class="map-point p3"></div><div class="map-point p4"></div>
                <div class="radar-origin"></div>
            </div>
            <div class="panel-footer"><span><b class="green"></b> 8 équipes actives</span><span>GPS synchronisé</span></div>
        </div>
    </div>
</section>

<section id="fonctionnalites" class="section">
    <div class="section-heading">
        <span>CAPACITÉS OPÉRATIONNELLES</span>
        <h2>Tout le terrain depuis une seule plateforme.</h2>
        <p>Des outils conçus pour faciliter la coordination et accélérer la prise de décision.</p>
    </div>
    <div class="feature-grid">
        <article class="feature-card"><div class="feature-icon">🗺️</div><h3>Suivi GPS</h3><p>Visualisez les dernières positions transmises par les équipes sur une carte interactive.</p></article>
        <article class="feature-card"><div class="feature-icon danger-icon">🆘</div><h3>Alertes SOS</h3><p>Une équipe en danger peut transmettre immédiatement son alerte et sa position GPS.</p></article>
        <article class="feature-card"><div class="feature-icon">🚨</div><h3>Interventions</h3><p>Centralisez les demandes, affectez les équipes et suivez l'état des interventions.</p></article>
        <article class="feature-card"><div class="feature-icon">👮</div><h3>Gestion des équipes</h3><p>Organisez les équipes, leurs membres et leurs secteurs d'intervention.</p></article>
        <article class="feature-card"><div class="feature-icon">🔔</div><h3>Notifications</h3><p>Recevez les événements importants afin de maintenir une vision opérationnelle actualisée.</p></article>
        <article class="feature-card"><div class="feature-icon">📊</div><h3>Tableau de bord</h3><p>Retrouvez les indicateurs essentiels et l'activité du système depuis une interface unique.</p></article>
    </div>
</section>

<section id="securite" class="security-section">
    <div class="security-card">
        <div>
            <span class="section-label">RÉACTION RAPIDE</span>
            <h2>Une alerte. Une position. Une réponse.</h2>
            <p>Le système rapproche les informations essentielles des équipes opérationnelles pour faciliter la coordination sur le terrain.</p>
        </div>
        <div class="security-flow">
            <div><span>01</span><b>Détection</b><small>Événement ou SOS</small></div>
            <div class="flow-arrow">→</div>
            <div><span>02</span><b>Transmission</b><small>Position GPS</small></div>
            <div class="flow-arrow">→</div>
            <div><span>03</span><b>Coordination</b><small>Équipe disponible</small></div>
        </div>
    </div>
</section>

<section id="apropos" class="cta-section">
    <div>
        <span class="section-label">POLICE INTERVENTION BF V4</span>
        <h2>Prêt à accéder au système ?</h2>
        <p>Connectez-vous pour accéder à votre espace opérationnel.</p>
    </div>
    <a class="btn btn-primary" href="<?= e(url('login.php')) ?>">Se connecter →</a>
</section>
</main>

<footer class="footer">
    <div>🇧🇫 <strong>Police BF</strong> — Intervention V4</div>
    <div>Plateforme de coordination opérationnelle</div>
</footer>

<script src="<?= e(asset('js/landing.js')) ?>"></script>
<script>
if ("serviceWorker" in navigator) {
    window.addEventListener("load", function () {
        navigator.serviceWorker.register(
            "<?= e(url('sw.js')) ?>",
            {scope: <?= json_encode(url(), JSON_UNESCAPED_SLASHES) ?>}
        ).catch(console.error);
    });
}
</script>
</body>
</html>
