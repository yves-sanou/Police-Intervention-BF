<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_login();

$pdo = null;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_response(['success' => false, 'message' => 'Méthode non autorisée.'], 405);
    }

    check_csrf();
    $pdo = db();

    $userId = (int)(user()['id'] ?? 0);
    $originTeamId = (int)($_POST['team_id'] ?? 0);
    $latitude = filter_var($_POST['latitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $longitude = filter_var($_POST['longitude'] ?? null, FILTER_VALIDATE_FLOAT);
    $accuracy = filter_var($_POST['accuracy'] ?? null, FILTER_VALIDATE_FLOAT);
    $message = trim((string)($_POST['message'] ?? 'Équipe en danger - SOS déclenché'));

    if ($originTeamId < 1) {
        json_response(['success' => false, 'message' => 'Équipe émettrice invalide.'], 422);
    }
    if ($latitude === false || $longitude === false
        || $latitude < -90 || $latitude > 90
        || $longitude < -180 || $longitude > 180) {
        json_response(['success' => false, 'message' => 'Coordonnées GPS invalides.'], 422);
    }

    $member = $pdo->prepare("
        SELECT 1 FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1
    ");
    $member->execute([$originTeamId, $userId]);
    if (!$member->fetchColumn()) {
        json_response(['success' => false, 'message' => 'Vous ne faites pas partie de cette équipe.'], 403);
    }

    $teamQuery = $pdo->prepare("SELECT id, nom FROM teams WHERE id = ? LIMIT 1");
    $teamQuery->execute([$originTeamId]);
    $originTeam = $teamQuery->fetch(PDO::FETCH_ASSOC);
    if (!$originTeam) {
        json_response(['success' => false, 'message' => 'Équipe émettrice introuvable.'], 404);
    }

    $pdo->beginTransaction();

    // Enregistrer le SOS de l'équipe qui signale la situation.
    $insertSos = $pdo->prepare("
        INSERT INTO sos_alerts
            (team_id, user_id, latitude, longitude, accuracy, message, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $insertSos->execute([
        $originTeamId, $userId, $latitude, $longitude,
        $accuracy !== false ? $accuracy : null, $message
    ]);
    $sosId = (int)$pdo->lastInsertId();

    // Intervention créée sans équipe affectée : les équipes peuvent se proposer.
    $reference = 'SOS-' . date('YmdHis') . '-' . random_int(100, 999);
    $description = "ALERTE SOS AUTOMATIQUE\n"
        . "Équipe émettrice : " . (string)$originTeam['nom'] . "\n"
        . "Agent : " . (string)(user()['nom'] ?? 'Agent') . "\n"
        . "Message : " . $message . "\n"
        . "Coordonnées GPS : "
        . number_format((float)$latitude, 7, '.', '') . ', '
        . number_format((float)$longitude, 7, '.', '') . "\n"
        . "Précision GPS : " . ($accuracy !== false ? number_format((float)$accuracy, 2, '.', '') . ' m' : 'non communiquée');

    $insertIntervention = $pdo->prepare("
        INSERT INTO interventions
            (reference, type, description, priority, latitude, longitude,
             address, team_id, created_by, status, created_at, updated_at)
        VALUES
            (?, 'Alerte SOS', ?, 'tres_urgent', ?, ?, ?, NULL, ?, 'nouvelle', NOW(), NOW())
    ");
    $insertIntervention->execute([
        $reference, $description, $latitude, $longitude,
        'Position GPS SOS - ' . (string)$originTeam['nom'], $userId
    ]);
    $interventionId = (int)$pdo->lastInsertId();

    // Relier explicitement le SOS à son intervention.
    $link = $pdo->prepare("UPDATE sos_alerts SET intervention_id = ? WHERE id = ?");
    $link->execute([$interventionId, $sosId]);

    // L'équipe qui signale le danger passe en SOS.
    $pdo->prepare("UPDATE teams SET statut = 'sos' WHERE id = ?")->execute([$originTeamId]);

    // Diffuser à TOUS les membres des équipes + administrateurs + superviseurs.
    // DISTINCT évite les doublons si un superviseur est aussi membre d'une équipe.
    $notificationMessage = 'SOS de ' . (string)$originTeam['nom']
        . ' — Intervention ' . $reference
        . ' à accepter. GPS : '
        . number_format((float)$latitude, 6, '.', '') . ', '
        . number_format((float)$longitude, 6, '.', '')
        . '. Précision : ' . ($accuracy !== false ? round((float)$accuracy) . ' m' : 'indisponible')
        . '. Message : ' . $message;

    $broadcast = $pdo->prepare("
        INSERT INTO notifications
            (user_id, intervention_id, sos_id, type, title, message, is_read, created_at)
        SELECT recipients.user_id, ?, ?, 'sos', '🆘 NOUVELLE ALERTE SOS - À ACCEPTER', ?, 0, NOW()
        FROM (
            SELECT id AS user_id
            FROM users
            WHERE actif = 1 AND role IN ('admin', 'superviseur')
            UNION
            SELECT tm.user_id
            FROM team_members tm
            INNER JOIN users u ON u.id = tm.user_id
            WHERE u.actif = 1
        ) AS recipients
    ");
    $broadcast->execute([$interventionId, $sosId, $notificationMessage]);

    $pdo->commit();

    json_response([
        'success' => true,
        'message' => 'SOS diffusé à toutes les équipes. La première équipe qui accepte sera affectée automatiquement.',
        'sos_id' => $sosId,
        'intervention_id' => $interventionId,
        'reference' => $reference,
        'team_id' => $originTeamId,
        'latitude' => (float)$latitude,
        'longitude' => (float)$longitude,
        'accuracy' => $accuracy !== false ? $accuracy : null
    ]);

} catch (Throwable $e) {
    if ($pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('SOS BROADCAST ERROR: ' . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Erreur serveur lors de la diffusion du SOS.'
    ], 500);
}
