<?php
$title = 'Interventions';
require __DIR__ . '/../includes/header.php';

$rows = db()->query("
    SELECT i.*, t.nom AS team, u.nom AS creator
    FROM interventions i
    LEFT JOIN teams t ON t.id = i.team_id
    LEFT JOIN users u ON u.id = i.created_by
    ORDER BY
        CASE i.priority
            WHEN 'tres_urgent' THEN 1
            WHEN 'urgent' THEN 2
            ELSE 3
        END,
        i.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="toolbar">
    <div>
        <h1>Interventions</h1>
        <p>Suivi des demandes et des interventions des équipes.</p>
    </div>
    <a class="btn" href="<?= e(url('interventions/create.php')) ?>">+ Nouvelle demande</a>
</div>

<div class="table-wrap">
<table class="table">
    <thead>
        <tr>
            <th>Référence</th>
            <th>Type</th>
            <th>Priorité</th>
            <th>Équipe affectée</th>
            <th>Position</th>
            <th>Statut</th>
            <th>Créée</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="8" style="text-align:center;padding:25px">Aucune intervention enregistrée.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <?php
            $isSos = str_starts_with((string)$r['reference'], 'SOS-')
                || (string)$r['type'] === 'Alerte SOS';
            $priorityClass = $r['priority'] === 'normal' ? '' : 'red';
            $lat = $r['latitude'] ?? null;
            $lon = $r['longitude'] ?? null;
            $hasGps = is_numeric($lat) && is_numeric($lon);
        ?>
        <tr>
            <td>
                <strong><?= e((string)$r['reference']) ?></strong>
                <?php if ($isSos): ?><br><span class="badge red">🆘 SOS automatique</span><?php endif; ?>
            </td>
            <td><?= e((string)$r['type']) ?></td>
            <td><span class="badge <?= e($priorityClass) ?>"><?= e((string)$r['priority']) ?></span></td>
            <td><?= e((string)($r['team'] ?? 'Non affectée')) ?></td>
            <td>
                <?php if ($hasGps): ?>
                    <a href="https://www.openstreetmap.org/?mlat=<?= rawurlencode((string)$lat) ?>&mlon=<?= rawurlencode((string)$lon) ?>#map=16/<?= rawurlencode((string)$lat) ?>/<?= rawurlencode((string)$lon) ?>"
                       target="_blank" rel="noopener noreferrer">Voir sur la carte</a>
                <?php else: ?>-<?php endif; ?>
            </td>
            <td><?= e((string)$r['status']) ?></td>
            <td><?= e((string)$r['created_at']) ?></td>
            <td><a class="btn secondary" href="<?= e(url('interventions/edit.php?id=' . (int)$r['id'])) ?>">Détails / Modifier</a></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
