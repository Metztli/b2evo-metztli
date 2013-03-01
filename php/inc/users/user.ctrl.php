<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI_general
 */
global $AdminUI;

param( 'user_tab', 'string' );
if( empty($user_tab) )
{
	$user_tab = 'profile';
}

$AdminUI->set_path( 'users', 'users' );

param_action();

param( 'user_ID', 'integer', NULL );	// Note: should NOT be memorized (would kill navigation/sorting) use memorize_param() if needed
param( 'redirect_to', 'string', NULL );

/**
 * @global boolean true, if user is only allowed to edit his profile
 */
$user_profile_only = ! $current_User->check_perm( 'users', 'view' );

if( $user_profile_only )
{ // User has no permissions to view: he can only edit his profile

	if( isset($user_ID) && $user_ID != $current_User->ID )
	{ // User is trying to edit something he should not: add error message (Should be prevented by UI)
		$Messages->add( T_('You have no permission to view other users!'), 'error' );
	}

	// Make sure the user only edits himself:
	$user_ID = $current_User->ID;
	if( ! in_array( $action, array( 'update', 'update_avatar', 'edit', 'default_settings' ) ) )
	{
		$action = 'edit';
	}
}

/*
 * Load editable objects and set $action (while checking permissions)
 */

$UserCache = & get_UserCache();

if( ! is_null($user_ID) )
{ // User selected
	if( $action == 'update' && $user_ID == 0 )
	{ // we create a new user
		$edited_User = new User();
		$edited_User->set_datecreated( $localtimenow );
	}
	elseif( ($edited_User = & $UserCache->get_by_ID( $user_ID, false )) === false )
	{	// We could not find the User to edit:
		unset( $edited_User );
		forget_param( 'user_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('User') ), 'error' );
		$action = 'list';
	}

	if( $action != 'view' )
	{ // check edit permissions
		if( ! $current_User->check_perm( 'users', 'edit' )
		    && $edited_User->ID != $current_User->ID )
		{ // user is only allowed to _view_ other user's profiles
			$Messages->add( T_('You have no permission to edit other users!'), 'error' );
			$action = 'view';
		}
		elseif( $demo_mode && ( $edited_User->ID <= 3 ) && ( $edited_User->ID > 0 ) )
		{ // Demo mode restrictions: users created by install process cannot be edited
			$Messages->add( sprintf( T_('You cannot edit %s profile in demo mode!'), $edited_User->login ), 'error' );

			if( strpos( $action, 'delete_' ) === 0 || $action == 'promote' )
			{   // Fallback to list/view action
				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&amp;action=list' ) );
			}
			else
			{
				$action = 'view';
			}
		}
	}
}

/*
 * Perform actions, if there were no errors:
 */
if( !$Messages->has_errors() )
{ // no errors
	switch( $action )
	{
		case 'new':
			// We want to create a new user:
			if( isset( $edited_User ) )
			{ // We want to use a template
				$new_User = $edited_User; // Copy !
				$new_User->set( 'ID', 0 );
				$edited_User = & $new_User;
			}
			else
			{ // We use an empty user:
				$edited_User = new User();
			}

			// Determine if the user must validate before using the system:
			$edited_User->set( 'validated', ! $Settings->get('newusers_mustvalidate') );
			break;

		case 'remove_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			if( !$edited_User->remove_avatar() )
			{ // could not remove the avatar
				$action = 'view';
				break;
			}

			$action = 'edit';
			break;

		case 'upload_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			$result = $edited_User->update_avatar_from_upload();
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			$action = 'edit';
			break;

		case 'update_avatar':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}
			$file_ID = param( 'file_ID', 'integer', NULL );

			$result = $edited_User->update_avatar( $file_ID );
			if( $result !== true )
			{
				$action = $result;
				break;
			}
			$action = 'edit';
			break;

		case 'update':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			// Update existing user OR create new user:
			if( empty($edited_User) || !is_object($edited_User) )
			{
				$Messages->add( 'No user set!' ); // Needs no translation, should be prevented by UI.
				$action = 'list';
				break;
			}

			// if new user is true then it will redirect to user list after user has been created
			$is_new_user = $edited_User->ID == 0 ? true : false;

			$result = $edited_User->update_from_request( $is_new_user );
			if( $result !== true )
			{
				$action = $result;
				break;
			}

			if( param( 'advanced_form', 'boolean', false ) )
			{
				$current_admin_skin = param( 'current_admin_skin', 'string' );
				if( ( $current_admin_skin == $UserSettings->get( 'admin_skin', $current_User->ID ) ) &&
					( $current_admin_skin == $UserSettings->get( 'admin_skin', $edited_User->ID ) ) )
				{ // Save Admin skin display settings if admin skin wasn't changed, and
					// edited user admin skin is the same as current user admin skin
					$AdminUI->set_skin_settings( $edited_User->ID );
				}

				if( $UserSettings->dbupdate() )
				{
					$Messages->add( T_('User feature settings have been changed.'), 'success');
				}

				// PluginUserSettings
				load_funcs('plugins/_plugin.funcs.php');

				$any_plugin_settings_updated = false;
				$Plugins->restart();
				while( $loop_Plugin = & $Plugins->get_next() )
				{
					$pluginusersettings = $loop_Plugin->GetDefaultUserSettings( $tmp_params = array('for_editing'=>true) );
					if( empty($pluginusersettings) )
					{
						continue;
					}

					// Loop through settings for this plugin:
					foreach( $pluginusersettings as $set_name => $set_meta )
					{
						autoform_set_param_from_request( $set_name, $set_meta, $loop_Plugin, 'UserSettings', $edited_User );
					}

					// Let the plugin handle custom fields:
					$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params = array(
						'User' => & $edited_User, 'action' => 'save' ) );

					if( $ok_to_update === false )
					{
						$loop_Plugin->UserSettings->reset();
					}
					elseif( $loop_Plugin->UserSettings->dbupdate() )
					{
						$any_plugin_settings_updated = true;
					}
				}

				if( $any_plugin_settings_updated )
				{
					$Messages->add( T_('Usersettings of Plugins have been updated.'), 'success' );
				}
			}

			if( $is_new_user )
			{
				header_redirect( regenerate_url( 'ctrl,action', 'ctrl=users&amp;action=list', '', '&' ), 303 );
			}
			else
			{
				header_redirect( regenerate_url( '', 'user_ID='.$edited_User->ID.'&action=edit&user_tab='.$user_tab, '', '&' ), 303 );
			}
			break;

		case 'default_settings':
			// Check that this action request is not a CSRF hacked request:
			$Session->assert_received_crumb( 'user' );

			$reload_page = false; // We set it to true, if a setting changes that needs a page reload (locale, admin skin, ..)

			// Admin skin:
			$cur_admin_skin = $UserSettings->get('admin_skin');

			$UserSettings->delete( 'admin_skin', $edited_User->ID );
			if( $cur_admin_skin
					&& $UserSettings->get('admin_skin', $edited_User->ID ) != $cur_admin_skin
					&& ($edited_User->ID == $current_User->ID) )
			{ // admin_skin has changed:
				$reload_page = true;
			}

			// Remove all UserSettings where a default exists:
			foreach( $UserSettings->_defaults as $k => $v )
			{
				$UserSettings->delete( $k, $edited_User->ID );
			}

			// Update user settings:
			if( $UserSettings->dbupdate() ) $Messages->add( T_('User feature settings have been changed.'), 'success');

			// PluginUserSettings
			$any_plugin_settings_updated = false;
			$Plugins->restart();
			while( $loop_Plugin = & $Plugins->get_next() )
			{
				$pluginusersettings = $loop_Plugin->GetDefaultUserSettings( $tmp_params = array('for_editing'=>true) );

				if( empty($pluginusersettings) )
				{
					continue;
				}

				foreach( $pluginusersettings as $k => $l_meta )
				{
					if( isset($l_meta['layout']) || ! empty($l_meta['no_edit']) )
					{ // a layout "setting" or not for editing
						continue;
					}

					$loop_Plugin->UserSettings->delete($k, $edited_User->ID);
				}

				// Let the plugin handle custom fields:
				$ok_to_update = $Plugins->call_method( $loop_Plugin->ID, 'PluginUserSettingsUpdateAction', $tmp_params = array(
					'User' => & $edited_User, 'action' => 'reset' ) );

				if( $ok_to_update === false )
				{
					$loop_Plugin->UserSettings->reset();
				}
				elseif( $loop_Plugin->UserSettings->dbupdate() )
				{
					$any_plugin_settings_updated = true;
				}
			}
			if( $any_plugin_settings_updated )
			{
				$Messages->add( T_('Usersettings of Plugins have been updated.'), 'success' );
			}

			// Always display the profile again:
			$action = 'edit';

			if( $reload_page )
			{ // reload the current page through header redirection:
				header_redirect( regenerate_url( '', 'user_ID='.$edited_User->ID.'&action='.$action, '', '&' ) ); // will save $Messages into Session
			}
			break;
	}
}

// require colorbox js
require_js_helper( 'colorbox' );

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Users'), '?ctrl=users' );
$AdminUI->breadcrumbpath_add( $edited_User->login, '?ctrl=user&amp;user_ID='.$edited_User->ID );
switch( $user_tab )
{
	case 'profile':
		$AdminUI->breadcrumbpath_add( T_('Profile'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'avatar':
		if( isset($GLOBALS['files_Module']) )
		{
			$AdminUI->breadcrumbpath_add( T_('Profile picture'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		}
		break;
	case 'pwdchange':
		$AdminUI->breadcrumbpath_add( T_('Change password'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'userprefs':
		$AdminUI->breadcrumbpath_add( T_('Preferences'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'advanced':
		$AdminUI->breadcrumbpath_add( T_('Advanced'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'admin':
		$AdminUI->breadcrumbpath_add( T_('Admin'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
	case 'blogs':
		$AdminUI->breadcrumbpath_add( T_('Personal blogs'), '?ctrl=user&amp;user_ID='.$edited_User->ID.'&amp;user_tab='.$user_tab );
		break;
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

/*
 * Display appropriate payload:
 */
switch( $action )
{
	case 'nil':
		// Display NO payload!
		break;

	case 'new':
	case 'view':
	case 'edit':
	default:

		switch( $user_tab )
		{
			case 'profile':
				// Display user identity form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_identity.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'avatar':
				// Display user avatar form:
				if( $Settings->get('allow_avatars') )
				{
					$AdminUI->disp_payload_begin();
					$AdminUI->disp_view( 'users/views/_user_avatar.form.php' );
					$AdminUI->disp_payload_end();
				}
				break;
			case 'pwdchange':
				// Display user password form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_password.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'userprefs':
				// Display user preferences form:
				$AdminUI->disp_payload_begin();
				$AdminUI->disp_view( 'users/views/_user_preferences.form.php' );
				$AdminUI->disp_payload_end();
				break;
			case 'advanced':
				// Display user advanced form:
				$AdminUI->disp_view( 'users/views/_user_advanced.form.php' );
				break;
			case 'admin':
				// Display user admin form:
				$AdminUI->disp_view( 'users/views/_user_admin.form.php' );
				break;
			case 'blogs':
				// Display user blog list:
				$AdminUI->disp_view( 'users/views/_user_coll_list.view.php' );
				break;
		}

		break;
}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: user.ctrl.php,v $
 */
?>