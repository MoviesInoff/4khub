<?php
require_once __DIR__.'/includes/core.php';
$pageTitle = 'Anime - '.setting('site_name','CineHub');
$apiKey = setting('tmdb_api_key','');
$page   = max(1, intval($_GET['page'] ?? 1));
$pp     = max(8, intval(setting('items_per_page','20')));
$items  = [];
$totalPages = 1;

if ($apiKey) {
    $data = tmdbRequest('/discover/tv', [
        'with_genres'    => 16,
        'sort_by'        => 'popularity.desc',
        'with_origin_country' => 'JP',
        'page'           => $page
    ]);
    $items = array_slice($data['results'] ?? [], 0, $pp);
    $totalPages = min((int)($data['total_pages'] ?? 1), 50);
}
$localTagsMap = localTagsMapByTmdb(array_map(fn($m)=>$m['id'] ?? 0, $items), 'tv');

include __DIR__.'/includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Anime</h1></div>
<div style="padding:0 20px 48px">
  <?php if(!$apiKey): ?>
  <div class="no-results"><i class="fas fa-key"></i><h3>API Key Required</h3><p><a href="/admin/api-settings.php" style="color:var(--primary)">Configure TMDB API</a></p></div>
  <?php elseif(empty($items)): ?>
  <div class="no-results"><i class="fas fa-dragon"></i><h3>No anime found</h3></div>
  <?php else: ?>
  <div class="cards-grid-lg">
    <?php foreach($items as $m):
      $t=$m['name']??''; $p=isset($m['poster_path'])&&$m['poster_path']?tmdbImg($m['poster_path'],'w342'):'/assets/images/no-poster.jpg';
      $y=substr($m['first_air_date']??'',0,4); $r=number_format(floatval($m['vote_average']??0),1); $u='/series.php?id='.$m['id'];
    ?>
    <div class="card" style="flex:none">
      <div class="card-poster-wrap">
        <a href="<?php echo e($u);?>"><img src="<?php echo e($p);?>" class="card-poster" alt="<?php echo e($t);?>" loading="lazy"></a>
        <span class="tag tag-tv card-tv-tag">TV</span>
        <?php $localTags = $localTagsMap[$m['id']] ?? []; if(!empty($localTags)): ?>
        <div class="card-tags"><?php foreach(array_slice($localTags,0,3) as $ltag): ?><span class="tag <?php echo tagClass($ltag); ?>"><?php echo e(strtoupper($ltag));?></span><?php endforeach;?></div>
        <?php endif;?>
        <div class="card-overlay"><a href="<?php echo e($u);?>" class="card-play"><i class="fas fa-play"></i></a></div>
      </div>
      <a href="<?php echo e($u);?>" class="card-info"><div class="card-title"><?php echo e($t);?></div><div class="card-meta"><span><?php echo e($y);?></span><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i> <?php echo e($r);?></span></div></a>
    </div>
    <?php endforeach;?>
  </div>
  <?php if($totalPages>1): ?>
  <div class="pagination">
    <?php if($page>1): ?><a href="/anime.php?page=<?php echo $page-1;?>" class="ppage"><i class="fas fa-chevron-left"></i></a><?php endif;?>
    <?php for($pg=max(1,$page-2);$pg<=min($totalPages,$page+2);$pg++): ?><a href="/anime.php?page=<?php echo $pg;?>" class="ppage <?php echo $pg===$page?'active':'';?>"><?php echo $pg;?></a><?php endfor;?>
    <?php if($page<$totalPages): ?><a href="/anime.php?page=<?php echo $page+1;?>" class="ppage"><i class="fas fa-chevron-right"></i></a><?php endif;?>
  </div>
  <?php endif;?>
  <?php endif;?>
</div>
<?php include __DIR__.'/includes/footer.php';?>
