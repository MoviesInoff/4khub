<?php
$adminTitle = 'Dashboard';
$adminSub   = 'Overview & Quick Stats';
require_once __DIR__.'/admin-header.php';
$totalMedia  = DB::row("SELECT COUNT(*) as c FROM media")['c'];
$totalMovies = DB::row("SELECT COUNT(*) as c FROM media WHERE type='movie'")['c'];
$totalSeries = DB::row("SELECT COUNT(*) as c FROM media WHERE type='tv'")['c'];
$totalUsers  = DB::row("SELECT COUNT(*) as c FROM users")['c'];
$totalDL     = DB::row("SELECT COUNT(*) as c FROM download_links")['c'];
$recentMedia = DB::rows("SELECT * FROM media ORDER BY created_at DESC LIMIT 8");
$apiConfigured = !empty(setting('tmdb_api_key',''));
?>
<?php if(!$apiConfigured): ?>
<div class="aalert aalert-warning"><i class="fas fa-exclamation-triangle"></i> <strong>TMDB API not configured!</strong> <a href="/admin/api-settings.php" style="color:var(--c-primary);text-decoration:underline">Set API key</a> before importing.</div>
<?php endif; ?>
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px">
<?php $stats=array(array('Total Media',$totalMedia,'fas fa-photo-film','#f97316','rgba(249,115,22,.12)'),array('Movies',$totalMovies,'fas fa-film','#3b82f6','rgba(59,130,246,.12)'),array('Series',$totalSeries,'fas fa-tv','#22c55e','rgba(34,197,94,.12)'),array('Users',$totalUsers,'fas fa-users','#a855f7','rgba(168,85,247,.12)'),array('DL Links',$totalDL,'fas fa-download','#ef4444','rgba(239,68,68,.12)'));
foreach($stats as $s): ?>
<div style="background:var(--c-bg2);border:1px solid var(--c-border);border-radius:11px;padding:18px;display:flex;align-items:center;gap:12px">
  <div style="width:40px;height:40px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:1rem;background:<?php echo $s[4];?>;color:<?php echo $s[3];?>"><i class="<?php echo $s[2];?>"></i></div>
  <div><div style="font-size:1.6rem;font-weight:900;color:var(--c-text);line-height:1"><?php echo number_format($s[1]);?></div><div style="font-size:.75rem;color:var(--c-text3)"><?php echo $s[0];?></div></div>
</div>
<?php endforeach; ?>
</div>
<div style="display:grid;grid-template-columns:1fr 280px;gap:18px;align-items:start">
<div class="ac">
  <div class="ac-head"><span>Recent Imports</span><a href="/admin/media.php" class="abtn abtn-sm">View All</a></div>
  <div style="overflow-x:auto">
  <table class="atable">
    <thead><tr><th>Poster</th><th>Title</th><th>Type</th><th>Year</th><th>Tags</th><th>DL</th><th>Status</th><th>Edit</th></tr></thead>
    <tbody>
    <?php if(empty($recentMedia)): ?><tr><td colspan="8" style="text-align:center;padding:32px;color:var(--c-text3)">No media yet. <a href="/admin/import.php" style="color:var(--c-primary)">Import now</a></td></tr><?php endif; ?>
    <?php foreach($recentMedia as $m):
      $mt=jd(isset($m['tags'])?$m['tags']:'[]');
      $dlc=DB::row("SELECT COUNT(*) as c FROM download_links WHERE media_id=?",array($m['id']))['c'];
    ?>
    <tr>
      <td><img src="<?php echo $m['poster_path']?tmdbImg($m['poster_path'],'w92'):'/assets/images/no-poster.jpg';?>" style="width:34px;height:51px;object-fit:cover;border-radius:5px" loading="lazy"></td>
      <td><strong style="color:var(--c-text);font-size:.85rem"><?php echo e($m['title']);?></strong></td>
      <td><span style="background:<?php echo $m['type']==='tv'?'rgba(59,130,246,.12)':'rgba(249,115,22,.12)';?>;color:<?php echo $m['type']==='tv'?'#3b82f6':'#f97316';?>;padding:2px 7px;border-radius:4px;font-size:.68rem;font-weight:700"><?php echo strtoupper($m['type']==='tv'?'TV':'Movie');?></span></td>
      <td style="font-size:.82rem"><?php echo e($m['year']);?></td>
      <td><div style="display:flex;gap:3px"><?php foreach(array_slice($mt,0,2) as $t): ?><span style="background:var(--c-bg5);color:var(--c-text3);padding:1px 5px;border-radius:3px;font-size:.65rem;font-weight:600"><?php echo e(strtoupper($t));?></span><?php endforeach; ?></div></td>
      <td><span style="color:<?php echo $dlc>0?'var(--c-green)':'var(--c-text4)';?>;font-weight:700"><?php echo $dlc;?></span></td>
      <td><span style="background:<?php echo $m['status']==='published'?'rgba(34,197,94,.1)':'rgba(107,114,128,.1)';?>;color:<?php echo $m['status']==='published'?'#22c55e':'#9ca3af';?>;padding:2px 7px;border-radius:4px;font-size:.68rem;font-weight:700"><?php echo ucfirst($m['status']);?></span></td>
      <td><a href="/admin/edit-media.php?id=<?php echo $m['id'];?>" class="abtn abtn-sm"><i class="fas fa-edit"></i></a></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<div style="display:flex;flex-direction:column;gap:14px">
  <div class="ac"><div class="ac-head"><span>Quick Actions</span></div>
    <div style="padding:12px;display:flex;flex-direction:column;gap:7px">
      <a href="/admin/import.php" class="abtn abtn-primary abtn-full"><i class="fas fa-cloud-download-alt"></i> Import from TMDB</a>
      <a href="/admin/media.php" class="abtn abtn-full"><i class="fas fa-list"></i> Manage Media</a>
      <a href="/admin/embed-servers.php" class="abtn abtn-full"><i class="fas fa-server"></i> Embed Servers</a>
      <a href="/admin/settings.php" class="abtn abtn-full"><i class="fas fa-cog"></i> Settings</a>
      <a href="/admin/api-settings.php" class="abtn abtn-full"><i class="fas fa-key"></i> API Settings</a>
    </div>
  </div>
  <div class="ac"><div class="ac-head"><span>API Status</span></div>
    <div style="padding:14px">
      <div style="display:flex;align-items:center;gap:10px;margin-bottom:12px">
        <div style="width:10px;height:10px;border-radius:50%;background:<?php echo $apiConfigured?'#22c55e':'#ef4444';?>;box-shadow:0 0 6px <?php echo $apiConfigured?'#22c55e':'#ef4444';?>"></div>
        <span style="font-weight:600;color:var(--c-text);font-size:.875rem"><?php echo $apiConfigured?'Connected':'Not Configured';?></span>
      </div>
      <a href="/admin/api-settings.php" class="abtn abtn-full"><?php echo $apiConfigured?'Update Key':'Configure API';?></a>
    </div>
  </div>
</div>
</div>
<?php include __DIR__.'/admin-footer.php'; ?>
