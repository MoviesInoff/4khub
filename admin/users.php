<?php
$adminTitle = 'Users';
require_once __DIR__.'/admin-header.php';
$msg=''; $msgType='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $action=isset($_POST['action'])?$_POST['action']:'';
    $uid=intval(isset($_POST['uid'])?$_POST['uid']:0);
    if($uid&&$uid!=$_SESSION['uid']){
        if($action==='toggle_role'){$u=DB::row("SELECT role FROM users WHERE id=?",array($uid));if($u){DB::exec("UPDATE users SET role=? WHERE id=?",array($u['role']==='admin'?'user':'admin',$uid));$msg='Role updated.';$msgType='success';}}
        elseif($action==='toggle_active'){$u=DB::row("SELECT is_active FROM users WHERE id=?",array($uid));if($u){DB::exec("UPDATE users SET is_active=? WHERE id=?",array($u['is_active']?0:1,$uid));$msg='Status updated.';$msgType='success';}}
        elseif($action==='delete'){DB::exec("DELETE FROM users WHERE id=?",array($uid));$msg='Deleted.';$msgType='success';}
    }
}
$users=DB::rows("SELECT * FROM users ORDER BY created_at DESC");
?>
<?php if($msg): ?><div class="aalert aalert-<?php echo $msgType;?>"><i class="fas fa-check-circle"></i> <?php echo $msg;?></div><?php endif; ?>
<div class="ac">
  <div class="ac-head"><span>All Users (<?php echo count($users);?>)</span></div>
  <div style="overflow-x:auto"><table class="atable">
    <thead><tr><th>User</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
    <tbody>
    <?php foreach($users as $u):
      $isSelf=($u['id']==$_SESSION['uid']);
    ?>
    <tr>
      <td><div style="display:flex;align-items:center;gap:9px"><div style="width:30px;height:30px;border-radius:50%;background:var(--c-primary);color:#000;font-weight:700;font-size:.8rem;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?php echo strtoupper(substr($u['username'],0,1));?></div><strong><?php echo e($u['username']);?><?php if($isSelf): ?> <span style="color:var(--c-primary);font-size:.7rem">(You)</span><?php endif;?></strong></div></td>
      <td style="color:var(--c-text3);font-size:.82rem"><?php echo e($u['email']);?></td>
      <td><span style="background:<?php echo $u['role']==='admin'?'rgba(249,115,22,.12)':'rgba(107,114,128,.12)';?>;color:<?php echo $u['role']==='admin'?'#f97316':'#9ca3af';?>;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo ucfirst($u['role']);?></span></td>
      <td><span style="background:<?php echo $u['is_active']?'rgba(34,197,94,.12)':'rgba(239,68,68,.12)';?>;color:<?php echo $u['is_active']?'#22c55e':'#ef4444';?>;padding:2px 8px;border-radius:4px;font-size:.7rem;font-weight:700"><?php echo $u['is_active']?'Active':'Banned';?></span></td>
      <td style="color:var(--c-text3);font-size:.8rem"><?php echo date('M d Y',strtotime($u['created_at']));?></td>
      <td><?php if(!$isSelf): ?><div style="display:flex;gap:5px">
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle_role"><input type="hidden" name="uid" value="<?php echo $u['id'];?>"><button type="submit" class="abtn abtn-sm" title="Toggle role"><i class="fas fa-user-cog"></i></button></form>
        <form method="POST" style="display:inline"><input type="hidden" name="action" value="toggle_active"><input type="hidden" name="uid" value="<?php echo $u['id'];?>"><button type="submit" class="abtn abtn-sm"><i class="fas fa-ban"></i></button></form>
        <form method="POST" style="display:inline" onsubmit="return confirm('Delete user?')"><input type="hidden" name="action" value="delete"><input type="hidden" name="uid" value="<?php echo $u['id'];?>"><button type="submit" class="abtn abtn-danger abtn-sm"><i class="fas fa-trash"></i></button></form>
      </div><?php else: echo '<span style="color:var(--c-text4)">—</span>';endif;?></td>
    </tr>
    <?php endforeach;?>
    </tbody>
  </table></div>
</div>
<?php include __DIR__.'/admin-footer.php'; ?>
