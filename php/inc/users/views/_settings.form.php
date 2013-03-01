<?php

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of User class
 */
global $current_User;

$current_User->check_perm( 'users', 'view', true );

$Form = new Form( NULL, 'usersettings_checkchanges' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'usersettings' );
	$Form->hidden( 'ctrl', 'usersettings' );
	$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('User latitude') );

	$Form->checkbox_input( 'allow_avatars', $Settings->get( 'allow_avatars', true ), T_('Allow profile pictures'), array( 'note'=>T_('Allow users to upload profile pictures.') ) );

	$Form->radio( 'uset_nickname_editing', $Settings->get( 'nickname_editing' ), array(
					array( 'edited-user', T_('Can be edited by user') ),
					array( 'edited-admin', T_('Can be edited by admins only') ),
					array( 'hidden', T_('Hidden') )
				), T_('Nickname'), true );

	$Form->radio( 'uset_multiple_sessions', $Settings->get( 'multiple_sessions' ), array(
					array( 'never', T_('Never allow') ),
					array( 'adminset_default_no', T_('Let admins decide for each user, default to "no" for new users') ),
					array( 'userset_default_no', T_('Let users decide, default to "no" for new users') ),
					array( 'userset_default_yes', T_('Let users decide, default to "yes" for new users') ),
					array( 'adminset_default_yes', T_('Let admins decide for each user, default to "yes" for new users') ),
					array( 'always', T_('Always allow') )
				), T_('Multiple sessions'), true );

$Form->end_fieldset();

if( $current_User->check_perm( 'users', 'edit' ) )
{
	$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

$Form->end_form();

/*
 * $Log: _settings.form.php,v $
 */
?>