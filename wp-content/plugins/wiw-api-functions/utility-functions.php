<?php

if (!function_exists('get_post_id_by_meta_key_and_value')) {
	/**
	 * Get post id from meta key and value
	 * @param string $key
	 * @param mixed $value
	 * @return int|bool
	 */
	function get_post_id_by_meta_key_and_value($key, $value) {
		global $wpdb;
		$meta = $wpdb->get_results("SELECT * FROM `".$wpdb->postmeta."` WHERE meta_key='".$wpdb->escape($key)."' AND meta_value='".$wpdb->escape($value)."'");
		if (is_array($meta) && !empty($meta) && isset($meta[0])) {
			$meta = $meta[0];
		}		
		if (is_object($meta)) {
			return $meta->post_id;
		}
		else {
			return false;
		}
	}
}

// Returns number of times x
// occurs in arr[0..n-1]
function countOccurrences($arr, $n, $x)
{
    $res = 0;
    for ($i = 0; $i < $n; $i++)
        if ($x == $arr[$i])
        $res++;
    return $res;
}

function getEmployeeByID($id, $employee_records) {
	foreach ($employee_records->users as $value) {
		if ($value->id === $id) {
		     $resultSet['first_name'] = $value->first_name;
			 $resultSet['last_name'] = $value->last_name;
			 $resultSet['email'] = $value->email;
			 $resultSet['phone_number'] = $value->phone_number;
			 $resultSet['position'] = $value->positions;
			return $resultSet;
		}
	}
    return null;
}

function get_current_url()
{
    $pageURL = 'http';
    if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }
    $pageURL .= "://";
    if ($_SERVER["SERVER_PORT"] != "۸۰") {
        $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    return $pageURL;
}

function loginAutobot(){
	//Login wiw_autobot as administrative user to make calendar updates.
	//If not, certain aspects of update, such as category ID's will not be entered.
	$loginusername = 'wiw_autobot';
	if (!is_user_logged_in()) {
		//get user's ID
		$user = get_user_by('login', $loginusername);
		   $user_id = $user->ID;
	
		//login
		wp_set_current_user($user_id, $loginusername);
		wp_set_auth_cookie($user_id);
		do_action('wp_login', $loginusername);
	}
	return $loginusername;
}

function logoutAutobot(){
	//Login wiw_autobot as administrative user to make calendar updates.
	//If not, certain aspects of update, such as category ID's will not be entered.
	$loginusername = 'wiw_autobot';
	if (is_user_logged_in()) {
		//get user's ID
		$user = get_user_by('login', $loginusername);
		   $user_id = $user->ID;
	
		//login
		wp_set_current_user($user_id, $loginusername);
		wp_set_auth_cookie($user_id);
		do_action('wp_logout', $loginusername);
	}
	return $loginusername;
}

?>