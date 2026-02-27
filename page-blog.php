<?php
/**
 * Template Name: Blog - Articles
 * 
 * Custom page template for displaying blog posts/articles
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
$context = Timber::context();

// Add theme directory URI to context
$context['theme_uri'] = get_stylesheet_directory_uri();

// Search term
$search_term = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
$context['search_term'] = $search_term;

// Page title
if ( ! empty( $search_term ) ) {
	$context['page_title'] = 'نتائج البحث';
	$context['page_description'] = 'نتائج البحث عن: ' . esc_html( $search_term );
} else {
	$context['page_title'] = 'المقالات';
	$context['page_description'] = 'تصفّح أحدث المقالات التقنية وابقَ على اطلاع دائم بكل جديد.';
}

// Instructor title (default)
$context['instructor_title'] = 'مهندس برمجيات';

// Pagination
$paged = isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : ( get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1 );
$posts_per_page = 12;

// Get articles (WordPress posts)
$context['articles'] = array();
$articles_args = array(
	'post_type'      => 'post',
	'post_status'    => 'publish',
	'posts_per_page' => $posts_per_page,
	'paged'          => $paged,
	'orderby'        => 'date',
	'order'          => 'DESC',
);

if ( ! empty( $search_term ) ) {
	$articles_args['s'] = $search_term;
}

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
				$article->thumbnail = learnsimply_no_image_url();
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

// Pagination data
$context['pagination'] = array(
	'current' => $paged,
	'total'   => $articles_query->max_num_pages,
);

// Generate pagination links
$pagination_add_args = array();
if ( ! empty( $search_term ) ) {
	$pagination_add_args['s'] = $search_term;
}
$context['pagination_links'] = paginate_links( array(
	'total'     => $articles_query->max_num_pages,
	'current'   => $paged,
	'prev_text' => '&larr; السابق',
	'next_text' => 'التالي &rarr;',
	'type'      => 'array',
	'add_args'  => $pagination_add_args,
) );

// Render Twig template
Timber::render( 'archive-posts.twig', $context );
