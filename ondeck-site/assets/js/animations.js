/**
 * Scroll reveal, glitch opcional en títulos, typewriter en elementos [data-typewriter]
 */

export function initScrollReveal(selector = ".reveal") {
  const els = document.querySelectorAll(selector);
  if (!els.length || !("IntersectionObserver" in window)) {
    els.forEach((el) => el.classList.add("is-visible"));
    return;
  }

  const io = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          io.unobserve(entry.target);
        }
      });
    },
    { rootMargin: "0px 0px -10% 0px", threshold: 0.08 }
  );

  els.forEach((el) => io.observe(el));
}

export function initGlitchText(selector = ".glitch-text--animated") {
  const el = document.querySelector(selector);
  if (!el) return;

  const text = el.textContent?.trim() ?? "";
  el.textContent = "";
  [...text].forEach((ch) => {
    const span = document.createElement("span");
    span.className = "char";
    span.textContent = ch;
    el.appendChild(span);
  });
}

export function initTypewriter(selector = "[data-typewriter]") {
  document.querySelectorAll(selector).forEach((el) => {
    const full = el.getAttribute("data-text")?.trim() ?? "";
    if (!full) return;
    el.textContent = "";
    let i = 0;
    const step = () => {
      if (i <= full.length) {
        el.textContent = full.slice(0, i);
        i++;
        window.setTimeout(step, 28 + Math.random() * 40);
      }
    };
    step();
  });
}
