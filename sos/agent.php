<?php

$title = 'Déclencher un SOS';

require '../includes/header.php';

$pdo = db();

$userId = (int) user()['id'];

$stmt = $pdo->prepare("
    SELECT
        t.id,
        t.nom,
        t.secteur,
        t.statut
    FROM team_members tm
    INNER JOIN teams t
        ON t.id = tm.team_id
    WHERE tm.user_id = ?
    ORDER BY t.nom
");

$stmt->execute([$userId]);

$teams = $stmt->fetchAll();

?>

<div class="toolbar">

    <div>
        <h1>🆘 Déclencher un SOS</h1>

        <p>
            Transmission urgente de votre position au centre de contrôle.
        </p>
    </div>

</div>


<div class="card" style="max-width:700px;margin:auto;text-align:center">

    <?php if (!$teams): ?>

        <div class="alert">

            ⚠️ Votre compte n'est associé à aucune équipe.

            <br><br>

            Contactez l'administrateur.

        </div>

    <?php else: ?>

        <div class="alert">

            🆘 En cas de danger, utilisez le bouton SOS.

            <br>

            Votre position GPS sera transmise automatiquement.

        </div>

        <br>


        <div class="field">

            <label for="team">

                Équipe concernée

            </label>

            <select id="team">

                <?php foreach ($teams as $team): ?>

                    <option value="<?= (int) $team['id'] ?>">

                        <?= e($team['nom']) ?>

                        <?php if (!empty($team['secteur'])): ?>

                            — <?= e($team['secteur']) ?>

                        <?php endif; ?>

                    </option>

                <?php endforeach; ?>

            </select>

        </div>


        <br>


        <button
            type="button"
            id="sosButton"
            class="sos-menu-btn"
            style="
                width:100%;
                max-width:420px;
                margin:auto;
                min-height:80px;
                font-size:20px;
            "
        >

            🆘 DÉCLENCHER UN SOS

        </button>


        <div
            id="sosStatus"
            class="sos-status"
        ></div>

    <?php endif; ?>

</div>


<style>

.sos-status {

    margin-top:20px;

    padding:16px;

    border-radius:10px;

    display:none;

}

.sos-status.info {

    display:block;

    background:#fff3cd;

    color:#664d03;

}

.sos-status.success {

    display:block;

    background:#d1e7dd;

    color:#0f5132;

}

.sos-status.error {

    display:block;

    background:#f8d7da;

    color:#842029;

}

.sos-countdown {

    font-size:48px;

    font-weight:900;

    margin:15px 0;

}

</style>


<script>

const sosButton =
    document.getElementById('sosButton');

const sosStatus =
    document.getElementById('sosStatus');

const teamSelect =
    document.getElementById('team');


if (sosButton) {

    sosButton.addEventListener(
        'click',
        startSos
    );

}


function startSos() {

    const teamId =
        teamSelect.value;

    if (!teamId) {

        showStatus(
            'error',
            '❌ Veuillez sélectionner une équipe.'
        );

        return;

    }


    const confirmation = confirm(

        "🆘 ALERTE SOS\n\n" +

        "Vous êtes sur le point de signaler " +
        "une situation de danger.\n\n" +

        "Votre position GPS sera transmise " +
        "au centre de contrôle.\n\n" +

        "Voulez-vous continuer ?"

    );


    if (!confirmation) {

        return;

    }


    getGps(teamId);

}


function getGps(teamId) {

    if (!navigator.geolocation) {

        showStatus(
            'error',
            '❌ La géolocalisation n’est pas disponible sur cet appareil.'
        );

        return;

    }


    sosButton.disabled = true;


    showStatus(
        'info',
        '📍 Récupération de votre position GPS...'
    );


    navigator.geolocation.getCurrentPosition(

        function (position) {

            const latitude =
                position.coords.latitude;

            const longitude =
                position.coords.longitude;

            const accuracy =
                position.coords.accuracy;


            if (
                !Number.isFinite(latitude) ||
                !Number.isFinite(longitude)
            ) {

                sosButton.disabled = false;

                showStatus(
                    'error',
                    '❌ Position GPS invalide.'
                );

                return;

            }


            if (accuracy > 1000) {

                const confirmation =
                    confirm(

                        "⚠️ PRÉCISION GPS FAIBLE\n\n" +

                        "Précision actuelle : " +
                        Math.round(accuracy) +
                        " mètres.\n\n" +

                        "Voulez-vous quand même continuer ?"

                    );


                if (!confirmation) {

                    sosButton.disabled = false;

                    showStatus(
                        'error',
                        '❌ Transmission annulée.'
                    );

                    return;

                }

            }


            countdown(
                teamId,
                latitude,
                longitude,
                accuracy
            );

        },

        function (error) {

            sosButton.disabled = false;


            let message =
                '❌ Impossible de récupérer votre position GPS.';


            if (error.code === 1) {

                message =
                    '❌ Autorisation GPS refusée. ' +
                    'Autorisez la géolocalisation puis réessayez.';

            }


            if (error.code === 2) {

                message =
                    '❌ Position GPS indisponible.';

            }


            if (error.code === 3) {

                message =
                    '❌ Délai dépassé pour récupérer le GPS.';

            }


            showStatus(
                'error',
                message
            );

        },

        {
            enableHighAccuracy: true,
            timeout: 15000,
            maximumAge: 0
        }

    );

}


function countdown(
    teamId,
    latitude,
    longitude,
    accuracy
) {

    let seconds = 3;

    let cancelled = false;


    showStatus(

        'info',

        '🚨 Transmission du SOS dans...' +

        '<div class="sos-countdown">' +
        seconds +
        '</div>' +

        '<button ' +
        'type="button" ' +
        'id="cancelSos" ' +
        'class="btn secondary">' +
        'Annuler' +
        '</button>'

    );


    const cancelButton =
        document.getElementById('cancelSos');


    cancelButton.onclick = function () {

        cancelled = true;

        sosButton.disabled = false;

        showStatus(
            'error',
            '❌ SOS annulé.'
        );

    };


    const timer =
        setInterval(function () {

            if (cancelled) {

                clearInterval(timer);

                return;

            }


            seconds--;


            const counter =
                document.querySelector(
                    '.sos-countdown'
                );


            if (counter) {

                counter.textContent =
                    seconds;

            }


            if (seconds <= 0) {

                clearInterval(timer);


                sendSos(
                    teamId,
                    latitude,
                    longitude,
                    accuracy
                );

            }

        }, 1000);

}


async function sendSos(
    teamId,
    latitude,
    longitude,
    accuracy
) {

    showStatus(
        'info',
        '🚨 Transmission du SOS au centre de contrôle...'
    );


    const formData =
        new FormData();


    formData.append(
        'team_id',
        teamId
    );


    formData.append(
        'latitude',
        latitude
    );


    formData.append(
        'longitude',
        longitude
    );


    formData.append(
        'accuracy',
        accuracy
    );


    formData.append(
        'message',
        'Équipe en danger - SOS déclenché depuis le terrain'
    );


    formData.append(
        'csrf',
        '<?= csrf() ?>'
    );


    try {

        const response =
            await fetch(
                (window.APP_BASE_URL || '') + '/api/sos_create.php',
                {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'application/json'
                    }
                }
            );


        const text =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(text);

        } catch (e) {

            console.error(
                'Réponse serveur:',
                text
            );

            throw new Error(
                'Le serveur a renvoyé une réponse invalide.'
            );

        }


        if (!response.ok || !data.success) {

            throw new Error(
                data.message ||
                'Impossible de transmettre le SOS.'
            );

        }


        showStatus(

            'success',

            '🆘 <strong>SOS TRANSMIS</strong><br><br>' +

            'Votre alerte a été enregistrée et diffusée au centre de contrôle.<br>' +
            'Une intervention très urgente a été créée et diffusée à toutes les équipes.<br>La première équipe disponible qui accepte sera affectée automatiquement.<br>' +
            (data.reference ? '<strong>Référence : ' + data.reference + '</strong><br>' : '') +
            '<br>' +

            '📍 Latitude : ' +
            latitude.toFixed(6) +

            '<br>' +

            '📍 Longitude : ' +
            longitude.toFixed(6) +

            '<br>' +

            '🎯 Précision : ' +
            Math.round(accuracy) +
            ' m'

        );


        sosButton.disabled = true;


        if (navigator.vibrate) {

            navigator.vibrate([
                300,
                150,
                300,
                150,
                500
            ]);

        }

    } catch (error) {

        console.error(
            'Erreur SOS:',
            error
        );


        sosButton.disabled = false;


        showStatus(
            'error',
            '❌ ' + error.message
        );

    }

}


function showStatus(
    type,
    message
) {

    sosStatus.className =
        'sos-status ' + type;

    sosStatus.innerHTML =
        message;

}

</script>


<?php require '../includes/footer.php'; ?>

