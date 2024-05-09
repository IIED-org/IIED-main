document.addEventListener('DOMContentLoaded', () => {
  const section = document.getElementById('dynamic-bg');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      // Add 'bg-active' class when the section intersects the viewport
      if (entry.isIntersecting) {
        section.classList.add('bg-active');
      } else {
        section.classList.remove('bg-active');
      }
    });
  }, { threshold: 0.2 });
      // 0.1 = 10% of the target's visibility passes the threshold within the viewport. 

  observer.observe(section);
});

