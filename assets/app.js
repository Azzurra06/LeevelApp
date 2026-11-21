// Leevel - interactions (aucune dépendance)
(() => {
  'use strict';
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const csrf = $('meta[name=csrf]')?.content || '';

  // Largeurs des barres (CSP : pas de style inline)
  requestAnimationFrame(() => $$('[data-w]').forEach(el => { el.style.width = Math.max(0, Math.min(100, +el.dataset.w)) + '%'; }));

  // Confirmation des actions destructrices
  document.addEventListener('submit', e => {
    const m = e.target.dataset.confirm || e.submitter?.dataset.confirm;
    if (m && !confirm(m)) e.preventDefault();
  });
  $$('select[data-autosubmit]').forEach(sel => sel.addEventListener('change', () => sel.form.submit()));
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
  $$('[data-toggle]').forEach(b => b.addEventListener('click', () => { const t = document.getElementById(b.dataset.toggle); if (t) t.hidden = !t.hidden; }));

  // ---------- Grille masonry (gauche à droite) ----------
  const masonry = grid => {
    const ROW = 4, GAP = 16;
    grid.classList.add('js-masonry');
    const fit = () => [...grid.children].forEach(c => {
      c.style.gridRowEnd = 'span ' + Math.max(1, Math.ceil((c.getBoundingClientRect().height + GAP) / ROW));
    });
    fit();
    if ('ResizeObserver' in window) { const ro = new ResizeObserver(fit); [...grid.children].forEach(c => ro.observe(c)); }
    window.addEventListener('resize', fit);
  };
  $$('.grid:not(.stat-grid), .grid-2').forEach(masonry); // partout : ordre gauche → droite, hauteurs libres

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
