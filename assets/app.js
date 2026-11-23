// Leevel - interactions (aucune dépendance)
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name=csrf]')?.content || '';

  // Mode app (ajoutée à l'écran d'accueil) : le serveur garde l'appareil connecté
  try {
    const inApp = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const secure = location.protocol === 'https:' ? '; secure' : '';
    if (inApp && !/(?:^|; )leevel_app=1/.test(document.cookie)) { document.cookie = 'leevel_app=1; max-age=31536000; path=/; samesite=lax' + secure; if ($('form [name=username]') === null && !sessionStorage.getItem('lv_app_r')) { sessionStorage.setItem('lv_app_r', '1'); location.reload(); } }
    else if (!inApp && /(?:^|; )leevel_app=1/.test(document.cookie)) document.cookie = 'leevel_app=; max-age=0; path=/; samesite=lax' + secure;
  } catch (e) {}

  // Largeurs des barres (CSP : pas de style inline)
  requestAnimationFrame(() => $$('[data-w]').forEach(el => { el.style.width = Math.max(0, Math.min(100, +el.dataset.w)) + '%'; }));

  // Confirmation des actions destructrices
  document.addEventListener('submit', e => {
    const m = e.target.dataset.confirm || e.submitter?.dataset.confirm;
    if (m && !confirm(m)) e.preventDefault();
  });
  // Matières / compétences en sous-onglets (fiche élève) : une seule visible à la fois, mémorisée dans l'adresse
  const kindNav = $('[data-kind-tabs]');
  const showKind = k => { if (!kindNav) return; $$('[data-kind-pane]').forEach(p => p.hidden = p.dataset.kindPane !== k); $$('[data-kind-tab]', kindNav).forEach(b => b.classList.toggle('on', b.dataset.kindTab === k)); };
  if (kindNav) $$('[data-kind-tab]', kindNav).forEach(b => b.addEventListener('click', () => { showKind(b.dataset.kindTab); history.replaceState(null, '', b.dataset.kindTab === 'skills' ? '#competences' : location.pathname + location.search); }));
  $$('[data-subj-tabs]').forEach(nav => {
    const scope = nav.parentElement, panes = $$('[data-subj-pane]', scope);
    const show = id => { panes.forEach(p => p.hidden = p.id !== id); $$('[data-subj-tab]', nav).forEach(b => b.classList.toggle('on', b.dataset.subjTab === id)); };
    $$('[data-subj-tab]', nav).forEach(b => b.addEventListener('click', () => { show(b.dataset.subjTab); history.replaceState(null, '', '#' + b.dataset.subjTab); }));
    const fromHash = () => { if (location.hash.length > 1) { const t = document.getElementById(location.hash.slice(1)); const pane = t?.closest('[data-subj-pane]'); if (pane && panes.includes(pane) && pane.hidden) { show(pane.id); t.scrollIntoView({ block: 'start' }); } } };
    fromHash(); window.addEventListener('hashchange', fromHash);
  });
  if (kindNav && location.hash.length > 1) { const t = document.getElementById(location.hash.slice(1)); const kp = t?.closest('[data-kind-pane]'); if (kp) showKind(kp.dataset.kindPane); }
  // Case « Autre » : affiche le champ associé
  $$('input[data-reveal]').forEach(cb => cb.addEventListener('change', () => { const t = document.querySelector(cb.dataset.reveal); if (t) { t.hidden = !cb.checked; if (cb.checked) t.focus(); else t.value = ''; } }));
  // Parcours d'accueil : volets affichés selon les cases cochées
  $$('[data-wish-mode]').forEach(r => r.addEventListener('change', () => { const b = document.querySelector('[data-wish-mode-box]'); if (b) b.hidden = r.value !== 'other'; }));
  $$('[data-wish-subj],[data-wish-other]').forEach(cb => cb.addEventListener('change', () => {
    const box = document.querySelector(cb.hasAttribute('data-wish-subj') ? '[data-wish-subj-box]' : '[data-wish-other-box]'); if (box) box.hidden = !cb.checked;
  }));
  // Visite guidée
  const tourEl = document.getElementById('tour-data');
  if (tourEl) {
    let T; try { T = JSON.parse(tourEl.textContent); } catch (e) { T = null; }
    if (T && T.steps.length) {
      const tu = T.kid; let i = 0, ov, pop;
      const close = () => { document.body.classList.remove('touring'); ov?.remove(); pop?.remove(); document.querySelectorAll('.tour-hl').forEach(e => e.classList.remove('tour-hl')); document.removeEventListener('keydown', onKey); };
      const onKey = e => { if (e.key === 'Escape') close(); if (e.key === 'ArrowRight') go(i + 1); if (e.key === 'ArrowLeft') go(i - 1); };
      const target = st => [...document.querySelectorAll('[data-tour="' + st.target + '"]')].find(el => el.offsetParent !== null);
      const place = el => {
        const r = el.getBoundingClientRect(), pw = pop.offsetWidth, ph = pop.offsetHeight, m = 12;
        let top = r.bottom + m; if (top + ph > innerHeight - 8) top = r.top - ph - m;
        let left = r.left + r.width / 2 - pw / 2; left = Math.max(8, Math.min(innerWidth - pw - 8, left));
        pop.style.top = Math.max(8, top) + 'px'; pop.style.left = left + 'px';
      };
      const go = n => {
        if (n < 0 || n >= T.steps.length) return;
        i = n; const st = T.steps[i], el = target(st);
        document.querySelectorAll('.tour-hl').forEach(e => e.classList.remove('tour-hl'));
        if (el) el.classList.add('tour-hl');
        pop.innerHTML = '<div class="tour-step">Étape ' + (i + 1) + ' sur ' + T.steps.length + '</div><h3></h3><p></p>' +
          '<div class="tour-btns"><button type="button" class="tour-skip" data-t="skip">' + (tu ? 'Passer' : 'Passer la visite') + '</button><span>' +
          (i > 0 ? '<button type="button" class="btn ghost sm" data-t="prev">Précédent</button>' : '') +
          '<button type="button" class="btn sm" data-t="next">' + (i === T.steps.length - 1 ? 'Terminer' : 'Suivant') + '</button></span></div>';
        pop.querySelector('h3').textContent = st.title; pop.querySelector('p').textContent = st.text;
        pop.querySelector('[data-t=next]').onclick = () => i === T.steps.length - 1 ? close() : go(i + 1);
        pop.querySelector('[data-t=skip]').onclick = close;
        const pv = pop.querySelector('[data-t=prev]'); if (pv) pv.onclick = () => go(i - 1);
        if (el) place(el); else { pop.style.top = '30%'; pop.style.left = Math.max(8, innerWidth / 2 - pop.offsetWidth / 2) + 'px'; }
        pop.querySelector('[data-t=next]').focus();
      };
      const start = () => {
        document.body.classList.add('touring');
        ov = document.createElement('div'); ov.className = 'tour-ov'; ov.onclick = close;
        pop = document.createElement('div'); pop.className = 'tour-pop'; pop.setAttribute('role', 'dialog'); pop.setAttribute('aria-live', 'polite');
        document.body.append(ov, pop); document.addEventListener('keydown', onKey); addEventListener('resize', () => { const el = target(T.steps[i]); if (el && pop.isConnected) place(el); });
        go(0);
      };
      if (T.auto) start();
      else {
        const m = document.createElement('div'); m.className = 'tour-ov tour-ov-modal';
        m.innerHTML = '<div class="tour-welcome" role="dialog" aria-modal="true"><div class="tour-emo">🧭</div><h2>' + (tu ? 'Bienvenue dans ton espace !' : 'Bienvenue dans votre espace !') + '</h2><p>' +
          (tu ? 'Veux-tu une petite visite pour découvrir les onglets ? Ça prend 1 minute.' : 'Souhaitez-vous une visite rapide pour découvrir les onglets et les fonctionnalités ? Cela prend 1 minute.') +
          '</p><div class="tour-btns"><button type="button" class="btn ghost" data-t="no">' + (tu ? 'Plus tard' : 'Plus tard') + '</button><button type="button" class="btn" data-t="go">' + (tu ? 'C\'est parti' : 'Commencer la visite') + '</button></div><small>' + (tu ? 'Tu pourras la revoir depuis le Centre d\'aide.' : 'Vous pourrez la revoir depuis le Centre d\'aide.') + '</small></div>';
        document.body.append(m);
        m.querySelector('[data-t=no]').onclick = () => m.remove();
        m.querySelector('[data-t=go]').onclick = () => { m.remove(); start(); };
        m.querySelector('[data-t=go]').focus();
      }
    }
  }
  // Notifications push : service worker (toutes les pages) + activation depuis « Mon compte »
  if ('serviceWorker' in navigator) navigator.serviceWorker.register('sw.js').catch(() => {});
  const pushBox = $('[data-push]');
  if (pushBox) {
    const st = $('[data-push-status]', pushBox), bOn = $('[data-push-on]', pushBox), bOff = $('[data-push-off]', pushBox), bTest = $('[data-push-test]', pushBox);
    const isIOS = /iP(hone|ad|od)/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
    const standalone = matchMedia('(display-mode: standalone)').matches || navigator.standalone === true;
    const supported = 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window;
    const post = (url, data) => { const f = new FormData(); f.append('_csrf', pushBox.dataset.csrf); Object.entries(data).forEach(([k, v]) => f.append(k, v)); return fetch(url, { method: 'POST', body: f, credentials: 'same-origin' }); };
    const keyBytes = b64 => { const s = atob((b64 + '='.repeat((4 - b64.length % 4) % 4)).replace(/-/g, '+').replace(/_/g, '/')); return Uint8Array.from(s, c => c.charCodeAt(0)); };
    const b64u = buf => btoa(String.fromCharCode(...new Uint8Array(buf))).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    const set = (state, msg) => { st.textContent = msg; bOn.hidden = state !== 'off'; bOff.hidden = bTest.hidden = state !== 'on'; };
    const refresh = async () => {
      if (!supported) { if (isIOS && !standalone) $('[data-push-ios]', pushBox).hidden = false; else $('[data-push-unsupported]', pushBox).hidden = false; set('na', isIOS && !standalone ? 'Pour recevoir les notifications sur iPhone, ajoutez d\'abord leevel à votre écran d\'accueil :' : 'Notifications indisponibles sur ce navigateur.'); return; }
      if (Notification.permission === 'denied') { set('na', 'Les notifications sont bloquées pour ce site : autorisez-les dans les réglages du navigateur, puis rechargez la page.'); return; }
      const reg = await navigator.serviceWorker.ready; const sub = await reg.pushManager.getSubscription();
      if (sub) { set('on', '✅ Notifications activées sur cet appareil.'); post(pushBox.dataset.sub, { endpoint: sub.endpoint, p256dh: b64u(sub.getKey('p256dh')), auth: b64u(sub.getKey('auth')) }); }
      else set('off', 'Notifications désactivées sur cet appareil.');
    };
    bOn.addEventListener('click', async () => {
      try {
        const perm = await Notification.requestPermission(); if (perm !== 'granted') return refresh();
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(pushBox.dataset.key) });
        const r = await post(pushBox.dataset.sub, { endpoint: sub.endpoint, p256dh: b64u(sub.getKey('p256dh')), auth: b64u(sub.getKey('auth')) });
        set(r.ok ? 'on' : 'off', r.ok ? '✅ Notifications activées sur cet appareil.' : 'L\'activation a échoué, réessayez.');
      } catch (e) { set('off', 'L\'activation a échoué : ' + (e.message || e)); }
    });
    bOff.addEventListener('click', async () => { const reg = await navigator.serviceWorker.ready; const sub = await reg.pushManager.getSubscription(); if (sub) { await post(pushBox.dataset.unsub, { endpoint: sub.endpoint }); await sub.unsubscribe(); } refresh(); });
    bTest.addEventListener('click', async () => { const r = await post(pushBox.dataset.test, {}); const j = await r.json().catch(() => ({})); st.textContent = j.sent ? '📨 Test envoyé : la notification devrait arriver dans quelques secondes.' : 'Le test n\'a pas pu être envoyé.'; });
    refresh();
  }
  // Afficher / masquer les mots de passe
  const sprite = (document.querySelector('svg use')?.getAttribute('href') || '/assets/icons.svg').split('#')[0];
  $$('input[type=password]').forEach(inp => {
    if (inp.closest('.pw-wrap')) return;
    const w = document.createElement('span'); w.className = 'pw-wrap';
    inp.parentNode.insertBefore(w, inp); w.appendChild(inp);
    const b = document.createElement('button'); b.type = 'button'; b.className = 'pw-toggle';
    b.setAttribute('aria-label', 'Afficher le mot de passe'); b.title = 'Afficher le mot de passe';
    b.innerHTML = '<svg class="ic" aria-hidden="true"><use href="' + sprite + '#i-eye"></use></svg>';
    b.addEventListener('click', () => {
      const show = inp.type === 'password'; inp.type = show ? 'text' : 'password';
      b.classList.toggle('on', show); const l = show ? 'Masquer le mot de passe' : 'Afficher le mot de passe'; b.setAttribute('aria-label', l); b.title = l;
      inp.focus({ preventScroll: true });
    });
    w.appendChild(b);
  });
  $$('select[data-autosubmit]').forEach(sel => sel.addEventListener('change', () => sel.form.submit()));
  // Onglets défilants : l'onglet actif est toujours visible
  $$('.tabs').forEach(t => { const on = t.querySelector('a.on'); if (on && t.scrollWidth > t.clientWidth) t.scrollLeft = on.offsetLeft - (t.clientWidth - on.offsetWidth) / 2; });
  // Sélecteur d'élève (barre du haut) : largeur ajustée à l'élève affiché, pas au nom le plus long
  const sw = $('.st-switch select');
  if (sw) {
    const meter = document.createElement('span');
    meter.style.cssText = 'position:absolute;left:-9999px;top:0;visibility:hidden;white-space:nowrap';
    document.body.appendChild(meter);
    const fit = () => {
      const cs = getComputedStyle(sw);
      ['fontFamily', 'fontSize', 'fontWeight', 'letterSpacing'].forEach(k => { meter.style[k] = cs[k]; });
      meter.textContent = sw.options[sw.selectedIndex] ? sw.options[sw.selectedIndex].text : '';
      sw.style.width = Math.ceil(meter.getBoundingClientRect().width + parseFloat(cs.paddingLeft) + parseFloat(cs.paddingRight) + 30) + 'px';
    };
    fit(); addEventListener('resize', fit);
  }
  // « Voir plus » : replie les blocs trop longs
  $$('[data-clamp]').forEach(el => {
    const max = +el.dataset.clamp || 190;
    if (el.scrollHeight <= max + 40) return;
    el.classList.add('clamped'); el.style.maxHeight = max + 'px';
    const b = document.createElement('button');
    b.type = 'button'; b.className = 'more-btn'; b.textContent = 'Voir plus';
    b.setAttribute('aria-expanded', 'false');
    b.addEventListener('click', () => {
      const open = el.classList.toggle('open');
      el.style.maxHeight = open ? el.scrollHeight + 'px' : max + 'px';
      b.textContent = open ? 'Voir moins' : 'Voir plus';
      b.setAttribute('aria-expanded', String(open));
    });
    el.after(b);
  });
  // Bascule Liste / Calendrier
  $$('[data-views]').forEach(box => {
    const btns = $$('[data-view]', box), panes = $$('[data-pane]', box);
    btns.forEach(b => b.addEventListener('click', () => {
      btns.forEach(x => x.classList.toggle('on', x === b));
      panes.forEach(p => { p.hidden = p.dataset.pane !== b.dataset.view; });
    }));
  });
  // Champs fixes : crayon → mode édition, Annuler → lecture
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-edit]');
    if (!b) return;
    const box = b.closest('.editable');
    const on = box.classList.toggle('editing');
    if (on) box.querySelector('.edit-form input:not([type=hidden])')?.focus();
  });
  // Création de dossier : « créer moi-même » / « inviter par e-mail »
  const applySwitch = (who) => {
    const val = $(`input[data-switch="${who}"]:checked`)?.value;
    $$(`[data-switch-pane^="${who}:"]`).forEach(p => {
      const on = p.dataset.switchPane === `${who}:${val}`;
      p.hidden = !on;
      $$('input', p).forEach(i => { i.disabled = !on; });
    });
  };
  [...new Set($$('input[data-switch]').map(i => i.dataset.switch))].forEach(who => {
    $$(`input[data-switch="${who}"]`).forEach(r => r.addEventListener('change', () => applySwitch(who)));
    applySwitch(who);
  });
  // Envoi rapide d'invitation : seul le champ e-mail concerné doit être valide
  $$('[data-quick]').forEach(b => b.addEventListener('click', e => {
    const f = b.form.querySelector(`[name="${b.dataset.quick}"]`);
    if (f && !f.reportValidity()) e.preventDefault();
  }));
  // Copier un lien
  $$('[data-copy]').forEach(b => b.addEventListener('click', async () => {
    const inp = b.parentElement.querySelector('input');
    inp.select();
    try { await navigator.clipboard.writeText(inp.value); } catch (e) { document.execCommand('copy'); }
    const t = b.innerHTML; b.textContent = 'Copié ✓'; setTimeout(() => { b.innerHTML = t; }, 1600);
  }));
  // Messagerie : afficher le dernier message
  $$('[data-thread]').forEach(t => { t.scrollTop = t.scrollHeight; });
  if (document.body.classList.contains('page-chat') && matchMedia('(max-width:720px)').matches) requestAnimationFrame(() => window.scrollTo(0, document.documentElement.scrollHeight));
  // Pièce jointe (photo, PDF) en plein écran, avec Télécharger et Fermer
  const iconSvg = n => '<svg class="ic" aria-hidden="true"><use href="' + ($('link[rel=icon-sprite]')?.href || document.querySelector('use')?.getAttribute('href')?.split('#')[0] || '') + '#i-' + n + '"></use></svg>';
  let lb = null;
  const closeLb = () => { if (!lb) return; lb.remove(); lb = null; document.body.classList.remove('lb-open'); };
  document.addEventListener('click', e => {
    const a = e.target.closest('[data-lightbox]'); if (!a) return;
    e.preventDefault(); closeLb();
    lb = document.createElement('div'); lb.className = 'lightbox'; lb.setAttribute('role', 'dialog'); lb.setAttribute('aria-label', a.dataset.name || 'Pièce jointe');
    const view = a.dataset.lightbox === 'img' ? Object.assign(document.createElement('img'), { src: a.getAttribute('href'), alt: a.dataset.name || '' }) : Object.assign(document.createElement('iframe'), { src: a.getAttribute('href'), title: a.dataset.name || 'Document' });
    const acts = document.createElement('div'); acts.className = 'lb-acts';
    const dl = document.createElement('a'); dl.className = 'lb-btn'; dl.href = a.dataset.dl; dl.setAttribute('download', a.dataset.name || ''); dl.setAttribute('aria-label', 'Télécharger'); dl.title = 'Télécharger'; dl.innerHTML = iconSvg('download');
    const x = document.createElement('button'); x.type = 'button'; x.className = 'lb-btn'; x.setAttribute('aria-label', 'Fermer'); x.title = 'Fermer'; x.innerHTML = iconSvg('x');
    x.addEventListener('click', () => history.state?.lb ? history.back() : closeLb());
    // Sur téléphone : « Télécharger » ouvre la feuille de partage (Enregistrer dans Fichiers / Photos), plus fiable en mode app
    if (navigator.canShare && matchMedia('(pointer: coarse)').matches) dl.addEventListener('click', async ev => {
      ev.preventDefault();
      try {
        const blob = await (await fetch(a.dataset.dl, { credentials: 'same-origin' })).blob();
        const file = new File([blob], a.dataset.name || 'fichier', { type: blob.type });
        if (navigator.canShare({ files: [file] })) await navigator.share({ files: [file] }); else location.href = a.dataset.dl;
      } catch (err) { if (err.name !== 'AbortError') location.href = a.dataset.dl; }
    });
    acts.append(dl, x); lb.append(view, acts); document.body.append(lb); document.body.classList.add('lb-open'); x.focus();
    history.pushState({ lb: 1 }, '');
  });
  window.addEventListener('popstate', closeLb);
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && lb) history.state?.lb ? history.back() : closeLb(); });

  // Messagerie : aperçu des pièces jointes choisies (avec retrait possible)
  $$('[data-msg-form]').forEach(form => {
    const input = $('[data-attach]', form), list = $('[data-attach-list]', form); if (!input || !list) return;
    let picked = [];
    const sync = () => { const dt = new DataTransfer(); picked.forEach(f => dt.items.add(f)); input.files = dt.files; };
    const draw = () => {
      list.textContent = ''; list.hidden = !picked.length;
      picked.forEach((f, i) => {
        const c = document.createElement('span'); c.className = 'att-chip';
        const n = document.createElement('span'); n.textContent = (f.type.startsWith('image/') ? '🖼️ ' : '📎 ') + f.name;
        const b = document.createElement('button'); b.type = 'button'; b.setAttribute('aria-label', 'Retirer ' + f.name); b.textContent = '×';
        b.addEventListener('click', () => { picked = picked.filter((_, j) => j !== i); sync(); draw(); });
        c.append(n, b); list.append(c);
      });
    };
    input.addEventListener('change', () => { const prev = picked; picked = prev.concat([...input.files]).slice(0, 5); try { sync(); } catch (e) { picked = [...input.files]; } draw(); });
    form.addEventListener('submit', e => { const t = $('textarea', form); if (!picked.length && !t.value.trim()) { e.preventDefault(); t.focus(); } });
  });

  $$('textarea[data-autogrow]').forEach(t => { const fit = () => { t.style.height = 'auto'; t.style.height = Math.min(t.scrollHeight, 140) + 'px'; }; t.addEventListener('input', fit); });
  // Recadrage de la photo (canvas, sans bibliothèque) : glisser pour placer, curseur pour zoomer
  const cropInput = $('[data-crop-input]');
  if (cropInput) {
    const box = $('[data-cropper]'), cv = $('[data-crop-canvas]'), ctx = cv.getContext('2d'), zoomEl = $('[data-crop-zoom]');
    const S = cv.width; let img = null, base = 1, zoom = 1, ox = 0, oy = 0, drag = null;
    const clamp = () => { const w = img.width * base * zoom, h = img.height * base * zoom; ox = Math.min(0, Math.max(S - w, ox)); oy = Math.min(0, Math.max(S - h, oy)); };
    const draw = () => { ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, S, S); ctx.drawImage(img, ox, oy, img.width * base * zoom, img.height * base * zoom); };
    cropInput.addEventListener('change', () => {
      const f = cropInput.files[0]; if (!f) return;
      if (f.size > 15 * 1024 * 1024) { alert('Image trop lourde.'); return; }
      const im = new Image();
      im.onload = () => { img = im; base = Math.max(S / im.width, S / im.height); zoom = 1; zoomEl.value = 1;
        ox = (S - im.width * base) / 2; oy = (S - im.height * base) / 2; clamp(); draw(); box.hidden = false; box.scrollIntoView({ behavior: 'smooth', block: 'center' }); };
      im.onerror = () => alert('Image illisible. Formats acceptés : JPEG, PNG, WebP.');
      im.src = URL.createObjectURL(f);
    });
    zoomEl.addEventListener('input', () => { if (!img) return; const c = S / 2, old = zoom; zoom = +zoomEl.value;
      ox = c - (c - ox) * zoom / old; oy = c - (c - oy) * zoom / old; clamp(); draw(); });
    const pos = e => { const r = cv.getBoundingClientRect(), p = e.touches ? e.touches[0] : e; return [(p.clientX - r.left) * S / r.width, (p.clientY - r.top) * S / r.height]; };
    const start = e => { if (!img) return; drag = pos(e); e.preventDefault(); };
    const move = e => { if (!drag) return; const p = pos(e); ox += p[0] - drag[0]; oy += p[1] - drag[1]; drag = p; clamp(); draw(); e.preventDefault(); };
    cv.addEventListener('mousedown', start); cv.addEventListener('touchstart', start, { passive: false });
    window.addEventListener('mousemove', move); window.addEventListener('touchmove', move, { passive: false });
    window.addEventListener('mouseup', () => { drag = null; }); window.addEventListener('touchend', () => { drag = null; });
    $('[data-crop-cancel]').addEventListener('click', () => { box.hidden = true; cropInput.value = ''; img = null; });
    $('[data-crop-save]').addEventListener('click', e => {
      const btn = e.currentTarget; btn.disabled = true;
      const out = document.createElement('canvas'); out.width = out.height = 512;
      const k = 512 / S; out.getContext('2d').fillStyle = '#fff'; out.getContext('2d').fillRect(0, 0, 512, 512);
      out.getContext('2d').drawImage(img, ox * k, oy * k, img.width * base * zoom * k, img.height * base * zoom * k);
      out.toBlob(async blob => {
        const form = $('[data-avatar-form]'), fd = new FormData();
        fd.append('_csrf', form.querySelector('[name=_csrf]').value); fd.append('action', 'avatar'); fd.append('avatar', blob, 'photo.jpg');
        try { await fetch(form.getAttribute('action') || location.href, { method: 'POST', body: fd, credentials: 'same-origin' }); } catch (err) { /* le message s'affichera au rechargement */ }
        location.reload();
      }, 'image/jpeg', 0.9);
    });
  }
  // Remises : ajouter / supprimer une ligne
  const dBox = $('[data-discounts]');
  if (dBox) {
    $('[data-discount-add]').addEventListener('click', () => {
      const rows = $$('[data-discount-row]', dBox); if (rows.length >= 10) return;
      const n = rows[0].cloneNode(true); $$('input', n).forEach(i => { i.value = ''; }); $('select', n).selectedIndex = 0; dBox.appendChild(n); $('input', n).focus();
    });
    dBox.addEventListener('click', e => {
      const b = e.target.closest('[data-discount-del]'); if (!b) return;
      const rows = $$('[data-discount-row]', dBox), row = b.closest('[data-discount-row]');
      if (rows.length > 1) row.remove(); else $$('input', row).forEach(i => { i.value = ''; });
    });
  }
  $$('[data-print]').forEach(b => b.addEventListener('click', () => window.print()));
  $$('[data-toggle]').forEach(b => b.addEventListener('click', () => { const t = document.getElementById(b.dataset.toggle); if (t) { t.hidden = !t.hidden; if (!t.hidden) { t.scrollIntoView({ behavior: 'smooth', block: 'center' }); const f = t.querySelector('textarea,input:not([type=hidden]),select'); if (f) setTimeout(() => f.focus({ preventScroll: true }), 350); } } }));

  // ---------- Grille masonry (gauche à droite) ----------
  const masonry = grid => {
    const GAP = 16; // lignes de grille de 1 px : placement au pixel près
    grid.classList.add('js-masonry');
    let busy = false;
    const fit = () => {
      if (busy) return; busy = true;
      const items = [...grid.children].filter(c => c.offsetParent !== null);
      items.forEach(c => { c.style.alignSelf = 'start'; });
      items.forEach(c => { c.style.gridRowEnd = 'span ' + Math.max(1, Math.round(c.getBoundingClientRect().height + GAP)); });
      // Bas de grille aligné : la dernière carte de chaque colonne plus courte s'allonge jusqu'au bas de la plus haute
      // (par tronçon : une carte pleine largeur « span-2 » commence un nouveau tronçon)
      const segments = [[]];
      items.forEach(c => { if (c.classList.contains('span-2')) segments.push([]); else segments[segments.length - 1].push(c); });
      segments.forEach(seg => {
        const cols = {};
        seg.forEach(c => { const r = c.getBoundingClientRect(); const k = Math.round(r.left); if (!cols[k] || r.bottom > cols[k].b) cols[k] = { el: c, b: r.bottom }; });
        const keys = Object.keys(cols);
        if (keys.length < 2) return;
        const max = Math.max(...keys.map(k => cols[k].b));
        keys.forEach(k => { const { el, b } = cols[k]; if (max - b > 1) { el.style.gridRowEnd = 'span ' + Math.round(el.getBoundingClientRect().height + GAP + (max - b)); el.style.alignSelf = 'stretch'; } });
      });
      requestAnimationFrame(() => { busy = false; });
    };
    fit();
    refitters.push(fit);
    // une carte étirée ne change pas de taille quand son contenu grandit : on observe aussi son contenu
    if ('ResizeObserver' in window) { const ro = new ResizeObserver(() => requestAnimationFrame(fit)); [...grid.children].forEach(c => { ro.observe(c); [...c.children].forEach(x => ro.observe(x)); }); }
    window.addEventListener('resize', fit);
  };
  const refitters = [];
  const refitAll = () => requestAnimationFrame(() => refitters.forEach(f => f()));
  $$('.grid:not(.stat-grid), .grid-2:not(.two-cols)').forEach(masonry);
  document.addEventListener('click', e => { if (e.target.closest('[data-edit], [data-toggle], summary, [data-view]')) { refitAll(); setTimeout(refitAll, 60); } });
  document.addEventListener('toggle', refitAll, true);
  // Retour sur un bloc précis après enregistrement : on rouvre les volets qui le contiennent
  if (location.hash.length > 1) {
    const tgt = document.getElementById(decodeURIComponent(location.hash.slice(1)));
    if (tgt) { let d = tgt; while (d) { if (d.tagName === 'DETAILS') d.open = true; d = d.parentElement; } setTimeout(() => tgt.scrollIntoView({ block: 'start' }), 60); }
  }
  // Menu du compte : se ferme en touchant ailleurs ou avec Échap
  const acct = $('[data-acct]');
  if (acct) {
    document.addEventListener('click', e => { if (acct.open && !acct.contains(e.target)) acct.open = false; });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && acct.open) { acct.open = false; acct.querySelector('summary').focus(); } });
  }
  // Quiz (tutrice) : modifier une question sur place ; attente pendant la génération par IA
  document.addEventListener('click', e => {
    const b = e.target.closest('[data-qa-edit]'); if (!b) return;
    const item = b.closest('.qa-item'); const f = $('.qa-edit', item), v = $('.qa-view', item);
    f.hidden = !f.hidden; v.hidden = !f.hidden ? true : false; if (!f.hidden) $('input[name=question]', f).focus();
  });
  $$('[data-ai-form]').forEach(f => f.addEventListener('submit', () => {
    const b = $('.ai-btn', f); b.disabled = true; b.classList.add('loading'); $('span', b).textContent = 'Génération en cours… (jusqu\'à 1 min)';
  }));
  // Rythme des séances : ajouter / retirer un créneau, total hebdomadaire en direct
  $$('[data-sched-rows]').forEach(box => {
    const form = box.closest('form'), out = $('[data-sched-total]', form);
    const mins = v => { const [h, m] = (v || '').split(':').map(Number); return isNaN(h) ? null : h * 60 + (m || 0); };
    const fmtD = m => m < 60 ? m + ' min' : Math.floor(m / 60) + 'h' + (m % 60 ? String(m % 60).padStart(2, '0') : '');
    const total = () => { let m = 0;
      $$('[data-sched-row]', box).forEach(r => { const a = mins($('input[name="slot_time[]"]', r).value), b = mins($('input[name="slot_end[]"]', r).value), d = a !== null && b !== null ? b - a : 0;
        const lab = $('[data-row-dur]', r); if (lab) { lab.textContent = d > 0 ? fmtD(d) : 'fin avant le début'; lab.classList.toggle('bad', d <= 0); } if (d > 0) m += d; });
      const n = $$('[data-sched-row]', box).length;
      if (out) out.textContent = n + ' créneau' + (n > 1 ? 'x' : '') + ' · ' + Math.floor(m / 60) + 'h' + (m % 60 ? String(m % 60).padStart(2, '0') : '') + ' par semaine'; };
    form.addEventListener('click', e => {
      if (e.target.closest('[data-sched-add]')) { const rows = $$('[data-sched-row]', box); const c = rows[rows.length - 1].cloneNode(true); box.appendChild(c); total(); }
      const del = e.target.closest('[data-sched-del]');
      if (del) { const rows = $$('[data-sched-row]', box); if (rows.length > 1) del.closest('[data-sched-row]').remove(); total(); }
    });
    form.addEventListener('change', total); form.addEventListener('input', total); total();
  });
  // Séance : champs selon le statut (raison, report)
  $$('select[data-status-select]').forEach(sel => {
    const form = sel.closest('form');
    const apply = () => $$('[data-show-for]', form).forEach(el => { el.hidden = !el.dataset.showFor.split(' ').includes(sel.value); });
    sel.addEventListener('change', apply); apply();
  });
  // Report découpable : ajouter / retirer un créneau, total comparé à la durée d'origine
  $$('[data-resched]').forEach(box => {
    const rows = $('[data-resched-rows]', box), out = $('[data-resched-total]', box), orig = +box.dataset.total || 0;
    const fmt = m => Math.floor(m / 60) + 'h' + (m % 60 ? String(m % 60).padStart(2, '0') : '');
    const total = () => { const filled = $$('[data-resched-row]', rows).filter(r => $('input[type=date]', r).value);
      const m = filled.reduce((a, r) => a + (+$('input[type=number]', r).value || 0), 0);
      if (out) out.textContent = filled.length ? fmt(m) + ' reprogrammé' + (m === orig ? '' : ' sur ' + fmt(orig)) : ''; };
    box.addEventListener('click', e => {
      if (e.target.closest('[data-resched-add]')) { const all = $$('[data-resched-row]', rows); const c = all[all.length - 1].cloneNode(true); $('input[type=date]', c).value = ''; rows.appendChild(c); total(); }
      const del = e.target.closest('[data-resched-del]');
      if (del) { const all = $$('[data-resched-row]', rows); if (all.length > 1) del.closest('[data-resched-row]').remove(); else $$('input[type=date]', rows).forEach(i => { i.value = ''; }); total(); }
    });
    box.addEventListener('input', total); total();
  });
  // Dépôt de fichier : envoi dès que le fichier est choisi
  $$('[data-autosubmit-file]').forEach(i => i.addEventListener('change', () => { if (i.files.length) { const l = i.closest('label'); if (l) l.classList.add('loading'); i.form.submit(); } })); // partout : ordre gauche → droite, hauteurs libres

  // ---------- Mes outils : chrono de travail ----------
  const store = { get: k => { try { return localStorage.getItem(k); } catch (e) { return null; } }, set: (k, v) => { try { localStorage.setItem(k, v); } catch (e) {} } };
  const today = new Date().toISOString().slice(0, 10);
  const tm = $('[data-timer]');
  if (tm) {
    let work = 15 * 60, left = work, total = work, run = null, pause = false;
    const fmt = s => String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
    const doneKey = 'leevel-pomo-' + today, doneEl = $('[data-done]', tm);
    doneEl.textContent = store.get(doneKey) || '0';
    const draw = () => {
      $('[data-time]', tm).textContent = fmt(left);
      $('[data-phase]', tm).textContent = pause ? 'Pause' : 'Travail';
      $('[data-ring]', tm).style.setProperty('--p', String(Math.round((1 - left / total) * 100)));
      $('[data-ring]', tm).classList.toggle('pause', pause);
      $('[data-start] span', tm).textContent = run ? 'Pause' : (left < total ? 'Reprendre' : 'Démarrer');
    };
    const stop = () => { clearInterval(run); run = null; };
    const reset = () => { stop(); pause = false; left = total = work; draw(); };
    $$('[data-min]', tm).forEach(b => b.addEventListener('click', () => { $$('[data-min]', tm).forEach(x => x.classList.toggle('on', x === b)); work = +b.dataset.min * 60; reset(); }));
    $('[data-reset]', tm).addEventListener('click', reset);
    $('[data-start]', tm).addEventListener('click', () => {
      if (run) { stop(); draw(); return; }
      run = setInterval(() => {
        left--;
        if (left <= 0) {
          if (!pause) { const n = (+(store.get(doneKey) || 0)) + 1; store.set(doneKey, String(n)); doneEl.textContent = n; pause = true; left = total = 5 * 60; }
          else { pause = false; left = total = work; stop(); }
          try { if (navigator.vibrate) navigator.vibrate(300); } catch (e) {}
        }
        draw();
      }, 1000);
      draw();
    });
    draw();
  }
  // Fiches méthode : cases cochées mémorisées pour la journée
  $$('[data-checklist]').forEach(box => {
    const key = 'leevel-chk-' + box.dataset.checklist + '-' + today;
    const saved = (store.get(key) || '').split(',');
    $$('input', box).forEach(i => { i.checked = saved.includes(i.value); i.addEventListener('change', () => store.set(key, $$('input:checked', box).map(x => x.value).join(','))); });
    const rb = box.parentElement.querySelector('[data-check-reset]');
    if (rb) rb.addEventListener('click', () => { $$('input', box).forEach(i => { i.checked = false; }); store.set(key, ''); });
  });

  // ---------- Questionnaire ----------
  const form = $('#qform');
  if (!form) return;
  const steps = $$('.step', form);
  const bars = $$('.progress span');
  let cur = +form.dataset.step || 0;
  let dirty = false, timer = null;
  const status = $('#saved');

  const collect = (scope) => {
    const out = {};
    $$('.qq', scope).forEach(q => {
      const key = q.dataset.key, type = q.dataset.type;
      if (type === 'multi') {
        const skip = $(`input[name="a[${key}]"][value="__skip"]:checked`, q);
        out[key] = skip ? '__skip' : $$(`input[name="a[${key}][]"]:checked`, q).map(i => i.value);
      } else if (type === 'select') {
        const f = $('select', q); out[key] = f ? f.value : '';
      } else if (type === 'multi_prio') {
        out[key] = $$('[data-prio-inputs] input', q).map(i => i.value);
      } else if (type === 'grades') {
        out[key] = $$('[data-grade-row]', q).map(r => ({
          subject: $('select', r).value, avg: $('input[type=number]', r).value, comment: $('input[type=text]', r).value
        })).filter(x => x.subject || x.avg || x.comment);
      } else if (type === 'single' || type === 'scale') {
        const c = $(`input[name="a[${key}]"]:checked`, q);
        out[key] = c ? c.value : '';
      } else {
        const f = $(`[name="a[${key}]"]`, q);
        out[key] = f ? f.value : '';
      }
    });
    return out;
  };
  const values = () => collect(form);

  // Conditions d'affichage (même règle que côté serveur)
  const applyConditions = () => {
    const v = values();
    $$('.qq[data-if]', form).forEach(q => {
      const c = JSON.parse(q.dataset.if);
      const val = v[c.key];
      const vals = Array.isArray(val) ? val : [val];
      q.hidden = !vals.some(x => c.in.includes(x));
    });
  };

  const save = async (stepNo) => {
    clearTimeout(timer);
    try {
      const r = await fetch(form.dataset.save, {
        method: 'POST', credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ answers: values(), step: stepNo ?? cur })
      });
      if (!r.ok) throw new Error(r.status);
      const j = await r.json();
      dirty = false;
      if (status) status.textContent = 'Enregistré à ' + j.saved_at;
    } catch (e) {
      if (status) status.textContent = '⚠ Enregistrement impossible - vérifiez la connexion';
    }
  };
  const scheduleSave = () => { dirty = true; if (status) status.textContent = 'Modification…'; clearTimeout(timer); timer = setTimeout(save, 1200); };

  const show = (i) => {
    cur = Math.max(0, Math.min(steps.length - 1, i));
    steps.forEach((s, k) => s.classList.toggle('on', k === cur));
    bars.forEach((b, k) => { b.classList.toggle('done', k < cur); b.classList.toggle('cur', k === cur); });
    $('#prev').hidden = cur === 0;
    $('#next').hidden = cur === steps.length - 1;
    $('#finish').hidden = cur !== steps.length - 1;
    const lbl = $('#stepno'); if (lbl) lbl.textContent = `Étape ${cur + 1} / ${steps.length}`;
    $$('[data-goto]').forEach(b => { const k = +b.dataset.goto; b.classList.toggle('cur', k === cur); b.classList.toggle('done', k < cur); b.setAttribute('aria-current', k === cur ? 'step' : 'false'); });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  // Niveau scolaire : n'afficher que les matières / objectifs du cycle choisi
  const cycleOf = g => ['CP', 'CE1', 'CE2', 'CM1', 'CM2'].includes(g) ? 'primaire' : ['6e', '5e', '4e', '3e'].includes(g) ? 'college' : ['2nde', '1re', 'Tle'].includes(g) ? 'lycee' : null;
  const levelSrc = $('[data-level-source]', form);
  const applyLevel = () => {
    const c = cycleOf(levelSrc.value);
    $$('[data-levels]', form).forEach(el => {
      const keep = !c || el.dataset.levels.split(' ').includes(c);
      const selected = (el.matches('option') && el.selected) || el.classList.contains('prio') || el.classList.contains('sec') || $('input:checked', el);
      el.hidden = !keep && !selected; // une réponse déjà donnée reste visible
    });
  };
  if (levelSrc) { levelSrc.addEventListener('change', applyLevel); applyLevel(); }
  // Objectifs multiples avec une priorité : 1er clic sélectionne, 2e clic = priorité, 3e clic retire
  form.addEventListener('click', e => {
    const b = e.target.closest('[data-prio-group] .pill-btn');
    if (!b) return;
    const group = b.closest('[data-prio-group]'), btns = $$('.pill-btn', group);
    if (b.classList.contains('prio')) b.classList.remove('prio');
    else if (b.classList.contains('sec')) { btns.forEach(x => { if (x.classList.contains('prio')) { x.classList.remove('prio'); x.classList.add('sec'); } }); b.classList.remove('sec'); b.classList.add('prio'); }
    else b.classList.add(btns.some(x => x.classList.contains('prio')) ? 'sec' : 'prio');
    if (!btns.some(x => x.classList.contains('prio'))) { const first = btns.find(x => x.classList.contains('sec')); if (first) { first.classList.remove('sec'); first.classList.add('prio'); } }
    const order = [...btns.filter(x => x.classList.contains('prio')), ...btns.filter(x => x.classList.contains('sec'))];
    const box = group.nextElementSibling; box.innerHTML = '';
    order.forEach(x => { const i = document.createElement('input'); i.type = 'hidden'; i.name = group.dataset.name + '[]'; i.value = x.dataset.value; box.appendChild(i); });
    btns.forEach(x => { x.setAttribute('aria-pressed', String(x.classList.contains('prio') || x.classList.contains('sec'))); $('.ic', x)?.remove(); });
    const p = btns.find(x => x.classList.contains('prio'));
    if (p) p.insertAdjacentHTML('afterbegin', '<svg class="ic" aria-hidden="true"><use href="' + ($('link[rel=stylesheet]')?.href.replace(/app\.css.*$/, 'icons.svg') || 'assets/icons.svg') + '#i-flag"></use></svg> ');
    applyConditions(); scheduleSave();
  });
  // Moyennes : ajouter / supprimer une matière
  form.addEventListener('click', e => {
    const add = e.target.closest('[data-grade-add]'), del = e.target.closest('[data-grade-del]');
    if (add) {
      const box = add.previousElementSibling, rows = $$('[data-grade-row]', box);
      if (rows.length >= 15) return;
      const n = rows[rows.length - 1].cloneNode(true), idx = Date.now();
      $$('select,input', n).forEach(i => { i.name = i.name.replace(/\[\d+\]\[(subject|avg|comment)\]$/, `[${idx}][$1]`); i.value = ''; });
      box.appendChild(n); $('select', n).focus(); scheduleSave();
    }
    if (del) {
      const box = del.closest('[data-grades]'), row = del.closest('[data-grade-row]');
      if ($$('[data-grade-row]', box).length > 1) row.remove(); else $$('select,input', row).forEach(i => { i.value = ''; });
      scheduleSave();
    }
  });
  form.addEventListener('change', e => {
    const t = e.target;
    // "Je préfère ne pas répondre" exclusif
    if (t.value === '__skip' && t.checked) $$(`input`, t.closest('.qq')).forEach(i => { if (i !== t && i.type !== 'text') i.checked = false; });
    else if (t.closest('.qq') && t.value !== '__skip') { const sk = $('input[value="__skip"]', t.closest('.qq')); if (sk && (t.checked || t.value)) sk.checked = false; }
    applyConditions(); scheduleSave();
  });
  form.addEventListener('input', e => { if (e.target.matches('textarea,input[type=text]')) scheduleSave(); });
  $$('[data-goto]').forEach(b => b.addEventListener('click', () => { show(+b.dataset.goto); save(cur); }));
  $('#prev').addEventListener('click', () => { show(cur - 1); save(cur); });
  $('#next').addEventListener('click', () => { show(cur + 1); save(cur); });
  // Champs masqués : vider avant envoi pour ne pas transmettre de réponses non affichées? Non - on conserve les réponses (jamais modifiées automatiquement).
  window.addEventListener('beforeunload', e => { if (dirty) { save(); e.preventDefault(); } });
  applyConditions();
  show(cur);
})();
