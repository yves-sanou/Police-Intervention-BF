<?php
require_once __DIR__ . '/../config/config.php';
require_login();
?>

<!doctype html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= e($title ?? 'Police Intervention BF V4') ?></title>

    <link
        rel="stylesheet"
        href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    >

    <link
        rel="stylesheet"
        href="<?= e(asset('css/app.css')) ?>"
    >
<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
>
    <style>
        .sos-menu-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            width: calc(100% - 20px);
            margin: 12px 10px;
            padding: 14px 10px;
            background: #c62828;
            color: #fff !important;
            border: 2px solid #ff5252;
            border-radius: 10px;
            text-align: center;
            text-decoration: none;
            font-size: 14px;
            font-weight: 900;
            box-shadow: 0 5px 15px rgba(198, 40, 40, .35);
            transition:
                background .2s ease,
                transform .2s ease,
                box-shadow .2s ease;
            animation: sosPulse 2s infinite;
        }

        .sos-menu-btn:hover {
            background: #a71919;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(198, 40, 40, .55);
        }

        .sos-menu-btn:active {
            transform: scale(.97);
            background: #8e1111;
        }

        @keyframes sosPulse {
            0% {
                box-shadow: 0 5px 15px rgba(198, 40, 40, .35);
            }

            50% {
                box-shadow: 0 5px 25px rgba(198, 40, 40, .75);
            }

            100% {
                box-shadow: 0 5px 15px rgba(198, 40, 40, .35);
            }
        }

        .sos-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 22px;
            height: 22px;
            padding: 0 6px;
            margin-left: auto;
            background: #ff1744;
            color: #fff;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 900;
            animation: badgePulse 1.5s infinite;
        }

        @keyframes badgePulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.15);
            }

            100% {
                transform: scale(1);
            }
        }

        .sos-alert-link {
            display: flex !important;
            align-items: center;
            gap: 8px;
        }

        .notification-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            margin-left: 5px;
            background: #dc3545;
            color: #fff;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 800;
        }
    </style>
<script>window.APP_BASE_URL = <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>;</script>
</head>

<body>

<header class="topbar">

    <div class="brand">
        🇧🇫 Police BF
        <span>Intervention V5</span>
    </div>

    <div class="top-actions">

        <span>
            👤 <?= e(user()['nom'] ?? '') ?>
        </span>

        <a href="<?= e(url('logout.php')) ?>">
            Déconnexion
        </a>

    </div>

</header>

<div class="layout">

    <aside class="sidebar">

        <a href="<?= e(url('dashboard.php')) ?>">
            📊 Dashboard
        </a>

        <a href="<?= e(url('interventions/')) ?>">
            🚨 Interventions
        </a>

        <a
            href="<?= e(url('sos/')) ?>"
            class="sos-alert-link"
        >
            <span>🆘 Alertes SOS</span>

            <span
                id="sosBadge"
                class="sos-badge"
                style="display: none;"
            >
                0
            </span>
        </a>

        <a
            href="<?= e(url('sos/agent.php')) ?>"
            class="sos-menu-btn"
        >
            🆘 DÉCLENCHER UN SOS
        </a>

        <a href="<?= e(url('admin/teams.php')) ?>">
            👮 Équipes
        </a>

        <a href="<?= e(url('admin/team_members.php')) ?>">
            👥 Membres
        </a>

        <?php if (user()['role'] === 'admin'): ?>

            <a href="<?= e(url('admin/users.php')) ?>">
                👤 Utilisateurs
            </a>

        <?php endif; ?>

        <a href="<?= e(url('admin/locations.php')) ?>">
            📍 Positions
        </a>

    </aside>

    <main class="content">

<script>
(function () {

    const badge = document.getElementById('sosBadge');

    if (!badge) {
        return;
    }

    async function updateSosBadge() {

        try {

            const response = await fetch(
                (window.APP_BASE_URL || '') + '/api/sos_active.php?t=' + Date.now(),
                {
                    cache: 'no-store'
                }
            );

            if (!response.ok) {
                return;
            }

            const data = await response.json();

            if (!data.success) {
                return;
            }

            const alerts = data.alerts || [];

            const active = alerts.filter(
                alert => alert.status === 'active'
            ).length;

            if (active > 0) {

                badge.textContent = active;
                badge.style.display = 'inline-flex';

            } else {

                badge.style.display = 'none';

            }

        } catch (error) {

            console.error(
                'Erreur compteur SOS:',
                error
            );

        }
    }

    updateSosBadge();

    setInterval(
        updateSosBadge,
        3000
    );

})();
</script>

