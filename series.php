<?php
require_once __DIR__.'/includes/core.php';

$apiKey  = setting('tmdb_api_key','');
$tmdbId  = intval($_GET['id'] ?? 0);
$slug    = trim($_GET['slug'] ?? '');

// ── DETAIL VIEW ────────────────────────────────────────────────────
if ($tmdbId || $slug) {
    if ($slug && !$tmdbId) {
        // slug lookup (legacy support)
        $row = DB::row("SELECT tmdb_id FROM media WHERE slug=? AND type='tv'", [$slug]);
        if ($row) $tmdbId = $row['tmdb_id'];
        else { header('Location: /series.php'); exit; }
    }

    $detail = tmdbRequest('/tv/'.$tmdbId, ['append_to_response'=>'credits,videos,similar,recommendations']);
    if (empty($detail['id'])) { header('Location: /series.php'); exit; }

    $title      = $detail['name'] ?? '';
    $tagline    = $detail['tagline'] ?? '';
    $overview   = $detail['overview'] ?? '';
    $poster     = $detail['poster_path'] ? tmdbImg($detail['poster_path'],'w500') : '/assets/images/no-poster.jpg';
    $backdrop   = $detail['backdrop_path'] ? tmdbImg($detail['backdrop_path'],'original') : '';
    $year       = substr($detail['first_air_date'] ?? '', 0, 4);
    $rating     = number_format(floatval($detail['vote_average'] ?? 0), 1);
    $seasons    = $detail['number_of_seasons'] ?? 0;
    $eps        = $detail['number_of_episodes'] ?? 0;
    $genres     = $detail['genres'] ?? [];
    $cast       = array_slice($detail['credits']['cast'] ?? [], 0, 10);
    $trailerKey = null;
    foreach ($detail['videos']['results'] ?? [] as $v) {
        if ($v['type']==='Trailer' && $v['site']==='YouTube') { $trailerKey=$v['key']; break; }
    }
    $similar = array_slice($detail['recommendations']['results'] ?? ($detail['similar']['results'] ?? []), 0, 12);
    $seasonsData = array_values(array_filter($detail['seasons'] ?? [], fn($s) => $s['season_number'] > 0));
    $servers = [];
    try { $servers = DB::rows("SELECT * FROM embed_servers WHERE is_active=1 ORDER BY sort_order ASC"); } catch(Exception $e){}
    // Fetch download links if imported
    $downloads = [];
    try {
        $lm = DB::row("SELECT id FROM media WHERE tmdb_id=? AND type='tv'", [$tmdbId]);
        if ($lm) $downloads = DB::rows("SELECT * FROM download_links WHERE media_id=? ORDER BY sort_order ASC, id ASC", [$lm['id']]);
    } catch(Exception $e){}

    $pageTitle = $title.' - '.setting('site_name','CineHub');
    include __DIR__.'/includes/header.php';
?>

<!-- BACKDROP -->
<div style="position:relative;padding-top:var(--header-h);min-height:520px;overflow:hidden">
  <?php if($backdrop): ?>
  <img src="<?php echo e($backdrop); ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center top;filter:blur(2px) brightness(.38);transform:scale(1.05);z-index:0">
  <?php endif; ?>
  <div style="position:absolute;inset:0;background:linear-gradient(0deg,var(--bg) 0%,rgba(13,13,13,.6) 50%,rgba(13,13,13,.35) 100%);z-index:1"></div>

  <div style="position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:36px 20px 48px;display:flex;gap:30px;align-items:flex-start">
    <div style="flex:0 0 200px;display:none" class="detail-poster-col">
      <div style="border-radius:14px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.7)">
        <img src="<?php echo e($poster); ?>" alt="<?php echo e($title); ?>" style="width:100%;display:block">
      </div>
    </div>
    <div style="flex:1;min-width:0">
      <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.5);font-size:.82rem;margin-bottom:18px;transition:color .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.5)'"><i class="fas fa-chevron-left"></i> Back</a>
      <div style="display:inline-flex;align-items:center;gap:5px;background:rgba(59,130,246,.12);border:1px solid rgba(59,130,246,.3);color:#60a5fa;font-size:.7rem;font-weight:700;padding:3px 10px;border-radius:20px;letter-spacing:1px;margin-bottom:10px"><i class="fas fa-tv"></i> SERIES</div>
      <h1 style="font-family:var(--font-display);font-size:clamp(2rem,5vw,3.8rem);letter-spacing:1.5px;color:#fff;line-height:.95;margin-bottom:8px;text-shadow:0 2px 20px rgba(0,0,0,.5)"><?php echo e($title); ?></h1>
      <?php if($tagline): ?><div style="color:var(--primary);font-size:.9rem;font-style:italic;margin-bottom:14px"><?php echo e($tagline); ?></div><?php endif; ?>
      <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px;font-size:.875rem;color:rgba(255,255,255,.75)">
        <?php if($year): ?><span><i class="fas fa-calendar" style="color:var(--primary);margin-right:5px"></i><?php echo e($year); ?></span><?php endif; ?>
        <?php if($rating > 0): ?><span style="color:#fbbf24"><i class="fas fa-star" style="margin-right:4px"></i><?php echo e($rating); ?>/10</span><?php endif; ?>
        <?php if($seasons): ?><span><i class="fas fa-layer-group" style="color:var(--primary);margin-right:5px"></i><?php echo $seasons; ?> Season<?php echo $seasons>1?'s':''; ?></span><?php endif; ?>
        <?php if($eps): ?><span><i class="fas fa-play-circle" style="color:var(--primary);margin-right:5px"></i><?php echo $eps; ?> Eps</span><?php endif; ?>
      </div>
      <?php if(!empty($genres)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:7px;margin-bottom:18px">
        <?php foreach($genres as $g): ?>
        <a href="/series.php?genre=<?php echo $g['id']; ?>" style="padding:4px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.2);font-size:.75rem;color:rgba(255,255,255,.6);transition:all .2s" onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'" onmouseout="this.style.borderColor='rgba(255,255,255,.2)';this.style.color='rgba(255,255,255,.6)'"><?php echo e($g['name']); ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if($overview): ?><p style="color:rgba(255,255,255,.72);font-size:.925rem;line-height:1.75;margin-bottom:22px;max-width:640px"><?php echo e($overview); ?></p><?php endif; ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php if(!empty($servers)): ?><a href="/watch.php?id=<?php echo $tmdbId; ?>&type=tv&s=1&e=1" class="btn btn-primary btn-lg"><i class="fas fa-play"></i> Watch Now</a><?php endif; ?>
        <?php if($trailerKey): ?><button class="btn btn-lg" onclick="openTrailer('<?php echo e($trailerKey); ?>')" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;backdrop-filter:blur(8px)"><i class="fas fa-film"></i> Trailer</button><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Mobile info card -->
<div style="display:flex;gap:18px;padding:20px;border-bottom:1px solid var(--border);max-width:1100px;margin:0 auto" class="mob-info-card">
  <img src="<?php echo e($poster); ?>" style="width:100px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.5);flex-shrink:0;align-self:flex-start" alt="">
  <div>
    <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:8px"><?php echo e($title); ?></div>
    <?php if(!empty($genres)): ?><div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px"><?php foreach(array_slice($genres,0,3) as $g): ?><span style="background:var(--bg3);border:1px solid var(--border);padding:3px 10px;border-radius:14px;font-size:.72rem;color:var(--text3)"><?php echo e($g['name']); ?></span><?php endforeach; ?></div><?php endif; ?>
    <?php if($overview): ?><p style="color:var(--text3);font-size:.82rem;line-height:1.6"><?php echo e(substr($overview,0,200)); echo strlen($overview)>200?'...':''; ?></p><?php endif; ?>
  </div>
</div>

<!-- Seasons -->
<?php if(!empty($seasonsData)): ?>
<section class="section" style="padding-top:20px">
  <div class="section-head"><div class="section-title"><i class="fas fa-layer-group icon"></i> Seasons</div></div>
  <div class="season-tabs">
    <?php foreach($seasonsData as $s): ?>
    <a href="/watch.php?id=<?php echo $tmdbId; ?>&type=tv&s=<?php echo $s['season_number']; ?>&e=1" class="season-tab">
      S<?php echo $s['season_number']; ?> <span style="font-size:.7rem;font-weight:400;opacity:.6">(<?php echo $s['episode_count']??0; ?> eps)</span>
    </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Cast -->
<?php if(!empty($cast)): ?>
<section class="section" style="padding-top:8px">
  <div class="section-head"><div class="section-title"><i class="fas fa-users icon"></i> Cast</div></div>
  <div class="cards-row" style="gap:12px;padding:0 20px">
    <?php foreach($cast as $p):
      $ci = isset($p['profile_path'])&&$p['profile_path'] ? tmdbImg($p['profile_path'],'w185') : '/assets/images/no-poster.jpg';
    ?>
    <div style="flex:0 0 80px;text-align:center">
      <div style="width:68px;height:68px;border-radius:50%;overflow:hidden;margin:0 auto 7px;border:2px solid var(--border)"><img src="<?php echo e($ci); ?>" alt="" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>
      <div style="font-size:.72rem;font-weight:600;color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:80px"><?php echo e($p['name']??''); ?></div>
      <?php if(!empty($p['character'])): ?><div style="font-size:.65rem;color:var(--text4);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:80px"><?php echo e($p['character']); ?></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- Similar -->
<?php if(!empty($downloads)): ?>
<section class="section" style="padding-top:8px">
  <div class="section-head"><div class="section-title"><i class="fas fa-download icon"></i> Download Links</div></div>
  <?php foreach($downloads as $dl): ?>
  <div class="dl-item" style="margin-bottom:8px">
    <div class="dl-head">
      <div class="dl-head-info">
        <div class="dl-name"><?php echo e($dl['title']); ?></div>
        <div class="dl-tags">
          <?php if(!empty($dl['season_num'])): ?><span class="tag tag-default">S<?php echo $dl['season_num'];?><?php if(!empty($dl['episode_num'])): ?> E<?php echo $dl['episode_num'];?><?php endif;?></span><?php endif;?>
          <?php if($dl['file_size']): ?><span class="tag" style="background:var(--primary);color:#000"><?php echo e($dl['file_size']); ?></span><?php endif;?>
          <?php if($dl['audio']): ?><span class="tag tag-default"><?php echo e($dl['audio']); ?></span><?php endif;?>
          <?php if($dl['format']): ?><span class="tag tag-webl"><?php echo e($dl['format']); ?></span><?php endif;?>
          <?php if($dl['quality']): ?><span class="tag tag-fhd"><?php echo e($dl['quality']); ?></span><?php endif;?>
          <?php if($dl['hdr']): ?><span class="tag tag-hdr"><?php echo e($dl['hdr']); ?></span><?php endif;?>
        </div>
      </div>
      <i class="fas fa-chevron-down dl-arrow" style="color:var(--text3);transition:transform .2s;flex-shrink:0"></i>
    </div>
    <div class="dl-body">
      <div class="dl-links">
        <?php foreach(array_filter(array_map('trim', explode("\n", $dl['url']??''))) as $lno=>$link): ?>
        <a href="<?php echo e($link);?>" class="dl-link-btn" target="_blank" rel="nofollow noopener">
          <span><i class="fas fa-download" style="margin-right:8px"></i>Download Link <?php echo $lno+1;?></span>
          <i class="fas fa-external-link-alt" style="font-size:.8rem"></i>
        </a>
        <?php endforeach;?>
      </div>
    </div>
  </div>
  <?php endforeach;?>
</section>
<?php endif;?>

<?php if(!empty($similar)): ?>
<section class="section">
  <div class="section-head"><div class="section-title"><i class="fas fa-th icon"></i> You May Also Like</div></div>
  <div class="cards-grid-lg">
    <?php foreach($similar as $m):
      $st = $m['name'] ?? ($m['title'] ?? '');
      $sp = isset($m['poster_path'])&&$m['poster_path']?tmdbImg($m['poster_path'],'w300'):'/assets/images/no-poster.jpg';
      $sy = substr($m['first_air_date']??($m['release_date']??''),0,4);
      $sr = number_format(floatval($m['vote_average']??0),1);
      $mt = $m['media_type'] ?? 'tv';
      $su = $mt==='movie' ? '/movie.php?id='.$m['id'] : '/series.php?id='.$m['id'];
    ?>
    <div class="card" style="flex:none">
      <div class="card-poster-wrap">
        <a href="<?php echo e($su); ?>"><img src="<?php echo e($sp); ?>" class="card-poster" alt="<?php echo e($st); ?>" loading="lazy"></a>
        <div class="card-overlay"><a href="<?php echo e($su); ?>" class="card-play"><i class="fas fa-play"></i></a></div>
      </div>
      <a href="<?php echo e($su); ?>" class="card-info">
        <div class="card-title"><?php echo e($st); ?></div>
        <div class="card-meta"><span><?php echo e($sy); ?></span><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i><?php echo e($sr); ?></span></div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="modal-overlay" id="trailerModal"><div class="modal-inner"><button class="modal-close" onclick="closeTrailer()"><i class="fas fa-times"></i> Close</button><iframe id="trailerFrame" allowfullscreen frameborder="0"></iframe></div></div>
<style>
@media(min-width:640px){.detail-poster-col{display:block!important}.mob-info-card{display:none!important}}
</style>
<?php include __DIR__.'/includes/footer.php';

} else {
// ── BROWSE VIEW ────────────────────────────────────────────────────

$sort    = $_GET['sort'] ?? 'popular';
$genreId = intval($_GET['genre'] ?? 0);
$page    = max(1, intval($_GET['page'] ?? 1));
$pp      = max(8, intval(setting('items_per_page','20')));
$genreName = '';
$items = [];
$totalPages = 1;

if ($apiKey) {
    if ($genreId) {
        $gl = tmdbRequest('/genre/tv/list');
        foreach ($gl['genres'] ?? [] as $g) { if($g['id']==$genreId){$genreName=$g['name'];break;} }
        $data = tmdbRequest('/discover/tv', ['with_genres'=>$genreId,'sort_by'=>$sort==='top_rated'?'vote_average.desc':'popularity.desc','vote_count.gte'=>50,'page'=>$page]);
    } elseif ($sort === 'top_rated') {
        $data = tmdbRequest('/tv/top_rated', ['page'=>$page]);
    } else {
        $data = tmdbRequest('/tv/popular', ['page'=>$page]);
    }
    $items = array_slice($data['results']??[], 0, $pp);
    $totalPages = min((int)($data['total_pages']??1), 50);
}
$localTagsMap = localTagsMapByTmdb(array_map(fn($m)=>$m['id'] ?? 0, $items), 'tv');

$pageTitle = 'Series - '.setting('site_name','CineHub');
include __DIR__.'/includes/header.php';
?>
<div class="page-header">
  <h1 class="page-title"><?php echo $genreName ? e($genreName).' Series' : 'Web Series & TV Shows'; ?></h1>
  <div class="filter-bar">
    <div class="filter-tabs">
      <a href="/series.php<?php echo $genreId?'?genre='.$genreId:''; ?>" class="ftab <?php echo $sort==='popular'?'active':''; ?>">Popular</a>
      <a href="/series.php?sort=top_rated<?php echo $genreId?'&genre='.$genreId:''; ?>" class="ftab <?php echo $sort==='top_rated'?'active':''; ?>">Top Rated</a>
    </div>
    <?php if($genreName): ?><a href="/series.php" style="font-size:.82rem;color:var(--text3);display:flex;align-items:center;gap:5px"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
  </div>
</div>
<div style="padding:0 20px 48px">
  <?php if(empty($items)): ?>
  <div class="no-results"><i class="fas fa-tv"></i><h3>No series found</h3></div>
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
    <?php endforeach; ?>
  </div>
  <?php if($totalPages>1): $bUrl='/series.php?sort='.urlencode($sort).($genreId?'&genre='.$genreId:''); ?>
  <div class="pagination">
    <?php if($page>1): ?><a href="<?php echo $bUrl;?>&page=<?php echo $page-1;?>" class="ppage"><i class="fas fa-chevron-left"></i></a><?php endif;?>
    <?php for($pg=max(1,$page-2);$pg<=min($totalPages,$page+2);$pg++): ?><a href="<?php echo $bUrl;?>&page=<?php echo $pg;?>" class="ppage <?php echo $pg===$page?'active':'';?>"><?php echo $pg;?></a><?php endfor;?>
    <?php if($page<$totalPages): ?><a href="<?php echo $bUrl;?>&page=<?php echo $page+1;?>" class="ppage"><i class="fas fa-chevron-right"></i></a><?php endif;?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php include __DIR__.'/includes/footer.php';
} ?>
