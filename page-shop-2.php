<?php
/**
 * Template Name: Shop 2
 * 
 * Custom shop page template that displays products in card format
 * Similar to the books section design
 * 
 * @package EduBlink_Child
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Timber is available
if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

// Get Timber context
$context = Timber::get_context();

// Add theme directory URI to context
$context['theme_uri'] = get_stylesheet_directory_uri();

// Get current page
$context['post'] = Timber::get_post();

// Get filter parameters from URL
$product_cat = isset( $_GET['product_cat'] ) ? sanitize_text_field( $_GET['product_cat'] ) : '';
$search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );

// Get products
$context['products'] = array();
if ( class_exists( 'WooCommerce' ) ) {
	// Build query args
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 12,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'paged'          => $paged,
	);
	
	// Add search filter if specified
	if ( ! empty( $search_term ) ) {
		$args['s'] = $search_term;
	}
	
	// Add category filter if specified
	if ( ! empty( $product_cat ) ) {
		// Bundle products use product_type taxonomy, not product_cat
		if ( $product_cat === 'bundle' ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_type',
					'field'    => 'slug',
					'terms'    => 'easy_product_bundle',
				),
			);
		} else {
			$args['tax_query'] = array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => $product_cat,
				),
			);
		}
	}
	
	$products_query = new WP_Query( $args );
	
	if ( $products_query->have_posts() ) {
		while ( $products_query->have_posts() ) {
			$products_query->the_post();
			$product_id = get_the_ID();
			$product = wc_get_product( $product_id );
			
			if ( $product && $product->is_visible() ) {
				// Get product data similar to books section
				$product_data = array(
					'id' => $product_id,
					'title' => $product->get_name(),
					'link' => get_permalink( $product_id ),
					'product_url' => get_permalink( $product_id ),
					'thumbnail' => get_the_post_thumbnail_url( $product_id, 'full' ) ?: learnsimply_no_image_url(),
				);
				
				// Get prices
				$regular_price = $product->get_regular_price();
				$sale_price = $product->get_sale_price();
				$price = $product->get_price();
				
				$product_data['regular_price'] = $regular_price ? floatval( $regular_price ) : null;
				$product_data['sale_price'] = $sale_price ? floatval( $sale_price ) : null;
				$product_data['price'] = $price ? floatval( $price ) : 0;
				
				// Calculate discount percentage
				if ( $product_data['sale_price'] && $product_data['regular_price'] && $product_data['regular_price'] > 0 ) {
					$product_data['discount_percent'] = round( ( ( $product_data['regular_price'] - $product_data['sale_price'] ) / $product_data['regular_price'] ) * 100 );
				} else {
					$product_data['discount_percent'] = 0;
				}
				
				// Check if product is free
				$product_data['is_free'] = ( ! $product_data['regular_price'] && ! $product_data['sale_price'] ) || $product_data['price'] == 0;
				
				// Get product rating
				$average_rating = $product->get_average_rating();
				$rating_count = $product->get_rating_count();
				$product_data['rating_avg'] = $average_rating ? number_format( $average_rating, 1 ) : 0;
				$product_data['rating_count'] = $rating_count;
				
				// Get stock information
				$product_data['stock_status'] = $product->get_stock_status();
				$product_data['stock_quantity'] = $product->get_stock_quantity();
				
				// Get book-specific meta if it's a book
				if ( $product_cat === 'book' ) {
					$product_data['pages'] = get_post_meta( $product_id, '_book_pages', true );
					$book_available_count = get_post_meta( $product_id, '_book_available_count', true );
					if ( $book_available_count ) {
						$product_data['stock_quantity'] = intval( $book_available_count );
					}
				}
				
				// Get bundle-specific data if it's a bundle
				if ( $product_cat === 'bundle' && $product->is_type( 'easy_product_bundle' ) ) {
					global $wpdb;
					$table_name = $wpdb->prefix . 'asnp_wepb_simple_bundle_items';
					$bundle_items_count = $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$table_name} WHERE bundle_id = %d",
						$product_id
					) );
					$product_data['bundle_items_count'] = intval( $bundle_items_count );
					$product_data['is_bundle'] = true;
				}
				
				// Get product author (if exists)
				$author_id = get_post_meta( $product_id, '_product_author', true );
				if ( $author_id ) {
					$product_data['author'] = Timber::get_user( $author_id );
				} else {
					$product_data['author'] = null;
				}
				
				$context['products'][] = $product_data;
			}
		}
		wp_reset_postdata();
		
		// Pagination
		$context['pagination'] = array(
			'total' => $products_query->max_num_pages,
			'current' => $paged,
		);
	}
}

// Set page title based on category and search
if ( ! empty( $search_term ) ) {
	$context['page_title'] = 'نتائج البحث';
	$context['page_description'] = 'نتائج البحث عن: ' . esc_html( $search_term );
} elseif ( $product_cat === 'book' ) {
	$context['page_title'] = 'الكتب الموجودة';
	$context['page_description'] = 'كتب تعليمية تساعدك تفهم البرمجة وتطبّق المفاهيم خطوة بخطوة.';
} elseif ( $product_cat === 'bundle' ) {
	$context['page_title'] = 'الباقات المميزة';
	$context['page_description'] = 'باقات متكاملة تجمع عدة دورات معاً بأسعار مميزة.';
} else {
	$context['page_title'] = 'المتجر';
	$context['page_description'] = 'جميع المنتجات المتاحة';
}

$context['product_cat'] = $product_cat;
$context['search_term'] = $search_term;

// Render Twig template
Timber::render( 'page-shop-2.twig', $context );

