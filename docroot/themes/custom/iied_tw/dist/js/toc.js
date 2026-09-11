/**
 * @file
 * Contents list: marks the heading most recently scrolled past, and smooths
 * the jump when a link in the list is followed.
 *
 * Expects one or more <nav data-toc> elements (paragraph--toc-list.html.twig)
 * whose links point at the ids of contents headings
 * (paragraph--toc-heading.html.twig). Every list on the page is updated
 * together, so a page with a list in two sections marks the same heading in
 * both.
 *
 * A heading becomes current once its top passes a line a quarter of the way
 * down the viewport, and stays current until the next one does – so a long
 * answer keeps its heading marked all the way through, scrolling either way.
 *
 * Positions are read on scroll, at most once per animation frame. An
 * IntersectionObserver (the pattern in dist/js/bg-colour.js) was the first
 * plan, but a jump or a dragged scrollbar can carry a heading past the line
 * between two observer callbacks and leave the wrong heading marked.
 */
(function (Drupal, once) {
  'use strict';

  // Fraction of the viewport height, from the top.
  const LINE = 0.25;

  Drupal.behaviors.iiedToc = {
    attach(context) {
      const navs = once('iied-toc', 'nav[data-toc]', context);
      if (!navs.length) {
        return;
      }

      // Every link in every list, grouped by the heading id it points at.
      const linksById = new Map();
      navs.forEach((nav) => {
        nav.querySelectorAll('a[href^="#"]').forEach((link) => {
          const id = decodeURIComponent(link.getAttribute('href').slice(1));
          if (!linksById.has(id)) {
            linksById.set(id, []);
          }
          linksById.get(id).push(link);
        });
      });

      // The headings themselves, in document order.
      const headings = [...linksById.keys()]
        .map((id) => document.getElementById(id))
        .filter(Boolean)
        .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1));
      if (!headings.length) {
        return;
      }

      let currentId;
      const setCurrent = (id) => {
        if (id === currentId) {
          return;
        }
        currentId = id;
        linksById.forEach((links, key) => {
          links.forEach((link) => {
            if (key === id) {
              link.setAttribute('aria-current', 'true');
            } else {
              link.removeAttribute('aria-current');
            }
          });
        });
      };

      // The last heading whose top is above the line; none before the first.
      const update = () => {
        const line = window.innerHeight * LINE;
        let current = null;
        for (const heading of headings) {
          if (heading.getBoundingClientRect().top > line) {
            break;
          }
          current = heading;
        }
        setCurrent(current ? current.id : null);
      };

      let queued = false;
      const queueUpdate = () => {
        if (queued) {
          return;
        }
        queued = true;
        window.requestAnimationFrame(() => {
          queued = false;
          update();
        });
      };
      window.addEventListener('scroll', queueUpdate, { passive: true });
      window.addEventListener('resize', queueUpdate, { passive: true });
      update();

      // Smooth jumps only for people who have not asked for reduced motion;
      // everyone else gets the browser's own jump, which the scroll listener
      // follows.
      if (!window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
        return;
      }
      linksById.forEach((links, id) => {
        links.forEach((link) => {
          link.addEventListener('click', (event) => {
            const target = document.getElementById(id);
            if (!target) {
              return;
            }
            event.preventDefault();
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
            window.history.pushState(null, '', '#' + encodeURIComponent(id));
            // Move focus as the browser's own jump would, so people using a
            // keyboard or screen reader carry on from the heading.
            if (!target.hasAttribute('tabindex')) {
              target.setAttribute('tabindex', '-1');
            }
            target.focus({ preventScroll: true });
          });
        });
      });
    },
  };
})(Drupal, once);
