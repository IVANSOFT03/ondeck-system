/**
 * Partículas sutiles en canvas + cursor personalizado (punto).
 */

export function initParticles(canvasSelector = "#particles-canvas") {
  const canvas = document.querySelector(canvasSelector);
  if (!canvas || !(canvas instanceof HTMLCanvasElement)) return;

  const ctx = canvas.getContext("2d");
  if (!ctx) return;

  let width = 0;
  let height = 0;
  const dots = [];
  const COUNT = 48;

  function resize() {
    width = window.innerWidth;
    height = window.innerHeight;
    canvas.width = width;
    canvas.height = height;
  }

  function spawn() {
    dots.length = 0;
    for (let i = 0; i < COUNT; i++) {
      dots.push({
        x: Math.random() * width,
        y: Math.random() * height,
        r: Math.random() * 1.2 + 0.3,
        vx: (Math.random() - 0.5) * 0.35,
        vy: (Math.random() - 0.5) * 0.35,
        a: Math.random() * 0.4 + 0.1,
      });
    }
  }

  function tick() {
    ctx.clearRect(0, 0, width, height);
    for (const d of dots) {
      d.x += d.vx;
      d.y += d.vy;
      if (d.x < 0 || d.x > width) d.vx *= -1;
      if (d.y < 0 || d.y > height) d.vy *= -1;
      ctx.beginPath();
      ctx.fillStyle = `rgba(0, 242, 234, ${d.a})`;
      ctx.arc(d.x, d.y, d.r, 0, Math.PI * 2);
      ctx.fill();
    }
    requestAnimationFrame(tick);
  }

  resize();
  spawn();
  window.addEventListener("resize", () => {
    resize();
    spawn();
  });
  requestAnimationFrame(tick);
}

export function initCustomCursor() {
  const dot = document.querySelector(".cursor-dot");
  if (!dot) return;

  document.body.classList.add("is-custom-cursor");

  const move = (e) => {
    dot.style.left = `${e.clientX}px`;
    dot.style.top = `${e.clientY}px`;
  };

  const leave = () => dot.classList.add("is-hidden");
  const enter = () => dot.classList.remove("is-hidden");

  window.addEventListener("pointermove", move);
  document.addEventListener("mouseleave", leave);
  document.addEventListener("mouseenter", enter);
}
