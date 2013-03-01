<?php
/**
 * This file implements the UI controller for settings management.
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
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: settings.ctrl.php 1097 2012-03-28 06:45:06Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Blog Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->dbupdate();

$AdminUI->set_path( 'options', 'general' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// UPDATE general settings:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		if( param( 'default_blog_ID', 'integer', NULL ) !== NULL )
		{
			$Settings->set( 'default_blog_ID', $default_blog_ID );
		}

		// Session timeout
		$timeout_sessions = param_duration( 'timeout_sessions' );

		if( $timeout_sessions < $crumb_expires )
		{ // lower than $crumb_expires: not allowed
			param_error( 'timeout_sessions', sprintf( T_( 'You cannot set a session timeout below %d minutes.' ), floor($crumb_expires/60) ) );
		}
		elseif( $timeout_sessions < 300 )
		{ // lower than 5 minutes: not allowed
			param_error( 'timeout_sessions', sprintf( T_( 'You cannot set a session timeout below %d minutes.' ), 5 ) );
		}
		elseif( $timeout_sessions < 86400 )
		{ // lower than 1 day: notice/warning
			$Messages->add( sprintf( T_( 'Warning: your session timeout is just %d minutes. Your users may have to re-login often!' ), floor($timeout_sessions/60) ), 'note' );
		}
		$Settings->set( 'timeout_sessions', $timeout_sessions );

		// Reload page timeout
		$reloadpage_timeout = param_duration( 'reloadpage_timeout' );

		if( $reloadpage_timeout > 99999 )
		{
			param_error( 'reloadpage_timeout', sprintf( T_( 'Reload-page timeout must be between %d and %d seconds.' ), 0, 99999 ) );
		}
		$Settings->set( 'reloadpage_timeout', $reloadpage_timeout );

		// Smart hit count
		$Settings->set( 'smart_hit_count', param( 'smart_hit_count', 'integer', 0 ) );

		$new_cache_status = param( 'general_cache_enabled', 'integer', 0 );
		if( ! $Messages->has_errors() )
		{
			load_funcs( 'collections/model/_blog.funcs.php' );
			$result = set_cache_enabled( 'general_cache_enabled', $new_cache_status, NULL, false );
			if( $result != NULL )
			{ // general cache setting was changed
				list( $status, $message ) = $result;
				$Messages->add( $message, $status );
			}
		}

		$Settings->set( 'newblog_cache_enabled', param( 'newblog_cache_enabled', 'integer', 0 ) );
		$Settings->set( 'newblog_cache_enabled_widget', param( 'newblog_cache_enabled_widget', 'integer', 0 ) );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('General settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=gensettings', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;
}


$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Global settings'), '?ctrl=settings',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('General'), '?ctrl=gensettings' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/views/_general.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log: settings.ctrl.php,v $
 */
?>