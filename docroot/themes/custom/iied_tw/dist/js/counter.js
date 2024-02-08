function shouldReduceMotion() {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    return mediaQuery.matches;
  }
  
  function isInViewport(el) {
    const rect = el.getBoundingClientRect();
    return rect.top >= 0 && rect.bottom <= window.innerHeight;
  }
  
  function animateNumber(el) {
    const data = parseInt(el.dataset.n, 10);
    let count = 0;
    const duration = 1000; // Total duration for the animation
    const steps = 100; // Number of steps in the animation
    const increment = data / steps; // Increment each step by this amount
    const stepTime = duration / steps; // Time each step takes
    
    const intervalId = setInterval(() => {
      count = Math.min(count + increment, data);
      el.textContent = Math.ceil(count).toString();
      
      if (count === data) {
        clearInterval(intervalId); // Clear interval when final number is reached
        if (el.dataset.sym) {
          el.textContent += el.dataset.sym; // Append symbol if any
        }
      }
    }, stepTime);
  }
  
  document.addEventListener('DOMContentLoaded', function () {
    if (shouldReduceMotion()) {
      console.log('User prefers reduced motion. No animations will be initiated.');
      return; // Do not initiate animations.
    }
  
    const statNumbers = document.querySelectorAll('.stat-number');
    const animated = new Set(); // Set to track which numbers have been animated
  
    // Initialize number elements to '0' if JavaScript is enabled
    statNumbers.forEach((el) => {
      el.textContent = '0';
    });
  
    // Check each number and animate it if it's in the viewport
    statNumbers.forEach((el) => {
      if (isInViewport(el)) {
        animateNumber(el);
        animated.add(el); // Mark as animated
      }
    });
  
    // Add scroll event listener
    window.addEventListener('scroll', () => {
      statNumbers.forEach((el) => {
        if (!animated.has(el) && isInViewport(el)) { // Animate on scroll into view
          animateNumber(el);
          animated.add(el); // Mark as animated
        }
      });
    });
  });
  