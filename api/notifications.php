<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_login();

$pdo = db();
$userId = (int)(user()['id'] ?? 0);

try {
    /*
     * Une notification SOS reste comptée tant que l'alerte liée est ouverte.
     * Dès que l'alerte passe à "resolue", elle ne gonfle plus le badge,
     * même si l'utilisateur n'a pas ouvert la notification.
     *
     * Les notifications classiques (intervention, system, urgent sans sos_id)
     * restent comptées jusqu'à leur lecture explicite.
     */
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM notifications n
        LEFT JOIN sos_alerts s ON s.id = n.sos_id
        WHERE n.user_id = :user_id
          AND n.is_read = 0
          AND (
              n.sos_id IS NULL
              OR s.status IN ('active', 'prise_en_charge')
          )
    ");
    $stmt->execute(['user_id' => $userId]);
    $unread = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT
            n.id,
            n.type,
            n.title,
            n.message,
            n.is_read,
            n.sos_id,
            n.created_at,
            s.status AS sos_status
        FROM notifications n
        LEFT JOIN sos_alerts s ON s.id = n.sos_id
        WHERE n.user_id = :user_id
        ORDER BY n.created_at DESC
        LIMIT 30
    ");
    $stmt->execute(['user_id' => $userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json_response([
        'success' => true,
        'unread' => $unread,
        'notifications' => $notifications
    ]);
} catch (Throwable $e) {
    error_log('NOTIFICATIONS API ERROR: ' . $e->getMessage());
    json_response([
        'success' => false,
        'message' => 'Impossible de récupérer les notifications.'
    ], 500);
}
