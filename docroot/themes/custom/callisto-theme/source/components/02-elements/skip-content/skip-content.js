/* Here we want to add a fallback for the page in case the anchor element is missing. */
(() => {
  const skipButton = document.querySelector('.skip-content a');
  const skipAnchor = document.getElementById('main-content');

  // If the anchor isn't found then we just put in the generic block ID
  if (!skipAnchor && skipButton && skipButton.href) {
    skipButton.href = '#block-mainpagecontent';
  }
})();
