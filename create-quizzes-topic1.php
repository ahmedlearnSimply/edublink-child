<?php
/**
 * Create 4 quizzes inside topic ID 24444 (course 24443).
 * Access: ?create_quizzes_topic1=1 while logged in as admin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp', 'learnsimply_delete_quizzes_topic1' );
add_action( 'wp', 'learnsimply_create_quizzes_topic1' );

function learnsimply_delete_quizzes_topic1() {
	if ( ! isset( $_GET['delete_quizzes_topic1'] ) || '1' !== $_GET['delete_quizzes_topic1'] ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Access denied.' );
	}

	try {
		global $wpdb;

		$topic_id = 24444;
		$quiz_ids = array( 38649, 38650, 38651, 38652 );

		echo '<!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8">';
		echo '<style>body{font-family:sans-serif;padding:20px;direction:rtl;} .ok{color:green;} .err{color:red;}</style>';
		echo '</head><body>';
		echo '<h2>حذف الاختبارات — Topic ID ' . esc_html( $topic_id ) . '</h2>';

		foreach ( $quiz_ids as $quiz_id ) {
			// Collect question IDs for this quiz.
			$q_ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT question_id FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id = %d",
					$quiz_id
				)
			);

			// Delete answers for all questions.
			if ( ! empty( $q_ids ) ) {
				$placeholders = implode( ',', array_fill( 0, count( $q_ids ), '%d' ) );
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}tutor_quiz_question_answers WHERE belongs_question_id IN ($placeholders)",
						...$q_ids
					)
				);
				// Delete questions.
				$wpdb->query(
					$wpdb->prepare(
						"DELETE FROM {$wpdb->prefix}tutor_quiz_questions WHERE quiz_id = %d",
						$quiz_id
					)
				);
			}

			// Delete post meta.
			$wpdb->delete( $wpdb->postmeta, array( 'post_id' => $quiz_id ), array( '%d' ) );

			// Get the quiz menu_order before deleting.
			$quiz_order = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT menu_order FROM {$wpdb->posts} WHERE ID = %d",
					$quiz_id
				)
			);

			// Delete the quiz post.
			$deleted = $wpdb->delete( $wpdb->posts, array( 'ID' => $quiz_id ), array( '%d' ) );

			if ( $deleted ) {
				// Shift siblings with higher menu_order down by 1 to close the gap.
				if ( $quiz_order > 0 ) {
					$wpdb->query(
						$wpdb->prepare(
							"UPDATE {$wpdb->posts}
							 SET menu_order = menu_order - 1
							 WHERE post_parent = %d
							   AND menu_order > %d",
							$topic_id,
							$quiz_order
						)
					);
				}
				echo '<p class="ok">تم حذف Quiz ID: <strong>' . esc_html( $quiz_id ) . '</strong> مع أسئلته وإجاباته</p>';
			} else {
				echo '<p class="err">لم يُعثر على Quiz ID: ' . esc_html( $quiz_id ) . ' أو حدث خطأ</p>';
			}
		}

		echo '<hr><p>اكتمل الحذف. يمكنك الآن زيارة <code>?create_quizzes_topic1=1</code> لإعادة الإنشاء.</p>';
		echo '</body></html>';

	} catch ( Exception $e ) {
		echo '<p style="color:red;">Error: ' . esc_html( $e->getMessage() ) . '</p>';
	}

	die();
}

function learnsimply_create_quizzes_topic1() {
	if ( ! isset( $_GET['create_quizzes_topic1'] ) || '1' !== $_GET['create_quizzes_topic1'] ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Access denied.' );
	}

	try {
		global $wpdb;

		$topic_id  = 24444;
		$quiz_note = 'ملاحظة: بعض الأسئلة النظرية ممكن تحتاج تبحث عنها — ده جزء من التعلم 💪';

		$quizzes = array(
			array(
				'title'      => 'اختبار - ما هي البرمجة',
				'menu_order' => 2,
				'shift_from' => 2,
				'questions'  => array(
					array(
						'title'   => 'ما هي البرمجة؟',
						'answers' => array(
							array( 'title' => 'تصميم الجرافيك', 'correct' => 0 ),
							array( 'title' => 'إعطاء تعليمات للكمبيوتر لتنفيذ مهام معينة', 'correct' => 1 ),
							array( 'title' => 'إصلاح الأجهزة', 'correct' => 0 ),
							array( 'title' => 'تصميم الدوائر الإلكترونية', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'أي من التالي يعتبر لغة برمجة؟',
						'answers' => array(
							array( 'title' => 'Photoshop', 'correct' => 0 ),
							array( 'title' => 'Windows', 'correct' => 0 ),
							array( 'title' => 'Java', 'correct' => 1 ),
							array( 'title' => 'Excel', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما الفرق بين المبرمج والمستخدم العادي؟',
						'answers' => array(
							array( 'title' => 'المبرمج يستخدم البرامج فقط', 'correct' => 0 ),
							array( 'title' => 'المبرمج يصنع البرامج والمستخدم يستخدمها', 'correct' => 1 ),
							array( 'title' => 'لا فرق بينهم', 'correct' => 0 ),
							array( 'title' => 'المستخدم أفضل من المبرمج', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'أي مثال يوضح مفهوم البرمجة في الحياة اليومية؟',
						'answers' => array(
							array( 'title' => 'مشاهدة فيلم', 'correct' => 0 ),
							array( 'title' => 'كتابة وصفة طبخ خطوة بخطوة', 'correct' => 1 ),
							array( 'title' => 'قراءة كتاب', 'correct' => 0 ),
							array( 'title' => 'الاستماع للموسيقى', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'لماذا نتعلم البرمجة؟',
						'answers' => array(
							array( 'title' => 'فقط للحصول على وظيفة', 'correct' => 0 ),
							array( 'title' => 'لحل المشاكل وأتمتة المهام وبناء تطبيقات', 'correct' => 1 ),
							array( 'title' => 'لأنها مادة إجبارية فقط', 'correct' => 0 ),
							array( 'title' => 'لإصلاح الكمبيوتر', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما هي الخطوة الأولى في أي برنامج؟',
						'answers' => array(
							array( 'title' => 'كتابة الكود مباشرة', 'correct' => 0 ),
							array( 'title' => 'فهم المشكلة وتحديد الخطوات', 'correct' => 1 ),
							array( 'title' => 'تشغيل البرنامج', 'correct' => 0 ),
							array( 'title' => 'حذف الأخطاء', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'الكمبيوتر يفهم لغة واحدة فقط، ما هي؟',
						'answers' => array(
							array( 'title' => 'لغة جافا', 'correct' => 0 ),
							array( 'title' => 'لغة الآلة (Binary - أصفار وواحدات)', 'correct' => 1 ),
							array( 'title' => 'اللغة العربية', 'correct' => 0 ),
							array( 'title' => 'لغة Python', 'correct' => 0 ),
						),
					),
				),
			),
			array(
				'title'      => 'اختبار - قصة لغة جافا',
				'menu_order' => 4,
				'shift_from' => 4,
				'questions'  => array(
					array(
						'title'   => 'من الذي أنشأ لغة جافا؟',
						'answers' => array(
							array( 'title' => 'Bill Gates', 'correct' => 0 ),
							array( 'title' => 'Mark Zuckerberg', 'correct' => 0 ),
							array( 'title' => 'James Gosling', 'correct' => 1 ),
							array( 'title' => 'Elon Musk', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'في أي سنة تم إصدار لغة جافا رسميًا؟',
						'answers' => array(
							array( 'title' => '1991', 'correct' => 0 ),
							array( 'title' => '1995', 'correct' => 1 ),
							array( 'title' => '2000', 'correct' => 0 ),
							array( 'title' => '1985', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما اسم المشروع الأصلي الذي تطورت منه جافا؟',
						'answers' => array(
							array( 'title' => 'Coffee', 'correct' => 0 ),
							array( 'title' => 'Oak', 'correct' => 1 ),
							array( 'title' => 'Tree', 'correct' => 0 ),
							array( 'title' => 'Sun', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما الشركة التي طورت جافا في البداية؟',
						'answers' => array(
							array( 'title' => 'Microsoft', 'correct' => 0 ),
							array( 'title' => 'Google', 'correct' => 0 ),
							array( 'title' => 'Sun Microsystems', 'correct' => 1 ),
							array( 'title' => 'Apple', 'correct' => 0 ),
						),
					),
					array(
						'title'   => '(مراجعة) ما هي البرمجة ببساطة؟',
						'answers' => array(
							array( 'title' => 'تصميم مواقع فقط', 'correct' => 0 ),
							array( 'title' => 'إعطاء أوامر للكمبيوتر لتنفيذها', 'correct' => 1 ),
							array( 'title' => 'استخدام الإنترنت', 'correct' => 0 ),
							array( 'title' => 'تشغيل الألعاب', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما هو شعار جافا الشهير؟',
						'answers' => array(
							array( 'title' => 'Code Once, Fix Everywhere', 'correct' => 0 ),
							array( 'title' => 'Write Once, Run Anywhere', 'correct' => 1 ),
							array( 'title' => 'Build Fast, Break Things', 'correct' => 0 ),
							array( 'title' => 'Code and Coffee', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما الشركة التي تملك جافا حاليًا؟',
						'answers' => array(
							array( 'title' => 'Google', 'correct' => 0 ),
							array( 'title' => 'Microsoft', 'correct' => 0 ),
							array( 'title' => 'Oracle', 'correct' => 1 ),
							array( 'title' => 'Amazon', 'correct' => 0 ),
						),
					),
				),
			),
			array(
				'title'      => 'اختبار - هنكتب كود فين',
				'menu_order' => 7,
				'shift_from' => 7,
				'questions'  => array(
					array(
						'title'   => 'ما هو الـ IDE؟',
						'answers' => array(
							array( 'title' => 'لغة برمجة جديدة', 'correct' => 0 ),
							array( 'title' => 'بيئة تطوير متكاملة لكتابة وتشغيل الكود', 'correct' => 1 ),
							array( 'title' => 'نوع من أنواع الكمبيوتر', 'correct' => 0 ),
							array( 'title' => 'موقع لتعلم البرمجة', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'أي من التالي يعتبر IDE يمكن استخدامه مع جافا؟',
						'answers' => array(
							array( 'title' => 'Photoshop', 'correct' => 0 ),
							array( 'title' => 'IntelliJ IDEA', 'correct' => 1 ),
							array( 'title' => 'Excel', 'correct' => 0 ),
							array( 'title' => 'PowerPoint', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما هو الـ JDK؟',
						'answers' => array(
							array( 'title' => 'برنامج لتصميم المواقع', 'correct' => 0 ),
							array( 'title' => 'حزمة أدوات تطوير جافا اللازمة لكتابة وتشغيل الكود', 'correct' => 1 ),
							array( 'title' => 'لغة برمجة مختلفة', 'correct' => 0 ),
							array( 'title' => 'نظام تشغيل', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'لماذا نحتاج تثبيت JDK قبل البرمجة بجافا؟',
						'answers' => array(
							array( 'title' => 'لتصميم واجهات فقط', 'correct' => 0 ),
							array( 'title' => 'لأنه يحتوي على المترجم (Compiler) الذي يحول الكود لبرنامج', 'correct' => 1 ),
							array( 'title' => 'لتشغيل الألعاب', 'correct' => 0 ),
							array( 'title' => 'لا نحتاجه', 'correct' => 0 ),
						),
					),
					array(
						'title'   => '(مراجعة) من أنشأ لغة جافا؟',
						'answers' => array(
							array( 'title' => 'Linus Torvalds', 'correct' => 0 ),
							array( 'title' => 'James Gosling', 'correct' => 1 ),
							array( 'title' => 'Dennis Ritchie', 'correct' => 0 ),
							array( 'title' => 'Guido van Rossum', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما الفرق بين JDK و JRE؟',
						'answers' => array(
							array( 'title' => 'لا فرق بينهم', 'correct' => 0 ),
							array( 'title' => 'JDK للتطوير والبرمجة و JRE لتشغيل البرامج فقط', 'correct' => 1 ),
							array( 'title' => 'JRE أحدث من JDK', 'correct' => 0 ),
							array( 'title' => 'JDK مجاني و JRE مدفوع', 'correct' => 0 ),
						),
					),
					array(
						'title'   => '(مراجعة) ما معنى "Write Once, Run Anywhere"؟',
						'answers' => array(
							array( 'title' => 'الكود يعمل على نظام واحد فقط', 'correct' => 0 ),
							array( 'title' => 'تكتب الكود مرة واحدة ويعمل على أي نظام تشغيل', 'correct' => 1 ),
							array( 'title' => 'تكتب الكود مرة ولا يمكن تعديله', 'correct' => 0 ),
							array( 'title' => 'الكود يعمل بدون إنترنت فقط', 'correct' => 0 ),
						),
					),
				),
			),
			array(
				'title'      => 'اختبار - البداية في لغة جافا',
				'menu_order' => 10,
				'shift_from' => 10,
				'questions'  => array(
					array(
						'title'   => 'ما هو أول سطر في أي برنامج جافا؟',
						'answers' => array(
							array( 'title' => 'import java.util', 'correct' => 0 ),
							array( 'title' => 'public class ClassName', 'correct' => 1 ),
							array( 'title' => 'System.out.println', 'correct' => 0 ),
							array( 'title' => 'void main', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما هي الدالة الرئيسية التي يبدأ منها تنفيذ البرنامج؟',
						'answers' => array(
							array( 'title' => 'public void start()', 'correct' => 0 ),
							array( 'title' => 'public static void main(String[] args)', 'correct' => 1 ),
							array( 'title' => 'public void run()', 'correct' => 0 ),
							array( 'title' => 'static void begin()', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ماذا يفعل System.out.println()؟',
						'answers' => array(
							array( 'title' => 'يقرأ بيانات من المستخدم', 'correct' => 0 ),
							array( 'title' => 'يطبع نص في الكونسول وينزل سطر جديد', 'correct' => 1 ),
							array( 'title' => 'يحذف ملف', 'correct' => 0 ),
							array( 'title' => 'يوقف البرنامج', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ما الفرق بين print و println؟',
						'answers' => array(
							array( 'title' => 'لا فرق', 'correct' => 0 ),
							array( 'title' => 'println ينزل لسطر جديد بعد الطباعة و print لا', 'correct' => 1 ),
							array( 'title' => 'print أسرع', 'correct' => 0 ),
							array( 'title' => 'println يطبع أرقام فقط', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'أي كود صحيح لطباعة "Hello World"؟',
						'answers' => array(
							array( 'title' => 'System.out.println(Hello World);', 'correct' => 0 ),
							array( 'title' => 'System.out.println("Hello World");', 'correct' => 1 ),
							array( 'title' => 'system.out.println("Hello World");', 'correct' => 0 ),
							array( 'title' => 'System.Out.Println("Hello World");', 'correct' => 0 ),
						),
					),
					array(
						'title'   => '(مراجعة) ما هو الـ IDE؟',
						'answers' => array(
							array( 'title' => 'لغة برمجة', 'correct' => 0 ),
							array( 'title' => 'بيئة تطوير متكاملة لكتابة الكود', 'correct' => 1 ),
							array( 'title' => 'نظام تشغيل', 'correct' => 0 ),
							array( 'title' => 'قاعدة بيانات', 'correct' => 0 ),
						),
					),
					array(
						'title'   => 'ماذا يحدث لو نسيت الفاصلة المنقوطة (;) في نهاية السطر في جافا؟',
						'answers' => array(
							array( 'title' => 'البرنامج يعمل عادي', 'correct' => 0 ),
							array( 'title' => 'يحدث خطأ في الكومبايل (Compilation Error)', 'correct' => 1 ),
							array( 'title' => 'البرنامج يعمل ببطء', 'correct' => 0 ),
							array( 'title' => 'يتم تجاهل السطر', 'correct' => 0 ),
						),
					),
				),
			),
		);

		echo '<!DOCTYPE html><html dir="rtl"><head><meta charset="UTF-8">';
		echo '<style>body{font-family:sans-serif;padding:20px;direction:rtl;} .ok{color:green;} .skip{color:orange;} .err{color:red;} table{border-collapse:collapse;margin-bottom:20px;} th,td{border:1px solid #ccc;padding:5px 10px;text-align:left;} th{background:#f0f0f0;}</style>';
		echo '</head><body>';
		echo '<h2>إنشاء الاختبارات — Topic ID ' . esc_html( $topic_id ) . '</h2>';

		$created_ids = array();
		$now         = current_time( 'mysql' );
		$now_gmt     = current_time( 'mysql', true );
		$author_id   = get_current_user_id();

		foreach ( $quizzes as $quiz_def ) {
			$quiz_title  = $quiz_def['title'];
			$menu_order  = $quiz_def['menu_order'];
			$shift_from  = $quiz_def['shift_from'];

			// Check if quiz already exists under this topic.
			$existing_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts}
					 WHERE post_parent = %d
					   AND post_type = 'tutor_quiz'
					   AND post_title = %s
					 LIMIT 1",
					$topic_id,
					$quiz_title
				)
			);

			if ( $existing_id ) {
				echo '<p class="skip">تم تخطي: <strong>' . esc_html( $quiz_title ) . '</strong> — موجود بالفعل (ID: ' . esc_html( $existing_id ) . ')</p>';
				$created_ids[] = array( 'title' => $quiz_title, 'id' => $existing_id, 'status' => 'skipped' );
				continue;
			}

			// Shift existing topic children with menu_order >= shift_from up by 1.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts}
					 SET menu_order = menu_order + 1
					 WHERE post_parent = %d
					   AND menu_order >= %d",
					$topic_id,
					$shift_from
				)
			);

			// Insert the quiz post.
			$inserted = $wpdb->insert(
				$wpdb->posts,
				array(
					'post_author'           => $author_id,
					'post_date'             => $now,
					'post_date_gmt'         => $now_gmt,
					'post_content'          => $quiz_note,
					'post_title'            => $quiz_title,
					'post_excerpt'          => '',
					'post_status'           => 'publish',
					'comment_status'        => 'closed',
					'ping_status'           => 'closed',
					'post_name'             => sanitize_title( $quiz_title ) . '-' . wp_generate_password( 4, false ),
					'post_modified'         => $now,
					'post_modified_gmt'     => $now_gmt,
					'post_parent'           => $topic_id,
					'menu_order'            => $menu_order,
					'post_type'             => 'tutor_quiz',
					'post_mime_type'        => '',
					'comment_count'         => 0,
					'guid'                  => '',
				),
				array(
					'%d', '%s', '%s', '%s', '%s', '%s', '%s',
					'%s', '%s', '%s', '%s', '%s', '%d', '%d',
					'%s', '%s', '%d', '%s',
				)
			);

			if ( ! $inserted ) {
				echo '<p class="err">خطأ في إنشاء: <strong>' . esc_html( $quiz_title ) . '</strong> — ' . esc_html( $wpdb->last_error ) . '</p>';
				continue;
			}

			$quiz_id = $wpdb->insert_id;

			// Update guid to include the real ID.
			$wpdb->update(
				$wpdb->posts,
				array( 'guid' => get_option( 'siteurl' ) . '/?post_type=tutor_quiz&p=' . $quiz_id ),
				array( 'ID' => $quiz_id ),
				array( '%s' ),
				array( '%d' )
			);

			// Store quiz options in post meta.
			$quiz_options = array(
				'time_limit'                         => array(
					'time_value' => 0,
					'time_type'  => 'minutes',
				),
				'hide_quiz_time_display'             => false,
				'feedback_mode'                      => 'default',
				'attempts_allowed'                   => 0,
				'max_questions_for_answer'           => 0,
				'quiz_auto_start'                    => false,
				'question_layout_view'               => '',
				'questions_order'                    => 'sorting',
				'short_answer_characters_limit'      => 200,
				'open_ended_answer_characters_limit' => 500,
				'passing_grade'                      => 70,
				'quiz_note'                          => $quiz_note,
			);

			add_post_meta( $quiz_id, '_tutor_quiz_option', $quiz_options );

			// Insert questions and answers.
			$q_order = 0;
			foreach ( $quiz_def['questions'] as $q ) {
				$q_order++;

				$q_settings = serialize(
					array(
						'question_mark'      => '1',
						'answer_required'    => false,
						'randomize_question' => false,
						'show_question_mark' => false,
					)
				);

				$wpdb->insert(
					$wpdb->prefix . 'tutor_quiz_questions',
					array(
						'quiz_id'              => $quiz_id,
						'question_title'       => $q['title'],
						'question_description' => '',
						'question_type'        => 'multiple_choice',
						'answer_explanation'   => '',
						'question_mark'        => 1,
						'question_order'       => $q_order,
						'question_settings'    => $q_settings,
					),
					array( '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' )
				);

				$question_id = $wpdb->insert_id;

				if ( ! $question_id ) {
					echo '<p class="err">خطأ في إنشاء سؤال: ' . esc_html( $q['title'] ) . ' — ' . esc_html( $wpdb->last_error ) . '</p>';
					continue;
				}

				$a_order = 0;
				foreach ( $q['answers'] as $ans ) {
					$a_order++;
					$wpdb->insert(
						$wpdb->prefix . 'tutor_quiz_question_answers',
						array(
							'belongs_question_id'   => $question_id,
							'belongs_question_type' => 'multiple_choice',
							'answer_title'          => $ans['title'],
							'is_correct'            => $ans['correct'],
							'image_id'              => 0,
							'answer_two_gap_match'  => '',
							'answer_view_format'    => 'text',
							'answer_settings'       => '',
							'answer_order'          => $a_order,
						),
						array( '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d' )
					);
				}
			}

			echo '<p class="ok">تم إنشاء: <strong>' . esc_html( $quiz_title ) . '</strong> — Quiz ID: <strong>' . esc_html( $quiz_id ) . '</strong></p>';
			$created_ids[] = array( 'title' => $quiz_title, 'id' => $quiz_id, 'status' => 'created' );
		}

		echo '<hr><h3>ملخص النتائج</h3><ul>';
		foreach ( $created_ids as $r ) {
			$label = 'created' === $r['status'] ? '✔ تم إنشاؤه' : '↩ تم تخطيه (موجود)';
			echo '<li>' . esc_html( $label ) . ' — ' . esc_html( $r['title'] ) . ' (ID: ' . esc_html( $r['id'] ) . ')</li>';
		}
		echo '</ul></body></html>';

	} catch ( Exception $e ) {
		echo '<p style="color:red;">Error: ' . esc_html( $e->getMessage() ) . '</p>';
	}

	die();
}
