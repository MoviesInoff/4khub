<?php
require_once __DIR__.'/../includes/core.php';
header('Content-Type: application/json');
sess();
if(!loggedIn()){echo json_encode(array('success'=>false,'redirect'=>'/login.php'));exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){echo json_encode(array('success'=>false));exit;}
$raw=file_get_contents('php://input');
$data=$raw?json_decode($raw,true):array();
if(!$data)$data=$_POST;
$mid=intval(isset($data['media_id'])?$data['media_id']:0);
if(!$mid){echo json_encode(array('success'=>false,'message'=>'Invalid ID'));exit;}
$uid=$_SESSION['uid'];
$ex=DB::row("SELECT id FROM watchlist WHERE user_id=? AND media_id=?",array($uid,$mid));
if($ex){DB::exec("DELETE FROM watchlist WHERE id=?",array($ex['id']));echo json_encode(array('success'=>true,'added'=>false,'message'=>'Removed from watchlist'));}
else{DB::insert("INSERT INTO watchlist(user_id,media_id) VALUES(?,?)",array($uid,$mid));echo json_encode(array('success'=>true,'added'=>true,'message'=>'Added to watchlist'));}
