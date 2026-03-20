/**
 * Arranque global: según data-page en <body>
 */
import { initParticles, initCustomCursor } from "./particles.js";
import { initScrollReveal, initGlitchText, initTypewriter } from "./animations.js";
import { initCounters } from "./counters.js";

const page = document.body.dataset.page ?? "";

initScrollReveal(".reveal");

if (page === "home") {
  initParticles("#particles-canvas");
  initCustomCursor();
  initGlitchText(".glitch-text--animated");
  initTypewriter("[data-typewriter]");
  initCounters(".section-stats");
}

if (page === "privacy" || page === "terms") {
  initScrollReveal(".legal-block, .terms-card");
}
