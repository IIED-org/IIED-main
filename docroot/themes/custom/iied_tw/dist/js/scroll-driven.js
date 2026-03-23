(function () {

  // Only run desktop behaviour.
  if (window.innerWidth < 1024) return;

  // ── Config ──────────────────────────────────────────────────
  const TRIGGER = 0.45; // viewport fraction for text step trigger
  const PIN_TOP = 32;   // px from top when graphic is pinned (≈ top-8)
  const REDUCE_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ── Layer states [stepIndex][layerIndex] ────────────────────
  const STATES = [
    // Step 0
    [
      { opacity: 1, scale: 1    },
      { opacity: 0, scale: 1    },
      { opacity: 0, scale: 1    },
    ],
    // Step 1
    [
      { opacity: 0, scale: 0.40 },
      { opacity: 1, scale: 1    },
      { opacity: 0, scale: 1    },
    ],
    // Step 2
    [
      { opacity: 0, scale: 0.40 },
      { opacity: 0, scale: 0.40 },
      { opacity: 1, scale: 1    },
    ],
  ];

  // ── Elements ────────────────────────────────────────────────
  // Required hooks in HTML:
  // - .framework-scroll on outer section
  // - .fw-right-col on desktop right column
  // - .graphic-pin-wrap on the graphic wrapper (the 540px box)
  const sectionEl = document.querySelector('.framework-scroll');
  const rightCol  = document.querySelector('.fw-right-col');
  const pinWrap   = document.querySelector('.graphic-pin-wrap');
  const sections  = Array.from(document.querySelectorAll('.fw-section[data-step]'));
  const layers    = Array.from(document.querySelectorAll('.graphic-layer[data-layer]'));

  if (!sectionEl || !rightCol || !pinWrap || !sections.length || !layers.length) return;

  let currentStep = null;

  // ── Apply graphic + text state ──────────────────────────────
  function applyState(stepIndex) {
    const state = STATES[stepIndex];

    layers.forEach((layer, i) => {
      const prev = currentStep !== null ? STATES[currentStep][i] : null;
      const next = state[i];

      // Reset transition classes
      layer.classList.remove('is-exiting', 'is-entering');

      // Respect reduced motion
      if (REDUCE_MOTION) {
        layer.style.transition = 'none';
      } else {
        layer.style.transition = '';
        if (prev !== null) {
          if (prev.opacity === 1 && next.opacity === 0) {
            layer.classList.add('is-exiting');  // shrink + fade
          } else if (prev.opacity === 0 && next.opacity === 1) {
            layer.classList.add('is-entering'); // fade only
          }
        }
      }

      layer.style.opacity = next.opacity;
      layer.style.transform = `scale(${next.scale})`;

      // Layer stacking
      layer.style.zIndex =
        next.opacity === 1 ? 3 :
        (prev && prev.opacity === 1) ? 2 :
        1;
    });

    // Dim inactive text sections
    sections.forEach((el, i) => {
      el.style.opacity = i === stepIndex ? '1' : '0.35';
    });
  }

  // ── Determine active text step ──────────────────────────────
  function getActiveStep() {
    const triggerY = window.innerHeight * TRIGGER;
    let active = 0;
    sections.forEach((el, i) => {
      if (el.getBoundingClientRect().top <= triggerY) active = i;
    });
    return active;
  }

  // ── Pin graphic robustly (instead of relying on sticky) ─────
  function pinGraphic() {
    const sectionRect = sectionEl.getBoundingClientRect();
    const rightRect   = rightCol.getBoundingClientRect();

    const sectionTopAbs = window.scrollY + sectionRect.top;
    const sectionBottomAbs = window.scrollY + sectionRect.bottom;

    const graphicHeight = pinWrap.offsetHeight || 540;

    const startPin = sectionTopAbs + PIN_TOP;
    const endPin = sectionBottomAbs - graphicHeight - PIN_TOP;

    const y = window.scrollY;

    // Ensure right column can contain absolute states
    rightCol.style.position = 'relative';

    if (y < startPin) {
      // Before: sits at top of right column
      pinWrap.style.position = 'absolute';
      pinWrap.style.top = '0';
      pinWrap.style.left = '0';
      pinWrap.style.width = '100%';
    } else if (y <= endPin) {
      // During: fixed in viewport
      pinWrap.style.position = 'fixed';
      pinWrap.style.top = PIN_TOP + 'px';
      pinWrap.style.left = rightRect.left + 'px';
      pinWrap.style.width = rightRect.width + 'px';
    } else {
      // After: locked to bottom of right column area
      pinWrap.style.position = 'absolute';
      pinWrap.style.top = (rightCol.offsetHeight - graphicHeight) + 'px';
      pinWrap.style.left = '0';
      pinWrap.style.width = '100%';
    }
  }

  // ── Unified update loop ──────────────────────────────────────
  function update() {
    const step = getActiveStep();
    if (step !== currentStep) {
      applyState(step);
      currentStep = step;
    }
    pinGraphic();
  }

  // ── Init ─────────────────────────────────────────────────────
  applyState(0);
  currentStep = 0;
  sectionEl.classList.add('is-ready');
  update();

  let ticking = false;
  function requestTick() {
    if (!ticking) {
      requestAnimationFrame(function () {
        update();
        ticking = false;
      });
      ticking = true;
    }
  }

  window.addEventListener('scroll', requestTick, { passive: true });
  window.addEventListener('resize', requestTick);
})();
