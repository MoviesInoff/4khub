<?php
$adminTitle = 'Edit Media';
$adminSub   = 'Edit details, tags and download links';
require_once __DIR__.'/admin-header.php';

$id = intval(isset($_GET['id'])?$_GET['id']:0);
if(!$id){ header('Location: /admin/media.php'); exit; }
$media = DB::row("SELECT * FROM media WHERE id=?", array($id));
if(!$media){ header('Location: /admin/media.php'); exit; }

$redir = '/admin/edit-media.php?id='.$id;

if($_SERVER['REQUEST_METHOD']==='POST'){
    $action = isset($_POST['action'])?$_POST['action']:'';

    if($action==='save_meta'){
        $title    = trim(isset($_POST['title'])?$_POST['title']:'');
        $tagline  = trim(isset($_POST['tagline'])?$_POST['tagline']:'');
        $overview = trim(isset($_POST['overview'])?$_POST['overview']:'');
        $year     = trim(isset($_POST['year'])?$_POST['year']:'');
        $runtime  = intval(isset($_POST['runtime'])?$_POST['runtime']:0);
        $rating   = floatval(isset($_POST['vote_average'])?$_POST['vote_average']:0);
        $director = trim(isset($_POST['director'])?$_POST['director']:'');
        $audio    = trim(isset($_POST['audio_languages'])?$_POST['audio_languages']:'');
        $status   = (isset($_POST['status'])&&$_POST['status']==='draft')?'draft':'published';
        $featured = isset($_POST['featured'])?1:0;
        $imdb     = trim(isset($_POST['imdb_id'])?$_POST['imdb_id']:'');
        $cvurl    = trim(isset($_POST['custom_video_url'])?$_POST['custom_video_url']:'');
        $tagsRaw  = trim(isset($_POST['tags'])?$_POST['tags']:'');
        $tagsArr  = $tagsRaw ? array_map('trim', explode(',', $tagsRaw)) : array();
        $tagsArr  = array_filter(array_map(function($t){return trim($t);}, $tagsArr));
        $tagsJson = json_encode(array_values($tagsArr));

        DB::exec("UPDATE media SET title=?,tagline=?,overview=?,year=?,runtime=?,vote_average=?,director=?,audio_languages=?,status=?,featured=?,imdb_id=?,tags=?,custom_video_url=? WHERE id=?",
            array($title,$tagline,$overview,$year,$runtime,$rating,$director,$audio,$status,$featured,$imdb,$tagsJson,$cvurl,$id));
        header('Location: '.$redir.'&saved=1'); exit;

    } elseif($action==='add_download'){
        $dlTitle = trim(isset($_POST['dl_title'])?$_POST['dl_title']:'');
        $quality = trim(isset($_POST['dl_quality'])?$_POST['dl_quality']:'');
        $format  = trim(isset($_POST['dl_format'])?$_POST['dl_format']:'');
        $codec   = trim(isset($_POST['dl_codec'])?$_POST['dl_codec']:'');
        $hdr     = trim(isset($_POST['dl_hdr'])?$_POST['dl_hdr']:'');
        $size    = trim(isset($_POST['dl_size'])?$_POST['dl_size']:'');
        $audio   = trim(isset($_POST['dl_audio'])?$_POST['dl_audio']:'');
        $url     = trim(isset($_POST['dl_url'])?$_POST['dl_url']:'');
        $order   = intval(isset($_POST['dl_order'])?$_POST['dl_order']:0);
        $snum    = intval($_POST['dl_season'] ?? 0) ?: null;
        $enum    = intval($_POST['dl_episode'] ?? 0) ?: null;
        if($dlTitle){
            DB::insert("INSERT INTO download_links (media_id,title,quality,format,codec,hdr,file_size,audio,url,sort_order,season_num,episode_num) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)",
                array($id,$dlTitle,$quality,$format,$codec,$hdr,$size,$audio,$url,$order,$snum,$enum));
        }
        header('Location: '.$redir.'&saved=1'); exit;

    } elseif($action==='delete_download'){
        $dlId = intval(isset($_POST['dl_id'])?$_POST['dl_id']:0);
        if($dlId) DB::exec("DELETE FROM download_links WHERE id=? AND media_id=?", array($dlId,$id));
        header('Location: '.$redir.'&saved=1'); exit;

    } elseif($action==='edit_download'){
        $dlId    = intval($_POST['dl_id'] ?? 0);
        $dlTitle = trim($_POST['dl_title'] ?? '');
        $quality = trim($_POST['dl_quality'] ?? '');
        $format  = trim($_POST['dl_format'] ?? '');
        $codec   = trim($_POST['dl_codec'] ?? '');
        $hdr     = trim($_POST['dl_hdr'] ?? '');
        $size    = trim($_POST['dl_size'] ?? '');
        $audio   = trim($_POST['dl_audio'] ?? '');
        $url     = trim($_POST['dl_url'] ?? '');
        $order   = intval($_POST['dl_order'] ?? 0);
        $snum    = intval($_POST['dl_season'] ?? 0) ?: null;
        $enum    = intval($_POST['dl_episode'] ?? 0) ?: null;
        if($dlId && $dlTitle){
            DB::exec("UPDATE download_links SET title=?,quality=?,format=?,codec=?,hdr=?,file_size=?,audio=?,url=?,sort_order=?,season_num=?,episode_num=? WHERE id=? AND media_id=?",
                array($dlTitle,$quality,$format,$codec,$hdr,$size,$audio,$url,$order,$snum,$enum,$dlId,$id));
        }
        header('Location: '.$redir.'&saved=1'); exit;

    } elseif($action==='delete_media'){
        DB::exec("DELETE FROM download_links WHERE media_id=?", array($id));
        DB::exec("DELETE FROM media WHERE id=?", array($id));
        header('Location: /admin/media.php?deleted=1'); exit;
    }
}

// Re-fetch after possible redirect loop on direct page load
$media     = DB::row("SELECT * FROM media WHERE id=?", array($id));
$downloads = DB::rows("SELECT * FROM download_links WHERE media_id=? ORDER BY sort_order ASC, id ASC", array($id));
$tags      = jd(isset($media['tags'])?$media['tags']:'[]');
$tagsStr   = implode(', ', $tags);
$poster    = $media['poster_path'] ? tmdbImg($media['poster_path'],'w500') : '/assets/images/no-poster.jpg';
$cvurl     = $media['custom_video_url'] ?? '';
?>

<?php if(isset($_GET['saved'])): ?>
<div class="aalert aalert-success"><i class="fas fa-check-circle"></i> Saved successfully!</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:200px 1fr;gap:20px;align-items:start;margin-bottom:20px">
  <div>
    <img src="<?php echo e($poster); ?>" style="width:100%;border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.5)">
    <div style="margin-top:10px;display:flex;flex-direction:column;gap:6px">
      <a href="<?php echo $media['type']==='tv' ? '/series/'.$media['slug'] : '/movie/'.$media['slug']; ?>" target="_blank" class="abtn abtn-sm abtn-full"><i class="fas fa-eye"></i> View Page</a>
      <form method="POST" action="/admin/edit-media.php?id=<?php echo $id;?>" onsubmit="return confirm('Delete this permanently?')">
        <input type="hidden" name="action" value="delete_media">
        <button type="submit" class="abtn abtn-danger abtn-sm abtn-full"><i class="fas fa-trash"></i> Delete</button>
      </form>
    </div>
  </div>
  <div>
    <!-- META FORM -->
    <div class="ac" style="margin-bottom:20px">
      <div class="ac-head"><span><i class="fas fa-info-circle" style="color:var(--c-primary);margin-right:8px"></i>Media Details</span>
        <span style="font-size:.75rem;color:var(--c-text3)">TMDB ID: <?php echo $media['tmdb_id']; ?> &middot; <?php echo strtoupper($media['type']==='tv'?'Series':'Movie'); ?></span>
      </div>
      <div style="padding:18px">
      <form method="POST" action="/admin/edit-media.php?id=<?php echo $id;?>">
        <input type="hidden" name="action" value="save_meta">
        <div class="afr">
          <div class="afg"><label class="afl">Title *</label><input type="text" name="title" class="afc" value="<?php echo e($media['title']); ?>" required></div>
          <div class="afg"><label class="afl">Year</label><input type="text" name="year" class="afc" value="<?php echo e($media['year']); ?>" maxlength="4"></div>
        </div>
        <div class="afg"><label class="afl">Tagline</label><input type="text" name="tagline" class="afc" value="<?php echo e($media['tagline']); ?>"></div>
        <div class="afg"><label class="afl">Overview</label><textarea name="overview" class="afc" rows="4"><?php echo e($media['overview']); ?></textarea></div>
        <div class="afr">
          <div class="afg"><label class="afl">Rating (0-10)</label><input type="number" name="vote_average" class="afc" value="<?php echo e($media['vote_average']); ?>" step="0.1" min="0" max="10"></div>
          <div class="afg"><label class="afl">Runtime (minutes)</label><input type="number" name="runtime" class="afc" value="<?php echo e($media['runtime']); ?>"></div>
        </div>
        <div class="afr">
          <div class="afg"><label class="afl">Director</label><input type="text" name="director" class="afc" value="<?php echo e($media['director']); ?>"></div>
          <div class="afg"><label class="afl">IMDb ID</label><input type="text" name="imdb_id" class="afc" value="<?php echo e($media['imdb_id']); ?>" placeholder="tt1234567"></div>
        </div>
        <div class="afg">
          <label class="afl">Audio Languages</label>
          <input type="text" name="audio_languages" class="afc" value="<?php echo e($media['audio_languages']); ?>" placeholder="Hindi | Tamil | Telugu | English">
          <div class="afh">Shown on detail page as download audio info</div>
        </div>
        <div class="afg">
          <label class="afl">Tags <span style="color:var(--c-text4)">(comma separated)</span></label>
          <input type="text" name="tags" class="afc" value="<?php echo e($tagsStr); ?>" placeholder="4K, HDR, DV, 1080p, WEB-DL, Blu-Ray">
          <div class="afh">These show as colored badges on cards and detail page. Examples: 4K, HDR, DV, 1080p, FHD, WEB-DL, BluRay</div>
        </div>
        <div class="afg">
          <label class="afl">Custom Video URL <span style="color:var(--c-text4)">(optional — your own video host)</span></label>
          <input type="url" name="custom_video_url" class="afc" value="<?php echo e($cvurl); ?>" placeholder="https://vhost.com/your-video-id">
          <div class="afh">If set, this URL will be used as the player source instead of embed servers. Use for self-hosted videos.</div>
        </div>
        <div class="afr" style="align-items:center">
          <div class="afg">
            <label class="afl">Status</label>
            <select name="status" class="afc">
              <option value="published" <?php echo $media['status']==='published'?'selected':''; ?>>Published</option>
              <option value="draft"     <?php echo $media['status']==='draft'?'selected':''; ?>>Draft</option>
            </select>
          </div>
          <div class="afg" style="display:flex;align-items:center;gap:10px;padding-top:20px">
            <label class="toggle">
              <input type="checkbox" name="featured" value="1" <?php echo $media['featured']?'checked':''; ?>>
              <span class="toggle-sl"></span>
            </label>
            <span style="font-size:.875rem;color:var(--c-text2)">Featured (show in hero)</span>
          </div>
        </div>
        <button type="submit" class="abtn abtn-primary"><i class="fas fa-save"></i> Save Changes</button>
      </form>
      </div>
    </div>
  </div>
</div>

<!-- DOWNLOAD LINKS SECTION -->
<div class="ac">
  <div class="ac-head"><span><i class="fas fa-download" style="color:var(--c-primary);margin-right:8px"></i>Download Links (<?php echo count($downloads); ?>)</span></div>
  <div style="padding:18px">

    <?php if(!empty($downloads)): ?>
    <div style="margin-bottom:20px">
      <?php foreach($downloads as $dl): ?>
      <div style="background:var(--c-bg3);border:1px solid var(--c-border);border-radius:10px;padding:14px 16px;margin-bottom:10px">
        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin-bottom:8px">
          <div style="flex:1;min-width:0">
            <div style="font-weight:700;color:var(--c-text);margin-bottom:6px"><?php echo e($dl['title']); ?></div>
            <div style="display:flex;flex-wrap:wrap;gap:4px">
              <?php if(!empty($dl['season_num'])): ?><span style="background:var(--c-bg5);color:var(--c-text2);padding:2px 7px;border-radius:4px;font-size:.7rem;font-weight:700">S<?php echo $dl['season_num'];?><?php if(!empty($dl['episode_num'])): ?> E<?php echo $dl['episode_num'];?><?php endif;?></span><?php endif;?>
              <?php if($dl['file_size']): ?><span style="background:var(--c-primary);color:#000;padding:2px 7px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo e($dl['file_size']); ?></span><?php endif; ?>
              <?php if($dl['quality']): ?><span style="background:rgba(59,130,246,.15);color:#3b82f6;padding:2px 7px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo e($dl['quality']); ?></span><?php endif; ?>
              <?php if($dl['hdr']): ?><span style="background:rgba(168,85,247,.15);color:#a855f7;padding:2px 7px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo e($dl['hdr']); ?></span><?php endif; ?>
              <?php if($dl['format']): ?><span style="background:rgba(34,197,94,.15);color:#22c55e;padding:2px 7px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo e($dl['format']); ?></span><?php endif; ?>
              <?php if($dl['audio']): ?><span style="background:var(--c-bg5);color:var(--c-text2);padding:2px 7px;border-radius:4px;font-size:.7rem"><?php echo e($dl['audio']); ?></span><?php endif; ?>
            </div>
            <?php if($dl['url']): ?><div style="font-size:.72rem;color:var(--c-text4);margin-top:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo e(explode("\n",trim($dl['url']))[0]); ?></div><?php endif; ?>
          </div>
          <div style="display:flex;gap:5px;flex-shrink:0">
            <button type="button" class="abtn abtn-sm" onclick="toggleEdit(<?php echo $dl['id'];?>)"><i class="fas fa-edit"></i></button>
            <form method="POST" action="/admin/edit-media.php?id=<?php echo $id;?>" onsubmit="return confirm('Delete this link?')" style="display:inline">
              <input type="hidden" name="action" value="delete_download">
              <input type="hidden" name="dl_id" value="<?php echo $dl['id']; ?>">
              <button type="submit" class="abtn abtn-danger abtn-sm"><i class="fas fa-trash"></i></button>
            </form>
          </div>
        </div>
        <div id="edit-<?php echo $dl['id'];?>" style="display:none;border-top:1px solid var(--c-border);padding-top:12px;margin-top:4px">
          <form method="POST" action="/admin/edit-media.php?id=<?php echo $id;?>">
            <input type="hidden" name="action" value="edit_download">
            <input type="hidden" name="dl_id" value="<?php echo $dl['id'];?>">
            <div class="afg"><label class="afl">Title</label><input type="text" name="dl_title" class="afc" value="<?php echo e($dl['title']);?>" required></div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
              <div class="afg"><label class="afl">Season #</label><input type="number" name="dl_season" class="afc" value="<?php echo e($dl['season_num']??'');?>" min="0" placeholder="0=all"></div>
              <div class="afg"><label class="afl">Episode #</label><input type="number" name="dl_episode" class="afc" value="<?php echo e($dl['episode_num']??'');?>" min="0" placeholder="0=all"></div>
              <div class="afg"><label class="afl">File Size</label><input type="text" name="dl_size" class="afc" value="<?php echo e($dl['file_size']??'');?>"></div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px">
              <div class="afg"><label class="afl">Quality</label><select name="dl_quality" class="afc"><option value="">-</option><option <?php echo $dl['quality']==='2160p'?'selected':'';?>>2160p</option><option <?php echo $dl['quality']==='1080p'?'selected':'';?>>1080p</option><option <?php echo $dl['quality']==='720p'?'selected':'';?>>720p</option><option <?php echo $dl['quality']==='480p'?'selected':'';?>>480p</option></select></div>
              <div class="afg"><label class="afl">Format</label><select name="dl_format" class="afc"><option value="">-</option><option <?php echo $dl['format']==='WEB-DL'?'selected':'';?>>WEB-DL</option><option <?php echo $dl['format']==='BluRay'?'selected':'';?>>BluRay</option><option <?php echo $dl['format']==='WEBRip'?'selected':'';?>>WEBRip</option></select></div>
              <div class="afg"><label class="afl">HDR</label><select name="dl_hdr" class="afc"><option value="">None</option><option <?php echo $dl['hdr']==='DV HDR'?'selected':'';?>>DV HDR</option><option <?php echo $dl['hdr']==='HDR10+'?'selected':'';?>>HDR10+</option><option <?php echo $dl['hdr']==='HDR10'?'selected':'';?>>HDR10</option></select></div>
            </div>
            <div class="afg"><label class="afl">Audio Languages</label><input type="text" name="dl_audio" class="afc" value="<?php echo e($dl['audio']??'');?>"></div>
            <div class="afg"><label class="afl">Download URLs (one per line)</label><textarea name="dl_url" class="afc" rows="4"><?php echo e($dl['url']??'');?></textarea></div>
            <div style="display:flex;gap:8px">
              <button type="submit" class="abtn abtn-primary abtn-sm"><i class="fas fa-save"></i> Save Changes</button>
              <button type="button" class="abtn abtn-sm" onclick="toggleEdit(<?php echo $dl['id'];?>)">Cancel</button>
            </div>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <script>function toggleEdit(id){var el=document.getElementById('edit-'+id);el.style.display=el.style.display==='none'?'block':'none';}</script>

    <!-- ADD DOWNLOAD LINK FORM -->
    <div style="background:var(--c-bg3);border:1px solid var(--c-border);border-radius:10px;padding:16px">
      <div style="font-weight:700;color:var(--c-text);margin-bottom:14px"><i class="fas fa-plus" style="color:var(--c-primary);margin-right:6px"></i>Add Download Link</div>
      <form method="POST" action="/admin/edit-media.php?id=<?php echo $id;?>">
        <input type="hidden" name="action" value="add_download">
        <div class="afg">
          <label class="afl">Title *</label>
          <input type="text" name="dl_title" class="afc" placeholder="e.g. Avatar Fire and Ash (2160p WEB-DL DV HDR H265)" required>
          <div class="afh">This is the main title shown for the download group</div>
        </div>
        <div class="afr3">
          <div class="afg"><label class="afl">Quality</label><select name="dl_quality" class="afc"><option value="">Select</option><option>2160p</option><option>1080p</option><option>720p</option><option>480p</option></select></div>
          <div class="afg"><label class="afl">Format</label><select name="dl_format" class="afc"><option value="">Select</option><option>WEB-DL</option><option>BluRay</option><option>WEBRip</option><option>HDTV</option><option>CAM</option></select></div>
          <div class="afg"><label class="afl">HDR</label><select name="dl_hdr" class="afc"><option value="">None</option><option>DV HDR</option><option>HDR10+</option><option>HDR10</option><option>SDR</option></select></div>
        </div>
        <div class="afr">
          <div class="afg"><label class="afl">Codec</label><select name="dl_codec" class="afc"><option value="">Select</option><option>H265</option><option>x264</option><option>x265</option><option>HEVC</option><option>AV1</option></select></div>
          <div class="afg"><label class="afl">File Size</label><input type="text" name="dl_size" class="afc" placeholder="e.g. 13.18 GB"></div>
        </div>
        <?php if($media['type']==='tv'): ?>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div class="afg">
            <label class="afl">Season # <span style="color:var(--c-text4)">(0 = all seasons)</span></label>
            <input type="number" name="dl_season" class="afc" value="0" min="0">
          </div>
          <div class="afg">
            <label class="afl">Episode # <span style="color:var(--c-text4)">(0 = full season)</span></label>
            <input type="number" name="dl_episode" class="afc" value="0" min="0">
          </div>
        </div>
        <?php endif;?>
        <div class="afg">
          <label class="afl">Audio Languages</label>
          <input type="text" name="dl_audio" class="afc" placeholder="e.g. Hindi, Tamil, Telugu, English">
        </div>
        <div class="afg">
          <label class="afl">Download URL(s)</label>
          <textarea name="dl_url" class="afc" rows="4" placeholder="Enter one URL per line&#10;https://example.com/download1&#10;https://example.com/download2"></textarea>
          <div class="afh">Enter one URL per line. Multiple URLs = multiple download buttons</div>
        </div>
        <div class="afg"><label class="afl">Sort Order</label><input type="number" name="dl_order" class="afc" value="0" min="0" style="max-width:120px"></div>
        <button type="submit" class="abtn abtn-primary"><i class="fas fa-plus"></i> Add Download Link</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__.'/admin-footer.php'; ?>
