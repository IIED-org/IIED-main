/*
Mini code for animating with CSS by adding classes when the target is in view
HOW TO USE:
Add '.animate' class to your div, this will then add an '.active-animation' class when it's active
Optional: add '.repeat-animation' class so that the animation repeats when the item goes out of view again
*/
(() => {
  window.addEventListener('load', () => {
    const animateTargets = document.querySelectorAll('.animate');

    if (animateTargets && animateTargets.length > 0) {
      /**
       * Our IntersectionObserver for animations.
       * @type {Object}
       * */
      const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add('active-animation');
            if (!entry.target.classList.contains('repeat-animation')) {
              observer.unobserve(entry.target);
            }
          } else if (entry.target.classList.contains('repeat-animation')) {
            entry.target.classList.remove('active-animation');
          }
        });
      });

      animateTargets.forEach((target) => {
        // If device has JS disabled, dont animate.
        target.classList.add('enable-animation');
        setTimeout(observer.observe(target), 5000);
      });
    }
  });
})();
