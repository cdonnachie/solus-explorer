/*
 * ngex side navigation: open/close the off-canvas drawer.
 * Replaces the MDB sideNav plugin. No dependencies.
 *
 * - .button-collapse toggles the drawer (#slide-out) and the overlay.
 * - Clicking the overlay, pressing Escape, or following a menu link while
 *   off-canvas closes it.
 * - When the viewport grows past the breakpoint the drawer is pinned open by
 *   CSS, so the off-canvas state is reset.
 */
(function () {
  'use strict';
  var BREAKPOINT = 1440;

  function init() {
    var drawer = document.getElementById('slide-out');
    var toggles = document.querySelectorAll('.button-collapse');
    if (!drawer || !toggles.length) return;

    var overlay = document.getElementById('sidenav-overlay');
    if (!overlay) {
      overlay = document.createElement('div');
      overlay.id = 'sidenav-overlay';
      document.body.appendChild(overlay);
    }

    function isOffCanvas() { return window.innerWidth <= BREAKPOINT; }
    function open() {
      drawer.classList.add('open');
      overlay.classList.add('show');
      document.body.classList.add('sidenav-open');
    }
    function close() {
      drawer.classList.remove('open');
      overlay.classList.remove('show');
      document.body.classList.remove('sidenav-open');
    }
    function toggle(e) {
      e.preventDefault();
      if (drawer.classList.contains('open')) close(); else open();
    }

    Array.prototype.forEach.call(toggles, function (t) { t.addEventListener('click', toggle); });
    overlay.addEventListener('click', close);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
    drawer.addEventListener('click', function (e) {
      var a = e.target.closest ? e.target.closest('a[href]') : null;
      if (a && isOffCanvas() && !a.hasAttribute('data-toggle')) close();
    });
    window.addEventListener('resize', function () { if (!isOffCanvas()) close(); });

    // navbar gets .top-nav-collapse once the page is scrolled (skins style it)
    var bar = document.querySelector('.navbar.scrolling-navbar');
    if (bar) {
      var onScroll = function () { bar.classList.toggle('top-nav-collapse', window.scrollY > 100); };
      window.addEventListener('scroll', onScroll, { passive: true });
      onScroll();
    }
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
