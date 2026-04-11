<?php
/**
 * Empty cart page — LearnSimply custom override
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package EduBlink_Child
 */

defined( 'ABSPATH' ) || exit;

$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/shop-2/' );

?>
<div class="ls-empty-cart">
	<div class="ls-empty-cart__inner">

		<!-- Icon -->
		<div class="ls-empty-cart__icon">
			<svg xmlns="http://www.w3.org/2000/svg" width="72" height="72" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
				<circle cx="9" cy="21" r="1"/>
				<circle cx="20" cy="21" r="1"/>
				<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
			</svg>
		</div>

		<!-- Heading -->
		<h2 class="ls-empty-cart__title">سلتك فارغة حالياً</h2>

		<!-- Sub-text -->
		<p class="ls-empty-cart__subtitle">
			لم تضف أي منتجات بعد.<br>
			تصفح متجرنا واختر ما يناسبك.
		</p>

		<!-- CTA -->
		<a href="<?php echo esc_url( $shop_url ); ?>" class="ls-empty-cart__btn">
			<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none"
				stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
				style="margin-left:8px;">
				<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
				<line x1="3" y1="6" x2="21" y2="6"/>
				<path d="M16 10a4 4 0 0 1-8 0"/>
			</svg>
			العودة إلى المتجر
		</a>

		<?php do_action( 'woocommerce_cart_is_empty' ); ?>
	</div>
</div>
