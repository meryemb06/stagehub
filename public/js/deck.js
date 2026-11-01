(function () {
  const piste = document.getElementById('piste');
  if (!piste) return;
  const cartes = [...piste.querySelectorAll('.carte-swipe')];
  const vide = document.getElementById('pile-vide');
  const reste = document.querySelector('[data-reste]');
  const detail = document.querySelector('[data-geste="detail"]');
  const garderBtn = document.querySelector('[data-geste="garder"]');
  const revoir = document.querySelector('[data-geste="revoir"]');
  const selection = document.getElementById('selection');
  const liste = selection ? selection.querySelector('ul') : null;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let index = 0;
  let drag = null;

  function top() {
    return cartes[index] || null;
  }

  function placer() {
    cartes.forEach((carte, i) => {
      const place = i - index;
      carte.dataset.place = place < 0 ? 'partie' : place === 0 ? '0' : place === 1 ? '1' : place === 2 ? '2' : 'loin';
      if (place >= 0) carte.style.transform = '';
      carte.querySelectorAll('.tampon').forEach((tampon) => { tampon.style.opacity = '0'; });
    });
    const carte = top();
    if (reste) reste.textContent = String(Math.max(cartes.length - index, 0));
    const fini = index >= cartes.length;
    if (vide) vide.hidden = !fini;
    piste.hidden = fini;
    if (garderBtn) garderBtn.hidden = fini;
    if (detail) detail.hidden = fini;
    if (revoir) revoir.hidden = !fini;
    if (!carte) return;
    const lienDetail = carte.querySelector('[data-role="detail"]');
    if (detail && lienDetail) detail.href = lienDetail.href;
    piste.classList.add('prete');
  }

  function memoriser(carte) {
    if (!liste || !carte) return;
    const lien = carte.querySelector('[data-role="detail"]');
    const postuler = carte.querySelector('[data-role="postuler"]');
    const item = document.createElement('li');
    const voir = document.createElement('a');
    voir.href = lien ? lien.href : `/offres/${carte.dataset.offre}`;
    voir.textContent = carte.querySelector('h2')?.textContent || 'Offre';
    item.appendChild(voir);
    if (postuler) {
      const go = document.createElement('a');
      go.href = postuler.href;
      go.textContent = 'Postuler';
      item.appendChild(go);
    }
    liste.appendChild(item);
    selection.classList.add('pleine');
  }

  function avancer(direction, garder) {
    const carte = top();
    if (!carte) return;
    if (garder) memoriser(carte);
    if (!reduce) {
      carte.style.transition = 'transform .42s cubic-bezier(.2,.7,.2,1)';
      carte.style.transform = `translateX(${direction * 130}%) rotate(${direction * 16}deg)`;
    }
    index += 1;
    window.setTimeout(placer, reduce ? 0 : 400);
  }

  piste.addEventListener('pointerdown', (event) => {
    const carte = top();
    if (!carte || !carte.contains(event.target) || event.target.closest('a, button')) return;
    drag = { id: event.pointerId, x: event.clientX, dx: 0 };
    carte.setPointerCapture(event.pointerId);
    carte.classList.add('entrain');
  });
  window.addEventListener('pointermove', (event) => {
    if (!drag || event.pointerId !== drag.id) return;
    const carte = top();
    if (!carte) return;
    drag.dx = event.clientX - drag.x;
    carte.style.transform = `translateX(${drag.dx}px) rotate(${drag.dx / 16}deg)`;
    const passer = carte.querySelector('.tampon.passer');
    const garder = carte.querySelector('.tampon.garder');
    if (passer) passer.style.opacity = drag.dx < 0 ? String(Math.min(1, -drag.dx / 90)) : '0';
    if (garder) garder.style.opacity = drag.dx > 0 ? String(Math.min(1, drag.dx / 90)) : '0';
  });
  function lacher(event) {
    if (!drag || event.pointerId !== drag.id) return;
    const dx = drag.dx;
    drag = null;
    const carte = top();
    if (carte) carte.classList.remove('entrain');
    if (dx > 90) avancer(1, true);
    else if (dx < -90) avancer(-1, false);
    else placer();
  }
  window.addEventListener('pointerup', lacher);
  window.addEventListener('pointercancel', lacher);

  document.querySelector('[data-geste="passer"]')?.addEventListener('click', () => avancer(-1, false));
  garderBtn?.addEventListener('click', () => avancer(1, true));
  revoir?.addEventListener('click', () => { index = 0; placer(); });
  document.addEventListener('keydown', (event) => {
    if (event.target.closest('input, textarea, select')) return;
    if (event.key === 'ArrowLeft') avancer(-1, false);
    if (event.key === 'ArrowRight') avancer(1, true);
  });
  placer();
})();
