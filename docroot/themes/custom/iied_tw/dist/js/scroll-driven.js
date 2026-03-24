(function () {

  // Only run desktop behaviour.
  if (window.innerWidth < 1024) return;

  // ── Config ──────────────────────────────────────────────────
  const TRIGGER_PX = 96; // px from top of viewport to trigger step changes
  const PIN_TOP = 32;    // px from top when graphic is pinned (≈ top-8)
  const REDUCE_MOTION = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ── Layer states [stepIndex][layerIndex] ────────────────────
  const STATES = [
    [
      { opacity: 1, scale: 1    },
      { opacity: 0, scale: 1    },
      { opacity: 0, scale: 1    },
    ],
    [
      { opacity: 0, scale: 0.40 },
      { opacity: 1, scale: 1    },
      { opacity: 0, scale: 1    },
    ],
    [
      { opacity: 0, scale: 0.40 },
      { opacity: 0, scale: 0.40 },
      { opacity: 1, scale: 1    },
    ],
  ];

  // ── Elements ────────────────────────────────────────────────
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

      layer.classList.remove('is-exiting', 'is-entering');

      if (REDUCE_MOTION) {
        layer.style.transition = 'none';
      } else {
        layer.style.transition = '';
        if (prev !== null) {
          if (prev.opacity === 1 && next.opacity === 0) {
            layer.classList.add('is-exiting');
          } else if (prev.opacity === 0 && next.opacity === 1) {
            layer.classList.add('is-entering');
          }
        }
      }

      layer.style.opacity = next.opacity;
      layer.style.transform = `scale(${next.scale})`;
      layer.style.zIndex =
        next.opacity === 1 ? 3 :
        (prev && prev.opacity === 1) ? 2 :
        1;
    });

    sections.forEach((el, i) => {
      el.style.opacity = i === stepIndex ? '1' : '0.35';
    });
  }

  // ── Determine active text step ──────────────────────────────
  function getActiveStep() {
    const triggerY = TRIGGER_PX;
    let active = 0;
    sections.forEach((el, i) => {
      if (el.getBoundingClientRect().top <= triggerY) active = i;
    });
    return active;
  }

  // ── Pin graphic robustly (instead of relying on sticky) ─────
    function pinGraphic() {
      const sectionRect = sectionEl.getBoundingClientRect();
      const rightRect = rightCol.getBoundingClientRect();

      const sectionTopAbs = window.scrollY + sectionRect.top;
      const sectionBottomAbs = sectionTopAbs + sectionEl.offsetHeight;

      const graphicHeight = pinWrap.offsetHeight || 540;
      const startPin = sectionTopAbs + PIN_TOP;
      const endPin = sectionBottomAbs - graphicHeight - PIN_TOP;

      const y = window.scrollY;

      // Ensure anchor context
      rightCol.style.position = 'relative';

      // Reset conflicting props each frame
      pinWrap.style.left = '';
      pinWrap.style.right = '';
      pinWrap.style.top = '';
      pinWrap.style.bottom = '';
      pinWrap.style.width = '';
      pinWrap.style.visibility = 'visible';

      if (y < startPin) {
        // Before pin: top of right column
        pinWrap.style.position = 'absolute';
        pinWrap.style.top = '0';
        pinWrap.style.left = '0';
        pinWrap.style.width = '100%';

      } else if (y <= endPin) {
        // During pin: fixed in viewport
        pinWrap.style.position = 'fixed';
        pinWrap.style.top = PIN_TOP + 'px';
        pinWrap.style.left = rightRect.left + 'px';
        pinWrap.style.width = rightRect.width + 'px';

      } else {
        // After pin: lock to bottom of right column and scroll away naturally
        pinWrap.style.position = 'absolute';
        pinWrap.style.bottom = '0';
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
