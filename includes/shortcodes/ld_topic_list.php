<?php
/**
 * Shortcode for ld_topic_list
 *
 * @since 2.1.0
 *
 * @package LearnDash\Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Builds the `ld_topic_list` shortcode output.
 *
 * @global boolean $learndash_shortcode_used
 *
 * @since 2.1.0
 *
 * @param array $attr Optional. An array of shortcode attributes. Default empty array. @see ld_course_list()
 *
 * @return string The `ld_topic_list` shortcode output.
 */
function ld_topic_list( $attr = array() ) {
	global $learndash_shortcode_used;
	$learndash_shortcode_used = true;

	if ( ! is_array( $attr ) ) {
		$attr = array();
	}

	$attr['post_type'] = learndash_get_post_type_slug( 'topic' );
	$attr['mycourses'] = false;
	$attr['status']    = false;

	// If we have a course_id. Then we set the orderby to match the items within the course.
	if ( ( isset( $attr['course_id'] ) ) && ( ! empty( $attr['course_id'] ) ) ) {
		$attr['course_id'] = absint( $attr['course_id'] );

		$course_steps = array();

		if ( isset( $attr['lesson_id'] ) ) {
			$attr['lesson_id'] = absint( $attr['lesson_id'] );
			if ( ! empty( $attr['lesson_id'] ) ) {
				$course_steps = learndash_get_topic_list( $attr['lesson_id'], $attr['course_id'] );
				if ( ! empty( $course_steps ) ) {
					$course_steps = wp_list_pluck( $course_steps, 'ID' );
				}
			} else {
				$course_steps = learndash_course_get_steps_by_type( intval( $attr['course_id'] ), $attr['post_type'] );
			}
		} else {
			$course_steps = learndash_course_get_steps_by_type( intval( $attr['course_id'] ), $attr['post_type'] );
		}

		if ( ! empty( $course_steps ) ) {
			$attr['post__in'] = $course_steps;
			// if ( !isset( $attr['orderby'] ) ) $attr['orderby'] = 'post__in';
		}
		if ( ! isset( $attr['order'] ) ) {
			$attr['order'] = 'ASC';
		}
		if ( ! isset( $attr['orderby'] ) ) {
			$attr['orderby'] = 'title';
		}
	}

	return ld_course_list( $attr );
}

add_shortcode( 'ld_topic_list', 'ld_topic_list' );
