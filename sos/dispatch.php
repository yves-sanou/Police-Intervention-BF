<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('sos/'));
    exit;
}
check_csrf();

$pdo = db();
$currentUser = user();
$userId = (int)($currentUser['id'] ?? 0);
$role = strtolower(trim((string)($currentUser['role'] ?? '')));
$isManager = in_array($role, ['admin', 'superviseur'], true);

$sosId = filter_var($_POST['sos_id'] ?? null, FILTER_VALIDATE_INT);
$teamId = filter_var($_POST['team_id'] ?? null, FILTER_VALIDATE_INT);
$mode = (string)($_POST['mode'] ?? 'claim');

if (!$sosId || $sosId < 1 || !$teamId || $teamId < 1 || !in_array($mode, ['claim', 'assign'], true)) {
    $_SESSION['sos_flash'] = 'Demande d’affectation invalide.';
    header('Location: ' . url('sos/'));
    exit;
}
if ($mode === 'assign' && !$isManager) {
    http_response_code(403);
    exit('Seuls les administrateurs et superviseurs peuvent affecter manuellement une équipe.');
}

try {
    $pdo->beginTransaction();

    // Verrouille le SOS : deux équipes ne peuvent pas gagner simultanément.
    $q = $pdo->prepare("
        SELECT s.id, s.team_id AS origin_team_id, s.status, s.intervention_id,
               i.id AS intervention_id_real, i.team_id AS assigned_team_id, i.status AS intervention_status
        FROM sos_alerts s
        INNER JOIN interventions i ON i.id = s.intervention_id
        WHERE s.id = ?
        FOR UPDATE
    ");
    $q->execute([$sosId]);
    $alert = $q->fetch(PDO::FETCH_ASSOC);

    if (!$alert) {
        throw new RuntimeException('Alerte SOS ou intervention associée introuvable. Vérifiez la migration SQL.');
    }
    if (!in_array($alert['status'], ['active', 'prise_en_charge'], true)) {
        throw new RuntimeException('Cette alerte SOS est déjà clôturée.');
    }
    if (!empty($alert['assigned_team_id'])) {
        throw new RuntimeException('Cette intervention a déjà été affectée à une équipe.');
    }

    // L'équipe de secours doit être disponible et différente de l'équipe émettrice.
    $teamQ = $pdo->prepare("SELECT id, nom, statut FROM teams WHERE id = ? FOR UPDATE");
    $teamQ->execute([$teamId]);
    $team = $teamQ->fetch(PDO::FETCH_ASSOC);
    if (!$team) {
        throw new RuntimeException('Équipe sélectionnée introuvable.');
    }
    if ((int)$teamId === (int)$alert['origin_team_id']) {
        throw new RuntimeException('L’équipe qui a déclenché le SOS ne peut pas être son équipe de renfort.');
    }
    if ((string)$team['statut'] !== 'disponible') {
        throw new RuntimeException('Cette équipe n’est pas disponible. Choisissez une équipe libre.');
    }

    if ($mode === 'claim') {
        $membership = $pdo->prepare("
            SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1
        ");
        $membership->execute([$teamId, $userId]);
        if (!$membership->fetchColumn()) {
            throw new RuntimeException('Vous ne pouvez accepter l’alerte qu’au nom d’une équipe dont vous êtes membre.');
        }
    }

    // Affectation atomique : l'intervention ne peut être attribuée qu'une seule fois.
    $update = $pdo->prepare("
        UPDATE interventions
        SET team_id = ?, status = 'affectee', updated_at = NOW()
        WHERE id = ? AND team_id IS NULL AND status = 'nouvelle'
    ");
    $update->execute([$teamId, (int)$alert['intervention_id_real']]);
    if ($update->rowCount() !== 1) {
        throw new RuntimeException('Une autre équipe vient probablement d’accepter cette intervention. Actualisez la page.');
    }

    $pdo->prepare("
        UPDATE sos_alerts
        SET status = 'prise_en_charge', acknowledged_at = COALESCE(acknowledged_at, NOW())
        WHERE id = ? AND status IN ('active', 'prise_en_charge')
    ")->execute([$sosId]);

    $pdo->prepare("UPDATE teams SET statut = 'en_intervention' WHERE id = ?")->execute([$teamId]);

    // Notifier les membres de l'équipe retenue.
    $notify = $pdo->prepare("
        INSERT INTO notifications
            (user_id, intervention_id, sos_id, type, title, message, is_read, created_at)
        SELECT tm.user_id, ?, ?, 'urgent', 'Intervention SOS acceptée',
               ?, 0, NOW()
        FROM team_members tm
        INNER JOIN users u ON u.id = tm.user_id
        WHERE tm.team_id = ? AND u.actif = 1
    ");
    $notify->execute([
        (int)$alert['intervention_id_real'],
        $sosId,
        'Votre équipe ' . (string)$team['nom'] . ' est affectée à l’intervention SOS. Consultez les coordonnées GPS et les informations dans Interventions.',
        $teamId
    ]);

    $pdo->commit();
    $_SESSION['sos_flash'] = 'Équipe ' . (string)$team['nom'] . ' affectée avec succès à l’intervention SOS.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SOS DISPATCH ERROR: ' . $e->getMessage());
    $_SESSION['sos_flash'] = $e->getMessage();
}

header('Location: ' . url('sos/'));
exit;
