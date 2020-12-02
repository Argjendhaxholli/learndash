<?php
/**
 * LearnDash Assignments (sfwd-assignment) Posts Listing Class.
 *
 * @package LearnDash
 * @subpackage admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Posts_Listing' ) ) && ( ! class_exists( 'Learndash_Admin_Assignments_Listing' ) ) ) {
	/**
	 * Class for LearnDash Assignments Listing Pages.
	 */
	class Learndash_Admin_Assignments_Listing extends Learndash_Admin_Posts_Listing {

		/**
		 * Public constructor for class
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'assignment' );

			parent::__construct();
		}

		/**
		 * Called via the WordPress init action hook.
		 */
		public function listing_init() {
			$this->selectors = array(
				'author'          => array(
					'type'                     => 'user',
					'show_all_value'           => '',
					'show_all_label'           => esc_html__( 'All Authors', 'learndash' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_author' ),
					'selector_value_function'  => array( $this, 'selector_value_for_author' ),
				),
				'approval_status' => array(
					'type'                   => 'early',
					'show_all_value'         => '',
					'show_all_label'         => esc_html__( 'Approval Status', 'learndash' ),
					'options'                => array(
						'approved'     => esc_html__( 'Approved', 'learndash' ),
						'not_approved' => esc_html__( 'Not Approved', 'learndash' ),
					),
					'listing_query_function' => array( $this, 'filter_by_approval_status' ),
					'select2'                => true,
					'select2_fetch'          => false,
				),
				'group_id'        => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'group' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'All %s', 'placeholder: Groups', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'groups' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_group' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_group' ),
					'selector_value_function'  => array( $this, 'selector_value_for_group' ),
				),
				'course_id'       => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'course' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'All %s', 'placeholder: Courses', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'courses' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_course' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_course' ),
					'selector_value_function'  => array( $this, 'selector_value_for_course' ),
					'selector_filters'         => array( 'group_id' ),
				),
				'lesson_id'       => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'lesson' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Lessons.
						esc_html_x( 'All %s', 'placeholder: Lessons', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lessons' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_lesson' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_lesson' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
					'selector_filters'         => array( 'course_id' ),
				),
				'topic_id'        => array(
					'type'                     => 'post_type',
					'post_type'                => learndash_get_post_type_slug( 'topic' ),
					'show_all_value'           => '',
					'show_all_label'           => sprintf(
						// translators: placeholder: Topics.
						esc_html_x( 'All %s', 'placeholder: Topics', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'topics' )
					),
					'listing_query_function'   => array( $this, 'listing_filter_by_topic' ),
					'selector_filter_function' => array( $this, 'selector_filter_for_topic' ),
					'selector_value_function'  => array( $this, 'selector_value_integer' ),
					'selector_filters'         => array( 'course_id', 'lesson_id' ),
				),
			);

			$this->columns = array(
				'approval_status' => array(
					'label'   => esc_html__( 'Status / Points', 'learndash' ),
					'after'   => 'author',
					'display' => array( $this, 'show_column_assignment_approval_status' ),
				),
				'course'          => array(
					'label'    => sprintf(
						// translators: Assigned Course Label.
						esc_html_x( 'Assigned %s', 'Assigned Course Label', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'after'    => 'approval_status',
					'display'  => array( $this, 'show_column_step_course' ),
					'required' => true,
				),
				'lesson_topic'    => array(
					'label'   => sprintf(
						// translators: Placeholders: Lesson, Topic.
						esc_html_x( 'Assigned %1$s / %2$s', 'Placeholders: Lesson, Topic', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' ),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'after'   => 'course',
					'display' => array( $this, 'show_column_step_lesson_or_topic' ),
				),
			);

			parent::listing_init();
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				parent::on_load_listing();

				add_action( 'admin_footer', array( $this, 'assignment_bulk_actions' ), 30 );
				add_filter( 'learndash_listing_table_query_vars_filter', array( $this, 'listing_table_query_vars_filter_assignments' ), 30, 3 );

				//add_filter( 'views_edit-' . learndash_get_post_type_slug( 'assignment' ), array( $this, 'edit_list_table_views' ), 10, 1 );

				$this->assignment_bulk_actions_approve();
			}
		}

		/** This function is documented in includes/admin/class-learndash-admin-posts-listing.php */
		public function listing_table_query_vars_filter_assignments( $q_vars, $post_type, $query ) {
			if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
				$gl_user_ids = learndash_get_groups_administrators_users( get_current_user_id() );
				if ( ! empty( $gl_user_ids ) ) {
					$q_vars['author__in'] = $gl_user_ids;
				} else {
					$q_vars['author__in'] = array( 0 );
				}
			}
			return $q_vars;
		}

		/**
		 * Show the assignment Approval Status.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     Assignment Post ID.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_assignment_approval_status( $post_id = 0, $column_meta = array() ) {
			$post_id = absint( $post_id );
			if ( ! empty( $post_id ) ) {
				$lesson_id = intval( get_post_meta( $post_id, 'lesson_id', true ) );
				if ( ! empty( $lesson_id ) ) {
					$approval_status_flag = learndash_is_assignment_approved_by_meta( $post_id );
					if ( 1 == $approval_status_flag ) {
						$approval_status_slug  = 'approved';
						$approval_status_label = _x( 'Approved', 'Assignment approval status', 'learndash' );
					} else {
						$approval_status_slug  = 'not_approved';
						$approval_status_label = _x( 'Not Approved', 'Assignment approval status', 'learndash' );
					}

					echo '<div class="ld-approval-status">' . sprintf(
						// translators: placeholder: Status label, Status value.
						esc_html_x( '%1$s: %2$s', 'placeholder: Status label, Status value', 'learndash' ),
						'<span class="learndash-listing-row-field-label">' . esc_html__( 'Status', 'learndash' ) . '</span>',
						esc_html( $approval_status_label )
					) . '</div>';

					echo '<div class="ld-approval-points">';
					if ( learndash_assignment_is_points_enabled( $post_id ) ) {
						$max_points = 0;
						$max_points = learndash_get_setting( $lesson_id, 'lesson_assignment_points_amount' );

						$current_points = get_post_meta( $post_id, 'points', true );
						if ( 1 != $approval_status_flag ) {
							echo sprintf(
								// translators: placeholders: Points label, points input, maximum points.
								esc_html_x( '%1$s: %2$s / %3$d', 'placeholders: Points label, points input, maximum points', 'learndash' ),
								'<label class="learndash-listing-row-field-label" for="assignment_points_' . absint( $post_id ) . '">' . esc_html__( 'Points', 'learndash' ) . '</label>',
								'<input id="assignment_points_' . absint( $post_id ) . '" class="small-text" type="number" value="' . absint( $current_points ) . '" max="' . absint( $max_points ) . '" min="0" step="1" name="assignment_points[' . absint( $post_id ) . ']" />',
								absint( $max_points )
							);
						} else {
							echo sprintf(
								// translators: placeholders: Points label, current points, maximum points.
								esc_html_x( '%1$s: %2$d / %3$d', 'placeholders: Points label, points input, maximum points', 'learndash' ),
								'<span class="learndash-listing-row-field-label">' . esc_html__( 'Points', 'learndash' ) . '</span>',
								absint( $current_points ),
								absint( $max_points )
							);
						}
					} else {
						echo sprintf(
							// translators: placeholder: Points label.
							esc_html_x( '%s: Not Enabled', 'placeholder: Points label', 'learndash' ),
							'<span class="learndash-listing-row-field-label">' . esc_html__( 'Points', 'learndash' ) . '</span>'
						);
					}
					echo '</div>';

					if ( 1 != $approval_status_flag ) {
						?>
						<div class="ld-approval-action">
							<button id="assignment_approve_<?php echo esc_attr( $post_id ); ?>" class="small assignment_approve_single"><?php esc_html_e( 'approve', 'learndash' ); ?></button>
						</div>
						<?php
					}
				}
			}
		}

		/**
		 * Adds a 'Approve' option next to certain selects on assignment edit screen in admin.
		 *
		 * Fires on `admin_footer` hook.
		 *
		 * @global WP_Post $post Global post object.
		 *
		 * @todo  check if needed, jQuery selector seems incorrect
		 *
		 * @since 2.1.0
		 */
		public function assignment_bulk_actions() {
			global $post;

			if ( ( ! empty( $post->post_type ) ) && ( learndash_get_post_type_slug( 'assignment' ) === $post->post_type ) ) {
				$approve_text = esc_html__( 'Approve', 'learndash' );
				?>
					<script type="text/javascript">
						jQuery( function() {
							jQuery('<option>').val('approve_assignment').text('<?php echo esc_attr( $approve_text ); ?>').appendTo("select[name='action']");
							jQuery('<option>').val('approve_assignment').text('<?php echo esc_attr( $approve_text ); ?>').appendTo("select[name='action2']");
						});
					</script>
				<?php
			}
		}

		/**
		 * Handles approval of assignments in bulk.
		 *
		 * @since 2.1.0
		 */
		protected function assignment_bulk_actions_approve() {

			if ( ( ! isset( $_REQUEST['ld-listing-nonce'] ) ) || ( empty( $_REQUEST['ld-listing-nonce'] ) ) || ( ! wp_verify_nonce( $_REQUEST['ld-listing-nonce'], get_called_class() ) ) ) {
				return;
			}

			if ( ( ! isset( $_REQUEST['post'] ) ) || ( empty( $_REQUEST['post'] ) ) || ( ! is_array( $_REQUEST['post'] ) ) ) {
				return;
			}

			if ( ( ! isset( $_REQUEST['post_type'] ) ) || ( learndash_get_post_type_slug( 'assignment' ) !== $_REQUEST['post_type'] ) ) {
				return;
			}

			$action = '';
			if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
				$action = esc_attr( $_REQUEST['action'] );

			} elseif ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
				$action = esc_attr( $_REQUEST['action2'] );

			} elseif ( ( isset( $_REQUEST['ld_action'] ) ) && ( 'approve_assignment' === $_REQUEST['ld_action'] ) ) {
				$action = 'approve_assignment';
			}

			if ( 'approve_assignment' === $action ) {
				if ( ( isset( $_REQUEST['post'] ) ) && ( ! empty( $_REQUEST['post'] ) ) ) {
					if ( ! is_array( $_REQUEST['post'] ) ) {
						$assignments = array( $_REQUEST['post'] );
					} else {
						$assignments = $_REQUEST['post'];
					}

					foreach ( $assignments as $assignment_id ) {

						$assignment_post = get_post( $assignment_id );
						if ( ( ! empty( $assignment_post ) ) && ( is_a( $assignment_post, 'WP_Post' ) ) && ( learndash_get_post_type_slug( 'assignment' ) === $assignment_post->post_type ) ) {

							$user_id   = absint( $assignment_post->post_author );
							$lesson_id = get_post_meta( $assignment_post->ID, 'lesson_id', true );

							if ( learndash_assignment_is_points_enabled( $assignment_id ) === true ) {

								if ( ( isset( $_REQUEST['assignment_points'] ) ) && ( isset( $_REQUEST['assignment_points'][ $assignment_id ] ) ) ) {
									$assignment_points = absint( $_REQUEST['assignment_points'][ $assignment_id ] );

									$assignment_settings_id = intval( get_post_meta( $assignment_id, 'lesson_id', true ) );
									if ( ! empty( $assignment_settings_id ) ) {
										$max_points = learndash_get_setting( $assignment_settings_id, 'lesson_assignment_points_amount' );
									}

									// Double check the assiged points is NOT larger than max points.
									if ( $assignment_points > $max_points ) {
										$assignment_points = $max_points;
									}

									update_post_meta( $assignment_id, 'points', $assignment_points );
								}
							}

							learndash_approve_assignment( $user_id, $lesson_id, $assignment_id );
						}
					}
				}
			}
		}

		/**
		 * This function fill filter the table listing items based on filters selected.
		 * Called via 'parse_query' filter from WP.
		 *
		 * @since 3.2.0
		 *
		 * @param  object $q_vars      Query vars used for the table listing
		 * @return object $q_vars.
		 */
		protected function filter_by_approval_status( $q_vars, $selector ) {

			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ! isset( $q_vars['meta_query'] ) ) {
					$q_vars['meta_query'] = array();
				}

				if ( 'approved' === $selector['selected'] ) {
					$q_vars['meta_query'][] = array(
						'key'   => 'approval_status',
						'value' => 1,
					);
				} elseif ( 'not_approved' === $selector['selected'] ) {
					$q_vars['meta_query'][] = array(
						'key'     => 'approval_status',
						'compare' => 'NOT EXISTS',
					);
				}
			}

			return $q_vars;
		}

		/**
		 * Hides the list table views for non admin users.
		 *
		 * Fires on `views_edit-sfwd-essays` and `views_edit-sfwd-assignment` hook.
		 *
		 * @param array $views Optional. An array of available list table views. Default empty array.
		 */
		public function edit_list_table_views( $views = array() ) {
			if ( ! learndash_is_admin_user() ) {
				$views = array();
			}

			return $views;
		}

		// End of functions.
	}
}
new Learndash_Admin_Assignments_Listing();
