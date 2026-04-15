<?php
if(!function_exists('setting')) require_once __DIR__.'/../includes/core.php';
sess();
$siteName   = setting('site_name','CineHub');
$pc         = setting('primary_color','#f97316');
$curUser    = curUser();
$curPage    = basename($_SERVER['PHP_SELF'],'.php');
$regEnabled = (bool)setting('allow_registration','1');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo e(isset($pageTitle)?$pageTitle:$siteName.' - Premium Entertainment'); ?></title>
<meta name="description" content="<?php echo e(isset($pageDesc)?$pageDesc:'Stream and download movies, series, anime in HD and 4K'); ?>">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
<style>:root{--primary:<?php echo $pc;?>;--primary-dark:<?php
$h=ltrim($pc,'#');
$r=max(0,min(255,hexdec(substr($h,0,2))-20));
$g=max(0,min(255,hexdec(substr($h,2,2))-20));
$b=max(0,min(255,hexdec(substr($h,4,2))-20));
echo '#'.sprintf('%02x%02x%02x',$r,$g,$b);
?>;--primary-glow:<?php
$r2=hexdec(substr($h,0,2));$g2=hexdec(substr($h,2,2));$b2=hexdec(substr($h,4,2));
echo "rgba($r2,$g2,$b2,0.25)";
?>;}</style>
</head>
<body>

<div class="toast-wrap"></div>
<div class="drawer-overlay"></div>

<!-- Mobile Drawer -->
<div class="drawer">
  <div class="drawer-head">
    <a href="/index.php" style="display:flex;align-items:center;gap:0;text-decoration:none;font-family:'Bebas Neue',sans-serif;font-size:1.85rem;letter-spacing:2px;color:var(--text)">
      <span style="color:var(--primary)"><?php echo e(strtoupper(substr($siteName,0,2))); ?></span><?php echo e(strtoupper(substr($siteName,2))); ?>
    </a>
    <button class="drawer-close btn-icon"><i class="fas fa-times"></i></button>
  </div>
  <?php if(loggedIn()): ?>
  <div style="background:var(--bg3);border-radius:var(--radius);padding:12px;margin-bottom:18px">
    <div style="font-weight:700;color:var(--text)"><?php echo e($_SESSION['uname']); ?></div>
    <div style="font-size:.75rem;color:var(--text3)">Member</div>
  </div>
  <?php endif; ?>
  <div class="form-group" style="margin-bottom:16px">
    <div style="position:relative">
      <i class="fas fa-search" style="position:absolute;left:11px;top:50%;transform:translateY(-50%);color:var(--text4);font-size:.85rem"></i>
      <input type="text" class="form-control" placeholder="Search..." style="border-radius:20px;padding-left:34px"
        onkeydown="if(event.key==='Enter'&&this.value.trim())window.location='/search.php?q='+encodeURIComponent(this.value.trim())">
    </div>
  </div>
  <nav class="drawer-nav">
    <a href="/index.php" <?php echo $curPage==='index'?'class="active"':''; ?>><i class="fas fa-home" style="width:20px"></i> Home</a>
    <a href="/movies.php" <?php echo $curPage==='movies'?'class="active"':''; ?>><i class="fas fa-film" style="width:20px"></i> Movies</a>
    <a href="/series.php" <?php echo $curPage==='series'?'class="active"':''; ?>><i class="fas fa-tv" style="width:20px"></i> Web Series</a>
    <a href="/anime.php" <?php echo $curPage==='anime'?'class="active"':''; ?>><i class="fas fa-dragon" style="width:20px"></i> Anime</a>
    <a href="/genres.php"><i class="fas fa-tags" style="width:20px"></i> Genres</a>
    <?php if(loggedIn()): ?>
    <hr style="border-color:var(--border);margin:10px 0">
    <a href="/watchlist.php"><i class="fas fa-bookmark" style="width:20px"></i> My Watchlist</a>
    <?php if(isAdmin()): ?><a href="/admin/index.php" style="color:var(--primary)"><i class="fas fa-cog" style="width:20px"></i> Admin Panel</a><?php endif; ?>
    <a href="/logout.php"><i class="fas fa-sign-out-alt" style="width:20px"></i> Sign Out</a>
    <?php else: ?>
    <hr style="border-color:var(--border);margin:10px 0">
    <a href="/login.php"><i class="fas fa-sign-in-alt" style="width:20px"></i> Sign In</a>
    <?php if($regEnabled): ?>
    <a href="/register.php" style="color:var(--primary)"><i class="fas fa-user-plus" style="width:20px"></i> Register Free</a>
    <?php endif; ?>
    <?php endif; ?>
  </nav>
</div>

<!-- Header — no logo box, just styled text -->
<header class="header" id="header">
  <!-- Logo: plain text, no box -->
  <a href="/index.php" style="display:flex;align-items:center;gap:0;text-decoration:none;font-family:'Bebas Neue',sans-serif;font-size:1.85rem;letter-spacing:2px;color:var(--text);flex-shrink:0">
    <span style="color:var(--primary)"><?php echo e(strtoupper(substr($siteName,0,2))); ?></span><?php echo e(strtoupper(substr($siteName,2))); ?>
  </a>

  <nav class="header-nav">
    <a href="/index.php" <?php echo $curPage==='index'?'class="active"':''; ?>>Home</a>
    <a href="/movies.php" <?php echo $curPage==='movies'?'class="active"':''; ?>>Movies</a>
    <a href="/series.php" <?php echo $curPage==='series'?'class="active"':''; ?>>Web Series</a>
    <a href="/anime.php" <?php echo $curPage==='anime'?'class="active"':''; ?>>Anime</a>
    <a href="/genres.php" <?php echo $curPage==='genres'?'class="active"':''; ?>>Genres</a>
  </nav>

  <div class="header-right">
    <!-- Desktop search (hidden on mobile) -->
    <div class="search-wrap">
      <i class="fas fa-search si"></i>
      <input type="text" class="search-input" placeholder="Search movies, shows...">
    </div>

    <?php if($curUser): ?>
    <!-- Logged in: avatar -->
    <div class="user-dropdown">
      <div class="user-btn"><?php echo strtoupper(substr($curUser['username'],0,1)); ?></div>
      <div class="user-menu">
        <div class="user-menu-head">
          <div class="uname"><?php echo e($curUser['username']); ?></div>
          <div class="uemail"><?php echo e($curUser['email']); ?></div>
        </div>
        <a href="/watchlist.php"><i class="fas fa-bookmark"></i> My Watchlist</a>
        <?php if(isAdmin()): ?>
        <a href="/admin/index.php" style="color:var(--primary)"><i class="fas fa-cog"></i> Admin Panel</a>
        <?php endif; ?>
        <a href="/logout.php" class="danger"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
      </div>
    </div>
    <!-- Mobile search icon (between avatar and hamburger) -->
    <button class="btn-icon mobile-search-btn" aria-label="Search" style="display:none">
      <i class="fas fa-search"></i>
    </button>
    <?php else: ?>
    <a href="/login.php" class="btn btn-outline btn-sm">Sign In</a>
    <?php if($regEnabled): ?>
    <a href="/register.php" class="btn btn-primary btn-sm">Join Free</a>
    <?php endif; ?>
    <!-- Mobile search icon (before hamburger when not logged in) -->
    <button class="btn-icon mobile-search-btn" aria-label="Search" style="display:none">
      <i class="fas fa-search"></i>
    </button>
    <?php endif; ?>

    <button class="hamburger" aria-label="Menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<!-- Mobile search bar (slides down) -->
<div id="mobileSearchBar" style="display:none;position:fixed;top:var(--header-h);left:0;right:0;z-index:998;background:var(--bg2);border-bottom:1px solid var(--border);padding:12px 16px">
  <div style="position:relative;max-width:600px;margin:0 auto">
    <i class="fas fa-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--text4);font-size:.85rem"></i>
    <input type="text" id="mobileSearchInput" class="form-control" placeholder="Search movies, shows, anime..." style="padding-left:36px;border-radius:20px"
      onkeydown="if(event.key==='Enter'&&this.value.trim())window.location='/search.php?q='+encodeURIComponent(this.value.trim())">
  </div>
</div>

<!-- Search dropdown -->
<div class="search-results-drop"></div>

<script>
// Mobile search toggle
document.querySelectorAll('.mobile-search-btn').forEach(function(btn){
  btn.addEventListener('click', function(){
    var bar = document.getElementById('mobileSearchBar');
    var inp = document.getElementById('mobileSearchInput');
    if(bar.style.display==='none'){
      bar.style.display='block';
      if(inp) setTimeout(function(){inp.focus();},80);
    } else {
      bar.style.display='none';
    }
  });
});
// Show mobile search btn only on mobile
(function(){
  function checkWidth(){
    var show = window.innerWidth < 768;
    document.querySelectorAll('.mobile-search-btn').forEach(function(b){ b.style.display = show ? 'flex' : 'none'; });
    var bar = document.getElementById('mobileSearchBar');
    if(!show && bar) bar.style.display='none';
  }
  checkWidth();
  window.addEventListener('resize', checkWidth);
})();
</script>
