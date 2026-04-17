<?php
/**
 * Single Post Template
 *
 * Renders individual blog post pages using Timber + Twig.
 *
 * @package EduBlink_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Fall back to parent theme if Timber is unavailable
if ( ! class_exists( 'Timber\Timber' ) ) {
	locate_template( 'single.php', true, true );
	return;
}

// ── Post data ───────────────────────────────────────────────────────────────
$context = Timber::context();

$post = Timber::get_post();
if ( ! $post ) {
	status_header( 404 );
	nocache_headers();
	include get_query_template( '404' );
	exit;
}

// Basic fields
$context['post']       = $post;
$context['theme_uri']  = get_stylesheet_directory_uri();
$context['site_url']   = get_site_url();
$context['post_url']   = get_permalink();
$context['post_title'] = esc_html( $post->title() );

// Featured image
$context['featured_image'] = get_the_post_thumbnail_url( $post->ID, 'full' );
if ( ! $context['featured_image'] ) {
	$context['featured_image'] = null;
}

// Author
$author_id = get_post_field( 'post_author', $post->ID );
$context['author'] = array(
	'name'        => get_the_author_meta( 'display_name', $author_id ),
	'description' => get_the_author_meta( 'description', $author_id ),
	'avatar'      => get_avatar_url( $author_id, array( 'size' => 80 ) ),
	'url'         => get_author_posts_url( $author_id ),
);

// Date
$context['post_date']         = get_the_date( 'j F Y',  $post->ID );
$context['post_date_machine'] = get_the_date( 'c',       $post->ID );

// Category
$cats = get_the_category( $post->ID );
$context['category'] = ( $cats && ! is_wp_error( $cats ) )
	? array( 'name' => $cats[0]->name, 'url' => get_category_link( $cats[0]->term_id ) )
	: null;

// Tags
$tags = get_the_tags( $post->ID );
if ( $tags && ! is_wp_error( $tags ) ) {
	$context['tags'] = array_map( static function ( $t ) {
		return array( 'name' => $t->name, 'url' => get_tag_link( $t->term_id ) );
	}, $tags );
} else {
	$context['tags'] = array();
}

// Reading-time estimate (200 words per minute)
$word_count               = str_word_count( wp_strip_all_tags( get_the_content( null, false, $post->ID ) ) );
$context['reading_time']  = max( 1, (int) ceil( $word_count / 200 ) );

// Post content (rendered)
$context['post_content'] = apply_filters( 'the_content', get_post_field( 'post_content', $post->ID ) );

// Related posts (same category, excluding current)
$related = array();
if ( $cats ) {
	$related_query = new WP_Query( array(
		'post_type'           => 'post',
		'post_status'         => 'publish',
		'posts_per_page'      => 3,
		'post__not_in'        => array( $post->ID ),
		'category__in'        => array( $cats[0]->term_id ),
		'orderby'             => 'rand',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
	) );
	if ( $related_query->have_posts() ) {
		while ( $related_query->have_posts() ) {
			$related_query->the_post();
			$rid = get_the_ID();
			$related[] = array(
				'title'     => esc_html( get_the_title() ),
				'url'       => get_permalink(),
				'thumbnail' => get_the_post_thumbnail_url( $rid, 'medium_large' ) ?: null,
				'date'      => get_the_date( 'j F Y' ),
			);
		}
		wp_reset_postdata();
	}
}
$context['related_posts'] = $related;

// Prev / Next post navigation
$prev_post = get_previous_post();
$next_post = get_next_post();

$context['prev_post'] = $prev_post
	? array( 'title' => esc_html( $prev_post->post_title ), 'url' => get_permalink( $prev_post ) )
	: null;
$context['next_post'] = $next_post
	? array( 'title' => esc_html( $next_post->post_title ), 'url' => get_permalink( $next_post ) )
	: null;

// ── Render ───────────────────────────────────────────────────────────────────
Timber::render( 'single-post.twig', $context );
