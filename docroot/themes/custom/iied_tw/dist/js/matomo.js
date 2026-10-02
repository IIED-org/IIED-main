var _paq = window._paq = window._paq || [];
_paq.push(["disableCookies"]);
_paq.push(["setDomains", ["*.iied.org"]]);

(function () {
  // 1. Locate the article element containing the Drupal node classes
  const nodeElement = document.querySelector('article.node');
  
  if (nodeElement) {
    // 2. Scan the element's classes for the 'node--type-' prefix
    const typeClass = Array.from(nodeElement.classList).find(cls => cls.startsWith('node--type-'));
    
    if (typeClass) {
      // 3. Extract the content type (e.g., 'node--type-article' -> 'article')
      const contentType = typeClass.replace('node--type-', '');
    _paq.push(['setCustomDimension', 2, contentType]);

    }
  }

  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);

  var u="//matomo.iied.org/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '2']);

    // Add this code below within the Matomo JavaScript tracker code
    // Important: the tracker url includes the /matomo.php
    var secondaryTrackerUrl = 'https://iied.matomo.cloud/matomo.php';
    var secondaryWebsiteId = 1;
    // Also send all of the tracking data to this other Matomo server, in website ID 77
    _paq.push(['addTracker', secondaryTrackerUrl, secondaryWebsiteId]);
    // That's it!

    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.type='text/javascript'; g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
})();
