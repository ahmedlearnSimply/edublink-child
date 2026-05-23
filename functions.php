<?php

// DEPLOY TEST - April 28 2026
/**
 * EduBlink Child Theme functions and definitions
 *
 * @package EduBlink_Child
 */

if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

/**
 * Disable public REST API endpoints that expose user data and admin status.
 * Without this filter, /wp-json/wp/v2/users leaks usernames + is_super_admin
 * for every registered user, enabling targeted brute-force attempts.
 */
add_filter('rest_endpoints', function ($endpoints) {
	if (isset($endpoints['/wp/v2/users'])) {
		unset($endpoints['/wp/v2/users']);
	}
	if (isset($endpoints['/wp/v2/users/(?P<id>[\d]+)'])) {
		unset($endpoints['/wp/v2/users/(?P<id>[\d]+)']);
	}
	return $endpoints;
});

/**
 * Enqueue IBM Plex Sans Arabic from Google Fonts (site-wide font)
 */
add_action('wp_enqueue_scripts', 'learnsimply_enqueue_ibm_plex_font', 1);
function learnsimply_enqueue_ibm_plex_font()
{
	wp_enqueue_style(
		'ibm-plex-sans-arabic',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap',
		array(),
		null
	);
}

add_action('wp_enqueue_scripts', 'learnsimply_enqueue_single_post_styles');

/**
 * Enqueue single post page styles.
 */
function learnsimply_enqueue_single_post_styles()
{
	if (!is_single() || get_post_type() !== 'post') {
		return;
	}
	$file = get_stylesheet_directory() . '/assets/single-post/style.css';
	if (file_exists($file)) {
		wp_enqueue_style(
			'learnsimply-single-post',
			get_stylesheet_directory_uri() . '/assets/single-post/style.css',
			array(),
			filemtime($file)
		);
	}
}

// ──────────────────────────────────────────────
// Legal Pages: Terms & Privacy
// ──────────────────────────────────────────────

/**
 * Auto-create Terms & Privacy pages on init if they don't exist yet.
 * Runs once and stores a flag so it never duplicates.
 */
add_action('init', 'learnsimply_create_legal_pages');
function learnsimply_create_legal_pages()
{
	if (get_option('learnsimply_legal_pages_created')) {
		return;
	}

	$pages = array(
		array(
			'post_title' => 'الشروط والأحكام',
			'post_name' => 'terms-conditions',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_content' => '',
		),
		array(
			'post_title' => 'سياسة الخصوصية',
			'post_name' => 'privacy-policy',
			'post_status' => 'publish',
			'post_type' => 'page',
			'post_content' => '',
		),
	);

	foreach ($pages as $page_data) {
		$existing = get_page_by_path($page_data['post_name'], OBJECT, 'page');
		if (!$existing) {
			wp_insert_post($page_data);
		}
	}

	update_option('learnsimply_legal_pages_created', true);
}

/**
 * Force Terms & Privacy pages to use our custom templates.
 */
add_filter('template_include', 'learnsimply_force_legal_templates', 999998);
function learnsimply_force_legal_templates($template)
{
	if (is_page('terms-conditions')) {
		$custom = get_stylesheet_directory() . '/page-terms.php';
		if (file_exists($custom)) {
			return $custom;
		}
	}
	if (is_page('privacy-policy')) {
		$custom = get_stylesheet_directory() . '/page-privacy.php';
		if (file_exists($custom)) {
			return $custom;
		}
	}
	return $template;
}

/**
 * Enqueue legal page styles.
 */
add_action('wp_enqueue_scripts', 'learnsimply_enqueue_legal_styles');
function learnsimply_enqueue_legal_styles()
{
	if (!is_page(array('terms-conditions', 'privacy-policy'))) {
		return;
	}
	$file = get_stylesheet_directory() . '/assets/legal/style.css';
	if (file_exists($file)) {
		wp_enqueue_style(
			'learnsimply-legal',
			get_stylesheet_directory_uri() . '/assets/legal/style.css',
			array(),
			filemtime($file)
		);
	}
}

/**
 * Update Timber context with resolved legal page URLs.
 * Overrides the fallback slugs with actual WordPress page permalinks.
 */
add_filter('timber/context', 'learnsimply_legal_urls_to_context', 20);
function learnsimply_legal_urls_to_context($context)
{
	$terms_page = get_page_by_path('terms-conditions', OBJECT, 'page');
	if ($terms_page) {
		$context['terms_url'] = get_permalink($terms_page->ID);
	}

	$privacy_page = get_page_by_path('privacy-policy', OBJECT, 'page');
	if ($privacy_page) {
		$context['privacy_url'] = get_permalink($privacy_page->ID);
	}

	return $context;
}



add_action('wp_enqueue_scripts', 'learnsimply_enqueue_custom_overrides', 999);

/**
 * Enqueue custom override styles (loaded late to override other styles)
 */
function learnsimply_enqueue_custom_overrides()
{
	$file = get_stylesheet_directory() . '/assets/global/custom-override.css';
	if (file_exists($file)) {
		wp_enqueue_style(
			'learnsimply-custom-overrides',
			get_stylesheet_directory_uri() . '/assets/global/custom-override.css',
			array('edublink-child-style'),
			filemtime($file)
		);
	}
}

/**
 * Inject checkout-page mobile fix as inline <style> in <head>.
 * This is loaded AFTER all external CSS and guaranteed to override everything.
 */
add_action('wp_head', 'learnsimply_checkout_mobile_inline_fix', 999);
function learnsimply_checkout_mobile_inline_fix()
{
	// We use the body class body.woocommerce-checkout in CSS so it's safe to inject everywhere,
	// but we'll still check is_checkout() just to be clean, but adding is_page('checkout') as fallback.
	if (!function_exists('is_checkout')) {
		return;
	}
	?>
	<style id="checkout-mobile-fix">
		/* ============================================================
		   CHECKOUT PAGE — AGGRESSIVE GAP FIX
		   ============================================================ */

		/* 1. Kill all potential top/bottom gaps from wrappers */
		body.woocommerce-checkout .woocommerce,
		body.woocommerce-checkout .entry-content,
		body.woocommerce-checkout .page-content,
		body.woocommerce-checkout .site-content,
		body.woocommerce-checkout .edublink-main-wrapper,
		body.woocommerce-checkout #primary,
		body.woocommerce-checkout #content,
		body.woocommerce-checkout main,
		body.woocommerce-checkout .site-main,
		body.woocommerce-checkout .eb-container,
		body.woocommerce-checkout .site-content-inner {
			padding-top: 10px !important;
			padding-bottom: 0 !important;
			margin-top: 0 !important;
			margin-bottom: 0 !important;
			min-height: 0 !important;
		}

		/* Hide ALL breadcrumb/title areas that create empty vertical space */
		body.woocommerce-checkout .edublink-breadcrumb-area,
		body.woocommerce-checkout .edu-breadcrumb-area,
		body.woocommerce-checkout .breadcrumb-area,
		body.woocommerce-checkout .page-title-area,
		body.woocommerce-checkout .sp-hero,
		body.woocommerce-checkout .entry-header,
		body.woocommerce-checkout .header-breadcrumb,
		body.woocommerce-checkout .breadcrumb {
			display: none !important;
			height: 0 !important;
			margin: 0 !important;
			padding: 0 !important;
			visibility: hidden !important;
		}

		/* h1/h2 title — compact margins (10px gap to element below) */
		body.woocommerce-checkout h1,
		body.woocommerce-checkout h2.entry-title,
		body.woocommerce-checkout .woocommerce>h2,
		body.woocommerce-checkout .woocommerce-checkout h2,
		body.woocommerce-checkout .elementor-widget-heading,
		body.woocommerce-checkout .elementor-heading-title {
			margin: 0 0 10px 0 !important;
			padding: 0 !important;
			font-size: 24px !important;
			text-align: center !important;
			line-height: 1.2 !important;
			color: #ffffff !important;
		}

		/* WooCommerce Notices / Login Toggle Spacing */
		body.woocommerce-checkout .woocommerce-notices-wrapper,
		body.woocommerce-checkout .woocommerce-form-login-toggle,
		body.woocommerce-checkout .woocommerce-info {
			margin-top: 0 !important;
			margin-bottom: 10px !important;
			padding-top: 10px !important;
			padding-bottom: 10px !important;
		}

		/* Force Promo Banner to have NO bottom spacing */
		body.woocommerce-checkout .learnsimply-promo-banner {
			margin-bottom: 0 !important;
			padding-bottom: 0 !important;
		}

		/* 2. CHECKOUT LOGIN FORM — Compact Block Design */
		body.woocommerce-checkout .woocommerce-form-login {
			display: block !important;
			background: #1b2133 !important;
			padding: 30px !important;
			border-radius: 15px !important;
			border: 1px solid rgba(255, 255, 255, 0.08) !important;
			margin: 0 auto 25px auto !important;
			max-width: 480px !important; /* Small, compact form */
			box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2) !important;
			direction: rtl !important;
		}

		/* Description Text Section - At the top */
		body.woocommerce-checkout .woocommerce-form-login > p:not(.form-row):not(.lost_password) {
			display: block !important;
			margin: 0 0 20px 0 !important;
			font-size: 14px !important;
			line-height: 1.6 !important;
			color: #c8cfe0 !important;
			background: rgba(64, 119, 243, 0.05) !important;
			padding: 15px !important;
			border-radius: 8px !important;
			border: 1px solid rgba(64, 119, 243, 0.1) !important;
			text-align: center !important;
		}

		/* ─── NUCLEAR RESET FOR FORM ROWS (Force Vertical Stacking) ─── */
		body.woocommerce-checkout .woocommerce-form-login .form-row,
		body.woocommerce-checkout .woocommerce-form-login p.form-row,
		body.woocommerce-checkout .woocommerce-form-login .form-group,
		body.woocommerce-checkout .woocommerce-form-login .lost_password {
			width: 100% !important;
			margin: 0 0 15px 0 !important;
			display: block !important;
			clear: both !important;
		}

		/* Break any inner flex containers that might hold label and input */
		body.woocommerce-checkout .woocommerce-form-login .form-row > span,
		body.woocommerce-checkout .woocommerce-form-login .form-row > div,
		body.woocommerce-checkout .woocommerce-form-login .password-input,
		body.woocommerce-checkout .woocommerce-form-login span.password-input,
		body.woocommerce-checkout .woocommerce-form-login .woocommerce-input-wrapper {
			display: block !important;
			width: 100% !important;
			position: relative !important;
			clear: both !important;
			float: none !important;
		}

		/* ─── LABELS: STRICT TOP-RIGHT ALIGNMENT ─── */
		body.woocommerce-checkout .woocommerce-form-login label:not(.woocommerce-form__label-for-checkbox),
		body.woocommerce-checkout .woocommerce-form-login label[for="password"],
		body.woocommerce-checkout .woocommerce-form-login label[for="username"] {
			display: block !important;
			width: 100% !important;
			margin: 0 0 8px 0 !important;
			font-size: 14px !important;
			font-weight: 500 !important;
			color: #ffffff !important;
			text-align: right !important;
			float: none !important;
			line-height: 1.5 !important;
			box-sizing: border-box !important;
		}

		/* ─── INPUTS: STRICT FULL WIDTH BELOW LABEL ─── */
		body.woocommerce-checkout .woocommerce-form-login input.input-text,
		body.woocommerce-checkout .woocommerce-form-login .woocommerce-Input,
		body.woocommerce-checkout .woocommerce-form-login input#password,
		body.woocommerce-checkout .woocommerce-form-login input#username {
			display: block !important;
			background: #141924 !important;
			border: 1px solid rgba(255, 255, 255, 0.1) !important;
			color: #ffffff !important;
			padding: 12px 16px !important;
			border-radius: 10px !important;
			width: 100% !important;
			font-size: 15px !important;
			transition: all 0.3s ease !important;
			box-sizing: border-box !important;
			margin: 0 !important;
			float: none !important;
		}

		body.woocommerce-checkout .woocommerce-form-login input.input-text:focus {
			border-color: #4077f3 !important;
			box-shadow: 0 0 0 3px rgba(64, 119, 243, 0.2) !important;
			outline: none !important;
		}

		/* Checkbox and Login Button row */
		body.woocommerce-checkout .woocommerce-form-login .form-row:has(button) {
			display: flex !important;
			flex-direction: row-reverse !important; /* Button then checkbox in RTL flow */
			align-items: center !important;
			justify-content: flex-end !important;
			gap: 15px !important;
			margin-top: 5px !important;
		}

		body.woocommerce-checkout .woocommerce-form-login .woocommerce-form__label-for-checkbox {
			display: inline-flex !important;
			align-items: center !important;
			gap: 8px !important;
			margin: 0 !important;
			cursor: pointer !important;
			color: #afb1b9 !important;
		}

		body.woocommerce-checkout .woocommerce-form-login .button {
			background: #4077f3 !important;
			color: #ffffff !important;
			padding: 12px 30px !important;
			border-radius: 10px !important;
			font-weight: 600 !important;
			border: none !important;
			cursor: pointer !important;
			transition: all 0.3s ease !important;
			white-space: nowrap !important;
		}

		body.woocommerce-checkout .woocommerce-form-login .button:hover {
			background: #2d61d6 !important;
			transform: translateY(-1px) !important;
		}

		/* Forgot Password link */
		body.woocommerce-checkout .woocommerce-form-login .lost_password {
			margin-top: 10px !important;
			font-size: 14px !important;
			text-align: right !important;
			display: block !important;
		}

		body.woocommerce-checkout .woocommerce-form-login .lost_password a {
			color: #4077f3 !important;
			text-decoration: none !important;
		}

		/* 3. GUEST LOGIN FORM — Mobile responsive */
		@media screen and (max-width: 991px) {
			body.woocommerce-checkout .woocommerce-form-login {
				padding: 20px !important;
				margin: 0 10px 20px 10px !important;
				max-width: 100% !important;
			}

			body.woocommerce-checkout .woocommerce-form-login > p:not(.form-row):not(.lost_password) {
				padding: 15px !important;
				font-size: 13px !important;
			}
			
			body.woocommerce-checkout .woocommerce-form-login .form-row:has(button) {
				flex-direction: column !important;
				align-items: stretch !important;
			}

			body.woocommerce-checkout .woocommerce-form-login .button {
				width: 100% !important;
			}
		}
		/* end @media 767px */
	</style>

	<script id="checkout-gap-nuke">
		(function () {
			function nukeCheckoutGaps() {
				var wc = document.querySelector('.woocommerce');
				if (wc) {
					wc.style.setProperty('margin-top', '0', 'important');
					wc.style.setProperty('padding-top', '10px', 'important');
					var children = wc.children;
					for (var i = 0; i < children.length; i++) {
						var child = children[i];
						if (window.getComputedStyle(child).display === 'none') continue;
						child.style.setProperty('margin-top', '0', 'important');
						child.style.setProperty('margin-bottom', '10px', 'important');
						child.style.setProperty('padding-top', '0', 'important');
					}
				}

				// Global margin limiter for all elements on checkout page
				var allDivs = document.querySelectorAll('.woocommerce-checkout div, .woocommerce-checkout section');
				allDivs.forEach(function (el) {
					var style = window.getComputedStyle(el);
					if (parseFloat(style.marginBottom) > 12) {
						el.style.setProperty('margin-bottom', '10px', 'important');
					}
					if (parseFloat(style.marginTop) > 12) {
						el.style.setProperty('margin-top', '0', 'important');
					}
				});

				var titles = document.querySelectorAll('h1, h2, h3, .elementor-heading-title, .page-title');
				for (var i = 0; i < titles.length; i++) {
					var txt = titles[i].textContent || '';
					if (txt.indexOf('الطلب') > -1) {
						titles[i].style.setProperty('margin-top', '0', 'important');
						titles[i].style.setProperty('margin-bottom', '10px', 'important');
						var p = titles[i].parentElement;
						while (p && p.tagName !== 'BODY') {
							if (p.classList.contains('learnsimply-promo-banner')) break;
							p.style.setProperty('padding-top', '0', 'important');
							p.style.setProperty('margin-top', '0', 'important');
							p.style.setProperty('padding-bottom', '0', 'important');
							p.style.setProperty('margin-bottom', '0', 'important');
							p.style.setProperty('min-height', '0', 'important');
							p = p.parentElement;
						}
					}
				}
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', nukeCheckoutGaps);
			} else {
				nukeCheckoutGaps();
			}
			window.addEventListener('load', nukeCheckoutGaps);
			setTimeout(nukeCheckoutGaps, 500);
			setTimeout(nukeCheckoutGaps, 1500);
		})();
	</script>
	<?php
}


/**
 * Strip blank/nbsp paragraphs and extra <br> tags from signup and login page content
 * so Elementor-injected whitespace doesn't create a visible gap above the form.
 */
function learnsimply_strip_signup_blank_paras($content)
{
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$is_signup_page = is_page('signup')
		|| is_page('dashboard')
		|| is_checkout()
		|| strpos($request_uri, '/signup') !== false
		|| strpos($request_uri, '/dashboard') !== false
		|| strpos($request_uri, '/checkout') !== false;

	if (!$is_signup_page) {
		return $content;
	}

	// Remove paragraphs containing only whitespace or &nbsp;
	$content = preg_replace('/<p[^>]*>(\s|&nbsp;)*<\/p>/i', '', $content);
	// Remove bare <br> / <br /> tags at the top level
	$content = preg_replace('/^(\s*<br\s*\/?>\s*)+/i', '', $content);

	return $content;
}
add_filter('the_content', 'learnsimply_strip_signup_blank_paras', 20);

/**
 * On signup/login pages, inject a small script that removes any Elementor
 * inline min-height styles set directly on section/container elements,
 * which can't be overridden by CSS alone when set as inline styles.
 */
function learnsimply_fix_signup_elementor_height()
{
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$is_signup_page = is_page('signup')
		|| is_page('dashboard')
		|| is_checkout()
		|| strpos($request_uri, '/signup') !== false
		|| strpos($request_uri, '/dashboard') !== false
		|| strpos($request_uri, '/checkout') !== false;

	if (!$is_signup_page) {
		return;
	}
	?>
	<script id="learnsimply-fix-signup-height">
		(function () {
			function fixHeight() {
				var selectors = [
					'.elementor-section',
					'.elementor-top-section',
					'.elementor-inner-section',
					'.elementor-column',
					'.elementor-widget-wrap',
					'.elementor-container',
					'[data-element_type="section"]',
					'[data-element_type="container"]',
					'[data-element_type="column"]'
				];
				selectors.forEach(function (sel) {
					document.querySelectorAll(sel).forEach(function (el) {
						el.style.removeProperty('min-height');
						el.style.removeProperty('height');
						el.style.removeProperty('padding-top');
						el.style.removeProperty('padding-bottom');
						el.style.removeProperty('margin-top');
						el.style.removeProperty('margin-bottom');
					});
				});
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', fixHeight);
			} else {
				fixHeight();
			}
		})();
	</script>
	<?php
}
add_action('wp_footer', 'learnsimply_fix_signup_elementor_height', 9998);

/**
 * Inject sidebar dark mode CSS at the very end of wp_footer (highest possible priority).
 * This MUST come after all Tutor LMS stylesheets to override them.
 * Only runs on lesson/course pages to avoid impacting other pages.
 */
add_action('wp_footer', 'learnsimply_inject_sidebar_dark_mode', 9999);

function learnsimply_inject_sidebar_dark_mode()
{
	// Inject on ALL Tutor LMS spotlight pages (lessons, quizzes, assignments)
	$is_tutor_page = false;
	if (function_exists('tutor_utils')) {
		$is_tutor_page = tutor_utils()->is_single_lesson()
			|| tutor_utils()->is_single_course()
			|| (function_exists('is_tutor_page') && is_tutor_page());
	}
	// Fallback: check URL for lesson, quiz, or assignment paths
	if (!$is_tutor_page) {
		$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$is_tutor_page = strpos($request_uri, '/lesson/') !== false
			|| strpos($request_uri, '/quiz/') !== false
			|| strpos($request_uri, '/quizzes/') !== false
			|| strpos($request_uri, '/tutor-quiz/') !== false
			|| strpos($request_uri, '/assignments/') !== false
			|| strpos($request_uri, '/tutor-assignment/') !== false
			|| (strpos($request_uri, '/courses/') !== false && strpos($request_uri, '/lesson/') !== false);
	}

	if (!$is_tutor_page) {
		return;
	}
	?>
	<style id="learnsimply-sidebar-dark-mode">
		/* ==========================================================
	   SIDEBAR DARK MODE - FOOTER INJECT (absolute last priority)
	   Targets confirmed DOM classes from browser inspection.
	   ========================================================== */

		/* ── Accordion topic headers ("الوحدة الأولى" rows) ──────── */
		.tutor-accordion-item-header,
		.tutor-course-topic-title,
		.tutor-course-topic-header,
		div.tutor-accordion-item-header,
		div.tutor-course-topic-title {
			background-color: #1b2133 !important;
			color: #e2e6f0 !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
		}

		/* Active accordion header */
		.tutor-accordion-item-header.is-active,
		.tutor-accordion-item-header.is-open {
			background-color: #232d45 !important;
		}

		.tutor-accordion-item-header:hover {
			background-color: #232d45 !important;
		}

		/* ── Accordion body (lesson list container) ──────────────── */
		.tutor-accordion-item-body,
		.tutor-course-topic-content,
		div.tutor-accordion-item-body {
			background-color: #141924 !important;
		}

		/* ── Links INSIDE the accordion body ────────────────────── */
		.tutor-accordion-item-body a,
		.tutor-course-topic-content a,
		.tutor-course-topic-item a,
		.tutor-course-topic-lesson-list li a,
		.tutor-lesson-list li a {
			background-color: transparent !important;
			color: #999eb2 !important;
		}

		/* ── Topic item rows ──────────────────────────────────────── */
		.tutor-course-topic-item,
		.tutor-course-topic-lesson-list li {
			background-color: transparent !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
		}

		/* ── Hover / active lesson rows ──────────────────────────── */
		.tutor-course-topic-item.is-active,
		.tutor-course-topic-item:hover,
		.tutor-course-topic-lesson-list li.is-active,
		.tutor-course-topic-lesson-list li:hover,
		.tutor-lesson-list li.is-active,
		.tutor-lesson-list li:hover {
			background-color: rgba(64, 119, 243, 0.12) !important;
			border-radius: 6px;
		}

		.tutor-course-topic-item.is-active a,
		.tutor-course-topic-item:hover a,
		.tutor-course-topic-lesson-list li.is-active a,
		.tutor-course-topic-lesson-list li:hover a,
		.tutor-lesson-list li.is-active a,
		.tutor-lesson-list li:hover a {
			color: #ffffff !important;
		}

		/* ── Item title text ──────────────────────────────────────── */
		.tutor-course-topic-item-title {
			color: #999eb2 !important;
		}

		.tutor-course-topic-item.is-active .tutor-course-topic-item-title,
		.tutor-course-topic-item:hover .tutor-course-topic-item-title {
			color: #ffffff !important;
		}

		/* ── Item duration meta ───────────────────────────────────── */
		.tutor-course-topic-item-duration {
			color: #6b7394 !important;
		}

		/* ── Completion circle / check icons ─────────────────────── */
		.tutor-form-check-input,
		.tutor-form-check-circle {
			border-color: rgba(255, 255, 255, 0.25) !important;
			background-color: transparent !important;
		}

		/* ── Sidebar container (double-confirm it's dark) ─────────── */
		.tutor-course-single-sidebar-wrapper,
		.tutor-lesson-sidebar,
		.tutor-course-spotlight-sidebar {
			background-color: #141924 !important;
			color: #999eb2 !important;
		}

		/* ── Sidebar "محتوى الدورة" title bar ─────────────────────── */
		.tutor-course-single-sidebar-wrapper .tutor-course-single-sidebar-title,
		.tutor-course-single-sidebar-title {
			background-color: #1b2133 !important;
			color: #ffffff !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.08) !important;
		}
	</style>
	<?php
}




/**
 * Placeholder image URL when no image is available
 * Change path here to update across the entire theme
 */
function learnsimply_no_image_url()
{
	return get_stylesheet_directory_uri() . '/assets/img/no-image.jpg';
}

/**
 * Load Composer dependencies
 */
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
	require_once __DIR__ . '/vendor/autoload.php';
}

add_filter('show_admin_bar', '__return_false');

/* ==========================================================================
   PROMO BANNER - صفحة العروض
   ========================================================================== */

/**
 * Add العروض (Offers) admin menu page
 */
function learnsimply_add_promo_admin_menu()
{
	add_menu_page(
		'العروض',
		'العروض',
		'manage_options',
		'learnsimply-promo',
		'learnsimply_promo_admin_page',
		'dashicons-megaphone',
		30
	);
}
add_action('admin_menu', 'learnsimply_add_promo_admin_menu');

/**
 * Register promo settings
 */
function learnsimply_register_promo_settings()
{
	register_setting('learnsimply_promo_group', 'learnsimply_promo_enabled', array(
		'type' => 'boolean',
		'default' => false,
		'sanitize_callback' => function ($v) {
			return !empty($v); },
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_text_primary', array(
		'type' => 'string',
		'default' => 'خصم 50% لمدة 3 أيام فقط — العرض سينتهي قريبًا!',
		'sanitize_callback' => 'sanitize_text_field',
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_text_secondary', array(
		'type' => 'string',
		'default' => 'الأماكن محدودة — الحق العرض قبل انتهاء المدة!',
		'sanitize_callback' => 'sanitize_text_field',
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_highlight', array(
		'type' => 'string',
		'default' => '50%',
		'sanitize_callback' => 'sanitize_text_field',
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_emoji', array(
		'type' => 'string',
		'default' => '🔥',
		'sanitize_callback' => 'sanitize_text_field',
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_cta_text', array(
		'type' => 'string',
		'default' => 'اشترك الآن',
		'sanitize_callback' => 'sanitize_text_field',
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_cta_url', array(
		'type' => 'string',
		'default' => '',
		'sanitize_callback' => 'esc_url_raw',
	));
	register_setting('learnsimply_promo_group', 'learnsimply_promo_deadline', array(
		'type' => 'string',
		'default' => '',
		'sanitize_callback' => function ($v) {
			if (empty($v))
				return '';
			if (is_numeric($v))
				return (string) (int) $v;
			$ts = strtotime($v);
			return $ts ? (string) $ts : '';
		},
	));
}
add_action('admin_init', 'learnsimply_register_promo_settings');

/**
 * Render promo admin page
 */
function learnsimply_promo_admin_page()
{
	if (!current_user_can('manage_options')) {
		return;
	}
	$enabled = get_option('learnsimply_promo_enabled', false);
	?>
	<div class="wrap" style="max-width: 700px;">
		<h1>العروض - إعدادات بانر البرومو</h1>
		<p style="margin-bottom: 24px; color: #666;">تحكم في ظهور بانر العروض الترويجية أسفل الهيدر وتعديل النصوص والوقت
			والرابط.</p>

		<form method="post" action="options.php" id="learnsimply-promo-form">
			<?php settings_fields('learnsimply_promo_group'); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">إظهار البانر</th>
					<td>
						<label>
							<input type="checkbox" name="learnsimply_promo_enabled" value="1" <?php checked($enabled); ?> />
							تفعيل عرض بانر البرومو
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="learnsimply_promo_text_primary">النص الرئيسي</label></th>
					<td>
						<input type="text" id="learnsimply_promo_text_primary" name="learnsimply_promo_text_primary"
							value="<?php echo esc_attr(get_option('learnsimply_promo_text_primary', 'خصم 50% لمدة 3 أيام فقط — العرض سينتهي قريبًا!')); ?>"
							class="large-text" dir="rtl" />
						<p class="description">مثال: خصم 50% لمدة 3 أيام فقط — العرض سينتهي قريبًا!</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="learnsimply_promo_highlight">النص المميز (للتلوين)</label></th>
					<td>
						<input type="text" id="learnsimply_promo_highlight" name="learnsimply_promo_highlight"
							value="<?php echo esc_attr(get_option('learnsimply_promo_highlight', '50%')); ?>"
							class="regular-text" dir="rtl" />
						<p class="description">سيتم تمييز هذا النص باللون الأزرق داخل النص الرئيسي (مثال: 50%)</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="learnsimply_promo_text_secondary">النص الثانوي</label></th>
					<td>
						<input type="text" id="learnsimply_promo_text_secondary" name="learnsimply_promo_text_secondary"
							value="<?php echo esc_attr(get_option('learnsimply_promo_text_secondary', 'الأماكن محدودة — الحق العرض قبل انتهاء المدة!')); ?>"
							class="large-text" dir="rtl" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="learnsimply_promo_emoji">الإيموجي</label></th>
					<td>
						<input type="text" id="learnsimply_promo_emoji" name="learnsimply_promo_emoji"
							value="<?php echo esc_attr(get_option('learnsimply_promo_emoji', '🔥')); ?>"
							class="small-text" />
						<p class="description">مثال: 🔥 أو ⚡ أو 🎉</p>
					</td>
				</tr>
				<tr>
					<th scope="row">وقت انتهاء العرض</th>
					<td>
						<?php
						$deadline_ts = get_option('learnsimply_promo_deadline', '');
						$dl_year = $dl_month = $dl_day = $dl_hour = $dl_min = '';
						if ($deadline_ts && is_numeric($deadline_ts)) {
							$ts = (int) $deadline_ts;
							$dl_year = (int) (function_exists('wp_date') ? wp_date('Y', $ts) : gmdate('Y', $ts));
							$dl_month = (int) (function_exists('wp_date') ? wp_date('n', $ts) : gmdate('n', $ts));
							$dl_day = (int) (function_exists('wp_date') ? wp_date('j', $ts) : gmdate('j', $ts));
							$dl_hour = (int) (function_exists('wp_date') ? wp_date('G', $ts) : gmdate('G', $ts));
							$dl_min = (int) (function_exists('wp_date') ? wp_date('i', $ts) : gmdate('i', $ts));
						} else {
							$dt = function_exists('current_datetime') ? current_datetime() : new DateTime('now');
							$dl_year = (int) $dt->format('Y');
							$dl_month = (int) $dt->format('n');
							$dl_day = (int) $dt->format('j');
							$dl_hour = 23;
							$dl_min = 59;
						}
						?>
						<div class="learnsimply-promo-deadline-fields"
							style="display:flex;flex-wrap:wrap;gap:8px;align-items:center;">
							<span>
								<label for="promo_dl_year">سنة</label>
								<input type="number" id="promo_dl_year" name="learnsimply_promo_deadline_year"
									value="<?php echo esc_attr($dl_year); ?>" min="<?php echo (int) gmdate('Y'); ?>"
									max="2100" style="width:70px;" />
							</span>
							<span>
								<label for="promo_dl_month">شهر</label>
								<input type="number" id="promo_dl_month" name="learnsimply_promo_deadline_month"
									value="<?php echo esc_attr($dl_month); ?>" min="1" max="12" style="width:50px;" />
							</span>
							<span>
								<label for="promo_dl_day">يوم</label>
								<input type="number" id="promo_dl_day" name="learnsimply_promo_deadline_day"
									value="<?php echo esc_attr($dl_day); ?>" min="1" max="31" style="width:50px;" />
							</span>
							<span>
								<label for="promo_dl_hour">ساعة</label>
								<input type="number" id="promo_dl_hour" name="learnsimply_promo_deadline_hour"
									value="<?php echo esc_attr($dl_hour); ?>" min="0" max="23" style="width:50px;" />
							</span>
							<span>
								<label for="promo_dl_min">دقيقة</label>
								<input type="number" id="promo_dl_min" name="learnsimply_promo_deadline_minute"
									value="<?php echo esc_attr($dl_min); ?>" min="0" max="59" style="width:50px;" />
							</span>
						</div>
						<input type="hidden" id="learnsimply_promo_deadline" name="learnsimply_promo_deadline"
							value="<?php echo esc_attr($deadline_ts); ?>" />
						<p class="description">التاريخ والوقت الذي ينتهي فيه العد التنازلي: السنة، الشهر، اليوم، الساعة
							(0–23)، الدقيقة (0–59)</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="learnsimply_promo_cta_text">نص الزر</label></th>
					<td>
						<input type="text" id="learnsimply_promo_cta_text" name="learnsimply_promo_cta_text"
							value="<?php echo esc_attr(get_option('learnsimply_promo_cta_text', 'اشترك الآن')); ?>"
							class="regular-text" dir="rtl" />
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="learnsimply_promo_cta_url">رابط الزر</label></th>
					<td>
						<input type="url" id="learnsimply_promo_cta_url" name="learnsimply_promo_cta_url"
							value="<?php echo esc_attr(get_option('learnsimply_promo_cta_url', home_url('/'))); ?>"
							class="large-text" />
						<p class="description">الرابط الذي ينتقل إليه المستخدم عند الضغط على الزر</p>
					</td>
				</tr>
			</table>

			<?php submit_button('حفظ الإعدادات'); ?>
		</form>
	</div>
	<script>
		(function () {
			var form = document.getElementById('learnsimply-promo-form');
			if (!form) return;
			form.addEventListener('submit', function () {
				var y = parseInt(document.getElementById('promo_dl_year').value, 10) || new Date().getFullYear();
				var m = (parseInt(document.getElementById('promo_dl_month').value, 10) || 1) - 1;
				var d = parseInt(document.getElementById('promo_dl_day').value, 10) || 1;
				var h = parseInt(document.getElementById('promo_dl_hour').value, 10) || 0;
				var min = parseInt(document.getElementById('promo_dl_min').value, 10) || 0;
				var ts = Math.floor(new Date(y, m, d, h, min, 0).getTime() / 1000);
				document.getElementById('learnsimply_promo_deadline').value = ts;
			});
		})();
	</script>
	<?php
}


/**
 * Enqueue promo banner CSS and localize deadline when promo is enabled
 */
function learnsimply_enqueue_promo_assets()
{
	if (!get_option('learnsimply_promo_enabled', false)) {
		return;
	}
	$promo_css = get_stylesheet_directory() . '/assets/promo-banner/style.css';
	if (file_exists($promo_css)) {
		// لا اعتماد - لضمان التحميل على كل الصفحات حتى لو لم تُحمّل أنماط أخرى
		wp_enqueue_style(
			'learnsimply-promo-banner',
			get_stylesheet_directory_uri() . '/assets/promo-banner/style.css',
			array(),
			filemtime($promo_css)
		);
	}
	$deadline = get_option('learnsimply_promo_deadline', '');
	if ($deadline && is_numeric($deadline)) {
		wp_localize_script('edublink-global-scripts', 'learnsimplyPromoDeadline', (string) ((int) $deadline * 1000));
	}
}
add_action('wp_enqueue_scripts', 'learnsimply_enqueue_promo_assets', 101);

/**
 * Initialize Timber
 */
if (class_exists('Timber\Timber')) {
	Timber\Timber::init();

	/**
	 * Set Timber locations
	 */
	Timber::$dirname = array('views', 'templates');

	/**
	 * Register sanitizing Twig filters so templates can stop using |raw on
	 * content authored by instructors/admins (course descriptions, video
	 * embeds, product bodies). |raw bypasses Twig's auto-escaping entirely.
	 *
	 * - |safe_html  : wp_kses_post — allows the post-editor tag whitelist.
	 *                 Use for course/product body content and rich text.
	 * - |safe_embed : wp_kses with an iframe whitelist for video embeds
	 *                 (YouTube, Vimeo, etc.). Strips script/onerror/etc.
	 */
	add_filter('timber/twig', function ($twig) {
		$twig->addFilter(new \Twig\TwigFilter('safe_html', function ($content) {
			return wp_kses_post((string) $content);
		}));

		$twig->addFilter(new \Twig\TwigFilter('safe_embed', function ($content) {
			$allowed_html = array(
				'iframe' => array(
					'src'             => true,
					'width'           => true,
					'height'          => true,
					'frameborder'     => true,
					'allow'           => true,
					'allowfullscreen' => true,
					'loading'         => true,
					'title'           => true,
					'referrerpolicy'  => true,
					'class'           => true,
					'style'           => true,
				),
				'div'    => array('class' => true, 'style' => true),
				'video'  => array('src' => true, 'controls' => true, 'width' => true, 'height' => true, 'poster' => true),
				'source' => array('src' => true, 'type' => true),
			);
			return wp_kses((string) $content, $allowed_html);
		}));

		return $twig;
	});

	/**
	 * Add WordPress conditional functions to Timber context
	 */
	add_filter('timber/context', 'edublink_child_add_to_context');
	function edublink_child_add_to_context($context)
	{
		// Global theme URI for assets in Twig (images, CSS, JS)
		$context['theme_uri'] = get_stylesheet_directory_uri();

		// Placeholder image when no image exists (change path in learnsimply_no_image_url())
		$context['no_image_url'] = learnsimply_no_image_url();

		// Cart page URL for JavaScript redirects
		$cart_page_id = function_exists('wc_get_page_id') ? wc_get_page_id('cart') : 0;
		if ($cart_page_id && $cart_page_id > 0) {
			$context['cart_url'] = get_permalink($cart_page_id);
		} else {
			$context['cart_url'] = function_exists('wc_get_cart_url') ? wc_get_cart_url() : '/cart-1/';
		}

		// Cart items count for header badge
		if (function_exists('WC') && WC()->cart) {
			$context['cart_count'] = WC()->cart->get_cart_contents_count();
		} else {
			$context['cart_count'] = 0;
		}

		$context['is_front_page'] = is_front_page();
		$context['is_home'] = is_home();
		$context['is_user_logged_in'] = is_user_logged_in();
		$context['instructor_title'] = 'مهندس برمجيات';

		if (is_user_logged_in()) {
			$context['user'] = Timber::get_user(get_current_user_id());
		}

		// Add main menu (Main Menu - ID: 28)
		$main_menu = Timber::get_menu(28);
		if ($main_menu) {
			$context['main_menu'] = $main_menu;
		}

		// Add courses archive URL
		if (function_exists('tutor_utils')) {
			$context['courses_archive_url'] = tutor_utils()->course_archive_page_url();
		} else {
			$context['courses_archive_url'] = home_url('/courses/');
		}

		// Add site icon (favicon) URL
		$site_icon_id = get_option('site_icon');
		if ($site_icon_id) {
			$context['site_icon_url'] = wp_get_attachment_image_url($site_icon_id, 'full');
		} else {
			// Fallback to default favicon if no site icon is set
			$context['site_icon_url'] = get_site_icon_url();
		}

		// Footer legal links (terms & privacy) — resolved from WordPress/WooCommerce page settings
		$context['privacy_url'] = function_exists('get_privacy_policy_url') && get_privacy_policy_url()
			? get_privacy_policy_url()
			: home_url('/privacy-policy/');

		if (function_exists('wc_get_page_id')) {
			$terms_page_id = wc_get_page_id('terms');
			$context['terms_url'] = ($terms_page_id && $terms_page_id > 0)
				? get_permalink($terms_page_id)
				: home_url('/terms-conditions/');
		} else {
			$context['terms_url'] = home_url('/terms-conditions/');
		}

		// Promo banner settings (from العروض admin page)
		$context['promo_banner_enabled'] = (bool) get_option('learnsimply_promo_enabled', false);
		if ($context['promo_banner_enabled']) {
			$context['promo_text_primary'] = get_option('learnsimply_promo_text_primary', 'خصم 50% لمدة 3 أيام فقط — العرض سينتهي قريبًا!');
			$context['promo_text_secondary'] = get_option('learnsimply_promo_text_secondary', 'الأماكن محدودة — الحق العرض قبل انتهاء المدة!');
			$context['promo_highlight'] = get_option('learnsimply_promo_highlight', '50%');
			$context['promo_emoji'] = get_option('learnsimply_promo_emoji', '🔥');
			$context['promo_cta_text'] = get_option('learnsimply_promo_cta_text', 'اشترك الآن');
			$cta_url = get_option('learnsimply_promo_cta_url', '');
			$java_url = home_url('/product/java-basics-oop-bundle/');
			// Use Java product page as default unless a custom URL has been explicitly set (not empty & not just the homepage)
			$home_trimmed = rtrim(home_url(), '/');
			$cta_trimmed = rtrim($cta_url, '/');
			$context['promo_cta_url'] = ($cta_url && $cta_trimmed !== $home_trimmed) ? esc_url($cta_url) : $java_url;
		}

		return $context;
	}
}

/* ==========================================================================
   FORCE FRONT-PAGE.PHP & DISABLE ELEMENTOR (FINAL NUCLEAR SOLUTION)
   ========================================================================== */

/**
 * 1. Force load front-page.php and shop-2.php
 * Use woocommerce_locate_template to override WooCommerce archive template for shop-2
 */
add_filter('woocommerce_locate_template', 'edublink_child_override_shop_2_template', 10, 3);

function edublink_child_override_shop_2_template($template, $template_name, $template_path)
{
	// IMPORTANT: Exclude single product pages, cart, checkout FIRST
	if (is_product() || is_cart() || is_checkout() || is_account_page()) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Check URL directly - most reliable method
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	if (
		strpos($request_uri, '/product/') !== false ||
		strpos($request_uri, '/cart') !== false ||
		strpos($request_uri, '/checkout') !== false ||
		strpos($request_uri, '/my-account') !== false
	) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Check if this is archive-product.php and we're on shop-2
	// IMPORTANT: Only apply to shop archive pages, not cart, checkout, or other pages
	if ($template_name === 'archive-product.php') {
		// Verify we're actually on a shop archive page
		if (!is_shop() && !is_product_category() && !is_product_tag() && !is_post_type_archive('product')) {
			return $template; // Not a shop archive, return original template
		}

		$woocommerce_shop_page_id = 0;
		if (function_exists('wc_get_page_id')) {
			$woocommerce_shop_page_id = wc_get_page_id('shop');
		}

		$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
		$is_shop_2_url = (strpos($request_uri, '/shop-2') !== false);

		if ($woocommerce_shop_page_id == 22662 || $is_shop_2_url) {
			$shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';
			if (file_exists($shop_2_template)) {
				// Prevent Elementor
				add_filter('elementor/frontend/print_google_fonts', '__return_false');
				add_filter('elementor/theme/get_location_templates', '__return_empty_array', 999);
				add_filter('elementor/theme/get_location_template_id', '__return_false', 999);
				add_filter('hfe_header_enabled', '__return_false');
				add_filter('hfe_footer_enabled', '__return_false');

				// Return shop-2 template instead
				return $shop_2_template;
			}
		}
	}

	return $template;
}

// Also use template_include as fallback
add_filter('template_include', 'edublink_child_force_custom_templates_via_filter', 999999);

function edublink_child_force_custom_templates_via_filter($template)
{
	// Check URL directly FIRST - most reliable method
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

	// IMPORTANT: Exclude product pages, cart, checkout, my-account URLs FIRST
	if (
		strpos($request_uri, '/product/') !== false ||
		strpos($request_uri, '/cart') !== false ||
		strpos($request_uri, '/checkout') !== false ||
		strpos($request_uri, '/my-account') !== false
	) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Check if we are on the front page or the specific page ID 9834 found in your HTML
	if (is_front_page() || is_home() || get_the_ID() == 9834) {
		$custom_front = get_stylesheet_directory() . '/front-page.php';

		if (file_exists($custom_front)) {
			return $custom_front;
		}
	}

	// IMPORTANT: Exclude cart, checkout, my-account, single product pages, and other pages FIRST
	if (is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() || is_product()) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Exclude single posts, pages (except shop-2), and other archives
	if (is_single() || (is_page() && !is_page('shop-2') && !is_page(22662)) || (is_archive() && !is_shop() && !is_product_category() && !is_product_tag() && !is_post_type_archive('product'))) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Get current page ID and slug to exclude cart pages
	$current_page_id = get_queried_object_id();
	global $wp_query;
	$queried_object = get_queried_object();
	$current_page_slug = '';
	if ($queried_object && isset($queried_object->post_name)) {
		$current_page_slug = $queried_object->post_name;
	}

	// Exclude cart pages by ID or slug - MUST be checked BEFORE shop-2 check
	if (
		$current_page_id == 21 || // cart-1 page ID
		$current_page_id == 21744 || // cart-3 page ID
		strpos($current_page_slug, 'cart') === 0 || // starts with 'cart'
		strpos($current_page_slug, 'checkout') === 0 ||
		strpos($current_page_slug, 'my-account') === 0
	) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Also check if we're on a regular page (not shop archive) - exclude all pages except shop-2
	if (is_page() && !is_page('shop-2') && !is_page(22662) && $current_page_id != 22662) {
		// This is a regular page, not shop-2, so don't apply shop-2 template
		return $template;
	}

	// Check URL directly - most reliable method
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

	// Also check if URL contains cart, checkout, product (single product), etc. and exclude them BEFORE checking shop-2
	if (
		strpos($request_uri, '/cart') !== false ||
		strpos($request_uri, '/checkout') !== false ||
		strpos($request_uri, '/my-account') !== false ||
		strpos($request_uri, '/product/') !== false
	) {
		return $template; // Don't apply shop-2 template to these pages
	}

	// Check if this is shop-2 URL (only after excluding cart/checkout)
	$is_shop_2_url = (strpos($request_uri, '/shop-2') !== false || strpos($request_uri, '/shop-2/') !== false);

	// Check if shop-2 is the WooCommerce shop page
	// IMPORTANT: Only apply to shop archive pages, not cart, checkout, or other pages
	$woocommerce_shop_page_id = 0;
	if (function_exists('wc_get_page_id')) {
		$woocommerce_shop_page_id = wc_get_page_id('shop');
	}

	// Verify we're actually on a shop archive page (not cart, checkout, etc.)
	$is_shop_archive = is_shop() || is_product_category() || is_product_tag() || is_post_type_archive('product');

	// Check if we're on shop-2 (either as shop page or regular page)
	// Only apply if we're on a shop archive page
	$is_shop_2 = false;
	if ($is_shop_archive) {
		if (
			$is_shop_2_url ||
			($woocommerce_shop_page_id == 22662 && (is_shop() || is_post_type_archive('product'))) ||
			is_page('shop-2') ||
			is_page(22662) ||
			get_queried_object_id() == 22662
		) {
			$is_shop_2 = true;
		}
	}

	if ($is_shop_2) {
		$shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';

		if (file_exists($shop_2_template)) {
			// Prevent Elementor from loading on this page
			add_filter('elementor/frontend/print_google_fonts', '__return_false');
			add_filter('elementor/theme/get_location_templates', '__return_empty_array', 999);
			add_filter('elementor/theme/get_location_template_id', '__return_false', 999);
			add_filter('hfe_header_enabled', '__return_false');
			add_filter('hfe_footer_enabled', '__return_false');

			return $shop_2_template;
		}
	}

	return $template;
}

// Also keep template_redirect as backup
add_action('template_redirect', 'edublink_child_force_custom_templates', 0);

function edublink_child_force_custom_templates()
{
	// CRITICAL: Never hijack 404 pages — this was causing terms/privacy to show shop
	if (is_404()) {
		return;
	}

	// Check URL directly FIRST - most reliable method
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

	// IMPORTANT: Exclude product pages, cart, checkout, my-account URLs FIRST
	if (
		strpos($request_uri, '/product/') !== false ||
		strpos($request_uri, '/cart') !== false ||
		strpos($request_uri, '/checkout') !== false ||
		strpos($request_uri, '/my-account') !== false
	) {
		return; // Don't apply shop-2 template to these pages
	}

	// IMPORTANT: Exclude cart, checkout, my-account, single product pages FIRST
	if (is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() || is_product()) {
		return; // Don't apply shop-2 template to these pages
	}

	// Exclude single posts, pages (except shop-2), and other archives
	if (is_single() || (is_page() && !is_page('shop-2') && !is_page(22662)) || (is_archive() && !is_shop() && !is_product_category() && !is_product_tag() && !is_post_type_archive('product'))) {
		return; // Don't apply shop-2 template to these pages
	}

	// Get current page info
	global $wp_query;
	$queried_object_id = get_queried_object_id();
	$current_page_slug = get_query_var('pagename');
	$queried_object = get_queried_object();
	$current_page_slug_from_object = '';
	if ($queried_object && isset($queried_object->post_name)) {
		$current_page_slug_from_object = $queried_object->post_name;
	}

	// Exclude cart pages by ID or slug
	if (
		$queried_object_id == 21 || // cart-1 page ID
		$queried_object_id == 21744 || // cart-3 page ID
		$current_page_slug === 'cart-1' ||
		$current_page_slug === 'cart-3' ||
		($current_page_slug_from_object && strpos($current_page_slug_from_object, 'cart') === 0)
	) {
		return; // Don't apply shop-2 template to these pages
	}

	// Check URL directly first (most reliable method)
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

	// Exclude cart, checkout, my-account, product (single product) URLs
	if (
		strpos($request_uri, '/cart') !== false ||
		strpos($request_uri, '/checkout') !== false ||
		strpos($request_uri, '/my-account') !== false ||
		strpos($request_uri, '/product/') !== false
	) {
		return; // Don't apply shop-2 template to these pages
	}

	$is_shop_2_url = (strpos($request_uri, '/shop-2') !== false);

	// Handle blog page separately (when is_home() is true for Posts page, or by URL)
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';

	// Check if this is the blog page by URL or WordPress conditions
	$is_blog_page = (is_home() && !is_front_page())
		|| preg_match('#/blog/?(\?.*)?$#', $request_uri);

	if ($is_blog_page) {
		// This is the Posts page (blog archive)
		$blog_template = get_stylesheet_directory() . '/page-blog.php';

		if (file_exists($blog_template)) {
			include($blog_template);
			exit;
		}
	}

	// Check if we are on the front page or the specific page ID 9834 found in your HTML
	// Note: is_home() removed - we handle it above for the blog page
	if (is_front_page() || get_the_ID() == 9834) {
		$custom_front = get_stylesheet_directory() . '/front-page.php';

		if (file_exists($custom_front)) {
			include($custom_front);
			exit;
		}
	}

	// Force load shop-2 template for shop-2 page
	// Check if shop-2 is set as WooCommerce shop page OR if it's a regular page
	// Check if shop-2 is the WooCommerce shop page
	$woocommerce_shop_page_id = 0;
	if (function_exists('wc_get_page_id')) {
		$woocommerce_shop_page_id = wc_get_page_id('shop');
	}

	// Check multiple ways to identify shop-2 page
	// NOTE: Do NOT use ($woocommerce_shop_page_id == 22662) here — that checks whether
	// the shop page ID equals 22662, which is TRUE on EVERY page and hijacks non-shop pages.
	$is_shop_2 = false;
	if (
		$is_shop_2_url ||
		is_page('shop-2') ||
		is_page(22662) ||
		$queried_object_id == 22662 ||
		(is_shop() && $woocommerce_shop_page_id == 22662) ||
		$current_page_slug === 'shop-2' ||
		(isset($wp_query->queried_object) && isset($wp_query->queried_object->post_name) && $wp_query->queried_object->post_name === 'shop-2') ||
		(isset($wp_query->queried_object) && isset($wp_query->queried_object->ID) && $wp_query->queried_object->ID == 22662)
	) {
		$is_shop_2 = true;
	}

	if ($is_shop_2) {
		$shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';

		if (file_exists($shop_2_template)) {
			// Prevent Elementor from loading on this page - do this BEFORE including template
			add_filter('elementor/frontend/print_google_fonts', '__return_false');
			add_filter('elementor/theme/get_location_templates', '__return_empty_array', 999);
			add_filter('elementor/theme/get_location_template_id', '__return_false', 999);
			add_filter('hfe_header_enabled', '__return_false');
			add_filter('hfe_footer_enabled', '__return_false');

			// Override WooCommerce archive template
			add_filter('woocommerce_is_shop', '__return_false', 999);

			include($shop_2_template);
			exit;
		}
	}
}

/**
 * Also use template_include as fallback
 */
add_filter('template_include', 'edublink_child_force_front_page_template', 999999);

function edublink_child_force_front_page_template($template)
{
	// This is a fallback - template_redirect should handle it first
	return $template;
}

/**
 * Force single blog post pages to use our custom single.php template.
 * Runs at very high priority (999999) so it beats Elementor and the parent theme.
 */
add_filter('template_include', 'edublink_child_force_single_post_template', 999999);

function edublink_child_force_single_post_template($template)
{
	if (is_single() && get_post_type() === 'post') {
		$custom = get_stylesheet_directory() . '/single.php';
		if (file_exists($custom)) {
			return $custom;
		}
	}
	return $template;
}

/**
 * Force blog page to use page-blog.php template
 */
add_filter('template_include', 'edublink_child_force_blog_template', 999998);

function edublink_child_force_blog_template($template)
{
	// Check if this is the blog page
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$is_blog_page = is_page('blog') || preg_match('#/blog/?(\?.*)?$#', $request_uri);

	if ($is_blog_page) {
		$blog_template = get_stylesheet_directory() . '/page-blog.php';
		if (file_exists($blog_template)) {
			return $blog_template;
		}
	}
	return $template;
}

/**
 * 2. Completely Dequeue Elementor Styles & Scripts on Front Page
 * Updated with specific IDs found in your guest HTML analysis
 */
add_action('wp_enqueue_scripts', 'edublink_child_unload_elementor_assets', 99999);

function edublink_child_unload_elementor_assets()
{
	// Check if we're on front page, home, or courses archive
	$is_courses_archive = false;
	if (function_exists('tutor_utils')) {
		$course_post_type = tutor()->course_post_type;
		$is_courses_archive = is_post_type_archive($course_post_type) || is_tax('course-category') || is_tax('course-tag');
	}

	// Check if we're on shop-2 page - multiple methods
	global $wp_query;
	$queried_object_id = get_queried_object_id();
	$current_page_slug = get_query_var('pagename');
	$is_shop_2 = false;

	if (
		is_page('shop-2') ||
		is_page(22662) ||
		$queried_object_id == 22662 ||
		$current_page_slug === 'shop-2' ||
		(isset($wp_query->queried_object) && isset($wp_query->queried_object->post_name) && $wp_query->queried_object->post_name === 'shop-2') ||
		(isset($wp_query->queried_object) && isset($wp_query->queried_object->ID) && $wp_query->queried_object->ID == 22662)
	) {
		$is_shop_2 = true;
	}

	if (is_front_page() || is_home() || get_the_ID() == 9834 || $is_courses_archive || $is_shop_2) {

		// Remove Elementor Core
		wp_dequeue_script('elementor-frontend');
		wp_dequeue_style('elementor-frontend');
		wp_dequeue_style('elementor-icons');
		wp_dequeue_style('elementor-global');

		// Remove Specific Elementor Files found in Guest View
		wp_dequeue_style('elementor-post-24541'); // Global Kit
		wp_dequeue_style('elementor-post-9834');  // Specific Page Style

		// Remove Elementor Pro
		wp_dequeue_script('elementor-pro-frontend');
		wp_dequeue_style('elementor-pro');

		// Remove Header Footer Elementor (HFE)
		wp_dequeue_script('hfe-frontend-js');
		wp_dequeue_style('hfe-style');
		wp_dequeue_style('hfe-widgets-style');

		// Remove EduBlink Theme specific Elementor styles
		// NOTE: Only remove edublink-style if it's loaded by Elementor, not the parent theme's main style
		wp_dequeue_style('edublink-elementor');
		// Don't remove edublink-style here - it's needed for other pages like cart, checkout, etc.
		// wp_dequeue_style( 'edublink-style' ); 

		// Remove Google Fonts loaded by Elementor
		add_filter('elementor/frontend/print_google_fonts', '__return_false');
	}
}

/**
 * 3. Force Disable Cache Headers for Front Page
 * This attempts to tell browsers and proxies NOT to cache the homepage
 */
add_action('send_headers', 'edublink_child_prevent_caching_front_page');
function edublink_child_prevent_caching_front_page()
{
	if (is_front_page() || is_home() || get_the_ID() == 9834) {
		header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
		header('Cache-Control: post-check=0, pre-check=0', false);
		header('Pragma: no-cache');
	}
}

/**
 * 4. Clean Body Classes
 * Removes 'elementor-page' classes to prevent any residual CSS from applying
 */
add_filter('body_class', 'edublink_child_clean_body_classes', 999);
function edublink_child_clean_body_classes($classes)
{
	$is_shop_2 = is_page('shop-2') || is_page(22662) || get_queried_object_id() == 22662;

	if (is_front_page() || is_home() || get_the_ID() == 9834 || $is_shop_2) {
		$remove_classes = array(
			'elementor-default',
			'elementor-kit-24541',
			'elementor-page',
			'elementor-page-9834',
			'elementor-page-22662'
		);
		$classes = array_diff($classes, $remove_classes);
	}
	return $classes;
}

/**
 * 5. Disable Elementor Locations Logic
 */
add_action('wp', 'edublink_child_disable_elementor_locations', 0);

function edublink_child_disable_elementor_locations()
{
	// Check if we're on front page, home, or courses archive
	$is_courses_archive = false;
	if (function_exists('tutor_utils')) {
		$course_post_type = tutor()->course_post_type;
		$is_courses_archive = is_post_type_archive($course_post_type) || is_tax('course-category') || is_tax('course-tag');
	}

	// Check if we're on shop-2 page - multiple methods
	global $wp_query;
	$queried_object_id = get_queried_object_id();
	$current_page_slug = get_query_var('pagename');
	$is_shop_2 = false;

	if (
		is_page('shop-2') ||
		is_page(22662) ||
		$queried_object_id == 22662 ||
		$current_page_slug === 'shop-2' ||
		(isset($wp_query->queried_object) && isset($wp_query->queried_object->post_name) && $wp_query->queried_object->post_name === 'shop-2') ||
		(isset($wp_query->queried_object) && isset($wp_query->queried_object->ID) && $wp_query->queried_object->ID == 22662)
	) {
		$is_shop_2 = true;
	}

	if (is_front_page() || is_home() || get_the_ID() == 9834 || $is_courses_archive || $is_shop_2) {
		// Stop Elementor Theme Builder
		add_filter('elementor/theme/get_location_templates', '__return_empty_array', 999);
		add_filter('elementor/theme/get_location_template_id', '__return_false', 999);

		// Stop Header Footer Elementor Plugin
		add_filter('hfe_header_enabled', '__return_false');
		add_filter('hfe_footer_enabled', '__return_false');
		add_filter('enable_hfe_render_header', '__return_false');
		add_filter('enable_hfe_render_footer', '__return_false');

		// Remove Theme Hooks
		remove_all_actions('edublink_header');
		remove_all_actions('edublink_footer');
	}
}

/* ==========================================================================
   OTHER ASSETS & WOOCOMMERCE
   ========================================================================== */

/**
 * Enqueue IBM Plex Sans Arabic from Google Fonts
 */
function edublink_child_enqueue_fonts()
{
	wp_enqueue_style(
		'ibm-plex-sans-arabic',
		'https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@100;200;300;400;500;600;700&display=swap',
		array(),
		null
	);
}
add_action('wp_enqueue_scripts', 'edublink_child_enqueue_fonts', 1);

/**
 * Enqueue parent and child theme styles
 */
function edublink_child_enqueue_styles()
{
	wp_enqueue_style('edublink-parent-style', get_template_directory_uri() . '/style.css', array(), '2.0.8');
	wp_enqueue_style('edublink-child-style', get_stylesheet_directory_uri() . '/style.css', array('edublink-parent-style'), wp_get_theme()->get('Version'));

	// Custom logic for products
	if (is_product()) {
		wp_enqueue_style('edublink-custom-product-style', get_stylesheet_directory_uri() . '/custom_product.css', array('edublink-child-style'), wp_get_theme()->get('Version'));
	}
	// Custom logic for archives
	if (is_shop() || is_product_category() || is_product_tag()) {
		wp_enqueue_style('edublink-custom-product-archive-style', get_stylesheet_directory_uri() . '/custom_product_archive.css', array('edublink-child-style'), wp_get_theme()->get('Version'));
	}
	// Custom logic for Tutor LMS
	if (function_exists('tutor_utils')) {
		$course_post_type = tutor()->course_post_type;
		if (is_singular($course_post_type) || get_post_type() === $course_post_type) {
			wp_enqueue_style('edublink-custom-course-style', get_stylesheet_directory_uri() . '/custom_course.css', array('edublink-child-style'), wp_get_theme()->get('Version'));
		}
		if (is_post_type_archive($course_post_type) || is_tax('course-category') || is_tax('course-tag')) {
			// At this stage we want to preserve the original theme design for course archives
			// So we do not remove Tutor LMS or theme styles, and only load our additional file if needed later
			wp_enqueue_style(
				'edublink-custom-course-archive-style',
				get_stylesheet_directory_uri() . '/custom_course_archive.css',
				array('edublink-child-style'),
				wp_get_theme()->get('Version')
			);
			// Note: wp_dequeue_style( 'tutor-frontend' ) and wp_dequeue_style( 'tutor' ) were removed to preserve the original design
		}
	}
}
add_action('wp_enqueue_scripts', 'edublink_child_enqueue_styles', 99);

/**
 * Enqueue global assets
 */
function edublink_child_enqueue_global_assets()
{
	$global_css = get_stylesheet_directory() . '/assets/global/styles.css';
	$global_js = get_stylesheet_directory() . '/assets/global/script.js';

	if (file_exists($global_css)) {
		wp_enqueue_style('edublink-global-styles', get_stylesheet_directory_uri() . '/assets/global/styles.css', array('edublink-child-style'), filemtime($global_css));
	}
	if (file_exists($global_js)) {
		wp_enqueue_script('edublink-global-scripts', get_stylesheet_directory_uri() . '/assets/global/script.js', array('jquery'), filemtime($global_js), true);
	}
}
add_action('wp_enqueue_scripts', 'edublink_child_enqueue_global_assets', 100);

/**
 * Ensure WooCommerce scripts are loaded for AJAX add to cart
 */
function edublink_child_enqueue_woocommerce_scripts()
{
	if (class_exists('WooCommerce') && is_front_page()) {
		// Ensure WooCommerce add to cart script is loaded
		if (!wp_script_is('wc-add-to-cart', 'enqueued')) {
			wp_enqueue_script('wc-add-to-cart');
		}
	}
}
add_action('wp_enqueue_scripts', 'edublink_child_enqueue_woocommerce_scripts', 101);

/**
 * Dynamic Assets Loader
 */
function edublink_child_load_page_assets()
{
	$assets_dir = get_stylesheet_directory() . '/assets';
	$assets_uri = get_stylesheet_directory_uri() . '/assets';
	$page_type = '';

	if (is_404())
		$page_type = '404';
	elseif (is_front_page())
		$page_type = 'home';
	elseif (is_page('about_me') || is_page_template('page-about_me.php'))
		$page_type = 'about-me';
	elseif (is_page('dashboard'))
		$page_type = 'dashboard';
	elseif (is_page('signup'))
		$page_type = 'signup';
	elseif (is_product()) {
		// Check if product has bundles
		global $wpdb;
		$product_id = get_the_ID();
		$has_bundles = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}asnp_wepb_simple_bundle_items WHERE bundle_id = %d",
			$product_id
		));
		$page_type = ($has_bundles > 0) ? 'single-product-bundle' : 'single-product';
	} elseif (is_shop() || is_product_category() || is_product_tag())
		$page_type = 'product_archive';
	elseif (is_cart() || is_page('cart-1'))
		$page_type = 'cart';
	elseif (is_checkout())
		$page_type = 'checkout';
	elseif (function_exists('tutor_utils')) {
		$course_post_type = tutor()->course_post_type;
		if (is_singular($course_post_type))
			$page_type = 'single-course';
		elseif (is_post_type_archive($course_post_type) || is_tax('course-category'))
			$page_type = 'course_archive';
	}

	if (empty($page_type)) {
		$template = get_page_template_slug();
		if (!empty($template))
			$page_type = str_replace(array('.php', '-', '/'), array('', '_', '_'), basename($template));
		// Also check page slug
		if (empty($page_type) && is_page()) {
			$page_slug = get_post_field('post_name', get_the_ID());
			if ($page_slug === 'about_me')
				$page_type = 'about-me';
			elseif ($page_slug === 'dashboard')
				$page_type = 'dashboard';
			elseif ($page_slug === 'signup')
				$page_type = 'signup';
		}
		// Check for Tutor LMS dashboard (via query vars)
		if (empty($page_type) && function_exists('tutor_utils')) {
			global $wp_query;
			if (isset($wp_query->query_vars['tutor_dashboard_page']) || isset($wp_query->query_vars['tutor_dashboard'])) {
				$page_type = 'dashboard';
			}
		}
		// Check URL path for dashboard or signup
		if (empty($page_type)) {
			$request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
			if (!empty($request_uri)) {
				if (strpos($request_uri, '/dashboard/') !== false || strpos($request_uri, '/dashboard') !== false) {
					$page_type = 'dashboard';
				} elseif (strpos($request_uri, '/signup/') !== false || strpos($request_uri, '/signup') !== false) {
					$page_type = 'signup';
				}
			}
		}
	}

	if (!empty($page_type) && is_dir($assets_dir . '/' . $page_type)) {
		$css_file = $assets_dir . '/' . $page_type . '/style.css';
		$js_file = $assets_dir . '/' . $page_type . '/script.js';

		if (file_exists($css_file)) {
			// Load with high priority and no dependencies to ensure it loads last and can override everything
			// Using empty array for dependencies ensures it loads after all other styles
			wp_enqueue_style('edublink-' . $page_type . '-style', $assets_uri . '/' . $page_type . '/style.css', array(), filemtime($css_file));
		}
		if (file_exists($js_file)) {
			wp_enqueue_script('edublink-' . $page_type . '-script', $assets_uri . '/' . $page_type . '/script.js', array('jquery'), filemtime($js_file), true);
		}

	}

	// Also load signup styles on dashboard page when user is NOT logged in (login form)
	if ($page_type === 'dashboard' && !is_user_logged_in()) {
		$signup_css = $assets_dir . '/signup/style.css';
		if (file_exists($signup_css)) {
			wp_enqueue_style('edublink-signup-style', $assets_uri . '/signup/style.css', array(), filemtime($signup_css));
		}
	}
}
add_action('wp_enqueue_scripts', 'edublink_child_load_page_assets', 999);

/**
 * Add page-specific CSS after all other styles (including Elementor)
 * This ensures our custom CSS can override everything
 */
function edublink_child_add_page_css_late()
{
	$assets_dir = get_stylesheet_directory() . '/assets';
	$assets_uri = get_stylesheet_directory_uri() . '/assets';
	$page_type = '';

	// Re-detect page type (same logic as edublink_child_load_page_assets)
	if (is_404())
		$page_type = '404';
	elseif (is_front_page())
		$page_type = 'home';
	elseif (is_page('about_me') || is_page_template('page-about_me.php'))
		$page_type = 'about-me';
	elseif (is_page('dashboard'))
		$page_type = 'dashboard';
	elseif (is_page('signup'))
		$page_type = 'signup';
	elseif (is_product()) {
		global $wpdb;
		$product_id = get_the_ID();
		$has_bundles = $wpdb->get_var($wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}asnp_wepb_simple_bundle_items WHERE bundle_id = %d",
			$product_id
		));
		$page_type = ($has_bundles > 0) ? 'single-product-bundle' : 'single-product';
	} elseif (is_shop() || is_product_category() || is_product_tag())
		$page_type = 'product_archive';
	elseif (is_cart() || is_page('cart-1'))
		$page_type = 'cart';
	elseif (is_checkout())
		$page_type = 'checkout';
	elseif (function_exists('tutor_utils')) {
		$course_post_type = tutor()->course_post_type;
		if (is_singular($course_post_type))
			$page_type = 'single-course';
		elseif (is_post_type_archive($course_post_type) || is_tax('course-category'))
			$page_type = 'course_archive';
	}

	if (empty($page_type)) {
		$template = get_page_template_slug();
		if (!empty($template))
			$page_type = str_replace(array('.php', '-', '/'), array('', '_', '_'), basename($template));
		if (empty($page_type) && is_page()) {
			$page_slug = get_post_field('post_name', get_the_ID());
			if ($page_slug === 'about_me')
				$page_type = 'about-me';
			elseif ($page_slug === 'dashboard')
				$page_type = 'dashboard';
			elseif ($page_slug === 'signup')
				$page_type = 'signup';
		}
		if (empty($page_type) && function_exists('tutor_utils')) {
			global $wp_query;
			if (isset($wp_query->query_vars['tutor_dashboard_page']) || isset($wp_query->query_vars['tutor_dashboard'])) {
				$page_type = 'dashboard';
			}
		}
		if (empty($page_type)) {
			$request_uri = isset($_SERVER['REQUEST_URI']) ? esc_url_raw(wp_unslash($_SERVER['REQUEST_URI'])) : '';
			if (!empty($request_uri)) {
				if (strpos($request_uri, '/dashboard/') !== false || strpos($request_uri, '/dashboard') !== false) {
					$page_type = 'dashboard';
				} elseif (strpos($request_uri, '/signup/') !== false || strpos($request_uri, '/signup') !== false) {
					$page_type = 'signup';
				}
			}
		}
	}

	// 1) Add CSS via wp_head to ensure it loads after all other styles (including Elementor)
	//    BUT only for pages that really needed hard overrides (dashboard + signup).
	if (in_array($page_type, array('dashboard', 'signup'), true) && is_dir($assets_dir . '/' . $page_type)) {
		$css_file = $assets_dir . '/' . $page_type . '/style.css';
		if (file_exists($css_file)) {
			echo '<link rel="stylesheet" id="edublink-' . esc_attr($page_type) . '-style-late" href="' . esc_url($assets_uri . '/' . $page_type . '/style.css?v=' . filemtime($css_file)) . '" type="text/css" media="all" />' . "\n";
		}
	}

	// Also load signup styles late on dashboard page when user is NOT logged in (login form)
	if ($page_type === 'dashboard' && !is_user_logged_in()) {
		$signup_css = $assets_dir . '/signup/style.css';
		if (file_exists($signup_css)) {
			echo '<link rel="stylesheet" id="edublink-signup-style-late" href="' . esc_url($assets_uri . '/signup/style.css?v=' . filemtime($signup_css)) . '" type="text/css" media="all" />' . "\n";
		}
	}

	// 2) Always load header/footer root protection last, on all pages.
	$hf_css = $assets_dir . '/header-footer-root.css';
	if (file_exists($hf_css)) {
		echo '<link rel="stylesheet" id="edublink-header-footer-root-style" href="' . esc_url($assets_uri . '/header-footer-root.css?v=' . filemtime($hf_css)) . '" type="text/css" media="all" />' . "\n";
	}
}
add_action('wp_head', 'edublink_child_add_page_css_late', 9999);

/**
 * Load global custom override CSS file (highest priority - loads last)
 * This file can override any CSS on any page across the entire site
 */
function edublink_child_load_global_override_css()
{
	$override_css = get_stylesheet_directory() . '/assets/global/custom-override.css';
	$override_css_uri = get_stylesheet_directory_uri() . '/assets/global/custom-override.css';

	if (file_exists($override_css)) {
		// Load with priority 10000 (higher than header-footer-root.css) to be the absolute last CSS file
		echo '<link rel="stylesheet" id="edublink-global-override-style" href="' . esc_url($override_css_uri . '?v=' . filemtime($override_css)) . '" type="text/css" media="all" />' . "\n";
	}
}
add_action('wp_head', 'edublink_child_load_global_override_css', 10000);

/**
 * Inject inline dark-mode CSS for Tutor LMS lesson sidebar (spotlight mode).
 * Loaded via wp_footer to guarantee it renders AFTER all other stylesheets,
 * bypassing any file-level server/CDN caching.
 */
function edublink_child_lesson_sidebar_dark_mode()
{
	// Load on ALL Tutor LMS spotlight pages (lessons, quizzes, assignments)
	$request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
	$is_spotlight_page = strpos($request_uri, '/lesson/') !== false
		|| strpos($request_uri, '/quiz/') !== false
		|| strpos($request_uri, '/quizzes/') !== false
		|| strpos($request_uri, '/tutor-quiz/') !== false
		|| strpos($request_uri, '/assignments/') !== false
		|| strpos($request_uri, '/tutor-assignment/') !== false;
	if (!$is_spotlight_page) {
		return;
	}
	?>
	<style id="learnsimply-sidebar-dark-mode">
		/* ── CSS Variables override (root cause of black icons) ── */
		.tutor-course-spotlight-sidebar,
		.tutor-course-topics-sidebar,
		.tutor-lesson-sidebar,
		.tutor-course-single-sidebar-wrapper,
		div[class*="course-spotlight-sidebar"],
		div[class*="topics-sidebar"] {
			--tutor-color-black: #c8cfe0 !important;
			--tutor-color-text: #c8cfe0 !important;
			--tutor-color-text-primary: #c8cfe0 !important;
			--tutor-color-text-secondary: #8893b0 !important;
			--tutor-color-text-hints: #8893b0 !important;
			--tutor-color-secondary: #8893b0 !important;
			--tutor-color-muted: #8893b0 !important;
			--tutor-icon-color: #8893b0 !important;
			--tutor-color-design-system-dark: #c8cfe0 !important;
			--color-text-primary: #c8cfe0 !important;
			--color-text-secondary: #8893b0 !important;
		}

		/* ── Sidebar container ── */
		.tutor-course-spotlight-sidebar,
		.tutor-course-spotlight-sidebar>div,
		.tutor-course-topics-sidebar,
		.tutor-course-single-sidebar-wrapper {
			background-color: #141924 !important;
			color: #999eb2 !important;
			border-right: 1px solid rgba(255, 255, 255, 0.08) !important;
			border-left: 1px solid rgba(255, 255, 255, 0.08) !important;
		}

		/* ── Sidebar header ── */
		.tutor-course-spotlight-sidebar-header,
		.tutor-course-single-sidebar-title {
			background-color: #141924 !important;
			color: #fff !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.1) !important;
		}

		/* ── Topic / module container ── */
		.tutor-course-topic-single {
			background-color: transparent !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
		}

		/* ── Topic / module header (e.g. الوحدة الاولي) ── */
		.tutor-course-topics-header {
			background-color: rgba(255, 255, 255, 0.04) !important;
			color: #fff !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.06) !important;
		}

		.tutor-course-topics-header:hover {
			background-color: rgba(255, 255, 255, 0.08) !important;
		}

		.tutor-course-topics-header,
		.tutor-course-topics-header * {
			color: #fff !important;
		}

		/* ── Topic title (older Tutor versions) ── */
		.tutor-course-topic-title,
		.tutor-course-topic-title *,
		.tutor-course-topic-title span,
		.tutor-course-topic-title div {
			color: #fff !important;
			background-color: rgba(255, 255, 255, 0.04) !important;
		}

		/* ── Lessons list container ── */
		.tutor-course-lessons-list {
			background-color: transparent !important;
		}

		/* ── Individual lesson items ── */
		.tutor-course-lesson-item,
		a.tutor-course-lesson-item,
		.tutor-course-topic-item {
			color: #999eb2 !important;
			background-color: transparent !important;
			border-bottom: 1px solid rgba(255, 255, 255, 0.04) !important;
		}

		.tutor-course-lesson-item span,
		.tutor-course-lesson-item div,
		.tutor-course-topic-item-title {
			color: inherit !important;
		}

		/* Hover + active state */
		.tutor-course-lesson-item:hover,
		.tutor-course-lesson-item.is-active,
		.tutor-course-lesson-item.tutor-active,
		.tutor-course-lesson-item.active,
		.tutor-course-topic-item:hover,
		.tutor-course-topic-item.is-active {
			background-color: rgba(64, 119, 243, 0.1) !important;
			color: #fff !important;
			border-radius: 6px;
		}

		/* ── Icons ── */
		.tutor-course-spotlight-sidebar [class^="tutor-icon-"],
		.tutor-course-spotlight-sidebar [class*=" tutor-icon-"],
		.tutor-course-spotlight-sidebar .tutor-form-check-input {
			color: #999eb2 !important;
			border-color: rgba(255, 255, 255, 0.2) !important;
			background-color: transparent !important;
		}

		/* SVG icons with hardcoded fill (Tutor LMS v2+) */
		.tutor-course-spotlight-sidebar svg,
		.tutor-course-spotlight-sidebar svg path,
		.tutor-course-spotlight-sidebar svg rect,
		.tutor-course-spotlight-sidebar svg circle,
		.tutor-course-topics-sidebar svg,
		.tutor-course-topics-sidebar svg path,
		.tutor-course-topics-sidebar svg rect,
		.tutor-course-topics-sidebar svg circle {
			fill: #8893b0 !important;
		}

		/* Active / hover lesson SVG icons → primary blue */
		.tutor-course-topic-item:hover svg,
		.tutor-course-topic-item:hover svg path,
		.tutor-course-topic-item.is-active svg,
		.tutor-course-topic-item.is-active svg path,
		.tutor-course-lesson-item:hover svg,
		.tutor-course-lesson-item:hover svg path,
		.tutor-course-lesson-item.is-active svg,
		.tutor-course-lesson-item.is-active svg path {
			fill: #4077f3 !important;
		}

		/* Checked/completed circle icon stays blue */
		.tutor-course-spotlight-sidebar .tutor-form-check-input:checked svg,
		.tutor-course-spotlight-sidebar .tutor-form-check-input:checked svg path {
			fill: #ffffff !important;
		}

		/* Arrow / chevron icons → white */
		.tutor-course-spotlight-sidebar [class*="tutor-icon-angle"] {
			color: #fff !important;
		}

		/* Info icon → accent blue */
		.tutor-course-spotlight-sidebar .tutor-icon-circle-info,
		.tutor-course-spotlight-sidebar .tutor-icon-info-circle {
			color: #4077f3 !important;
		}

		/* Checked checkbox */
		.tutor-course-spotlight-sidebar .tutor-form-check-input:checked,
		.tutor-course-spotlight-sidebar input[type="checkbox"]:checked {
			background-color: #4077f3 !important;
			border-color: #4077f3 !important;
		}

		/* ── Progress counts (0/5, 2/18) ── */
		.tutor-course-spotlight-sidebar .tutor-fs-7,
		.tutor-course-spotlight-sidebar .tutor-color-muted {
			color: #999eb2 !important;
		}

		/* ── Override Tutor utility bg/color classes inside sidebar ── */
		.tutor-course-spotlight-sidebar .tutor-bg-white,
		.tutor-course-spotlight-sidebar [class*="bg-white"],
		.tutor-course-spotlight-sidebar [class*="bg-light"] {
			background-color: #141924 !important;
		}

		.tutor-course-spotlight-sidebar .tutor-color-black,
		.tutor-course-spotlight-sidebar .tutor-color-dark,
		.tutor-course-spotlight-sidebar [class*="color-black"],
		.tutor-course-spotlight-sidebar [class*="color-dark"] {
			color: #fff !important;
		}

		/* ── Default state icons: high-specificity selectors to beat Tutor LMS ── */
		/* Icon fonts – nested 3 classes deep to exceed Tutor's own specificity */
		.tutor-course-spotlight-sidebar .tutor-course-topic-item i,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item [class^="tutor-icon-"],
		.tutor-course-spotlight-sidebar .tutor-course-topic-item [class*=" tutor-icon-"],
		.tutor-course-spotlight-sidebar .tutor-course-topic-item [class*="icon"],
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item i,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item [class^="tutor-icon-"],
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item [class*=" tutor-icon-"],
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item [class*="icon"],
		.tutor-course-topics-sidebar .tutor-course-topic-item i,
		.tutor-course-topics-sidebar .tutor-course-topic-item [class^="tutor-icon-"],
		.tutor-course-topics-sidebar .tutor-course-topic-item [class*=" tutor-icon-"],
		.tutor-course-topics-sidebar .tutor-course-topic-item [class*="icon"],
		.tutor-course-topics-sidebar li i,
		.tutor-course-topics-sidebar li [class^="tutor-icon-"],
		.tutor-course-topics-sidebar li [class*=" tutor-icon-"] {
			color: #8893b0 !important;
		}

		.tutor-course-spotlight-sidebar .tutor-course-topic-item i::before,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item [class^="tutor-icon-"]::before,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item [class*=" tutor-icon-"]::before,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item i::before,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item [class^="tutor-icon-"]::before,
		.tutor-course-topics-sidebar .tutor-course-topic-item i::before,
		.tutor-course-topics-sidebar .tutor-course-topic-item [class^="tutor-icon-"]::before,
		.tutor-course-topics-sidebar li i::before,
		.tutor-course-topics-sidebar li [class^="tutor-icon-"]::before {
			color: #8893b0 !important;
		}

		/* SVG icons – nested for high specificity */
		.tutor-course-spotlight-sidebar .tutor-course-topic-item svg,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item svg *,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item svg,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item svg *,
		.tutor-course-topics-sidebar .tutor-course-topic-item svg,
		.tutor-course-topics-sidebar .tutor-course-topic-item svg *,
		.tutor-course-topics-sidebar li svg,
		.tutor-course-topics-sidebar li svg * {
			fill: #8893b0 !important;
			color: #8893b0 !important;
		}

		/* Active/hover → blue (same depth, so equal specificity, comes later = wins) */
		.tutor-course-spotlight-sidebar .tutor-course-topic-item:hover i,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item:hover [class*="icon"],
		.tutor-course-spotlight-sidebar .tutor-course-topic-item.is-active i,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item.is-active [class*="icon"],
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item:hover i,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item:hover [class*="icon"],
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item.is-active i,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item.is-active [class*="icon"],
		.tutor-course-topics-sidebar .tutor-course-topic-item:hover i,
		.tutor-course-topics-sidebar .tutor-course-topic-item:hover [class*="icon"],
		.tutor-course-topics-sidebar .tutor-course-topic-item.is-active i,
		.tutor-course-topics-sidebar .tutor-course-topic-item.is-active [class*="icon"],
		.tutor-course-topics-sidebar li:hover i,
		.tutor-course-topics-sidebar li:hover [class*="icon"],
		.tutor-course-topics-sidebar li.is-active i,
		.tutor-course-topics-sidebar li.is-active [class*="icon"] {
			color: #4077f3 !important;
		}

		.tutor-course-spotlight-sidebar .tutor-course-topic-item:hover svg *,
		.tutor-course-spotlight-sidebar .tutor-course-topic-item.is-active svg *,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item:hover svg *,
		.tutor-course-spotlight-sidebar .tutor-course-lesson-item.is-active svg *,
		.tutor-course-topics-sidebar .tutor-course-topic-item:hover svg *,
		.tutor-course-topics-sidebar .tutor-course-topic-item.is-active svg *,
		.tutor-course-topics-sidebar li:hover svg *,
		.tutor-course-topics-sidebar li.is-active svg * {
			fill: #4077f3 !important;
		}

		/* ── Quiz items: always blue icon, never grey ── */
		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] i,
		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] [class^="tutor-icon-"],
		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] [class*=" tutor-icon-"],
		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] [class*="icon"],
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] i,
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] [class^="tutor-icon-"],
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] [class*=" tutor-icon-"],
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] [class*="icon"] {
			color: #4077f3 !important;
		}

		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] i::before,
		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] [class^="tutor-icon-"]::before,
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] i::before,
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] [class^="tutor-icon-"]::before {
			color: #4077f3 !important;
		}

		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] svg,
		.tutor-course-spotlight-sidebar [data-content-type="tutor_quiz"] svg *,
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] svg,
		.tutor-course-topics-sidebar [data-content-type="tutor_quiz"] svg * {
			fill: #4077f3 !important;
			color: #4077f3 !important;
		}

		/* ── NUCLEAR: wipe stray backgrounds on ALL sidebar descendants ── */
		.tutor-course-spotlight-sidebar *:not(.tutor-btn):not([class*="icon"]):not(svg):not(path):not(i):not(input) {
			background-color: transparent !important;
		}

		/* Re-apply sidebar container bg */
		.tutor-course-spotlight-sidebar {
			background-color: #141924 !important;
		}

		/* Re-apply topic header bg */
		.tutor-course-topics-header {
			background-color: rgba(255, 255, 255, 0.04) !important;
		}

		/* Re-apply hover/active bg */
		.tutor-course-lesson-item:hover,
		.tutor-course-lesson-item.is-active,
		.tutor-course-lesson-item.tutor-active,
		.tutor-course-lesson-item.active {
			background-color: rgba(64, 119, 243, 0.1) !important;
		}

		/* ── Scrollbar ── */
		.tutor-course-spotlight-sidebar::-webkit-scrollbar {
			width: 6px;
		}

		.tutor-course-spotlight-sidebar::-webkit-scrollbar-track {
			background: #0a0f1a;
		}

		.tutor-course-spotlight-sidebar::-webkit-scrollbar-thumb {
			background: rgba(255, 255, 255, 0.15);
			border-radius: 3px;
		}

		.tutor-course-spotlight-sidebar::-webkit-scrollbar-thumb:hover {
			background: rgba(255, 255, 255, 0.25);
		}
	</style>
	<script>
		(function () {
			var DC = '#8893b0';
			var AC = '#4077f3';
			var SELS = '.tutor-course-spotlight-sidebar, .tutor-course-topics-sidebar, .tutor-lesson-sidebar';
			var isRunning = false;

			function fixIcons() {
				if (isRunning) return;
				isRunning = true;
				var sidebars = document.querySelectorAll(SELS);
				sidebars.forEach(function (sb) {
					sb.querySelectorAll('svg').forEach(function (svg) {
						var isActive = svg.closest('.is-active, .tutor-active, .active');
						var isQuiz = svg.closest('[data-content-type="tutor_quiz"]');
						var c = (isActive || isQuiz) ? AC : DC;
						svg.style.fill = c;
						svg.style.color = c;
						svg.querySelectorAll('path, rect, circle').forEach(function (s) {
							s.style.fill = c;
						});
					});
					sb.querySelectorAll('i, [class^="tutor-icon-"]').forEach(function (el) {
						var isActive = el.closest('.is-active, .tutor-active, .active');
						var isQuiz = el.closest('[data-content-type="tutor_quiz"]');
						el.style.color = (isActive || isQuiz) ? AC : DC;
					});
				});
				isRunning = false;
			}

			document.addEventListener('DOMContentLoaded', fixIcons);
			window.addEventListener('load', function () {
				fixIcons();
				setTimeout(fixIcons, 1000);
			});

			var timeout;
			var obs = new MutationObserver(function (mutations) {
				var dominated = mutations.some(function (m) {
					return m.type === 'childList' ||
						(m.type === 'attributes' && m.attributeName === 'class');
				});
				if (!dominated) return;
				clearTimeout(timeout);
				timeout = setTimeout(fixIcons, 100);
			});
			if (document.body) {
				obs.observe(document.body, {
					childList: true, subtree: true, attributes: true,
					attributeFilter: ['class']
				});
			}

			document.addEventListener('click', function () {
				setTimeout(fixIcons, 100);
			});
		})();
	</script>
	<!-- <script>
	(function(){
		var DC = '#8893b0';
		var AC = '#4077f3';
		var SELS = '.tutor-course-spotlight-sidebar, .tutor-course-topics-sidebar, .tutor-lesson-sidebar, [class*="spotlight-sidebar"], [class*="topics-sidebar"]';

		function fixIcons(){
			var sidebars = document.querySelectorAll(SELS);
			if(!sidebars.length){
				// Sidebar not found yet — try broader selectors
				sidebars = document.querySelectorAll('[class*="sidebar"]');
			}
			sidebars.forEach(function(sb){
				// ALL svg elements
				sb.querySelectorAll('svg').forEach(function(svg){
					var isActive = svg.closest('.is-active, .tutor-active, .active');
					var c = isActive ? AC : DC;
					svg.setAttribute('fill', c);
					svg.setAttribute('color', c);
					svg.style.cssText += 'fill:'+c+'!important;color:'+c+'!important;';
					var shapes = svg.querySelectorAll('*');
					for(var i=0;i<shapes.length;i++){
						var s = shapes[i];
						s.setAttribute('fill', c);
						s.style.cssText += 'fill:'+c+'!important;';
						if(s.hasAttribute('stroke') && s.getAttribute('stroke')!=='none'){
							s.setAttribute('stroke', c);
							s.style.cssText += 'stroke:'+c+'!important;';
						}
					}
				});
				// Icon fonts
				sb.querySelectorAll('i, [class*="tutor-icon"]').forEach(function(el){
					var isActive = el.closest('.is-active, .tutor-active, .active');
					var c = isActive ? AC : DC;
					el.style.cssText += 'color:'+c+'!important;';
				});
			});
		}

		// Aggressive polling for the first 10 seconds (sidebar may load late via JS)
		var start = Date.now();
		function poll(){
			fixIcons();
			if(Date.now() - start < 10000){
				requestAnimationFrame(poll);
			}
		}
		poll();

		// Also run on standard events
		document.addEventListener('DOMContentLoaded', fixIcons);
		window.addEventListener('load', function(){
			fixIcons();
			setTimeout(fixIcons, 1000);
			setTimeout(fixIcons, 3000);
			setTimeout(fixIcons, 5000);
		});

		// MutationObserver on body (catches sidebar creation too)
		var obs = new MutationObserver(function(){ fixIcons(); });
		obs.observe(document.body || document.documentElement, {
			childList: true, subtree: true, attributes: true,
			attributeFilter: ['class','style','fill']
		});

		// Re-fix on any click (topic expand, lesson switch)
		document.addEventListener('click', function(){
			setTimeout(fixIcons, 50);
			setTimeout(fixIcons, 300);
			setTimeout(fixIcons, 800);
		});
	})();
	</script> -->
	<?php
}
add_action('wp_footer', 'edublink_child_lesson_sidebar_dark_mode', 99999);

/**
 * Remove WooCommerce CSS
 */
function edublink_child_remove_woocommerce_styles()
{
	if (is_product() || is_shop() || is_product_category() || is_product_tag()) {
		wp_dequeue_style('woocommerce-general');
		wp_dequeue_style('woocommerce-layout');
		wp_dequeue_style('woocommerce-smallscreen');
		wp_dequeue_style('edublink-woocommerce');
	}
}
add_action('wp_enqueue_scripts', 'edublink_child_remove_woocommerce_styles', 999);

/**
 * Override WooCommerce Templates
 */
function edublink_child_override_woocommerce_templates($template, $template_name, $args = array(), $template_path = '', $default_path = '')
{
	if ('cart/cart.php' === $template_name)
		$child_template = get_stylesheet_directory() . '/woocommerce/cart/cart.php';
	elseif ('content-single-product.php' === $template_name)
		$child_template = get_stylesheet_directory() . '/woocommerce/content-single-product.php';
	elseif ('single-product/tabs/tabs.php' === $template_name)
		$child_template = get_stylesheet_directory() . '/woocommerce/single-product/tabs/tabs.php';

	if (isset($child_template) && file_exists($child_template))
		return $child_template;
	return $template;
}
add_filter('wc_get_template', 'edublink_child_override_woocommerce_templates', 5, 5);
add_filter('woocommerce_locate_template', 'edublink_child_override_woocommerce_templates', 1, 4);

/**
 * Override Course Archive
 */
function edublink_child_override_course_archive_template($template)
{
	if (function_exists('tutor_utils')) {
		$course_post_type = tutor()->course_post_type;
		$post_type = get_query_var('post_type');
		$course_category = get_query_var('course-category');

		if ((is_post_type_archive($course_post_type) || (!empty($post_type) && in_array($course_post_type, (array) $post_type, true)) || !empty($course_category)) && is_archive()) {
			$child_template = get_stylesheet_directory() . '/archive-courses.php';
			if (file_exists($child_template))
				return $child_template;
		}
	}
	return $template;
}
add_filter('template_include', 'edublink_child_override_course_archive_template', 999);

/**
 * Disable Elementor Product Templates
 */
function edublink_child_disable_elementor_product_mods()
{
	if (is_product()) {
		if (class_exists('\ElementorPro\Modules\ThemeBuilder\Module')) {
			add_filter('elementor/theme/get_location_templates', '__return_empty_array', 999);
			add_filter('elementor/theme/get_location_template_id', '__return_false', 999);
		}
		add_filter('wpr_theme_builder_template_id', '__return_false', 999);
		add_filter('wpr_theme_builder_should_render', '__return_false', 999);
	}
}
add_action('template_redirect', 'edublink_child_disable_elementor_product_mods', 1);

/**
 * Remove OLD theme/Elementor promo bar (excludes our learnsimply-promo-banner)
 */
function edublink_child_remove_promo_bar_enhanced()
{
	if (!is_product())
		return;
	?>
	<style id="edublink-remove-promo-bar">
		#promo-bar,
		.promo-bar,
		.promo-inner,
		.promo-left,
		.promo-timer,
		.promo-btn {
			display: none !important;
			visibility: hidden !important;
			height: 0 !important;
			overflow: hidden !important;
		}
	</style>
	<script>
		(function () {
			function removePromoBar() {
				const selectors = ['#promo-bar', '.promo-bar', '.promo-inner', '.promo-left', '.promo-timer', '.promo-btn'];
				selectors.forEach(function (s) {
					document.querySelectorAll(s).forEach(function (el) {
						if (!el.closest('.learnsimply-promo-banner')) el.remove();
					});
				});
			}
			if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', removePromoBar);
			else removePromoBar();
		})();
	</script>
	<?php
}
add_action('wp_footer', 'edublink_child_remove_promo_bar_enhanced', 999);

/* ==========================================================================
   SINGLE BLOG POST - INJECT ABSOLUTE DARK MODE (runs after ALL stylesheets)
   ========================================================================== */

add_action('wp_footer', 'learnsimply_inject_single_post_dark_mode', 10000);

function learnsimply_inject_single_post_dark_mode()
{
	if (!is_single() || get_post_type() !== 'post') {
		return;
	}
	?>
	<style id="learnsimply-single-post-dark">
		/* === SINGLE POST DARK MODE — scoped to content area only === */
		/* Header, footer, whatsapp button, and other global elements are NOT touched */

		/* 1. Reset only inside the article content area */
		body.single-post .sp-content *:not(img):not(video):not(iframe):not(canvas):not(svg):not(path):not(circle):not(rect):not(polygon):not(line):not(polyline):not(use):not(defs):not(g):not(symbol):not(clipPath):not(linearGradient):not(stop) {
			background-color: transparent !important;
			color: var(--db-white, #d0d4e0) !important;
		}

		/* 2. Headings inside content → white */
		body.single-post .sp-content h1,
		body.single-post .sp-content h1 *,
		body.single-post .sp-content h2,
		body.single-post .sp-content h2 *,
		body.single-post .sp-content h3,
		body.single-post .sp-content h3 *,
		body.single-post .sp-content h4,
		body.single-post .sp-content h4 *,
		body.single-post .sp-content h5,
		body.single-post .sp-content h5 *,
		body.single-post .sp-content h6,
		body.single-post .sp-content h6 * {
			color: #ffffff !important;
			background-color: transparent !important;
		}

		/* 3. Transparent wrappers for media */
		body.single-post .sp-content img,
		body.single-post .sp-content figure,
		body.single-post .sp-content .wp-block-image,
		body.single-post .sp-content [class*="thumbnail"],
		body.single-post .sp-content [class*="featured-image"] {
			background-color: transparent !important;
		}

		/* 4. Links inside content */
		body.single-post .sp-content a {
			color: #4d85f5 !important;
			background-color: transparent !important;
		}

		body.single-post .sp-content a:hover {
			color: #7aabff !important;
		}

		/* 5. Form fields */
		body.single-post .sp-comments input:not([type="submit"]):not([type="button"]),
		body.single-post .sp-comments textarea,
		body.single-post .sp-comments select {
			background-color: #1b2133 !important;
			color: #d0d5e8 !important;
			border-color: rgba(255, 255, 255, 0.15) !important;
		}

		/* 6. Submit buttons (comments area only) */
		body.single-post .sp-comments input[type="submit"],
		body.single-post .sp-comments button[type="submit"],
		body.single-post .sp-comments .edu-btn,
		body.single-post .sp-comments a.edu-btn,
		body.single-post .sp-comments .wp-block-button__link {
			background-color: #4077f3 !important;
			color: #ffffff !important;
			border-color: #4077f3 !important;
		}

		/* 7. Code / pre */
		body.single-post .sp-content code,
		body.single-post .sp-content code *,
		body.single-post .sp-content pre,
		body.single-post .sp-content pre * {
			background-color: #1b2133 !important;
			color: #e2e8f0 !important;
		}

		/* 8. Blockquote */
		body.single-post .sp-content blockquote,
		body.single-post .sp-content blockquote * {
			background-color: #111827 !important;
			border-color: #4077f3 !important;
			color: #c0c8de !important;
		}

		/* ================================================================
	   PROMO BANNER RESTORE — exact values from promo-banner/style.css
	   Must come AFTER the nuclear rule to win the specificity war
	   ================================================================ */
		body.single-post .learnsimply-promo-banner,
		body.single-post .learnsimply-promo-banner * {
			display: none !important;
		}

		/* ================================================================
	   KILL ALL WHITE / LIGHT BACKGROUNDS — content area & Gutenberg
	   Targets inline styles, has-background blocks, and theme wrappers
	   ================================================================ */

		/* Any element with an inline background-color that's white/light */
		body.single-post .sp-content [style*="background-color"],
		body.single-post .sp-content [style*="background:"],
		body.single-post .sp-content .has-background,
		body.single-post .sp-content .wp-block-group,
		body.single-post .sp-content .wp-block-group__inner-container,
		body.single-post .sp-content .wp-block-cover,
		body.single-post .sp-content .wp-block-cover__inner-container,
		body.single-post .sp-content .wp-block-columns,
		body.single-post .sp-content .wp-block-column,
		body.single-post .sp-content .wp-block-media-text,
		body.single-post .sp-content .wp-block-pullquote,
		body.single-post .sp-content .wp-block-table,
		body.single-post .sp-content .wp-block-verse,
		body.single-post .sp-content .wp-block-preformatted,
		body.single-post .sp-content .wp-block-html,
		body.single-post .sp-content .entry-content,
		body.single-post .sp-content .post-content,
		body.single-post .sp-content .article-content,
		body.single-post .entry-content,
		body.single-post .post-content,
		body.single-post .article-content,
		body.single-post .post-wrapper,
		body.single-post .post-inner,
		body.single-post .single-post-content,
		body.single-post .edublink-post-content,
		body.single-post [class*="post-content"],
		body.single-post [class*="entry-content"] {
			background-color: transparent !important;
			background: transparent !important;
		}

		/* Gutenberg named colour classes */
		body.single-post .sp-content .has-white-background-color,
		body.single-post .sp-content .has-light-gray-background-color,
		body.single-post .sp-content .has-pale-pink-background-color,
		body.single-post .sp-content .has-very-light-gray-background-color,
		body.single-post .sp-content [class*="has-"][class*="background-color"],
		body.single-post .sp-content [class*="has-background"] {
			background-color: transparent !important;
			background: transparent !important;
		}
	</style>
	<script id="learnsimply-kill-white-bg">
		/* Kill any white/light inline background-color set directly on Gutenberg blocks */
		(function () {
			var LIGHT_THRESHOLD = 200; /* RGB channels above this = "light" */

			function isLight(color) {
				var m = color.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
				if (!m) return false;
				return parseInt(m[1]) > LIGHT_THRESHOLD &&
					parseInt(m[2]) > LIGHT_THRESHOLD &&
					parseInt(m[3]) > LIGHT_THRESHOLD;
			}

			function killWhiteBg() {
				var content = document.querySelector('.sp-content');
				if (!content) return;

				/* 1 — strip inline style background-color / background */
				var all = content.querySelectorAll('*');
				for (var i = 0; i < all.length; i++) {
					var el = all[i];
					var s = el.style;
					if (s.backgroundColor) {
						if (isLight(s.backgroundColor) || s.backgroundColor === 'white' || s.backgroundColor === '#fff' || s.backgroundColor === '#ffffff') {
							s.removeProperty('background-color');
						}
					}
					if (s.background && (s.background.indexOf('white') !== -1 || s.background.indexOf('#fff') !== -1 || s.background.indexOf('rgb(255, 255, 255)') !== -1)) {
						s.removeProperty('background');
					}
				}

				/* 2 — check computed background-color for any remaining light element */
				for (var j = 0; j < all.length; j++) {
					var computed = window.getComputedStyle(all[j]).backgroundColor;
					if (computed && computed !== 'rgba(0, 0, 0, 0)' && computed !== 'transparent' && isLight(computed)) {
						all[j].style.setProperty('background-color', 'transparent', 'important');
					}
				}
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', killWhiteBg);
			} else {
				killWhiteBg();
			}
		})();
	</script>
	<?php
}

/* ==========================================================================
   BOOK PRODUCT METABOX
   ========================================================================== */

/**
 * Add custom metabox for book products
 */
function edublink_child_add_book_metabox()
{
	add_meta_box(
		'edublink_book_details',
		'تفاصيل الكتاب',
		'edublink_child_book_metabox_callback',
		'product',
		'side',
		'default'
	);
}
add_action('add_meta_boxes', 'edublink_child_add_book_metabox');

/**
 * Metabox callback function
 */
function edublink_child_book_metabox_callback($post)
{
	// Add nonce for security
	wp_nonce_field('edublink_book_metabox_nonce', 'edublink_book_metabox_nonce');

	// Get current values
	$book_pages = get_post_meta($post->ID, '_book_pages', true);
	$book_available_count = get_post_meta($post->ID, '_book_available_count', true);

	?>
	<div class="edublink-book-metabox" style="padding: 10px 0;">
		<p>
			<label for="book_pages" style="display: block; margin-bottom: 5px; font-weight: 600;">
				عدد الصفحات:
			</label>
			<input type="number" id="book_pages" name="book_pages" value="<?php echo esc_attr($book_pages); ?>"
				placeholder="مثال: 260" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
				min="0" />
			<span style="display: block; margin-top: 5px; color: #666; font-size: 12px;">
				أدخل عدد صفحات الكتاب
			</span>
		</p>

		<p>
			<label for="book_available_count" style="display: block; margin-bottom: 5px; font-weight: 600;">
				العدد المتوفر:
			</label>
			<input type="number" id="book_available_count" name="book_available_count"
				value="<?php echo esc_attr($book_available_count); ?>" placeholder="مثال: 40"
				style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" min="0" />
			<span style="display: block; margin-top: 5px; color: #666; font-size: 12px;">
				أدخل عدد النسخ المتوفرة من الكتاب
			</span>
		</p>
	</div>
	<?php
}

/**
 * Save metabox data
 */
function edublink_child_save_book_metabox($post_id)
{
	// Check if nonce is set
	if (!isset($_POST['edublink_book_metabox_nonce'])) {
		return;
	}

	// Verify nonce
	if (!wp_verify_nonce($_POST['edublink_book_metabox_nonce'], 'edublink_book_metabox_nonce')) {
		return;
	}

	// Check if this is an autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	// Check user permissions
	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	// Check if this is a product
	if (get_post_type($post_id) !== 'product') {
		return;
	}

	// Save book pages
	if (isset($_POST['book_pages'])) {
		$book_pages = sanitize_text_field($_POST['book_pages']);
		update_post_meta($post_id, '_book_pages', $book_pages);
	} else {
		delete_post_meta($post_id, '_book_pages');
	}

	// Save book available count
	if (isset($_POST['book_available_count'])) {
		$book_available_count = sanitize_text_field($_POST['book_available_count']);
		update_post_meta($post_id, '_book_available_count', $book_available_count);
	} else {
		delete_post_meta($post_id, '_book_available_count');
	}
}
add_action('save_post', 'edublink_child_save_book_metabox');
add_action('woocommerce_process_product_meta', 'edublink_child_save_book_metabox');

/**
 * Simple redirect to cart after add to cart
 * Only use woocommerce_add_to_cart_redirect filter - the standard WooCommerce way
 */
add_filter('woocommerce_add_to_cart_redirect', 'edublink_child_redirect_to_cart_after_add', 10);
function edublink_child_redirect_to_cart_after_add($url)
{
	// Redirect to cart page after adding product
	return wc_get_cart_url();
}

/**
 * Change 'Proceed to PayPal' button text to just 'Proceed'
 */
add_filter('woocommerce_order_button_text', 'learnsimply_change_checkout_button_text', 99);
function learnsimply_change_checkout_button_text($button_text)
{
	// Check for common variations
	if (strpos($button_text, 'PayPal') !== false) {
		return 'Proceed';
	}
	return $button_text;
}

add_filter('gettext', 'learnsimply_change_paypal_text', 20, 3);
function learnsimply_change_paypal_text($translated_text, $text, $domain)
{
	if ($text === 'Proceed to PayPal') {
		return 'Proceed';
	}
	return $translated_text;
}

