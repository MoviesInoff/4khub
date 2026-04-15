<?php
$sn  = setting('site_name','CineHub');
$pc  = setting('primary_color','#f97316');
$tg  = setting('social_telegram','');
$tw  = setting('social_twitter','');
$ig  = setting('social_instagram','');
$yt  = setting('social_youtube','');
$fb  = setting('social_facebook','');
?>
<footer style="background:#0a0a0a;border-top:1px solid rgba(255,255,255,.06);padding:48px 20px 0;margin-top:auto">
  <div style="max-width:1100px;margin:0 auto">

    <div style="display:grid;grid-template-columns:1.8fr 1fr 1fr 1fr;gap:40px;margin-bottom:40px" class="footer-top-grid">

      <!-- Brand -->
      <div>
        <a href="/index.php" style="display:inline-flex;align-items:center;gap:0;text-decoration:none;margin-bottom:16px;font-family:'Bebas Neue',sans-serif;font-size:1.85rem;letter-spacing:2px">
          <span style="color:<?php echo $pc;?>"><?php echo e(strtoupper(substr($sn,0,2)));?></span><span style="color:#f0f0f0"><?php echo e(strtoupper(substr($sn,2)));?></span>
        </a>
        <p style="color:#555;font-size:.82rem;line-height:1.7;max-width:240px;margin-bottom:20px"><?php echo e(setting('site_tagline','Your free entertainment hub for movies, series and anime.')); ?></p>

        <!-- Social icons with brand colors -->
        <div style="display:flex;gap:8px;flex-wrap:wrap">
          <?php if($tg): ?>
          <a href="<?php echo e($tg); ?>" target="_blank" rel="noopener"
             style="width:36px;height:36px;border-radius:50%;background:#229ED9;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none;transition:opacity .2s"
             onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
            <i class="fab fa-telegram"></i>
          </a>
          <?php endif; ?>
          <?php if($tw): ?>
          <a href="<?php echo e($tw); ?>" target="_blank" rel="noopener"
             style="width:36px;height:36px;border-radius:50%;background:#000;border:1px solid #333;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none;transition:opacity .2s"
             onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
            <i class="fab fa-x-twitter"></i>
          </a>
          <?php endif; ?>
          <?php if($ig): ?>
          <a href="<?php echo e($ig); ?>" target="_blank" rel="noopener"
             style="width:36px;height:36px;border-radius:50%;background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fdf497 5%,#fd5949 45%,#d6249f 60%,#285AEB 90%);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none;transition:opacity .2s"
             onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
            <i class="fab fa-instagram"></i>
          </a>
          <?php endif; ?>
          <?php if($yt): ?>
          <a href="<?php echo e($yt); ?>" target="_blank" rel="noopener"
             style="width:36px;height:36px;border-radius:50%;background:#FF0000;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none;transition:opacity .2s"
             onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
            <i class="fab fa-youtube"></i>
          </a>
          <?php endif; ?>
          <?php if($fb): ?>
          <a href="<?php echo e($fb); ?>" target="_blank" rel="noopener"
             style="width:36px;height:36px;border-radius:50%;background:#1877F2;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none;transition:opacity .2s"
             onmouseover="this.style.opacity='.8'" onmouseout="this.style.opacity='1'">
            <i class="fab fa-facebook-f"></i>
          </a>
          <?php endif; ?>
          <?php if(!$tg && !$tw && !$ig && !$yt && !$fb): ?>
          <!-- Placeholder icons shown when no links are set -->
          <a href="/admin/settings.php" style="width:36px;height:36px;border-radius:50%;background:#229ED9;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none"><i class="fab fa-telegram"></i></a>
          <a href="/admin/settings.php" style="width:36px;height:36px;border-radius:50%;background:#000;border:1px solid #333;display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none"><i class="fab fa-x-twitter"></i></a>
          <a href="/admin/settings.php" style="width:36px;height:36px;border-radius:50%;background:radial-gradient(circle at 30% 107%,#fdf497 0%,#fdf497 5%,#fd5949 45%,#d6249f 60%,#285AEB 90%);display:flex;align-items:center;justify-content:center;color:#fff;font-size:.9rem;text-decoration:none"><i class="fab fa-instagram"></i></a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Browse -->
      <div>
        <div style="font-size:.75rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#f0f0f0;margin-bottom:16px">Browse</div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <a href="/movies.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Movies</a>
          <a href="/series.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Web Series</a>
          <a href="/anime.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Anime</a>
          <a href="/genres.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Genres</a>
        </div>
      </div>

      <!-- Account -->
      <div>
        <div style="font-size:.75rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#f0f0f0;margin-bottom:16px">Account</div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php if(loggedIn()): ?>
          <a href="/watchlist.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">My Watchlist</a>
          <a href="/logout.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Sign Out</a>
          <?php else: ?>
          <a href="/login.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Sign In</a>
          <a href="/register.php" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Register Free</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Info -->
      <div>
        <div style="font-size:.75rem;font-weight:700;letter-spacing:.8px;text-transform:uppercase;color:#f0f0f0;margin-bottom:16px">Info</div>
        <div style="display:flex;flex-direction:column;gap:10px">
          <a href="#" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">About Us</a>
          <a href="#" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">DMCA</a>
          <a href="#" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Privacy Policy</a>
          <a href="#" style="color:#555;font-size:.85rem;text-decoration:none;transition:color .2s" onmouseover="this.style.color='<?php echo $pc;?>'" onmouseout="this.style.color='#555'">Contact</a>
        </div>
      </div>
    </div>

    <!-- Disclaimer -->
    <div style="background:#111;border-radius:10px;padding:14px 18px;margin-bottom:24px;font-size:.78rem;color:#3a3a3a;line-height:1.6">
      <strong style="color:#444">Disclaimer:</strong> This site does not store any files on its server. All content is provided by non-affiliated third parties. TMDB data used under their API terms.
    </div>

    <!-- Bottom bar -->
    <div style="border-top:1px solid #161616;padding:18px 0;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
      <div style="color:#333;font-size:.78rem">&copy; <?php echo date('Y'); ?> <?php echo e($sn); ?> &mdash; All rights reserved.</div>
      <div style="display:flex;align-items:center;gap:6px;font-size:.75rem;color:#2a2a2a">
        <img src="https://www.themoviedb.org/assets/2/v4/logos/v2/blue_square_2-d537fb228cf3ded904ef09b136fe3fec72548ebc1fea3fbbd1ad9e36364db38b.svg" alt="TMDB" style="height:16px;opacity:.3">
        <span>Data by TMDB</span>
      </div>
    </div>
  </div>
</footer>

<style>
@media(max-width:768px){.footer-top-grid{grid-template-columns:1fr 1fr!important;gap:28px!important}}
@media(max-width:480px){.footer-top-grid{grid-template-columns:1fr!important}}
</style>

<script src="/assets/js/main.js"></script>
<?php if(!empty($extraJs)) foreach($extraJs as $j): ?><script src="<?php echo $j;?>"></script><?php endforeach; ?>
</body>
</html>
