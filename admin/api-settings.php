<?php
$adminTitle = 'API Settings';
require_once __DIR__.'/admin-header.php';
$msg=''; $msgType='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $key = trim(isset($_POST['api_key'])?$_POST['api_key']:'');
    if($key){
        // Test key
        $test = tmdbRequest('/movie/popular');
        if(!empty($test['results'])){
            setSetting('tmdb_api_key',$key);
            $msg='API key saved and verified! TMDB is connected.'; $msgType='success';
        } else {
            setSetting('tmdb_api_key',$key); // Save anyway in case of network issue
            $msg='Key saved. Could not verify (check your network), but it has been saved.'; $msgType='warning';
        }
    } else { $msg='API key cannot be empty.'; $msgType='error'; }
}
$curKey = setting('tmdb_api_key','');
?>
<div style="max-width:700px">
<?php if($msg): ?><div class="aalert aalert-<?php echo $msgType;?>"><i class="fas fa-info-circle"></i> <?php echo $msg;?></div><?php endif; ?>
<div class="ac" style="margin-bottom:20px">
  <div class="ac-head"><span>TMDB Connection</span></div>
  <div style="padding:14px 18px">
    <div style="display:flex;align-items:center;gap:12px;padding:14px;background:var(--c-bg3);border-radius:9px">
      <div style="width:12px;height:12px;border-radius:50%;background:<?php echo $curKey?'var(--c-green)':'var(--c-red)';?>;box-shadow:0 0 8px <?php echo $curKey?'var(--c-green)':'var(--c-red)';?>"></div>
      <div><div style="font-weight:600;color:var(--c-text);font-size:.9rem"><?php echo $curKey?'API Connected':'Not Configured';?></div>
      <?php if($curKey): ?><div style="font-size:.75rem;color:var(--c-text4);font-family:monospace;margin-top:2px"><?php echo substr($curKey,0,8).'...'.substr($curKey,-4);?></div><?php endif; ?></div>
    </div>
  </div>
</div>
<div class="ac">
  <div class="ac-head"><span><i class="fas fa-key" style="color:var(--c-primary);margin-right:7px"></i>TMDB API Key (v3)</span></div>
  <div style="padding:18px">
    <form method="POST">
      <div class="afg"><label class="afl">API Key</label>
        <div style="display:flex;gap:8px">
          <input type="text" name="api_key" class="afc" value="<?php echo e($curKey);?>" placeholder="Paste your TMDB v3 API key" required style="font-family:monospace;letter-spacing:.5px">
          <button type="submit" class="abtn abtn-primary" style="flex-shrink:0"><i class="fas fa-save"></i> Save & Test</button>
        </div>
        <div class="afh">Get your free key at <a href="https://www.themoviedb.org/settings/api" target="_blank" style="color:var(--c-primary)">themoviedb.org/settings/api</a></div>
      </div>
    </form>
    <div style="margin-top:20px;padding:14px;background:var(--c-bg3);border-radius:9px;border-left:3px solid var(--c-blue)">
      <div style="font-size:.8rem;color:var(--c-text3)"><strong style="color:var(--c-blue)">Getting your free key:</strong><br>
      1. Create account at themoviedb.org<br>2. Go to Profile → Settings → API<br>3. Click "Create" → Choose "Developer"<br>4. Copy the <strong>API Key (v3 auth)</strong></div>
    </div>
  </div>
</div>
</div>
<?php include __DIR__.'/admin-footer.php'; ?>
