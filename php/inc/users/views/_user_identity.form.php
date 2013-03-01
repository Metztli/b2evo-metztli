<?php
/**
 * This file implements the UI view for the user properties.
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
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 *
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _user_identity.form.php 9 2011-10-24 22:32:00Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

/**
 * @var instance of GeneralSettings class
 */
global $Settings;
/**
 * @var instance of UserSettings class
 */
global $UserSettings;
/**
 * @var instance of User class
 */
global $edited_User;
/**
 * @var instance of User class
 */
global $current_User;
/**
 * @var current action
 */
global $action;
/**
 * @var user permission, if user is only allowed to edit his profile
 */
global $user_profile_only;
/**
 * @var the action destination of the form (NULL for pagenow)
 */
global $form_action;

?>
<script type="text/javascript">
	// Identity shown dropdown list handler
	// init variables
	var idmodes = [];
	var laquo = String.fromCharCode(171);
	var raquo = String.fromCharCode(187);
	idmodes["nickname"] = " " + laquo + "<?php echo T_( 'Nickname' ); ?>" + raquo;
	idmodes["login"] = " " + laquo + "<?php echo T_( 'Login' ); ?>" + raquo;
	idmodes["firstname"] = " " + laquo + "<?php echo T_( 'First name' ); ?>" + raquo;
	idmodes["lastname"] = " " + laquo + "<?php echo T_( 'Last name' ); ?>" + raquo;
	idmodes["namefl"] = " " + laquo + "<?php echo T_( 'First name' ).' '.T_( 'Last name' ); ?>" + raquo;
	idmodes["namelf"] = " " + laquo + "<?php echo T_( 'Last name' ).' '.T_( 'First name' ); ?>" + raquo;

	// Identity fields on change fucntion
	function idmodes_onchange( fieldname )
	{
		var fieldText = jQuery( '#edited_user_' + fieldname ).val();
		if( fieldText == "" )
		{
			fieldText = "-";
		}
		jQuery( '#edited_user_idmode option[value="' + fieldname + '"]' ).text( fieldText + idmodes[fieldname] );
	}

	// Handle Identity shown composite fields (-First name Last name- and -Last name First name-)
	function name_onchange()
	{
		var firstName = jQuery( '#edited_user_firstname' ).val();
		var lastName = jQuery( '#edited_user_lastname' ).val();
		jQuery( '#edited_user_idmode option[value="namefl"]' ).text( firstName + " " + lastName + idmodes["namefl"] );
		jQuery( '#edited_user_idmode option[value="namelf"]' ).text( lastName + " " + firstName + idmodes["namelf"] );
	}
</script>
<?php

$has_full_access = $current_User->check_perm( 'users', 'edit' );
$new_user_creating = ( $edited_User->ID == 0 );

$Form = new Form( $form_action, 'user_checkchanges' );

if( !$user_profile_only )
{
	echo_user_actions( $Form, $edited_User, $action );
}

$is_admin = is_admin_page();
if( $is_admin )
{
	if( $new_user_creating )
	{
		$form_title = T_('Edit user profile');
	}
	else
	{
		$form_title = get_usertab_header( $edited_User, 'profile', T_( 'Edit profile' ) );
	}
	$form_class = 'fform';
}
else
{
	$form_title = '';
	$form_class = 'bComment';
}

	$Form->begin_form( $form_class, $form_title );

	$Form->add_crumb( 'user' );
	$Form->hidden_ctrl();
	$Form->hidden( 'user_tab', 'profile' );
	$Form->hidden( 'identity_form', '1' );

	$Form->hidden( 'user_ID', $edited_User->ID );

if( $new_user_creating )
{
	$current_User->check_perm( 'users', 'edit', true );
	$edited_User->get_Group();

	$Form->begin_fieldset( T_( 'New user' ), array( 'class' => 'fieldset clear' ) );

	$chosengroup = ( $edited_User->Group === NULL ) ? $Settings->get('newusers_grp_ID') : $edited_User->Group->ID;
	$GroupCache = & get_GroupCache();
	$Form->select_object( 'edited_user_grp_ID', $chosengroup, $GroupCache, T_( 'User group' ) );

	$field_note = '[0 - 10] '.sprintf( T_('See <a %s>online manual</a> for details.'), 'href="http://manual.b2evolution.net/User_levels"' );
	$Form->text_input( 'edited_user_level', $edited_User->get('level'), 2, T_('User level'), $field_note, array( 'required' => true ) );

	$email_fieldnote = '<a href="mailto:'.$edited_User->get('email').'">'.get_icon( 'email', 'imgtag', array('title'=>T_('Send an email')) ).'</a>';
	$Form->text_input( 'edited_user_email', $edited_User->email, 30, T_('Email'), $email_fieldnote, array( 'maxlength' => 100, 'required' => true ) );
	$Form->checkbox( 'edited_user_validated', $edited_User->get('validated'), T_('Validated email'), T_('Has this email address been validated (through confirmation email)?') );

	$Form->end_fieldset();
}

	/***************  Identity  **************/

$Form->begin_fieldset( T_('Identity') );

if( $action != 'view' )
{   // We can edit the values:

	$Form->text_input( 'edited_user_login', $edited_User->login, 20, T_('Login'), '', array( 'required' => true, 'onchange' => 'idmodes_onchange( "login" )' ) );
	$Form->text_input( 'edited_user_firstname', $edited_User->firstname, 20, T_('First name'), '', array( 'maxlength' => 50 ) );
	$Form->text_input( 'edited_user_lastname', $edited_User->lastname, 20, T_('Last name'), '', array( 'maxlength' => 50 ) );

	$nickname_editing = $Settings->get( 'nickname_editing' );
	if( ( $nickname_editing == 'edited-user' && $edited_User->ID == $current_User->ID ) || ( $nickname_editing != 'hidden' && $has_full_access ) )
	{
		$Form->text_input( 'edited_user_nickname', $edited_User->nickname, 20, T_('Nickname'), '', array( 'maxlength' => 50, 'required' => true, 'onchange' => 'idmodes_onchange( "nickname" )' ) );
	}
	else
	{
		$Form->hidden( 'edited_user_nickname', $edited_User->nickname );
	}

	$Form->select( 'edited_user_idmode', $edited_User->get( 'idmode' ), array( &$edited_User, 'callback_optionsForIdMode' ), T_('Identity shown') );

	$CountryCache = & get_CountryCache();
	$Form->select_input_object( 'edited_user_ctry_ID', $edited_User->ctry_ID, $CountryCache, T_('Country'), array( 'required' => !$has_full_access, 'allow_none' => $has_full_access ) );

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$Form->radio( 'edited_user_gender', $edited_User->get('gender'), array(
					array( 'M', T_('A man') ),
					array( 'F', T_('A woman') ),
				), T_('I am') );
	}
}
else
{ // display only

	if( $Settings->get('allow_avatars') )
	{
		$Form->info( T_('Profile picture'), $edited_User->get_avatar_imgtag() );
	}

	$Form->info( T_('Login'), $edited_User->get('login') );
	$Form->info( T_('First name'), $edited_User->get('firstname') );
	$Form->info( T_('Last name'), $edited_User->get('lastname') );
	$Form->info( T_('Nickname'), $edited_User->get('nickname') );
	$Form->info( T_('Identity shown'), $edited_User->get('preferredname') );
	$Form->info( T_('Country'), $edited_User->get_country_name() );

	if( $Settings->get( 'registration_require_gender' ) != 'hidden' )
	{
		$user_gender = $edited_User->get( 'gender' );
		if( ! empty( $user_gender ) )
		{
			$Form->info( T_('Gender'), ( $user_gender == 'M' ) ? T_( 'Male' ) : T_( 'Female' ) );
		}
	}
	$Form->info( T_('Multiple sessions'), ($UserSettings->get('login_multiple_sessions', $edited_User->ID) ? T_('Allowed') : T_('Forbidden')) );
}

$Form->end_fieldset();

	/***************  Password  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{ // We can edit the values:

	$Form->begin_fieldset( T_('Password') );
		$Form->password_input( 'edited_user_pass1', '', 20, T_('New password'), array( 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
		$Form->password_input( 'edited_user_pass2', '', 20, T_('Confirm new password'), array( 'note'=>sprintf( T_('Minimum length: %d characters.'), $Settings->get('user_minpwdlen') ), 'maxlength' => 50, 'required' => true, 'autocomplete'=>'off' ) );
	$Form->end_fieldset();
}

	/***************  Multiple sessions  **************/

if( empty( $edited_User->ID ) && $action != 'view' )
{	// New user will be created with default multiple_session setting

	$multiple_sessions = $Settings->get( 'multiple_sessions' );
	if( $multiple_sessions == 'userset_default_yes' || ( $has_full_access && $multiple_sessions == 'adminset_default_yes' ) )
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', 1 );
	}
	else
	{
		$Form->hidden( 'edited_user_set_login_multiple_sessions', 0 );
	}
}

	/***************  Additional info  **************/

$Form->begin_fieldset( T_('Additional info') );

if( ($url = $edited_User->get('url')) != '' )
{
	if( !preg_match('#://#', $url) )
	{
		$url = 'http://'.$url;
	}
	$url_fieldnote = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
}
else
	$url_fieldnote = '';

if( $edited_User->get('icq') != 0 )
	$icq_fieldnote = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$edited_User->get('icq').'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
else
	$icq_fieldnote = '';

if( $edited_User->get('aim') != '' )
	$aim_fieldnote = '<a href="aim:goim?screenname='.$edited_User->get('aim').'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
else
	$aim_fieldnote = '';


if( $action != 'view' )
{ // We can edit the values:

	if( $new_user_creating )
	{
		$Form->text_input( 'edited_user_source', $edited_User->source, 30, T_('Source'), '', array( 'maxlength' => 30 ) );
	}
	$Form->text_input( 'edited_user_url', $edited_User->url, 30, T_('URL'), $url_fieldnote, array( 'maxlength' => 100 ) );
	$Form->text_input( 'edited_user_icq', $edited_User->icq, 30, T_('ICQ'), $icq_fieldnote, array( 'maxlength' => 10 ) );
	$Form->text_input( 'edited_user_aim', $edited_User->aim, 30, T_('AIM'), $aim_fieldnote, array( 'maxlength' => 50 ) );
	$Form->text_input( 'edited_user_msn', $edited_User->msn, 30, T_('MSN IM'), '', array( 'maxlength' => 100 ) );
	$Form->text_input( 'edited_user_yim', $edited_User->yim, 30, T_('YahooIM'), '', array( 'maxlength' => 50 ) );

}
else
{ // display only

	$Form->info( T_('URL'), $edited_User->get('url'), $url_fieldnote );
	$Form->info( T_('ICQ'), $edited_User->get('icq', 'formvalue'), $icq_fieldnote );
	$Form->info( T_('AIM'), $edited_User->get('aim'), $aim_fieldnote );
	$Form->info( T_('MSN IM'), $edited_User->get('msn') );
	$Form->info( T_('YahooIM'), $edited_User->get('yim') );

  }

$Form->end_fieldset();

	/***************  Experimental  **************/

$Form->begin_fieldset( T_('Experimental') );

// This totally needs to move into User object
global $DB;

// Get existing userfields:
$userfields = $DB->get_results( '
	SELECT uf_ID, ufdf_ID, ufdf_type, ufdf_name, uf_varchar
		FROM T_users__fields LEFT JOIN T_users__fielddefs ON uf_ufdf_ID = ufdf_ID
	 WHERE uf_user_ID = '.$edited_User->ID.'
	 ORDER BY uf_ID' );

foreach( $userfields as $userfield )
{
	switch( $userfield->ufdf_ID )
	{
		case 10200:
			$field_note = '<a href="aim:goim?screenname='.$userfield->uf_varchar.'&amp;message=Hello">'.get_icon( 'play', 'imgtag', array('title'=>T_('Instant Message to user')) ).'</a>';
			break;

		case 10300:
			$field_note = '<a href="http://wwp.icq.com/scripts/search.dll?to='.$userfield->uf_varchar.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Search on ICQ.com')) ).'</a>';
			break;

		default:
			if( $userfield->ufdf_ID >= 100000 && $userfield->ufdf_ID < 200000 )
			{
				$url = $userfield->uf_varchar;
				if( !preg_match('#://#', $url) )
				{
					$url = 'http://'.$url;
				}
				$field_note = '<a href="'.$url.'" target="_blank">'.get_icon( 'play', 'imgtag', array('title'=>T_('Visit the site')) ).'</a>';
			}
			else
			{
				$field_note = '';
			}
	}

	$uf_val = param( 'uf_'.$userfield->uf_ID, 'string', NULL );
	if( is_null( $uf_val ) )
	{	// No value submitted yet, get DB val:
		$uf_val = $userfield->uf_varchar;
	}

	// Display existing field:
	$Form->text_input( 'uf_'.$userfield->uf_ID, $uf_val, 50, $userfield->ufdf_name, $field_note, array( 'maxlength' => 255 ) );
}

// Get list of possible field types:
// TODO: use userfield manipulation functions
$userfielddefs = $DB->get_results( '
	SELECT ufdf_ID, ufdf_type, ufdf_name
		FROM T_users__fielddefs
	 ORDER BY ufdf_ID' );
// New fields:
// TODO: JS for adding more than 3 at a time.
for( $i=1; $i<=3; $i++ )
{
	$label = '<select name="new_uf_type_'.$i.'"><option value="">'.T_('Add field...').'</option><optgroup label="'.T_('Instant Messaging').'">';
	foreach( $userfielddefs as $fielddef )
	{
		// check for group header:
		switch( $fielddef->ufdf_ID )
		{
			case 50000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Phone').'">';
				break;
			case 100000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Web').'">';
				break;
			case 200000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Organization').'">';
				break;
			case 300000:
				$label .= "\n".'</optgroup><optgroup label="'.T_('Address').'">';
				break;
		}
		$label .= "\n".'<option value="'.$fielddef->ufdf_ID.'"';
		if( param( 'new_uf_type_'.$i, 'string', '' ) == $fielddef->ufdf_ID )
		{	// We had selected this type before getting an error:
			$label .= ' selected="selected"';
		}
		$label .= '>'.$fielddef->ufdf_name.'</option>';
	}
	$label .= '</optgroup></select>';

	$Form->text_input( 'new_uf_val_'.$i, param( 'new_uf_val_'.$i, 'string', '' ), 30, $label, '', array('maxlength' => 255, 'clickable_label'=>false) );
}

$Form->end_fieldset();

	/***************  Buttons  **************/

if( $action != 'view' )
{ // Edit buttons
	$action_buttons = array(
		array( '', 'actionArray[update]', T_('Save !'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ) );
	if( $is_admin )
	{
		// dh> TODO: Non-Javascript-confirm before trashing all settings with a misplaced click.
		$action_buttons[] = array( 'type' => 'submit', 'name' => 'actionArray[default_settings]', 'value' => T_('Restore defaults'), 'class' => 'ResetButton',
			'onclick' => "return confirm('".TS_('This will reset all your user settings.').'\n'.TS_('This cannot be undone.').'\n'.TS_('Are you sure?')."');" );
	}
	$Form->buttons( $action_buttons );
}


$Form->end_form();

?>
<script type="text/javascript">
	// handle firstname and lastname change in the Identity shown dropdown list
	jQuery( '#edited_user_firstname' ).change( function()
	{
		// change First name text
		idmodes_onchange( "firstname" );
		// change -First name Last name- and -Last name First name- texts
		name_onchange();
	} );
	jQuery( '#edited_user_lastname' ).change( function()
	{
		// change Last name text
		idmodes_onchange( "lastname" );
		// change -First name Last name- and -Last name First name- texts
		name_onchange();
	} );
</script>
<?php

/*
 * $Log: _user_identity.form.php,v $
 */
?>