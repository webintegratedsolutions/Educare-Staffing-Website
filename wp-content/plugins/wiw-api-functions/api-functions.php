<?php

//Retrieve all shifts from When I Work API and return results array
function getShiftsListingResult(){
	global $wiw;
	//set Start and End Dates (adjusted 4 hour time zone)
	date_default_timezone_set('AMERICA/TORONTO');
	$startDate = date("Y-m-d G:i:s", strtotime(date("Y-m-d G:i:s")));
	$endDate = date("Y-m-d G:i:s", strtotime($startDate . ' +90 days'));
	//Make API request to retrieve all shifts from When I work
	//Only including closed shifts
	$listingShiftsResult = $wiw->get("shifts", array(
		"include_open" => true,
		"include_allopen"  => true,
		"start" => $startDate,
		"end" => $endDate
	));
	return $listingShiftsResult;
}

//Retrieve all job sites (clients) from When I Work API and return results array
function getlistingJobSitesResult(){
	global $wiw;
	//Make API request to retrieve all job sites from When I work
    $jobSitesResult = $wiw->get("sites");
	return $jobSitesResult;
}


//Retrieve all users (employees) from When I Work API and return results array
function getlistingUsersResult(){
	global $wiw;
	//Make API request to retrieve all job sites from When I work
    $usersResult = $wiw->get("users");
	return $usersResult;
}

 ?>