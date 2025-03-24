<?php
/* Template Name: Calendar Page */
/* The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * 
 * @package staffing-agency-wiw
 */
if(!is_user_logged_in()) {

    header('Location: login');

} else {

    if(!current_user_can('manage_options')){
        if (strpos(wp_get_referer(), '/login/') !== false){
        header('Location: dashboard');
        } 
    } else {
        if (strpos(wp_get_referer(), '/login/') !== false){
        header('Location: administration');
        } 
    }

}

get_header();
$user = wp_get_current_user();

?>
<div id="meta-page">
    <div class="container">
        <div class="meta-page-left">
            <h2>
                <?php echo $user->display_name ?>
            </h2>
        </div>
        <?php
        if(current_user_can( 'manage_options' )){
        ?>
		<div class="meta-page-right"><a href="/my-profile/" class="admin-alink"><i class="um-faicon-user"></i><?php echo " My  Profile"; ?></a></div>
        <?
        } else {
        ?>
		<div class="meta-page-right admin-alink"><a href="/my-profile/"><i class="um-faicon-user"></i><?php echo " My  Profile"; ?></a></div>
        <?
        }
        ?>
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
</div>

</main><!-- #main -->

<?php
get_footer();