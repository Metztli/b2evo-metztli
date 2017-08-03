<?php
/**
 * This file is the template that displays "login required" for non logged-in users.
 *
 * For a quick explanation of b2evo 2.0 skins, please start here:
 * {@link http://b2evolution.net/man/skin-development-primer}
 *
 * @package evoskins
 * @subpackage bootstrap_blog
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


global $app_version, $disp, $Blog;

if( evo_version_compare( $app_version, '6.4' ) < 0 )
{ // Older skins (versions 2.x and above) should work on newer b2evo versions, but newer skins may not work on older b2evo versions.
	die( 'This skin is designed for b2evolution 6.4 and above. Please <a href="http://b2evolution.net/downloads/index.html">upgrade your b2evolution</a>.' );
}

// This is the main template; it may be used to display very different things.
// Do inits depending on current $disp:
skin_init( $disp );


// -------------------------- HTML HEADER INCLUDED HERE --------------------------
skin_include( '_html_header.inc.php', array() );
// -------------------------------- END OF HEADER --------------------------------


// ---------------------------- SITE HEADER INCLUDED HERE ----------------------------
// If site headers are enabled, they will be included here:
siteskin_include( '_site_body_header.inc.php' );
// ------------------------------- END OF SITE HEADER --------------------------------
?>


<div class="container-fluid">
<div class="row">

<?php
if( $Skin->show_container_when_access_denied( 'Header' ) )
{ // Display 'Page Top' widget container
?>
		
	<div class="headpicture">

		<div class="headipic_section <?php 
										if( $Skin->get_setting( 'header_content_pos' ) == 'center_pos' ) {
											echo 'center';
										} elseif( $Skin->get_setting( 'header_content_pos' ) == 'left_pos' ){
											echo 'left';
										} elseif( $Skin->get_setting( 'header_content_pos' ) == 'right_pos' ){
											echo 'right';
										}
										?>">
			<?php
				if( $Skin->get_setting( 'header_content_pos' ) == 'column_pos' ) {
					echo '<div class="container">';
				}
				skin_container( NT_('Header'), array(
				) );
				if( $Skin->get_setting( 'header_content_pos' ) == 'column_pos' ) {
					echo '</div>';
				}
			?>				
			
		</div>
		
	</div>
	
<?php } ?>

<?php
if( $Skin->show_container_when_access_denied( 'Menu' ) )
{ // Display 'Page Top' widget container
?>

<nav class="top-menu container-fluid">
	<div class="row">
		<!-- Brand and toggle get grouped for better mobile display -->

<?php if( $Skin->get_setting( 'top_menu_position' ) == 'menu_inline' ) {
		echo '<div class="container menu_inline_container">';
} ?>

		<div class="navbar-header<?php if( $Skin->get_setting( 'top_menu_position' ) == 'menu_center' ) { echo ' navbar-header-center'; } ?>">
			<button type="button" class="navbar-toggle navbar-toggle-hamb collapsed" data-toggle="collapse" data-target="#navbar-collapse-1" aria-expanded="false">
				<span class="sr-only">Toggle navigation</span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
			
				<?php				 
				if( $Skin->get_setting( 'top_menu_brand' ) ) {
				// ------------------------- "Menu" Collection title --------------------------
					skin_widget( array(
						// CODE for the widget:
						'widget'              => 'coll_title',
						// Optional display params
						'block_start'         => '<div class="navbar-brand">',
						'block_end'           => '</div>',
						'item_class'           => 'navbar-brand',
					) );
				// ------------------------- "Menu" Collection logo --------------------------
				}
				?>
		</div><!-- /.navbar-header -->
		
		<!-- Collect the nav links, forms, and other content for toggling -->
		<div class="collapse navbar-collapse<?php if( $Skin->get_setting( 'top_menu_position' ) == 'menu_center' ) { echo ' menu_center'; } ?>" id="navbar-collapse-1">
			<ul class="navbar-nav evo_container evo_container__menu" id="menu">				
				<?php
					// ------------------------- "Menu" CONTAINER EMBEDDED HERE --------------------------
					// Display container and contents:
					// Note: this container is designed to be a single <ul> list
					skin_container( NT_('Menu'), array(
							// The following params will be used as defaults for widgets included in this container:
							'block_start'         => '',
							'block_end'           => '',
							'block_display_title' => false,
							'list_start'          => '',
							'list_end'            => '',
							'item_start'          => '<li class="evo_widget $wi_class$">',
							'item_end'            => '</li>',
							'item_selected_start' => '<li class="active evo_widget $wi_class$">',
							'item_selected_end'   => '</li>',
							'item_title_before'   => '',
							'item_title_after'    => '',
						) );
					// ----------------------------- END OF "Menu" CONTAINER -----------------------------
				?>
			</ul>
		</div><!-- .collapse -->
		
<?php if( $Skin->get_setting( 'top_menu_position' ) == 'menu_inline' ) {
		echo '</div><!-- .container -->';
} ?>
		
	</div><!-- .row -->
</nav><!-- .top-menu -->

<?php } ?>

</div>
</div>

<div class="container">

<!-- =================================== START OF MAIN AREA =================================== -->
	<div class="row">
		<div class="col-md-12">

		<?php
			// -------------- MAIN CONTENT TEMPLATE INCLUDED HERE (Based on $disp) --------------
			skin_include( '$disp$', array(
					// Form params for the forms below: login, register, lostpassword, activateinfo and msgform
					'skin_form_before'      => '<div class="panel panel-default skin-form">'
																				.'<div class="panel-heading">'
																					.'<h3 class="panel-title">$form_title$</h3>'
																				.'</div>'
																				.'<div class="panel-body">',
					'skin_form_after'       => '</div></div>',
					// Login
					'display_form_messages' => true,
					'form_title_login'      => T_('Log in to your account').'$form_links$',
					'form_title_lostpass'   => get_request_title().'$form_links$',
					'lostpass_page_class'   => 'evo_panel__lostpass',
					'login_form_inskin'     => false,
					'login_page_class'      => 'evo_panel__login',
					'login_page_before'     => '<div class="$form_class$">',
					'login_page_after'      => '</div>',
					'display_reg_link'      => true,
					'abort_link_position'   => 'form_title',
					'abort_link_text'       => '<button type="button" class="close" aria-label="Close"><span aria-hidden="true">&times;</span></button>',
				) );
			// Note: you can customize any of the sub templates included here by
			// copying the matching php file into your skin directory.
			// ------------------------- END OF MAIN CONTENT TEMPLATE ---------------------------
		?>

		</div>
	</div>

</div>

<?php
if( $Skin->show_container_when_access_denied( 'footer' ) )
{ // Display 'Footer' widget container
?>

<!-- =================================== START OF FOOTER =================================== -->
<footer class="footer">
	<div class='container'>
	<div class="row">
		<?php
			// Display container and contents:
			skin_container( NT_("Footer"), array(
					// The following params will be used as defaults for widgets included in this container:
					'block_start' => '<div class="widget $wi_class$">',
					'block_end' => '</div>',
					'block_title_start' => '<div class="panel-heading"><h4 class="panel-title">',
					'block_title_end' => '</h4></div>',
					'block_body_start' => '<div class="panel-body">',
					'block_body_end' => '</div>',
				) );
			// Note: Double quotes have been used around "Footer" only for test purposes.
		?>
		<div class="footer_note__wrapper clear">
			<p class="footer_note">
				<?php
					// Display footer text (text can be edited in Blog Settings):
					$Blog->footer_text( array(
							'before'      => '',
							'after'       => ' &bull; ',
						) );
				?>

				<?php
					// Display a link to contact the owner of this blog (if owner accepts messages):
					$Blog->contact_link( array(
							'before'      => '',
							'after'       => ' &bull; ',
							'text'   => T_('Contact'),
							'title'  => T_('Send a message to the owner of this blog...'),
						) );
					// Display a link to help page:
					$Blog->help_link( array(
							'before'      => ' ',
							'after'       => ' ',
							'text'        => T_('Help'),
						) );
				?>

				<?php
					if($Skin->get_setting('b2evo_credits')==true) {
					// Display additional credits:
					// If you can add your own credits without removing the defaults, you'll be very cool :))
					// Please leave this at the bottom of the page to make sure your blog gets listed on b2evolution.net
					credits( array(
							'list_start'  => '&bull;',
							'list_end'    => ' ',
							'separator'   => '&bull;',
							'item_start'  => ' ',
							'item_end'    => ' ',
						) );
					}
				?>
			</p>
			<?php
			if($Skin->get_setting('footer_links')==true) {
				skin_widget( array(
					// CODE for the widget:
					'widget'              => 'user_links',
				) );
			}
			?>
		</div>
	</div>
	</div>
</footer>

<?php } ?>

<?php
// ---------------------------- SITE FOOTER INCLUDED HERE ----------------------------
// If site footers are enabled, they will be included here:
siteskin_include( '_site_body_footer.inc.php' );
// ------------------------------- END OF SITE FOOTER --------------------------------


// ------------------------- HTML FOOTER INCLUDED HERE --------------------------
skin_include( '_html_footer.inc.php' );
// ------------------------------- END OF FOOTER --------------------------------
?>