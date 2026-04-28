<?php
/**
 * List all topics and lessons for course ID 24443.
 * Access: ?list_lessons=1 while logged in as admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp', 'learnsimply_list_lessons_debug' );

function learnsimply_list_lessons_debug() {
	if ( ! isset( $_GET['list_lessons'] ) || '1' !== $_GET['list_lessons'] ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Access denied.' );
	}

	try {
		global $wpdb;

		$course_id = 24443;

		$topics = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID, post_title, menu_order
				 FROM {$wpdb->posts}
				 WHERE post_parent = %d
				   AND post_type = 'topics'
				   AND post_status = 'publish'
				 ORDER BY menu_order ASC",
				$course_id
			)
		);

		echo '<style>body{font-family:monospace;padding:20px;} h2{color:#333;} h3{color:#555;margin-top:20px;} table{border-collapse:collapse;width:100%;max-width:800px;margin-bottom:20px;} th,td{border:1px solid #ccc;padding:6px 12px;text-align:left;} th{background:#f0f0f0;}</style>';
		echo '<h2>Course ' . esc_html( $course_id ) . ' — Topics &amp; Lessons</h2>';

		if ( empty( $topics ) ) {
			echo '<p>No topics found for this course.</p>';
			exit;
		}

		foreach ( $topics as $topic ) {
			echo '<h3>Topic: ' . esc_html( $topic->post_title ) . ' <small>(ID: ' . esc_html( $topic->ID ) . ')</small></h3>';

			$lessons = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT ID, post_title, menu_order
					 FROM {$wpdb->posts}
					 WHERE post_parent = %d
					   AND post_type IN ('lesson', 'tutor_quiz', 'tutor_assignments')
					   AND post_status = 'publish'
					 ORDER BY menu_order ASC",
					$topic->ID
				)
			);

			if ( empty( $lessons ) ) {
				echo '<p><em>No lessons found under this topic.</em></p>';
				continue;
			}

			echo '<table>';
			echo '<tr><th>ID</th><th>Title</th><th>menu_order</th></tr>';
			foreach ( $lessons as $lesson ) {
				echo '<tr>';
				echo '<td>' . esc_html( $lesson->ID ) . '</td>';
				echo '<td>' . esc_html( $lesson->post_title ) . '</td>';
				echo '<td>' . esc_html( $lesson->menu_order ) . '</td>';
				echo '</tr>';
			}
			echo '</table>';
		}
	} catch ( Exception $e ) {
		echo '<p style="color:red;">Error: ' . esc_html( $e->getMessage() ) . '</p>';
	}

	exit;
}
