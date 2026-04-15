<?php
require_once __DIR__.'/includes/core.php';

$tmdbId = intval($_GET['id'] ?? 0);
$slug   = trim($_GET['slug'] ?? '');

if ($slug && !$tmdbId) {
    $row = DB::row("SELECT tmdb_id FROM media WHERE slug=? AND type='movie'", [$slug]);
    if ($row) { header('Location: /movie.php?id='.$row['tmdb_id']); exit; }
    else { header('Location: /movies.php'); exit; }
}
if (!$tmdbId) { header('Location: /movies.php'); exit; }

$data = tmdbRequest('/movie/'.$tmdbId, ['append_to_response'=>'credits,videos,similar,recommendations']);
if (empty($data['id'])) { header('Location: /movies.php'); exit; }

$title      = $data['title'] ?? '';
$tagline    = $data['tagline'] ?? '';
$overview   = $data['overview'] ?? '';
$poster     = $data['poster_path'] ? tmdbImg($data['poster_path'],'w500') : '/assets/images/no-poster.jpg';
$backdrop   = $data['backdrop_path'] ? tmdbImg($data['backdrop_path'],'original') : '';
$year       = substr($data['release_date'] ?? '', 0, 4);
$rating     = floatval($data['vote_average'] ?? 0);
$runtime    = intval($data['runtime'] ?? 0);
$imdbId     = $data['imdb_id'] ?? '';
$genres     = $data['genres'] ?? [];
$castData   = array_slice($data['credits']['cast'] ?? [], 0, 10);
$trailerKey = null;
foreach ($data['videos']['results'] ?? [] as $v) {
    if ($v['type']==='Trailer' && $v['site']==='YouTube') { $trailerKey=$v['key']; break; }
}
$director = '';
foreach ($data['credits']['crew'] ?? [] as $c) {
    if ($c['job']==='Director') { $director=$c['name']; break; }
}
$similar = array_slice($data['recommendations']['results'] ?? ($data['similar']['results'] ?? []), 0, 12);

// Fetch download links from DB if this movie was manually imported
$downloads = [];
$localMedia = null;
try {
    $localMedia = DB::row("SELECT id FROM media WHERE tmdb_id=? AND type='movie'", [$tmdbId]);
    if ($localMedia) {
        $downloads = DB::rows("SELECT * FROM download_links WHERE media_id=? ORDER BY sort_order ASC, id ASC", [$localMedia['id']]);
    }
} catch(Exception $e){}

$servers = [];
try { $servers = DB::rows("SELECT * FROM embed_servers WHERE is_active=1 ORDER BY sort_order ASC"); } catch(Exception $e){}

$pageTitle = $title.' ('.$year.') - '.setting('site_name','CineHub');
include __DIR__.'/includes/header.php';
?>

<!-- ── BACKDROP ────────────────────────────────────────────────── -->
<div style="position:relative;padding-top:var(--header-h);min-height:520px;overflow:hidden">
  <?php if($backdrop): ?>
  <img src="<?php echo e($backdrop); ?>" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover;object-position:center top;filter:blur(2px) brightness(.4);transform:scale(1.05);z-index:0">
  <?php endif; ?>
  <div style="position:absolute;inset:0;background:linear-gradient(0deg,var(--bg) 0%,rgba(13,13,13,.65) 50%,rgba(13,13,13,.35) 100%);z-index:1"></div>

  <div style="position:relative;z-index:2;max-width:1100px;margin:0 auto;padding:36px 20px 52px;display:flex;gap:32px;align-items:flex-start">
    <!-- Poster (desktop) -->
    <div style="flex:0 0 200px;display:none" class="detail-poster-col">
      <div style="border-radius:14px;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.7)">
        <img src="<?php echo e($poster); ?>" alt="<?php echo e($title); ?>" style="width:100%;display:block">
      </div>
    </div>

    <!-- Info -->
    <div style="flex:1;min-width:0">
      <a href="javascript:history.back()" style="display:inline-flex;align-items:center;gap:6px;color:rgba(255,255,255,.5);font-size:.82rem;margin-bottom:18px;transition:color .2s" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,.5)'"><i class="fas fa-chevron-left"></i> Back</a>
      <h1 style="font-family:var(--font-display);font-size:clamp(2rem,5vw,3.8rem);letter-spacing:1.5px;color:#fff;line-height:.95;margin-bottom:8px;text-shadow:0 2px 20px rgba(0,0,0,.5)"><?php echo e($title); ?></h1>
      <?php if($tagline): ?><div style="color:var(--primary);font-size:.9rem;font-style:italic;margin-bottom:14px"><?php echo e($tagline); ?></div><?php endif; ?>
      <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:16px;font-size:.875rem;color:rgba(255,255,255,.75)">
        <?php if($year): ?><span><i class="fas fa-calendar" style="color:var(--primary);margin-right:5px"></i><?php echo e($year); ?></span><?php endif; ?>
        <?php if($rating>0): ?><span style="color:#fbbf24"><i class="fas fa-star" style="margin-right:4px"></i><?php echo number_format($rating,1); ?>/10</span><?php endif; ?>
        <?php if($runtime): ?><span><i class="fas fa-clock" style="color:var(--primary);margin-right:5px"></i><?php echo formatRuntime($runtime); ?></span><?php endif; ?>
        <span style="background:var(--primary);color:#000;padding:2px 10px;border-radius:4px;font-size:.7rem;font-weight:700">MOVIE</span>
      </div>
      <?php if(!empty($genres)): ?>
      <div style="display:flex;flex-wrap:wrap;gap:7px;margin-bottom:18px">
        <?php foreach($genres as $g): ?>
        <a href="/movies.php?genre=<?php echo $g['id']; ?>" style="padding:4px 14px;border-radius:20px;border:1px solid rgba(255,255,255,.2);font-size:.75rem;color:rgba(255,255,255,.6);transition:all .2s;text-decoration:none" onmouseover="this.style.borderColor='var(--primary)';this.style.color='var(--primary)'" onmouseout="this.style.borderColor='rgba(255,255,255,.2)';this.style.color='rgba(255,255,255,.6)'"><?php echo e($g['name']); ?></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
      <?php if($overview): ?><p style="color:rgba(255,255,255,.72);font-size:.925rem;line-height:1.75;margin-bottom:22px;max-width:640px"><?php echo e($overview); ?></p><?php endif; ?>
      <?php if($director || $imdbId): ?>
      <div style="margin-bottom:20px;display:flex;flex-direction:column;gap:7px">
        <?php if($director): ?><div style="font-size:.855rem;color:rgba(255,255,255,.65)"><i class="fas fa-video" style="color:var(--primary);margin-right:6px"></i><span style="color:rgba(255,255,255,.4);margin-right:6px">Director:</span><?php echo e($director); ?></div><?php endif; ?>
        <?php if($imdbId): ?><div style="font-size:.855rem"><i class="fab fa-imdb" style="color:#f5c518;margin-right:6px"></i><a href="https://www.imdb.com/title/<?php echo e($imdbId); ?>" target="_blank" style="color:#f5c518"><?php echo e($imdbId); ?></a></div><?php endif; ?>
      </div>
      <?php endif; ?>
      <?php if(!empty($castData)): ?>
      <div style="margin-bottom:22px">
        <div style="font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;color:rgba(255,255,255,.4);margin-bottom:6px">Stars</div>
        <div style="color:rgba(255,255,255,.7);font-size:.875rem"><?php echo e(implode(', ', array_slice(array_map(fn($c)=>$c['name']??'', $castData),0,6))); ?></div>
      </div>
      <?php endif; ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        <?php if(!empty($servers)): ?><a href="/watch.php?id=<?php echo $tmdbId; ?>&type=movie" class="btn btn-primary btn-lg"><i class="fas fa-play"></i> Watch Online</a><?php endif; ?>
        <?php if($trailerKey): ?><button class="btn btn-lg" onclick="openTrailer('<?php echo e($trailerKey); ?>')" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);color:#fff;backdrop-filter:blur(8px)"><i class="fas fa-film"></i> Trailer</button><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Mobile poster+info card -->
<div style="display:flex;gap:16px;padding:20px;border-bottom:1px solid var(--border);max-width:1100px;margin:0 auto" class="mob-info-card">
  <img src="<?php echo e($poster); ?>" style="width:100px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.5);flex-shrink:0;align-self:flex-start" alt="">
  <div>
    <div style="font-weight:700;font-size:1rem;color:var(--text);margin-bottom:8px"><?php echo e($title); ?></div>
    <?php if(!empty($genres)): ?><div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px"><?php foreach(array_slice($genres,0,3) as $g): ?><span style="background:var(--bg3);border:1px solid var(--border);padding:3px 10px;border-radius:14px;font-size:.72rem;color:var(--text3)"><?php echo e($g['name']); ?></span><?php endforeach; ?></div><?php endif; ?>
    <?php if($overview): ?><p style="color:var(--text3);font-size:.82rem;line-height:1.6"><?php echo e(substr($overview,0,200)); echo strlen($overview)>200?'...':''; ?></p><?php endif; ?>
  </div>
</div>

<!-- Download links (only shows if manually imported) -->
<?php if(!empty($downloads)): ?>
<div style="max-width:1100px;margin:0 auto;padding:24px 20px 0">
  <div style="font-size:1rem;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px"><i class="fas fa-download" style="color:var(--primary)"></i> Download Links</div>
  <?php foreach($downloads as $dl): ?>
  <div class="dl-item" style="margin-bottom:10px">
    <div class="dl-head">
      <div class="dl-head-info">
        <div class="dl-name"><?php echo e($dl['title']); ?></div>
        <div class="dl-tags">
          <?php if($dl['file_size']): ?><span class="tag" style="background:var(--primary);color:#000"><?php echo e($dl['file_size']); ?></span><?php endif; ?>
          <?php if($dl['audio']): ?><span class="tag tag-default"><?php echo e($dl['audio']); ?></span><?php endif; ?>
          <?php if($dl['quality']): ?><span class="tag tag-fhd"><?php echo e($dl['quality']); ?></span><?php endif; ?>
          <?php if($dl['hdr']): ?><span class="tag tag-hdr"><?php echo e($dl['hdr']); ?></span><?php endif; ?>
        </div>
      </div>
      <i class="fas fa-chevron-down dl-arrow" style="color:var(--text3);transition:transform .2s;flex-shrink:0"></i>
    </div>
    <div class="dl-body">
      <?php foreach(array_filter(array_map('trim',explode("\n",$dl['url']??''))) as $lno=>$link): ?>
      <a href="<?php echo e($link); ?>" class="dl-link-btn" target="_blank" rel="nofollow noopener"><span><i class="fas fa-download" style="margin-right:8px"></i>Download Now <?php echo count(array_filter(array_map('trim', explode("\n", $dl['url']??''))))==1 ? '' : "(Link ".($lno+1).")"; ?></span><i class="fas fa-external-link-alt" style="font-size:.8rem"></i></a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Cast -->
<?php if(!empty($castData)): ?>
<section class="section" style="padding-top:20px">
  <div class="section-head"><div class="section-title"><i class="fas fa-users icon"></i> Cast</div></div>
  <div class="cards-row" style="gap:12px;padding:0 20px">
    <?php foreach($castData as $p): $ci=isset($p['profile_path'])&&$p['profile_path']?tmdbImg($p['profile_path'],'w185'):'/assets/images/no-poster.jpg'; ?>
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
<?php if(!empty($similar)): ?>
<section class="section">
  <div class="section-head"><div class="section-title"><i class="fas fa-th icon"></i> You May Also Like</div></div>
  <div class="cards-grid-lg">
    <?php foreach($similar as $m):
      $st=$m['title']??($m['name']??''); $sp=isset($m['poster_path'])&&$m['poster_path']?tmdbImg($m['poster_path'],'w300'):'/assets/images/no-poster.jpg';
      $sy=substr($m['release_date']??($m['first_air_date']??''),0,4); $sr=number_format(floatval($m['vote_average']??0),1);
      $mt=$m['media_type']??'movie'; $su=$mt==='tv'?'/series.php?id='.$m['id']:'/movie.php?id='.$m['id'];
    ?>
    <div class="card">
      <div class="card-poster-wrap">
        <a href="<?php echo e($su);?>"><img src="<?php echo e($sp);?>" class="card-poster" alt="<?php echo e($st);?>" loading="lazy"></a>
        <div class="card-overlay"><a href="<?php echo e($su);?>" class="card-play"><i class="fas fa-play"></i></a></div>
      </div>
      <a href="<?php echo e($su);?>" class="card-info"><div class="card-title"><?php echo e($st);?></div><div class="card-meta"><span><?php echo e($sy);?></span><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i><?php echo e($sr);?></span></div></a>
    </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<div class="modal-overlay" id="trailerModal"><div class="modal-inner"><button class="modal-close" onclick="closeTrailer()"><i class="fas fa-times"></i> Close</button><iframe id="trailerFrame" allowfullscreen allow="autoplay;fullscreen" frameborder="0"></iframe></div></div>
<style>
@media(min-width:640px){.detail-poster-col{display:block!important}.mob-info-card{display:none!important}}
</style>
<?php include __DIR__.'/includes/footer.php'; ?>
