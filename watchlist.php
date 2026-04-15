<?php
require_once __DIR__.'/includes/core.php';
require_once __DIR__.'/includes/cards.php';
sess(); if(!loggedIn()){header('Location: /login.php');exit;}
$pageTitle='My Watchlist - '.setting('site_name','CineHub');
$page=max(1,intval(isset($_GET['page'])?$_GET['page']:1));$pp=20;$offset=($page-1)*$pp;$uid=$_SESSION['uid'];
$total=DB::row("SELECT COUNT(*) as c FROM watchlist wl JOIN media m ON m.id=wl.media_id WHERE wl.user_id=?",array($uid))['c'];
$items=DB::rows("SELECT m.* FROM media m JOIN watchlist wl ON wl.media_id=m.id WHERE wl.user_id=? ORDER BY wl.added_at DESC LIMIT ".intval($pp)." OFFSET ".intval($offset),array($uid));
$totalPages=max(1,ceil($total/$pp));
include __DIR__.'/includes/header.php';
?>
<div class="page-header"><h1 class="page-title">My Watchlist</h1><p style="color:var(--text3);margin-top:8px"><?php echo number_format($total);?> saved title<?php echo $total!==1?'s':'';?></p></div>
<div style="padding:0 20px 48px">
<?php if(empty($items)): ?>
<div class="no-results"><i class="fas fa-bookmark"></i><h3>Nothing saved yet</h3><p>Browse movies and series and save titles to watch later.</p><a href="/movies.php" class="btn btn-primary" style="margin-top:14px">Browse Movies</a></div>
<?php else: ?><div class="cards-grid-lg"><?php foreach($items as $m) echo renderCard($m);?></div>
<?php if($totalPages>1): ?><div class="pagination"><?php if($page>1): ?><a href="/watchlist.php?page=<?php echo $page-1;?>" class="ppage"><i class="fas fa-chevron-left"></i></a><?php endif;?><?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?><a href="/watchlist.php?page=<?php echo $p;?>" class="ppage <?php echo $p===$page?'active':'';?>"><?php echo $p;?></a><?php endfor;?><?php if($page<$totalPages): ?><a href="/watchlist.php?page=<?php echo $page+1;?>" class="ppage"><i class="fas fa-chevron-right"></i></a><?php endif;?></div><?php endif;?>
<?php endif;?>
</div>
<?php include __DIR__.'/includes/footer.php';?>
