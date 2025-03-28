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

const ACTION_MODE = 1;

//Require Utility Functions
require("utility-functions.php");

global $adminMsg;

// Get current URL
$url = $_SERVER['REQUEST_URI'];

if ( strpos($url, '/update-calendar-shifts/') !== false || strpos($url, '/administration/') !== false ) {
    require("wheniwork-config.php");
    $wiw = new Wheniwork($myLoginToken);

    //Require API Functions
    require("api-functions.php");

     //Require Calendar Functions
	 require("dashboard-shortcodes.php");
}

// Check if URL or is Update Calendar Shifts page or Administration page or scheduled WP-Cron event
if ( strpos($url, '/update-calendar-shifts/') !== false) {
	if (!isset($_GET['auth_token']) || $_GET['auth_token'] !== ADMIN_CRON_SECRET) {
		die('❌ Unauthorized access');
	} else {
        // Require Calendar Functions
        require_once("calendar-functions.php");
        // Run the function to update calendar shifts
        add_action('wp_loaded', 'updateDeletedNewCalendarShifts');
	}
}

/************************************************************************
Update Deleted and New Calendar Shifts from When I Work API
************************************************************************/
function updateDeletedNewCalendarShifts() {

    //If user is not an admin then login When I Work Autobot
    if (!current_user_can('manage_options')) {
        // The user is an admin
        loginAutobot();
    }

    //set Global admin message var
	global $adminMsg;

    //Begin admin messages header
	$adminMsg = "<h2>Update Calendar Shifts Report</h2><hr />\n";

    //Get calendar shifts from WordPress Storage
	$listingShiftsResult = getShiftsListingResult();

	//Get calendar shifts from WordPress Storage
	$calendar_shift_ids = getCalendarShiftIds();

	//Get When I Work API Shift ID's
	$wiw_shift_ids = getlistingShiftIDs($listingShiftsResult);

    //Add headers and details to admin messages string
	$adminMsg .= "<h4>API Shift Result</h4>\n";
	$adminMsg .= "When I Work Listing Shift - Total Count: <strong>" . count($listingShiftsResult->shifts) . "</strong><hr /><br />\n";
	$adminMsg .=  "<h4>Current Calendar Shifts</h4>";
	$adminMsg .=  "Calendar Shift IDs - Total Count: <strong>" . count($calendar_shift_ids) . "</strong><hr /><br />\n";

    //If there are any deleted or Updated Shifts in When I Work they need to be removed first prior to a clean update
	//Remove Deleted Shifts
	$adminMsg .= "<h4>Remove Deleted Calendar Shifts</h4><hr />";
	$shiftDelCount = removeCalendarShifts($wiw_shift_ids, $calendar_shift_ids);

    //If there were shifts to be deleted that were removed, get the updated Calendar Shift ID's
	if($shiftDelCount){
		$adminMsg .= "<strong>" . $shiftDelCount . " deleted shifts were removed from the calendar.</strong><hr />\n";
		$calendar_shift_ids = getCalendarShiftIds();
		$adminMsg .= "<h4>Current Calendar Shifts</h4>";
		$adminMsg .= "Calendar Shift IDs - Total Count: " . count($calendar_shift_ids) . "<br />\n";
	} else {
		$adminMsg .= "There were no deleted shifts to be removed from the calendar.";
	}

	//Remove Updated Shifts
	$adminMsg .= "<hr /><h4>Remove Updated Calendar Shifts</h4><hr />";
	$updatedShiftDelCount = removeUpdatedCalendarShifts($listingShiftsResult, $calendar_shift_ids);

    //If there were shifts to be updated that were removed, get the updated Calendar Shift ID's
	if($updatedShiftDelCount){
		$adminMsg .= "<strong>" . $updatedShiftDelCount . " updated shifts were removed from the calendar.</strong><hr />\n";
		$calendar_shift_ids = getCalendarShiftIds();
		$adminMsg .= "<h4>Current Client Calendar Shifts</h4>";
		$adminMsg .= "Shift ID's - Total Count (After Calendar Removal): <strong>" . count($calendar_shift_ids) . "</strong><br />\n";
	} else {
		//Get calendar shifts after removed shifts
		$adminMsg .= "There were no updated shifts to be removed from the calendar.";
	}

    //If there are calendar shifts from When I Work to be added to WordPress storage
    //Greater than 60 is a buffer to ensure that client IDs are loading. There should never be less than 60 shifts on average around 150.
	if(count($calendar_shift_ids)<count($listingShiftsResult->shifts)&&count($calendar_shift_ids)>60){

        //Get Employee (When I Work Users)
	    $employee_records = getlistingUsersResult();

        //Count the difference in amount of shifts
	    $shiftsToBeUpdatedCount = count($listingShiftsResult->shifts)-count($calendar_shift_ids);
	    $adminMsg .= "<p><strong>There are " . $shiftsToBeUpdatedCount . " shifts to be added to client calendars.</strong></p><hr />\n";
	    if (ACTION_MODE===2){

		} else {
		    $adminMsg .= addNewCalendarShifts($employee_records, $listingShiftsResult, $calendar_shift_ids, $wiw_shift_ids);
		    $adminMsg .= '<div class="admin-report"><p><small><strong>End of Update Calendar Shifts Admin Report</strong></small><p>' . "<hr /></div>\n";
		}
	} else {
        	$adminMsg .= "<p><strong>The client calendars are all up-to-date.</strong></p><hr />\n";
	}

	//If there are updates, send Admin Calendar Update Report to Webmaster by Email
	$adminMsg .= sendAdminCalendarUpdateReport($adminMsg);

	//Add shortcode to be used on When I Work Admin Dashboard
	add_shortcode( 'wiw_dashboard', function(){ global $adminMsg; return $adminMsg; });

    //if wiw_autobot is logged in then log out
    logoutAutobot();

	//sendClientConfirmationEmails();

}

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

//add_action( 'wiw_cron_action_confimation_emails', 'sendClientConfirmationEmails' );

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
