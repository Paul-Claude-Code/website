/* ============================================================
   LEAP — Integrations: Brevo · Zeitplanr · Ablefy · Umami
   ------------------------------------------------------------
   Zentrale Konfiguration für alle externen Tools. Sobald echte
   Zugangsdaten/Links vorliegen, NUR die Werte unten eintragen —
   der Rest der Website reagiert automatisch (Fallbacks fallen weg).
   Genaue Setup-Schritte je Tool: siehe INTEGRATIONS.md
   ============================================================ */
window.LEAP_INTEGRATIONS = {

  brevo: {
    // Embed-URL des Brevo "Web Form" fürs Kontaktformular/Newsletter
    // (Brevo → Kontakte → Formulare → Teilen → "Direkter Link")
    contactFormEmbedUrl: null,
    // Liste-ID, der neue Kontakte zugeordnet werden (für die
    // automatisierten Follow-up-Mails nach einer Buchung)
    listId: null,
    // Brevo Meetings: Link deiner kurzen Standard-Buchungsseite (15 Min)
    // (Brevo → Meetings → Meeting-Typ → Link kopieren)
    meetingsUrl: "https://meet.brevo.com/paul-scheipl",
    // Separater Meeting-Typ für die 60-minütige Coaching-Stunde (nur für
    // leapBookCall('coaching') verwendet — Moderation/Allgemein bleiben
    // beim kurzen Link oben). Beide Meeting-Typen müssen in Brevo auf
    // denselben Kalender zeigen, damit sich Buchungen gegenseitig blocken.
    meetingsUrlCoaching: "https://meet.brevo.com/paul-scheipl/coaching-stunde"
  },

  ablefy: {
    // pro Workshop-Kit der Ablefy-Checkout-Link (Produkt → "Verkaufen" → Link kopieren)
    products: {
      vertrauen: "https://myablefy.com/s/leap/workshop-kit-vertrauen-e315ec6e/payment",
      rollen: "https://myablefy.com/s/leap/workshop-kit-rollen-f8a7f57e/payment",
      feedback: "https://myablefy.com/s/leap/workshop-kit-feedback-79a89d9b/payment"
    }
  },

  umami: {
    enabled: true,
    scriptUrl: "https://cloud.umami.is/script.js",
    websiteId: "4732d90e-7a13-4f96-9399-19abf913fa0f"
  }
};

(function(){

  /* ---------- Sprache: DE (Standard) oder EN (Seiten unter /en/) ---------- */
  var LANG = (document.documentElement.lang || 'de').slice(0,2) === 'en' ? 'en' : 'de';
  window.LEAP_LANG = LANG;
  var T = {
    de: {
      redirect: 'Weiterleitung läuft',
      checkoutTitle: 'Weiter zu deiner sicheren Kasse',
      checkoutText: 'Du wirst gleich zu unserem Zahlungspartner <strong>Ablefy</strong> weitergeleitet, um <span id="ablefy-transit-kit"></span> sicher zu bezahlen.',
      checkoutGo: 'Jetzt weiter zur Kasse →',
      cancel: 'Abbrechen',
      fallbackKit: 'dein Workshop-Kit',
      contactPage: 'kontakt.html',
      kits: { vertrauen: 'Vertrauen aufbauen', rollen: 'Rollen & Verantwortung', feedback: 'Feedback' }
    },
    en: {
      redirect: 'Redirecting',
      checkoutTitle: 'On to secure checkout',
      checkoutText: 'You\'re being sent to our payment partner <strong>Ablefy</strong> to pay for <span id="ablefy-transit-kit"></span> securely. Checkout is in German.',
      checkoutGo: 'Continue to checkout →',
      cancel: 'Cancel',
      fallbackKit: 'your workshop kit',
      contactPage: 'contact.html',
      kits: { vertrauen: 'Building Trust', rollen: 'Roles & Responsibility', feedback: 'Feedback' }
    }
  }[LANG];

  /* ---------- Ablefy: Workshop-Kit kaufen ---------- */
  var kitLabels = T.kits;
  var transitTimer = null;
  var TRANSIT_DELAY_MS = 1800;

  function ensureTransitModal(){
    if(document.getElementById('ablefy-transit-modal')) return;
    var wrap = document.createElement('div');
    wrap.innerHTML =
      '<div class="modal-ov" id="ablefy-transit-modal" onclick="if(event.target===this) window.closeAblefyTransit()">' +
        '<div class="modal" style="text-align:center;">' +
          '<div style="display:flex;align-items:center;justify-content:center;gap:8px;margin-bottom:18px;">' +
            '<span class="transit-dot"></span>' +
            '<span style="font-size:10px;font-weight:700;letter-spacing:0.16em;text-transform:uppercase;color:var(--gray);">' + T.redirect + '</span>' +
          '</div>' +
          '<h3>' + T.checkoutTitle + '</h3>' +
          '<p>' + T.checkoutText + '</p>' +
          '<button class="btn btn-lime" style="width:100%;justify-content:center;" id="ablefy-transit-go">' + T.checkoutGo + '</button>' +
          '<button class="transit-cancel" id="ablefy-transit-cancel">' + T.cancel + '</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(wrap.firstElementChild);
    document.addEventListener('keydown', function(e){ if(e.key === 'Escape') window.closeAblefyTransit(); });
  }

  function goToAblefy(url){
    if(transitTimer){ clearTimeout(transitTimer); transitTimer = null; }
    window.location.href = url;
  }

  window.closeAblefyTransit = function(){
    if(transitTimer){ clearTimeout(transitTimer); transitTimer = null; }
    var m = document.getElementById('ablefy-transit-modal');
    if(m){ m.classList.remove('op'); document.body.style.overflow = ''; }
  };

  window.leapBuy = function(productKey){
    var url = (window.LEAP_INTEGRATIONS.ablefy.products || {})[productKey];
    if(url){
      ensureTransitModal();
      document.getElementById('ablefy-transit-kit').textContent = kitLabels[productKey] || T.fallbackKit;
      document.getElementById('ablefy-transit-go').onclick = function(){ goToAblefy(url); };
      document.getElementById('ablefy-transit-cancel').onclick = window.closeAblefyTransit;
      document.getElementById('ablefy-transit-modal').classList.add('op');
      document.body.style.overflow = 'hidden';
      transitTimer = setTimeout(function(){ goToAblefy(url); }, TRANSIT_DELAY_MS);
      return;
    }
    // Fallback solange kein Ablefy-Link hinterlegt ist: bestehendes Warteliste-Modal
    if(typeof window.openModal === 'function') window.openModal();
  };

  /* ---------- Zeitplanr: Termin/Gespräch anfragen ---------- */
  var topicLabels = { moderation:'Moderation oder Coaching', coaching:'Moderation oder Coaching', begleitung:'Moderation oder Coaching', allgemein:'Allgemeine Frage', workshop:'Workshop-Kit', programm:'Programm', unternehmen:'Unternehmen / Bundle / Lizenz' };

  function preselectTopic(context){
    var sel = document.getElementById('ctopic');
    var label = topicLabels[context];
    if(sel && label){
      for(var i=0;i<sel.options.length;i++){ if(sel.options[i].value===label || sel.options[i].text===label){ sel.selectedIndex=i; break; } }
    }
    var hint = document.getElementById('cform-hint');
    if(hint) hint.style.display='block';
  }

  window.leapBookCall = function(context, fallbackUrl){
    var brevoCfg = window.LEAP_INTEGRATIONS.brevo;
    var url = (context === 'coaching' && brevoCfg.meetingsUrlCoaching) ? brevoCfg.meetingsUrlCoaching : brevoCfg.meetingsUrl;
    if(url){
      var withCtx = context ? (url + (url.indexOf('?')>-1?'&':'?') + 'thema=' + encodeURIComponent(context)) : url;
      var modal = document.getElementById('booking-modal');
      var frame = document.getElementById('booking-frame');
      if(modal && frame){
        frame.src = withCtx;
        modal.classList.add('op');
        document.body.style.overflow='hidden';
        return;
      }
      window.open(withCtx, '_blank');
      return;
    }
    // Kein Brevo-Meetings-Link hinterlegt: aufs Kontaktformular verweisen
    var onContactPage = /(kontakt|contact)\.html$/.test(window.location.pathname);
    if(onContactPage){
      preselectTopic(context);
      var grid = document.querySelector('.contact-grid');
      if(grid) window.scrollTo({top:grid.offsetTop-80, behavior:'smooth'});
      return;
    }
    window.location.href = fallbackUrl || (T.contactPage + '?thema=' + encodeURIComponent(context||''));
  };

  window.closeBookingModal = function(){
    var modal = document.getElementById('booking-modal');
    if(modal){ modal.classList.remove('op'); document.body.style.overflow=''; }
  };

  /* ---------- Umami: Analytics (bleibt inaktiv bis enabled:true) ---------- */
  var u = window.LEAP_INTEGRATIONS.umami;
  if(u && u.enabled && u.scriptUrl && u.websiteId){
    var s = document.createElement('script');
    s.defer = true;
    s.src = u.scriptUrl;
    s.setAttribute('data-website-id', u.websiteId);
    document.head.appendChild(s);
  }

  /* ---------- Kontaktformular: Thema + Programm aus Query-Param vorbelegen ---------- */
  document.addEventListener('DOMContentLoaded', function(){
    var params = new URLSearchParams(window.location.search);
    var thema = params.get('thema');
    if(thema) preselectTopic(thema);
    var programm = params.get('programm');
    var programmField = document.getElementById('cprogramm');
    if(programm && programmField) programmField.value = programm;
  });

})();
