<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_login();

$pdo = db();
$title = 'Alertes SOS';
$currentUser = user();
$userId = (int)($currentUser['id'] ?? 0);
$role = strtolower(trim((string)($currentUser['role'] ?? '')));
$canManage = in_array($role, ['admin', 'superviseur'], true);

$flash = $_SESSION['sos_flash'] ?? '';
unset($_SESSION['sos_flash']);

$rows = [];
$loadError = null;
$availableTeams = [];
$myTeamsByOrigin = [];

try {
    $rows = $pdo->query("
        SELECT
            s.id, s.team_id AS origin_team_id, s.user_id, s.latitude, s.longitude,
            s.accuracy, s.message, s.status, s.created_at,
            s.acknowledged_at, s.resolved_at, s.intervention_id,
            origin.nom AS origin_team,
            u.nom AS agent,
            i.reference, i.team_id AS assigned_team_id, i.status AS intervention_status,
            assigned.nom AS assigned_team
        FROM sos_alerts s
        LEFT JOIN teams origin ON origin.id = s.team_id
        LEFT JOIN users u ON u.id = s.user_id
        LEFT JOIN interventions i ON i.id = s.intervention_id
        LEFT JOIN teams assigned ON assigned.id = i.team_id
        ORDER BY
            CASE s.status WHEN 'active' THEN 1 WHEN 'prise_en_charge' THEN 2 ELSE 3 END,
            s.created_at DESC
        LIMIT 200
    ")->fetchAll(PDO::FETCH_ASSOC);

    $availableTeams = $pdo->query("
        SELECT id, nom, secteur
        FROM teams
        WHERE statut = 'disponible'
        ORDER BY nom
    ")->fetchAll(PDO::FETCH_ASSOC);

    $q = $pdo->prepare("
        SELECT tm.team_id, t.nom, t.secteur
        FROM team_members tm
        INNER JOIN teams t ON t.id = tm.team_id
        WHERE tm.user_id = ? AND t.statut = 'disponible'
        ORDER BY t.nom
    ");
    $q->execute([$userId]);
    $myTeams = $q->fetchAll(PDO::FETCH_ASSOC);
    foreach ($myTeams as $myTeam) {
        $myTeamsByOrigin[(int)$myTeam['team_id']] = $myTeam;
    }
} catch (Throwable $e) {
    error_log('SOS INDEX ERROR: ' . $e->getMessage());
    $loadError = "Impossible de charger les alertes SOS. Vérifiez la migration SQL et la base de données.";
}

$activeCount = count(array_filter($rows, static fn(array $r): bool =>
    in_array((string)$r['status'], ['active', 'prise_en_charge'], true)
));

require __DIR__ . '/../includes/header.php';
?>
<div class="toolbar">
    <div>
        <h1>🆘 Alertes SOS</h1>
        <p>Les alertes sont diffusées à toutes les équipes. La première équipe disponible qui accepte est affectée.</p>
    </div>
    <span id="sosCounter" class="badge <?= $activeCount ? 'red' : 'green' ?>"><?= $activeCount ?> SOS actif(s)</span>
</div>

<?php if ($flash !== ''): ?><div class="alert"><?= e((string)$flash) ?></div><?php endif; ?>
<?php if ($loadError !== null): ?><div class="alert"><?= e($loadError) ?></div><?php endif; ?>

<div class="sos-summary">
    <div class="sos-summary-item"><span class="sos-summary-icon">🚨</span><div><strong id="activeCount"><?= $activeCount ?></strong><span>Alertes en cours</span></div></div>
    <div class="sos-summary-item"><span class="sos-summary-icon">👮</span><div><strong><?= count($availableTeams) ?></strong><span>Équipes disponibles</span></div></div>
</div>

<div class="table-wrap">
<table class="table sos-table">
<thead><tr>
    <th>Référence</th><th>Équipe émettrice</th><th>Agent</th><th>Coordonnées GPS</th>
    <th>Précision</th><th>Message</th><th>Équipe affectée</th><th>Statut</th><th>Actions</th>
</tr></thead>
<tbody>
<?php if (!$rows): ?><tr><td colspan="9" class="empty-state">Aucune alerte SOS enregistrée.</td></tr><?php endif; ?>
<?php foreach ($rows as $r): ?>
<?php
    $status = (string)$r['status'];
    $isOpen = in_array($status, ['active', 'prise_en_charge'], true);
    $hasGps = is_numeric($r['latitude']) && is_numeric($r['longitude']);
    $unassigned = empty($r['assigned_team_id']);
    $canClaimTeams = [];
    foreach ($myTeamsByOrigin as $team) {
        if ((int)$team['team_id'] !== (int)$r['origin_team_id']) {
            $canClaimTeams[] = $team;
        }
    }
?>
<tr>
    <td><strong><?= e((string)($r['reference'] ?? ('SOS-' . $r['id']))) ?></strong></td>
    <td><?= e((string)($r['origin_team'] ?? 'Équipe inconnue')) ?></td>
    <td><?= e((string)($r['agent'] ?? '-')) ?></td>
    <td>
        <?php if ($hasGps): ?>
            <a target="_blank" rel="noopener noreferrer"
               href="https://www.openstreetmap.org/?mlat=<?= rawurlencode((string)$r['latitude']) ?>&mlon=<?= rawurlencode((string)$r['longitude']) ?>#map=17/<?= rawurlencode((string)$r['latitude']) ?>/<?= rawurlencode((string)$r['longitude']) ?>">
                <?= e((string)$r['latitude']) ?>, <?= e((string)$r['longitude']) ?>
            </a>
        <?php else: ?>Indisponible<?php endif; ?>
    </td>
    <td><?= is_numeric($r['accuracy']) ? e((string)$r['accuracy']) . ' m' : '-' ?></td>
    <td><?= e((string)($r['message'] ?? '-')) ?></td>
    <td><?= e((string)($r['assigned_team'] ?? 'En attente d’affectation')) ?></td>
    <td>
        <span class="badge <?= $status === 'active' ? 'red' : ($status === 'prise_en_charge' ? 'orange' : 'green') ?>">
            <?= $status === 'active' ? 'En attente' : ($status === 'prise_en_charge' ? 'Prise en charge' : 'Résolue') ?>
        </span>
    </td>
    <td class="sos-actions">
        <?php if ($isOpen && $unassigned && !$canManage && $canClaimTeams): ?>
            <?php foreach ($canClaimTeams as $team): ?>
                <form method="post" action="<?= e(url('sos/dispatch.php')) ?>" class="sos-action-form"
                      onsubmit="return confirm('Votre équipe <?= e((string)$team['nom']) ?> accepte-t-elle cette intervention ?');">
                    <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                    <input type="hidden" name="sos_id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="team_id" value="<?= (int)$team['team_id'] ?>">
                    <input type="hidden" name="mode" value="claim">
                    <button class="btn" type="submit">✓ Accepter avec <?= e((string)$team['nom']) ?></button>
                </form>
            <?php endforeach; ?>
        <?php elseif ($isOpen && $unassigned && $canManage): ?>
            <form method="post" action="<?= e(url('sos/dispatch.php')) ?>" class="sos-action-form"
                  onsubmit="return confirm('Confirmer l’affectation de cette équipe ?');">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="sos_id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="mode" value="assign">
                <select name="team_id" required>
                    <option value="">Choisir une équipe disponible</option>
                    <?php foreach ($availableTeams as $team): ?>
                        <?php if ((int)$team['id'] !== (int)$r['origin_team_id']): ?>
                            <option value="<?= (int)$team['id'] ?>"><?= e((string)$team['nom']) ?> — <?= e((string)($team['secteur'] ?? '')) ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <button class="btn" type="submit">Affecter</button>
            </form>
        <?php elseif ($isOpen && $unassigned): ?>
            <span class="action-note">En attente d’une équipe disponible</span>
        <?php elseif (!empty($r['assigned_team'])): ?>
            <span class="action-note">Affectée à <?= e((string)$r['assigned_team']) ?></span>
        <?php else: ?>
            <span class="action-note">Aucune action disponible</span>
        <?php endif; ?>

        <?php if ($canManage && $isOpen): ?>
            <form method="post" action="<?= e(url('sos/resolve.php')) ?>" class="sos-action-form"
                  onsubmit="return confirm('Clôturer cette alerte SOS ?');">
                <input type="hidden" name="csrf" value="<?= e(csrf()) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <input type="hidden" name="action" value="resolve">
                <button class="btn secondary" type="submit">Clôturer</button>
            </form>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
</tbody></table></div>

<style>
.sos-summary{display:flex;flex-wrap:wrap;gap:16px;margin:20px 0}
.sos-summary-item{display:flex;align-items:center;gap:14px;padding:18px 22px;border:1px solid #e2e8f0;border-radius:12px;background:var(--card,#fff);min-width:220px}
.sos-summary-icon{font-size:30px}
.sos-summary-item strong,.sos-summary-item span:last-child{display:block}
.sos-summary-item strong{font-size:24px}
.sos-summary-item span:last-child{color:#64748b;font-size:13px}
.sos-actions{min-width:245px;max-width:330px}
.sos-action-form{display:flex;flex-wrap:wrap;gap:6px;margin:4px 0}
.sos-action-form select{max-width:190px;min-width:150px}
.action-note{font-size:12px;color:#64748b}
.empty-state{text-align:center;padding:28px!important;color:#64748b}
.sos-table td{vertical-align:middle}
@media(max-width:900px){.table-wrap{overflow-x:auto}.sos-table{min-width:1450px}}
</style>

<script>
(() => {
    const base = (window.APP_BASE_URL || '').replace(/\/$/, '');
    const counter = document.getElementById('sosCounter');
    const activeCount = document.getElementById('activeCount');

    async function refreshSosCounter() {
        try {
            const response = await fetch(base + '/api/sos_active.php?t=' + Date.now(), {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {'Accept': 'application/json'}
            });
            if (!response.ok) throw new Error('HTTP ' + response.status);
            const data = await response.json();
            if (!data.success) throw new Error(data.message || 'API SOS invalide');
            const alerts = Array.isArray(data.alerts) ? data.alerts : [];
            const count = alerts.filter(a => ['active', 'prise_en_charge'].includes(a.status)).length;
            if (counter) {
                counter.textContent = count + ' SOS actif(s)';
                counter.className = 'badge ' + (count ? 'red' : 'green');
            }
            if (activeCount) activeCount.textContent = String(count);
        } catch (error) {
            console.error('Actualisation SOS :', error);
        }
    }
    refreshSosCounter();
    window.setInterval(refreshSosCounter, 5000);
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
