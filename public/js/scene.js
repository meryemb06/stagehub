(function () {
  const piste = document.getElementById('piste-mini');
  if (!piste) return;
  const cartes = [...piste.querySelectorAll('.mini')];
  if (!cartes.length) return;
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  let index = 0;
  let drag = null;

  function placer() {
    cartes.forEach((carte, i) => {
      const place = (i - index + cartes.length) % cartes.length;
      carte.dataset.place = place > 2 ? 'loin' : String(place);
      carte.style.transform = '';
      carte.style.zIndex = String(10 - place);
    });
  }

  function suivant(sens) {
    index = (index + sens + cartes.length) % cartes.length;
    placer();
  }

  piste.addEventListener('pointerdown', (event) => {
    const top = cartes[index];
    if (!top || !top.contains(event.target)) return;
    drag = { id: event.pointerId, x: event.clientX, dx: 0 };
    top.setPointerCapture(event.pointerId);
  });
  piste.addEventListener('pointermove', (event) => {
    if (!drag || event.pointerId !== drag.id) return;
    drag.dx = event.clientX - drag.x;
    cartes[index].style.transform = `translateX(${drag.dx}px) rotate(${drag.dx / 18}deg)`;
  });
  function lacher(event) {
    if (!drag || event.pointerId !== drag.id) return;
    const dx = drag.dx;
    drag = null;
    if (Math.abs(dx) > 70) suivant(dx > 0 ? 1 : -1);
    else placer();
  }
  piste.addEventListener('pointerup', lacher);
  piste.addEventListener('pointercancel', lacher);
  placer();
  if (!reduce) setInterval(() => { if (!drag) suivant(1); }, 2800);
})();
