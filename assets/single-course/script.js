/**
 * Single Course Page JavaScript
 * Handles interactive functionality for the course page
 */

document.addEventListener("DOMContentLoaded", function () {
	// Course Content Card - Week Toggle Functionality
	const weekHeaders = document.querySelectorAll(".week-header");

	weekHeaders.forEach((header) => {
		header.addEventListener("click", function () {
			const weekContainer = this.parentElement;
			const isOpen = weekContainer.classList.contains("open");

			// Close all weeks first
			document.querySelectorAll(".week-container").forEach((container) => {
				container.classList.remove("open");
			});

			// Open clicked week if it wasn't open
			if (!isOpen) {
				weekContainer.classList.add("open");
			}
		});
	});

	// Show More / Show Less for Course Description
	const showMoreBtn = document.querySelector(".show-more-btn");
	if (showMoreBtn) {
		showMoreBtn.addEventListener("click", function () {
			const card = this.closest(".description-card");
			if (!card) return;

			const isExpanded = card.classList.toggle("expanded");

			// Update button text and aria attribute
			this.textContent = isExpanded ? "عرض أقل" : "عرض المزيد";
			this.setAttribute("aria-expanded", isExpanded ? "true" : "false");

			// If expanded, smoothly scroll to reveal top of card (optional)
			if (isExpanded) {
				card.scrollIntoView({ behavior: "smooth", block: "start" });
			}
		});
	}

	// Replace all lecture-icon SVGs with the Subtract image from the project
	document.querySelectorAll("svg.lecture-icon").forEach((svg) => {
		try {
			const img = document.createElement("img");
			img.className = "lecture-icon";
			img.src = window.learnsimplyThemeUri
				? window.learnsimplyThemeUri + "/assets/img/Subtract.png"
				: "/wp-content/themes/edublink-child/assets/img/Subtract.png";
			img.alt = "أيقونة فيديو";
			svg.parentNode.replaceChild(img, svg);
		} catch (e) {
			console.warn("Failed to replace lecture svg icon", e);
		}
	});

	// Add to Cart functionality for WooCommerce products
	// No AJAX - let the link work naturally, PHP redirect will handle it
	const addToCartButtons = document.querySelectorAll(
		".add-to-cart-button"
	);
	addToCartButtons.forEach((button) => {
		// Just add loading state, don't prevent default
		button.addEventListener("click", function (e) {
			// Show loading state
			const originalText = this.textContent;
			this.textContent = "جاري الإضافة...";
			this.disabled = true;
			
			// Let the link work naturally - PHP redirect will handle the redirect
			// No preventDefault, no AJAX - simple and reliable
		});
	});
});

// Share course function
function shareCourse() {
	if (navigator.share) {
		navigator
			.share({
				title: document.title,
				text: document.querySelector(".course-title")?.textContent || "",
				url: window.location.href,
			})
			.catch((error) => {
				console.log("Error sharing:", error);
			});
	} else {
		// Fallback: copy to clipboard
		navigator.clipboard.writeText(window.location.href).then(
			() => {
				alert("تم نسخ رابط الكورس!");
			},
			() => {
				alert("فشل نسخ الرابط");
			}
		);
	}
}

// Enroll course function (for free courses)
function enrollCourse() {
	// This will be handled by Tutor LMS enrollment form
	const form = document.querySelector(".custom-enroll-form");
	if (form) {
		form.submit();
	}
}

// Load More Reviews functionality
document.addEventListener("DOMContentLoaded", function () {
	const loadMoreBtn = document.getElementById("load-more-reviews");
	if (loadMoreBtn) {
		let showingAll = false;
		
		loadMoreBtn.addEventListener("click", function () {
			const hiddenReviews = document.querySelectorAll(".review-card.hidden-review");
			
			if (!showingAll) {
				// Show all reviews
				hiddenReviews.forEach((review, index) => {
					setTimeout(() => {
						review.classList.add("show-review");
					}, index * 100); // Stagger animation
				});
				
				// Update button text
				this.innerHTML = '<span>إخفاء التقييمات</span>';
				this.classList.add("hide-reviews");
				showingAll = true;
			} else {
				// Hide reviews
				hiddenReviews.forEach((review) => {
					review.classList.remove("show-review");
				});
				
				// Update button text
				const count = hiddenReviews.length;
				this.innerHTML = '<span>عرض المزيد من التقييمات</span><span class="reviews-count">(' + count + ' تقييم إضافي)</span>';
				this.classList.remove("hide-reviews");
				showingAll = false;
				
				// Scroll back to reviews section
				document.querySelector(".reviews-section")?.scrollIntoView({ behavior: "smooth", block: "start" });
			}
		});
	}
});

// Star Rating Input functionality
document.addEventListener("DOMContentLoaded", function () {
	const starRatingInput = document.getElementById("star-rating-input");
	if (!starRatingInput) return;
	
	const stars = starRatingInput.querySelectorAll(".star-icon");
	const ratingInput = document.getElementById("tutor_rating_gen_input");
	
	if (!stars.length || !ratingInput) return;
	
	let currentRating = parseInt(ratingInput.value) || 0;
	
	// Apply initial visual state
	updateStarsVisual(currentRating);
	
	// Hover effect
	stars.forEach((star) => {
		star.addEventListener("mouseenter", function () {
			const rating = parseInt(this.dataset.rating);
			updateStarsVisual(rating, true);
		});
		
		star.addEventListener("mouseleave", function () {
			updateStarsVisual(currentRating);
		});
		
		// Click to select rating
		star.addEventListener("click", function () {
			currentRating = parseInt(this.dataset.rating);
			ratingInput.value = currentRating;
			updateStarsVisual(currentRating);
		});
	});
	
	function updateStarsVisual(rating, isHover = false) {
		stars.forEach((star) => {
			const starRating = parseInt(star.dataset.rating);
			star.classList.remove("filled", "hovered");
			
			if (starRating <= rating) {
				star.classList.add(isHover ? "hovered" : "filled");
			}
		});
	}
});

// Review Form Submission via AJAX (Tutor LMS)
document.addEventListener("DOMContentLoaded", function () {
	const reviewForm = document.getElementById("tutor-review-form");
	if (!reviewForm) return;
	
	reviewForm.addEventListener("submit", function (e) {
		e.preventDefault();
		
		const submitBtn = reviewForm.querySelector(".submit-review-btn");
		const messageDiv = document.getElementById("review-message");
		const ratingInput = document.getElementById("tutor_rating_gen_input");
		const reviewTextarea = reviewForm.querySelector('textarea[name="review"]');
		const courseIdInput = reviewForm.querySelector('input[name="course_id"]');
		const reviewIdInput = reviewForm.querySelector('input[name="review_id"]');
		
		// Validate rating
		if (!ratingInput.value || parseInt(ratingInput.value) < 1) {
			showMessage(messageDiv, "يرجى اختيار تقييم من النجوم", "error");
			return;
		}
		
		// Validate review text
		if (!reviewTextarea.value.trim()) {
			showMessage(messageDiv, "يرجى كتابة تقييمك", "error");
			return;
		}
		
		// Disable submit button
		submitBtn.disabled = true;
		submitBtn.innerHTML = '<span>جاري الإرسال...</span>';
		
		// Prepare form data using FormData from the form itself
		const formData = new FormData(reviewForm);
		
		// Send AJAX request
		fetch(window.ajaxurl || "/wp-admin/admin-ajax.php", {
			method: "POST",
			body: formData,
			credentials: "same-origin"
		})
		.then(response => response.json())
		.then(data => {
			if (data.success) {
				showMessage(messageDiv, "تم إرسال تقييمك بنجاح! سيظهر بعد المراجعة.", "success");
				// Optionally reload after a delay
				setTimeout(() => {
					window.location.reload();
				}, 2000);
			} else {
				const errorMsg = data.data?.message || data.message || "حدث خطأ أثناء إرسال التقييم";
				showMessage(messageDiv, errorMsg, "error");
				submitBtn.disabled = false;
				submitBtn.innerHTML = '<span>إرسال التقييم</span>';
			}
		})
		.catch(error => {
			console.error("Review submission error:", error);
			showMessage(messageDiv, "حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.", "error");
			submitBtn.disabled = false;
			submitBtn.innerHTML = '<span>إرسال التقييم</span>';
		});
	});
	
	function showMessage(el, message, type) {
		if (!el) return;
		el.textContent = message;
		el.className = "review-message " + type;
		el.style.display = "block";
	}
});

