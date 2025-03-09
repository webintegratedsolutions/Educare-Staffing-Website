<?php

// Load WP components, no themes.
require('../wp-load.php');
define('WP_USE_THEMES', false);

$shift_id = "3014332138";

loginAutobot();
echo "<h2>Delete Calendar Shift</h2><hr />\n";
if(deleteCalendarShift($shift_id)){
    echo "<div><p>Shift ID: " . $shift_id . " has been deleted.</p></div>\n";
} else {
    echo "<div><p>Shift ID: " . $shift_id . " failed to be deleted.</p></div>\n";
};
//Remove shift from Current Calendar Shifts Array
logoutAutobot();

function deleteCalendarShift($shift_id){
    $post_shift_id = get_post_id_by_meta_key_and_value("Shift ID", $shift_id);
    //Remove shift from Current Calendar Shifts
    if(wp_delete_post($post_shift_id, true)){
        return true;
    } else {     
        return false;
    }
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

?>