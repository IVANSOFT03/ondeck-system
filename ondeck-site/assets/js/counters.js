/**
 * Animación de números en estadísticas (data-counter-target)
 */

function easeOutCubic(t) {
  return 1 - (1 - t) ** 3;
}

export function initCounters(containerSelector = ".section-stats") {
  const root = document.querySelector(containerSelector);
  if (!root) return;

  const items = root.querySelectorAll("[data-counter-target]");
  if (!items.length || !("IntersectionObserver" in window)) return;

  const animateOne = (el) => {
    const raw = el.getAttribute("data-counter-target") ?? "0";
    const suffix = el.getAttribute("data-counter-suffix") ?? "";
    const isFloat = raw.includes(".");
    const target = parseFloat(raw.replace(/[^\d.]/g, ""));
    if (Number.isNaN(target)) return;

    const duration = 1400;
    const start = performance.now();

    const frame = (now) => {
      const t = Math.min(1, (now - start) / duration);
      const v = easeOutCubic(t) * target;
      const display = isFloat
        ? v.toFixed(raw.split(".")[1]?.length ?? 1)
        : String(Math.round(v));
      el.textContent = display + suffix;
      if (t < 1) requestAnimationFrame(frame);
    };
    requestAnimationFrame(frame);
  };

  const io = new IntersectionObserver(
    (entries, obs) => {
      entries.forEach((entry) => {
        if (!entry.isIntersecting) return;
        const wrap = entry.target;
        wrap.querySelectorAll("[data-counter-target]").forEach((el) => {
          if (el.getAttribute("data-counter-done")) return;
          el.setAttribute("data-counter-done", "1");
          animateOne(el);
        });
        obs.unobserve(wrap);
      });
    },
    { threshold: 0.25 }
  );

  io.observe(root);
}
