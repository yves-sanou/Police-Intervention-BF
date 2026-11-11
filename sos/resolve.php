<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . url('sos/'));
    exit;
}
check_csrf();

$role = strtolower(trim((string)(user()['role'] ?? '')));
if (!in_array($role, ['admin', 'superviseur'], true)) {
    http_response_code(403);
    exit('Accès refusé : seuls les administrateurs et superviseurs peuvent clôturer une alerte SOS.');
}

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
$action = (string)($_POST['action'] ?? '');
if (!$id || $id < 1 || $action !== 'resolve') {
    $_SESSION['sos_flash'] = 'Action SOS invalide.';
    header('Location: ' . url('sos/'));
    exit;
}

$pdo = db();
try {
    $pdo->beginTransaction();
    $q = $pdo->prepare("
        SELECT s.id, s.team_id AS origin_team_id, s.status,
               i.team_id AS assigned_team_id
        FROM sos_alerts s
        LEFT JOIN interventions i ON i.id = s.intervention_id
        WHERE s.id = ?
        FOR UPDATE
    ");
    $q->execute([$id]);
    $alert = $q->fetch(PDO::FETCH_ASSOC);
    if (!$alert) throw new RuntimeException('Alerte SOS introuvable.');

    if (in_array($alert['status'], ['active', 'prise_en_charge'], true)) {
        $pdo->prepare("
            UPDATE sos_alerts SET status = 'resolue', resolved_at = NOW()
            WHERE id = ?
        ")->execute([$id]);

        if (!empty($alert['assigned_team_id'])) {
            $teamId = (int)$alert['assigned_team_id'];
            $pdo->prepare("
                UPDATE teams
                SET statut = 'disponible'
                WHERE id = ?
                  AND statut = 'en_intervention'
                  AND NOT EXISTS (
                    SELECT 1
                    FROM interventions other_i
                    WHERE other_i.team_id = ?
                      AND other_i.id <> (
                          SELECT s.intervention_id
                          FROM sos_alerts s
                          WHERE s.id = ?
                      )
                      AND other_i.status NOT IN ('terminee', 'annulee')
                  )
            ")->execute([$teamId, $teamId, $id]);
            $pdo->prepare("
                UPDATE interventions SET status = 'terminee', updated_at = NOW()
                WHERE id = (SELECT intervention_id FROM sos_alerts WHERE id = ?)
            ")->execute([$id]);
        }

        $originTeamId = (int)$alert['origin_team_id'];
        $pdo->prepare("
            UPDATE teams SET statut = 'disponible'
            WHERE id = ? AND statut = 'sos'
              AND NOT EXISTS (
                SELECT 1 FROM sos_alerts
                WHERE team_id = ? AND status IN ('active','prise_en_charge')
              )
        ")->execute([$originTeamId, $originTeamId]);

        $_SESSION['sos_flash'] = 'Alerte SOS clôturée.';
    } else {
        $_SESSION['sos_flash'] = 'Cette alerte est déjà clôturée.';
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('SOS RESOLVE ERROR: ' . $e->getMessage());
    $_SESSION['sos_flash'] = 'Erreur pendant la clôture de l’alerte : ' . $e->getMessage();
}
header('Location: ' . url('sos/'));
exit;
