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
    // Brevo Meetings: Link deiner Buchungsseite
    // (Brevo → Meetings → Buchungsseite → Link kopieren, z.B. https://meetings.brevo.com/paul/gespraech)
    meetingsUrl: "https://meetings-eu1.brevo.com/demo/gespraech" // DEMO-Platzhalter zum Ansehen des Modals — durch echten Link ersetzen
  },

  ablefy: {
    // pro Workshop-Kit der Ablefy-Checkout-Link (Produkt → "Verkaufen" → Link kopieren)
    products: {
      vertrauen: "https://pay.ablefy.io/demo/vertrauen", // DEMO-Platzhalter — durch echten Ablefy-Checkout-Link ersetzen
      rollen: "https://pay.ablefy.io/demo/rollen",       // DEMO-Platzhalter
      feedback: "https://pay.ablefy.io/demo/feedback"    // DEMO-Platzhalter
    }
  },

  umami: {
    enabled: false,   // auf true setzen, sobald scriptUrl + websiteId eingetragen sind
    scriptUrl: null,  // z.B. https://umami.deine-domain.at/script.js
    websiteId: null
  }
};

(function(){

  /* ---------- Ablefy: Workshop-Kit kaufen ---------- */
  window.leapBuy = function(productKey){
    var url = (window.LEAP_INTEGRATIONS.ablefy.products || {})[productKey];
    if(url){ window.location.href = url; return; }
    // Fallback solange kein Ablefy-Link hinterlegt ist: bestehendes Warteliste-Modal
    if(typeof window.openModal === 'function') window.openModal();
  };

  /* ---------- Zeitplanr: Termin/Gespräch anfragen ---------- */
  var topicLabels = { moderation:'Moderation oder Coaching', coaching:'Moderation oder Coaching', allgemein:'Allgemeine Frage', workshop:'Workshop-Kit', programm:'Programm', unternehmen:'Unternehmen / Bundle / Lizenz' };

  function preselectTopic(context){
    var sel = document.getElementById('ctopic');
    var label = topicLabels[context];
    if(sel && label){
      for(var i=0;i<sel.options.length;i++){ if(sel.options[i].text===label){ sel.selectedIndex=i; break; } }
    }
    var hint = document.getElementById('cform-hint');
    if(hint) hint.style.display='block';
  }

  window.leapBookCall = function(context, fallbackUrl){
    var url = window.LEAP_INTEGRATIONS.brevo.meetingsUrl;
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
    var onContactPage = /kontakt\.html$/.test(window.location.pathname);
    if(onContactPage){
      preselectTopic(context);
      var grid = document.querySelector('.contact-grid');
      if(grid) window.scrollTo({top:grid.offsetTop-80, behavior:'smooth'});
      return;
    }
    window.location.href = fallbackUrl || ('kontakt.html?thema=' + encodeURIComponent(context||''));
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
