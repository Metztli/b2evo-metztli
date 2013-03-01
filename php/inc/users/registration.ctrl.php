<?php
/**
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-bogdan: Evo Factory / Bogdan.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: registration.ctrl.php 16 2011-10-25 01:34:59Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Check minimum permission:
$current_User->check_perm( 'users', 'view', true );

$AdminUI->set_path( 'users', 'usersettings', 'registration' );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'registration' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// keep old newusers_canregister setting value to check if we need to invalidate pagecaches
		$old_newusers_canregister = $Settings->get( 'newusers_canregister' );

		// UPDATE general settings:
		param( 'newusers_canregister', 'integer', 0 );

		param( 'newusers_grp_ID', 'integer', true );

		param_integer_range( 'newusers_level', 0, 9, T_('User level must be between %d and %d.') );

		param( 'newusers_mustvalidate', 'integer', 0 );

		param( 'newusers_revalidate_emailchg', 'integer', 0 );

		param_integer_range( 'user_minpwdlen', 1, 32, T_('Minimum password length must be between %d and %d.') );

		param( 'js_passwd_hashing', 'integer', 0 );

		param( 'registration_require_country', 'integer', 0 );

		param( 'registration_ask_locale', 'integer', 0 );

		param( 'registration_require_gender', 'string', '' );

		$Settings->set_array( array(
									 array( 'newusers_canregister', $newusers_canregister),

									 array( 'newusers_grp_ID', $newusers_grp_ID),

									 array( 'newusers_level', $newusers_level),

									 array( 'newusers_mustvalidate', $newusers_mustvalidate),

		                             array( 'newusers_revalidate_emailchg', $newusers_revalidate_emailchg),

									 array( 'user_minpwdlen', $user_minpwdlen),

									 array( 'js_passwd_hashing', $js_passwd_hashing),

									 array( 'registration_require_country', $registration_require_country),

									 array( 'registration_ask_locale', $registration_ask_locale),

									 array( 'registration_require_gender', $registration_require_gender) ) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{ // update was successful
				if( $old_newusers_canregister != $newusers_canregister )
				{ // invalidate all PageCaches
					invalidate_pagecaches();
				}
				$Messages->add( T_('General settings updated.'), 'success' );
				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=registration', 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=usersettings' );
$AdminUI->breadcrumbpath_add( T_('Registration'), '?ctrl=registration' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_registration.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: registration.ctrl.php,v $
 */
?>