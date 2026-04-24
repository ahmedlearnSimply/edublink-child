/**
 * About Me Page Scripts
 * 
 * Custom JavaScript for the About Me page
 * Located in: /assets/about-me/script.js
 * 
 * @package EduBlink_Child
 */

(function() {
	'use strict';

	// Wait for DOM to be ready
	document.addEventListener('DOMContentLoaded', function() {
		// Testimonials Slider (if exists on page)
		initTestimonialsSlider();
		
		// FAQ Accordion (if exists on page)
		initFAQAccordion();
		
		// Smooth Scroll for anchor links
		initSmoothScroll();
	});

	/**
	 * Smooth Scroll for Anchor Links
	 */
	function initSmoothScroll() {
		document.querySelectorAll('a[href^="#"]').forEach(anchor => {
			anchor.addEventListener('click', function (e) {
				e.preventDefault();
				const targetId = this.getAttribute('href');
				if (targetId === '#') return;
				
				const targetElement = document.querySelector(targetId);
				if (targetElement) {
					// Check if there is a sticky header to offset
					const headerOffset = 90; 
					const elementPosition = targetElement.getBoundingClientRect().top;
					const offsetPosition = elementPosition + window.pageYOffset - headerOffset;

					window.scrollTo({
						top: offsetPosition,
						behavior: 'smooth'
					});
				}
			});
		});
	}

	/**
	 * Initialize Testimonials Slider
	 */
	function initTestimonialsSlider() {
		const grid = document.getElementById("testimonialsGrid");
		const prevBtn = document.getElementById("prevBtn");
		const nextBtn = document.getElementById("nextBtn");

		if (!grid || !prevBtn || !nextBtn) return;
		
		// Mark as initialized to prevent global script from interfering
		grid.dataset.sliderInitialized = 'true';

		let currentIndex = 0;
		const cards = Array.from(
			grid.querySelectorAll(".learnsimply-new-testimonial-card")
		);
		const totalCards = cards.length;

		// Determine cards per view based on screen size
		function getCardsPerView() {
			if (window.innerWidth >= 1200) return 3;
			if (window.innerWidth >= 768) return 2;
			return 1;
		}

		function getCardWidth() {
			if (cards.length === 0) return 0;
			const cardWidth = cards[0].offsetWidth;
			const gap = 24; // gap from CSS
			return cardWidth + gap;
		}

		function updateSlider() {
			const cardsPerView = getCardsPerView();
			const maxIndex = Math.max(0, totalCards - cardsPerView);

			// Clamp current index
			currentIndex = Math.min(Math.max(0, currentIndex), maxIndex);

			// Calculate and apply transform (positive for RTL)
			const cardWidth = getCardWidth();
			const translateX = currentIndex * cardWidth;
			
			// Use positive translateX for RTL layout
			grid.style.transform = `translateX(${translateX}px)`;

			// Update button states with visual feedback
			prevBtn.disabled = currentIndex === 0;
			nextBtn.disabled = currentIndex >= maxIndex;
			
			// Add/remove disabled class for additional styling
			prevBtn.classList.toggle('is-disabled', currentIndex === 0);
			nextBtn.classList.toggle('is-disabled', currentIndex >= maxIndex);
		}

		// Previous button - go to previous cards (move right in RTL)
		prevBtn.addEventListener("click", function () {
			if (currentIndex > 0) {
				currentIndex--;
				updateSlider();
			}
		});

		// Next button - go to next cards (move left in RTL)
		nextBtn.addEventListener("click", function () {
			const cardsPerView = getCardsPerView();
			const maxIndex = totalCards - cardsPerView;
			if (currentIndex < maxIndex) {
				currentIndex++;
				updateSlider();
			}
		});

		// Update on window resize
		let resizeTimeout;
		window.addEventListener("resize", function () {
			clearTimeout(resizeTimeout);
			resizeTimeout = setTimeout(function () {
				currentIndex = 0; // Reset to first slide on resize
				updateSlider();
			}, 250);
		});

		// Initial setup
		updateSlider();
	}

	/**
	 * Initialize FAQ Accordion
	 */
	function initFAQAccordion() {
		// FAQ uses onclick="toggleFaq(this)" in the markup (matching the reference project),
		// and the function is defined globally in assets/global/script.js.
		// Avoid attaching extra listeners here to prevent double-toggling/conflicts.
		return;
	}

})();

