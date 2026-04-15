</div><!-- end admin-content -->
</main><!-- end admin-main -->
</div><!-- end admin-layout -->

<script>
function aToast(msg,type){
  type=type||'success';
  var colors={success:'var(--c-green)',error:'var(--c-red)',info:'var(--c-blue)'};
  var icons={success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle'};
  var w=document.querySelector('.atoast-wrap')||document.createElement('div');
  w.className='atoast-wrap';
  Object.assign(w.style,{position:'fixed',bottom:'20px',right:'20px',zIndex:'9999',display:'flex',flexDirection:'column',gap:'8px'});
  document.body.appendChild(w);
  var t=document.createElement('div');
  t.style.cssText='background:var(--c-bg3);border:1px solid var(--c-border2);border-radius:10px;padding:11px 15px;min-width:240px;display:flex;align-items:center;gap:10px;box-shadow:0 8px 24px rgba(0,0,0,.5);animation:aToastIn .3s ease';
  t.innerHTML='<i class="fas '+(icons[type]||icons.success)+'" style="color:'+(colors[type]||colors.success)+';font-size:.95rem;flex-shrink:0"></i><span style="font-size:.845rem;color:var(--c-text2)">'+msg+'</span>';
  var st=document.createElement('style');st.textContent='@keyframes aToastIn{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}@keyframes aToastOut{to{opacity:0;transform:translateX(110%)}}';document.head.appendChild(st);
  w.appendChild(t);
  setTimeout(function(){t.style.animation='aToastOut .3s ease forwards';t.addEventListener('animationend',function(){t.remove();});},3000);
}
</script>
</body></html>
