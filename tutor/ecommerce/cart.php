<?php
/**
 * Custom Cart Template - Empty Design
 * 
 * Custom cart page template with different HTML elements and classes
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if Tutor LMS is active
if ( ! function_exists( 'tutor_utils' ) ) {
	echo 'Tutor LMS is not active';
	exit;
}

use Tutor\Ecommerce\CartController;
use Tutor\Ecommerce\CheckoutController;
use Tutor\Ecommerce\Tax;
use Tutor\Models\CourseModel;

$cart_controller = new CartController();
$get_cart = $cart_controller->get_cart_items();
$courses = $get_cart['courses'];
$total_count = $courses['total_count'];
$course_list = $courses['results'];

$subtotal = 0;
$tax_exempt_price = 0;

$checkout_page_url = CheckoutController::get_page_url();

?>

<!-- Custom Cart Header -->
<section class="custom-cart-header">
	<h1 class="custom-cart-title">
		<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#4077f3" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align: middle; margin-left: 10px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
		سلة التسوق
	</h1>
	<?php if ( is_array( $course_list ) && count( $course_list ) > 0 ) : ?>
		<p class="custom-cart-count"><?php echo esc_html( sprintf( _n( '%d دورة في السلة', '%d دورات في السلة', $total_count, 'tutor' ), $total_count ) ); ?></p>
	<?php endif; ?>
</section>

<?php if ( is_array( $course_list ) && count( $course_list ) > 0 ) : ?>

<!-- Custom Cart Items List -->
<section class="custom-cart-items-section">
	<div class="custom-cart-items-list">
		<?php
		foreach ( $course_list as $key => $course ) :
			$course_duration = get_tutor_course_duration_context( $course->ID, true );
			$course_price = tutor_utils()->get_raw_course_price( $course->ID );
			$regular_price = $course_price->regular_price;
			$sale_price = $course_price->sale_price;
			$display_price = $sale_price ? $sale_price : $regular_price;
			$course_image = get_tutor_course_thumbnail_src( '', $course->ID );
			$course_permalink = get_permalink( $course->ID );
			$course_level = get_tutor_course_level( $course->ID );

			$subtotal += $display_price;

			$tax_collection = CourseModel::is_tax_enabled_for_single_purchase( $course->ID );
			if ( ! $tax_collection ) {
				$tax_exempt_price += $display_price;
			}
			?>
			<div class="custom-cart-item">
				<div class="custom-cart-item-image">
					<a href="<?php echo esc_url( $course_permalink ); ?>">
						<img src="<?php echo esc_url( $course_image ); ?>" alt="<?php echo esc_attr( $course->post_title ); ?>">
					</a>
				</div>
				
				<div class="custom-cart-item-info">
					<h3 class="custom-cart-item-title">
						<a href="<?php echo esc_url( $course_permalink ); ?>">
							<?php echo esc_html( $course->post_title ); ?>
						</a>
					</h3>
					
					<div class="custom-cart-item-meta">
						<?php if ( $course_duration ) : ?>
							<span class="custom-meta-item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
								<?php echo esc_html( tutor_utils()->clean_html_content( $course_duration ) ); ?>
							</span>
						<?php endif; ?>
						
						<?php if ( $course_level ) : ?>
							<span class="custom-meta-item">
								<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>
								<?php echo esc_html( $course_level ); ?>
							</span>
						<?php endif; ?>
					</div>
				</div>
				
				<div class="custom-cart-item-price-section">
					<div class="custom-cart-item-price">
						<?php if ( $regular_price && $sale_price && $sale_price !== $regular_price ) : ?>
							<span class="custom-price-old"><?php tutor_print_formatted_price( $regular_price ); ?></span>
						<?php endif; ?>
						<span class="custom-price-current"><?php tutor_print_formatted_price( $display_price ); ?></span>
					</div>
					
					<button type="button" class="custom-remove-item-btn" data-course-id="<?php echo esc_attr( $course->ID ); ?>">
						<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><line x1="10" y1="11" x2="10" y2="17"/><line x1="14" y1="11" x2="14" y2="17"/></svg>
						إزالة
					</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
</section>

<!-- Custom Cart Summary -->
<section class="custom-cart-summary-section">
	<div class="custom-cart-summary">
		<h2 class="custom-summary-title">ملخص الطلب</h2>
		
		<div class="custom-summary-details">
			<?php
			$should_calculate_tax = Tax::should_calculate_tax();
			$is_tax_included_in_price = Tax::is_tax_included_in_price();
			$tax_rate = Tax::get_user_tax_rate();
			$show_tax_incl_text = $should_calculate_tax && $tax_rate > 0 && $is_tax_included_in_price;
			$tax_amount = 0;

			if ( $should_calculate_tax ) {
				$tax_amount = Tax::calculate_tax( $subtotal, $tax_rate );
				$tax_exempt_amount = Tax::calculate_tax( $tax_exempt_price, $tax_rate );
				$tax_amount = $tax_amount - $tax_exempt_amount;
			}

			$grand_total = $subtotal;
			if ( ! $is_tax_included_in_price ) {
				$grand_total += $tax_amount;
			}
			?>
			
			<div class="custom-summary-row">
				<span class="custom-summary-label">المجموع الفرعي:</span>
				<span class="custom-summary-value"><?php tutor_print_formatted_price( $subtotal ); ?></span>
			</div>
			
			<?php if ( $should_calculate_tax && $tax_rate > 0 && ! $is_tax_included_in_price ) : ?>
				<div class="custom-summary-row">
					<span class="custom-summary-label">الضريبة:</span>
					<span class="custom-summary-value"><?php tutor_print_formatted_price( $tax_amount ); ?></span>
				</div>
			<?php endif; ?>
			
			<?php if ( $should_calculate_tax && $tax_rate > 0 && $is_tax_included_in_price ) : ?>
				<div class="custom-summary-tax-note">
					<?php
					/* translators: %s: tax amount */
					echo esc_html( sprintf( __( '(شامل الضريبة %s)', 'tutor' ), tutor_get_formatted_price( $tax_amount ) ) );
					?>
				</div>
			<?php endif; ?>
			
			<div class="custom-summary-row custom-summary-total">
				<span class="custom-summary-label">المجموع الكلي:</span>
				<span class="custom-summary-value"><?php tutor_print_formatted_price( $grand_total ); ?></span>
			</div>
		</div>
		
		<?php if ( $checkout_page_url ) : ?>
			<a href="<?php echo esc_url( $checkout_page_url ); ?>" class="custom-checkout-btn">
				المتابعة للدفع
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px; transform: scaleX(-1);"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
		<?php else : ?>
			<p class="custom-error-message">صفحة الدفع غير مُعرّفة</p>
		<?php endif; ?>
	</div>
</section>

<?php else : ?>

<!-- Custom Empty Cart State -->
<section class="custom-empty-cart-section">
	<div class="custom-empty-cart-content">
		<svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="#999eb2" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" style="opacity: 0.5; margin-bottom: 8px;"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
		<p class="custom-empty-cart-message">لا توجد دورات في السلة</p>
		<?php
		$course_archive_url = tutor_utils()->course_archive_page_url();
		if ( $course_archive_url ) :
			?>
			<a href="<?php echo esc_url( $course_archive_url ); ?>" class="custom-browse-courses-btn">
				<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-left: 8px;"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>
				تصفح الدورات
			</a>
		<?php endif; ?>
	</div>
</section>

<?php endif; ?>

