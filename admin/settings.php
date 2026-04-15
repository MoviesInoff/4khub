<?php
$adminTitle = 'Site Settings';
require_once __DIR__.'/admin-header.php';
$msg=''; $msgType='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    foreach(['site_name','site_tagline','primary_color','items_per_page','homepage_count',
             'social_telegram','social_twitter','social_instagram','social_youtube','social_facebook'] as $f){
        setSetting($f, trim($_POST[$f] ?? ''));
    }
    setSetting('show_hero_slider', isset($_POST['show_hero_slider']) ? '1' : '0');
    setSetting('allow_registration', isset($_POST['allow_registration']) ? '1' : '0');
    setSetting('maintenance_mode',   isset($_POST['maintenance_mode'])   ? '1' : '0');
    $msg='Settings saved!'; $msgType='success';
}
?>
<?php if($msg): ?><div class="aalert aalert-<?php echo $msgType;?>"><i class="fas fa-check-circle"></i> <?php echo $msg;?></div><?php endif; ?>
<div style="max-width:700px">
<form method="POST">

<!-- General -->
<div class="ac" style="margin-bottom:18px">
  <div class="ac-head"><span>General</span></div>
  <div style="padding:18px">
    <div class="afr">
      <div class="afg"><label class="afl">Site Name</label><input type="text" name="site_name" class="afc" value="<?php echo e(setting('site_name','CineHub'));?>" required></div>
      <div class="afg"><label class="afl">Tagline</label><input type="text" name="site_tagline" class="afc" value="<?php echo e(setting('site_tagline',''));?>"></div>
    </div>
  </div>
</div>

<!-- Color -->
<div class="ac" style="margin-bottom:18px">
  <div class="ac-head"><span>Primary Accent Color</span></div>
  <div style="padding:18px">
    <div style="display:flex;gap:10px;align-items:center">
      <input type="color" name="primary_color" value="<?php echo e(setting('primary_color','#f97316'));?>" id="colorPicker" style="width:44px;height:40px;border-radius:8px;border:1px solid var(--c-border);cursor:pointer;padding:3px">
      <input type="text" id="colorText" class="afc" value="<?php echo e(setting('primary_color','#f97316'));?>" style="max-width:130px;font-family:monospace">
    </div>
  </div>
</div>

<!-- Social Links -->
<div class="ac" style="margin-bottom:18px">
  <div class="ac-head"><span>Social Links</span></div>
  <div style="padding:18px;display:flex;flex-direction:column;gap:12px">
    <div class="afg">
      <label class="afl"><i class="fab fa-telegram" style="color:#229ED9;margin-right:6px"></i>Telegram URL</label>
      <input type="url" name="social_telegram" class="afc" value="<?php echo e(setting('social_telegram',''));?>" placeholder="https://t.me/yourchannel">
    </div>
    <div class="afg">
      <label class="afl"><i class="fab fa-x-twitter" style="color:#fff;margin-right:6px"></i>X / Twitter URL</label>
      <input type="url" name="social_twitter" class="afc" value="<?php echo e(setting('social_twitter',''));?>" placeholder="https://x.com/yourhandle">
    </div>
    <div class="afg">
      <label class="afl"><i class="fab fa-instagram" style="color:#e1306c;margin-right:6px"></i>Instagram URL</label>
      <input type="url" name="social_instagram" class="afc" value="<?php echo e(setting('social_instagram',''));?>" placeholder="https://instagram.com/yourpage">
    </div>
    <div class="afg">
      <label class="afl"><i class="fab fa-youtube" style="color:#FF0000;margin-right:6px"></i>YouTube URL</label>
      <input type="url" name="social_youtube" class="afc" value="<?php echo e(setting('social_youtube',''));?>" placeholder="https://youtube.com/@yourchannel">
    </div>
    <div class="afg">
      <label class="afl"><i class="fab fa-facebook-f" style="color:#1877F2;margin-right:6px"></i>Facebook URL</label>
      <input type="url" name="social_facebook" class="afc" value="<?php echo e(setting('social_facebook',''));?>" placeholder="https://facebook.com/yourpage">
    </div>
    <div style="font-size:.75rem;color:var(--c-text3)">Leave blank to hide that icon from the footer.</div>
  </div>
</div>

<!-- Content Display -->
<div class="ac" style="margin-bottom:18px">
  <div class="ac-head"><span>Content Display</span></div>
  <div style="padding:18px">
    <div class="afr">
      <div class="afg">
        <label class="afl">Homepage Items Per Page</label>
        <input type="number" name="homepage_count" class="afc" value="<?php echo e(setting('homepage_count','20'));?>" min="8" max="40" step="4">
        <div style="font-size:.73rem;color:var(--c-text3);margin-top:4px">Latest Releases on homepage (8–40)</div>
      </div>
      <div class="afg">
        <label class="afl">Browse Pages Items Per Page</label>
        <input type="number" name="items_per_page" class="afc" value="<?php echo e(setting('items_per_page','20'));?>" min="8" max="40" step="4">
        <div style="font-size:.73rem;color:var(--c-text3);margin-top:4px">Movies / Series / Anime pages (8–40)</div>
      </div>
    </div>
  </div>
</div>

<!-- Access Control -->
<div class="ac" style="margin-bottom:18px">
  <div class="ac-head"><span>Access Control</span></div>
  <div style="padding:18px;display:flex;flex-direction:column;gap:14px">
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--c-bg3);border-radius:9px">
      <div>
        <div style="font-weight:600;color:var(--c-text);margin-bottom:3px">Homepage Hero Slider</div>
        <div style="font-size:.78rem;color:var(--c-text3)">Turn ON/OFF the trending hero slider on homepage</div>
      </div>
      <label class="toggle"><input type="checkbox" name="show_hero_slider" value="1" <?php echo setting('show_hero_slider','1')?'checked':'';?>><span class="toggle-sl"></span></label>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--c-bg3);border-radius:9px">
      <div>
        <div style="font-weight:600;color:var(--c-text);margin-bottom:3px">User Registration</div>
        <div style="font-size:.78rem;color:var(--c-text3)">When OFF — Sign In &amp; Register buttons are hidden from the site</div>
      </div>
      <label class="toggle"><input type="checkbox" name="allow_registration" value="1" <?php echo setting('allow_registration','1')?'checked':'';?>><span class="toggle-sl"></span></label>
    </div>
    <div style="display:flex;align-items:center;justify-content:space-between;padding:14px;background:var(--c-bg3);border-radius:9px">
      <div>
        <div style="font-weight:600;color:var(--c-text);margin-bottom:3px">Maintenance Mode</div>
        <div style="font-size:.78rem;color:var(--c-text3)">Show maintenance page to regular visitors</div>
      </div>
      <label class="toggle"><input type="checkbox" name="maintenance_mode" value="1" <?php echo setting('maintenance_mode','0')?'checked':'';?>><span class="toggle-sl"></span></label>
    </div>
  </div>
</div>

<button type="submit" class="abtn abtn-primary"><i class="fas fa-save"></i> Save Settings</button>
</form>
</div>
<script>
var cp=document.getElementById('colorPicker'),ct=document.getElementById('colorText');
cp.addEventListener('input',function(){ct.value=cp.value;});
ct.addEventListener('input',function(){if(/^#[0-9a-f]{6}$/i.test(ct.value))cp.value=ct.value;});
</script>
<?php include __DIR__.'/admin-footer.php'; ?>
