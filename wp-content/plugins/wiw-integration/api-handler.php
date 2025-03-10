<?php
if (!defined('ABSPATH')) {
    exit;
}

class WIW_API_Handler {

    private static $api_url = "https://api.wheniwork.com/2/";
    private static $api_key;

    public function __construct() {
        self::$api_key = get_option('wiw_api_key');
    }

    public static function make_request($endpoint, $method = 'GET', $body = []) {
        $args = [
            'method'  => $method,
            'headers' => [
                'W-Token'       => self::$api_key,
                'Content-Type'  => 'application/json',
            ],
        ];

        if (!empty($body)) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request(self::$api_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    // Get Employee Shifts
    public static function get_shifts($user_id = null) {
        $endpoint = 'shifts';
        if ($user_id) {
            $endpoint .= '?user_id=' . $user_id;
        }
        return self::make_request($endpoint);
    }

    // Get Employee Data
    public static function get_employees() {
        return self::make_request('users');
    }
}
