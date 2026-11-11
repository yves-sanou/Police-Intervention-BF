<?php
declare(strict_types=1);
require_once __DIR__ . '/../config/config.php';
require_login();

try {
    $rows = db()->query("
        SELECT
            s.id, s.team_id AS origin_team_id, s.user_id, s.latitude, s.longitude,
            s.accuracy, s.message, s.status, s.created_at, s.intervention_id,
            origin.nom AS team, origin.secteur AS secteur,
            u.nom AS agent,
            i.reference, i.team_id AS assigned_team_id, i.status AS intervention_status,
            assigned.nom AS assigned_team
        FROM sos_alerts s
        LEFT JOIN teams origin ON origin.id = s.team_id
        LEFT JOIN users u ON u.id = s.user_id
        LEFT JOIN interventions i ON i.id = s.intervention_id
        LEFT JOIN teams assigned ON assigned.id = i.team_id
        WHERE s.status IN ('active', 'prise_en_charge')
        ORDER BY s.created_at DESC
        LIMIT 100
    ")->fetchAll(PDO::FETCH_ASSOC);
    json_response(['success' => true, 'alerts' => $rows]);
} catch (Throwable $e) {
    error_log('SOS ACTIVE API ERROR: ' . $e->getMessage());
    json_response(['success' => false, 'message' => 'Impossible de charger les alertes SOS.'], 500);
}
