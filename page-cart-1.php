<?php
/**
 * Template for /cart-1/ page — Tutor LMS Ecommerce Cart
 *
 * WordPress template hierarchy: page-{slug}.php takes highest priority,
 * ensuring this file loads instead of Tutor's default table layout.
 *
 * @package EduBlink_Child
 */

use Tutor\Ecommerce\CartController;
use Tutor\Ecommerce\CheckoutController;
use Tutor\Ecommerce\Tax;
use Tutor\Models\CourseModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Timber\Timber' ) ) {
	echo 'Timber plugin is not installed.';
	return;
}

$context = Timber::context();

// ── No Tutor LMS → show empty state ──────────────────────────────────────
if ( ! function_exists( 'tutor_utils' ) || ! class_exists( CartController::class ) ) {
	$context['has_items']    = false;
	$context['cart_items']   = [];
	$context['total_count']  = 0;
	$context['shop_url']     = home_url( '/courses/' );
	Timber::render( 'page-cart-1.twig', $context );
	return;
}

// ── Fetch cart data ───────────────────────────────────────────────────────
$cart_controller = new CartController();
$get_cart        = $cart_controller->get_cart_items();
$courses         = $get_cart['courses'];
$total_count     = (int) ( $courses['total_count'] ?? 0 );
$course_list     = $courses['results'] ?? [];

$subtotal         = 0.0;
$tax_exempt_price = 0.0;
$checkout_url     = CheckoutController::get_page_url();

// ── Build items array ─────────────────────────────────────────────────────
$cart_items = [];

if ( is_array( $course_list ) ) {
	foreach ( $course_list as $course ) {
		$raw_price     = tutor_utils()->get_raw_course_price( $course->ID );
		$regular_price = (float) ( $raw_price->regular_price ?? 0 );
		$sale_price    = (float) ( $raw_price->sale_price ?? 0 );
		$display_price = $sale_price > 0 ? $sale_price : $regular_price;

		$subtotal += $display_price;

		$tax_enabled = CourseModel::is_tax_enabled_for_single_purchase( $course->ID );
		if ( ! $tax_enabled ) {
			$tax_exempt_price += $display_price;
		}

		// Capture formatted prices (tutor_print_formatted_price echoes directly)
		ob_start();
		tutor_print_formatted_price( $display_price );
		$fmt_price = ob_get_clean();

		ob_start();
		tutor_print_formatted_price( $regular_price );
		$fmt_regular = ob_get_clean();

		$cart_items[] = [
			'id'          => (int) $course->ID,
			'title'       => $course->post_title,
			'permalink'   => get_permalink( $course->ID ),
			'thumbnail'   => get_tutor_course_thumbnail_src( '', $course->ID ),
			'duration'    => get_tutor_course_duration_context( $course->ID, true ),
			'level'       => get_tutor_course_level( $course->ID ),
			'author'      => get_the_author_meta( 'display_name', $course->post_author ),
			'has_sale'    => ( $regular_price > 0 && $sale_price > 0 && $sale_price !== $regular_price ),
			'price'       => $fmt_price,
			'price_old'   => $fmt_regular,
		];
	}
}

// ── Tax calculation ───────────────────────────────────────────────────────
$should_calc_tax = Tax::should_calculate_tax();
$is_tax_incl     = Tax::is_tax_included_in_price();
$tax_rate        = (float) Tax::get_user_tax_rate();
$tax_amount      = 0.0;
$total           = $subtotal;

if ( $should_calc_tax && $tax_rate > 0 ) {
	if ( $is_tax_incl ) {
		$tax_full    = Tax::calculate_tax( $subtotal, $tax_rate );
		$tax_exempt  = Tax::calculate_tax( $tax_exempt_price, $tax_rate );
		$tax_amount  = $tax_full - $tax_exempt;
	} else {
		$tax_amount = Tax::calculate_tax( $subtotal - $tax_exempt_price, $tax_rate );
		$total     += $tax_amount;
	}
}

ob_start(); tutor_print_formatted_price( $subtotal );   $fmt_subtotal = ob_get_clean();
ob_start(); tutor_print_formatted_price( $tax_amount ); $fmt_tax      = ob_get_clean();
ob_start(); tutor_print_formatted_price( $total );      $fmt_total    = ob_get_clean();

// ── Build Timber context ──────────────────────────────────────────────────
$context['has_items']    = ! empty( $cart_items );
$context['cart_items']   = $cart_items;
$context['total_count']  = $total_count;
$context['checkout_url'] = esc_url( $checkout_url );
$context['fmt_subtotal'] = $fmt_subtotal;
$context['fmt_tax']      = $fmt_tax;
$context['fmt_total']    = $fmt_total;
$context['show_tax']     = ( $should_calc_tax && $tax_rate > 0 && $tax_amount > 0 );
$context['tax_label']    = sprintf( 'الضريبة (%s%%)', number_format( $tax_rate, 0 ) );
$context['shop_url']     = function_exists( 'wc_get_page_permalink' )
	? wc_get_page_permalink( 'shop' )
	: home_url( '/courses/' );

Timber::render( 'page-cart-1.twig', $context );
