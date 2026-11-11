<?php

require_once __DIR__ . '/../config/config.php';

require_login();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    check_csrf();

    $action = $_POST['action'] ?? '';

    $teamId = (int) ($_POST['team_id'] ?? 0);

    $userId = (int) ($_POST['user_id'] ?? 0);

    if ($teamId <= 0 || $userId <= 0) {

        header('Location: team_members.php');
        exit;

    }

    if ($action === 'add') {

        $stmt = $pdo->prepare("
            INSERT IGNORE INTO team_members
            (
                team_id,
                user_id
            )
            VALUES
            (
                ?,
                ?
            )
        ");

        $stmt->execute([
            $teamId,
            $userId
        ]);

    }

    elseif ($action === 'delete') {

        $stmt = $pdo->prepare("
            DELETE FROM team_members
            WHERE team_id = ?
            AND user_id = ?
        ");

        $stmt->execute([
            $teamId,
            $userId
        ]);

    }

    header('Location: team_members.php');
    exit;
}


$title = 'Membres des équipes';

require '../includes/header.php';


$teams = $pdo->query("
    SELECT
        id,
        nom,
        secteur,
        statut
    FROM teams
    ORDER BY nom
")->fetchAll();


$users = $pdo->query("
    SELECT
        id,
        nom,
        email,
        role
    FROM users
    WHERE actif = 1
    ORDER BY nom
")->fetchAll();


$members = $pdo->query("
    SELECT
        tm.team_id,
        tm.user_id,
        t.nom AS team,
        u.nom AS user_name,
        u.email,
        u.role
    FROM team_members tm
    INNER JOIN teams t
        ON t.id = tm.team_id
    INNER JOIN users u
        ON u.id = tm.user_id
    ORDER BY
        t.nom,
        u.nom
")->fetchAll();

?>

<div class="toolbar">

    <div>

        <h1>👥 Membres des équipes</h1>

        <p>
            Gestion des agents affectés aux équipes d'intervention.
        </p>

    </div>

</div>


<div class="card">

    <h2>➕ Affecter un utilisateur à une équipe</h2>

    <form method="post">

        <input
            type="hidden"
            name="csrf"
            value="<?= e(csrf()) ?>"
        >

        <input
            type="hidden"
            name="action"
            value="add"
        >

        <div class="form-grid">

            <div class="field">

                <label for="team_id">
                    Équipe
                </label>

                <select
                    id="team_id"
                    name="team_id"
                    required
                >

                    <option value="">
                        Sélectionner une équipe
                    </option>

                    <?php foreach ($teams as $team): ?>

                        <option
                            value="<?= (int) $team['id'] ?>"
                        >

                            <?= e($team['nom']) ?>

                            <?php if (!empty($team['secteur'])): ?>

                                —
                                <?= e($team['secteur']) ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>


            <div class="field">

                <label for="user_id">
                    Utilisateur
                </label>

                <select
                    id="user_id"
                    name="user_id"
                    required
                >

                    <option value="">
                        Sélectionner un utilisateur
                    </option>

                    <?php foreach ($users as $u): ?>

                        <option
                            value="<?= (int) $u['id'] ?>"
                        >

                            <?= e($u['nom']) ?>

                            —
                            <?= e($u['role']) ?>

                            <?php if (!empty($u['email'])): ?>

                                —
                                <?= e($u['email']) ?>

                            <?php endif; ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

        </div>

        <br>

        <button
            type="submit"
            class="btn"
        >
            ➕ Ajouter à l'équipe
        </button>

    </form>

</div>


<br>


<div class="table-wrap">

    <table class="table">

        <thead>

            <tr>

                <th>Équipe</th>

                <th>Utilisateur</th>

                <th>Email</th>

                <th>Rôle</th>

                <th>Action</th>

            </tr>

        </thead>

        <tbody>

        <?php if (!$members): ?>

            <tr>

                <td
                    colspan="5"
                    style="text-align:center;padding:30px"
                >

                    👥 Aucun membre affecté.

                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($members as $m): ?>

                <tr>

                    <td>

                        👮
                        <?= e($m['team']) ?>

                    </td>

                    <td>

                        <?= e($m['user_name']) ?>

                    </td>

                    <td>

                        <?= e($m['email'] ?? '-') ?>

                    </td>

                    <td>

                        <span class="badge green">

                            <?= e($m['role']) ?>

                        </span>

                    </td>

                    <td>

                        <form
                            method="post"
                            onsubmit="return confirm(
                                'Retirer cet utilisateur de cette équipe ?'
                            );"
                        >

                            <input
                                type="hidden"
                                name="csrf"
                                value="<?= e(csrf()) ?>"
                            >

                            <input
                                type="hidden"
                                name="action"
                                value="delete"
                            >

                            <input
                                type="hidden"
                                name="team_id"
                                value="<?= (int) $m['team_id'] ?>"
                            >

                            <input
                                type="hidden"
                                name="user_id"
                                value="<?= (int) $m['user_id'] ?>"
                            >

                            <button
                                type="submit"
                                class="btn danger"
                            >
                                🗑️ Retirer
                            </button>

                        </form>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>


<?php require '../includes/footer.php'; ?>
