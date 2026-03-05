<?php
/**
 * Custom Single Product Template - Timber/Twig
 * 
 * Custom single product page template using Timber/Twig
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Check if Timber is available
if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

// Check if WooCommerce is active
if ( ! function_exists( 'wc_get_product' ) ) {
	echo 'WooCommerce is not active';
	return;
}

global $product;

// Get product object if not already set
if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
	$product = wc_get_product( get_the_ID() );
}

if ( ! $product ) {
	return;
}

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

// Get product post using Timber
$product_post = Timber::get_post( get_the_ID() );
$context['product'] = $product_post;

// Get product basic data
$context['product_id'] = $product->get_id();
$context['product_title'] = $product->get_name();
$context['product_content'] = $product->get_description();
$context['product_excerpt'] = $product->get_short_description();

// Get product prices
$context['regular_price'] = $product->get_regular_price() ? floatval( $product->get_regular_price() ) : null;
$context['sale_price'] = $product->get_sale_price() ? floatval( $product->get_sale_price() ) : null;
$context['price'] = $product->get_price() ? floatval( $product->get_price() ) : null;

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

if ( $context['price'] ) {
	$context['price_formatted'] = number_format( $context['price'], 2, '.', ',' );
} else {
	$context['price_formatted'] = null;
}

// Calculate discount percentage
if ( $context['sale_price'] && $context['regular_price'] && $context['regular_price'] > 0 ) {
	$context['discount_percent'] = round( ( ( $context['regular_price'] - $context['sale_price'] ) / $context['regular_price'] ) * 100 );
} else {
	$context['discount_percent'] = 0;
}

// Check if product is on sale
$context['is_on_sale'] = $product->is_on_sale();
$context['is_in_stock'] = $product->is_in_stock();
$context['stock_status'] = $product->get_stock_status();
$context['stock_quantity'] = $product->get_stock_quantity();

// Get product images
$context['product_image_id'] = $product->get_image_id();
$context['product_image'] = wp_get_attachment_image_url( $context['product_image_id'], 'full' );
if ( ! $context['product_image'] ) {
	$context['product_image'] = learnsimply_no_image_url(); // Fallback image
}

// Get product gallery images
$gallery_ids = $product->get_gallery_image_ids();
$context['gallery_images'] = array();
if ( ! empty( $gallery_ids ) ) {
	foreach ( $gallery_ids as $gallery_id ) {
		$image_url = wp_get_attachment_image_url( $gallery_id, 'full' );
		if ( $image_url ) {
			$context['gallery_images'][] = $image_url;
		}
	}
}

// If no gallery images, use main product image
if ( empty( $context['gallery_images'] ) && $context['product_image'] ) {
	$context['gallery_images'][] = $context['product_image'];
}

// Get product categories
$categories = wc_get_product_terms( $context['product_id'], 'product_cat', array( 'fields' => 'all' ) );
$context['categories'] = $categories && ! is_wp_error( $categories ) ? $categories : array();

// Get product tags
$tags = wc_get_product_terms( $context['product_id'], 'product_tag', array( 'fields' => 'all' ) );
$context['tags'] = $tags && ! is_wp_error( $tags ) ? $tags : array();

// Get product rating
$context['rating'] = $product->get_average_rating();
$context['rating_count'] = $product->get_rating_count();
$context['rating_avg'] = $context['rating'];

// Get product meta (for books)
$context['book_pages'] = get_post_meta( $context['product_id'], '_book_pages', true );
$context['book_available_count'] = get_post_meta( $context['product_id'], '_book_available_count', true );

// Get product reviews (WooCommerce reviews) in a structure similar to Tutor LMS course reviews
$context['course_reviews'] = array();
$comments                  = get_comments(
	array(
		'post_id' => $context['product_id'],
		'status'  => 'approve',
		'type'    => 'review',
		'number'  => 10,
	)
);
if ( $comments && is_array( $comments ) ) {
	foreach ( $comments as $comment ) {
		$rating = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );

		// Get avatar by user ID if available, otherwise by email
		$avatar_source = $comment->user_id ? $comment->user_id : $comment->comment_author_email;
		$avatar_url    = get_avatar_url(
			$avatar_source,
			array(
				'size' => 40,
			)
		);

		$context['course_reviews'][] = array(
			'id'      => $comment->comment_ID,
			'author'  => $comment->comment_author,
			'rating'  => $rating,
			'content' => $comment->comment_content,
			'date'    => $comment->comment_date,
			'avatar'  => $avatar_url,
			'pending' => false,
		);
	}
}

// If user is logged in, add their pending (unapproved) review so they see "قيد المراجعة"
$current_user_id = get_current_user_id();
if ( $current_user_id > 0 ) {
	$pending = get_comments(
		array(
			'post_id' => $context['product_id'],
			'status'  => 'hold',
			'type'    => 'review',
			'user_id' => $current_user_id,
			'number'  => 1,
		)
	);
	if ( ! empty( $pending ) ) {
		$comment = $pending[0];
		$rating  = intval( get_comment_meta( $comment->comment_ID, 'rating', true ) );
		$avatar_url = get_avatar_url( $current_user_id, array( 'size' => 40 ) );
		$context['course_reviews'] = array_merge(
			array(
				array(
					'id'      => $comment->comment_ID,
					'author'  => $comment->comment_author,
					'rating'  => $rating,
					'content' => $comment->comment_content,
					'date'    => $comment->comment_date,
					'avatar'  => $avatar_url,
					'pending' => true,
				),
			),
			$context['course_reviews']
		);
	}
}

// Build WooCommerce review form HTML (stars + comment) to use in Twig
$context['reviews_form'] = '';
if ( comments_open( $context['product_id'] ) ) {
	ob_start();

	$comment_form = array(
		'title_reply'          => '',
		'title_reply_to'       => '',
		'label_submit'         => __( 'إرسال التقييم', 'woocommerce' ),
		'comment_notes_before' => '',
		'comment_notes_after'  => '',
		// Redirect back to this product page after submit so the template loads and shows success/pending
		'redirect_to'          => get_permalink( $product->get_id() ),
		// Remove name/email/website fields – reviews rely on logged-in user data only.
		'fields'               => array(),
	);

	// Custom rating + textarea structure. We only output the label and <select id="rating">.
	// WooCommerce's single-product.js adds the star links via JS when it finds #rating.
	// Including stars here would duplicate them because WooCommerce JS adds them anyway.
	if ( wc_review_ratings_enabled() ) {
		$comment_form['comment_field']  = '<div class="comment-form-rating">';
		// Arabic label: "تقييمك" with clarification about selecting number of stars
		$comment_form['comment_field'] .= '<label for="rating" id="comment-form-rating-label">' . esc_html__( 'تقييمك (اختر عدد النجوم من 1 إلى 5)', 'woocommerce' ) . ( wc_review_ratings_required() ? '&nbsp;<span class="required">*</span>' : '' ) . '</label>';
		$comment_form['comment_field'] .= '<select name="rating" id="rating" required style="display:none;">';
		$comment_form['comment_field'] .= '<option value="">' . esc_html__( 'Rate…', 'woocommerce' ) . '</option>';
		$comment_form['comment_field'] .= '<option value="5">' . esc_html__( 'Perfect', 'woocommerce' ) . '</option>';
		$comment_form['comment_field'] .= '<option value="4">' . esc_html__( 'Good', 'woocommerce' ) . '</option>';
		$comment_form['comment_field'] .= '<option value="3">' . esc_html__( 'Average', 'woocommerce' ) . '</option>';
		$comment_form['comment_field'] .= '<option value="2">' . esc_html__( 'Not that bad', 'woocommerce' ) . '</option>';
		$comment_form['comment_field'] .= '<option value="1">' . esc_html__( 'Very poor', 'woocommerce' ) . '</option>';
		$comment_form['comment_field'] .= '</select>';
		$comment_form['comment_field'] .= '</div>';
	} else {
		$comment_form['comment_field'] = '';
	}

	// Review textarea - Arabic label: \"مراجعتك\"
	$comment_form['comment_field'] .= '<p class="comment-form-comment"><label for="comment">' . esc_html__( 'مراجعتك', 'woocommerce' ) . '&nbsp;<span class="required">*</span></label><textarea id="comment" name="comment" cols="45" rows="6" required></textarea></p>';

	comment_form( apply_filters( 'woocommerce_product_review_comment_form_args', $comment_form, $product->get_id() ) );

	$context['reviews_form'] = ob_get_clean();
}

// Get product updated date
$context['product_updated'] = get_the_modified_date( 'F j, Y', $context['product_id'] );

// Get product author (WooCommerce product post author)
$context['product_author'] = Timber::get_user( $product_post->post_author );

// Get product type
$context['product_type'] = $product->get_type();

// Get first 5 features from product description or short description
$context['first_five_features'] = array();
$description_text = $context['product_content'] ?: $context['product_excerpt'];
if ( $description_text ) {
	// Split by newlines or bullets and take first 5
	$lines = preg_split( '/\n|•|·/', $description_text );
	$features = array_filter( array_map( 'trim', $lines ) );
	$context['first_five_features'] = array_slice( $features, 0, 5 );
}

// Get students count (if product is linked to a course)
$context['students_count'] = 0;
if ( function_exists( 'tutor_utils' ) ) {
	$course_id = tutor_utils()->get_course_id_by_product( $context['product_id'] );
	if ( $course_id ) {
		$context['students_count'] = tutor_utils()->count_enrolled_users_by_course( $course_id );
	}
}

// Check if product has bundles
$context['has_bundles'] = false;
$context['bundle_items'] = array();
$context['bundle_items_count'] = 0;
$context['bundle_items_json'] = '';

// Check if product type is bundle or has bundle items in database
if ( $product->get_type() === 'bundle' || $product->get_type() === 'easy_product_bundle' || class_exists( 'AsanaPlugins\WooCommerce\ProductBundles\Plugin' ) ) {
	global $wpdb;
	$bundle_items = $wpdb->get_results( $wpdb->prepare(
		"SELECT product_id, quantity FROM {$wpdb->prefix}asnp_wepb_simple_bundle_items WHERE bundle_id = %d",
		$context['product_id']
	) );
	
	if ( ! empty( $bundle_items ) ) {
		$context['has_bundles'] = true;
		$context['bundle_items_count'] = count( $bundle_items );
		
		// Build bundle items JSON for add to cart form (required by bundle plugin)
		$bundle_items_for_json = array();
		
		// Get bundle items details
		foreach ( $bundle_items as $index => $item ) {
			$bundle_product = wc_get_product( $item->product_id );
			if ( $bundle_product && $bundle_product->is_visible() ) {
				$bundle_item_data = array(
					'id' => $item->product_id,
					'title' => $bundle_product->get_name(),
					'quantity' => $item->quantity,
					'image' => wp_get_attachment_image_url( $bundle_product->get_image_id(), 'full' ),
					'link' => get_permalink( $item->product_id ),
				);
				
				// Get course data if linked to Tutor
				if ( function_exists( 'tutor_utils' ) ) {
					$course_id = tutor_utils()->get_course_id_by_product( $item->product_id );
					if ( $course_id ) {
						$bundle_item_data['duration'] = get_tutor_course_duration_context( $course_id );
						$bundle_item_data['lesson_count'] = tutor_utils()->get_lesson_count_by_course( $course_id );
						$bundle_item_data['topics_count'] = $bundle_item_data['lesson_count'];
					}
				}
				
				$context['bundle_items'][] = $bundle_item_data;
				
				// Add to JSON array for bundle plugin
				// Format: array of {id: product_id, qty: quantity}
				$bundle_items_for_json[] = array(
					'id' => absint( $item->product_id ),
					'qty' => absint( $item->quantity ),
				);
			}
		}
		
		// Create JSON string for bundle plugin
		// Format expected by bundle plugin: JSON array of {id, qty} objects
		if ( ! empty( $bundle_items_for_json ) ) {
			$context['bundle_items_json'] = wp_json_encode( $bundle_items_for_json );
		}
	}
}

// Determine which template to use
$template_name = $context['has_bundles'] ? 'single-product-bundle.twig' : 'single-product.twig';

// Render the template
Timber::render( $template_name, $context );
