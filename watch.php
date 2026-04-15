<?php
require_once __DIR__.'/includes/core.php';
$tmdbId = intval($_GET['id'] ?? 0);
$type   = (isset($_GET['type']) && $_GET['type']==='tv') ? 'tv' : 'movie';
$season = max(1, intval($_GET['s'] ?? 1));
$ep     = max(1, intval($_GET['e'] ?? 1));
$srvId  = intval($_GET['server'] ?? 0);
if(!$tmdbId){ header('Location: /index.php'); exit; }
$telegramUrl = trim(setting('social_telegram', ''));

function fetchImdbIdFromTmdb($tmdbId, $type) {
    if (!$tmdbId) return '';
    if ($type === 'movie') {
        $movieDetail = tmdbRequest('/movie/'.$tmdbId);
        return trim($movieDetail['imdb_id'] ?? '');
    }
    $external = tmdbRequest('/tv/'.$tmdbId.'/external_ids');
    return trim($external['imdb_id'] ?? '');
}

// Try local DB first
$media = DB::row("SELECT * FROM media WHERE tmdb_id=? AND type=?", [$tmdbId, $type]);

// Fetch full TMDB details
$detail = tmdbRequest('/'.($type==='tv'?'tv':'movie').'/'.$tmdbId, [
    'append_to_response' => 'credits,videos,similar,recommendations'
]);

if ($media) {
    $title       = $media['title'];
    $overview    = $media['overview'] ?? '';
    $year        = $media['year'];
    $rating      = number_format(floatval($media['vote_average']??0), 1);
    $poster      = $media['poster_path'] ? tmdbImg($media['poster_path'],'w500') : '/assets/images/no-poster.jpg';
    $backdrop    = $media['backdrop_path'] ? tmdbImg($media['backdrop_path'],'w1280') : '';
    $genres      = jd($media['genres'] ?? '[]');
    $seasonsData = jd($media['seasons_data'] ?? '[]');
    $runtime     = intval($media['runtime'] ?? 0);
    $imdbId      = $media['imdb_id'] ?? '';
    $customVideoUrl = trim($media['custom_video_url'] ?? '');
} else {
    $title       = $detail['title'] ?? ($detail['name'] ?? '');
    $overview    = $detail['overview'] ?? '';
    $year        = substr($detail['release_date'] ?? ($detail['first_air_date']??''), 0, 4);
    $rating      = number_format(floatval($detail['vote_average']??0), 1);
    $poster      = isset($detail['poster_path'])&&$detail['poster_path'] ? tmdbImg($detail['poster_path'],'w500') : '/assets/images/no-poster.jpg';
    $backdrop    = isset($detail['backdrop_path'])&&$detail['backdrop_path'] ? tmdbImg($detail['backdrop_path'],'w1280') : '';
    $genres      = $detail['genres'] ?? [];
    $seasonsData = array_values(array_filter($detail['seasons']??[], fn($s)=>$s['season_number']>0));
    $runtime     = intval($detail['runtime'] ?? 0);
    $imdbId      = trim($detail['imdb_id'] ?? '');
    $customVideoUrl = '';
}

if (!$imdbId) {
    $imdbId = fetchImdbIdFromTmdb($tmdbId, $type);
}

// Episode details for current season (fetch from TMDB)
$episodeList = [];
if ($type === 'tv') {
    $seasonDetail = tmdbRequest('/tv/'.$tmdbId.'/season/'.$season);
    $episodeList  = $seasonDetail['episodes'] ?? [];
}

// Download links
$downloads = [];
if ($media) {
    $downloads = DB::rows(
        "SELECT * FROM download_links WHERE media_id=? ORDER BY sort_order ASC, id ASC",
        [$media['id']]
    );
}

// Servers from DB
$servers = [];
try { $servers = DB::rows("SELECT * FROM embed_servers WHERE is_active=1 ORDER BY sort_order ASC"); } catch(Exception $e){}
if(empty($servers)){ header('Location: /index.php'); exit; }

// If custom video URL is set, prepend a special "Alpha" server
$alphaServer = null;
if ($customVideoUrl) {
    $alphaServer = [
        'id'          => 0,
        'name'        => 'Alpha',
        'is_alpha'    => true,
        'movie_url'   => $customVideoUrl,
        'tv_url'      => $customVideoUrl,
        'use_imdb_id' => 0,
    ];
    // Prepend alpha to server list for display
    array_unshift($servers, $alphaServer);
}

// Determine active server
// Default: alpha (id=0) if exists, else first
$activeSrv = $servers[0];
foreach($servers as $s){ if($s['id']==$srvId){ $activeSrv=$s; break; } }

// Build embed URL
$embedUrl = '';
if (!empty($activeSrv['is_alpha']) && $customVideoUrl) {
    // Alpha server: play custom video directly (no sandbox)
    $embedUrl = $customVideoUrl;
} elseif ($type==='tv') {
    $idToUse = ($activeSrv['use_imdb_id']??0) ? ($imdbId ?: $tmdbId) : $tmdbId;
    $template = $activeSrv['tv_url'] ?? '';
    if (($activeSrv['use_imdb_id'] ?? 0) && $imdbId) {
        $template = preg_replace('#/?\{season\}/?\{episode\}#', '', $template);
        $template = str_replace(['{season}','{episode}'], '', $template);
    }
    $embedUrl = str_replace(['{tmdb_id}','{imdb_id}','{season}','{episode}'], [$tmdbId, $idToUse, $season, $ep], $template);
    $embedUrl = preg_replace('#(?<!:)/{2,}#', '/', $embedUrl);
} else {
    $idToUse = ($activeSrv['use_imdb_id']??0) ? ($imdbId ?: $tmdbId) : $tmdbId;
    $embedUrl = str_replace(['{tmdb_id}','{imdb_id}'], [$tmdbId, $idToUse], $activeSrv['movie_url']??'');
}

// Recommendations
$similar = array_slice(
    $detail['recommendations']['results'] ?? ($detail['similar']['results'] ?? []),
    0, 12
);

// Cast
$castData = array_slice($detail['credits']['cast'] ?? [], 0, 10);

// Trailer
$trailerKey = null;
foreach($detail['videos']['results'] ?? [] as $v) {
    if($v['type']==='Trailer' && $v['site']==='YouTube') { $trailerKey=$v['key']; break; }
}

// Current episode info
$curEpInfo = null;
foreach($episodeList as $epData) {
    if($epData['episode_number'] == $ep) { $curEpInfo = $epData; break; }
}

$isAlpha = !empty($activeSrv['is_alpha']);

$pageTitle = 'Watch '.e($title).' - '.setting('site_name','CineHub');
include __DIR__.'/includes/header.php';
?>

<div style="padding-top:var(--header-h);background:var(--bg);min-height:100vh">

<!-- ── PLAYER ─────────────────────────────────────────────────── -->
<div style="background:#000;width:100%">
  <div style="max-width:1400px;margin:0 auto">
    <div style="position:relative;width:100%;padding-bottom:56.25%;background:#000">
      <?php if($embedUrl): ?>
      <iframe src="<?php echo e($embedUrl);?>" style="position:absolute;inset:0;width:100%;height:100%;border:none"
              allowfullscreen allow="autoplay;fullscreen;picture-in-picture"
              referrerpolicy="no-referrer"
              <?php if(!$isAlpha): ?>sandbox="allow-scripts allow-same-origin allow-forms allow-popups allow-popups-to-escape-sandbox"<?php endif;?>></iframe>
      <?php else: ?>
      <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;flex-direction:column;gap:12px;color:var(--text3)">
        <i class="fas fa-exclamation-circle" style="font-size:2.5rem;color:var(--primary)"></i>
        <p>No server configured. <a href="/admin/embed-servers.php" style="color:var(--primary)">Setup servers</a></p>
      </div>
      <?php endif;?>
    </div>
  </div>
</div>

<!-- ── SERVER BOX ────────────────────────────────────────────── -->
<div style="background:var(--bg2);border-bottom:1px solid var(--border);padding:14px 20px">
  <div style="max-width:1100px;margin:0 auto">
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:10px">
      <span style="font-size:.7rem;font-weight:700;color:var(--text4);letter-spacing:1px;text-transform:uppercase;flex-shrink:0">Server</span>
      <div style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">
      <?php foreach($servers as $s):
        $active = $s['id']==$activeSrv['id'];
        $isAlphaSrv = !empty($s['is_alpha']);
        $bg = $active ? ($isAlphaSrv ? '#d97706' : 'var(--primary)') : 'var(--bg3)';
        $border = $active ? ($isAlphaSrv ? '#d97706' : 'var(--primary)') : 'var(--border)';
        $color = $active ? '#000' : 'var(--text2)';
      ?>
      <a href="?id=<?php echo $tmdbId;?>&type=<?php echo $type;?>&s=<?php echo $season;?>&e=<?php echo $ep;?>&server=<?php echo $s['id'];?>"
         style="display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:700;text-decoration:none;transition:all .2s;border:1.5px solid <?php echo $border;?>;background:<?php echo $bg;?>;color:<?php echo $color;?>">
        <?php if($isAlphaSrv): ?>
        <i class="fas fa-bolt" style="font-size:.7rem"></i>
        <?php else: ?>
        <i class="fas fa-server" style="font-size:.7rem"></i>
        <?php endif;?>
        <?php echo e($s['name']);?>
        <?php if($active): ?><span style="width:6px;height:6px;border-radius:50%;background:#000;flex-shrink:0"></span><?php endif;?>
      </a>
      <?php endforeach;?>
      </div>
    </div>
    <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
      <span style="font-size:.7rem;font-weight:700;color:var(--text4);letter-spacing:1px;text-transform:uppercase;flex-shrink:0">Actions</span>
      <?php if($type==='tv'): ?>
      <a href="/series.php?id=<?php echo $tmdbId;?>" class="btn btn-outline btn-sm" style="font-size:.78rem"><i class="fas fa-info-circle"></i> Details</a>
      <?php else: ?>
      <a href="/movie.php?id=<?php echo $tmdbId;?>" class="btn btn-outline btn-sm" style="font-size:.78rem"><i class="fas fa-info-circle"></i> Details</a>
      <?php endif;?>
      <?php if($trailerKey): ?>
      <button class="btn btn-outline btn-sm" style="font-size:.78rem" onclick="openTrailer('<?php echo e($trailerKey);?>')"><i class="fas fa-film"></i> Trailer</button>
      <?php endif;?>
    </div>
  </div>
</div>

<div style="max-width:1100px;margin:0 auto;padding:20px 16px">

<!-- ── SHOW INFO (flat, no box) ────────────────────────────────── -->
<div style="display:flex;gap:14px;align-items:flex-start;margin-bottom:22px;padding-bottom:20px;border-bottom:1px solid var(--border)">
  <img src="<?php echo e($poster);?>" style="width:72px;min-width:72px;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.5)" alt="">
  <div style="flex:1;min-width:0">
    <h2 style="font-family:'Bebas Neue',sans-serif;font-size:clamp(1.3rem,3vw,1.9rem);letter-spacing:1px;color:var(--text);margin-bottom:6px;line-height:1">
      <?php echo e($title);?>
      <?php if($type==='tv'): ?><span style="font-size:.85rem;font-family:'Inter',sans-serif;font-weight:400;color:var(--primary);margin-left:8px">S<?php echo $season;?> E<?php echo $ep;?></span><?php endif;?>
    </h2>
    <?php if($curEpInfo && $type==='tv'): ?>
    <div style="font-size:.85rem;font-weight:600;color:var(--text2);margin-bottom:4px"><?php echo e($curEpInfo['name']??''); ?></div>
    <?php endif;?>
    <div style="display:flex;align-items:center;gap:12px;font-size:.82rem;color:var(--text3);flex-wrap:wrap;margin-bottom:8px">
      <?php if($year): ?><span><i class="fas fa-calendar" style="color:var(--primary);margin-right:4px"></i><?php echo e($year);?></span><?php endif;?>
      <?php if($rating>0): ?><span><i class="fas fa-star" style="color:#fbbf24;margin-right:4px"></i><?php echo e($rating);?>/10</span><?php endif;?>
      <?php if($runtime): ?><span><i class="fas fa-clock" style="color:var(--primary);margin-right:4px"></i><?php echo formatRuntime($runtime);?></span><?php endif;?>
      <span style="background:var(--primary);color:#000;padding:2px 9px;border-radius:4px;font-size:.68rem;font-weight:700"><?php echo $type==='tv'?'SERIES':'MOVIE';?></span>
    </div>
    <?php if(!empty($genres)): ?>
    <div style="display:flex;flex-wrap:wrap;gap:5px">
      <?php foreach(array_slice($genres,0,4) as $g): ?>
      <span style="background:var(--bg3);border:1px solid var(--border);padding:2px 10px;border-radius:12px;font-size:.72rem;color:var(--text3)"><?php echo e($g['name']??$g);?></span>
      <?php endforeach;?>
    </div>
    <?php endif;?>
    <?php if($overview): ?>
    <p style="font-size:.82rem;color:var(--text3);line-height:1.65;margin-top:10px;display:-webkit-box;-webkit-line-clamp:4;-webkit-box-orient:vertical;overflow:hidden">
      <?php echo e($overview); ?>
    </p>
    <?php endif; ?>
    <?php if($telegramUrl): ?>
    <a href="<?php echo e($telegramUrl); ?>" target="_blank" rel="noopener" class="btn btn-outline btn-sm" style="margin-top:10px;border-color:rgba(34,158,217,.5);color:#8fd7ff;background:rgba(34,158,217,.08)">
      <i class="fab fa-telegram-plane"></i> Join Telegram
    </a>
    <?php endif; ?>
  </div>
</div>

<!-- ── SEASONS + EPISODE LIST (TV) ──────────────────────────── -->
<?php if($type==='tv' && !empty($seasonsData)): ?>
<div style="margin-bottom:20px">
  <div style="font-size:.9rem;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:7px">
    <i class="fas fa-layer-group" style="color:var(--primary)"></i> Seasons &amp; Episodes
  </div>
  <div class="season-tabs">
    <?php foreach($seasonsData as $sea): ?>
    <a href="?id=<?php echo $tmdbId;?>&type=tv&s=<?php echo $sea['season_number'];?>&e=1&server=<?php echo $activeSrv['id'];?>"
       style="padding:6px 18px;border-radius:20px;font-size:.82rem;font-weight:700;text-decoration:none;white-space:nowrap;transition:all .2s;flex-shrink:0;<?php echo $sea['season_number']==$season?'background:var(--primary);color:#000;border:1px solid var(--primary)':'background:var(--bg3);color:var(--text3);border:1px solid var(--border)';?>">
      Season <?php echo $sea['season_number'];?> <span style="font-size:.7rem;opacity:.7">(<?php echo $sea['episode_count']??0;?> ep)</span>
    </a>
    <?php endforeach;?>
  </div>
  <?php if(!empty($episodeList)): ?>
  <div style="display:flex;flex-direction:column;gap:8px;max-height:420px;overflow-y:auto;padding-right:4px" class="ep-list-scroll">
    <?php foreach($episodeList as $epData):
      $epNum   = $epData['episode_number'];
      $epName  = $epData['name'] ?? '';
      $epStill = isset($epData['still_path']) && $epData['still_path'] ? tmdbImg($epData['still_path'],'w300') : '';
      $epDesc  = $epData['overview'] ?? '';
      $epRt    = isset($epData['runtime']) ? intval($epData['runtime']) : 0;
      $isActive = $epNum == $ep;
      $epUrl   = '?id='.$tmdbId.'&type=tv&s='.$season.'&e='.$epNum.'&server='.$activeSrv['id'];
    ?>
    <a href="<?php echo e($epUrl);?>"
       style="display:flex;gap:12px;align-items:flex-start;padding:10px;border-radius:10px;text-decoration:none;transition:background .2s;background:<?php echo $isActive?'rgba(249,115,22,.1)':'transparent';?>;border:1px solid <?php echo $isActive?'var(--primary)':'transparent';?>">
      <div style="flex-shrink:0;position:relative;width:120px;min-width:120px">
        <?php if($epStill): ?>
        <img src="<?php echo e($epStill);?>" style="width:120px;height:68px;object-fit:cover;border-radius:7px;display:block" loading="lazy">
        <?php else: ?>
        <div style="width:120px;height:68px;background:var(--bg4);border-radius:7px;display:flex;align-items:center;justify-content:center">
          <i class="fas fa-play" style="color:var(--text4);font-size:.9rem"></i>
        </div>
        <?php endif;?>
        <?php if($isActive): ?>
        <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.5);border-radius:7px">
          <div style="width:30px;height:30px;background:var(--primary);border-radius:50%;display:flex;align-items:center;justify-content:center">
            <i class="fas fa-play" style="color:#000;font-size:.7rem;margin-left:2px"></i>
          </div>
        </div>
        <?php endif;?>
        <?php if($epRt): ?><div style="position:absolute;bottom:4px;right:4px;background:rgba(0,0,0,.8);color:#fff;font-size:.65rem;padding:1px 5px;border-radius:3px"><?php echo $epRt;?>m</div><?php endif;?>
      </div>
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:3px">
          <span style="font-size:.7rem;font-weight:700;color:var(--primary)">EP <?php echo $epNum;?></span>
          <?php if($isActive): ?><span style="font-size:.65rem;background:var(--primary);color:#000;padding:1px 6px;border-radius:3px;font-weight:700">PLAYING</span><?php endif;?>
        </div>
        <div style="font-size:.85rem;font-weight:600;color:<?php echo $isActive?'var(--primary)':'var(--text)';?>;line-height:1.3;margin-bottom:3px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo e($epName);?></div>
        <?php if($epDesc): ?>
        <div style="font-size:.75rem;color:var(--text4);line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?php echo e($epDesc);?></div>
        <?php endif;?>
      </div>
    </a>
    <?php endforeach;?>
  </div>
  <?php endif;?>
</div>
<?php endif;?>

<!-- ── DOWNLOAD LINKS ────────────────────────────────────────── -->
<?php if(!empty($downloads)):
  $isTV = ($type === 'tv');
  $seasonGroups = [];
  if ($isTV) {
    foreach ($downloads as $dl) {
      $sKey = $dl['season_num'] ? 'S'.str_pad($dl['season_num'],2,'0',STR_PAD_LEFT) : 'ALL';
      $seasonGroups[$sKey][] = $dl;
    }
    ksort($seasonGroups);
  }
?>
<div style="margin-bottom:20px">
  <div style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:12px;display:flex;align-items:center;gap:8px">
    <i class="fas fa-download" style="color:var(--primary)"></i> Download Links
  </div>
  <?php if($isTV && !empty($seasonGroups)): ?>
  <?php foreach($seasonGroups as $sKey => $sDownloads): ?>
  <div style="background:var(--bg2);border:1px solid var(--border);border-radius:12px;margin-bottom:14px;overflow:hidden">
    <div style="background:var(--bg3);padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:8px">
      <i class="fas fa-layer-group" style="color:var(--primary);font-size:.8rem"></i>
      <span style="font-size:.85rem;font-weight:700;color:var(--text)"><?php echo $sKey==='ALL' ? 'All Seasons' : 'Season '.ltrim(substr($sKey,1),'0'); ?></span>
    </div>
    <div style="padding:12px 14px;display:flex;flex-direction:column;gap:8px">
    <?php foreach($sDownloads as $dl): ?>
    <div class="dl-item" style="margin-bottom:0">
      <div class="dl-head">
        <div class="dl-head-info">
          <div class="dl-name"><?php echo e($dl['title']); ?></div>
          <div class="dl-tags">
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
        <?php $allUrls = array_filter(array_map('trim', explode("\n", $dl['url']??''))); ?>
        <?php if(!empty($allUrls)): ?>
        <?php if ($dl['episode_num'] > 0): ?>
        <div style="font-size:.75rem;font-weight:700;color:var(--primary);margin-bottom:8px"><i class="fas fa-play-circle"></i> Episode <?php echo $dl['episode_num']; ?></div>
        <?php endif; ?>
        <div class="dl-links">
          <?php foreach($allUrls as $lno => $link): ?>
          <a href="<?php echo e($link);?>" class="dl-link-btn" target="_blank" rel="nofollow noopener">
            <span><i class="fas fa-download" style="margin-right:8px"></i>Download <?php echo $dl['episode_num']>0 ? 'Ep '.$dl['episode_num'].' ' : ''; ?>Link <?php echo $lno+1;?></span>
            <i class="fas fa-external-link-alt" style="font-size:.8rem"></i>
          </a>
          <?php endforeach;?>
        </div>
        <?php endif;?>
      </div>
    </div>
    <?php endforeach;?>
    </div>
  </div>
  <?php endforeach;?>
  <?php else: ?>
  <?php foreach($downloads as $dl): ?>
  <div class="dl-item">
    <div class="dl-head">
      <div class="dl-head-info">
        <div class="dl-name"><?php echo e($dl['title']); ?></div>
        <div class="dl-tags">
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
      <?php $allUrls = array_filter(array_map('trim', explode("\n", $dl['url']??''))); ?>
      <?php if(!empty($allUrls)): ?>
      <div class="dl-links">
        <?php foreach($allUrls as $lno => $link): ?>
        <a href="<?php echo e($link);?>" class="dl-link-btn" target="_blank" rel="nofollow noopener">
          <span><i class="fas fa-download" style="margin-right:8px"></i>Download Now <?php echo count($allUrls)>1 ? '(Link '.($lno+1).')' : ''; ?></span>
          <i class="fas fa-external-link-alt" style="font-size:.8rem"></i>
        </a>
        <?php endforeach;?>
      </div>
      <?php endif;?>
    </div>
  </div>
  <?php endforeach;?>
  <?php endif;?>
</div>
<?php endif;?>

<!-- ── CAST ──────────────────────────────────────────────────── -->
<?php if(!empty($castData)): ?>
<div style="margin-bottom:20px">
  <div style="font-size:.9rem;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px">
    <i class="fas fa-users" style="color:var(--primary)"></i> Cast
  </div>
  <div style="display:flex;gap:12px;overflow-x:auto;padding-bottom:4px">
    <?php foreach($castData as $p):
      $ci = isset($p['profile_path'])&&$p['profile_path'] ? tmdbImg($p['profile_path'],'w185') : '/assets/images/no-poster.jpg';
    ?>
    <div style="flex:0 0 72px;text-align:center">
      <div style="width:64px;height:64px;border-radius:50%;overflow:hidden;margin:0 auto 6px;border:2px solid var(--border)">
        <img src="<?php echo e($ci);?>" alt="" style="width:100%;height:100%;object-fit:cover" loading="lazy">
      </div>
      <div style="font-size:.7rem;font-weight:600;color:var(--text2);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:72px"><?php echo e($p['name']??'');?></div>
      <?php if(!empty($p['character'])): ?><div style="font-size:.62rem;color:var(--text4);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:72px"><?php echo e($p['character']);?></div><?php endif;?>
    </div>
    <?php endforeach;?>
  </div>
</div>
<?php endif;?>

<!-- ── RECOMMENDATIONS ──────────────────────────────────────── -->
<?php if(!empty($similar)): ?>
<div style="margin-bottom:24px">
  <div style="font-size:.95rem;font-weight:700;color:var(--text);margin-bottom:14px;display:flex;align-items:center;gap:8px">
    <i class="fas fa-th" style="color:var(--primary)"></i> You May Also Like
  </div>
  <div class="cards-grid-lg">
    <?php foreach($similar as $m):
      $mt  = $m['media_type'] ?? ($type==='tv'?'tv':'movie');
      $st  = $m['title'] ?? ($m['name'] ?? '');
      $sp  = isset($m['poster_path'])&&$m['poster_path'] ? tmdbImg($m['poster_path'],'w300') : '/assets/images/no-poster.jpg';
      $sy  = substr($m['release_date']??($m['first_air_date']??''), 0, 4);
      $sr  = number_format(floatval($m['vote_average']??0), 1);
      $su  = $mt==='tv' ? '/series.php?id='.$m['id'] : '/movie.php?id='.$m['id'];
    ?>
    <div class="card">
      <div class="card-poster-wrap">
        <a href="<?php echo e($su);?>"><img src="<?php echo e($sp);?>" class="card-poster" alt="<?php echo e($st);?>" loading="lazy"></a>
        <div class="card-overlay"><a href="<?php echo e($su);?>" class="card-play"><i class="fas fa-play"></i></a></div>
      </div>
      <a href="<?php echo e($su);?>" class="card-info">
        <div class="card-title"><?php echo e($st);?></div>
        <div class="card-meta"><span><?php echo e($sy);?></span><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i> <?php echo e($sr);?></span></div>
      </a>
    </div>
    <?php endforeach;?>
  </div>
</div>
<?php endif;?>

</div><!-- /container -->
</div><!-- /page -->

<!-- Trailer Modal -->
<div class="modal-overlay" id="trailerModal">
  <div class="modal-inner">
    <button class="modal-close" onclick="closeTrailer()"><i class="fas fa-times"></i> Close</button>
    <iframe id="trailerFrame" allowfullscreen allow="autoplay;fullscreen" frameborder="0"></iframe>
  </div>
</div>

<style>
.season-tabs{
  display:flex;
  gap:8px;
  flex-wrap:nowrap;
  overflow-x:auto;
  margin-bottom:16px;
  padding-bottom:4px;
  scrollbar-width:none;
}
.season-tabs::-webkit-scrollbar{display:none}
.ep-list-scroll::-webkit-scrollbar{width:4px}
.ep-list-scroll::-webkit-scrollbar-track{background:var(--bg3)}
.ep-list-scroll::-webkit-scrollbar-thumb{background:var(--bg5);border-radius:2px}
</style>

<?php include __DIR__.'/includes/footer.php';?>
