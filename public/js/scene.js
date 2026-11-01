(function () {
  const toile = document.getElementById('toile');
  if (!toile) return;
  const ctx = toile.getContext('2d');
  const grains = Array.from({ length: 56 }, (_, i) => ({
    x: Math.random(),
    y: Math.random(),
    r: 1 + Math.random() * 2.4,
    v: 0.012 + Math.random() * 0.03,
    or: i % 5 === 0,
  }));
  let largeur = 0;
  let hauteur = 0;

  function mesurer() {
    const boite = toile.parentElement.getBoundingClientRect();
    const ratio = Math.min(window.devicePixelRatio || 1, 2);
    largeur = boite.width;
    hauteur = boite.height;
    toile.width = Math.max(1, Math.floor(largeur * ratio));
    toile.height = Math.max(1, Math.floor(hauteur * ratio));
    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
  }

  function tracer(temps) {
    ctx.clearRect(0, 0, largeur, hauteur);
    const cx = largeur * 0.72;
    const cy = hauteur * 0.38;
    const halo = ctx.createRadialGradient(cx, cy, 20, cx, cy, Math.max(largeur, hauteur) * 0.7);
    halo.addColorStop(0, 'rgba(226, 91, 42, 0.28)');
    halo.addColorStop(0.45, 'rgba(226, 177, 90, 0.08)');
    halo.addColorStop(1, 'rgba(0, 0, 0, 0)');
    ctx.fillStyle = halo;
    ctx.fillRect(0, 0, largeur, hauteur);

    ctx.strokeStyle = 'rgba(255, 244, 230, 0.08)';
    ctx.lineWidth = 1;
    for (let i = 0; i < 4; i += 1) {
      ctx.beginPath();
      const rayon = 90 + i * 70;
      ctx.ellipse(cx, cy, rayon, rayon * 0.62, temps / 8000 + i, 0, Math.PI * 2);
      ctx.stroke();
    }

    grains.forEach((grain, i) => {
      grain.y -= grain.v / 100;
      if (grain.y < -0.04) grain.y = 1.04;
      const x = grain.x * largeur + Math.sin(temps / 900 + i) * 16;
      const y = grain.y * hauteur;
      ctx.beginPath();
      ctx.fillStyle = grain.or ? 'rgba(226, 177, 90, 0.9)' : 'rgba(255, 122, 74, 0.8)';
      ctx.arc(x, y, grain.r, 0, Math.PI * 2);
      ctx.fill();
    });
    if (!document.hidden) requestAnimationFrame(tracer);
  }

  mesurer();
  window.addEventListener('resize', mesurer);
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden) requestAnimationFrame(tracer);
  });
  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    requestAnimationFrame(tracer);
  } else {
    tracer(0);
  }
})();
