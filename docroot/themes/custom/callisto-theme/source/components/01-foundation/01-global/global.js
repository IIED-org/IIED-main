/* eslint-disable */
// Some common vars that won't change across scripts
const body = document.getElementsByTagName('body')[0]
const headerContainer = document.querySelector('.header-container')
const header = document.querySelector('.main-header')
const adminRegion = document.querySelector('.admin-region')
/* eslint-enable */

// Declare our helperFunctions here so that we can reuse them more easily
// eslint-disable-next-line no-unused-vars
const helperFunctions = {

  // Limit a number between a custom range
  limitNumberWithinRange(num, min, max) {
    const MIN = min || 1,
      MAX = max || 10,
      parsed = parseInt(num, 10);

    return Math.min(Math.max(parsed, MIN), MAX);
  },

  // Loops through parents to find distance from top of document for an element
  getDistanceFromTop(element) {
    let distance = 0;

    // Loop up the DOM
    // Is this the best way? I couldn't find a better way :/
    if (element.offsetParent) {
      do {
        distance += element.offsetTop;
        element = element.offsetParent;
      } while (element);
    }
    return distance < 0 ? 0 : distance;
  },
};


// eslint-disable-next-line no-console, quotes
console.log(`This website is made by %cAgile Collective`, 'color: #F35C49; font-family: \'Apercu\', \'Apercu Pro\', \'Mukta\', aria, sans-serif; background: #23334B; padding: 12px; font-size: 22px;');
// eslint-disable-next-line no-console
console.log('https://www.agile.coop/');
