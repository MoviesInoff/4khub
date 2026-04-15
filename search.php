<?php
require_once __DIR__.'/includes/core.php';
require_once __DIR__.'/includes/cards.php';
$q    = trim(isset($_GET['q'])?$_GET['q']:'');
$type = isset($_GET['type'])?$_GET['type']:'all';
$page = max(1,intval(isset($_GET['page'])?$_GET['page']:1));
$pageTitle = ($q?'"'.e($q).'" - ':'').'Search - '.setting('site_name','CineHub');
$results=array(); $totalPages=1; $totalResults=0;
if($q){
    // Search local DB first
    $dbResults=DB::rows("SELECT * FROM media WHERE status='published' AND title LIKE ? ORDER BY vote_average DESC LIMIT 40",array('%'.addslashes($q).'%'));
    // Also search TMDB
    $ep = ($type==='tv')?'/search/tv':($type==='movie'?'/search/movie':'/search/multi');
    $td = tmdbRequest($ep,array('query'=>$q,'page'=>$page));
    $tmdbResults=isset($td['results'])?$td['results']:array(); $totalPages=min(isset($td['total_pages'])?$td['total_pages']:1,20); $totalResults=isset($td['total_results'])?$td['total_results']:0;
}
include __DIR__.'/includes/header.php';
?>
<div class="page-header">
  <h1 class="page-title"><?php echo $q?'Results for "'.$q.'"':'Search';?></h1>
  <div style="margin-top:16px;max-width:560px">
    <form method="GET" style="display:flex;gap:10px">
      <div style="flex:1;position:relative"><i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.85rem"></i><input type="text" name="q" class="form-control" placeholder="Search movies, series, anime..." value="<?php echo e($q);?>" style="padding-left:36px;border-radius:20px"></div>
      <select name="type" class="fselect"><option value="all">All</option><option value="movie" <?php echo $type==='movie'?'selected':'';?>>Movies</option><option value="tv" <?php echo $type==='tv'?'selected':'';?>>Series</option></select>
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
  </div>
</div>
<div style="padding:0 20px 48px">
<?php if(!$q): ?>
<div class="no-results"><i class="fas fa-search"></i><h3>Search for something</h3><p>Enter a movie or series name above</p></div>
<?php elseif(!empty($dbResults)): ?>
<div style="margin-bottom:8px;color:var(--text3);font-size:.82rem">Found <?php echo count($dbResults);?> local result<?php echo count($dbResults)!==1?'s':'';?></div>
<div class="cards-grid-lg"><?php foreach($dbResults as $m) echo renderCard($m);?></div>
<?php elseif(!empty($tmdbResults)): ?>
<div style="margin-bottom:8px;color:var(--text3);font-size:.82rem"><?php echo number_format($totalResults);?> results (from TMDB)</div>
<div class="cards-grid-lg">
<?php foreach($tmdbResults as $m):
  $mt=isset($m['media_type'])?$m['media_type']:$type;$t=isset($m['title'])?$m['title']:(isset($m['name'])?$m['name']:'');
  $p=isset($m['poster_path'])&&$m['poster_path']?tmdbImg($m['poster_path'],'w342'):'/assets/images/no-poster.jpg';
  $y=substr(isset($m['release_date'])?$m['release_date']:(isset($m['first_air_date'])?$m['first_air_date']:''),0,4);
  $r=number_format(floatval($m['vote_average']??0),1);
  $u=($mt==='tv'||$type==='tv')?'/series.php?id='.$m['id']:'/movie.php?id='.$m['id'];
?>
<div class="card" style="flex:none"><a href="<?php echo e($u);?>"><img src="<?php echo e($p);?>" class="card-poster" alt="<?php echo e($t);?>" loading="lazy"></a><a href="<?php echo e($u);?>" class="card-info"><div class="card-title"><?php echo e($t);?></div><div class="card-meta"><span><?php echo e($y);?></span><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i><?php echo e($r);?></span></div></a><div class="card-overlay"><a href="<?php echo e($u);?>" class="card-play"><i class="fas fa-play"></i></a></div></div>
<?php endforeach;?></div>
<?php else: ?><div class="no-results"><i class="fas fa-search"></i><h3>No results for "<?php echo e($q);?>"</h3></div><?php endif;?>
</div>
<?php include __DIR__.'/includes/footer.php';?>
