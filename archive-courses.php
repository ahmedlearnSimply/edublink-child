<?php
/**
 * Template for displaying courses archive
 * Uses the same card design as the homepage (learnsimply-courses-grid)
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Timber is available
if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

// Check if Tutor LMS is active
if ( ! function_exists( 'tutor_utils' ) ) {
	echo 'Tutor LMS is not active';
	return;
}

use TUTOR\Input;

// Get Timber context
$context = Timber::context();

// Add theme directory URI to context
$context['theme_uri'] = get_stylesheet_directory_uri();

// Handle search query first
$search_query = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$context['search_query'] = $search_query;

// Handle course filters
$get = isset( $_GET['course_filter'] ) ? Input::sanitize_array( $_GET ) : array();//phpcs:ignore
if ( isset( $get['course_filter'] ) ) {
	$filter = ( new \Tutor\Course_Filter( false ) )->load_listing( $get, true );
	query_posts( $filter );
} elseif ( ! empty( $search_query ) ) {
	// If search query exists, modify the main query
	global $wp_query;
	$course_post_type = tutor()->course_post_type;
	
	$args = array(
		'post_type'      => $course_post_type,
		'post_status'    => 'publish',
		's'              => $search_query,
		'posts_per_page' => get_option( 'posts_per_page', 12 ),
	);
	
	query_posts( $args );
}

// Get archive title - use professional title instead of "Archive"
$raw_title = get_the_archive_title();
$current_term = get_queried_object();

// If viewing a specific category, use category name
if ( $current_term && isset( $current_term->taxonomy ) && 'course-category' === $current_term->taxonomy ) {
	$archive_title = $current_term->name;
} else {
	// For main courses archive, use professional title
	$archive_title = 'جميع الدورات';
}

$context['archive_title'] = $archive_title;

// Get courses from current query
$context['courses'] = array();
$course_post_type = tutor()->course_post_type;

if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
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
				$course->thumbnail = learnsimply_no_image_url();
			}
			
			// Get WooCommerce product ID if course is monetized by WooCommerce
			$monetize_by = tutor_utils()->get_option( 'monetize_by' );
			if ( $monetize_by === 'wc' && class_exists( 'WooCommerce' ) ) {
				$product_id = tutor_utils()->get_course_product_id( $course_id );
				if ( $product_id ) {
					$course->product_id = $product_id;
					$product = wc_get_product( $product_id );
					if ( $product ) {
						$course->product_url = get_permalink( $product_id );
					} else {
						$course->product_url = null;
					}
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

// Sidebar: course categories for simple filtering/navigation
$course_categories_raw = get_terms(
	array(
		'taxonomy'   => 'course-category',
		'hide_empty' => true,
	)
);

// Convert to Timber Terms for proper link() method
$context['course_categories'] = array();
if ( ! is_wp_error( $course_categories_raw ) && ! empty( $course_categories_raw ) ) {
	foreach ( $course_categories_raw as $term ) {
		$context['course_categories'][] = Timber::get_term( $term->term_id, 'course-category' );
	}
}

// Current category slug (if viewing a course-category archive)
$current_term = get_queried_object();
$context['current_course_category_slug'] = ( $current_term && isset( $current_term->taxonomy ) && 'course-category' === $current_term->taxonomy )
	? $current_term->slug
	: '';

// Pagination
global $wp_query;
$context['pagination'] = Timber::get_pagination();

// Render Twig template
Timber::render( 'archive-courses.twig', $context );

