<?php
require_once __DIR__.'/includes/core.php';
sess(); if(loggedIn()){header('Location: /index.php');exit;}
if(!setting('allow_registration','1')){header('Location: /login.php');exit;}
$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $uname=trim(isset($_POST['username'])?$_POST['username']:'');
    $email=trim(isset($_POST['email'])?$_POST['email']:'');
    $pass=isset($_POST['password'])?$_POST['password']:'';
    $conf=isset($_POST['confirm'])?$_POST['confirm']:'';
    if(!$uname||!$email||!$pass){$err='All fields required.';}
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL)){$err='Invalid email.';}
    elseif(strlen($pass)<6){$err='Password min 6 characters.';}
    elseif($pass!==$conf){$err='Passwords do not match.';}
    else{
        $ex=DB::row("SELECT id FROM users WHERE email=?",array($email));
        if($ex){$err='Email already registered.';}
        else{
            DB::insert("INSERT INTO users(username,email,password,role) VALUES(?,?,?,'user')",array($uname,$email,password_hash($pass,PASSWORD_DEFAULT)));
            doLogin($email,$pass); header('Location: /index.php'); exit;
        }
    }
}
$sn=setting('site_name','CineHub'); $pc=setting('primary_color','#f97316');
function ac2($h,$a){$h=ltrim($h,'#');$r=max(0,min(255,hexdec(substr($h,0,2))+$a));$g=max(0,min(255,hexdec(substr($h,2,2))+$a));$b=max(0,min(255,hexdec(substr($h,4,2))+$a));return '#'.sprintf('%02x%02x%02x',$r,$g,$b);}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Register - <?php echo e($sn);?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
<style>:root{--primary:<?php echo $pc;?>;--primary-dark:<?php echo ac2(ltrim($pc,'#'),-20);?>;}</style>
</head><body>
<div class="auth-wrap">
<div class="auth-visual">
  <a href="/index.php" class="logo" style="font-size:1.6rem;display:inline-flex;margin-bottom:32px"><div class="logo-icon">C</div><?php echo e($sn);?></a>
  <h2 class="auth-headline">Start watching<br><em>everything</em><br>today.</h2>
  <p class="auth-sub">Create a free account and access thousands of movies and series in HD and 4K.</p>
  <div class="auth-stats">
    <div class="auth-stat"><strong>10K+</strong><span>Titles</span></div>
    <div class="auth-stat"><strong>Free</strong><span>Forever</span></div>
    <div class="auth-stat"><strong>4K</strong><span>Quality</span></div>
  </div>
</div>
<div class="auth-panel">
  <h1 class="auth-title">Create account</h1>
  <p class="auth-sub2">Fill in your details to get started</p>
  <?php if($err): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo e($err);?></div><?php endif;?>
  <form method="POST">
    <div class="form-group"><label class="form-label">Username</label><input type="text" name="username" class="form-control" placeholder="Your username" value="<?php echo e(isset($_POST['username'])?$_POST['username']:'');?>" required></div>
    <div class="form-group"><label class="form-label">Email</label><input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?php echo e(isset($_POST['email'])?$_POST['email']:'');?>" required></div>
    <div class="form-row">
      <div class="form-group"><label class="form-label">Password</label><div style="position:relative"><input type="password" name="password" class="form-control" placeholder="Min 6 chars" style="padding-right:40px" required minlength="6"><i class="fas fa-eye-slash pw-eye" style="position:absolute;right:13px;top:50%;transform:translateY(-50%);color:var(--text3);font-size:.85rem;cursor:pointer"></i></div></div>
      <div class="form-group"><label class="form-label">Confirm</label><input type="password" name="confirm" class="form-control" placeholder="Repeat" required></div>
    </div>
    <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-bottom:14px;border-radius:10px">Create Account <i class="fas fa-arrow-right"></i></button>
  </form>
  <div class="auth-footer">Already have an account? <a href="/login.php">Sign in</a></div>
  <div style="text-align:center;margin-top:12px"><a href="/index.php" style="font-size:.82rem;color:var(--text3)"><i class="fas fa-arrow-left"></i> Back to Home</a></div>
</div>
</div>
<script src="/assets/js/main.js"></script>
</body></html>
