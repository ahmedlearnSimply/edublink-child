<?php
/**
 * EduBlink Child Theme functions and definitions
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Placeholder image URL when no image is available
 * Change path here to update across the entire theme
 */
function learnsimply_no_image_url() {
	return get_stylesheet_directory_uri() . '/assets/img/no-image.jpg';
}

/**
 * Load Composer dependencies
 */
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

add_filter('show_admin_bar', '__return_false');

/**
 * Initialize Timber
 */
if ( class_exists( 'Timber\Timber' ) ) {
	Timber\Timber::init();
	
	/**
	 * Set Timber locations
	 */
	Timber::$dirname = array( 'views', 'templates' );
	
	/**
	 * Add WordPress conditional functions to Timber context
	 */
	add_filter( 'timber/context', 'edublink_child_add_to_context' );
	function edublink_child_add_to_context( $context ) {
		// Global theme URI for assets in Twig (images, CSS, JS)
		$context['theme_uri'] = get_stylesheet_directory_uri();
		
		// Placeholder image when no image exists (change path in learnsimply_no_image_url())
		$context['no_image_url'] = learnsimply_no_image_url();
		
		// Cart page URL for JavaScript redirects
		$cart_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
		if ( $cart_page_id && $cart_page_id > 0 ) {
			$context['cart_url'] = get_permalink( $cart_page_id );
		} else {
			$context['cart_url'] = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart-1/';
		}
		
		// Cart items count for header badge
		if ( function_exists( 'WC' ) && WC()->cart ) {
			$context['cart_count'] = WC()->cart->get_cart_contents_count();
		} else {
			$context['cart_count'] = 0;
		}
		
		$context['is_front_page'] = is_front_page();
		$context['is_home'] = is_home();
		$context['is_user_logged_in'] = is_user_logged_in();
		$context['instructor_title'] = 'مهندس برمجيات';
		
		if ( is_user_logged_in() ) {
			$context['user'] = Timber::get_user( get_current_user_id() );
		}
		
		// Add main menu (Main Menu - ID: 28)
		$main_menu = Timber::get_menu( 28 );
		if ( $main_menu ) {
			$context['main_menu'] = $main_menu;
		}
		
		// Add courses archive URL
		if ( function_exists( 'tutor_utils' ) ) {
			$context['courses_archive_url'] = tutor_utils()->course_archive_page_url();
		} else {
			$context['courses_archive_url'] = home_url( '/courses/' );
		}
		
		// Add site icon (favicon) URL
		$site_icon_id = get_option( 'site_icon' );
		if ( $site_icon_id ) {
			$context['site_icon_url'] = wp_get_attachment_image_url( $site_icon_id, 'full' );
		} else {
			// Fallback to default favicon if no site icon is set
			$context['site_icon_url'] = get_site_icon_url();
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
add_filter( 'woocommerce_locate_template', 'edublink_child_override_shop_2_template', 10, 3 );

function edublink_child_override_shop_2_template( $template, $template_name, $template_path ) {
    // IMPORTANT: Exclude single product pages, cart, checkout FIRST
    if ( is_product() || is_cart() || is_checkout() || is_account_page() ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Check URL directly - most reliable method
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    if ( strpos( $request_uri, '/product/' ) !== false ||
         strpos( $request_uri, '/cart' ) !== false || 
         strpos( $request_uri, '/checkout' ) !== false || 
         strpos( $request_uri, '/my-account' ) !== false ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Check if this is archive-product.php and we're on shop-2
    // IMPORTANT: Only apply to shop archive pages, not cart, checkout, or other pages
    if ( $template_name === 'archive-product.php' ) {
        // Verify we're actually on a shop archive page
        if ( ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_post_type_archive( 'product' ) ) {
            return $template; // Not a shop archive, return original template
        }
        
        $woocommerce_shop_page_id = 0;
        if ( function_exists( 'wc_get_page_id' ) ) {
            $woocommerce_shop_page_id = wc_get_page_id( 'shop' );
        }
        
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
        $is_shop_2_url = ( strpos( $request_uri, '/shop-2' ) !== false );
        
        if ( $woocommerce_shop_page_id == 22662 || $is_shop_2_url ) {
            $shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';
            if ( file_exists( $shop_2_template ) ) {
                // Prevent Elementor
                add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
                add_filter( 'elementor/theme/get_location_templates', '__return_empty_array', 999 );
                add_filter( 'elementor/theme/get_location_template_id', '__return_false', 999 );
                add_filter( 'hfe_header_enabled', '__return_false' );
                add_filter( 'hfe_footer_enabled', '__return_false' );
                
                // Return shop-2 template instead
                return $shop_2_template;
            }
        }
    }
    
    return $template;
}

// Also use template_include as fallback
add_filter( 'template_include', 'edublink_child_force_custom_templates_via_filter', 999999 );

function edublink_child_force_custom_templates_via_filter( $template ) {
    // Check URL directly FIRST - most reliable method
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    
    // IMPORTANT: Exclude product pages, cart, checkout, my-account URLs FIRST
    if ( strpos( $request_uri, '/product/' ) !== false ||
         strpos( $request_uri, '/cart' ) !== false || 
         strpos( $request_uri, '/checkout' ) !== false || 
         strpos( $request_uri, '/my-account' ) !== false ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Check if we are on the front page or the specific page ID 9834 found in your HTML
    if ( is_front_page() || is_home() || get_the_ID() == 9834 ) {
        $custom_front = get_stylesheet_directory() . '/front-page.php';
        
        if ( file_exists( $custom_front ) ) {
            return $custom_front;
        }
    }
    
    // IMPORTANT: Exclude cart, checkout, my-account, single product pages, and other pages FIRST
    if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() || is_product() ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Exclude single posts, pages (except shop-2), and other archives
    if ( is_single() || ( is_page() && ! is_page( 'shop-2' ) && ! is_page( 22662 ) ) || ( is_archive() && ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_post_type_archive( 'product' ) ) ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Get current page ID and slug to exclude cart pages
    $current_page_id = get_queried_object_id();
    global $wp_query;
    $queried_object = get_queried_object();
    $current_page_slug = '';
    if ( $queried_object && isset( $queried_object->post_name ) ) {
        $current_page_slug = $queried_object->post_name;
    }
    
    // Exclude cart pages by ID or slug - MUST be checked BEFORE shop-2 check
    if ( $current_page_id == 21 || // cart-1 page ID
         $current_page_id == 21744 || // cart-3 page ID
         strpos( $current_page_slug, 'cart' ) === 0 || // starts with 'cart'
         strpos( $current_page_slug, 'checkout' ) === 0 ||
         strpos( $current_page_slug, 'my-account' ) === 0 ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Also check if we're on a regular page (not shop archive) - exclude all pages except shop-2
    if ( is_page() && ! is_page( 'shop-2' ) && ! is_page( 22662 ) && $current_page_id != 22662 ) {
        // This is a regular page, not shop-2, so don't apply shop-2 template
        return $template;
    }
    
    // Check URL directly - most reliable method
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    
    // Also check if URL contains cart, checkout, product (single product), etc. and exclude them BEFORE checking shop-2
    if ( strpos( $request_uri, '/cart' ) !== false || 
         strpos( $request_uri, '/checkout' ) !== false || 
         strpos( $request_uri, '/my-account' ) !== false ||
         strpos( $request_uri, '/product/' ) !== false ) {
        return $template; // Don't apply shop-2 template to these pages
    }
    
    // Check if this is shop-2 URL (only after excluding cart/checkout)
    $is_shop_2_url = ( strpos( $request_uri, '/shop-2' ) !== false || strpos( $request_uri, '/shop-2/' ) !== false );
    
    // Check if shop-2 is the WooCommerce shop page
    // IMPORTANT: Only apply to shop archive pages, not cart, checkout, or other pages
    $woocommerce_shop_page_id = 0;
    if ( function_exists( 'wc_get_page_id' ) ) {
        $woocommerce_shop_page_id = wc_get_page_id( 'shop' );
    }
    
    // Verify we're actually on a shop archive page (not cart, checkout, etc.)
    $is_shop_archive = is_shop() || is_product_category() || is_product_tag() || is_post_type_archive( 'product' );
    
    // Check if we're on shop-2 (either as shop page or regular page)
    // Only apply if we're on a shop archive page
    $is_shop_2 = false;
    if ( $is_shop_archive ) {
        if ( 
            $is_shop_2_url ||
            ( $woocommerce_shop_page_id == 22662 && ( is_shop() || is_post_type_archive( 'product' ) ) ) ||
            is_page( 'shop-2' ) || 
            is_page( 22662 ) || 
            get_queried_object_id() == 22662
        ) {
            $is_shop_2 = true;
        }
    }
    
    if ( $is_shop_2 ) {
        $shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';
        
        if ( file_exists( $shop_2_template ) ) {
            // Prevent Elementor from loading on this page
            add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
            add_filter( 'elementor/theme/get_location_templates', '__return_empty_array', 999 );
            add_filter( 'elementor/theme/get_location_template_id', '__return_false', 999 );
            add_filter( 'hfe_header_enabled', '__return_false' );
            add_filter( 'hfe_footer_enabled', '__return_false' );
            
            return $shop_2_template;
        }
    }
    
    return $template;
}

// Also keep template_redirect as backup
add_action( 'template_redirect', 'edublink_child_force_custom_templates', 0 );

function edublink_child_force_custom_templates() {
    // Check URL directly FIRST - most reliable method
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    
    // IMPORTANT: Exclude product pages, cart, checkout, my-account URLs FIRST
    if ( strpos( $request_uri, '/product/' ) !== false ||
         strpos( $request_uri, '/cart' ) !== false || 
         strpos( $request_uri, '/checkout' ) !== false || 
         strpos( $request_uri, '/my-account' ) !== false ) {
        return; // Don't apply shop-2 template to these pages
    }
    
    // IMPORTANT: Exclude cart, checkout, my-account, single product pages FIRST
    if ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() || is_product() ) {
        return; // Don't apply shop-2 template to these pages
    }
    
    // Exclude single posts, pages (except shop-2), and other archives
    if ( is_single() || ( is_page() && ! is_page( 'shop-2' ) && ! is_page( 22662 ) ) || ( is_archive() && ! is_shop() && ! is_product_category() && ! is_product_tag() && ! is_post_type_archive( 'product' ) ) ) {
        return; // Don't apply shop-2 template to these pages
    }
    
    // Get current page info
    global $wp_query;
    $queried_object_id = get_queried_object_id();
    $current_page_slug = get_query_var( 'pagename' );
    $queried_object = get_queried_object();
    $current_page_slug_from_object = '';
    if ( $queried_object && isset( $queried_object->post_name ) ) {
        $current_page_slug_from_object = $queried_object->post_name;
    }
    
    // Exclude cart pages by ID or slug
    if ( $queried_object_id == 21 || // cart-1 page ID
         $queried_object_id == 21744 || // cart-3 page ID
         $current_page_slug === 'cart-1' ||
         $current_page_slug === 'cart-3' ||
         ( $current_page_slug_from_object && strpos( $current_page_slug_from_object, 'cart' ) === 0 ) ) {
        return; // Don't apply shop-2 template to these pages
    }
    
    // Check URL directly first (most reliable method)
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    
    // Exclude cart, checkout, my-account, product (single product) URLs
    if ( strpos( $request_uri, '/cart' ) !== false || 
         strpos( $request_uri, '/checkout' ) !== false || 
         strpos( $request_uri, '/my-account' ) !== false ||
         strpos( $request_uri, '/product/' ) !== false ) {
        return; // Don't apply shop-2 template to these pages
    }
    
    $is_shop_2_url = ( strpos( $request_uri, '/shop-2' ) !== false );
    
    // Handle blog page separately (when is_home() is true for Posts page, or by URL)
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    
    // Check if this is the blog page by URL or WordPress conditions
    $is_blog_page = ( is_home() && ! is_front_page() ) 
        || preg_match( '#/blog/?(\?.*)?$#', $request_uri );
    
    if ( $is_blog_page ) {
        // This is the Posts page (blog archive)
        $blog_template = get_stylesheet_directory() . '/page-blog.php';
        
        if ( file_exists( $blog_template ) ) {
            include( $blog_template );
            exit;
        }
    }
    
    // Check if we are on the front page or the specific page ID 9834 found in your HTML
    // Note: is_home() removed - we handle it above for the blog page
    if ( is_front_page() || get_the_ID() == 9834 ) {
        $custom_front = get_stylesheet_directory() . '/front-page.php';
        
        if ( file_exists( $custom_front ) ) {
            include( $custom_front );
            exit;
        }
    }
    
    // Force load shop-2 template for shop-2 page
    // Check if shop-2 is set as WooCommerce shop page OR if it's a regular page
    // Check if shop-2 is the WooCommerce shop page
    $woocommerce_shop_page_id = 0;
    if ( function_exists( 'wc_get_page_id' ) ) {
        $woocommerce_shop_page_id = wc_get_page_id( 'shop' );
    }
    
    // Check multiple ways to identify shop-2 page
    $is_shop_2 = false;
    if ( 
        $is_shop_2_url ||
        is_page( 'shop-2' ) || 
        is_page( 22662 ) || 
        $queried_object_id == 22662 ||
        $woocommerce_shop_page_id == 22662 ||
        $current_page_slug === 'shop-2' ||
        ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->post_name ) && $wp_query->queried_object->post_name === 'shop-2' ) ||
        ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->ID ) && $wp_query->queried_object->ID == 22662 )
    ) {
        $is_shop_2 = true;
    }
    
    if ( $is_shop_2 ) {
        $shop_2_template = get_stylesheet_directory() . '/page-shop-2.php';
        
        if ( file_exists( $shop_2_template ) ) {
            // Prevent Elementor from loading on this page - do this BEFORE including template
            add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
            add_filter( 'elementor/theme/get_location_templates', '__return_empty_array', 999 );
            add_filter( 'elementor/theme/get_location_template_id', '__return_false', 999 );
            add_filter( 'hfe_header_enabled', '__return_false' );
            add_filter( 'hfe_footer_enabled', '__return_false' );
            
            // Override WooCommerce archive template
            add_filter( 'woocommerce_is_shop', '__return_false', 999 );
            
            include( $shop_2_template );
            exit;
        }
    }
}

/**
 * Also use template_include as fallback
 */
add_filter( 'template_include', 'edublink_child_force_front_page_template', 999999 );

function edublink_child_force_front_page_template( $template ) {
    // This is a fallback - template_redirect should handle it first
    return $template;
}

/**
 * Force blog page to use page-blog.php template
 */
add_filter( 'template_include', 'edublink_child_force_blog_template', 999998 );

function edublink_child_force_blog_template( $template ) {
    // Check if this is the blog page
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? $_SERVER['REQUEST_URI'] : '';
    $is_blog_page = is_page( 'blog' ) || preg_match( '#/blog/?(\?.*)?$#', $request_uri );
    
    if ( $is_blog_page ) {
        $blog_template = get_stylesheet_directory() . '/page-blog.php';
        if ( file_exists( $blog_template ) ) {
            return $blog_template;
        }
    }
    return $template;
}

/**
 * 2. Completely Dequeue Elementor Styles & Scripts on Front Page
 * Updated with specific IDs found in your guest HTML analysis
 */
add_action( 'wp_enqueue_scripts', 'edublink_child_unload_elementor_assets', 99999 );

function edublink_child_unload_elementor_assets() {
    // Check if we're on front page, home, or courses archive
    $is_courses_archive = false;
    if ( function_exists( 'tutor_utils' ) ) {
        $course_post_type = tutor()->course_post_type;
        $is_courses_archive = is_post_type_archive( $course_post_type ) || is_tax( 'course-category' ) || is_tax( 'course-tag' );
    }
    
    // Check if we're on shop-2 page - multiple methods
    global $wp_query;
    $queried_object_id = get_queried_object_id();
    $current_page_slug = get_query_var( 'pagename' );
    $is_shop_2 = false;
    
    if ( 
        is_page( 'shop-2' ) || 
        is_page( 22662 ) || 
        $queried_object_id == 22662 ||
        $current_page_slug === 'shop-2' ||
        ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->post_name ) && $wp_query->queried_object->post_name === 'shop-2' ) ||
        ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->ID ) && $wp_query->queried_object->ID == 22662 )
    ) {
        $is_shop_2 = true;
    }
    
    if ( is_front_page() || is_home() || get_the_ID() == 9834 || $is_courses_archive || $is_shop_2 ) {
        
        // Remove Elementor Core
        wp_dequeue_script( 'elementor-frontend' );
        wp_dequeue_style( 'elementor-frontend' );
        wp_dequeue_style( 'elementor-icons' );
        wp_dequeue_style( 'elementor-global' );
        
        // Remove Specific Elementor Files found in Guest View
        wp_dequeue_style( 'elementor-post-24541' ); // Global Kit
        wp_dequeue_style( 'elementor-post-9834' );  // Specific Page Style
        
        // Remove Elementor Pro
        wp_dequeue_script( 'elementor-pro-frontend' );
        wp_dequeue_style( 'elementor-pro' );
        
        // Remove Header Footer Elementor (HFE)
        wp_dequeue_script( 'hfe-frontend-js' );
        wp_dequeue_style( 'hfe-style' );
        wp_dequeue_style( 'hfe-widgets-style' );
        
        // Remove EduBlink Theme specific Elementor styles
        // NOTE: Only remove edublink-style if it's loaded by Elementor, not the parent theme's main style
        wp_dequeue_style( 'edublink-elementor' );
        // Don't remove edublink-style here - it's needed for other pages like cart, checkout, etc.
        // wp_dequeue_style( 'edublink-style' ); 
        
        // Remove Google Fonts loaded by Elementor
        add_filter( 'elementor/frontend/print_google_fonts', '__return_false' );
    }
}

/**
 * 3. Force Disable Cache Headers for Front Page
 * This attempts to tell browsers and proxies NOT to cache the homepage
 */
add_action( 'send_headers', 'edublink_child_prevent_caching_front_page' );
function edublink_child_prevent_caching_front_page() {
    if ( is_front_page() || is_home() || get_the_ID() == 9834 ) {
        header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0' );
        header( 'Cache-Control: post-check=0, pre-check=0', false );
        header( 'Pragma: no-cache' );
    }
}

/**
 * 4. Clean Body Classes
 * Removes 'elementor-page' classes to prevent any residual CSS from applying
 */
add_filter( 'body_class', 'edublink_child_clean_body_classes', 999 );
function edublink_child_clean_body_classes( $classes ) {
    $is_shop_2 = is_page( 'shop-2' ) || is_page( 22662 ) || get_queried_object_id() == 22662;
    
    if ( is_front_page() || is_home() || get_the_ID() == 9834 || $is_shop_2 ) {
        $remove_classes = array( 
            'elementor-default', 
            'elementor-kit-24541', 
            'elementor-page', 
            'elementor-page-9834',
            'elementor-page-22662'
        );
        $classes = array_diff( $classes, $remove_classes );
    }
    return $classes;
}

/**
 * 5. Disable Elementor Locations Logic
 */
add_action( 'wp', 'edublink_child_disable_elementor_locations', 0 );

function edublink_child_disable_elementor_locations() {
    // Check if we're on front page, home, or courses archive
    $is_courses_archive = false;
    if ( function_exists( 'tutor_utils' ) ) {
        $course_post_type = tutor()->course_post_type;
        $is_courses_archive = is_post_type_archive( $course_post_type ) || is_tax( 'course-category' ) || is_tax( 'course-tag' );
    }
    
    // Check if we're on shop-2 page - multiple methods
    global $wp_query;
    $queried_object_id = get_queried_object_id();
    $current_page_slug = get_query_var( 'pagename' );
    $is_shop_2 = false;
    
    if ( 
        is_page( 'shop-2' ) || 
        is_page( 22662 ) || 
        $queried_object_id == 22662 ||
        $current_page_slug === 'shop-2' ||
        ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->post_name ) && $wp_query->queried_object->post_name === 'shop-2' ) ||
        ( isset( $wp_query->queried_object ) && isset( $wp_query->queried_object->ID ) && $wp_query->queried_object->ID == 22662 )
    ) {
        $is_shop_2 = true;
    }
    
    if ( is_front_page() || is_home() || get_the_ID() == 9834 || $is_courses_archive || $is_shop_2 ) {
        // Stop Elementor Theme Builder
        add_filter( 'elementor/theme/get_location_templates', '__return_empty_array', 999 );
        add_filter( 'elementor/theme/get_location_template_id', '__return_false', 999 );
        
        // Stop Header Footer Elementor Plugin
        add_filter( 'hfe_header_enabled', '__return_false' );
        add_filter( 'hfe_footer_enabled', '__return_false' );
        add_filter( 'enable_hfe_render_header', '__return_false' );
        add_filter( 'enable_hfe_render_footer', '__return_false' );
        
        // Remove Theme Hooks
        remove_all_actions( 'edublink_header' ); 
        remove_all_actions( 'edublink_footer' );
    }
}

/* ==========================================================================
   OTHER ASSETS & WOOCOMMERCE
   ========================================================================== */

/**
 * Enqueue parent and child theme styles
 */
function edublink_child_enqueue_styles() {
	wp_enqueue_style( 'edublink-parent-style', get_template_directory_uri() . '/style.css', array(), '2.0.8' );
	wp_enqueue_style( 'edublink-child-style', get_stylesheet_directory_uri() . '/style.css', array( 'edublink-parent-style' ), wp_get_theme()->get( 'Version' ) );
	
    // Custom logic for products
	if ( is_product() ) {
		wp_enqueue_style( 'edublink-custom-product-style', get_stylesheet_directory_uri() . '/custom_product.css', array( 'edublink-child-style' ), wp_get_theme()->get( 'Version' ) );
	}
    // Custom logic for archives
	if ( is_shop() || is_product_category() || is_product_tag() ) {
		wp_enqueue_style( 'edublink-custom-product-archive-style', get_stylesheet_directory_uri() . '/custom_product_archive.css', array( 'edublink-child-style' ), wp_get_theme()->get( 'Version' ) );
	}
    // Custom logic for Tutor LMS
	if ( function_exists( 'tutor_utils' ) ) {
		$course_post_type = tutor()->course_post_type;
		if ( is_singular( $course_post_type ) || get_post_type() === $course_post_type ) {
			wp_enqueue_style( 'edublink-custom-course-style', get_stylesheet_directory_uri() . '/custom_course.css', array( 'edublink-child-style' ), wp_get_theme()->get( 'Version' ) );
		}
		if ( is_post_type_archive( $course_post_type ) || is_tax( 'course-category' ) || is_tax( 'course-tag' ) ) {
			// At this stage we want to preserve the original theme design for course archives
			// So we do not remove Tutor LMS or theme styles, and only load our additional file if needed later
			wp_enqueue_style(
				'edublink-custom-course-archive-style',
				get_stylesheet_directory_uri() . '/custom_course_archive.css',
				array( 'edublink-child-style' ),
				wp_get_theme()->get( 'Version' )
			);
			// Note: wp_dequeue_style( 'tutor-frontend' ) and wp_dequeue_style( 'tutor' ) were removed to preserve the original design
		}
	}
}
add_action( 'wp_enqueue_scripts', 'edublink_child_enqueue_styles', 99 );

/**
 * Enqueue global assets
 */
function edublink_child_enqueue_global_assets() {
	$global_css = get_stylesheet_directory() . '/assets/global/styles.css';
	$global_js = get_stylesheet_directory() . '/assets/global/script.js';
	
	if ( file_exists( $global_css ) ) {
		wp_enqueue_style( 'edublink-global-styles', get_stylesheet_directory_uri() . '/assets/global/styles.css', array( 'edublink-child-style' ), filemtime( $global_css ) );
	}
	if ( file_exists( $global_js ) ) {
		wp_enqueue_script( 'edublink-global-scripts', get_stylesheet_directory_uri() . '/assets/global/script.js', array( 'jquery' ), filemtime( $global_js ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'edublink_child_enqueue_global_assets', 100 );

/**
 * Ensure WooCommerce scripts are loaded for AJAX add to cart
 */
function edublink_child_enqueue_woocommerce_scripts() {
	if ( class_exists( 'WooCommerce' ) && is_front_page() ) {
		// Ensure WooCommerce add to cart script is loaded
		if ( ! wp_script_is( 'wc-add-to-cart', 'enqueued' ) ) {
			wp_enqueue_script( 'wc-add-to-cart' );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'edublink_child_enqueue_woocommerce_scripts', 101 );

/**
 * Dynamic Assets Loader
 */
function edublink_child_load_page_assets() {
	$assets_dir = get_stylesheet_directory() . '/assets';
	$assets_uri = get_stylesheet_directory_uri() . '/assets';
	$page_type = '';
	
	if ( is_404() ) $page_type = '404';
	elseif ( is_front_page() ) $page_type = 'home';
	elseif ( is_page( 'about_me' ) || is_page_template( 'page-about_me.php' ) ) $page_type = 'about-me';
	elseif ( is_page( 'dashboard' ) ) $page_type = 'dashboard';
	elseif ( is_page( 'signup' ) ) $page_type = 'signup';
	elseif ( is_product() ) {
		// Check if product has bundles
		global $wpdb;
		$product_id = get_the_ID();
		$has_bundles = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}asnp_wepb_simple_bundle_items WHERE bundle_id = %d",
			$product_id
		) );
		$page_type = ( $has_bundles > 0 ) ? 'single-product-bundle' : 'single-product';
	}
	elseif ( is_shop() || is_product_category() || is_product_tag() ) $page_type = 'product_archive';
	elseif ( is_cart() ) $page_type = 'cart';
	elseif ( is_checkout() ) $page_type = 'checkout';
	elseif ( function_exists( 'tutor_utils' ) ) {
		$course_post_type = tutor()->course_post_type;
		if ( is_singular( $course_post_type ) ) $page_type = 'single_course';
		elseif ( is_post_type_archive( $course_post_type ) || is_tax( 'course-category' ) ) $page_type = 'course_archive';
	}
	
	if ( empty( $page_type ) ) {
		$template = get_page_template_slug();
		if ( ! empty( $template ) ) $page_type = str_replace( array( '.php', '-', '/' ), array( '', '_', '_' ), basename( $template ) );
		// Also check page slug
		if ( empty( $page_type ) && is_page() ) {
			$page_slug = get_post_field( 'post_name', get_the_ID() );
			if ( $page_slug === 'about_me' ) $page_type = 'about-me';
			elseif ( $page_slug === 'dashboard' ) $page_type = 'dashboard';
			elseif ( $page_slug === 'signup' ) $page_type = 'signup';
		}
		// Check for Tutor LMS dashboard (via query vars)
		if ( empty( $page_type ) && function_exists( 'tutor_utils' ) ) {
			global $wp_query;
			if ( isset( $wp_query->query_vars['tutor_dashboard_page'] ) || isset( $wp_query->query_vars['tutor_dashboard'] ) ) {
				$page_type = 'dashboard';
			}
		}
		// Check URL path for dashboard or signup
		if ( empty( $page_type ) ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			if ( ! empty( $request_uri ) ) {
				if ( strpos( $request_uri, '/dashboard/' ) !== false || strpos( $request_uri, '/dashboard' ) !== false ) {
					$page_type = 'dashboard';
				} elseif ( strpos( $request_uri, '/signup/' ) !== false || strpos( $request_uri, '/signup' ) !== false ) {
					$page_type = 'signup';
				}
			}
		}
	}
	
	if ( ! empty( $page_type ) && is_dir( $assets_dir . '/' . $page_type ) ) {
		$css_file = $assets_dir . '/' . $page_type . '/style.css';
		$js_file = $assets_dir . '/' . $page_type . '/script.js';
		
		if ( file_exists( $css_file ) ) {
			// Load with high priority and no dependencies to ensure it loads last and can override everything
			// Using empty array for dependencies ensures it loads after all other styles
			wp_enqueue_style( 'edublink-' . $page_type . '-style', $assets_uri . '/' . $page_type . '/style.css', array(), filemtime( $css_file ) );
		}
		if ( file_exists( $js_file ) ) {
			wp_enqueue_script( 'edublink-' . $page_type . '-script', $assets_uri . '/' . $page_type . '/script.js', array( 'jquery' ), filemtime( $js_file ), true );
		}
	}
}
add_action( 'wp_enqueue_scripts', 'edublink_child_load_page_assets', 999 );

/**
 * Add page-specific CSS after all other styles (including Elementor)
 * This ensures our custom CSS can override everything
 */
function edublink_child_add_page_css_late() {
	$assets_dir = get_stylesheet_directory() . '/assets';
	$assets_uri = get_stylesheet_directory_uri() . '/assets';
	$page_type = '';
	
	// Re-detect page type (same logic as edublink_child_load_page_assets)
	if ( is_404() ) $page_type = '404';
	elseif ( is_front_page() ) $page_type = 'home';
	elseif ( is_page( 'about_me' ) || is_page_template( 'page-about_me.php' ) ) $page_type = 'about-me';
	elseif ( is_page( 'dashboard' ) ) $page_type = 'dashboard';
	elseif ( is_page( 'signup' ) ) $page_type = 'signup';
	elseif ( is_product() ) {
		global $wpdb;
		$product_id = get_the_ID();
		$has_bundles = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}asnp_wepb_simple_bundle_items WHERE bundle_id = %d",
			$product_id
		) );
		$page_type = ( $has_bundles > 0 ) ? 'single-product-bundle' : 'single-product';
	}
	elseif ( is_shop() || is_product_category() || is_product_tag() ) $page_type = 'product_archive';
	elseif ( is_cart() ) $page_type = 'cart';
	elseif ( is_checkout() ) $page_type = 'checkout';
	elseif ( function_exists( 'tutor_utils' ) ) {
		$course_post_type = tutor()->course_post_type;
		if ( is_singular( $course_post_type ) ) $page_type = 'single_course';
		elseif ( is_post_type_archive( $course_post_type ) || is_tax( 'course-category' ) ) $page_type = 'course_archive';
	}
	
	if ( empty( $page_type ) ) {
		$template = get_page_template_slug();
		if ( ! empty( $template ) ) $page_type = str_replace( array( '.php', '-', '/' ), array( '', '_', '_' ), basename( $template ) );
		if ( empty( $page_type ) && is_page() ) {
			$page_slug = get_post_field( 'post_name', get_the_ID() );
			if ( $page_slug === 'about_me' ) $page_type = 'about-me';
			elseif ( $page_slug === 'dashboard' ) $page_type = 'dashboard';
			elseif ( $page_slug === 'signup' ) $page_type = 'signup';
		}
		if ( empty( $page_type ) && function_exists( 'tutor_utils' ) ) {
			global $wp_query;
			if ( isset( $wp_query->query_vars['tutor_dashboard_page'] ) || isset( $wp_query->query_vars['tutor_dashboard'] ) ) {
				$page_type = 'dashboard';
			}
		}
		if ( empty( $page_type ) ) {
			$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
			if ( ! empty( $request_uri ) ) {
				if ( strpos( $request_uri, '/dashboard/' ) !== false || strpos( $request_uri, '/dashboard' ) !== false ) {
					$page_type = 'dashboard';
				} elseif ( strpos( $request_uri, '/signup/' ) !== false || strpos( $request_uri, '/signup' ) !== false ) {
					$page_type = 'signup';
				}
			}
		}
	}
	
	// 1) Add CSS via wp_head to ensure it loads after all other styles (including Elementor)
	//    BUT only for pages that really needed hard overrides (dashboard + signup).
	if ( in_array( $page_type, array( 'dashboard', 'signup' ), true ) && is_dir( $assets_dir . '/' . $page_type ) ) {
		$css_file = $assets_dir . '/' . $page_type . '/style.css';
		if ( file_exists( $css_file ) ) {
			echo '<link rel="stylesheet" id="edublink-' . esc_attr( $page_type ) . '-style-late" href="' . esc_url( $assets_uri . '/' . $page_type . '/style.css?v=' . filemtime( $css_file ) ) . '" type="text/css" media="all" />' . "\n";
		}
	}

	// 2) Always load header/footer root protection last, on all pages.
	$hf_css = $assets_dir . '/header-footer-root.css';
	if ( file_exists( $hf_css ) ) {
		echo '<link rel="stylesheet" id="edublink-header-footer-root-style" href="' . esc_url( $assets_uri . '/header-footer-root.css?v=' . filemtime( $hf_css ) ) . '" type="text/css" media="all" />' . "\n";
	}
}
add_action( 'wp_head', 'edublink_child_add_page_css_late', 9999 );

/**
 * Load global custom override CSS file (highest priority - loads last)
 * This file can override any CSS on any page across the entire site
 */
function edublink_child_load_global_override_css() {
	$override_css = get_stylesheet_directory() . '/assets/global/custom-override.css';
	$override_css_uri = get_stylesheet_directory_uri() . '/assets/global/custom-override.css';
	
	if ( file_exists( $override_css ) ) {
		// Load with priority 10000 (higher than header-footer-root.css) to be the absolute last CSS file
		echo '<link rel="stylesheet" id="edublink-global-override-style" href="' . esc_url( $override_css_uri . '?v=' . filemtime( $override_css ) ) . '" type="text/css" media="all" />' . "\n";
	}
}
add_action( 'wp_head', 'edublink_child_load_global_override_css', 10000 );

/**
 * Remove WooCommerce CSS
 */
function edublink_child_remove_woocommerce_styles() {
	if ( is_product() || is_shop() || is_product_category() || is_product_tag() ) {
		wp_dequeue_style( 'woocommerce-general' );
		wp_dequeue_style( 'woocommerce-layout' );
		wp_dequeue_style( 'woocommerce-smallscreen' );
		wp_dequeue_style( 'edublink-woocommerce' );
	}
}
add_action( 'wp_enqueue_scripts', 'edublink_child_remove_woocommerce_styles', 999 );

/**
 * Override WooCommerce Templates
 */
function edublink_child_override_woocommerce_templates( $template, $template_name, $args = array(), $template_path = '', $default_path = '' ) {
	if ( 'cart/cart.php' === $template_name ) $child_template = get_stylesheet_directory() . '/woocommerce/cart/cart.php';
	elseif ( 'content-single-product.php' === $template_name ) $child_template = get_stylesheet_directory() . '/woocommerce/content-single-product.php';
	elseif ( 'single-product/tabs/tabs.php' === $template_name ) $child_template = get_stylesheet_directory() . '/woocommerce/single-product/tabs/tabs.php';
	
	if ( isset($child_template) && file_exists( $child_template ) ) return $child_template;
	return $template;
}
add_filter( 'wc_get_template', 'edublink_child_override_woocommerce_templates', 5, 5 );
add_filter( 'woocommerce_locate_template', 'edublink_child_override_woocommerce_templates', 1, 4 );

/**
 * Override Course Archive
 */
function edublink_child_override_course_archive_template( $template ) {
	if ( function_exists( 'tutor_utils' ) ) {
		$course_post_type = tutor()->course_post_type;
		$post_type = get_query_var( 'post_type' );
		$course_category = get_query_var( 'course-category' );
		
		if ( ( is_post_type_archive( $course_post_type ) || ( ! empty( $post_type ) && in_array( $course_post_type, (array) $post_type, true ) ) || ! empty( $course_category ) ) && is_archive() ) {
			$child_template = get_stylesheet_directory() . '/archive-courses.php';
			if ( file_exists( $child_template ) ) return $child_template;
		}
	}
	return $template;
}
add_filter( 'template_include', 'edublink_child_override_course_archive_template', 999 );

/**
 * Disable Elementor Product Templates
 */
function edublink_child_disable_elementor_product_mods() {
	if ( is_product() ) {
		if ( class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
			add_filter( 'elementor/theme/get_location_templates', '__return_empty_array', 999 );
			add_filter( 'elementor/theme/get_location_template_id', '__return_false', 999 );
		}
        add_filter( 'wpr_theme_builder_template_id', '__return_false', 999 );
        add_filter( 'wpr_theme_builder_should_render', '__return_false', 999 );
	}
}
add_action( 'template_redirect', 'edublink_child_disable_elementor_product_mods', 1 );

/**
 * Remove PROMO BAR
 */
function edublink_child_remove_promo_bar_enhanced() {
	if ( ! is_product() ) return;
	?>
	<style id="edublink-remove-promo-bar">
		#promo-bar, .promo-bar, [id*="promo"], [class*="promo-bar"], .promo-inner, .promo-left, .promo-timer, .promo-btn { display: none !important; visibility: hidden !important; height: 0 !important; overflow: hidden !important; }
	</style>
	<script>
	(function() {
		function removePromoBar() {
			const selectors = ['#promo-bar', '.promo-bar', '[id*="promo"]', '[class*="promo-bar"]'];
			selectors.forEach(function(s) { document.querySelectorAll(s).forEach(el => el.remove()); });
		}
		if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', removePromoBar);
		else removePromoBar();
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'edublink_child_remove_promo_bar_enhanced', 999 );

/* ==========================================================================
   BOOK PRODUCT METABOX
   ========================================================================== */

/**
 * Add custom metabox for book products
 */
function edublink_child_add_book_metabox() {
	add_meta_box(
		'edublink_book_details',
		'تفاصيل الكتاب',
		'edublink_child_book_metabox_callback',
		'product',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'edublink_child_add_book_metabox' );

/**
 * Metabox callback function
 */
function edublink_child_book_metabox_callback( $post ) {
	// Add nonce for security
	wp_nonce_field( 'edublink_book_metabox_nonce', 'edublink_book_metabox_nonce' );
	
	// Get current values
	$book_pages = get_post_meta( $post->ID, '_book_pages', true );
	$book_available_count = get_post_meta( $post->ID, '_book_available_count', true );
	
	?>
	<div class="edublink-book-metabox" style="padding: 10px 0;">
		<p>
			<label for="book_pages" style="display: block; margin-bottom: 5px; font-weight: 600;">
				عدد الصفحات:
			</label>
			<input 
				type="number" 
				id="book_pages" 
				name="book_pages" 
				value="<?php echo esc_attr( $book_pages ); ?>" 
				placeholder="مثال: 260"
				style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
				min="0"
			/>
			<span style="display: block; margin-top: 5px; color: #666; font-size: 12px;">
				أدخل عدد صفحات الكتاب
			</span>
		</p>
		
		<p>
			<label for="book_available_count" style="display: block; margin-bottom: 5px; font-weight: 600;">
				العدد المتوفر:
			</label>
			<input 
				type="number" 
				id="book_available_count" 
				name="book_available_count" 
				value="<?php echo esc_attr( $book_available_count ); ?>" 
				placeholder="مثال: 40"
				style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;"
				min="0"
			/>
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
function edublink_child_save_book_metabox( $post_id ) {
	// Check if nonce is set
	if ( ! isset( $_POST['edublink_book_metabox_nonce'] ) ) {
		return;
	}
	
	// Verify nonce
	if ( ! wp_verify_nonce( $_POST['edublink_book_metabox_nonce'], 'edublink_book_metabox_nonce' ) ) {
		return;
	}
	
	// Check if this is an autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	
	// Check user permissions
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	
	// Check if this is a product
	if ( get_post_type( $post_id ) !== 'product' ) {
		return;
	}
	
	// Save book pages
	if ( isset( $_POST['book_pages'] ) ) {
		$book_pages = sanitize_text_field( $_POST['book_pages'] );
		update_post_meta( $post_id, '_book_pages', $book_pages );
	} else {
		delete_post_meta( $post_id, '_book_pages' );
	}
	
	// Save book available count
	if ( isset( $_POST['book_available_count'] ) ) {
		$book_available_count = sanitize_text_field( $_POST['book_available_count'] );
		update_post_meta( $post_id, '_book_available_count', $book_available_count );
	} else {
		delete_post_meta( $post_id, '_book_available_count' );
	}
}
add_action( 'save_post', 'edublink_child_save_book_metabox' );
add_action( 'woocommerce_process_product_meta', 'edublink_child_save_book_metabox' );

/**
 * Simple redirect to cart after add to cart
 * Only use woocommerce_add_to_cart_redirect filter - the standard WooCommerce way
 */
add_filter( 'woocommerce_add_to_cart_redirect', 'edublink_child_redirect_to_cart_after_add', 10 );
function edublink_child_redirect_to_cart_after_add( $url ) {
    // Redirect to cart page after adding product
    return wc_get_cart_url();
}
