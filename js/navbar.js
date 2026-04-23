/**
 * Mobile hamburger menu - vanilla JavaScript
 * Toggles the dropdown menu when hamburger is tapped.
 * Closes the menu first, then scrolls to anchor sections smoothly
 * using the sticky navbar height as the offset.
 */
(function () {
  "use strict";

  function initNavbar() {
    var hamburger = document.querySelector(".nav-hamburger");
    var navbar = document.querySelector(".navbar");
    var dropdownLinks = document.querySelectorAll(".nav-dropdown .nav-links a");
    var logoLink = document.querySelector(".nav-logo-link");

    if (!hamburger || !navbar) return;

    function toggleMenu() {
      var isOpen = navbar.classList.contains("navbar--open");
      navbar.classList.toggle("navbar--open", !isOpen);
      hamburger.setAttribute("aria-expanded", String(!isOpen));
    }

    function closeMenu() {
      navbar.classList.remove("navbar--open");
      hamburger.setAttribute("aria-expanded", "false");
    }

    function getHeaderOffset() {
      return navbar.offsetHeight || 0;
    }

    function scrollToTarget(targetSelector) {
      var target = document.querySelector(targetSelector);
      if (!target) return;

      var headerOffset = getHeaderOffset();
      var targetTop = target.getBoundingClientRect().top + window.pageYOffset;
      var scrollPosition = targetTop - headerOffset;

      window.scrollTo({
        top: Math.max(scrollPosition, 0),
        behavior: "smooth"
      });
    }

    hamburger.addEventListener("click", function () {
      toggleMenu();
    });

    dropdownLinks.forEach(function (link) {
      link.addEventListener("click", function (e) {
        var href = link.getAttribute("href");

        if (href && href.startsWith("#")) {
          e.preventDefault();
          closeMenu();

          requestAnimationFrame(function () {
            requestAnimationFrame(function () {
              scrollToTarget(href);
            });
          });
        } else {
          closeMenu();
        }
      });
    });

    if (logoLink) {
      logoLink.addEventListener("click", function (e) {
        var href = logoLink.getAttribute("href");

        if (href && href.startsWith("#")) {
          e.preventDefault();

          if (navbar.classList.contains("navbar--open")) {
            closeMenu();

            requestAnimationFrame(function () {
              requestAnimationFrame(function () {
                scrollToTarget(href);
              });
            });
          } else {
            scrollToTarget(href);
          }
        }
      });
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initNavbar);
  } else {
    initNavbar();
  }
})();