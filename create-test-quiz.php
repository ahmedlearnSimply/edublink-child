<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'learnsimply_create_test_quiz' );
function learnsimply_create_test_quiz() {
	if ( ! isset( $_GET['create_test_quiz'] ) || $_GET['create_test_quiz'] !== '1' ) {
		return;
	}

	global $wpdb;

	$topic_id    = 24444;
	$quiz_order  = 2;
	$quiz_title  = 'اختبار - ما هي البرمجة';

	// --- Step 1: Shift existing siblings with menu_order >= 2 up by 1 ---
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->posts}
		 SET menu_order = menu_order + 1
		 WHERE post_parent = %d
		 AND post_status = 'publish'
		 AND menu_order >= %d",
		$topic_id,
		$quiz_order
	) );

	// --- Step 2: Insert the quiz post ---
	$quiz_id = wp_insert_post( [
		'post_type'   => 'tutor_quiz',
		'post_title'  => $quiz_title,
		'post_status' => 'publish',
		'post_parent' => $topic_id,
		'menu_order'  => $quiz_order,
		'post_author' => get_current_user_id() ?: 1,
	] );

	if ( is_wp_error( $quiz_id ) || ! $quiz_id ) {
		echo '<pre>ERROR: Failed to insert quiz post: ' . esc_html( $quiz_id->get_error_message() ) . '</pre>';
		die();
	}

	// --- Step 3: Save TutorLMS quiz options in post meta ---
	$quiz_option = [
		'time_limit'                  => [ 'time_value' => 0, 'time_type' => 'minutes' ],
		'hide_quiz_time_display'      => 0,
		'feedback_mode'               => 'default',   // show result after submission
		'attempts_allowed'            => 0,            // unlimited
		'passing_grade'               => 80,
		'max_questions_for_answer'    => 0,
		'quiz_auto_start'             => 0,
		'question_layout_view'        => '',
		'questions_order'             => 'rand',
		'hide_question_number_overview' => 0,
		'short_answer_characters_limit' => 200,
		'open_ended_answer_characters_limit' => 500,
	];
	update_post_meta( $quiz_id, '_tutor_quiz_option', $quiz_option );

	// --- Step 4: Define questions and answers ---
	$questions = [
		[
			'title'   => 'ما هي البرمجة؟',
			'type'    => 'multiple_choice',
			'mark'    => 1,
			'order'   => 1,
			'answers' => [
				[ 'title' => 'تصميم مواقع',          'correct' => 0 ],
				[ 'title' => 'إعطاء أوامر للكمبيوتر', 'correct' => 1 ],
				[ 'title' => 'إصلاح الأجهزة',         'correct' => 0 ],
				[ 'title' => 'تصميم جرافيك',          'correct' => 0 ],
			],
		],
		[
			'title'   => 'أي من التالي يعتبر لغة برمجة؟',
			'type'    => 'multiple_choice',
			'mark'    => 1,
			'order'   => 2,
			'answers' => [
				[ 'title' => 'Photoshop', 'correct' => 0 ],
				[ 'title' => 'Excel',     'correct' => 0 ],
				[ 'title' => 'Java',      'correct' => 1 ],
				[ 'title' => 'Windows',   'correct' => 0 ],
			],
		],
	];

	$questions_table = $wpdb->prefix . 'tutor_quiz_questions';
	$answers_table   = $wpdb->prefix . 'tutor_quiz_question_answers';

	foreach ( $questions as $q ) {
		// Insert question
		$wpdb->insert( $questions_table, [
			'quiz_id'           => $quiz_id,
			'question_title'    => $q['title'],
			'question_description' => '',
			'question_type'     => $q['type'],
			'question_mark'     => $q['mark'],
			'question_order'    => $q['order'],
			'question_settings' => maybe_serialize( [] ),
		] );

		$question_id = $wpdb->insert_id;

		if ( ! $question_id ) {
			echo '<pre>ERROR: Failed to insert question: ' . esc_html( $q['title'] ) . '</pre>';
			die();
		}

		// Insert answers
		foreach ( $q['answers'] as $index => $a ) {
			$wpdb->insert( $answers_table, [
				'belongs_question_id'   => $question_id,
				'belongs_question_type' => $q['type'],
				'answer_title'          => $a['title'],
				'is_correct'            => $a['correct'],
				'image_id'              => 0,
				'answer_two_gap_match'  => '',
				'answer_view_format'    => 'text',
				'answer_settings'       => maybe_serialize( [] ),
				'answer_order'          => $index + 1,
			] );
		}
	}

	// --- Done ---
	echo '<pre>';
	echo "Quiz created successfully!\n\n";
	echo "Quiz ID    : {$quiz_id}\n";
	echo "Title      : {$quiz_title}\n";
	echo "post_parent: {$topic_id}\n";
	echo "menu_order : {$quiz_order}\n\n";
	echo "Questions inserted: " . count( $questions ) . "\n";
	echo '</pre>';
	die();
}

add_action( 'init', 'learnsimply_delete_test_quiz' );
function learnsimply_delete_test_quiz() {
	if ( ! isset( $_GET['delete_test_quiz'] ) || $_GET['delete_test_quiz'] !== '1' ) {
		return;
	}

	global $wpdb;

	$quiz_id         = 38670;
	$topic_id        = 24444;
	$questions_table = $wpdb->prefix . 'tutor_quiz_questions';
	$answers_table   = $wpdb->prefix . 'tutor_quiz_question_answers';

	// --- Step 1: Collect question IDs belonging to this quiz ---
	$question_ids = $wpdb->get_col( $wpdb->prepare(
		"SELECT question_id FROM {$questions_table} WHERE quiz_id = %d",
		$quiz_id
	) );

	// --- Step 2: Delete answers for those questions ---
	$answers_deleted = 0;
	if ( ! empty( $question_ids ) ) {
		$placeholders    = implode( ',', array_fill( 0, count( $question_ids ), '%d' ) );
		$answers_deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$answers_table} WHERE belongs_question_id IN ({$placeholders})",
				...$question_ids
			)
		);
	}

	// --- Step 3: Delete questions ---
	$questions_deleted = $wpdb->delete( $questions_table, [ 'quiz_id' => $quiz_id ], [ '%d' ] );

	// --- Step 4: Get the quiz's menu_order before deleting ---
	$quiz_order = (int) $wpdb->get_var( $wpdb->prepare(
		"SELECT menu_order FROM {$wpdb->posts} WHERE ID = %d",
		$quiz_id
	) );

	// --- Step 5: Delete the quiz post and its meta ---
	wp_delete_post( $quiz_id, true );

	// --- Step 6: Shift siblings with menu_order > quiz's old order back down by 1 ---
	$wpdb->query( $wpdb->prepare(
		"UPDATE {$wpdb->posts}
		 SET menu_order = menu_order - 1
		 WHERE post_parent = %d
		 AND post_status = 'publish'
		 AND menu_order > %d",
		$topic_id,
		$quiz_order
	) );

	echo '<pre>';
	echo "Quiz deleted successfully!\n\n";
	echo "Quiz ID         : {$quiz_id}\n";
	echo "Questions deleted: " . count( $question_ids ) . "\n";
	echo "Answers deleted  : {$answers_deleted}\n";
	echo "Siblings shifted : menu_order > {$quiz_order} decremented by 1\n";
	echo '</pre>';
	die();
}
