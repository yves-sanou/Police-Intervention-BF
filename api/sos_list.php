<?php
require_once __DIR__.'/../config/config.php';
require_login();
$rows=db()->query("SELECT s.id,s.team_id,s.user_id,s.latitude,s.longitude,s.accuracy,s.message,s.status,s.created_at,s.acknowledged_at,s.resolved_at,t.nom team,t.secteur,u.nom agent FROM sos_alerts s JOIN teams t ON t.id=s.team_id LEFT JOIN users u ON u.id=s.user_id ORDER BY s.created_at DESC LIMIT 100")->fetchAll();
json_response(['success'=>true,'alerts'=>$rows]);
