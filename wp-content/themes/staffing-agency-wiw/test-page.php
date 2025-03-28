<?php
/* Template Name: Test Page */
/* Template for testing functionality
 * 
 * @package staffing-agency-wiw
 */
if(!is_user_logged_in()) {
    wp_redirect( wp_login_url() );
}
get_header();
$user = wp_get_current_user();

sendClientConfirmationEmails();

?>
<div id="meta-page">
    <div class="container">
        <div class="meta-page-left">
            <h2>
                <? wp_title('') ?>
            </h2>
        </div>
		<div class="meta-page-right"><strong><?php echo $user->display_name ?></strong></div>
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