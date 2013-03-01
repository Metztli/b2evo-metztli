<?php

if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );

global $demo_mode;

$AdminUI->set_path( 'users', 'usersettings', 'usersettings' );

$current_User->check_perm( 'users', 'view', true );

param_action();

switch ( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'usersettings' );

		// Check permission:
		$current_User->check_perm( 'users', 'edit', true );

		// keep old allow_avatars setting value to check if we need to invalidate pagecaches
		$old_allow_avatars = $Settings->get( 'allow_avatars' );

		// UPDATE general settings:

		param( 'allow_avatars', 'integer', 0 );
		$Settings->set( 'allow_avatars', $allow_avatars );

		param( 'uset_nickname_editing', 'string', 'edited-user' );
		if( $demo_mode )
		{
			$uset_multiple_sessions = 'always';
			$Messages->add( 'Demo mode requires multiple sessions setting to be set to always.', 'note' );
		}
		else
		{
			param( 'uset_multiple_sessions', 'string', 'default-no' );
		}

		$Settings->set_array( array(
									array( 'nickname_editing', $uset_nickname_editing ),
									array( 'multiple_sessions', $uset_multiple_sessions ) ) );

		if( ! $Messages->has_errors() )
		{
			if( $Settings->dbupdate() )
			{
				if( $old_allow_avatars != $allow_avatars )
				{ // invalidate all PageCaches
					invalidate_pagecaches();
				}

				$Messages->add( T_('General settings updated.'), 'success' );
			}
		}

		// Redirect so that a reload doesn't write to the DB twice:
		header_redirect( '?ctrl=usersettings', 303 ); // Will EXIT
		// We have EXITed already at this point!!

		break;
}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( T_('Settings'), '?ctrl=settings' );
$AdminUI->breadcrumbpath_add( T_('User latitude'), '?ctrl=usersettings' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'users/views/_settings.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: settings.ctrl.php,v $
 */
?>