/* LEAP marketing pages — shared interactions */
(function(){
  window.toggleMenu = function(){
    var m = document.getElementById('mobmenu');
    if(m) m.classList.toggle('op');
  };

  /* scroll fade-up reveal */
  var obs = new IntersectionObserver(function(entries){
    entries.forEach(function(e,i){ if(e.isIntersecting) setTimeout(function(){e.target.classList.add('vis');}, i*70); });
  }, {threshold:0.12});
  document.querySelectorAll('.fu,.tli').forEach(function(el){ obs.observe(el); });

  /* nav: shrink + blur once scrolled */
  var navEl = document.querySelector('nav');
  if(navEl){
    var onScroll = function(){ navEl.classList.toggle('scrolled', window.scrollY > 24); };
    onScroll();
    window.addEventListener('scroll', onScroll, {passive:true});
  }

  /* button arrow nudge — wrap trailing → so it can slide on hover */
  document.querySelectorAll('.btn,.kit-btn,.nav-cta,.coach-li,.prog-card .kit-btn').forEach(function(el){
    if(el.querySelector('.arw')) return;
    el.childNodes.forEach(function(n){
      if(n.nodeType===3 && n.nodeValue.indexOf('\u2192')>-1){
        var span=document.createElement('span');
        span.innerHTML=n.nodeValue.replace(/\u2192/g,'<span class="arw">\u2192</span>');
        n.replaceWith.apply(n,[].slice.call(span.childNodes));
      }
    });
  });

  /* count-up numbers when scrolled into view */
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion:reduce)').matches;
  var easeOut = function(t){ return 1 - Math.pow(1-t, 3); };
  var countUp = function(el){
    var target = parseFloat(el.getAttribute('data-count'));
    var prefix = el.getAttribute('data-prefix') || '';
    var suffix = el.getAttribute('data-suffix') || '';
    if(isNaN(target)) return;
    if(reduce){ el.textContent = prefix + target + suffix; return; }
    var dur = 1300, start = null;
    var step = function(ts){
      if(start===null) start = ts;
      var p = Math.min((ts-start)/dur, 1);
      var val = Math.round(target * easeOut(p));
      el.textContent = prefix + val + suffix;
      if(p<1) requestAnimationFrame(step);
    };
    requestAnimationFrame(step);
  };
  var cObs = new IntersectionObserver(function(entries){
    entries.forEach(function(e){
      if(e.isIntersecting){ countUp(e.target); cObs.unobserve(e.target); }
    });
  }, {threshold:0.4});
  document.querySelectorAll('[data-count]').forEach(function(el){ cObs.observe(el); });
})();
