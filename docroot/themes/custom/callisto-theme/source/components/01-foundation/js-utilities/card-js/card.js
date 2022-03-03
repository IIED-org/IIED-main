/* Code for making divs clickable cards */
/* Why? Because it's more accessible than making the whole card a link or floating a link over the content of the card */
/* Some of the best practice information is from https://inclusive-components.design/cards/ */
// HOW TO USE:
// Add '.card' class to your div
// Optional: add '.card-link' class to your link -/- otherwise it will use the first anchor available
(() => {
  window.addEventListener('load', () => {
    const cards = document.querySelectorAll('.card');

    if (cards && cards.length > 0) {
      cards.forEach((card) => {
        // Get the card-link anchor
        // If that doesn't exist lets use the first anchor we find
        const cardLink = card.querySelector('.card-link') ? card.querySelector('.card-link') : card.querySelector('a');
        let down,
          up;

        // Allow the user to select text,
        // so we compare timings between mousedown and mouseup
        if (cardLink) {
          card.addEventListener('mousedown', () => {
            down = +new Date();
          });

          card.addEventListener('mouseup', (e) => {
            up = +new Date();
            if (((up - down) < 200) && cardLink !== e.target) {
              cardLink.click();
            }
          });
        }
      });
    }
  });
})();
