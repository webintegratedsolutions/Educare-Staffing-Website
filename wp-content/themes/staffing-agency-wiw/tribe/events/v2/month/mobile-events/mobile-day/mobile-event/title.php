<?php
/**
 * View: Month View - Mobile Event Title
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/month/mobile-events/mobile-day/mobile-event/title.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://evnt.is/1aiy
 *
 * @version 5.0.0
 *
 * @var WP_Post $event The event post object with properties added by the `tribe_get_event` function.
 *
 * @see tribe_get_event() For the format of the event object.
 */

//Get the current global post
global $post;

$shift_meta_shift_id = get_post_meta( $post->ID, 'Shift ID' );
$shift_meta_employee_name = get_post_meta( $post->ID, 'Employee Name' );
$shift_meta_position = get_post_meta( $post->ID, 'Shift Position' );
$shift_meta_shift_room = get_post_meta( $post->ID, 'Shift Room' );
$shift_meta_shift_status = get_post_meta( $post->ID, 'Shift Status' );

$additional_data = array();
$additional_data['shift_id'] = $shift_meta_shift_id[0];
$additional_data['employee_name'] = $shift_meta_employee_name[0];
$additional_data['shift_position'] = $shift_meta_position[0];
$additional_data['shift_room'] = $shift_meta_shift_room[0];
$additional_data['shift_status'] = $shift_meta_shift_status[0];

$classes = [ 'tribe-events-calendar-month-mobile-events__mobile-event-title', 'tribe-common-h7' ];

?>
<? if ($additional_data['shift_status']=="closed") { ?>
	<div class="tribe-events-calendar-month__calendar-event-tooltip-view-school"><?php echo $additional_data['shift_room']; ?></div>
	<div class="tribe-events-calendar-month__calendar-event-tooltip-view-employee"><?php echo $additional_data['employee_name']; ?></div>
	<div class="tribe-events-calendar-month__calendar-event-tooltip-view-position"><?php echo $additional_data['shift_position']; ?></div>
<? } else { ?>
	<div class="tribe-events-open-alert">Open Shift</div>
	<div class="tribe-events-calendar-month__calendar-event-tooltip-view-school"><?php echo $additional_data['shift_room']; ?></div>
<? } ?>
