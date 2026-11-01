(function () {
  const menu = document.querySelector('.menu');
  const burger = document.querySelector('.burger');
  const voile = document.querySelector('.voile');

  function fermer() {
    if (!menu) return;
    menu.classList.remove('ouvert');
    if (voile) voile.hidden = true;
    if (burger) burger.setAttribute('aria-expanded', 'false');
  }

  if (burger && menu) {
    burger.addEventListener('click', () => {
      const ouvert = menu.classList.toggle('ouvert');
      if (voile) voile.hidden = !ouvert;
      burger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    });
  }
  if (voile) voile.addEventListener('click', fermer);
  if (menu) menu.querySelectorAll('a').forEach((lien) => lien.addEventListener('click', fermer));

  document.querySelectorAll('.stat b').forEach((noeud) => {
    const texte = noeud.textContent.trim();
    const match = texte.match(/^(\d+)(.*)$/);
    if (!match || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const cible = Number(match[1]);
    const suffixe = match[2];
    const debut = performance.now();
    const duree = 700;
    function pas(instant) {
      const p = Math.min(1, (instant - debut) / duree);
      const valeur = Math.round((1 - Math.pow(1 - p, 3)) * cible);
      noeud.textContent = valeur + suffixe;
      if (p < 1) requestAnimationFrame(pas);
    }
    noeud.textContent = '0' + suffixe;
    requestAnimationFrame(pas);
  });
})();
