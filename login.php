<?php
require_once __DIR__.'/includes/core.php';
sess(); if(loggedIn()){header('Location: /index.php');exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim(isset($_POST['email'])?$_POST['email']:'');
    $pass=isset($_POST['password'])?$_POST['password']:'';
    if(!$email||!$pass){$err='Please fill all fields.';}
    elseif(!doLogin($email,$pass)){$err='Invalid email or password.';}
    else{header('Location: /index.php');exit;}
}
$sn=setting('site_name','CineHub'); $pc=setting('primary_color','#f97316');
function ac($h,$a){$h=ltrim($h,'#');$r=max(0,min(255,hexdec(substr($h,0,2))+$a));$g=max(0,min(255,hexdec(substr($h,2,2))+$a));$b=max(0,min(255,hexdec(substr($h,4,2))+$a));return '#'.sprintf('%02x%02x%02x',$r,$g,$b);}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Sign In - <?php echo e($sn);?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
<style>:root{--primary:<?php echo $pc;?>;--primary-dark:<?php echo ac(ltrim($pc,'#'),-20);?>;}</style>
</head><body>
<div class="auth-wrap">
<div class="auth-visual">
  <a href="/index.php" class="logo" style="font-size:1.6rem;display:inline-flex;margin-bottom:32px"><div class="logo-icon">C</div><?php echo e($sn);?></a>
  <h2 class="auth-headline">Your world of<br><em>entertainment</em><br>awaits.</h2>
  <p class="auth-sub">Stream movies, series and anime. Free, always.</p>
  <div class="auth-stats">
    <div class="auth-stat"><strong>10K+</strong><span>Titles</span></div>
    <div class="auth-stat"><strong>Free</strong><span>Always</span></div>
    <div class="auth-stat"><strong>4K</strong><span>Quality</span></div>
  </div>
</div>
<div class="auth-panel">
  <h1 class="auth-title">Welcome back</h1>
  <p class="auth-sub2">Sign in to continue watching</p>
  <?php if($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo e($err);?></div><?php endif;?>
  <form method="POST">
    <div class="form-group"><label class="form-label">Email</label><div style="position:relative"><i class="fas fa-envelope" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.85rem"></i><input type="email" name="email" class="form-control" placeholder="you@example.com" style="padding-left:36px" value="<?php echo e(isset($_POST['email'])?$_POST['email']:'');?>" required></div></div>
    <div class="form-group"><label class="form-label">Password</label><div style="position:relative"><i class="fas fa-lock" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.85rem"></i><input type="password" name="password" class="form-control" placeholder="Your password" style="padding-left:36px;padding-right:40px" required><i class="fas fa-eye-slash pw-eye" style="position:absolute;right:13px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.85rem;cursor:pointer"></i></div></div>
    <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-bottom:14px;border-radius:10px">Sign In <i class="fas fa-arrow-right"></i></button>
  </form>
  <div class="auth-footer">Don't have an account? <a href="/register.php">Register free</a></div>
  <div style="text-align:center;margin-top:12px"><a href="/index.php" style="font-size:.82rem;color:var(--text3)"><i class="fas fa-arrow-left"></i> Back to Home</a></div>
</div>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
