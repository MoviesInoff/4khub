<?php
$adminTitle = 'Embed Servers';
require_once __DIR__.'/admin-header.php';
$msg=''; $msgType='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=isset($_POST['action'])?$_POST['action']:'';
    if($action==='add'||$action==='edit'){
        $name     = trim(isset($_POST['name'])?$_POST['name']:'');
        $mUrl     = trim(isset($_POST['movie_url'])?$_POST['movie_url']:'');
        $tUrl     = trim(isset($_POST['tv_url'])?$_POST['tv_url']:'');
        $active   = isset($_POST['is_active'])?1:0;
        $order    = intval(isset($_POST['sort_order'])?$_POST['sort_order']:0);
        $useImdb  = isset($_POST['use_imdb_id'])?1:0;
        if($name){
            if($action==='edit'&&isset($_POST['sid'])){
                DB::exec("UPDATE embed_servers SET name=?,movie_url=?,tv_url=?,is_active=?,sort_order=?,use_imdb_id=? WHERE id=?",
                    array($name,$mUrl,$tUrl,$active,$order,$useImdb,intval($_POST['sid'])));
                $msg='Updated!';
            } else {
                DB::insert("INSERT INTO embed_servers(name,movie_url,tv_url,is_active,sort_order,use_imdb_id) VALUES(?,?,?,?,?,?)",
                    array($name,$mUrl,$tUrl,$active,$order,$useImdb));
                $msg='Server added!';
            }
            $msgType='success';
        }
    } elseif($action==='delete'&&isset($_POST['sid'])){
        DB::exec("DELETE FROM embed_servers WHERE id=?",array(intval($_POST['sid'])));
        $msg='Deleted.'; $msgType='success';
    } elseif($action==='toggle'&&isset($_POST['sid'])){
        $cur=DB::row("SELECT is_active FROM embed_servers WHERE id=?",array(intval($_POST['sid'])));
        if($cur) DB::exec("UPDATE embed_servers SET is_active=? WHERE id=?",array($cur['is_active']?0:1,intval($_POST['sid'])));
        $msg='Updated.'; $msgType='success';
    }
}
$editSrv=null;
if(isset($_GET['edit'])) $editSrv=DB::row("SELECT * FROM embed_servers WHERE id=?",array(intval($_GET['edit'])));
$servers=DB::rows("SELECT * FROM embed_servers ORDER BY sort_order ASC,id ASC");
?>
<?php if($msg): ?><div class="aalert aalert-<?php echo $msgType;?>"><i class="fas fa-check-circle"></i> <?php echo $msg;?></div><?php endif; ?>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">
<div class="ac">
  <div class="ac-head"><span>Servers (<?php echo count($servers);?>)</span></div>
  <?php if(empty($servers)): ?>
  <div style="padding:30px;text-align:center;color:var(--c-text3)">No servers yet. Add one.</div>
  <?php else: ?>
  <div style="overflow-x:auto"><table class="atable">
    <thead><tr><th>Name</th><th>ID Mode</th><th>Status</th><th>Order</th><th></th></tr></thead>
    <tbody>
    <?php foreach($servers as $s): ?>
    <tr>
      <td>
        <strong><?php echo e($s['name']);?></strong>
        <div style="font-size:.72rem;color:var(--c-text4);margin-top:2px"><?php echo e(substr($s['movie_url']??'',0,45));?>...</div>
      </td>
      <td>
        <span style="background:<?php echo ($s['use_imdb_id']??0)?'rgba(251,191,36,.15)':'rgba(99,102,241,.15)';?>;color:<?php echo ($s['use_imdb_id']??0)?'#fbbf24':'#818cf8';?>;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700">
          <?php echo ($s['use_imdb_id']??0)?'IMDb':'TMDB';?>
        </span>
      </td>
      <td><span style="background:<?php echo $s['is_active']?'rgba(34,197,94,.12)':'rgba(107,114,128,.12)';?>;color:<?php echo $s['is_active']?'#22c55e':'#9ca3af';?>;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo $s['is_active']?'Active':'Off';?></span></td>
      <td><?php echo $s['sort_order'];?></td>
      <td><div style="display:flex;gap:5px">
        <a href="?edit=<?php echo $s['id'];?>" class="abtn abtn-sm"><i class="fas fa-edit"></i></a>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle"><input type="hidden" name="sid" value="<?php echo $s['id'];?>"><button type="submit" class="abtn abtn-sm"><i class="fas fa-power-off"></i></button></form>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="sid" value="<?php echo $s['id'];?>"><button type="submit" class="abtn abtn-danger abtn-sm"><i class="fas fa-trash"></i></button></form>
      </div></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php endif; ?>
</div>
<div class="ac">
  <div class="ac-head"><span><?php echo $editSrv?'Edit Server':'Add Server';?></span><?php if($editSrv): ?><a href="/admin/embed-servers.php" class="abtn abtn-sm">Cancel</a><?php endif; ?></div>
  <div style="padding:18px">
  <form method="POST">
    <input type="hidden" name="action" value="<?php echo $editSrv?'edit':'add';?>">
    <?php if($editSrv): ?><input type="hidden" name="sid" value="<?php echo $editSrv['id'];?>"><?php endif; ?>
    <div class="afg"><label class="afl">Server Name *</label><input type="text" name="name" class="afc" value="<?php echo e($editSrv?$editSrv['name']:'');?>" placeholder="e.g. VidSrc" required></div>

    <div class="afg" style="background:var(--c-bg3);border-radius:8px;padding:12px;border:1px solid var(--c-border)">
      <label class="afl" style="margin-bottom:8px;display:block">ID Type Used by This Server</label>
      <div style="display:flex;gap:16px">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem;color:var(--c-text2)">
          <input type="radio" name="use_imdb_id" value="0" <?php echo (!$editSrv||!($editSrv['use_imdb_id']??0))?'checked':'';?> style="accent-color:var(--c-primary)">
          <span><strong style="color:var(--c-text)">TMDB ID</strong> — use <code style="color:var(--c-primary)">{tmdb_id}</code> placeholder</span>
        </label>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:.875rem;color:var(--c-text2)">
          <input type="radio" name="use_imdb_id" value="1" <?php echo ($editSrv&&($editSrv['use_imdb_id']??0))?'checked':'';?> style="accent-color:var(--c-primary)">
          <span><strong style="color:var(--c-text)">IMDb ID</strong> — use <code style="color:var(--c-primary)">{imdb_id}</code> placeholder</span>
        </label>
      </div>
      <div class="afh" style="margin-top:6px">Choose based on what ID the embed URL expects. IMDb IDs look like <em>tt1234567</em>.</div>
    </div>

    <div class="afg">
      <label class="afl">Movie Embed URL</label>
      <input type="text" name="movie_url" class="afc" value="<?php echo e($editSrv?$editSrv['movie_url']:'');?>" placeholder="https://vidsrc.to/embed/movie/{tmdb_id}">
      <div class="afh">Use <code style="color:var(--c-primary)">{tmdb_id}</code> or <code style="color:var(--c-primary)">{imdb_id}</code> as placeholder</div>
    </div>
    <div class="afg">
      <label class="afl">TV Show Embed URL</label>
      <input type="text" name="tv_url" class="afc" value="<?php echo e($editSrv?$editSrv['tv_url']:'');?>" placeholder="https://vidsrc.to/embed/tv/{tmdb_id}/{season}/{episode}">
      <div class="afh">Use <code style="color:var(--c-primary)">{tmdb_id}</code> or <code style="color:var(--c-primary)">{imdb_id}</code>, plus <code style="color:var(--c-primary)">{season}</code>, <code style="color:var(--c-primary)">{episode}</code></div>
    </div>
    <div class="afg"><label class="afl">Sort Order</label><input type="number" name="sort_order" class="afc" value="<?php echo $editSrv?$editSrv['sort_order']:0;?>" style="max-width:100px"></div>
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px"><label class="toggle"><input type="checkbox" name="is_active" value="1" <?php echo(!$editSrv||$editSrv['is_active'])?'checked':'';?>><span class="toggle-sl"></span></label><span style="font-size:.875rem;color:var(--c-text2)">Active</span></div>
    <button type="submit" class="abtn abtn-primary"><i class="fas fa-save"></i> <?php echo $editSrv?'Update':'Add Server';?></button>
  </form>
  <div style="margin-top:16px;padding:12px;background:var(--c-bg3);border-radius:8px;border-left:3px solid var(--c-blue)">
    <div style="font-size:.78rem;color:var(--c-text3)"><strong style="color:var(--c-blue)">Free embed sources:</strong><br>
    TMDB: <code style="color:var(--c-primary);font-size:.72rem">vidsrc.to/embed/movie/{tmdb_id}</code><br>
    TMDB TV: <code style="color:var(--c-primary);font-size:.72rem">vidsrc.to/embed/tv/{tmdb_id}/{season}/{episode}</code><br>
    IMDb: <code style="color:var(--c-primary);font-size:.72rem">www.2embed.cc/embed/{imdb_id}</code></div>
  </div>
  </div>
</div>
</div>
<?php include __DIR__.'/admin-footer.php'; ?>
