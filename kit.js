/* LEAP kit-detail page interactions */
(function(){
  var EN = (document.documentElement.lang || '').slice(0,2) === 'en';
  var TX = EN
    ? { missing:'Please enter your name and email.', sending:'Sending …', failed:'Sorry, that didn\'t work. Please try again later or email paul@ich-leaps.at.', endpoint:'../send-waitlist.php' }
    : { missing:'Bitte Name und E-Mail eintragen.', sending:'Wird gesendet …', failed:'Fehler beim Senden.', endpoint:'send-waitlist.php' };

  // --- footer copyright year — always current, no yearly manual edits needed ---
  var yearEl = document.getElementById('copy-year');
  if(yearEl) yearEl.textContent = new Date().getFullYear();

  // --- Slide preview ---
  var slides = window.LEAP_SLIDES || [];
  window.selSlide = function(i){
    document.querySelectorAll('.sthumb').forEach(function(t,j){t.classList.toggle('cur', i===j);});
    var s = slides[i]; if(!s) return;
    var el = document.getElementById('slide-lg');
    el.style.background = s.bg;
    el.innerHTML = '<div>'+s.html+'</div>';
  };

  window.switchPreview = function(id, btn){
    document.querySelectorAll('.ptab').forEach(function(t){t.classList.remove('act');});
    document.querySelectorAll('.ppanel').forEach(function(p){p.classList.remove('act');});
    document.getElementById('pp-'+id).classList.add('act');
    btn.classList.add('act');
  };

  window.selGuide = function(el, id){
    document.querySelectorAll('.gn').forEach(function(n){n.classList.remove('ga');});
    document.querySelectorAll('.guide-main > div').forEach(function(s){s.style.display='none';});
    el.classList.add('ga');
    document.getElementById(id).style.display='block';
  };

  // --- FAQ ---
  window.tfaq = function(el){
    var a = el.nextElementSibling;
    var open = a.classList.contains('op');
    document.querySelectorAll('.fa.op').forEach(function(x){x.classList.remove('op');});
    document.querySelectorAll('.fq.op').forEach(function(x){x.classList.remove('op');});
    if(!open){ a.classList.add('op'); el.classList.add('op'); }
  };

  // --- Modal ---
  window.openModal = function(){
    document.getElementById('modal').classList.add('op');
    document.body.style.overflow='hidden';
    setTimeout(function(){var n=document.getElementById('mname'); if(n) n.focus();},100);
  };
  window.closeModal = function(){
    document.getElementById('modal').classList.remove('op');
    document.body.style.overflow='';
  };
  window.closeOut = function(e){ if(e.target===document.getElementById('modal')) closeModal(); };
  window.submitModal = function(){
    var n=document.getElementById('mname').value.trim();
    var em=document.getElementById('memail').value.trim();
    var errEl=document.getElementById('mform-error');
    errEl.style.display='none';
    if(!n||!em){ errEl.textContent=TX.missing; errEl.style.display='block'; return; }

    var btn=document.getElementById('mform-submit');
    btn.disabled=true;
    var originalLabel=btn.textContent;
    btn.textContent=TX.sending;

    fetch(TX.endpoint, {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        name:n,
        email:em,
        kit: window.LEAP_KIT_ID || '',
        lang: EN ? 'en' : 'de',
        website: document.getElementById('mwebsite').value
      })
    }).then(function(res){ return res.json().then(function(data){ return {ok:res.ok, data:data}; }); })
      .then(function(r){
        if(!r.ok || !r.data.ok){ throw new Error(EN ? TX.failed : ((r.data && r.data.message) || TX.failed)); }
        document.getElementById('mform').style.display='none';
        document.getElementById('msuccess').style.display='block';
      })
      .catch(function(err){
        errEl.textContent=err.message;
        errEl.style.display='block';
        btn.disabled=false;
        btn.textContent=originalLabel;
      });
  };
  document.addEventListener('keydown', function(e){ if(e.key==='Escape') closeModal(); });

  // --- Scroll reveal ---
  var obs = new IntersectionObserver(function(entries){
    entries.forEach(function(e,i){ if(e.isIntersecting) setTimeout(function(){e.target.classList.add('vis');}, i*80); });
  }, {threshold:0.12});
  document.querySelectorAll('.fu,.tli').forEach(function(el){ obs.observe(el); });

  // init first slide
  if(slides.length) selSlide(0);
})();

/* Miro-Vorschau auf den Kit-Seiten: echter Zoom (− / +) und Ziehen zum Verschieben */
document.querySelectorAll('.miro-body').forEach(function(body){
  var canvas = body.querySelector('.miro-canvas'), img = canvas && canvas.querySelector('img');
  var zoom = body.querySelector('.miro-zoom'); if(!img || !zoom) return;
  var parts = zoom.querySelectorAll('span'), minus = parts[0], label = parts[1], plus = parts[2];
  var steps = [460, 620, 800, 1000], i = 1;
  function maxH(){ return img.naturalHeight || 1070; }
  function apply(){
    var h = Math.min(steps[i], maxH()), cx = (canvas.scrollLeft + canvas.clientWidth / 2) / (img.offsetWidth || 1);
    img.style.height = h + 'px';
    label.textContent = Math.round(h / maxH() * 100) + '%';
    canvas.scrollLeft = cx * img.offsetWidth - canvas.clientWidth / 2;
    minus.style.opacity = i === 0 ? .3 : 1;
    plus.style.opacity = steps[i] >= maxH() || i === steps.length - 1 ? .3 : 1;
  }
  minus.addEventListener('click', function(){ if(i > 0){ i--; apply(); } });
  plus.addEventListener('click', function(){ if(i < steps.length - 1 && steps[i] < maxH()){ i++; apply(); } });
  canvas.style.overflowY = 'auto'; canvas.style.maxHeight = '620px'; canvas.style.cursor = 'grab';
  var down = false, sx, sy, sl, st;
  canvas.addEventListener('mousedown', function(e){ down = true; sx = e.pageX; sy = e.pageY; sl = canvas.scrollLeft; st = canvas.scrollTop; canvas.style.cursor = 'grabbing'; e.preventDefault(); });
  window.addEventListener('mouseup', function(){ down = false; canvas.style.cursor = 'grab'; });
  window.addEventListener('mousemove', function(e){ if(!down) return; canvas.scrollLeft = sl - (e.pageX - sx); canvas.scrollTop = st - (e.pageY - sy); });
  if(img.complete) apply(); else img.addEventListener('load', apply);
});
