<?php
require_once __DIR__.'/includes/core.php';

$siteName = setting('site_name','CineHub');
$pageTitle = $siteName.' - Your Entertainment Hub';
$apiKey   = setting('tmdb_api_key','');
$showHeroSlider = setting('show_hero_slider', '1') !== '0';
$telegramUrl = trim(setting('social_telegram', ''));
$perPage  = max(8, intval(setting('homepage_count','20')));
$page     = max(1, intval($_GET['page'] ?? 1));

if (!$apiKey) {
    include __DIR__.'/includes/header.php'; ?>
    <div style="padding-top:var(--header-h);min-height:100vh;display:flex;align-items:center;justify-content:center">
      <div style="text-align:center;padding:40px 20px;max-width:500px">
        <div style="font-size:3rem;margin-bottom:16px">🎬</div>
        <h1 style="font-size:2rem;font-weight:900;color:var(--text);margin-bottom:12px">Welcome to <?php echo e($siteName); ?></h1>
        <p style="color:var(--text3);margin-bottom:28px">Add your TMDB API key to start loading content automatically.</p>
        <a href="/admin/api-settings.php" class="btn btn-primary btn-lg">Configure API Key</a>
      </div>
    </div>
    <?php include __DIR__.'/includes/footer.php'; exit;
}

// Hero: trending movies this week
$trendData = tmdbRequest('/trending/movie/week', ['page' => 1]);
$heroItems = array_slice($trendData['results'] ?? [], 0, 6);

// Latest Releases: mix of movies + tv, sorted by popularity (now_playing + on_the_air)
// Fetch from multiple endpoints and merge
$moviesData = tmdbRequest('/movie/now_playing', ['page' => $page]);
$tvData     = tmdbRequest('/tv/on_the_air',     ['page' => $page]);

$movieItems = array_map(function($m){ $m['_type']='movie'; return $m; }, $moviesData['results'] ?? []);
$tvItems    = array_map(function($m){ $m['_type']='tv'; return $m; }, $tvData['results'] ?? []);

// Interleave movies and tv
$combined = [];
$mi = 0; $ti = 0;
$mc = count($movieItems); $tc = count($tvItems);
while ($mi < $mc || $ti < $tc) {
    if ($mi < $mc) $combined[] = $movieItems[$mi++];
    if ($ti < $tc) $combined[] = $tvItems[$ti++];
}
$items = array_slice($combined, 0, $perPage);
$totalPages = min(max((int)($moviesData['total_pages']??1),(int)($tvData['total_pages']??1)), 20);

$movieTagMap = localTagsMapByTmdb(array_map(fn($m) => $m['id'], array_filter($items, fn($m) => ($m['_type'] ?? '') === 'movie')), 'movie');
$tvTagMap = localTagsMapByTmdb(array_map(fn($m) => $m['id'], array_filter($items, fn($m) => ($m['_type'] ?? '') === 'tv')), 'tv');

include __DIR__.'/includes/header.php';
?>

<!-- ── HERO SLIDER ─────────────────────────────────────────────────
     Wrapped in a block container with overflow:hidden + padding-bottom
     so the dots don't bleed into the section below
-->
<?php if ($showHeroSlider): ?>
<div class="hero-slider-wrap" style="padding-top:var(--header-h);position:relative;overflow:hidden;padding-bottom:0">
  <div class="hero-slider" style="position:relative">
    <?php foreach($heroItems as $i => $item):
      $title    = $item['title'] ?? '';
      $overview = $item['overview'] ?? '';
      $bdUrl    = isset($item['backdrop_path']) && $item['backdrop_path'] ? tmdbImg($item['backdrop_path'],'w1280') : '';
      $year     = substr($item['release_date'] ?? '', 0, 4);
      $rating   = $item['vote_average'] ?? 0;
      $tmdbId   = $item['id'] ?? '';
    ?>
    <div class="hero-slide <?php echo $i===0?'active':''; ?>"
         style="position:<?php echo $i===0?'relative':'absolute'; ?>;<?php echo $i>0?'inset:0;opacity:0;transition:opacity .8s ease;':''; ?>min-height:clamp(340px,60vw,560px)">
      <?php if($bdUrl): ?>
      <div style="position:absolute;inset:0;z-index:0">
        <img src="<?php echo e($bdUrl); ?>" alt="" style="width:100%;height:100%;object-fit:cover;object-position:center top;filter:brightness(.62)">
        <div style="position:absolute;inset:0;background:linear-gradient(90deg,rgba(13,13,13,.68) 0%,rgba(13,13,13,.28) 55%,rgba(13,13,13,.04) 100%),linear-gradient(0deg,var(--bg) 0%,transparent 34%)"></div>
      </div>
      <?php else: ?><div style="position:absolute;inset:0;background:var(--bg2)"></div><?php endif; ?>
      <div style="position:relative;z-index:1;max-width:1100px;margin:0 auto;padding:clamp(40px,8vw,90px) 20px clamp(50px,9vw,100px)">
        <div style="max-width:580px">
          <h1 style="font-family:var(--font-display);font-size:clamp(2.2rem,6vw,4.8rem);letter-spacing:1px;color:#fff;line-height:.93;margin-bottom:14px;text-shadow:0 2px 20px rgba(0,0,0,.4)"><?php echo e($title); ?></h1>
          <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:18px;font-size:.875rem;color:rgba(255,255,255,.8)">
            <?php if($year): ?><span><?php echo e($year); ?></span><?php endif; ?>
            <?php if($rating > 0): ?><span style="display:flex;align-items:center;gap:4px;color:#fbbf24"><i class="fas fa-star" style="font-size:.75rem"></i><?php echo number_format(floatval($rating),1); ?>/10</span><?php endif; ?>
            <span style="background:var(--primary);color:#000;padding:2px 10px;border-radius:4px;font-size:.7rem;font-weight:700">MOVIE</span>
          </div>
          <?php if($overview): ?>
          <p style="color:rgba(255,255,255,.7);font-size:.9rem;line-height:1.7;margin-bottom:28px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden"><?php echo e($overview); ?></p>
          <?php endif; ?>
          <div style="display:flex;gap:12px;flex-wrap:wrap">
            <a href="/watch.php?id=<?php echo $tmdbId; ?>&type=movie" class="btn btn-primary btn-lg"><i class="fas fa-play"></i> Watch Now</a>
            <a href="/movie.php?id=<?php echo $tmdbId; ?>" class="btn btn-lg" style="background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.3);color:#fff;backdrop-filter:blur(8px)"><i class="fas fa-info-circle"></i> Details</a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Dots: overlaid at bottom of hero, no gap -->
  <?php if(count($heroItems) > 1): ?>
  <div style="position:absolute;bottom:12px;left:0;right:0;z-index:5;display:flex;justify-content:center;align-items:center;gap:6px;pointer-events:none">
    <?php foreach($heroItems as $i => $_): ?>
    <div class="hdot <?php echo $i===0?'active':''; ?>" data-idx="<?php echo $i; ?>"
         style="width:<?php echo $i===0?'24':'8'; ?>px;height:8px;border-radius:4px;background:<?php echo $i===0?'var(--primary)':'rgba(255,255,255,.3)'; ?>;cursor:pointer;transition:all .3s;pointer-events:all"></div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($telegramUrl): ?>
<div style="padding:12px 20px 0">
  <div style="max-width:1100px;margin:0 auto">
    <a href="<?php echo e($telegramUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline" style="width:100%;justify-content:center;gap:10px;border-color:rgba(34,158,217,.55);color:#8fd7ff;background:rgba(34,158,217,.08)">
      <i class="fab fa-telegram-plane"></i> Join Telegram
    </a>
  </div>
</div>
<?php endif; ?>

<!-- ── LATEST RELEASES ──────────────────────────────────────────── -->
<section class="section" style="<?php echo $showHeroSlider ? '' : 'padding-top:calc(var(--header-h) + 20px);'; ?>">
  <div class="section-head">
    <div class="section-title"><i class="fas fa-fire icon"></i> Latest Releases</div>
    <span style="font-size:.8rem;color:var(--text3)">Page <?php echo $page; ?> / <?php echo $totalPages; ?></span>
  </div>

  <?php if(empty($items)): ?>
  <div class="no-results"><i class="fas fa-film"></i><h3>No content found</h3><p>Check your TMDB API key in <a href="/admin/api-settings.php" style="color:var(--primary)">settings</a>.</p></div>
  <?php else: ?>
  <div class="cards-grid-lg">
    <?php foreach($items as $m):
      $isTV = $m['_type'] === 'tv';
      $t    = $isTV ? ($m['name'] ?? '') : ($m['title'] ?? '');
      $p    = isset($m['poster_path']) && $m['poster_path'] ? tmdbImg($m['poster_path'],'w342') : '/assets/images/no-poster.jpg';
      $y    = substr($isTV ? ($m['first_air_date']??'') : ($m['release_date']??''), 0, 4);
      $r    = number_format(floatval($m['vote_average'] ?? 0), 1);
      $u    = $isTV ? '/series.php?id='.$m['id'] : '/movie.php?id='.$m['id'];
    ?>
    <div class="card" style="flex:none">
      <div class="card-poster-wrap">
        <a href="<?php echo e($u); ?>"><img src="<?php echo e($p); ?>" class="card-poster" alt="<?php echo e($t); ?>" loading="lazy"></a>
        <?php if($isTV): ?><span class="tag tag-tv card-tv-tag">TV</span><?php endif; ?>
        <?php $localTags = $isTV ? ($tvTagMap[$m['id']] ?? []) : ($movieTagMap[$m['id']] ?? []); if(!empty($localTags)): ?>
        <div class="card-tags"><?php foreach(array_slice($localTags,0,3) as $ltag): ?><span class="tag <?php echo tagClass($ltag); ?>"><?php echo e(strtoupper($ltag));?></span><?php endforeach;?></div>
        <?php endif;?>
        <div class="card-overlay"><a href="<?php echo e($u); ?>" class="card-play"><i class="fas fa-play"></i></a></div>
      </div>
      <a href="<?php echo e($u); ?>" class="card-info">
        <div class="card-title"><?php echo e($t); ?></div>
        <div class="card-meta">
          <span><?php echo e($y); ?><?php echo $isTV?' &middot; TV':''; ?></span>
          <span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i> <?php echo e($r); ?></span>
        </div>
      </a>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if($totalPages > 1): ?>
  <div class="pagination">
    <?php if($page > 1): ?><a href="/?page=<?php echo $page-1; ?>" class="ppage"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
    <?php for($pg = max(1,$page-2); $pg <= min($totalPages,$page+2); $pg++): ?>
    <a href="/?page=<?php echo $pg; ?>" class="ppage <?php echo $pg===$page?'active':''; ?>"><?php echo $pg; ?></a>
    <?php endfor; ?>
    <?php if($page < $totalPages): ?><a href="/?page=<?php echo $page+1; ?>" class="ppage"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</section>

<script>
(function(){
  var slides=document.querySelectorAll('.hero-slide'),dots=document.querySelectorAll('.hdot'),cur=0,total=slides.length;
  if(total<=1) return;
  function show(n){
    slides[cur].style.opacity='0';slides[cur].style.position='absolute';slides[cur].classList.remove('active');
    dots[cur].style.width='8px';dots[cur].style.background='rgba(255,255,255,.25)';
    cur=(n+total)%total;
    slides[cur].style.opacity='1';slides[cur].style.position='relative';slides[cur].classList.add('active');
    dots[cur].style.width='24px';dots[cur].style.background='var(--primary)';
  }
  dots.forEach(function(d,i){d.addEventListener('click',function(){show(i);clearInterval(t);t=setInterval(function(){show(cur+1);},6000);});});
  var t=setInterval(function(){show(cur+1);},6000);
})();
</script>

<?php include __DIR__.'/includes/footer.php'; ?>
