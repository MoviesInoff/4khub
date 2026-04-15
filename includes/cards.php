<?php
function renderCard($m, $rank=0) {
    $type   = $m['type'] ?? 'movie';
    $slug   = $m['slug'] ?? '';
    $title  = $m['title'] ?? '';
    $year   = $m['year'] ?? '';
    $rating = isset($m['vote_average']) ? number_format(floatval($m['vote_average']),1) : '';
    $poster = isset($m['poster_path']) && $m['poster_path'] ? tmdbImg($m['poster_path'],'w342') : '/assets/images/no-poster.jpg';
    $tags   = parseTags($m['tags'] ?? '');
    if ($slug) $url = ($type==='tv') ? '/series/'.$slug : '/movie/'.$slug;
    elseif (isset($m['tmdb_id'])) $url = ($type==='tv') ? '/series.php?id='.$m['tmdb_id'] : '/movie.php?id='.$m['tmdb_id'];
    else $url = '#';
    ob_start(); ?>
    <div class="card">
      <?php if($rank > 0): ?><div class="rank-num"><?php echo $rank; ?></div><?php endif; ?>
      <div class="card-poster-wrap">
        <a href="<?php echo e($url); ?>"><img src="<?php echo e($poster); ?>" class="card-poster" alt="<?php echo e($title); ?>" loading="lazy"></a>
        <?php if($type==='tv'): ?><span class="tag tag-tv card-tv-tag">TV</span><?php endif; ?>
        <?php if(!empty($tags)): ?>
        <div class="card-tags"><?php foreach(array_slice($tags,0,3) as $tag): ?><span class="tag <?php echo tagClass($tag); ?>"><?php echo e(strtoupper($tag)); ?></span><?php endforeach; ?></div>
        <?php endif; ?>
        <div class="card-overlay"><a href="<?php echo e($url); ?>" class="card-play"><i class="fas fa-play"></i></a></div>
      </div>
      <a href="<?php echo e($url); ?>" class="card-info">
        <div class="card-title"><?php echo e($title); ?></div>
        <div class="card-meta">
          <span><?php echo e($year); ?><?php echo ($type==='tv')?' &middot; TV':''; ?></span>
          <?php if($rating): ?><span class="card-rating"><i class="fas fa-star" style="font-size:.65rem"></i> <?php echo e($rating); ?></span><?php endif; ?>
        </div>
      </a>
    </div>
    <?php return ob_get_clean();
}

function renderSection($title, $icon, $items, $seeAllUrl='', $ranked=false) {
    if(empty($items)) return;
    echo '<section class="section"><div class="section-head"><div class="section-title"><i class="'.$icon.' icon"></i> '.e($title).'</div>';
    if($seeAllUrl) echo '<a href="'.e($seeAllUrl).'" class="section-link">See All &rarr;</a>';
    echo '</div><div class="cards-row">';
    $i=1; foreach($items as $m) echo renderCard($m, $ranked ? $i++ : 0);
    echo '</div></section>';
}
