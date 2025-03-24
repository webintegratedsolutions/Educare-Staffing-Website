<?php
/**
 * Single Event Meta (Details) Template
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe-events/modules/meta/details.php
 *
 * @link http://evnt.is/1aiy
 *
 * @package TribeEventsCalendar
 *
 * @version 4.6.19
 */


$event_id             = Tribe__Main::post_id_helper();
$time_format          = get_option( 'time_format', Tribe__Date_Utils::TIMEFORMAT );
$time_range_separator = tec_events_get_time_range_separator();
$show_time_zone       = tribe_get_option( 'tribe_events_timezones_show_zone', false );
$local_start_time     = tribe_get_start_date( $event_id, true, Tribe__Date_Utils::DBDATETIMEFORMAT );
$time_zone_label      = Tribe__Events__Timezones::is_mode( 'site' ) ? Tribe__Events__Timezones::wp_timezone_abbr( $local_start_time ) : Tribe__Events__Timezones::get_event_timezone_abbr( $event_id );

$start_datetime = tribe_get_start_date();
$start_date = tribe_get_start_date( null, false );
$start_time = tribe_get_start_date( null, false, $time_format );
$start_ts = tribe_get_start_date( null, false, Tribe__Date_Utils::DBDATEFORMAT );

$end_datetime = tribe_get_end_date();
$end_date = tribe_get_display_end_date( null, false );
$end_time = tribe_get_end_date( null, false, $time_format );
$end_ts = tribe_get_end_date( null, false, Tribe__Date_Utils::DBDATEFORMAT );

$time_formatted = null;
if ( $start_time == $end_time ) {
	$time_formatted = esc_html( $start_time );
} else {
	$time_formatted = esc_html( $start_time . $time_range_separator . $end_time );
}

/**
 * Returns a formatted time for a single event
 *
 * @var string Formatted time string
 * @var int Event post id
 */
$time_formatted = apply_filters( 'tribe_events_single_event_time_formatted', $time_formatted, $event_id );

/**
 * Returns the title of the "Time" section of event details
 *
 * @var string Time title
 * @var int Event post id
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
$time_title = apply_filters( 'tribe_events_single_event_time_title', __( 'Time:', 'the-events-calendar' ), $event_id );

$cost    = tribe_get_formatted_cost();
$website = tribe_get_event_website_link( $event_id );
$website_title = tribe_events_get_event_website_title();
?>

<div class="tribe-events-meta-group tribe-events-meta-group-details">
	<h2 class="tribe-events-single-section-title"> <?php esc_html_e( 'Details', 'the-events-calendar' ); ?> </h2><br />
	<dl>
		<?php
		do_action( 'tribe_events_single_meta_details_section_start' );
		// Single day events
			?>

<? if ($additional_data['shift_status']=="closed") { ?>
	<dt class="tribe-events-start-date-label"> <?php esc_html_e( 'Room:', 'the-events-calendar' ); ?> </dt>
	<dd>
		<abbr class="tribe-events-abbr tribe-events-start-date published dtstart" title="<?php echo esc_attr( $additional_data['shift_room'] ); ?>"> <?php echo esc_html( $additional_data['shift_room'] ); ?> </abbr>
	</dd>
	<dt class="tribe-events-start-date-label"> <?php esc_html_e( 'Employee:', 'the-events-calendar' ); ?> </dt>
	<dd>
		<abbr class="tribe-events-abbr tribe-events-start-date published dtstart" title="<?php echo esc_attr( $additional_data['employee_name'] ); ?>"> <?php echo esc_html( $additional_data['employee_name'] ); ?> </abbr>
	</dd>
	<dt class="tribe-events-start-date-label"> <?php esc_html_e( 'Employee:', 'the-events-calendar' ); ?> </dt>
	<dd>
		<abbr class="tribe-events-abbr tribe-events-start-date published dtstart" title="<?php echo esc_attr( $additional_data['shift_position'] ); ?>"> <?php echo esc_html( $additional_data['shift_position'] ); ?> </abbr>
	</dd>
<? } else { ?>
	<div class="tribe-events-open-alert">Open Shift</div>
	<div class="tribe-events-calendar-month__calendar-event-tooltip-view-school"><?php echo $additional_data['shift_room']; ?></div>
<? } ?>

		<?php
		/**
		 * Included an action where we inject Series information about the event.
		 *
		 * @since 6.0.0
		 */
		do_action( 'tribe_events_single_meta_details_section_after_datetime' );
		?>

		<?php
		// Event Cost
		if ( ! empty( $cost ) ) : ?>

			<dt class="tribe-events-event-cost-label"> <?php esc_html_e( 'Cost:', 'the-events-calendar' ); ?> </dt>
			<dd class="tribe-events-event-cost"> <?php echo esc_html( $cost ); ?> </dd>
		<?php endif ?>

		<?php
		// Event Categories
		/*
		echo tribe_get_event_categories(
			get_the_id(),
			[
				'before'       => '',
				'sep'          => ', ',
				'after'        => '',
				'label'        => null, // An appropriate plural/singular label will be provided
				'label_before' => '<dt class="tribe-events-event-categories-label">',
				'label_after'  => '</dt>',
				'wrap_before'  => '<dd class="tribe-events-event-categories">',
				'wrap_after'   => '</dd>',
			]
		);
		*/
		?>

		<?php
		tribe_meta_event_archive_tags(
			/* Translators: %s: Event (singular) */
			sprintf(
				esc_html__( '%s Tags:', 'the-events-calendar' ),
				tribe_get_event_label_singular()
			),
			', ',
			true
		);
		?>

		<?php
		// Event Website
		if ( ! empty( $website ) ) : ?>
			<?php if ( ! empty( $website_title ) ): ?>
				<dt class="tribe-events-event-url-label"> <?php echo esc_html( $website_title ); ?> </dt>
			<?php endif; ?>
			<dd class="tribe-events-event-url"> <?php echo $website; ?> </dd>
		<?php endif ?>

		<?php do_action( 'tribe_events_single_meta_details_section_end' ); ?>
	</dl>
</div>
