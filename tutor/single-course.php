<?php
/**
 * Template for displaying single course - Custom HTML Structure
 * 
 * Custom template with different HTML elements and classes (not using Tutor default structure)
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
	exit;
}


$course_id = get_the_ID();

// Get Timber context
$context = Timber::context();

// Add theme directory URI to context
$context['theme_uri'] = get_stylesheet_directory_uri();

// Add cart URL for JavaScript redirects
$cart_page_id = function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'cart' ) : 0;
if ( $cart_page_id && $cart_page_id > 0 ) {
	$context['cart_url'] = get_permalink( $cart_page_id );
} else {
	$context['cart_url'] = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : '/cart-1/';
}

// Get course post using Timber
$course = Timber::get_post( $course_id );
$context['course'] = $course;

// Get course basic data
$context['course_title'] = get_the_title( $course_id );
$context['course_content'] = get_the_content( null, false, $course_id );
$context['course_excerpt'] = get_the_excerpt( $course_id );

// Get course rating
$course_rating = tutor_utils()->get_course_rating( $course_id );
$context['course_rating'] = $course_rating;
$context['rating_avg'] = $course_rating ? number_format( $course_rating->rating_avg, 1 ) : 0;
$context['rating_count'] = $course_rating ? $course_rating->rating_count : 0;

// Get enrollment status
$context['is_enrolled'] = tutor_utils()->is_enrolled( $course_id, get_current_user_id() );
$context['is_public'] = get_post_meta( $course_id, '_tutor_is_public_course', true ) == 'yes';
$context['is_purchasable'] = tutor_utils()->is_course_purchasable( $course_id );
$context['tutor_course_sell_by'] = apply_filters( 'tutor_course_sell_by', null );
$context['lesson_url'] = tutor_utils()->get_course_first_lesson( $course_id );

// Get course price (raw prices for proper formatting)
$price_info = tutor_utils()->get_raw_course_price( $course_id );
$context['regular_price'] = $price_info->regular_price ? floatval( $price_info->regular_price ) : null;
$context['sale_price'] = $price_info->sale_price ? floatval( $price_info->sale_price ) : null;
$context['display_price'] = $price_info->display_price;

// Format prices for display
if ( $context['regular_price'] ) {
	$context['regular_price_formatted'] = number_format( $context['regular_price'], 2, '.', ',' );
} else {
	$context['regular_price_formatted'] = null;
}

if ( $context['sale_price'] ) {
	$context['sale_price_formatted'] = number_format( $context['sale_price'], 2, '.', ',' );
} else {
	$context['sale_price_formatted'] = null;
}

// Calculate discount percentage
if ( $context['sale_price'] && $context['regular_price'] && $context['regular_price'] > 0 ) {
	$context['discount_percent'] = round( ( ( $context['regular_price'] - $context['sale_price'] ) / $context['regular_price'] ) * 100 );
} else {
	$context['discount_percent'] = 0;
}

// Check if course is free
$price_type = tutor_utils()->price_type( $course_id );
$context['is_free'] = ( $price_type === 'free' || ( ! $context['regular_price'] && ! $context['sale_price'] ) );

// Get course meta data
$context['course_duration'] = get_tutor_course_duration_context( $course_id );
$context['lesson_count'] = tutor_utils()->get_lesson_count_by_course( $course_id );
$context['quiz_count'] = tutor_utils()->get_quiz_count_by_course( $course_id );
$context['assignment_count'] = tutor_utils()->get_assignment_count_by_course( $course_id );
$context['course_level'] = get_tutor_course_level( $course_id );
if ( empty( $context['course_level'] ) ) {
	$context['course_level'] = 'مبتدئ';
}

// Get instructors
$instructors = tutor_utils()->get_instructors_by_course( $course_id );
$context['instructors'] = array();
if ( ! empty( $instructors ) ) {
	foreach ( $instructors as $instructor ) {
		if ( isset( $instructor->ID ) ) {
			$context['instructors'][] = Timber::get_user( $instructor->ID );
		}
	}
}

// Get students count
$context['students_count'] = tutor_utils()->count_enrolled_users_by_course( $course_id );

// Get enrolled students data (first 3 for avatars display)
$enrolled_students = tutor_utils()->get_students_data_by_course_id( $course_id, 'ID', true );
$context['students_avatars'] = array();
if ( ! empty( $enrolled_students ) && is_array( $enrolled_students ) ) {
	// Get first 3 students
	$students_to_show = array_slice( $enrolled_students, 0, 3 );
	foreach ( $students_to_show as $student ) {
		// Get student ID - it might be in ID property or as the object itself
		$student_id = isset( $student->ID ) ? $student->ID : ( is_object( $student ) ? $student->ID : $student );
		if ( $student_id ) {
			$avatar_url = get_avatar_url( $student_id, array( 'size' => 40 ) );
			$display_name = isset( $student->display_name ) ? $student->display_name : '';
			if ( $avatar_url ) {
				$context['students_avatars'][] = array(
					'id' => $student_id,
					'avatar' => $avatar_url,
					'name' => $display_name,
				);
			}
		}
	}
}

// Get course topics and content
$topics = tutor_utils()->get_topics( $course_id );
$context['topics'] = array();
$context['first_five_contents'] = array(); // First 5 content items for features list
$content_count = 0;
if ( $topics && $topics->have_posts() ) {
	while ( $topics->have_posts() ) {
		$topics->the_post();
		$topic_id = get_the_ID();
		$topic_contents = tutor_utils()->get_course_contents_by_topic( $topic_id, -1 );
		
		$topic_data = array(
			'id' => $topic_id,
			'title' => get_the_title(),
			'content' => get_the_content(),
			'contents' => array(),
		);
		
		if ( $topic_contents && $topic_contents->have_posts() ) {
			while ( $topic_contents->have_posts() ) {
				$topic_contents->the_post();
				$content_type = get_post_type();
				$content_id = get_the_ID();
				$content_duration = '';
				
				if ( 'lesson' === $content_type || 'tutor_lesson' === $content_type ) {
					$content_duration = get_post_meta( $content_id, '_video_duration', true );
				}
				
				$content_item = array(
					'id' => $content_id,
					'type' => $content_type,
					'title' => get_the_title(),
					'duration' => $content_duration,
				);
				
				$topic_data['contents'][] = $content_item;
				
				// Collect first 5 content items for features list
				if ( $content_count < 5 ) {
					$context['first_five_contents'][] = $content_item;
					$content_count++;
				}
			}
			wp_reset_postdata();
		}
		
		$context['topics'][] = $topic_data;
	}
	wp_reset_postdata();
}

// Get course reviews (include current user's review even if pending, so they see "قيد المراجعة")
$current_user_id_for_reviews = get_current_user_id();
$course_reviews = tutor_utils()->get_course_reviews( $course_id, 0, 10, false, array( 'approved' ), $current_user_id_for_reviews );
$context['course_reviews'] = array();
if ( $course_reviews && is_array( $course_reviews ) ) {
	foreach ( $course_reviews as $review ) {
		$context['course_reviews'][] = array(
			'id' => $review->comment_ID,
			'author' => $review->display_name,
			'rating' => $review->rating,
			'content' => $review->comment_content,
			'date' => $review->comment_date,
			'avatar' => get_avatar_url( $review->user_id, array( 'size' => 40 ) ),
			'pending' => isset( $review->comment_status ) && $review->comment_status === 'hold',
		);
	}
}

// Check if user can write a review (enrolled users only)
$context['can_write_review'] = false;
$context['user_review'] = null;
$context['is_user_logged_in'] = is_user_logged_in();

// Add Tutor nonce for review form
$context['tutor_nonce'] = wp_create_nonce( tutor()->nonce_action );
$context['tutor_nonce_field'] = tutor()->nonce;

if ( $context['is_enrolled'] && is_user_logged_in() ) {
	$context['can_write_review'] = true;
	
	// Check if user already has a review
	$current_user_id = get_current_user_id();
	$my_rating = tutor_utils()->get_reviews_by_user( 0, 0, 150, false, $course_id, array( 'approved', 'hold' ) );
	
	if ( $my_rating && ! empty( $my_rating->rating ) ) {
		$context['user_review'] = array(
			'id' => $my_rating->comment_ID,
			'rating' => $my_rating->rating,
			'content' => stripslashes( $my_rating->comment_content ),
		);
	}
}

// Get course categories and tags
$categories = get_the_terms( $course_id, 'course-category' );
$context['categories'] = $categories && ! is_wp_error( $categories ) ? $categories : array();

$tags = get_the_terms( $course_id, 'course-tag' );
$context['tags'] = $tags && ! is_wp_error( $tags ) ? $tags : array();

// Get course meta fields
// Use tutor_course_benefits() function which returns an array of benefits
if ( function_exists( 'tutor_course_benefits' ) ) {
	$benefits_array = tutor_course_benefits( $course_id );
	// Convert array back to string with newlines for Twig template compatibility
	$context['course_benefits'] = ! empty( $benefits_array ) ? implode( "\n", $benefits_array ) : '';
} else {
$context['course_benefits'] = get_post_meta( $course_id, '_tutor_course_benefits', true );
}
$context['course_requirements'] = get_post_meta( $course_id, '_tutor_course_requirements', true );
$context['course_target_audience'] = get_post_meta( $course_id, '_tutor_course_target_audience', true );
$context['course_material_includes'] = get_post_meta( $course_id, '_tutor_course_material_includes', true );

// Get course image
$context['course_image'] = get_the_post_thumbnail_url( $course_id, 'full' );
if ( ! $context['course_image'] ) {
	$context['course_image'] = learnsimply_no_image_url(); // Fallback image
}

// Get course intro video (if available)
$context['course_video'] = null;
$video_info = get_post_meta( $course_id, '_video', true );
if ( $video_info && is_array( $video_info ) && ! empty( $video_info['source'] ) ) {
	$video_data = array(
		'source' => $video_info['source'],
	);
	
	switch ( $video_info['source'] ) {
		case 'html5':
			// HTML5 video - get attachment URL
			if ( ! empty( $video_info['source_video_id'] ) ) {
				$video_data['url'] = wp_get_attachment_url( $video_info['source_video_id'] );
				$video_data['type'] = 'video/mp4';
			}
			// Get poster image
			if ( ! empty( $video_info['poster'] ) ) {
				$video_data['poster'] = wp_get_attachment_url( $video_info['poster'] );
			}
			break;
			
		case 'external_url':
			// External URL video
			if ( ! empty( $video_info['source_external_url'] ) ) {
				$video_data['url'] = $video_info['source_external_url'];
				$video_data['type'] = 'video/mp4';
			}
			// Get poster image
			if ( ! empty( $video_info['poster'] ) ) {
				$video_data['poster'] = wp_get_attachment_url( $video_info['poster'] );
			}
			break;
			
		case 'youtube':
			// YouTube video
			if ( ! empty( $video_info['source_youtube'] ) ) {
				// Extract video ID from YouTube URL
				$youtube_url = $video_info['source_youtube'];
				$video_id = '';
				
				// Match various YouTube URL formats
				if ( preg_match( '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $youtube_url, $matches ) ) {
					$video_id = $matches[1];
				}
				
				if ( $video_id ) {
					$video_data['video_id'] = $video_id;
					$video_data['embed_url'] = 'https://www.youtube.com/embed/' . $video_id;
				}
			}
			break;
			
		case 'vimeo':
			// Vimeo video
			if ( ! empty( $video_info['source_vimeo'] ) ) {
				// Extract video ID from Vimeo URL
				$vimeo_url = $video_info['source_vimeo'];
				$video_id = '';
				
				if ( preg_match( '/vimeo\.com\/(?:video\/)?(\d+)/', $vimeo_url, $matches ) ) {
					$video_id = $matches[1];
				}
				
				if ( $video_id ) {
					$video_data['video_id'] = $video_id;
					$video_data['embed_url'] = 'https://player.vimeo.com/video/' . $video_id;
				}
			}
			break;
			
		case 'embedded':
			// Embedded code
			if ( ! empty( $video_info['source_embedded'] ) ) {
				$video_data['embed_code'] = $video_info['source_embedded'];
			}
			break;
	}
	
	// Only set video if we have valid data
	if ( isset( $video_data['url'] ) || isset( $video_data['embed_url'] ) || isset( $video_data['embed_code'] ) ) {
		$context['course_video'] = $video_data;
	}
}

// Get course updated date
$context['course_updated'] = get_the_modified_date( 'F j, Y', $course_id );

// Check if course has certificate
// Tutor Pro Certificate addon uses 'tutor_course_certificate_template' meta field
$certificate_template = get_post_meta( $course_id, 'tutor_course_certificate_template', true );
$context['has_certificate'] = ! empty( $certificate_template );

// Get WooCommerce product ID if course is sold via WooCommerce
$context['product_id'] = null;
if ( $context['tutor_course_sell_by'] === 'woocommerce' && class_exists( 'WooCommerce' ) ) {
	$context['product_id'] = tutor_utils()->get_course_product_id( $course_id );
}

// Get course content count (lessons + quizzes + assignments)
$context['content_count'] = $context['lesson_count'] + $context['quiz_count'] + $context['assignment_count'];

// Render the template
Timber::render( 'single-course.twig', $context );
