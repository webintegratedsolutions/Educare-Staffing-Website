<?php
/**
 * Plugin Name: WIW Functions
 * Description: When I Work API Shortcode Functions
 * Version: 0.1
 * Text Domain: wiw-functions
 * Author: Julian Nyte
 */

// DEFINE GLOBAL VARIABLES



 //Require When I Work Config (Including and API Class)
//include When I Work API Class

//Require Utility Functions
require("utility-functions.php");

const ACTION_MODE = 1;

if (ACTION_MODE===2){
	echo "<h3>ACTION_MODE: 2</h3>\n";
}

global $adminMsg;

$url = get_current_url();

if ( strpos($url, '/administration/') !== false || strpos($url, '/dashboard/') !== false || strpos($url, '/test-page/') !== false || strpos($url, '/email-confirmations/') !== false  || strpos($url, '/my-shifts/') !== false) { 

	require("wheniwork-config.php");
	$wiw = new Wheniwork($myLoginToken);

	//Require API Functions
	require("api-functions.php");

	//Require Calendar Functions
	require("calendar-functions.php");

	//Require Calendar Functions
	require("dashboard-shortcodes.php");
	add_action('plugins_loaded', 'updateDeletedNewCalendarShifts');

}

/************************************************************************
Update Deleted and New Calendar Shifts from When I Work API
************************************************************************/
function updateDeletedNewCalendarShifts() {

	global $adminMsg;

	$adminMsg = "<h2>Update Calendar Shifts Report</h2><hr />\n";

	//Get Employee (When I Work Users)
	$employee_records = getlistingUsersResult();

	$listingShiftsResult = getShiftsListingResult();

	//Get calendar shifts
	$calendar_shift_ids = getCalendarShiftIds();

	//Get When I Work API Shift ID's
	$wiw_shift_ids = getlistingShiftIDs($listingShiftsResult); 
	print_r($wiw_shift_ids);

	$adminMsg .= "<h4>API Shift Result</h4>\n";
	$adminMsg .= "When I Work Listing Shift - Total Count: <strong>" . count($listingShiftsResult->shifts) . "</strong><hr /><br />\n";

	$adminMsg .=  "<h4>Current Calendar Shifts</h4>";
	$adminMsg .=  "Calendar Shift IDs - Total Count: <strong>" . count($calendar_shift_ids) . "</strong><hr /><br />\n";
	
	//Remove Deleted Shifts
	$adminMsg .= "<h4>Remove Deleted Calendar Shifts</h4><hr />";
	$shiftDelCount = removeCalendarShifts($wiw_shift_ids, $calendar_shift_ids);

	if($shiftDelCount){
		$adminMsg .= "<strong>" . $shiftDelCount . " deleted shifts were removed from the calendar.</strong><hr />\n";
		$calendar_shift_ids = getCalendarShiftIds();
		$adminMsg .= "<h4>Current Calendar Shifts</h4>";
		$adminMsg .= "Calendar Shift IDs - Total Count: " . count($calendar_shift_ids) . "<br />\n";
	} else {
		//Get calendar shifts after removed shifts
		$adminMsg .= "There were no deleted shifts to be removed from the calendar.";
	}

	//Remove Deleted Shifts
	$adminMsg .= "<hr /><h4>Remove Updated Calendar Shifts</h4><hr />";
	$updatedShiftDelCount = removeUpdatedCalendarShifts($listingShiftsResult, $calendar_shift_ids);
	
	if($updatedShiftDelCount){
		$adminMsg .= "<strong>" . $updatedShiftDelCount . " updated shifts were removed from the calendar.</strong><hr />\n";
		$calendar_shift_ids = getCalendarShiftIds();
		$adminMsg .= "<h4>Current Client Calendar Shifts</h4>";
		$adminMsg .= "Shift ID's - Total Count (After Calendar Removal): <strong>" . count($calendar_shift_ids) . "</strong><br />\n";
	} else {
		//Get calendar shifts after removed shifts
		$adminMsg .= "There were no updated shifts to be removed from the calendar.";
	}

	//Conditions to Update Calendar Shifts
	//If Admin, then ensure that at least 50 shifts exist in database to make updates (to ensure against bad updates based on client filters)
	if(current_user_can('edit_pages')&&count($calendar_shift_ids)<count($listingShiftsResult->shifts)){
		$shiftsToBeUpdatedCount = count($listingShiftsResult->shifts)-count($calendar_shift_ids);
		$adminMsg .= "<hr /><p><strong>There are " . $shiftsToBeUpdatedCount . " shifts to be added to client calendars.</strong></p><hr />\n";
		if (ACTION_MODE===2){

		} else {
			$adminMsg .= addNewCalendarShifts($employee_records, $listingShiftsResult, $calendar_shift_ids, $wiw_shift_ids);
			$adminMsg .= '<div class="admin-report"><p><small><strong>End of Update Calendar Shifts Admin Report</strong></small><p>' . "<hr /></div>\n";
		}
	} else if(!current_user_can('edit_pages')&&count($calendar_shift_ids)>40&&count($calendar_shift_ids)<count($listingShiftsResult->shifts)){
		$shiftsToBeUpdatedCount = count($listingShiftsResult->shifts)-count($calendar_shift_ids);
		$adminMsg .= "<hr /><p><strong>There are " . $shiftsToBeUpdatedCount . " shifts to be added to client calendars.</strong></p><hr />\n";
		if (ACTION_MODE===2){

		} else {
			$adminMsg .= addNewCalendarShifts($employee_records, $listingShiftsResult, $calendar_shift_ids, $wiw_shift_ids);
			$adminMsg .= '<div class="admin-report"><p><small><strong>End of Update Calendar Shifts Admin Report</strong></small><p>' . "<hr /></div>\n";
			$adminMsg = '<h4>Client Calendar Shift Update</h4>' . "<hr />\n" . $adminMsg;
		}
	} else {
		$adminMsg .= "<hr /><p><strong>The client calendars are all up-to-date.</strong></p><hr />\n";
	}

	//echo $adminMsg;

	//Send Admin Calendar Update Report to Webmaster by Email
	$adminMsg .= sendAdminCalendarUpdateReport($adminMsg);

	//echo $adminMsg;

	//Add shortcode to be used on When I Work Admin Dashboard
	add_shortcode( 'wiw_dashboard', function(){ global $adminMsg; return $adminMsg; });

	//sendClientConfirmationEmails();

}
add_action( 'wiw_cron_action', 'updateDeletedNewCalendarShifts' );


/************************************************************************
Send Confirmation Emails
************************************************************************/
function sendClientConfirmationEmails() {

	global $adminConfirmationEmailsMsg;

	$adminConfirmationEmailsMsg = "<h2>Send Confirmation Emails Report</h2><hr />\n";
	$adminConfirmationEmailsMsg .= "<p>WP Refferrer: " . wp_get_referer() . "</p><hr />\n";

	//Get calendar shifts
	$calendar_shift_ids = getCalendarShiftIds();
	$client_records = getlistingJobSitesResult();

	$sendClientConfirmationEmailCount = 0;
	foreach ($calendar_shift_ids as $shift_id){

		$post_shift_id = get_post_id_by_meta_key_and_value("Shift ID", $shift_id);
		$shift_confirmation_email = get_post_meta($post_shift_id, 'Confirmation Email');
		$shiftAckowledged = "";

		if (!get_post_meta($post_shift_id, 'Acknowleded')){
			$shiftAckowledged = "0";
		} else {
			$shiftAckowledged = "1";
		}

		if(get_post_meta($post_shift_id, 'Confirmation Email')[0]==0&&get_post_meta($post_shift_id, 'Shift Status')[0]=="closed"){

			$sendClientConfirmationEmailCount++;

			$client_id = get_post_meta($post_shift_id, 'Client ID')[0];

			foreach ($client_records->sites as $site) {
				if ($site->id==$client_id){
					$client_name = $site->name;
					break;
				}
			}

			$args = array(
				'meta_query' => array(
					array(
						'Client Account Number' => $client_id ,
						'value' => $client_id,
						'compare' => '='
					)
				)
			);
			 
			$client_arr = get_users($args);
			$client_email = $client_arr[0]->user_email;
			$shift_id = get_post_meta($post_shift_id, 'Shift ID')[0];
			$shift_coverage_title = get_post_meta($post_shift_id, 'Shift Coverage Title')[0];
			$employee_name = get_post_meta($post_shift_id, 'Employee Name')[0];
			$created_at = get_post_meta($post_shift_id, 'Created At')[0];
			$shift_room = get_post_meta($post_shift_id, 'Shift Room')[0];
			$shift_position = get_post_meta($post_shift_id, 'Shift Position')[0];

			$adminConfirmationEmailsMsg .= "<p><strong>Shift Confirmation Email Send Count: " . $sendClientConfirmationEmailCount. "</strong><br />\n";
			$adminConfirmationEmailsMsg .= "Shift ID: ". $shift_id ."<br />\n";
			$adminConfirmationEmailsMsg .= "Created At: ". $created_at . "<br />\n";
			$adminConfirmationEmailsMsg .= "Updated At: ". get_post_meta($post_shift_id, 'Updated At')[0] . "<br />\n";
			$adminConfirmationEmailsMsg .= "Shift Coverage Title: ". $shift_coverage_title ."<br />\n";
			$adminConfirmationEmailsMsg .= "Employee Name: ". $employee_name ."<br />\n";
			$adminConfirmationEmailsMsg .= "Acknowleded: ". $shiftAckowledged . "<br />\n";
			$adminConfirmationEmailsMsg .= "Acknowleded At: ". date("Y-m-d H:i:s",get_post_meta($post_shift_id, 'Acknowleded At')[0]) . "<br />\n";
			$adminConfirmationEmailsMsg .= "Shift Position: ". get_post_meta($post_shift_id, 'Shift Position')[0] ."<br />\n";
			$adminConfirmationEmailsMsg .= "Shift Room: ". $shift_room ."<br />\n";
			$adminConfirmationEmailsMsg .= "Shift Continuous: ". get_post_meta($post_shift_id, 'Shift Continuous')[0] ."<br />\n";
			$adminConfirmationEmailsMsg .= "Client ID: ". get_post_meta($post_shift_id, 'Client ID')[0] ."<br />\n";
			$adminConfirmationEmailsMsg .= "Client Name: ". $client_name ."<br />\n";
			$adminConfirmationEmailsMsg .= "Client Email: ". $client_email ."<br />\n";
			$adminConfirmationEmailsMsg .= sendConfirmationMsg($shift_coverage_title, $shift_id, $employee_name, $employee_email, $post_shift_id, $shift_room);
			$adminConfirmationEmailsMsg .= "<br />\n";
		}

	}

	$adminConfirmationEmailsMsg .= "</p></body></html>";

	if($sendClientConfirmationEmailCount>1){
		$subject = 'Admin Confirmation Emails Message';
		$headers = 'From: Educare Staffing Services <admin@educarestaffing.ca>' . "\r\n";
		$headers .= 'MIME-Version: 1.0' . "\r\n";
		$headers .= 'Content-type: text/html; charset=iso-8859-1';
		$to = "webmaster@educarestaffing.com";
		wp_mail($to, $subject, $adminConfirmationEmailsMsg, $headers);
		echo $adminConfirmationEmailsMsg;
	} else {
		"<P>There were no conformation emails to be sent.</p>\n";
	}

}

add_action( 'wiw_cron_action_confimation_emails', 'sendClientConfirmationEmails' );

function sendConfirmationMsg($shift_coverage_title, $shift_id, $employee_name, $employee_email, $shift_post_id, $shift_room) {

    $adminMsg = '<html><body>';
    $subject = 'Shift coverage for '. $shift_coverage_title;
    $subject .= ' - '. $shift_room;
    $headers = 'From: Educare Staffing Services <admin@educarestaffing.ca>' . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1';
    $confirmation_email_to = $to = "webmaster@educarestaffing.com";
  
    //PDF File Attachment
    $attachment = "/home/www/educarestaffing.com/wp-content/employee_profiles/Personal File - " . $employee_name . ".pdf";

    if (file_exists($attachment)){
        //set message for confirmation email
        $confirmation_message = "Hello,<br /><br />" . $employee_name . " will cover the above mentioned shift. The employee's personal file is attached for your consideration.<br /><br />Best Regards,<br />Educare Staffing Services";
        if (wp_mail($to, $subject, $confirmation_message, $headers, $attachment)) {	
            http_response_code(200);
            update_post_meta($shift_post_id,'Confirmation Email', "1");
            $adminMsg .= "Confirmation email successfully sent for shift ID: " . $shift_id . "\n";
            $adminMsg .= '</body></html>';
         } else {
            $subject = 'Confirmation Email Error - For Shift ID: '. $shift_id;
            $adminMsg .= "<hr /><br />There was an unknown email error sending confirmation message after this shift that has been added to the client calendar for shift ID: " . $shift_id . "\n";
            $adminMsg .= '</body></html>';
            wp_mail($to, $subject, $adminMsg, $headers);
            update_post_meta($shift_post_id,'Confirmation Email', "0");
        }
    } else {
        $subject = 'Confirmation Email Error - For Shift ID: '. $shift_id;
        $adminMsg .= "<h4>Error Message:</h4>\n";
        $adminMsg .= "<strong>There was an error sending confirmation message for shift ID: " . $shift_id . " that has been added to the client calendar</strong>.<br /><br />";
        $adminMsg .= "A PDF attachment could not be found for the following employee: " . $employee_name;
        $adminMsg .= "<br /><br />To fix this by the next automatic confirmation, ensure that this employee profile PDF exists in the folder (/wp-content/employee_profiles/).";
        $adminMsg .= '</body></html>';
        wp_mail($to, $subject, $adminMsg, $headers);
        update_post_meta($shift_post_id,'Confirmation Email', "0");
    }

	return $adminMsg;
  
}











// // Fetch data from When I Work API
// function fetch_when_i_work_data() {

	
// 	//include When I Work API Class
// 	require("wheniwork.php");
	
// 	//call to When I Work API login function
// 	$loginResult = Wheniwork::login(
// 	"d3edc80bffb2c0299b92a1a4a2fef7422d47c325",
// 	"nytej1@gmail.com",
// 	"wiwPASS777!@"
// 	);
// 	//request When I Work shift
// 	$myLoginToken = $loginResult->login->{'token'};
	
	
//     $api_url = 'https://api.wheniwork.com/2/shifts?start=2024-01-01&end=2025-12-31'; // Example endpoint
//     $api_key = 'd3edc80bffb2c0299b92a1a4a2fef7422d47c325'; // Replace with your actual API key

// 	$response_login = wp_remote_get('https://api.wheniwork.com/2/login?show_pending=true', [
// 	        'headers' => [
// 	            'Authorization' => 'Bearer '.$myLoginToken,
// 	            'Accept' => 'application/json',
// 	        ],
// 	    ]);
// 	    $body_login = wp_remote_retrieve_body($response_login);
// 	    $data_login = json_decode($body_login, true);
//     $response = wp_remote_get($api_url, [
//         'headers' => [
//             'Authorization' => 'Bearer '.$myLoginToken,
//             'W-UserId'=>$data_login['users'][0]['id'],
//             'Accept' => 'application/json',
//         ],
//     ]);
    

//     if (is_wp_error($response)) {
//     echo 'EMPTY';
//         return [];
//     }

//     $body = wp_remote_retrieve_body($response);
//     $data = json_decode($body, true);

//     return $data['shifts'] ?? [];
// }

// function get_or_create_venue() {
//         // Create or get a default venue
//         $venue_id = get_option('wiw_default_venue_id');
        
//         if (!$venue_id) {
//             $venue_data = array(
//                 'post_title'    => 'Default Venue',
//                 'post_type'     => 'tribe_venue',
//                 'post_status'   => 'publish'
//             );
            
//             $venue_id = wp_insert_post($venue_data);
//             update_option('wiw_default_venue_id', $venue_id);
//         }
        
//         return '95769';
//     }
    
//     function find_existing_event($shift_id) {

//         $args = array(
//             'post_type' => 'tribe_events',
//             'meta_key' => 'wiw_shift_id',
//             'meta_value' => $shift_id,
//             'posts_per_page' => 1
//         );

//         $query = new WP_Query($args);
        
       
//         if ($query->have_posts()) {
//             return true;
//         }

//         return false;
//     }
    
    
    
//     function clear_all_caches($event_id) {
//     // Clear The Events Calendar cache

//     // Clear specific event cache
//     clean_post_cache($event_id);

//     // Clear tribe events cache
//     delete_transient('tribe_events_calendar_widget_day_post_ids');
//     delete_transient('tribe_events_calendar_widget_month_post_ids');

//     // Clear all transients related to The Events Calendar
//     global $wpdb;
//     $wpdb->query(
//         "DELETE FROM $wpdb->options 
//         WHERE option_name LIKE '%tribe_events%' 
//         AND option_name LIKE '%transient%'"
//     );

//     // Clear rewrite rules
//     flush_rewrite_rules(false);

//     // Clear object cache for this post type
//     wp_cache_delete('latest_posts', 'tribe_events');
//     wp_cache_delete('all_ids', 'tribe_events');

//     // Force calendar update
//     delete_transient('tribe_events_calendar_update_key');
    
//     // Clear Tribe's cache for the month
//     $date = new DateTime($shift['start_time']);
//     $month_key = $date->format('Y-m');
//     delete_transient('tribe_get_events_' . $month_key);

//     // Trigger an action that The Events Calendar can hook into
//     do_action('tribe_events_after_event_save', $event_id);
    
//         wp_remote_get(get_site_url() . '/events/month/', array('timeout' => 0.01));
//     wp_remote_get(get_site_url() . '/events/list/', array('timeout' => 0.01));
//         wp_remote_get(get_site_url() . '/my-calendar', array('timeout' => 0.01));
// }
// // Sync When I Work data to The Events Calendar
// function sync_when_i_work_to_events_calendar() {
//     //echo "Fetching schedules from When I Work API...<br>";
//     define('WP_DEBUG', true);
// define('WP_DEBUG_LOG', true);
//     if (!is_admin()) return;
    
//     $args = array(
//             'post_type' => 'tribe_events',
//                         'meta_key' => 'wiw_shift_id',
//             'meta_value' => '3458944389',
//             'posts_per_page' => 1
//         );

//         $query = new WP_Query($args);
        
       
//         if ($query->have_posts()) {

//     $schedules = fetch_when_i_work_data();
    
//    // echo 'SCHEDULES:'.$schedules;

//     if (empty($schedules)) {
//         echo "No schedules found or an error occurred.<br>";
//         return;
//     }

//     echo "Found " . count($schedules) . " schedules.<br>";

//     foreach ($schedules as $schedule) {
//         echo "Processing schedule: " . $schedule['position']['name'] . "<br>";
        
//          $event_data = array(
//             'post_title'    => $schedule['position']['name'] . ' Shift',
//             'post_content'  => $schedule['notes'],
//             'post_status'   => 'publish',
//             'post_type'     => 'tribe_events',
//             'meta_input'    => array(
//                 '_EventStartDate'    => $schedule['start_time'],
//                 '_EventEndDate'      => $schedule['end_time'],
//                 '_EventVenueID'      => get_or_create_venue(),
//                 '_EventAllDay' => 'yes',
//                 'wiw_shift_id'       => $schedule['id']
//             )
//         );

//   $existing_event = find_existing_event($schedule['id']);
//         echo 'EVENT: '.json_encode($event_data) . 'EXISTS:'.json_encode($existing_event);
//         if ($existing_event) {
//            // $event_data['ID'] = $existing_event;
//           // wp_update_post($event_data);
//         } else {
//               $event_id = wp_insert_post($event_data);
//               }
// }
// }
// //    echo "Sync completed.<br>";
// }

// Run the sync function manually or on a schedule