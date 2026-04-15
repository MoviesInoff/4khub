<?php
require_once __DIR__.'/includes/core.php';
$pageTitle = 'Genres - '.setting('site_name','CineHub');
$apiKey = setting('tmdb_api_key','');
$mGenres = $tGenres = [];

if ($apiKey) {
    $mg = tmdbRequest('/genre/movie/list');
    $mGenres = $mg['genres'] ?? [];
    $tg = tmdbRequest('/genre/tv/list');
    $tGenres = $tg['genres'] ?? [];
}

$colors = ['#f97316','#3b82f6','#22c55e','#ef4444','#a855f7','#ec4899','#06b6d4','#eab308','#14b8a6','#f43f5e','#8b5cf6','#10b981'];
include __DIR__.'/includes/header.php';
?>
<div class="page-header"><h1 class="page-title">Browse by Genre</h1></div>
<div style="padding:0 20px 48px;max-width:1100px;margin:0 auto">

<?php if(!$apiKey): ?>
<div class="no-results"><i class="fas fa-key"></i><h3>API Key Required</h3><p><a href="/admin/api-settings.php" style="color:var(--primary)">Configure TMDB API key</a></p></div>
<?php endif; ?>

<?php if(!empty($mGenres)): ?>
<h2 class="section-title" style="margin-bottom:18px"><i class="fas fa-film icon"></i> Movie Genres</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;margin-bottom:40px">
  <?php foreach($mGenres as $i => $g): $c = $colors[$i % count($colors)]; ?>
  <a href="/movies.php?genre=<?php echo $g['id']; ?>"
     style="display:flex;align-items:center;justify-content:center;height:68px;border-radius:12px;font-weight:700;font-size:.9rem;letter-spacing:.3px;color:#fff;background:linear-gradient(135deg,<?php echo $c; ?> 0%,rgba(13,13,13,.85) 100%);border:1px solid rgba(255,255,255,.08);transition:transform .2s,box-shadow .2s;text-decoration:none"
     onmouseover="this.style.transform='scale(1.04)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.4)'"
     onmouseout="this.style.transform='';this.style.boxShadow=''">
    <?php echo e($g['name']); ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php if(!empty($tGenres)): ?>
<h2 class="section-title" style="margin-bottom:18px"><i class="fas fa-tv icon"></i> Series Genres</h2>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px">
  <?php foreach($tGenres as $i => $g): $c = $colors[($i+5) % count($colors)]; ?>
  <a href="/series.php?genre=<?php echo $g['id']; ?>"
     style="display:flex;align-items:center;justify-content:center;height:68px;border-radius:12px;font-weight:700;font-size:.9rem;letter-spacing:.3px;color:#fff;background:linear-gradient(135deg,<?php echo $c; ?> 0%,rgba(13,13,13,.85) 100%);border:1px solid rgba(255,255,255,.08);transition:transform .2s,box-shadow .2s;text-decoration:none"
     onmouseover="this.style.transform='scale(1.04)';this.style.boxShadow='0 8px 24px rgba(0,0,0,.4)'"
     onmouseout="this.style.transform='';this.style.boxShadow=''">
    <?php echo e($g['name']); ?>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include __DIR__.'/includes/footer.php'; ?>
