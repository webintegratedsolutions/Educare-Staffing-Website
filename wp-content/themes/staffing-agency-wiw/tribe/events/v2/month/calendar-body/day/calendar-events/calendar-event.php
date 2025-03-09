<?php
/**
 * View: Month Calendar Event
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/month/calendar-body/day/calendar-events/calendar-event.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://evnt.is/1aiy
 *
 * @since 5.0.0
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

$classes = tribe_get_post_class( [ 'tribe-events-calendar-month__calendar-event' ], $event->ID );

$classes['tribe-events-calendar-month__calendar-event--featured'] = ! empty( $event->featured );
$classes['tribe-events-calendar-month__calendar-event--sticky']   = ( -1 === $event->menu_order );
?>

<article <?php tribe_classes( $classes ) ?>>

	<?php $this->template( 'month/calendar-body/day/calendar-events/calendar-event/featured-image', [ 'event' => $event ] ); ?>

	<div class="tribe-events-calendar-month__calendar-event-details <? if ($additional_data['shift_status']=="open") { ?>tribe-events-open-alert<? } ?>">

		<?php $this->template( 'month/calendar-body/day/calendar-events/calendar-event/date', [ 'event' => $event ] ); ?>
		<?php $this->template( 'month/calendar-body/day/calendar-events/calendar-event/title', [ 'event' => $event ] ); ?>

		<?php $this->template( 'month/calendar-body/day/calendar-events/calendar-event/tooltip', [ 'event' => $event ] ); ?>

	</div>

</article>
