<?php
require_once __DIR__.'/includes/core.php';

$pageTitle = 'Movies - '.setting('site_name','CineHub');
$apiKey    = setting('tmdb_api_key','');
$sort      = isset($_GET['sort']) ? $_GET['sort'] : 'popular';
$genreId   = intval($_GET['genre'] ?? 0);
$page      = max(1, intval($_GET['page'] ?? 1));
$pp        = max(8, intval(setting('items_per_page','20')));

$items = [];
$totalPages = 1;
$genreName = '';

if ($apiKey) {
    if ($genreId) {
        // Genre filter
        $genreList = tmdbRequest('/genre/movie/list');
        foreach ($genreList['results'] ?? $genreList['genres'] ?? [] as $g) {
            if ($g['id'] == $genreId) { $genreName = $g['name']; break; }
        }
        $data = tmdbRequest('/discover/movie', [
            'with_genres' => $genreId,
            'sort_by'     => $sort === 'top_rated' ? 'vote_average.desc' : 'popularity.desc',
            'vote_count.gte' => 50,
            'page'        => $page
        ]);
    } elseif ($sort === 'top_rated') {
        $data = tmdbRequest('/movie/top_rated', ['page' => $page]);
    } elseif ($sort === 'upcoming') {
        $data = tmdbRequest('/movie/upcoming', ['page' => $page]);
    } else {
        $data = tmdbRequest('/movie/popular', ['page' => $page]);
    }
    $all   = $data['results'] ?? [];
    $items = array_slice($all, 0, $pp);
    $totalPages = min((int)($data['total_pages'] ?? 1), 50);
}

include __DIR__.'/includes/header.php';
?>

<div class="page-header">
  <h1 class="page-title"><?php echo $genreName ? e($genreName).' Movies' : 'Movies'; ?></h1>
  <div class="filter-bar">
    <div class="filter-tabs">
      <a href="/movies.php<?php echo $genreId?'?genre='.$genreId:''; ?>" class="ftab <?php echo $sort==='popular'&&!$genreId?'active':''; ?>">Popular</a>
      <a href="/movies.php?sort=top_rated<?php echo $genreId?'&genre='.$genreId:''; ?>" class="ftab <?php echo $sort==='top_rated'?'active':''; ?>">Top Rated</a>
      <a href="/movies.php?sort=upcoming" class="ftab <?php echo $sort==='upcoming'?'active':''; ?>">Upcoming</a>
    </div>
    <?php if($genreName): ?>
    <a href="/movies.php" style="font-size:.82rem;color:var(--text3);display:flex;align-items:center;gap:5px"><i class="fas fa-times"></i> Clear filter</a>
    <?php endif; ?>
  </div>
</div>

<div style="padding:0 20px 48px">
  <?php if(!$apiKey): ?>
  <div class="no-results"><i class="fas fa-key"></i><h3>API Key Required</h3><p><a href="/admin/api-settings.php" style="color:var(--primary)">Configure TMDB API key</a> in admin settings.</p></div>
  <?php elseif(empty($items)): ?>
  <div class="no-results"><i class="fas fa-film"></i><h3>No movies found</h3></div>
  <?php else: ?>
  <div class="cards-grid-lg">
    <?php foreach($items as $m):
      $t = $m['title'] ?? '';
      $p = isset($m['poster_path']) && $m['poster_path'] ? tmdbImg($m['poster_path'],'w342') : '/assets/images/no-poster.jpg';
      $y = substr($m['release_date'] ?? '', 0, 4);
      $r = number_format(floatval($m['vote_average'] ?? 0), 1);
      $u = '/movie.php?id='.$m['id'];
    ?>
    <div class="card" style="flex:none">
      <a href="<?php echo e($u); ?>"><img src="<?php echo e($p); ?>" class="card-poster" alt="<?php echo e($t); ?>" loading="lazy"></a>
      <a href="<?php echo e($u); ?>" class="card-info">
        <div class="card-title"><?php echo e($t); ?></div>
        <div class="card-meta"><span><?php echo e($y); ?></span><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i> <?php echo e($r); ?></span></div>
      </a>
      <div class="card-overlay"><a href="<?php echo e($u); ?>" class="card-play"><i class="fas fa-play"></i></a></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if($totalPages > 1):
    $bUrl = '/movies.php?sort='.urlencode($sort).($genreId?'&genre='.$genreId:'');
  ?>
  <div class="pagination">
    <?php if($page > 1): ?><a href="<?php echo $bUrl; ?>&page=<?php echo $page-1; ?>" class="ppage"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
    <?php for($pg = max(1,$page-2); $pg <= min($totalPages,$page+2); $pg++): ?>
    <a href="<?php echo $bUrl; ?>&page=<?php echo $pg; ?>" class="ppage <?php echo $pg===$page?'active':''; ?>"><?php echo $pg; ?></a>
    <?php endfor; ?>
    <?php if($page < $totalPages): ?><a href="<?php echo $bUrl; ?>&page=<?php echo $page+1; ?>" class="ppage"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php include __DIR__.'/includes/footer.php'; ?>
