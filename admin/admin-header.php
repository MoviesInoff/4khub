<?php
require_once __DIR__.'/../includes/core.php';
requireAdmin();
$sn = setting('site_name','CineHub');
$ap = basename($_SERVER['PHP_SELF'],'.php');
$adminUser = curUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title><?php echo e(isset($adminTitle)?$adminTitle:'Admin'); ?> – <?php echo e($sn); ?> Admin</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/admin/admin.css">
</head>
<body>
<div class="admin-layout">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
  <div class="sb-brand">
    <a href="/" style="display:flex;align-items:center;gap:0;text-decoration:none;font-family:'Bebas Neue',sans-serif;font-size:1.55rem;letter-spacing:2px;color:var(--c-text);flex-shrink:0">
      <span style="color:var(--c-primary)"><?php echo e(strtoupper(substr($sn,0,2))); ?></span><?php echo e(strtoupper(substr($sn,2))); ?>
    </a>
    <div style="font-size:.65rem;color:var(--c-primary);font-weight:700;letter-spacing:1px;text-transform:uppercase;padding-left:2px;margin-top:-4px">Admin</div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <a href="/admin/index.php" class="sb-link <?php echo $ap==='index'?'active':''; ?>"><i class="fas fa-th-large"></i> Dashboard</a>

    <div class="sb-section">Content</div>
    <a href="/admin/import.php" class="sb-link <?php echo $ap==='import'?'active':''; ?>"><i class="fas fa-cloud-download-alt"></i> Import from TMDB</a>
    <a href="/admin/media.php" class="sb-link <?php echo $ap==='media'?'active':''; ?>"><i class="fas fa-photo-film"></i> All Media</a>

    <div class="sb-section">Settings</div>
    <a href="/admin/embed-servers.php" class="sb-link <?php echo $ap==='embed-servers'?'active':''; ?>"><i class="fas fa-server"></i> Embed Servers</a>
    <a href="/admin/api-settings.php" class="sb-link <?php echo $ap==='api-settings'?'active':''; ?>"><i class="fas fa-key"></i> API Settings</a>
    <a href="/admin/settings.php" class="sb-link <?php echo $ap==='settings'?'active':''; ?>"><i class="fas fa-cog"></i> Site Settings</a>
    <a href="/admin/users.php" class="sb-link <?php echo $ap==='users'?'active':''; ?>"><i class="fas fa-users"></i> Users</a>

    <div class="sb-section">Site</div>
    <a href="/" target="_blank" class="sb-link"><i class="fas fa-external-link-alt"></i> View Site</a>
  </nav>
  <div class="sb-foot">
    <a href="/logout.php" class="sb-link"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
    <div style="display:flex;align-items:center;gap:9px;padding:9px 8px;border-radius:8px;margin-top:4px">
      <div style="width:30px;height:30px;border-radius:50%;background:var(--c-primary);color:#000;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?php echo strtoupper(substr($adminUser['username'],0,1)); ?></div>
      <div><div style="font-size:.82rem;font-weight:600;color:var(--c-text)"><?php echo e($adminUser['username']); ?></div><div style="font-size:.68rem;color:var(--c-primary)">Administrator</div></div>
    </div>
  </div>
</aside>

<!-- MAIN -->
<main class="admin-main">
<header class="admin-topbar">
  <div style="display:flex;align-items:center;gap:12px">
    <button id="sbToggle" class="abtn abtn-sm" style="display:none"><i class="fas fa-bars"></i></button>
    <div>
      <div class="tb-title"><?php echo e(isset($adminTitle)?$adminTitle:'Dashboard'); ?></div>
      <div class="tb-sub"><?php echo e(isset($adminSub)?$adminSub:'CineHub Admin Panel'); ?></div>
    </div>
  </div>
  <div class="tb-actions">
    <a href="/" target="_blank" class="abtn abtn-sm"><i class="fas fa-external-link-alt"></i> View Site</a>
  </div>
</header>
<div class="admin-content">

<script>
var sb=document.getElementById('adminSidebar'),tog=document.getElementById('sbToggle');
function chk(){tog.style.display=window.innerWidth<=768?'flex':'none';}
chk();window.addEventListener('resize',chk);
tog.addEventListener('click',function(){sb.classList.toggle('open');});
document.addEventListener('click',function(e){if(window.innerWidth<=768&&!sb.contains(e.target)&&!tog.contains(e.target))sb.classList.remove('open');});
</script>
