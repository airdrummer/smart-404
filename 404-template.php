<?php
/**
 * @package WordPress
 * @subpackage subpackage
 		load search field with smart404's search terms, focus on s.f.
 */

get_header(); 
?>

	<div id="content-container">
		<div id="content" role="main">

			<div id="post-0" class="post error404 not-found" >
				<h1 class="entry-title"><?php _e( 'Not Found', 'subpackage' ); ?></h1>
				<div class="entry-content" style="text-align:center;">
smart404_display_suggestions('yourthemenamehere');
				</div><!-- .entry-content -->
			</div><!-- #post-0 -->
		</div><!-- #content -->
	</div><!-- #content-container -->
<?php get_footer(); ?>
