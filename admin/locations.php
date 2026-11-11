<?php

$title = 'Positions des équipes';

require '../includes/header.php';

$rows = db()->query("
    SELECT
        l.id,
        l.team_id,
        l.user_id,
        l.latitude,
        l.longitude,
        l.accuracy,
        l.recorded_at,
        t.nom AS team,
        t.secteur,
        t.statut AS team_status,
        u.nom AS agent
    FROM team_locations l
    INNER JOIN teams t
        ON t.id = l.team_id
    LEFT JOIN users u
        ON u.id = l.user_id
    INNER JOIN (
        SELECT
            team_id,
            MAX(recorded_at) AS last_recorded
        FROM team_locations
        GROUP BY team_id
    ) latest
        ON latest.team_id = l.team_id
        AND latest.last_recorded = l.recorded_at
    ORDER BY t.nom ASC
")->fetchAll();

$teams = [];

foreach ($rows as $row) {

    $teamId = (int) $row['team_id'];

    if (!isset($teams[$teamId])) {

        $teams[$teamId] = [
            'team_id' => $teamId,
            'team' => $row['team'],
            'secteur' => $row['secteur'],
            'status' => $row['team_status'] ?: 'normal',
            'latitude' => $row['latitude'],
            'longitude' => $row['longitude'],
            'accuracy' => $row['accuracy'],
            'recorded_at' => $row['recorded_at'],
            'agents' => []
        ];
    }

    if (!empty($row['agent'])) {

        $teams[$teamId]['agents'][] =
            $row['agent'];

    }
}

$teams = array_values($teams);

?>

<div class="toolbar">

    <div>

        <h1>🗺️ Positions des équipes</h1>

        <p>
            Suivi GPS des équipes sur le terrain
        </p>

    </div>

    <div
        style="
            display:flex;
            gap:8px;
            flex-wrap:wrap;
        "
    >

        <button
            type="button"
            class="btn"
            onclick="centerMap()"
        >
            🎯 Recentrer
        </button>

        <button
            type="button"
            class="btn secondary"
            onclick="reloadPositions()"
        >
            🔄 Actualiser
        </button>

    </div>

</div>


<div
    class="grid"
    style="margin-bottom:16px"
>

    <div class="card">

        <div>
            🚙 Équipes
        </div>

        <div
            class="stat"
            id="totalTeams"
        >
            0
        </div>

    </div>


    <div class="card">

        <div>
            📍 GPS
        </div>

        <div
            class="stat"
            id="gpsCount"
        >
            0
        </div>

    </div>


    <div class="card">

        <div>
            🟢 Disponibles
        </div>

        <div
            class="stat"
            id="availableCount"
        >
            0
        </div>

    </div>


    <div class="card">

        <div>
            🔴 SOS
        </div>

        <div
            class="stat"
            id="sosCount"
        >
            0
        </div>

    </div>

</div>


<div class="card legend-card">

    <strong>
        Légende
    </strong>

    <span>
        🚙 Normal
    </span>

    <span>
        🟢 Disponible
    </span>

    <span>
        🟠 Intervention
    </span>

    <span>
        🔴 SOS
    </span>

</div>


<br>


<div
    class="card map-card"
>

    <div
        id="map"
        class="team-map"
    ></div>

</div>


<br>


<div
    class="card"
    style="
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
    "
>

    <div>

        <strong>
            🔄 Suivi automatique
        </strong>

        <div
            id="lastUpdate"
            style="
                font-size:13px;
                color:#68737d;
                margin-top:4px;
            "
        >
            Chargement...
        </div>

    </div>


    <div id="connectionStatus">

        🟡 Connexion...

    </div>

</div>


<br>


<div class="table-wrap">

    <table class="table">

        <thead>

            <tr>

                <th>
                    Équipe
                </th>

                <th>
                    État
                </th>

                <th>
                    Agents
                </th>

                <th>
                    GPS
                </th>

                <th>
                    Précision
                </th>

                <th>
                    Dernière transmission
                </th>

            </tr>

        </thead>


        <tbody>

        <?php if (!$teams): ?>

            <tr>

                <td
                    colspan="6"
                    style="
                        text-align:center;
                        padding:30px;
                    "
                >

                    📍 Aucune position GPS enregistrée.

                </td>

            </tr>

        <?php else: ?>

            <?php foreach ($teams as $team): ?>

                <?php

                $status =
                    strtolower(
                        trim(
                            $team['status'] ?? 'normal'
                        )
                    );

                if (
                    in_array(
                        $status,
                        [
                            'disponible',
                            'available'
                        ],
                        true
                    )
                ) {

                    $statusLabel =
                        '🟢 Disponible';

                } elseif (
                    in_array(
                        $status,
                        [
                            'intervention',
                            'en intervention'
                        ],
                        true
                    )
                ) {

                    $statusLabel =
                        '🟠 Intervention';

                } elseif (
                    in_array(
                        $status,
                        [
                            'danger',
                            'sos',
                            'active'
                        ],
                        true
                    )
                ) {

                    $statusLabel =
                        '🔴 SOS';

                } else {

                    $statusLabel =
                        '🚙 Normal';

                }

                $agents =
                    !empty($team['agents'])
                    ? implode(
                        ', ',
                        $team['agents']
                    )
                    : '-';

                ?>

                <tr>

                    <td>

                        <strong>
                            🚙
                            <?= e(
                                $team['team']
                            ) ?>
                        </strong>

                        <?php if (
                            !empty(
                                $team['secteur']
                            )
                        ): ?>

                            <br>

                            <small>

                                📌
                                <?= e(
                                    $team['secteur']
                                ) ?>

                            </small>

                        <?php endif; ?>

                    </td>


                    <td>

                        <?= $statusLabel ?>

                    </td>


                    <td>

                        👮
                        <?= e(
                            $agents
                        ) ?>

                    </td>


                    <td>

                        <?php if (
                            $team['latitude'] !== null &&
                            $team['longitude'] !== null
                        ): ?>

                            <span class="gps">

                                <?= number_format(
                                    (float)
                                    $team['latitude'],
                                    6,
                                    '.',
                                    ''
                                ) ?>

                                ,

                                <?= number_format(
                                    (float)
                                    $team['longitude'],
                                    6,
                                    '.',
                                    ''
                                ) ?>

                            </span>

                        <?php else: ?>

                            -

                        <?php endif; ?>

                    </td>


                    <td>

                        <?= $team['accuracy'] !== null
                            ? e(
                                $team['accuracy']
                            ) . ' m'
                            : '-'
                        ?>

                    </td>


                    <td>

                        <?= e(
                            $team['recorded_at'] ?? '-'
                        ) ?>

                    </td>

                </tr>

            <?php endforeach; ?>

        <?php endif; ?>

        </tbody>

    </table>

</div>


<style>

.team-map {

    width:100%;

    height:620px;

    min-height:450px;

    border-radius:12px;

    overflow:hidden;

}


.map-card {

    padding:8px;

}


.legend-card {

    display:flex;

    align-items:center;

    gap:20px;

    flex-wrap:wrap;

}


.gps {

    font-family:monospace;

    font-size:12px;

}


.vehicle-marker {

    width:46px;

    height:46px;

    display:flex;

    align-items:center;

    justify-content:center;

    border-radius:50%;

    border:3px solid #fff;

    box-shadow:
        0 3px 12px rgba(0,0,0,.35);

    font-size:25px;

}


.vehicle-normal {

    background:#52606d;

}


.vehicle-available {

    background:#198754;

}


.vehicle-intervention {

    background:#f08c00;

}


.vehicle-sos {

    background:#c62828;

    animation:
        sosPulse 1.2s infinite;

}


@keyframes sosPulse {

    0% {

        box-shadow:
            0 0 0 0
            rgba(198,40,40,.7);

    }

    70% {

        box-shadow:
            0 0 0 18px
            rgba(198,40,40,0);

    }

    100% {

        box-shadow:
            0 0 0 0
            rgba(198,40,40,0);

    }

}


.team-popup {

    min-width:260px;

}


.team-popup h3 {

    margin:
        0 0 8px;

}


.team-popup hr {

    border:0;

    border-top:
        1px solid #ddd;

    margin:
        10px 0;

}


.team-popup .danger-link {

    display:block;

    margin-top:12px;

    padding:10px;

    background:#c62828;

    color:#fff;

    text-decoration:none;

    text-align:center;

    border-radius:8px;

    font-weight:800;

}


.team-popup .danger-link:hover {

    background:#a71919;

}


.marker-cluster-small,
.marker-cluster-medium,
.marker-cluster-large {

    background:
        rgba(18,60,45,.25);

}


.marker-cluster div {

    background:
        #123c2d;

    color:#fff;

    font-weight:800;

}


@media(max-width:600px) {

    .team-map {

        height:450px;

        min-height:400px;

    }

    .legend-card {

        gap:10px;

    }

}

</style>


<script>

const initialTeams =
    <?= json_encode(
        $teams,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_NUMERIC_CHECK
    ) ?>;


let map = null;

let markerCluster = null;

let markers = [];


document.addEventListener(
    'DOMContentLoaded',
    function () {

        initTeamMap();

        displayTeams(
            initialTeams
        );

    }
);


function initTeamMap() {

    const element =
        document.getElementById(
            'map'
        );


    if (!element) {

        return;

    }


    if (typeof L === 'undefined') {

        element.innerHTML = `

            <div style="
                height:100%;
                display:flex;
                align-items:center;
                justify-content:center;
                text-align:center;
                padding:30px;
                color:#b42318;
                background:#fff0f0;
            ">

                <div>

                    <strong>
                        ⚠️ Leaflet n'est pas chargé.
                    </strong>

                    <br><br>

                    Vérifiez votre
                    <strong>footer.php</strong>
                    et le chargement de Leaflet.

                </div>

            </div>

        `;

        return;

    }


    if (
        typeof L.MarkerClusterGroup ===
        'undefined'
    ) {

        element.innerHTML = `

            <div style="
                height:100%;
                display:flex;
                align-items:center;
                justify-content:center;
                text-align:center;
                padding:30px;
                color:#b42318;
                background:#fff0f0;
            ">

                <div>

                    <strong>
                        ⚠️ Leaflet MarkerCluster
                        n'est pas chargé.
                    </strong>

                    <br><br>

                    Ajoutez le plugin
                    Leaflet MarkerCluster
                    dans votre footer.php.

                </div>

            </div>

        `;

        return;

    }


    map =
        L.map(
            'map',
            {
                center:[
                    12.3714,
                    -1.5197
                ],
                zoom:6,
                zoomControl:true
            }
        );


    L.tileLayer(
        'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        {
            maxZoom:19,

            attribution:
                '&copy; OpenStreetMap contributors'
        }
    ).addTo(map);


    markerCluster =
        L.markerClusterGroup({

            showCoverageOnHover:false,

            zoomToBoundsOnClick:true,

            spiderfyOnMaxZoom:true,

            removeOutsideVisibleBounds:true,

            animate:true,

            animateAddingMarkers:true,

            maxClusterRadius:60,

            disableClusteringAtZoom:16

        });


    map.addLayer(
        markerCluster
    );


    setTimeout(
        function () {

            map.invalidateSize();

        },
        500
    );


    window.addEventListener(
        'resize',
        function () {

            map.invalidateSize();

        }
    );

}


function displayTeams(
    teams
) {

    if (!map || !markerCluster) {

        return;

    }


    markerCluster.clearLayers();

    markers = [];


    let gpsCount = 0;

    let availableCount = 0;

    let sosCount = 0;


    teams.forEach(
        function (team) {

            const lat =
                parseFloat(
                    team.latitude
                );

            const lng =
                parseFloat(
                    team.longitude
                );


            if (
                !Number.isFinite(lat) ||
                !Number.isFinite(lng)
            ) {

                return;

            }


            gpsCount++;


            const status =
                normalizeStatus(
                    team.status
                );


            if (
                status === 'available'
            ) {

                availableCount++;

            }


            if (
                status === 'sos'
            ) {

                sosCount++;

            }


            const marker =
                L.marker(
                    [
                        lat,
                        lng
                    ],
                    {
                        icon:
                            createVehicleIcon(
                                status
                            ),

                        title:
                            team.team
                    }
                );


            marker.bindPopup(
                createPopup(
                    team,
                    status
                )
            );


            markerCluster.addLayer(
                marker
            );


            markers.push(
                marker
            );

        }
    );


    document.getElementById(
        'totalTeams'
    ).textContent =
        teams.length;


    document.getElementById(
        'gpsCount'
    ).textContent =
        gpsCount;


    document.getElementById(
        'availableCount'
    ).textContent =
        availableCount;


    document.getElementById(
        'sosCount'
    ).textContent =
        sosCount;


    if (
        markers.length === 1
    ) {

        map.setView(
            markers[0].getLatLng(),
            15
        );

    }


    if (
        markers.length > 1
    ) {

        const group =
            L.featureGroup(
                markers
            );


        map.fitBounds(
            group.getBounds().pad(
                .15
            )
        );

    }

}


function normalizeStatus(
    status
) {

    status =
        String(
            status || ''
        )
        .toLowerCase()
        .trim();


    if (
        [
            'disponible',
            'available'
        ].includes(status)
    ) {

        return 'available';

    }


    if (
        [
            'intervention',
            'en intervention'
        ].includes(status)
    ) {

        return 'intervention';

    }


    if (
        [
            'danger',
            'sos',
            'active'
        ].includes(status)
    ) {

        return 'sos';

    }


    return 'normal';

}


function createVehicleIcon(
    status
) {

    let css =
        'vehicle-normal';


    if (
        status === 'available'
    ) {

        css =
            'vehicle-available';

    }


    if (
        status === 'intervention'
    ) {

        css =
            'vehicle-intervention';

    }


    if (
        status === 'sos'
    ) {

        css =
            'vehicle-sos';

    }


    return L.divIcon({

        className:'',

        html:
            '<div class="vehicle-marker ' +
            css +
            '">🚙</div>',

        iconSize:[
            46,
            46
        ],

        iconAnchor:[
            23,
            23
        ],

        popupAnchor:[
            0,
            -23
        ]

    });

}


function createPopup(
    team,
    status
) {

    let statusLabel =
        '🚙 Normal';


    if (
        status === 'available'
    ) {

        statusLabel =
            '🟢 Disponible';

    }


    if (
        status === 'intervention'
    ) {

        statusLabel =
            '🟠 En intervention';

    }


    if (
        status === 'sos'
    ) {

        statusLabel =
            '🔴 ÉQUIPE EN DANGER';

    }


    let agents =
        'Aucun agent';


    if (
        Array.isArray(
            team.agents
        ) &&
        team.agents.length
    ) {

        agents =
            team.agents.join(
                ', '
            );

    }


    const gps =
        Number(
            team.latitude
        ).toFixed(6)
        +
        ', '
        +
        Number(
            team.longitude
        ).toFixed(6);


    let sosButton = '';


    if (
        status === 'sos'
    ) {

        sosButton = `

            <a
                href="<?= e(url('sos/')) ?>"
                class="danger-link"
            >
                🆘 Ouvrir l'alerte SOS
            </a>

        `;

    }


    return `

        <div class="team-popup">

            <h3>

                🚙
                ${escapeHtml(
                    team.team
                )}

            </h3>

            <strong>

                ${statusLabel}

            </strong>

            <hr>

            <div>

                👮
                <strong>
                    Agents :
                </strong>

                <br>

                ${escapeHtml(
                    agents
                )}

            </div>

            <br>

            <div>

                📍
                <strong>
                    GPS :
                </strong>

                <br>

                <span class="gps">
                    ${gps}
                </span>

            </div>

            <br>

            <div>

                🎯
                <strong>
                    Précision :
                </strong>

                ${
                    team.accuracy !== null
                    ? escapeHtml(
                        String(
                            team.accuracy
                        )
                    ) + ' m'
                    : '-'
                }

            </div>

            <br>

            <div>

                🕐
                <strong>
                    Dernière transmission :
                </strong>

                <br>

                ${escapeHtml(
                    team.recorded_at || '-'
                )}

            </div>

            ${
                team.secteur
                ? `
                    <br>

                    <div>

                        📌
                        <strong>
                            Secteur :
                        </strong>

                        ${escapeHtml(
                            team.secteur
                        )}

                    </div>
                `
                : ''
            }

            ${sosButton}

        </div>

    `;

}


async function reloadPositions() {

    try {

        const response =
            await fetch(
                (window.APP_BASE_URL || '') + '/api/team_positions.php?t=' +
                Date.now(),
                {
                    method:'GET',

                    cache:'no-store',

                    headers:{
                        'Accept':
                            'application/json'
                    }
                }
            );


        const text =
            await response.text();


        let data;


        try {

            data =
                JSON.parse(
                    text
                );

        } catch (error) {

            console.error(
                'Réponse API :',
                text
            );

            throw new Error(
                'Réponse JSON invalide'
            );

        }


        if (
            !response.ok ||
            !data.success
        ) {

            throw new Error(
                data.message ||
                'Erreur API'
            );

        }


        const teams =
            Array.isArray(
                data.teams
            )
            ? data.teams
            : [];


        displayTeams(
            teams
        );


        document.getElementById(
            'lastUpdate'
        ).textContent =
            'Dernière actualisation : ' +
            new Date().toLocaleTimeString(
                'fr-FR'
            );


        document.getElementById(
            'connectionStatus'
        ).innerHTML =
            '<span style="color:#087443">🟢 Connecté</span>';


    } catch (error) {

        console.error(
            'Erreur GPS :',
            error
        );


        document.getElementById(
            'connectionStatus'
        ).innerHTML =
            '<span style="color:#b42318">🔴 Erreur de connexion</span>';

    }

}


setInterval(
    reloadPositions,
    5000
);


function centerMap() {

    if (!map) {

        return;

    }


    if (!markers.length) {

        map.setView(
            [
                12.3714,
                -1.5197
            ],
            6
        );

        return;

    }


    if (
        markers.length === 1
    ) {

        map.setView(
            markers[0].getLatLng(),
            15
        );

        return;

    }


    const group =
        L.featureGroup(
            markers
        );


    map.fitBounds(
        group.getBounds().pad(
            .15
        )
    );

}


function escapeHtml(
    value
) {

    return String(
        value
    )

    .replace(
        /&/g,
        '&amp;'
    )

    .replace(
        /</g,
        '&lt;'
    )

    .replace(
        />/g,
        '&gt;'
    )

    .replace(
        /"/g,
        '&quot;'
    )

    .replace(
        /'/g,
        '&#039;'
    );

}

</script>


<?php

require '../includes/footer.php';

?>

