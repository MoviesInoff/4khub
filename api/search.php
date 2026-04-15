<?php
require_once __DIR__.'/../includes/core.php';
header('Content-Type: application/json');
$q=trim(isset($_GET['q'])?$_GET['q']:'');
$limit=min(10,intval(isset($_GET['limit'])?$_GET['limit']:8));
if(strlen($q)<2){echo json_encode(array('results'=>array()));exit;}
// DB first
$dbR=DB::rows("SELECT tmdb_id as id,type as media_type,title,poster_path,year as release_date FROM media WHERE status='published' AND title LIKE ? LIMIT ".intval($limit),array('%'.addslashes($q).'%'));
if(!empty($dbR)){echo json_encode(array('results'=>$dbR));exit;}
// TMDB fallback
$data=tmdbRequest('/search/multi',array('query'=>$q));
$results=array();
foreach(isset($data['results'])?$data['results']:array() as $r){
    $mt=isset($r['media_type'])?$r['media_type']:'';
    if(in_array($mt,array('movie','tv'))&&!empty($r['poster_path'])){$results[]=$r;if(count($results)>=$limit)break;}
}
echo json_encode(array('results'=>$results));
