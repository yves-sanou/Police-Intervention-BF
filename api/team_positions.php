<?php

require_once __DIR__ . '/../config/config.php';

require_login();

header('Content-Type: application/json; charset=utf-8');

try {

    $pdo = db();

    $teamsStmt = $pdo->query("
        SELECT
            id,
            nom,
            secteur,
            statut
        FROM teams
        ORDER BY nom
    ");

    $teams = $teamsStmt->fetchAll(PDO::FETCH_ASSOC);

    $result = [];

    foreach ($teams as $team) {

        $teamId = (int) $team['id'];

        $locationStmt = $pdo->prepare("
            SELECT
                latitude,
                longitude,
                accuracy,
                recorded_at
            FROM team_locations
            WHERE team_id = ?
            ORDER BY recorded_at DESC
            LIMIT 1
        ");

        $locationStmt->execute([
            $teamId
        ]);

        $location =
            $locationStmt->fetch(PDO::FETCH_ASSOC);


        $membersStmt = $pdo->prepare("
            SELECT
                u.id,
                u.nom,
                u.email,
                u.role
            FROM team_members tm
            INNER JOIN users u
                ON u.id = tm.user_id
            WHERE tm.team_id = ?
            AND u.actif = 1
            ORDER BY u.nom
        ");

        $membersStmt->execute([
            $teamId
        ]);

        $members =
            $membersStmt->fetchAll(PDO::FETCH_ASSOC);


        $agents = [];

        foreach ($members as $member) {

            $agents[] =
                $member['nom'];

        }


        $sosStmt = $pdo->prepare("
            SELECT
                id,
                created_at
            FROM sos_alerts
            WHERE team_id = ?
            AND status = 'active'
            ORDER BY created_at DESC
            LIMIT 1
        ");

        $sosStmt->execute([
            $teamId
        ]);

        $sos =
            $sosStmt->fetch(PDO::FETCH_ASSOC);


        $status =
            'normal';


        if ($sos) {

            $status =
                'danger';

        } else {

            $teamStatus =
                strtolower(
                    trim(
                        $team['statut'] ?? ''
                    )
                );


            if (
                in_array(
                    $teamStatus,
                    [
                        'intervention',
                        'en_intervention',
                        'en intervention'
                    ],
                    true
                )
            ) {

                $status =
                    'intervention';

            } elseif (
                in_array(
                    $teamStatus,
                    [
                        'disponible',
                        'available'
                    ],
                    true
                )
            ) {

                $status =
                    'available';

            }

        }


        $result[] = [

            'team_id' =>
                $teamId,

            'team_name' =>
                $team['nom'],

            'secteur' =>
                $team['secteur'] ?? '',

            'team_status' =>
                $team['statut'] ?? '',

            'status' =>
                $status,

            'agents' =>
                implode(
                    ', ',
                    $agents
                ),

            'members' =>
                $members,

            'latitude' =>
                $location
                    ? (float) $location['latitude']
                    : null,

            'longitude' =>
                $location
                    ? (float) $location['longitude']
                    : null,

            'accuracy' =>
                $location &&
                $location['accuracy'] !== null
                    ? (float) $location['accuracy']
                    : null,

            'recorded_at' =>
                $location
                    ? $location['recorded_at']
                    : null,

            'sos_id' =>
                $sos
                    ? (int) $sos['id']
                    : null,

            'sos_created_at' =>
                $sos
                    ? $sos['created_at']
                    : null

        ];

    }


    echo json_encode(
        [
            'success' => true,
            'teams' => $result
        ],
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES
    );


} catch (Throwable $e) {

    error_log(
        'TEAM POSITIONS ERROR: ' .
        $e->getMessage()
    );

    http_response_code(500);

    echo json_encode(
        [
            'success' => false,
            'message' =>
                'Erreur lors de la récupération des équipes.'
        ],
        JSON_UNESCAPED_UNICODE
    );

}

