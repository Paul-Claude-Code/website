/* LEAP kit-detail page interactions */
(function(){
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
    if(!n||!em){ errEl.textContent='Bitte Name und E-Mail eintragen.'; errEl.style.display='block'; return; }

    var btn=document.getElementById('mform-submit');
    btn.disabled=true;
    var originalLabel=btn.textContent;
    btn.textContent='Wird gesendet …';

    fetch('send-waitlist.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({
        name:n,
        email:em,
        kit: window.LEAP_KIT_ID || '',
        website: document.getElementById('mwebsite').value
      })
    }).then(function(res){ return res.json().then(function(data){ return {ok:res.ok, data:data}; }); })
      .then(function(r){
        if(!r.ok || !r.data.ok){ throw new Error((r.data && r.data.message) || 'Fehler beim Senden.'); }
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
