<?php
/**
 * Template Name: Front Page
 * 
 * Front page template for the home page
 * Uses Timber Twig template
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

// Add cart URL for JavaScript redirects
$cart_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
if ( $cart_page_id && $cart_page_id > 0 ) {
	$context['cart_url'] = get_permalink( $cart_page_id );
} else {
	$context['cart_url'] = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart-1/';
}

// Get featured courses from Tutor LMS
$context['courses'] = array();
if ( function_exists( 'tutor_utils' ) ) {
	$course_post_type = tutor()->course_post_type;
	
	// Get featured courses (limit to 6)
	$args = array(
		'post_type'      => $course_post_type,
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
	);
	
	$courses_query = new WP_Query( $args );
	
	if ( $courses_query->have_posts() ) {
		while ( $courses_query->have_posts() ) {
			$courses_query->the_post();
			$course_id = get_the_ID();
			
			// Get course data using Timber::get_post()
			$course = Timber::get_post( $course_id );
			
			if ( $course ) {
				// Get course rating
				$course_rating = tutor_utils()->get_course_rating( $course_id );
				$course->rating_avg = $course_rating ? number_format( $course_rating->rating_avg, 1 ) : 0;
				$course->rating_count = $course_rating ? $course_rating->rating_count : 0;
				
				// Get course price (raw prices for proper formatting)
				$price_info = tutor_utils()->get_raw_course_price( $course_id );
				$course->regular_price = $price_info->regular_price ? floatval( $price_info->regular_price ) : null;
				$course->sale_price = $price_info->sale_price ? floatval( $price_info->sale_price ) : null;
				$course->price = $price_info->sale_price ? floatval( $price_info->sale_price ) : ( $price_info->regular_price ? floatval( $price_info->regular_price ) : 0 );
				
				// Calculate discount percentage
				if ( $course->sale_price && $course->regular_price && $course->regular_price > 0 ) {
					$course->discount_percent = round( ( ( $course->regular_price - $course->sale_price ) / $course->regular_price ) * 100 );
				} else {
					$course->discount_percent = 0;
				}
				
				// Check if course is free
				$price_type = tutor_utils()->price_type( $course_id );
				$course->is_free = ( $price_type === 'free' || ( ! $course->regular_price && ! $course->sale_price ) );
				
				// Get course duration
				$course->duration = get_tutor_course_duration_context( $course_id );
				
				// Get lesson count
				$course->lesson_count = tutor_utils()->get_lesson_count_by_course( $course_id );
				
				// Get students count
				$course->students_count = tutor_utils()->count_enrolled_users_by_course( $course_id );
				
				// Get instructors
				$instructors = tutor_utils()->get_instructors_by_course( $course_id );
				if ( ! empty( $instructors ) && isset( $instructors[0]->ID ) ) {
					$course->instructor = Timber::get_user( $instructors[0]->ID );
				} else {
					$course->instructor = null;
				}
				
				// Get course level
				$course->level = get_post_meta( $course_id, '_tutor_course_level', true );
				if ( empty( $course->level ) ) {
					$course->level = 'مبتدئ';
				}
				
				// Get course image
				$course->thumbnail = get_the_post_thumbnail_url( $course_id, 'full' );
				if ( ! $course->thumbnail ) {
					$course->thumbnail = tutor()->url . 'assets/images/placeholder-course.jpg';
				}
				
				// Get WooCommerce product ID if course is monetized by WooCommerce
				$monetize_by = tutor_utils()->get_option( 'monetize_by' );
				if ( $monetize_by === 'wc' && class_exists( 'WooCommerce' ) ) {
					$product_id = tutor_utils()->get_course_product_id( $course_id );
					if ( $product_id ) {
						$course->product_id = $product_id;
						$course->product_url = get_permalink( $product_id );
					} else {
						$course->product_id = null;
						$course->product_url = null;
					}
				} else {
					$course->product_id = null;
					$course->product_url = null;
				}
				
				$context['courses'][] = $course;
			}
		}
		wp_reset_postdata();
	}
}

// Get books (WooCommerce products with category "book")
$context['books'] = array();
if ( class_exists( 'WooCommerce' ) ) {
	// Get book category term
	$book_term = get_term_by( 'slug', 'book', 'product_cat' );
	
	if ( $book_term && ! is_wp_error( $book_term ) ) {
		// Get products with book category
		$books_args = array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => 8, // Limit to 8 books
			'orderby'        => 'date',
			'order'          => 'DESC',
			'tax_query'      => array(
				array(
					'taxonomy' => 'product_cat',
					'field'    => 'slug',
					'terms'    => 'book',
				),
			),
		);
		
		$books_query = new WP_Query( $books_args );
		
		if ( $books_query->have_posts() ) {
			while ( $books_query->have_posts() ) {
				$books_query->the_post();
				$product_id = get_the_ID();
				
				// Get product using Timber::get_post()
				$product = Timber::get_post( $product_id );
				
				if ( $product ) {
					// Get WooCommerce product object
					$wc_product = wc_get_product( $product_id );
					
					if ( $wc_product ) {
						// Get product price (formatted)
						$regular_price = $wc_product->get_regular_price();
						$sale_price = $wc_product->get_sale_price();
						$price = $wc_product->get_price();
						
						$product->regular_price = $regular_price ? floatval( $regular_price ) : null;
						$product->sale_price = $sale_price ? floatval( $sale_price ) : null;
						$product->price = $price ? floatval( $price ) : 0;
						
						// Check if product is free
						$product->is_free = ( $product->price == 0 && ! $product->regular_price && ! $product->sale_price );
						
						// Calculate discount percentage
						if ( $sale_price && $regular_price && $regular_price > 0 ) {
							$product->discount_percent = round( ( ( $regular_price - $sale_price ) / $regular_price ) * 100 );
						} else {
							$product->discount_percent = 0;
						}
						
						// Get product image
						$product->thumbnail = get_the_post_thumbnail_url( $product_id, 'full' );
						if ( ! $product->thumbnail ) {
							$product->thumbnail = wc_placeholder_img_src( 'full' );
						}
						
						// Get stock quantity (use custom field if available, otherwise use WooCommerce stock)
						$book_available_count = get_post_meta( $product_id, '_book_available_count', true );
						if ( $book_available_count !== '' && $book_available_count !== false ) {
							$product->stock_quantity = intval( $book_available_count );
						} else {
							$product->stock_quantity = $wc_product->get_stock_quantity();
						}
						$product->stock_status = $wc_product->get_stock_status();
						
						// Get product URL
						$product->product_url = get_permalink( $product_id );
						
						// Get product author/instructor (if available)
						$author_id = get_post_field( 'post_author', $product_id );
						if ( $author_id ) {
							$product->author = Timber::get_user( $author_id );
						} else {
							$product->author = null;
						}
						
						// Get custom fields (pages)
						$product->pages = get_post_meta( $product_id, '_book_pages', true );
						
						$context['books'][] = $product;
					}
				}
			}
			wp_reset_postdata();
		}
	}
}

// Get products with bundles using asnp-product-bundles plugin
$context['bundles'] = array();
if ( class_exists( 'WooCommerce' ) && class_exists( 'AsanaPlugins\WooCommerce\ProductBundles\Plugin' ) ) {
	// Get all products of type 'easy_product_bundle'
	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_type',
				'field'    => 'slug',
				'terms'    => 'easy_product_bundle',
			),
		),
	);
	
	$bundles_query = new WP_Query( $args );
	
	if ( $bundles_query->have_posts() ) {
		while ( $bundles_query->have_posts() ) {
			$bundles_query->the_post();
			$bundle_product_id = get_the_ID();
			$product = wc_get_product( $bundle_product_id );
			
			// Verify it's actually a bundle product
			if ( $product && $product->is_type( 'easy_product_bundle' ) ) {
				global $wpdb;
				$table_name = $wpdb->prefix . 'asnp_wepb_simple_bundle_items';
				
				// Get bundle items count
				$has_bundles = $wpdb->get_var( $wpdb->prepare(
					"SELECT COUNT(*) FROM {$table_name} WHERE bundle_id = %d",
					$bundle_product_id
				) );
				
				if ( $has_bundles > 0 ) {
					// Get bundle items count
					$bundle_items_count = intval( $has_bundles );
					
					// Get product data
					$bundle_data = array(
						'id' => $bundle_product_id,
						'title' => $product->get_name(),
						'link' => get_permalink( $bundle_product_id ),
						'thumbnail' => get_the_post_thumbnail_url( $bundle_product_id, 'full' ) ?: wc_placeholder_img_src(),
						'bundle_items_count' => $bundle_items_count,
					);
					
					// Get product prices
					$regular_price = $product->get_regular_price();
					$sale_price = $product->get_sale_price();
					$price = $product->get_price();
					
					$bundle_data['regular_price'] = $regular_price ? floatval( $regular_price ) : null;
					$bundle_data['sale_price'] = $sale_price ? floatval( $sale_price ) : null;
					$bundle_data['price'] = $price ? floatval( $price ) : 0;
					
					// Calculate discount percentage
					if ( $bundle_data['sale_price'] && $bundle_data['regular_price'] && $bundle_data['regular_price'] > 0 ) {
						$bundle_data['discount_percent'] = round( ( ( $bundle_data['regular_price'] - $bundle_data['sale_price'] ) / $bundle_data['regular_price'] ) * 100 );
					} else {
						$bundle_data['discount_percent'] = 0;
					}
					
					// Check if product is free
					$bundle_data['is_free'] = ( ! $bundle_data['regular_price'] && ! $bundle_data['sale_price'] ) || $bundle_data['price'] == 0;
					
					// Get product rating
					$average_rating = $product->get_average_rating();
					$rating_count = $product->get_rating_count();
					$bundle_data['rating_avg'] = $average_rating ? number_format( $average_rating, 1 ) : 0;
					$bundle_data['rating_count'] = $rating_count;
					
					// Get bundle author/instructor
					$author_id = get_post_field( 'post_author', $bundle_product_id );
					if ( $author_id ) {
						$author = Timber::get_user( $author_id );
						$bundle_data['author'] = $author;
						$bundle_data['author_name'] = $author ? $author->display_name : '';
						$bundle_data['author_avatar'] = $author ? get_avatar_url( $author_id, array( 'size' => 40 ) ) : '';
					} else {
						$bundle_data['author'] = null;
						$bundle_data['author_name'] = '';
						$bundle_data['author_avatar'] = '';
					}
					
					$context['bundles'][] = $bundle_data;
				}
			}
		}
		wp_reset_postdata();
	}
}

// Ensure bundles is always set (even if empty)
if ( ! isset( $context['bundles'] ) ) {
	$context['bundles'] = array();
}

// Get articles (WordPress posts)
$context['articles'] = array();
$articles_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => 6,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

$articles_query = new WP_Query( $articles_args );

if ( $articles_query->have_posts() ) {
	while ( $articles_query->have_posts() ) {
		$articles_query->the_post();
		$post_id = get_the_ID();
		
		$article = Timber::get_post( $post_id );
		
		if ( $article ) {
			// Get featured image
			$article->thumbnail = get_the_post_thumbnail_url( $post_id, 'full' );
			if ( ! $article->thumbnail ) {
				$article->thumbnail = $context['theme_uri'] . '/assets/img/DataStructure.png';
			}
			
			// Get author
			$author_id = get_post_field( 'post_author', $post_id );
			if ( $author_id ) {
				$article->author = Timber::get_user( $author_id );
			} else {
				$article->author = null;
			}
			
			// Get excerpt (short description)
			$article->description = get_the_excerpt( $post_id );
			if ( empty( $article->description ) ) {
				$article->description = wp_trim_words( get_the_content( null, false, $post_id ), 20, '...' );
			}
			
			$context['articles'][] = $article;
		}
	}
	wp_reset_postdata();
}

// Render Twig template
Timber::render( 'front-page.twig', $context );

