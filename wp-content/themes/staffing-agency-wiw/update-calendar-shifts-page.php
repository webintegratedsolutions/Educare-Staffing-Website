<?php
/* Template Name: Update Calendar Shifts Page */
/* The template for updating calendar shifts page
 *
 * @package staffing-agency-wiw
 */
 // Authenticate admin cron request
if (!isset($_GET['auth_token']) || $_GET['auth_token'] !== ADMIN_CRON_SECRET) {
    http_response_code(403);
    //logout if logged in as autobot
    $checkuser = get_user_by('email', 'wiw_autobot@educarestaffing.ca');
    if ($checkuser && get_current_user_id() == $checkuser->ID) {
        echo "✅ WIW Autobot logged in!";
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        wp_set_current_user(0);
    } else {
        echo "❌ WIW Autobot is not logged in!";
    }  
    die('❌ Unauthorized access');
}

get_header();
$user = wp_get_current_user();
/*Call function from When I Work API Plugin to Update Calendar Shifts
if ( is_page( 'administrator' ) || is_page( 'administration' ) && function_exists( 'updateDeletedNewCalendarShifts') ) {
    updateDeletedNewCalendarShifts();
}
*/
?>
<div id="meta-page">
    <div class="container">
        <div class="meta-page-left">
            <h2>
                <? wp_title('') ?>
            </h2>
        </div>
    </div>
</div>
<main id="primary" class="site-main">

    <?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content', 'page' );

		endwhile; // End of the loop.
		?>

</main><!-- #main -->

<?php
get_footer();