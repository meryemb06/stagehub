(function () {
  const rail = document.querySelector('.rail');
  const burger = document.querySelector('.burger');
  const voile = document.querySelector('.voile');
  function fermer() {
    if (!rail) return;
    rail.classList.remove('ouvert');
    if (voile) voile.hidden = true;
    if (burger) burger.setAttribute('aria-expanded', 'false');
  }
  if (burger && rail) {
    burger.addEventListener('click', () => {
      const ouvert = rail.classList.toggle('ouvert');
      if (voile) voile.hidden = !ouvert;
      burger.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
    });
  }
  if (voile) voile.addEventListener('click', fermer);
  if (rail) rail.querySelectorAll('a').forEach((lien) => lien.addEventListener('click', fermer));
  document.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const nom = input.files && input.files[0] ? input.files[0].name : '';
      const cible = input.parentElement && input.parentElement.querySelector('.nom-fichier');
      if (cible) cible.textContent = nom;
    });
  });
})();
