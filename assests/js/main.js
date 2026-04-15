// CineHub - Main JS
(function(){
'use strict';

// Header scroll
var header = document.querySelector('.header');
if(header){
  window.addEventListener('scroll', function(){
    header.classList.toggle('scrolled', window.scrollY > 10);
  });
}

// Mobile drawer
var hamburger = document.querySelector('.hamburger');
var drawer    = document.querySelector('.drawer');
var drawerOverlay = document.querySelector('.drawer-overlay');
function openDrawer(){if(drawer){drawer.classList.add('open');if(drawerOverlay)drawerOverlay.classList.add('show');document.body.style.overflow='hidden';}}
function closeDrawer(){if(drawer){drawer.classList.remove('open');if(drawerOverlay)drawerOverlay.classList.remove('show');document.body.style.overflow='';}}
if(hamburger) hamburger.addEventListener('click', openDrawer);
if(drawerOverlay) drawerOverlay.addEventListener('click', closeDrawer);
document.querySelectorAll('.drawer-close').forEach(function(b){b.addEventListener('click',closeDrawer);});

// User dropdown
var userBtn  = document.querySelector('.user-btn');
var userMenu = document.querySelector('.user-menu');
if(userBtn && userMenu){
  userBtn.addEventListener('click',function(e){e.stopPropagation();userMenu.classList.toggle('show');});
  document.addEventListener('click',function(){if(userMenu)userMenu.classList.remove('show');});
}

// Search
var searchInput = document.querySelector('.search-input');
var searchDrop  = document.querySelector('.search-results-drop');
var searchTimer;
if(searchInput){
  searchInput.addEventListener('input', function(){
    clearTimeout(searchTimer);
    var q = searchInput.value.trim();
    if(q.length < 2){if(searchDrop)searchDrop.classList.remove('show');return;}
    searchTimer = setTimeout(function(){doSearch(q);}, 380);
  });
  searchInput.addEventListener('keydown', function(e){
    if(e.key==='Enter' && searchInput.value.trim()){
      window.location = '/search.php?q=' + encodeURIComponent(searchInput.value.trim());
    }
    if(e.key==='Escape'){if(searchDrop)searchDrop.classList.remove('show');searchInput.blur();}
  });
  document.addEventListener('click', function(e){
    if(!searchInput.contains(e.target)&&searchDrop&&!searchDrop.contains(e.target)){
      searchDrop.classList.remove('show');
    }
  });
}

function doSearch(q){
  if(!searchDrop) return;
  searchDrop.innerHTML = '<div style="padding:16px 20px;color:var(--text3);font-size:.85rem">Searching...</div>';
  searchDrop.classList.add('show');
  fetch('/api/search.php?q=' + encodeURIComponent(q) + '&limit=8')
    .then(function(r){return r.json();})
    .then(function(data){
      if(!data.results||!data.results.length){
        searchDrop.innerHTML='<div style="padding:16px 20px;color:var(--text3);font-size:.85rem">No results found</div>';
        return;
      }
      var html = data.results.map(function(m){
        var poster = m.poster_path ? 'https://image.tmdb.org/t/p/w92'+m.poster_path : '/assets/images/no-poster.jpg';
        var type   = m.media_type||'movie';
        var title  = m.title||m.name||'';
        var year   = (m.release_date||m.first_air_date||'').slice(0,4);
        var url    = type==='tv' ? '/series.php?id='+m.id : '/movie.php?id='+m.id;
        return '<a href="'+url+'" class="sr-item"><img src="'+poster+'" class="sr-poster" alt="" loading="lazy"><div class="sr-info"><div class="sr-title">'+esc(title)+'</div><div class="sr-meta">'+(year?year+' · ':'')+( type==='tv'?'Series':'Movie')+'</div></div></a>';
      }).join('');
      html += '<a href="/search.php?q='+encodeURIComponent(q)+'" style="display:block;padding:12px 20px;text-align:center;color:var(--primary);font-size:.82rem;font-weight:600;border-top:1px solid var(--border)">View all results &rarr;</a>';
      searchDrop.innerHTML = html;
    })
    .catch(function(){searchDrop.innerHTML='<div style="padding:16px 20px;color:var(--text3)">Search failed</div>';});
}

// Password toggle
document.querySelectorAll('.pw-eye').forEach(function(btn){
  btn.addEventListener('click',function(){
    var inp = btn.parentElement.querySelector('input');
    if(!inp) return;
    var isP = inp.type==='password';
    inp.type = isP?'text':'password';
    btn.classList.toggle('fa-eye', isP);
    btn.classList.toggle('fa-eye-slash', !isP);
  });
});

// Download accordion
document.querySelectorAll('.dl-head').forEach(function(h){
  h.addEventListener('click',function(){
    var body = h.parentElement.querySelector('.dl-body');
    if(body){body.classList.toggle('open');}
    var arrow = h.querySelector('.dl-arrow');
    if(arrow) arrow.style.transform = body&&body.classList.contains('open') ? 'rotate(180deg)' : '';
  });
});

// Watchlist toggle
document.addEventListener('click', function(e){
  var btn = e.target.closest('[data-wl]');
  if(!btn) return;
  e.preventDefault();
  var mid = btn.dataset.wl;
  fetch('/api/watchlist.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({media_id:mid})
  }).then(function(r){return r.json();}).then(function(d){
    if(d.redirect){window.location=d.redirect;return;}
    if(d.success){
      toast(d.message, 'success');
      btn.classList.toggle('wl-active', d.added);
      var i = btn.querySelector('i');
      if(i){i.style.color=d.added?'var(--primary)':'';}
    } else { toast(d.message||'Error','error'); }
  }).catch(function(){toast('Network error','error');});
});

// Toast
window.toast = function(msg, type){
  type = type||'info';
  var wrap = document.querySelector('.toast-wrap');
  if(!wrap){wrap=document.createElement('div');wrap.className='toast-wrap';document.body.appendChild(wrap);}
  var icons={success:'fa-check-circle',error:'fa-times-circle',info:'fa-info-circle'};
  var t = document.createElement('div');
  t.className='toast '+type;
  t.innerHTML='<i class="fas '+(icons[type]||icons.info)+'"></i><span class="toast-msg">'+esc(msg)+'</span>';
  wrap.appendChild(t);
  setTimeout(function(){t.classList.add('hide');t.addEventListener('animationend',function(){t.remove();});},3000);
};

// Trailer modal
window.openTrailer = function(key){
  var m=document.getElementById('trailerModal');
  var f=document.getElementById('trailerFrame');
  if(m&&f){f.src='https://www.youtube.com/embed/'+key+'?autoplay=1';m.classList.add('open');document.body.style.overflow='hidden';}
};
window.closeTrailer = function(){
  var m=document.getElementById('trailerModal');
  var f=document.getElementById('trailerFrame');
  if(m){m.classList.remove('open');document.body.style.overflow='';if(f)f.src='';}
};
var tm = document.getElementById('trailerModal');
if(tm) tm.addEventListener('click',function(e){if(e.target===tm)closeTrailer();});

// Lazy images
if('IntersectionObserver' in window){
  var obs = new IntersectionObserver(function(entries){
    entries.forEach(function(en){
      if(en.isIntersecting){var img=en.target;if(img.dataset.src){img.src=img.dataset.src;img.removeAttribute('data-src');}obs.unobserve(img);}
    });
  },{rootMargin:'200px'});
  document.querySelectorAll('img[data-src]').forEach(function(img){obs.observe(img);});
}

function esc(s){
  var map={'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
  return String(s).replace(/[&<>"']/g,function(m){return map[m];});
}

})();
