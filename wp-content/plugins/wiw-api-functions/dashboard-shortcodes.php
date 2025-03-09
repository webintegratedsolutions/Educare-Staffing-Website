<?php

//Generate shortcode content to display shift listing result
function wiw_get_current_shifts($atts) {
	$Content = '';
    //Get All Shifts from When I Work API
	if ($listingShiftsResult = getShiftsListingResult()){
		$shiftCount = 1;
		$wiw_shift_ids = Array();
		$wiw_duplicate_shifts = Array();
    	foreach ($listingShiftsResult->shifts as $shift) {
        	if($shift->is_open==1){
            	$shift_open_status = "open";
        	} else {
            	$shift_open_status = "closed";
        	}
        	$Content .= "<p>Shift Count: " . $shiftCount . "<br />\n";
        	$Content .= "Shift ID: ". $shift->id."<br />\n";
        	$Content .= "Shift Open Status: ". $shift_open_status."<br />\n";
        	$Content .= "Client ID (Site ID): ". $shift->site_id."<br />\n";
        	$Content .= "Employee ID (User ID): ". $shift->user_id."<br />\n";
        	$Content .= "Shift Start Time: ". date("Y-m-d H:i:s", strtotime($shift->start_time)) . "<br />\n";
        	$Content .= "Shift End Time: ". date("Y-m-d H:i:s", strtotime($shift->end_time)) . "<br />\n";
        	$Content .= "Created At: ". date("Y-m-d H:i:s", strtotime($shift->created_at)) . "<br />\n";
        	$Content .= "Updated: ". date("Y-m-d H:i:s", strtotime($shift->updated_at)) . "<br />\n";
        	$Content .= "Acknowleded: ". $shift->acknowledged . "<br />\n";
        	$Content .= "Published: ". $shift->published . "<br />\n";
        	$Content .= "Published Date: ". date("Y-m-d H:i:s", strtotime($shift->published_date)) . "<br />\n";
        	$Content .= "Shift Notes: ". $shift->notes . "<br />\n";
        	$Content .= "</p>\n";
       		$shiftCount++;
    	}
		$Content .= "<p><hr /></p>\n";
		if(count($wiw_duplicate_shifts)>0){
			$Content .= "<p>Warning. Duplicate shifts.</p>\n";
			$Content .= print_r($wiw_duplicate_shifts);
		} else {
			$Content .= "<p>No duplicate shifts detected.</p>\n";
			$Content .= print_r($wiw_shift_ids);
		}
	} else {
		$Content .= "<p>Unable to connect to When I Work API.<br />\n";
	}
    return $Content;
}
add_shortcode('wiw_current_shifts', 'wiw_get_current_shifts');

//Generate shortcode content to display job sites (clients) listing result
function wiw_get_job_sites($atts) {
	$Content = '';
	$jobSiteCount = 0;
	if($listingJobSitesResult = getlistingJobSitesResult()){
	    //Get All Shifts from When I Work API
	    foreach ($listingJobSitesResult->sites as $site) {
       		$Content .= "<p>Client (Job Site) Count: " . $jobSiteCount . "<br />\n";
        	$Content .= "Client Name: ". $site->name."<br />\n";
        	$Content .= "Address: ". $site->address."<br />\n";
        	$Content .= "Account Created At: ". $site->created_at."<br />\n";
			$Content .= "Client ID: <strong>". $site->id."</strong><br />\n";
       		$Content .= "</p>\n";
        	$jobSiteCount++;
    	}
	} else {
			$Content .= "<p>Unable to connect to When I Work API.<br />\n";
	}
    return $Content;
}
add_shortcode('wiw_job_sites', 'wiw_get_job_sites');

//Generate shortcode content to display users (employees) listing result
function wiw_get_users($atts) {
	$Content = '';
	$userCount = 0;
    //Get All Shifts from When I Work API
	if($listingUsersResult = getlistingUsersResult()){
	    foreach ($listingUsersResult->users as $user) {
        	$Content .= "<p>User (Employee) Count: " . $userCount . "<br />\n";
        	$Content .= "Employee ID: " . $user->id . "<br />\n";
			$shift_position = "";
			if ( $user->positions[0] == "2611462") {
				$shift_position = "ECA";
			} elseif ( $user->positions[0] == "2611465") {
				$shift_position = "RECE";
			} else {
				$shift_position = "No Position";
			}
			$Content .= "Position: " .  $shift_position . " (" . $user->positions[0] . ")<br />\n";
        	$Content .= "First Name: " . $user->first_name . "<br />\n";
        	$Content .= "Last Name: " . $user->last_name . "<br />\n";
        	$Content .= "Email: " . $user->email . "<br />\n";
        	$Content .= "Phone Number: " . $user->phone_number. "<br />\n";
        	$shift_emplyee_name = $user->first_name . " " . $user->last_name;
        	$attachment = "/home/www/educarestaffing.com/wp-content/employee_profiles/Personal File - " . $shift_emplyee_name . ".pdf";
        	if (file_exists($attachment)){
            	$Content .= "PDF profile: " . $attachment . "<br />\n";
        	} else {
            	$Content .= "<span>* A PDF profile could not be located for this employee.</span>";
        	}
        	$Content .= "</p>\n";
        	$userCount++;
    	}
	} else {
		$Content .= "<p>Unable to connect to When I Work API.<br />\n";
	}

    return $Content;
}
add_shortcode('wiw_users', 'wiw_get_users');

 ?>