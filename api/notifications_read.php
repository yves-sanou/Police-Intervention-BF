<?php
require_once __DIR__.'/../config/config.php';
require_login();
if($_SERVER['REQUEST_METHOD']!=='POST')json_response(['success'=>false,'message'=>'Méthode non autorisée'],405);
$id=(int)($_POST['id']??0);
$q=db()->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?");
$q->execute([$id,user()['id']]);
json_response(['success'=>true]);
