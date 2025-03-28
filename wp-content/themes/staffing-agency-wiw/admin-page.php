<?php
/* Template Name: Admin Page */
/* The template for displaying all site admin pages
 *
 * This is the template that displays all pages by default.
 *
 * @package staffing-agency-wiw
 */

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
                <? wp_title('') ?> - <?php echo $user->display_name ?>
            </h2>
        </div>
		<div class="meta-page-right"><a href="/wp-admin/" class="admin-alink"><?php echo " WordPress Dashboard"; ?></a> | <a href="/my-profile/" class="admin-alink"><i class="um-faicon-user"></i><?php echo " My Profile"; ?></a> | <a href="/my-calendar/" class="admin-alink"><?php echo "Calendar"; ?></a> | <a href="/update-calendar-shifts/?auth_token=T7m3bTgHq2X9@PqZ!eKc" class="admin-alink"><?php echo "Update Calendar Shifts"; ?></a></div>
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