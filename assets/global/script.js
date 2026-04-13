/**
 * Learnsimply Global Scripts
 * Pure Vanilla JavaScript - No Libraries
 */

(function () {
  "use strict";

  // ===== UTILITY FUNCTIONS =====

  /**
   * Smoothly scroll to element
   */
  function smoothScrollTo(targetElement) {
    if (!targetElement) return;

    const headerOffset = 100;
    const elementPosition = targetElement.getBoundingClientRect().top;
    const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

    window.scrollTo({
      top: offsetPosition,
      behavior: "smooth",
    });
  }

  /**
   * Debounce function to limit execution rate
   */
  function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
      const later = () => {
        clearTimeout(timeout);
        func(...args);
      };
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
    };
  }

  // ===== HEADER NAVIGATION FUNCTIONALITY =====

  /**
   * Initialize header navigation menu items
   */
  function initHeaderNavigation() {
    const menuItems = document.querySelectorAll(
      ".learnsimply-header-menu-item",
    );

    if (!menuItems.length) return;

    menuItems.forEach((menuItem) => {
      menuItem.addEventListener("click", function (e) {
        const target = this.getAttribute("data-target");

        // Only prevent default if it's a hash link for smooth scroll
        if (target && target.startsWith("#")) {
          e.preventDefault();
          
          // Remove active state from all menu items
          menuItems.forEach((item) => {
            item.classList.remove("learnsimply-header-menu-item-active");
            const menuText = item.querySelector(".learnsimply-header-menu-text");
            if (menuText) {
              menuText.classList.remove("learnsimply-header-menu-text-active");
            }
          });

          // Add active state to clicked item
          this.classList.add("learnsimply-header-menu-item-active");
          const clickedMenuText = this.querySelector(
            ".learnsimply-header-menu-text",
          );
          if (clickedMenuText) {
            clickedMenuText.classList.add("learnsimply-header-menu-text-active");
          }

          // Smooth scroll to target section
          const targetElement = document.querySelector(target);
          if (targetElement) {
            smoothScrollTo(targetElement);
          }
        }
      });
    });
  }

  /**
   * Update active navigation on scroll
   */
  function updateHeaderNavigationOnScroll() {
    const sections = document.querySelectorAll("section[id]");
    const menuItems = document.querySelectorAll(
      ".learnsimply-header-menu-item",
    );

    if (!sections.length || !menuItems.length) return;

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            const sectionId = entry.target.getAttribute("id");

            menuItems.forEach((item) => {
              const target = item.getAttribute("data-target");

              if (target === `#${sectionId}`) {
                menuItems.forEach((mi) => {
                  mi.classList.remove("learnsimply-header-menu-item-active");
                  const text = mi.querySelector(
                    ".learnsimply-header-menu-text",
                  );
                  if (text)
                    text.classList.remove(
                      "learnsimply-header-menu-text-active",
                    );
                });

                item.classList.add("learnsimply-header-menu-item-active");
                const menuText = item.querySelector(
                  ".learnsimply-header-menu-text",
                );
                if (menuText) {
                  menuText.classList.add("learnsimply-header-menu-text-active");
                }
              }
            });
          }
        });
      },
      {
        threshold: 0.3,
        rootMargin: "-100px 0px -66%",
      },
    );

    sections.forEach((section) => observer.observe(section));
  }

  // ===== FAQ ACCORDION FUNCTIONALITY =====

  /**
   * Global FAQ toggle function (used in Twig templates via onclick)
   * This is the primary method for FAQ toggling
   */
  window.toggleFaq = function (button) {
    if (!button) return;
    
    const faqItem = button.closest(".faq-accordion-item");

    if (!faqItem) {
      console.error("Could not find FAQ item");
      return;
    }

    // Close all other FAQ items
    document.querySelectorAll(".faq-accordion-item").forEach((item) => {
      if (item !== faqItem) {
        item.classList.remove("active");
      }
    });

    // Toggle current FAQ item
    faqItem.classList.toggle("active");
  };

  /**
   * Initialize FAQ accordion using event delegation
   * This works as a fallback for FAQ items that don't have onclick="toggleFaq(this)"
   * Items with onclick will use the global toggleFaq function instead
   */
  function initFaqAccordion() {
    // Use event delegation but only for items without onclick
    document.addEventListener("click", function (e) {
      const question = e.target.closest(".faq-question");
      
      // Skip if the question has onclick handler (it will use toggleFaq)
      if (question && question.hasAttribute("onclick")) {
        return;
      }
      
      // Also support old class names for backwards compatibility
      const oldQuestion = e.target.closest(".learnsimply-faq-question");
      if (oldQuestion) {
        const oldFaqItem = oldQuestion.closest(".learnsimply-faq-item");
        if (oldFaqItem) {
          // Close all other FAQ items
          document.querySelectorAll(".learnsimply-faq-item").forEach((item) => {
            if (item !== oldFaqItem) {
              item.classList.remove("learnsimply-faq-item-active");
            }
          });
          // Toggle current FAQ item
          oldFaqItem.classList.toggle("learnsimply-faq-item-active");
        }
        return;
      }
      
      if (question) {
        const faqItem = question.closest(".faq-accordion-item");

        if (!faqItem) return;

        // Close all other FAQ items
        document.querySelectorAll(".faq-accordion-item").forEach((item) => {
          if (item !== faqItem) {
            item.classList.remove("active");
          }
        });

        // Toggle current FAQ item
        faqItem.classList.toggle("active");
      }
    });
  }

  // ===== TESTIMONIALS SLIDER FUNCTIONALITY =====

  /**
   * Initialize new testimonials grid slider
   */
  function initNewTestimonialsSlider() {
    const grid = document.getElementById("testimonialsGrid");
    const prevBtn = document.getElementById("prevBtn");
    const nextBtn = document.getElementById("nextBtn");
    
    if (!grid || !prevBtn || !nextBtn) return;
    
    // Skip if already initialized by page-specific script
    if (grid.dataset.sliderInitialized === 'true') {
      return;
    }

    const cards = Array.from(
      grid.querySelectorAll(".learnsimply-new-testimonial-card"),
    );
    if (!cards.length) return;

    const GAP = 24; // should match CSS gap

    function getSlideWidth() {
      const rect = cards[0].getBoundingClientRect();
      return Math.round(rect.width) + GAP;
    }

    function scrollToLeft(left) {
      grid.scrollTo({ left, behavior: "smooth" });
    }

    function nextSlide() {
      const slideWidth = getSlideWidth();
      const maxScroll = grid.scrollWidth - grid.clientWidth;
      let target = Math.round(grid.scrollLeft + slideWidth);
      if (target > maxScroll - 2) target = 0; // loop
      scrollToLeft(target);
    }

    function prevSlide() {
      const slideWidth = getSlideWidth();
      const maxScroll = grid.scrollWidth - grid.clientWidth;
      let target = Math.round(grid.scrollLeft - slideWidth);
      if (target < 2) target = maxScroll; // to end
      scrollToLeft(target);
    }

    prevBtn.addEventListener("click", function () {
      prevSlide();
      restartAuto();
    });

    nextBtn.addEventListener("click", function () {
      nextSlide();
      restartAuto();
    });

    // Auto-advance every 3s
    let autoId = setInterval(nextSlide, 3000);
    function restartAuto() {
      clearInterval(autoId);
      autoId = setInterval(nextSlide, 3000);
    }

    // Pause on interaction
    grid.addEventListener("mouseenter", () => clearInterval(autoId));
    grid.addEventListener("mouseleave", () => restartAuto());
    grid.addEventListener("pointerdown", () => clearInterval(autoId));
    grid.addEventListener("pointerup", () => restartAuto());

    document.addEventListener("visibilitychange", () => {
      if (document.hidden) clearInterval(autoId);
      else restartAuto();
    });

    // Handle resize - snap to nearest
    let resizeTimer;
    window.addEventListener("resize", function () {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(() => {
        const slide = getSlideWidth();
        const index = Math.round(grid.scrollLeft / slide);
        scrollToLeft(index * slide);
      }, 150);
    });
  }

  // ===== HEADER MOBILE MENU TOGGLE =====
  (function () {
    const mobileToggle = document.querySelector(
      ".learnsimply-header-mobile-toggle",
    );
    const navMenu = document.querySelector(
      ".learnsimply-header-navigation-menu",
    );

    if (mobileToggle && navMenu) {
      mobileToggle.addEventListener("click", function (e) {
        e.stopPropagation();
        this.classList.toggle("active");
        navMenu.classList.toggle("active");
      });

      // Close menu when clicking menu items
      const menuItems = document.querySelectorAll(
        ".learnsimply-header-menu-item",
      );
      menuItems.forEach(function (item) {
        item.addEventListener("click", function () {
          mobileToggle.classList.remove("active");
          navMenu.classList.remove("active");
        });
      });

      // Close menu when clicking outside
      document.addEventListener("click", function (event) {
        const isClickInside =
          mobileToggle.contains(event.target) || navMenu.contains(event.target);

        if (!isClickInside && navMenu.classList.contains("active")) {
          mobileToggle.classList.remove("active");
          navMenu.classList.remove("active");
        }
      });

      // Close menu on window resize if screen gets larger
      window.addEventListener(
        "resize",
        debounce(function () {
          if (window.innerWidth > 900 && navMenu.classList.contains("active")) {
            mobileToggle.classList.remove("active");
            navMenu.classList.remove("active");
          }
        }, 250),
      );
    }
  })();

  // ===== SCROLL TO TOP BUTTON =====

  /**
   * Initialize scroll to top button
   */
  function initScrollToTop() {
    const scrollButton = document.querySelector(".learnsimply-scroll-to-top");

    if (!scrollButton) return;

    // Show/hide button based on scroll position
    function toggleScrollButton() {
      if (window.pageYOffset > 300) {
        scrollButton.classList.add("learnsimply-visible");
      } else {
        scrollButton.classList.remove("learnsimply-visible");
      }
    }

    // Scroll to top on click
    scrollButton.addEventListener("click", function () {
      window.scrollTo({
        top: 0,
        behavior: "smooth",
      });
    });

    // Listen to scroll events
    window.addEventListener("scroll", debounce(toggleScrollButton, 100));

    // Initial check
    toggleScrollButton();
  }

  // ===== HEADER SCROLL EFFECT =====

  /**
   * Add background blur effect to header on scroll
   */
  function initHeaderScrollEffect() {
    const header = document.querySelector(".learnsimply-header-main-container");

    if (!header) return;

    function updateHeaderStyle() {
      if (window.pageYOffset > 50) {
        header.style.backgroundColor = "rgba(10, 15, 26, 0.95)";
        header.style.backdropFilter = "blur(20px)";
        header.style.webkitBackdropFilter = "blur(20px)";
      } else {
        header.style.backgroundColor = "rgba(10, 15, 26, 0.8)";
        header.style.backdropFilter = "blur(10px)";
        header.style.webkitBackdropFilter = "blur(10px)";
      }
    }

    window.addEventListener("scroll", debounce(updateHeaderStyle, 50));
  }

  // ===== MARQUEE ANIMATION =====

  /**
   * Setup marquee animation with dynamic speed calculation
   */
  function setupMarquee() {
    const content = document.querySelector(".marquee-content");
    const track = document.querySelector(".marquee-track");
    if (!content || !track) return;

    function recalc() {
      // width of one track (including gaps)
      const trackWidth = track.getBoundingClientRect().width;
      // desired speed in pixels per second
      const speed = 120; // px/s
      const duration = Math.max(8, Math.round(trackWidth / speed));

      content.style.setProperty("--track-width", trackWidth + "px");
      content.style.setProperty("--marquee-duration", duration + "s");
    }

    // initial calc
    recalc();

    // recalc on resize
    let resizeTimer;
    window.addEventListener("resize", () => {
      clearTimeout(resizeTimer);
      resizeTimer = setTimeout(recalc, 150);
    });
  }

  // ===== PROMO COUNTDOWN TIMER =====

  /**
   * Initialize promo banner countdown
   * Uses deadline from window.learnsimplyPromoDeadline (set by wp_localize_script)
   */
  function initPromoCountdown() {
    var daysEl = document.getElementById("promo-days");
    var hoursEl = document.getElementById("promo-hours");
    var minutesEl = document.getElementById("promo-minutes");
    var secondsEl = document.getElementById("promo-seconds");

    if (!daysEl || !hoursEl || !minutesEl || !secondsEl) return;

    var STORAGE_KEY = "learnsimply_promo_deadline";
    var deadline = window.learnsimplyPromoDeadline
      ? parseInt(window.learnsimplyPromoDeadline, 10)
      : null;

    if (!deadline || isNaN(deadline)) {
      deadline = parseInt(localStorage.getItem(STORAGE_KEY), 10);
    }
    if (!deadline || isNaN(deadline)) {
      deadline = Date.now() + 3 * 24 * 60 * 60 * 1000;
      localStorage.setItem(STORAGE_KEY, deadline);
    }

    function pad(n) {
      return n.toString().padStart(2, "0");
    }

    function tick() {
      var diff = deadline - Date.now();

      if (diff <= 0) {
        daysEl.textContent = "00";
        hoursEl.textContent = "00";
        minutesEl.textContent = "00";
        secondsEl.textContent = "00";
        clearInterval(timer);
        return;
      }

      var totalSeconds = Math.floor(diff / 1000);
      var secs = totalSeconds % 60;
      var mins = Math.floor(totalSeconds / 60) % 60;
      var hrs = Math.floor(totalSeconds / 3600) % 24;
      var days = Math.floor(totalSeconds / 86400);

      daysEl.textContent = pad(days);
      hoursEl.textContent = pad(hrs);
      minutesEl.textContent = pad(mins);
      secondsEl.textContent = pad(secs);
    }

    tick();
    var timer = setInterval(tick, 1000);
  }

  // ===== REVIEW "عرض المزيد" EXPAND/COLLAPSE =====

  /**
   * Initialize review "Show more" / "Show less" toggle
   * Used on product, bundle, and course review cards
   */
  function initReviewShowMore() {
    document.addEventListener("click", function (e) {
      const btn = e.target.closest(".show-more-review");
      if (!btn) return;

      const p = btn.closest(".review-text-content");
      if (!p) return;

      const full = p.getAttribute("data-full");
      const short = p.getAttribute("data-short");
      if (!full) return;

      e.preventDefault();

      if (p.classList.contains("expanded")) {
        p.innerHTML = (short || full.slice(0, 200) + "...") + ' <button type="button" class="show-more-review">عرض المزيد</button>';
        p.classList.remove("expanded");
      } else {
        var escaped = full.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;");
        p.innerHTML = escaped + ' <button type="button" class="show-more-review">عرض أقل</button>';
        p.classList.add("expanded");
      }
    });
  }

  // ===== ADD TO CART LOADER =====

  function showAddToCartLoader() {
    var existingLoader = document.querySelector(".learnsimply-cart-loader");
    if (existingLoader) {
      existingLoader.classList.add("is-visible");
      return;
    }

    var loader = document.createElement("div");
    loader.className = "learnsimply-cart-loader is-visible";
    loader.setAttribute("role", "status");
    loader.setAttribute("aria-live", "polite");
    loader.innerHTML =
      '<div class="learnsimply-cart-loader-box">' +
      '<span class="learnsimply-cart-loader-spinner" aria-hidden="true"></span>' +
      '<span class="learnsimply-cart-loader-text">جاري إضافة المنتج إلى السلة...</span>' +
      "</div>";

    document.body.appendChild(loader);
  }

  function initAddToCartLoader() {
    document.addEventListener("click", function (e) {
      var link = e.target.closest('a[href*="add-to-cart="]');
      if (!link) return;
      if (e.defaultPrevented || link.target === "_blank" || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;

      showAddToCartLoader();
      link.setAttribute("aria-busy", "true");
    });
  }

  // ===== INITIALIZE ALL FUNCTIONALITY =====

  /**
   * Main initialization function
   */
  function init() {
    // Wait for DOM to be fully loaded
    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", init);
      return;
    }

    initHeaderNavigation();
    updateHeaderNavigationOnScroll();
    initFaqAccordion();
    initNewTestimonialsSlider();
    initScrollToTop();
    initHeaderScrollEffect();
    setupMarquee();
    initPromoCountdown();
    initReviewShowMore();
    initAddToCartLoader();
  }

  // Start initialization
  init();
})();
