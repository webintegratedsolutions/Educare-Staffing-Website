<?php

//Function to retrieve all calendar shift ID's from WordPress Storage
function getCalendarShiftIds() {
    global $wp_query;

    error_log("🔍 Running getCalendarShiftIds()...");

    if (!function_exists('tribe_get_events')) {
        error_log("⚠️ Tribe events not loaded, attempting manual include...");
        if (file_exists(WP_PLUGIN_DIR . '/the-events-calendar/the-events-calendar.php')) {
            include_once WP_PLUGIN_DIR . '/the-events-calendar/the-events-calendar.php';
        }
    }

    if (!post_type_exists('tribe_events')) {
        error_log("⚠️ Tribe Events post type missing, attempting manual registration...");
        do_action('tribe_events_register_post_types');
    }

    if (empty($wp_query)) {
        error_log("⚠️ WP_Query is empty, initializing...");
        global $wp;
        $wp_query = new WP_Query();
    }

    $current_shifts = tribe_get_events([
        'posts_per_page' => -1,
        'post_status'    => 'publish'
    ]);

    if (empty($current_shifts)) {
        error_log("🚨 No shifts found via tribe_get_events(), trying direct WP_Query...");
        $args = [
            'post_type'      => 'tribe_events',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        ];
        $query = new WP_Query($args);
        $current_shifts = $query->posts;
    }

    if (empty($current_shifts)) {
        error_log("🚨 Still no events found! Tribe Events might not be loaded properly.");
    } else {
        error_log("✅ Found " . count($current_shifts) . " shifts.");
    }

    $calendar_shift_ids = [];
    foreach ($current_shifts as $shift) {
        $shift_meta_shift_id = get_post_meta($shift->ID, 'Shift ID', true);
        if (!empty($shift_meta_shift_id)) {
            $calendar_shift_ids[] = $shift_meta_shift_id;
        }
    }

    error_log("✅ Returning " . count($calendar_shift_ids) . " shift IDs.");
    return $calendar_shift_ids;
}

//Function to retrieve all calendar shift ID's through When I Work API
function getlistingShiftIDs($listingShiftsResult){
    if(!$listingShiftsResult){
        return NULL;
    }
    $wiw_shift_ids = Array();
    foreach ($listingShiftsResult->shifts as $shift) {
        $wiw_shift_ids[] = $shift->id;
    }
    return $wiw_shift_ids;
}

//Function to remove all calendar shifts from WordPress that were updated on When I Work (Prior to clean update)
function removeUpdatedCalendarShifts($listingShiftsResult, $calendar_shift_ids){
	$updatedShiftDelCount = 0;
	foreach ($listingShiftsResult->shifts as $shift) {
		if (in_array($shift->id, $calendar_shift_ids)){ 
			$post_shift_id = get_post_id_by_meta_key_and_value("Shift ID", $shift->id);
			$shift_meta_updated_at = get_post_meta($post_shift_id, 'Updated At');
			$post_shift_updated_at = $shift_meta_updated_at[0];
			$wiw_shift_updated_at = $shift->updated_at;
			if ($post_shift_updated_at != $wiw_shift_updated_at){
				if(removeCalendarShift($post_shift_id)){
					$updatedShiftDelCount++;
				}
			}
		}
	}
	return $updatedShiftDelCount;
}

//Function to remove all calendar shifts from WordPress that were deleted When I Work
function removeCalendarShifts($wiw_shift_ids, $calendar_shift_ids){
	$shiftDelCount = 0;
	foreach ($calendar_shift_ids as $shift_id){
		$post_shift_id = get_post_id_by_meta_key_and_value("Shift ID", $shift_id);
		if (!in_array($shift_id, $wiw_shift_ids)){
			if(removeCalendarShift($post_shift_id)){
				$shiftDelCount++;
			}
		}
	}
	return $shiftDelCount;
}

//Function to remove a single calendar shift from WordPress storage
function removeCalendarShift($post_shift_id){
	//Remove shift from Current Calendar Shifts
	if(wp_delete_post($post_shift_id, true)){
		//echo "<div><p>Shift ID: " . $shift_id . " has been deleted.</p></div>\n";
		return true;
	} else {
		//echo "<div><p>Shift ID: " . $shift_id . " failed to be deleted.</p></div>\n";
		return false;
	}
	//Remove shift from Current Calendar Shifts Array
}

// Function to add a Calendar Shift to WordPress Storage retrieved through When I Work API
function addCalendarShift($shift_record, $employee_record){

    $shiftAddedMsg = "";

    $shift_id = $shift_record->id;
    $shift_notes = $shift_record->notes;
    $shift_room = "";
    $shift_continuous = "false";

    if (strpos($shift_notes, 'Kindergarten') !== false) {
        $shift_room = 'Kindergarten';
    } else if (strpos($shift_notes, 'Preschool') !== false) {
        $shift_room = 'Preschool';
    } else if (strpos($shift_notes, 'Infant') !== false) {
        $shift_room = 'Infant';
    } else if (strpos($shift_notes, 'Float') !== false) {
        $shift_room = 'Float';
    } else if (strpos($shift_notes, 'Toddler') !== false) {
        $shift_room = 'Toddler';
    } else if (strpos($shift_notes, 'Schoolage') !== false) {
        $shift_room = 'Schoolage';
    } else {
        $shift_room = 'Any';
    }

    if (strpos($shift_notes, '(Continuous)') !== false) {
        $shift_continuous = "true";
    }

    $employee_name = $employee_record["first_name"] . " " . $employee_record["last_name"];
    $employee_email = $employee_record["email"];

    $shift_position = "";
    if ($employee_record["position"][0] == "2611462") {
        $shift_position = "ECA";
    } elseif ($employee_record["position"][0] == "2611465") {
        $shift_position = "RECE";
    } else {
        $shift_position = "No Position";
    }

    $shift_date = date("Y-m-d", strtotime($shift_record->start_time));
    $shift_start_time = date("g:i", strtotime($shift_record->start_time));
    $shift_end_time = date("g:i", strtotime($shift_record->end_time));
    $shift_start_hour = date("g", strtotime($shift_record->start_time));
    $shift_end_hour = date("g", strtotime($shift_record->end_time));
    $shift_start_minute = date("i", strtotime($shift_record->start_time));
    $shift_end_minute = date("i", strtotime($shift_record->end_time));
    $shift_start_meridian = date("a", strtotime($shift_record->start_time));
    $shift_end_meridian = date("a", strtotime($shift_record->end_time));
    $shift_title = $shift_start_time . " " . $shift_start_meridian . " - " . $shift_end_time . " " . $shift_end_meridian;
    $shift_created_at = $shift_record->created_at;
    $shift_updated_at = $shift_record->updated_at;
    $shift_acknowledged = $shift_record->acknowledged;
    $shift_acknowledged_at = $shift_record->acknowledged_at;
    $shift_site_id = $shift_record->site_id;
    $category_id = get_term_by('name', "cid_" . $shift_site_id, 'tribe_events_cat');
    $client_sid = $category_id->term_id ?? '';

    $shiftAddedMsg .= "Shift Date: " . $shift_date . " - " . $shift_title . "<br />\n";
    $shiftAddedMsg .= "Employee Name: " . $employee_name . "<br />\n";
    $shiftAddedMsg .= "Room: " . $shift_room . "<br />\n";
    $shiftAddedMsg .= "Position: " . $shift_position . "<br />\n";
    $shiftAddedMsg .= "Created At: " . date("M, d, Y - G:i:s", strtotime($shift_created_at)) . "<br />\n";

    $shift_is_open = $shift_record->is_open;
    if ($shift_is_open == 1) {
        $shift_open_status = "open";
        $shiftAddedMsg .= "(Created as an open shift to be acknowledged by an employee.)<br />\n";
    } else {
        $shift_open_status = "closed";
        $shiftAddedMsg .= "(Closed shift assigned to employee.)<br />\n";
    }

    $shiftAddedMsg .= "Last updated At: " . date("M, d, Y - G:i:s", strtotime($shift_updated_at)) . "<br />\n";
    $shiftAddedMsg .= "Shift ID: " . $shift_id . "<br />\n";
    $shiftAddedMsg .= "Client ID (Job Site ID): " . $shift_site_id . "<br />\n";
    $shiftAddedMsg .= "Client SID: " . $client_sid . "<br />\n";

    $shift_coverage_title = date("F j - g:i a", strtotime($shift_start_time)) . ' to ' . date("g:i a", strtotime($shift_end_time));

    // Create post object
    $shift_data = array(
        'post_type' => 'tribe_events',
        'post_title' => $shift_title,
        'post_content' => $shift_position,
        'post_status' => 'publish',
        'post_author' => 62, // WIW AUTOBOT - User ID 62
        'EventStartDate' => $shift_date,
        'EventEndDate' => $shift_date,
        'EventStartHour' => $shift_start_hour,
        'EventStartMinute' => $shift_start_minute,
        'EventStartMeridian' => $shift_start_meridian,
        'EventEndHour' => $shift_end_hour,
        'EventEndMinute' => $shift_end_minute,
        'EventEndMeridian' => $shift_end_meridian,
        'tax_input' => array( Tribe__Events__Main::TAXONOMY => array( '11', $client_sid ))
    );

    $shift_post_id = tribe_create_event($shift_data);
    update_post_meta($shift_post_id, 'Shift ID', $shift_id);
    update_post_meta($shift_post_id,'Shift ID', $shift_id);
  	update_post_meta($shift_post_id,'Employee Name', $employee_name);
  	update_post_meta($shift_post_id,'Shift Position', $shift_position);
  	update_post_meta($shift_post_id,'Shift Room', $shift_room);
  	update_post_meta($shift_post_id,'Created At', $shift_created_at);
  	update_post_meta($shift_post_id,'Updated At', $shift_updated_at);
  	update_post_meta($shift_post_id,'Acknowledged', $shift_acknowledged);
  	update_post_meta($shift_post_id,'Acknowledged At', $shift_acknowledged_at);
	update_post_meta($shift_post_id,'Shift Status', $shift_open_status);
	update_post_meta($shift_post_id,'Shift Continuous', $shift_continuous);
	update_post_meta($shift_post_id,'Client ID', $shift_site_id);
	update_post_meta($shift_post_id,'Confirmation Email', "0");
	update_post_meta($shift_post_id,'Shift Coverage Title', $shift_coverage_title);

    $shiftAddedMsg .= "<div><p>Shift ID: <strong>" . $shift_id . "</strong> was successfully added to calendar.</p></div>\n";
	if(!$shift_notes){
		$shiftAddedMsg .= "<div><p>There were no shift notes published with this shift.</p></div>\n";
	} else {
		$shiftAddedMsg .= "<div><p>Shift Notes: <strong>" . $shift_notes . "</strong></p></div>\n";
	}

	//Shift confirmation emails
	//If shift is not open do not send confirmation email to client
	if($shift_open_status == "open"){
		$shiftAddedMsg .= "<p>Shift status is <strong>open</strong> - a confirmation email will not be sent.</p>\n";
		$shiftAddedMsg .= "<hr />\n";
		//update_post_meta($shift_post_id,'Confirmation Email', "1");
	} else {
		$shiftAddedMsg .= "<p>Shift status is <strong>closed</strong> - a confirmation email will be sent to client.</p>\n";
		//$shiftAddedMsg .= sendShiftConfirmationEmail($shift_record, $employee_record, $shiftAddedMsg, $shift_post_id, $shift_room);
		$shiftAddedMsg .= "<hr />\n";
	}
    return $shiftAddedMsg;
}

// Function to loop through and update all Calendar Shifts to WordPress Storage
function addNewCalendarShifts($employee_records, $listingShiftsResult, $calendar_shift_ids, $wiw_shift_ids) {

    // Set Counter for total Shifts updated count
    $newShiftsCount = 0;

    // Set String for administrator message
    $addedShiftMsg = "";
    $addedShiftMsg .= "<h4>Add New Shifts to Calendar:</h4><hr />\n";

    foreach ($listingShiftsResult->shifts as $shift) {
        $shift_id = (int) $shift->id;

        // Ensure the shift is NOT already in WordPress storage
        if (!in_array($shift_id, array_map('intval', $calendar_shift_ids), true)) {
            $newShiftsCount++;
            $employee_record = getEmployeeByID($shift->user_id, $employee_records);

            // Logging for debugging
            error_log("✅ Adding new shift: ID {$shift_id}");

            $addedShiftMsg .= "<strong>{$newShiftsCount} - Shift ID: {$shift_id}</strong> needs to be added to calendar.<br /><br />\n";

            if (!get_post_id_by_meta_key_and_value("Shift ID", $shift->id)){
                // Add the shift to WordPress storage
                $addedShiftMsg .= addCalendarShift($shift, $employee_record);
            }

        } else {
            // Shift already exists, log and skip
            error_log("❌ Skipping duplicate shift: ID {$shift_id} (Already Exists)");
            $addedShiftMsg .= "❌ Skipping Duplicate Shift: {$shift_id} (Already Exists).<br />\n";
        }
    }

    return $addedShiftMsg;
}

//Send Admin Calendar Update Report to Webmaster by Email
function sendAdminCalendarUpdateReport($adminMsg){
	$sendAdminCalendarReportEmailMsg = "";
	$message = "<html><body>" . $adminMsg . "</div></body></html>";
	$subject = 'Edcuare Staffing - Update Calendar Shifts Report';
	$headers = 'From: Educare Staffing Services <webmaster@educarestaffing.com>' . "\r\n";
  	$headers .= 'MIME-Version: 1.0' . "\r\n";
  	$headers .= 'Content-type: text/html; charset=iso-8859-1';
  	$to = "webmaster@educarestaffing.com";
	if(wp_mail($to, $subject, $message, $headers)){
		$sendAdminCalendarReportEmailMsg .= "<p>Admin calendar update report email sent.</p><br />\n";
	} else {
		$sendAdminCalendarReportEmailMsg .= "<p>There was a problem sending the admin calendar update report email.</p><br />\n";
	}
	return $sendAdminCalendarReportEmailMsg;
}

//Send Shift Confirmation to Client by Email
function sendShiftConfirmationEmail($shift_record, $employee_record, $shiftAddedMsg, $shift_post_id, $shift_room){

	$confirmationEmailMsg = "";

    $shift_start_time=date('D, d M Y h:i A', strtotime($shift_record->start_time));
    $shift_end_time=date('D, d M Y h:i A', strtotime($shift_record->end_time));
    $shift_emplyee_name = $employee_record["first_name"] . " " . $employee_record["last_name"];
  
    $message = '<html><head><title>Shift Client Confirmation</title></head><body>';
    $message .= "<h2>Shift Client Confirmation</h2>\n";
    $message .= $shiftAddedMsg;

    $subject = 'Shift coverage for '. date("F j - g:i a", strtotime($shift_start_time)) .' to ' . date("g:i a", strtotime($shift_end_time));
    $subject .= ' - '. $shift_room;
    $headers = 'From: Educare Staffing Services <admin@educarestaffing.ca>' . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-type: text/html; charset=iso-8859-1';
    $confirmation_email_to = $to = "webmaster@educarestaffing.com";
  
    //PDF File Attachment
    $attachment = "/home/www/educarestaffing.com/wp-content/employee_profiles/Personal File - " . $shift_emplyee_name . ".pdf";

    if (file_exists($attachment)){
        //set message for confirmation email
        $confirmation_message = "Hello,<br /><br />" . $shift_emplyee_name . " will cover the above mentioned shift. The employee's personal file is attached for your consideration.<br /><br />Best Regards,<br />Educare Staffing Services";
        if (wp_mail($to, $subject, $confirmation_message, $headers, $attachment)) {	
            http_response_code(200);
            update_post_meta($shift_post_id,'Confirmation Email', "1");
            $confirmationEmailMsg .= "Confirmation email successfully sent to " . $confirmation_email_to . " for Shift ID: " . $shift_record->id . "\n";
         } else {
            $subject = $confirmationEmailMsg .= 'Confirmation Email Error - For Shift ID: ' . $shift_record->id;
            $message .= $confirmationEmailMsg .= "<hr /><br />There was an unknown email error sending confirmation message to " . $confirmation_email_to . " after this shift was added to the client calendar.";
            $message .= '</body></html>';
            wp_mail($to, $subject, $message, $headers);
            update_post_meta($shift_post_id,'Confirmation Email', "0");
            $confirmationEmailMsg .= "There was an issue sending confirmation email for Shift ID: " . $shift_record->id . "\n";
        }
    } else {
        $subject = $confirmationEmailMsg .= 'Confirmation Email Error - For Shift ID: '. $shift_record->id;
        $message .= $confirmationEmailMsg .= "<h4>Error Message:</h4>\n";
        $message .= $confirmationEmailMsg .= "<strong>There was an error sending confirmation message to " . $confirmation_email_to . " after this shift was added to the client calendar.</strong>.<br /><br />";
        $message .= $confirmationEmailMsg .= "A PDF attachment could not be found for the following employee: " . $shift_emplyee_name;
        $message .= $confirmationEmailMsg .= "<br /><br />To fix this by the next automatic confirmation, ensure that this employee profile PDF exists in the folder (/wp-content/employee_profiles/).";
        $message .= '</body></html>';
        wp_mail($to, $subject, $message, $headers);
        update_post_meta($shift_post_id,'Confirmation Email', "0");
        $confirmationEmailMsg .= "There was an issue sending confirmation email to " . $confirmation_email_to . " for Shift ID: " . $shift_record->id . ".<br /><br />The following PDF attachment could not be found: " . $attachment;
    }

	return $confirmationEmailMsg;

}

?>