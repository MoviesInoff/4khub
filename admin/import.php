<?php
$adminTitle = 'Import from TMDB';
$adminSub   = 'Search TMDB and import movies or series';
require_once __DIR__.'/admin-header.php';

$msg = ''; $msgType = '';
$imported = array();
$searchQuery = '';
$searchType  = 'movie';
$searchResults = array();

// Handle Import Action
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && $_POST['action']==='import') {
    $tmdbId  = intval($_POST['tmdb_id']);
    $mType   = ($_POST['media_type']==='tv') ? 'tv' : 'movie';

    // Check if already imported
    $exists = DB::row("SELECT id FROM media WHERE tmdb_id=? AND type=?", array($tmdbId, $mType));
    if ($exists) {
        $msg = 'Already imported! <a href="/admin/edit-media.php?id='.$exists['id'].'" style="color:var(--c-yellow);text-decoration:underline">Edit it here</a>.';
        $msgType = 'warning';
    } else {
        // Fetch full details from TMDB
        $detail = tmdbRequest('/'.($mType==='tv'?'tv':'movie').'/'.$tmdbId, array('append_to_response'=>'credits,videos,external_ids'));

        if (!empty($detail['id'])) {
            // Extract data
            $title    = isset($detail['title']) ? $detail['title'] : (isset($detail['name'])?$detail['name']:'');
            $tagline  = isset($detail['tagline']) ? $detail['tagline'] : '';
            $overview = isset($detail['overview']) ? $detail['overview'] : '';
            $poster   = isset($detail['poster_path']) ? $detail['poster_path'] : '';
            $backdrop = isset($detail['backdrop_path']) ? $detail['backdrop_path'] : '';
            $rdate    = isset($detail['release_date']) ? $detail['release_date'] : (isset($detail['first_air_date'])?$detail['first_air_date']:'');
            $year     = substr($rdate, 0, 4);
            $runtime  = isset($detail['runtime']) ? intval($detail['runtime']) : null;
            $rating   = isset($detail['vote_average']) ? floatval($detail['vote_average']) : 0;
            $imdbId   = isset($detail['imdb_id']) ? $detail['imdb_id'] : (isset($detail['external_ids']['imdb_id'])?$detail['external_ids']['imdb_id']:'');
            $seasons  = isset($detail['number_of_seasons']) ? intval($detail['number_of_seasons']) : null;
            $eps      = isset($detail['number_of_episodes']) ? intval($detail['number_of_episodes']) : null;

            // Genres
            $genres = isset($detail['genres']) ? $detail['genres'] : array();

            // Cast
            $castRaw = isset($detail['credits']['cast']) ? array_slice($detail['credits']['cast'],0,15) : array();
            $cast = array_map(function($c){ return array('name'=>$c['name'],'character'=>isset($c['character'])?$c['character']:'','profile_path'=>isset($c['profile_path'])?$c['profile_path']:''); }, $castRaw);

            // Director
            $director = '';
            if (isset($detail['credits']['crew'])) {
                foreach ($detail['credits']['crew'] as $crew) {
                    if (isset($crew['job']) && $crew['job']==='Director') { $director=$crew['name']; break; }
                }
            }

            // Trailer
            $trailerKey = null;
            if (isset($detail['videos']['results'])) {
                foreach ($detail['videos']['results'] as $v) {
                    if (isset($v['type'])&&$v['type']==='Trailer'&&isset($v['site'])&&$v['site']==='YouTube') { $trailerKey=$v['key']; break; }
                }
            }

            // Seasons data (for TV)
            $seasonsData = array();
            if ($mType==='tv' && isset($detail['seasons'])) {
                foreach ($detail['seasons'] as $s) {
                    if (isset($s['season_number']) && $s['season_number'] > 0) {
                        $seasonsData[] = array('season_number'=>$s['season_number'],'episode_count'=>isset($s['episode_count'])?$s['episode_count']:0,'name'=>isset($s['name'])?$s['name']:'');
                    }
                }
            }

            $slug = makeSlug($title, $year, $mType);

            $newId = DB::insert(
                "INSERT INTO media (tmdb_id,type,title,slug,tagline,overview,poster_path,backdrop_path,release_date,year,runtime,vote_average,genres,cast_data,director,trailer_key,seasons_count,episodes_count,seasons_data,imdb_id,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'published')",
                array($tmdbId,$mType,$title,$slug,$tagline,$overview,$poster,$backdrop,$rdate,$year,$runtime,$rating,json_encode($genres),json_encode($cast),$director,$trailerKey,$seasons,$eps,json_encode($seasonsData),$imdbId)
            );

            $msg = '<i class="fas fa-check-circle"></i> Successfully imported <strong>'.e($title).'</strong>! <a href="/admin/edit-media.php?id='.$newId.'" style="color:var(--c-yellow);text-decoration:underline">Add download links &rarr;</a>';
            $msgType = 'success';
        } else {
            $msg = 'Failed to fetch details from TMDB. Try again.';
            $msgType = 'error';
        }
    }
}

// Search
if (isset($_GET['q']) && trim($_GET['q'])) {
    $searchQuery = trim($_GET['q']);
    $searchType  = (isset($_GET['type']) && $_GET['type']==='tv') ? 'tv' : 'movie';
    $page = max(1, intval(isset($_GET['page'])?$_GET['page']:1));
    $sData = tmdbRequest('/search/'.($searchType==='tv'?'tv':'movie'), array('query'=>$searchQuery,'page'=>$page));
    $searchResults = isset($sData['results']) ? $sData['results'] : array();
    $totalPages    = min(isset($sData['total_pages'])?$sData['total_pages']:1, 20);
    $totalResults  = isset($sData['total_results']) ? $sData['total_results'] : 0;

    // Check which ones are already imported
    if (!empty($searchResults)) {
        $ids = array_map(function($r){return $r['id'];}, $searchResults);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pArr = array_merge(array($searchType), $ids);
        $existing = DB::rows("SELECT tmdb_id FROM media WHERE type=? AND tmdb_id IN ($placeholders)", $pArr);
        $existingIds = array_column($existing, 'tmdb_id');
        $imported = $existingIds;
    }
}
?>

<?php if($msg): ?>
<div class="aalert aalert-<?php echo $msgType; ?>"><?php echo $msg; ?></div>
<?php endif; ?>

<!-- SEARCH FORM -->
<div class="ac" style="margin-bottom:20px">
  <div class="ac-head"><span><i class="fas fa-cloud-download-alt" style="color:var(--c-primary);margin-right:8px"></i>Search & Import from TMDB</span></div>
  <div style="padding:18px">
    <form method="GET" action="">
      <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <div style="flex:1;min-width:200px">
          <label class="afl">Search Title</label>
          <div class="asearch-wrap"><i class="fas fa-search si"></i><input type="text" name="q" class="afc" placeholder="e.g. Avatar, Breaking Bad, Demon Slayer..." value="<?php echo e($searchQuery); ?>" required></div>
        </div>
        <div style="width:160px">
          <label class="afl">Type</label>
          <select name="type" class="afc">
            <option value="movie" <?php echo $searchType==='movie'?'selected':''; ?>>Movie</option>
            <option value="tv"    <?php echo $searchType==='tv'?'selected':''; ?>>TV / Web Series</option>
          </select>
        </div>
        <button type="submit" class="abtn abtn-primary" style="height:38px"><i class="fas fa-search"></i> Search</button>
      </div>
    </form>
  </div>
</div>

<!-- RESULTS -->
<?php if($searchQuery && empty(setting('tmdb_api_key',''))): ?>
<div class="aalert aalert-warning"><i class="fas fa-exclamation-triangle"></i> TMDB API key not configured. <a href="/admin/api-settings.php" style="color:var(--c-primary);text-decoration:underline">Set it here</a>.</div>
<?php elseif($searchQuery): ?>
<div class="ac">
  <div class="ac-head">
    <span>Results for "<?php echo e($searchQuery); ?>" <?php echo $totalResults>0?'('.$totalResults.' found)':''; ?></span>
    <span style="font-size:.75rem;color:var(--c-text3)"><?php echo ucfirst($searchType); ?></span>
  </div>
  <div style="padding:14px">
    <?php if(empty($searchResults)): ?>
    <div style="text-align:center;padding:40px;color:var(--c-text3)"><i class="fas fa-search" style="font-size:2rem;margin-bottom:12px;display:block"></i>No results found.</div>
    <?php else: ?>
    <form method="POST">
      <input type="hidden" name="action" value="import">
      <input type="hidden" name="media_type" value="<?php echo e($searchType); ?>">
      <?php foreach($searchResults as $item):
        $isTV    = ($searchType==='tv');
        $iTitle  = $isTV ? (isset($item['name'])?$item['name']:'') : (isset($item['title'])?$item['title']:'');
        $iYear   = substr($isTV ? (isset($item['first_air_date'])?$item['first_air_date']:'') : (isset($item['release_date'])?$item['release_date']:''), 0, 4);
        $iPoster = isset($item['poster_path'])&&$item['poster_path'] ? tmdbImg($item['poster_path'],'w92') : '/assets/images/no-poster.jpg';
        $iRating = number_format(floatval(isset($item['vote_average'])?$item['vote_average']:0),1);
        $iOverview = isset($item['overview']) ? substr($item['overview'],0,120) : '';
        $alreadyIn = in_array($item['id'], $imported);
      ?>
      <div class="import-result <?php echo $alreadyIn?'imported':''; ?>">
        <img src="<?php echo e($iPoster); ?>" class="ir-poster" alt="" loading="lazy">
        <div class="ir-info">
          <div class="ir-title"><?php echo e($iTitle); ?> <?php if($iYear): ?><span style="color:var(--c-text3);font-weight:400">(<?php echo e($iYear); ?>)</span><?php endif; ?></div>
          <div class="ir-meta">
            <?php if($isTV): ?><span style="color:var(--c-blue);font-weight:600;margin-right:8px">Series</span><?php endif; ?>
            <?php if($iRating>0): ?><span style="color:var(--c-yellow);margin-right:8px"><i class="fas fa-star" style="font-size:.7rem"></i> <?php echo $iRating; ?></span><?php endif; ?>
            <?php if($iOverview): ?><span><?php echo e($iOverview); ?>...</span><?php endif; ?>
          </div>
        </div>
        <div class="ir-actions">
          <?php if($alreadyIn): ?>
          <span class="abtn" style="background:rgba(34,197,94,.1);color:var(--c-green);border-color:rgba(34,197,94,.3)"><i class="fas fa-check"></i> Imported</span>
          <?php else: ?>
          <button type="submit" name="tmdb_id" value="<?php echo $item['id']; ?>" class="abtn abtn-primary"><i class="fas fa-download"></i> Import</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </form>

    <!-- Pagination -->
    <?php if(isset($totalPages) && $totalPages>1): $bUrl='?q='.urlencode($searchQuery).'&type='.$searchType; ?>
    <div class="apag" style="margin-top:16px">
      <?php if($page>1): ?><a href="<?php echo $bUrl.'&page='.($page-1); ?>"><i class="fas fa-chevron-left"></i></a><?php endif; ?>
      <?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
      <a href="<?php echo $bUrl.'&page='.$p; ?>" <?php echo $p===$page?'class="active"':''; ?>><?php echo $p; ?></a>
      <?php endfor; ?>
      <?php if($page<$totalPages): ?><a href="<?php echo $bUrl.'&page='.($page+1); ?>"><i class="fas fa-chevron-right"></i></a><?php endif; ?>
    </div>
    <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
<?php else: ?>
<!-- Tips when no search -->
<div class="ac">
  <div class="ac-head"><span>How to Import</span></div>
  <div style="padding:20px;display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px">
    <?php foreach([
      ['1','Search','Type a movie or series name above and select Movie or TV Series'],
      ['2','Import','Click the Import button next to any title to fetch all details from TMDB'],
      ['3','Edit','After import, click "Add download links" to add HD/4K download links and tags'],
    ] as $s): ?>
    <div style="display:flex;align-items:flex-start;gap:12px;padding:14px;background:var(--c-bg3);border-radius:10px">
      <div style="width:28px;height:28px;border-radius:50%;background:var(--c-primary);color:#000;font-weight:900;font-size:.85rem;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?php echo $s[0]; ?></div>
      <div><div style="font-weight:700;color:var(--c-text);margin-bottom:4px"><?php echo $s[1]; ?></div><div style="font-size:.8rem;color:var(--c-text3)"><?php echo $s[2]; ?></div></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php include __DIR__.'/admin-footer.php'; ?>
