<?php
$adminTitle = 'Browse Media';
$adminSub   = 'Explore TMDB movie & TV database';
require_once __DIR__.'/admin-header.php';

$apiKey  = setting('tmdb_api_key','');
$tab     = $_GET['tab']    ?? 'movies';   // movies | series
$sort    = $_GET['sort']   ?? 'popular';
$genreId = intval($_GET['genre'] ?? 0);
$search  = trim($_GET['s'] ?? '');
$page    = max(1, intval($_GET['page'] ?? 1));

$items      = [];
$totalPages = 1;
$genres     = [];

if ($apiKey) {
    // Load genre list for filter
    if ($tab === 'series') {
        $gl = tmdbRequest('/genre/tv/list');
    } else {
        $gl = tmdbRequest('/genre/movie/list');
    }
    $genres = $gl['genres'] ?? [];

    // Fetch content
    if ($search) {
        $sd = tmdbRequest('/search/'.($tab==='series'?'tv':'movie'), ['query'=>$search,'page'=>$page]);
        $items = $sd['results'] ?? [];
        $totalPages = min((int)($sd['total_pages']??1), 20);
    } elseif ($genreId) {
        $sd = tmdbRequest('/discover/'.($tab==='series'?'tv':'movie'), [
            'with_genres' => $genreId,
            'sort_by'     => $sort==='top_rated' ? 'vote_average.desc' : 'popularity.desc',
            'vote_count.gte' => 20,
            'page' => $page
        ]);
        $items = $sd['results'] ?? [];
        $totalPages = min((int)($sd['total_pages']??1), 50);
    } else {
        $endpoint = $tab === 'series'
            ? ($sort==='top_rated' ? '/tv/top_rated' : '/tv/popular')
            : ($sort==='top_rated' ? '/movie/top_rated' : '/movie/popular');
        $sd = tmdbRequest($endpoint, ['page'=>$page]);
        $items = $sd['results'] ?? [];
        $totalPages = min((int)($sd['total_pages']??1), 100);
    }
}

// Check which items are already imported
$importedIds = [];
if (!empty($items)) {
    $type = $tab === 'series' ? 'tv' : 'movie';
    $ids  = array_column($items, 'id');
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $rows = DB::rows("SELECT tmdb_id FROM media WHERE type=? AND tmdb_id IN ($placeholders)",
                         array_merge([$type], $ids));
        $importedIds = array_column($rows, 'tmdb_id');
    }
}

$bUrl = '/admin/media.php?tab='.urlencode($tab).'&sort='.urlencode($sort).'&genre='.$genreId.'&s='.urlencode($search);
?>

<!-- Tabs -->
<div style="display:flex;gap:8px;margin-bottom:18px;border-bottom:1px solid var(--c-border);padding-bottom:14px">
  <a href="/admin/media.php?tab=movies" class="abtn <?php echo $tab==='movies'?'abtn-primary':''; ?>"><i class="fas fa-film"></i> Movies</a>
  <a href="/admin/media.php?tab=series" class="abtn <?php echo $tab==='series'?'abtn-primary':''; ?>"><i class="fas fa-tv"></i> TV Series</a>
  <a href="/admin/import.php" class="abtn" style="margin-left:auto"><i class="fas fa-cloud-download-alt"></i> Import by TMDB ID</a>
</div>

<!-- Filters -->
<form method="GET" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:18px">
  <input type="hidden" name="tab" value="<?php echo e($tab); ?>">
  <div class="asearch-wrap" style="flex:1;min-width:160px">
    <i class="fas fa-search si"></i>
    <input type="text" name="s" class="afc" placeholder="Search <?php echo $tab==='series'?'series...':'movies...'; ?>" value="<?php echo e($search); ?>">
  </div>
  <select name="sort" class="afc" style="width:auto">
    <option value="popular"   <?php echo $sort==='popular'  ?'selected':''; ?>>Popular</option>
    <option value="top_rated" <?php echo $sort==='top_rated'?'selected':''; ?>>Top Rated</option>
  </select>
  <select name="genre" class="afc" style="width:auto">
    <option value="0">All Genres</option>
    <?php foreach($genres as $g): ?>
    <option value="<?php echo $g['id']; ?>" <?php echo $g['id']==$genreId?'selected':''; ?>><?php echo e($g['name']); ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="abtn abtn-primary">Filter</button>
  <?php if($search||$genreId): ?><a href="/admin/media.php?tab=<?php echo $tab; ?>" class="abtn">Reset</a><?php endif; ?>
</form>

<?php if(!$apiKey): ?>
<div class="aalert aalert-warning"><i class="fas fa-exclamation-triangle"></i> TMDB API key not configured. <a href="/admin/api-settings.php" style="color:var(--c-primary)">Set API key</a></div>
<?php elseif(empty($items)): ?>
<div style="text-align:center;padding:48px 20px;color:var(--c-text3)">
  <i class="fas fa-search" style="font-size:2rem;margin-bottom:12px;display:block"></i>
  <div>No results found.</div>
</div>
<?php else: ?>

<div class="ac">
  <div class="ac-head">
    <span><?php echo $tab==='series'?'TV Series':'Movies'; ?> <span style="font-weight:400;color:var(--c-text3)">— Page <?php echo $page; ?> of <?php echo $totalPages; ?></span></span>
  </div>
  <div style="overflow-x:auto">
  <table class="atable">
    <thead>
      <tr>
        <th style="width:50px">Poster</th>
        <th>Title</th>
        <th>Year</th>
        <th>Rating</th>
        <th>Genres</th>
        <th>TMDB ID</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($items as $m):
      $isTV    = $tab === 'series';
      $title   = $m['title'] ?? ($m['name'] ?? '');
      $year    = substr($m['release_date'] ?? ($m['first_air_date'] ?? ''), 0, 4);
      $rating  = number_format(floatval($m['vote_average'] ?? 0), 1);
      $poster  = isset($m['poster_path']) && $m['poster_path'] ? 'https://image.tmdb.org/t/p/w92'.$m['poster_path'] : '/assets/images/no-poster.jpg';
      $viewUrl = $isTV ? '/series.php?id='.$m['id'] : '/movie.php?id='.$m['id'];
      $mGenres = [];
      foreach($genres as $g) { if(in_array($g['id'], $m['genre_ids']??[])) $mGenres[] = $g['name']; }
      $isImported = in_array($m['id'], $importedIds);
      // Get local DB id if imported
      $localId = null;
      if($isImported){
        $lr = DB::row("SELECT id FROM media WHERE tmdb_id=? AND type=?", [$m['id'], $isTV?'tv':'movie']);
        if($lr) $localId = $lr['id'];
      }
    ?>
    <tr>
      <td><img src="<?php echo e($poster); ?>" style="width:38px;height:57px;object-fit:cover;border-radius:6px;display:block" loading="lazy"></td>
      <td>
        <strong style="color:var(--c-text);font-size:.875rem"><?php echo e($title); ?></strong>
        <?php if($isImported): ?>
        <span style="display:inline-block;margin-left:6px;background:rgba(34,197,94,.12);color:#22c55e;padding:1px 6px;border-radius:4px;font-size:.65rem;font-weight:700">IMPORTED</span>
        <?php endif; ?>
      </td>
      <td style="font-size:.85rem;color:var(--c-text2)"><?php echo e($year); ?></td>
      <td><span style="color:#fbbf24;font-size:.85rem"><i class="fas fa-star" style="font-size:.7rem"></i> <?php echo $rating; ?></span></td>
      <td>
        <div style="display:flex;flex-wrap:wrap;gap:3px">
          <?php foreach(array_slice($mGenres,0,2) as $gn): ?>
          <span style="background:var(--c-bg4);color:var(--c-text3);padding:2px 7px;border-radius:4px;font-size:.68rem"><?php echo e($gn); ?></span>
          <?php endforeach; ?>
        </div>
      </td>
      <td style="font-size:.78rem;color:var(--c-text4);font-family:monospace"><?php echo $m['id']; ?></td>
      <td>
        <div style="display:flex;gap:5px;align-items:center">
          <a href="<?php echo e($viewUrl); ?>" target="_blank" class="abtn abtn-sm" title="View on site"><i class="fas fa-eye"></i></a>
          <?php if($isImported && $localId): ?>
          <a href="/admin/edit-media.php?id=<?php echo $localId; ?>" class="abtn abtn-sm abtn-primary" title="Edit & Download Links"><i class="fas fa-edit"></i> Edit</a>
          <?php else: ?>
          <a href="/admin/import.php?prefill=<?php echo $m['id']; ?>&type=<?php echo $isTV?'tv':'movie'; ?>" class="abtn abtn-sm" title="Import this"><i class="fas fa-download"></i> Import</a>
          <?php endif; ?>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>

  <!-- Pagination -->
  <?php if($totalPages > 1): ?>
  <div class="apag">
    <?php if($page>1): ?><a href="<?php echo $bUrl.'&page='.($page-1); ?>"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
    <?php for($pg=max(1,$page-2); $pg<=min($totalPages,$page+2); $pg++): ?>
    <a href="<?php echo $bUrl.'&page='.$pg; ?>" <?php echo $pg===$page?'class="active"':''; ?>><?php echo $pg; ?></a>
    <?php endfor; ?>
    <?php if($page<$totalPages): ?><a href="<?php echo $bUrl.'&page='.($page+1); ?>"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php include __DIR__.'/admin-footer.php'; ?>
