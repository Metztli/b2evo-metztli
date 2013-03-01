<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal License choice
 * - If you have received this file as part of a package, please find the license.txt file in
 *   the same folder or the closest folder above for complete license terms.
 * - If you have received this file individually (e-g: from http://evocms.cvs.sourceforge.net/)
 *   then you must choose one of the following licenses before using the file:
 *   - GNU General Public License 2 (GPL) - http://www.opensource.org/licenses/gpl-license.php
 *   - Mozilla Public License 1.1 (MPL) - http://www.opensource.org/licenses/mozilla1.1.php
 * }}
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 *
 * @version $Id: coll_settings.ctrl.php 1201 2012-04-07 04:03:31Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Store/retrieve preferred tab from UserSettings:
$UserSettings->param_Request( 'tab', 'pref_coll_settings_tab', 'string', 'general', true /* memorize */, true /* force */ );
if( $tab == 'widgets' )
{	// This is another controller!
   require_once dirname(__FILE__).'/../widgets/widgets.ctrl.php';
   return;
}


param_action( 'edit' );

// Check permissions on requested blog and autoselect an appropriate blog if necessary.
// This will prevent a fat error when switching tabs and you have restricted perms on blog properties.
if( $selected = autoselect_blog( 'blog_properties', 'edit' ) ) // Includes perm check
{	// We have a blog to work on:

	if( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
	{	// Selected a new blog:
		$BlogCache = & get_BlogCache();
		/**
		 * @var Blog
		 */
		$Blog = & $BlogCache->get_by_ID( $blog );
	}

	/**
	 * @var Blog
	 */
	$edited_Blog = & $Blog;
}
else
{	// We could not find a blog we have edit perms on...
	// Note: we may still have permission to edit categories!!
	// redirect to blog list:
	header_redirect( '?ctrl=collections' );
	// EXITED:
	$Messages->add( T_('Sorry, you have no permission to edit blog properties.'), 'error' );
	$action = 'nil';
	$tab = '';
}

memorize_param( 'blog', 'integer', -1 );	// Needed when generating static page for example

param( 'skinpage', 'string', '' );
if( $tab == 'skin' && $skinpage != 'selection' )	// If not screen selection => screen settings
{
	$SkinCache = & get_SkinCache();
	/**
	 * @var Skin
	 */
	$edited_Skin = & $SkinCache->get_by_ID( $Blog->skin_ID );
}


if( ( $tab == 'perm' || $tab == 'permgroup' )
	&& ( empty($blog) || ! $Blog->advanced_perms ) )
{	// We're trying to access advanced perms but they're disabled!
	$tab = 'features';	// the screen where you can enable advanced perms
	if( $action == 'update' )
	{ // make sure we don't update anything here
		$action = 'edit';
	}
}

/**
 * Perform action:
 */
switch( $action )
{
	case 'edit':
	case 'filter1':
	case 'filter2':
		// Edit collection form (depending on tab):
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		param( 'preset', 'string', '' );

		$edited_Blog->load_presets( $preset );

		break;

	case 'update':
		// Update DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );
		$update_redirect_url = '?ctrl=coll_settings&tab='.$tab.'&blog='.$blog;

		switch( $tab )
		{
			case 'general':
			case 'urls':
				if( $edited_Blog->load_from_Request( array() ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'features':
				if( $edited_Blog->load_from_Request( array( 'features' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'seo':
				if( $edited_Blog->load_from_Request( array( 'seo' ) ) )
				{ // Commit update to the DB:
					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'skin':
				if( $skinpage == 'selection' )
				{
					if( $edited_Blog->load_from_Request( array() ) )
					{ // Commit update to the DB:
						$edited_Blog->dbupdate();
						$Messages->add( T_('The blog skin has been changed.')
											.' <a href="'.$admin_url.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$edited_Blog->ID.'">'.T_('Edit...').'</a>', 'success' );
						header_redirect( $edited_Blog->gen_blogurl() );
					}
				}
				else
				{ // Update params/Settings
					$edited_Skin->load_params_from_Request();

					if(	! param_errors_detected() )
					{	// Update settings:
						$edited_Skin->dbupdate_settings();
						$Messages->add( T_('Skin settings have been updated'), 'success' );
						// Redirect so that a reload doesn't write to the DB twice:
						header_redirect( $update_redirect_url, 303 ); // Will EXIT
					}
				}
				break;

			case 'plugin_settings':
				// Update Plugin params/Settings
				load_funcs('plugins/_plugin.funcs.php');

				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$pluginsettings = $loop_Plugin->get_coll_setting_definitions( $tmp_params = array('for_editing'=>true) );
					if( empty($pluginsettings) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginsettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'CollSettings', $Blog );
					}
				}

				if(	! param_errors_detected() )
				{	// Update settings:
					$Blog->dbupdate();
					$Messages->add( T_('Plugin settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'advanced':
				if( $edited_Blog->load_from_Request( array( 'pings', 'cache', 'authors', 'login' ) ) )
				{ // Commit update to the DB:
					if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
					{
						$cache_status = param( 'cache_enabled', 'integer', 0 );
						load_funcs( 'collections/model/_blog.funcs.php' );
						$result = set_cache_enabled( 'cache_enabled', $cache_status, $edited_Blog->ID, false );
						if( $result != NULL )
						{
							list( $status, $message ) = $result;
							$Messages->add( $message, $status );
						}
					}

					$edited_Blog->dbupdate();
					$Messages->add( T_('The blog settings have been updated'), 'success' );
					// Redirect so that a reload doesn't write to the DB twice:
					header_redirect( $update_redirect_url, 303 ); // Will EXIT
				}
				break;

			case 'perm':
				blog_update_perms( $blog, 'user' );
				$Messages->add( T_('The blog permissions have been updated'), 'success' );
				break;

			case 'permgroup':
				blog_update_perms( $blog, 'group' );
				$Messages->add( T_('The blog permissions have been updated'), 'success' );
				break;
		}

		break;
}

$AdminUI->set_path( 'blogs',  $tab  );


/**
 * Display page header, menus & messages:
 */
$AdminUI->set_coll_list_params( 'blog_properties', 'edit',
											array( 'ctrl' => 'coll_settings', 'tab' => $tab, 'action' => 'edit' ),
											T_('List'), '?ctrl=collections&amp;blog=0' );


$AdminUI->breadcrumbpath_init( false );
$AdminUI->breadcrumbpath_add( T_('Blog settings'), '?ctrl=coll_settings&amp;blog=$blog$' );
switch( $AdminUI->get_path(1) )
{
	case 'general':
		$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'features':
		$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'skin':
		if( $skinpage == 'selection' )
		{
			$AdminUI->breadcrumbpath_add( T_('Skin selection'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab.'&amp;skinpage=selection' );
		}
		else
		{
			$AdminUI->breadcrumbpath_add( T_('Settings for current skin'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		}
		break;

	case 'plugin_settings':
		$AdminUI->breadcrumbpath_add( T_('Blog specific plugin settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'urls':
		$AdminUI->breadcrumbpath_add( T_('URL configuration'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'seo':
		$AdminUI->breadcrumbpath_add( T_('SEO settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'advanced':
		$AdminUI->breadcrumbpath_add( T_('Advanced settings'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'perm':
		$AdminUI->breadcrumbpath_add( T_('User permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;

	case 'permgroup':
		$AdminUI->breadcrumbpath_add( T_('Group permissions'), '?ctrl=coll_settings&amp;blog=$blog$&amp;tab='.$tab );
		break;
}


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


// Begin payload block:
$AdminUI->disp_payload_begin();


// Display VIEW:
switch( $AdminUI->get_path(1) )
{
	case 'general':
		$next_action = 'update';
		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );
		break;

	case 'features':
		$AdminUI->disp_view( 'collections/views/_coll_features.form.php' );
		break;

	case 'skin':
		if( $skinpage == 'selection' )
		{
			$AdminUI->disp_view( 'skins/views/_coll_skin.view.php' );
		}
		else
		{
			$AdminUI->disp_view( 'skins/views/_coll_skin_settings.form.php' );
		}
		break;

	case 'plugin_settings':
		$AdminUI->disp_view( 'collections/views/_coll_plugin_settings.form.php' );
		break;

	case 'urls':
		$AdminUI->disp_view( 'collections/views/_coll_urls.form.php' );
		break;

	case 'seo':
		$AdminUI->disp_view( 'collections/views/_coll_seo.form.php' );
		break;

	case 'advanced':
		$AdminUI->disp_view( 'collections/views/_coll_advanced.form.php' );
		break;

	case 'perm':
		$AdminUI->disp_view( 'collections/views/_coll_user_perm.form.php' );
		break;

	case 'permgroup':
		$AdminUI->disp_view( 'collections/views/_coll_group_perm.form.php' );
		break;
}

// End payload block:
$AdminUI->disp_payload_end();


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log: coll_settings.ctrl.php,v $
 */
?>