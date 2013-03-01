<?php
/**
 * This file implements the login form
 *
 * This file is not meant to be called directly.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author asimo: Evo Factory / Attila Simo
 *
 * @version $Id: _login.disp.php 1518 2012-07-16 07:16:42Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $dummy_fields;

$login = param( $dummy_fields[ 'login' ], 'string', '' );
$email = param( $dummy_fields[ 'email' ], 'string', '' );
$action = param( 'action', 'string', '' );
$redirect_to = param( 'redirect_to', 'string', '' );
$login_required = ( $action == 'req_login' );

if( is_logged_in() && ( $action != 'req_validatemail' ) )
{ // already logged in
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

global $admin_url, $ReqHost, $htsvr_url;

if( !isset( $redirect_to ) )
{
	$redirect_to = regenerate_url( 'disp' );
}

if( $action != 'req_validatemail' )
{ // -----------------------------------------------------------------------------------------------------------------

	if( empty($login_required)
		&& $action != 'req_validatemail'
		&& strpos($redirect_to, $admin_url) !== 0
		&& strpos($ReqHost.$redirect_to, $admin_url ) !== 0 )
	{ // No login required, allow to pass through
		// TODO: dh> validate redirect_to param?!
		$links[] = '<a href="'.htmlspecialchars(url_rel_to_same_host($redirect_to, $ReqHost)).'">'
		./* Gets displayed as link to the location on the login form if no login is required */ T_('Abort login!').'</a>';
	}

	if( is_logged_in() )
	{ // if we arrive here, but are logged in, provide an option to logout (e.g. during the email
		// validation procedure)
		$links[] = get_user_logout_link();
	}

	if( !empty($links) )
	{
		echo '<div style="float:right; margin: 0 1em">'.implode( $links, ' &middot; ' ).'</div>
		<div class="clear"></div>';
	}

	$Form = new Form( '', 'login_form', 'post' );

	$Form->begin_form( 'bComment' );

	$Form->hidden( 'redirect_to', $redirect_to );
	$Form->hidden( 'inskin', true );
	$Form->add_crumb( 'loginform' );

	$Form->begin_field();
	$Form->text_input( $dummy_fields[ 'login' ], $login, 18, T_('Login'), '<br />'.T_('Type your username, <b>not</b> your email address.'),
					array( 'maxlength' => 20, 'class' => 'input_text', 'required'=>true ) );
	$Form->end_field();

	$pwd_note = '<a href="'.$htsrv_url_sensitive.'login.php?action=lostpassword&amp;redirect_to='
		.rawurlencode( url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	if( !empty($login) )
	{
		$pwd_note .= '&amp;'.$dummy_fields[ 'login' ].'='.rawurlencode($login);
	}
	$pwd_note .= '">'.T_('Lost password ?').'</a>';

	$Form->begin_field();
	$Form->password_input( $dummy_fields[ 'pwd' ], '', 18, T_('Password'), array( 'note'=>$pwd_note, 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->end_field();

	// Allow a plugin to add fields/payload
	$Plugins->trigger_event( 'DisplayLoginFormFieldset', array( 'Form' => & $Form ) );

	// Submit button:
	$submit_button = array( array( 'name'=>'login_action[login]', 'value'=>T_('Log in!'), 'class'=>'search', 'style'=>'font-size: 120%' ) );

	$Form->buttons_input($submit_button);

	// $Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	echo '<div class="login_actions" style="text-align:right; margin: 1em 0 1ex">';
	echo get_user_register_link( '<strong>', '</strong>', T_('No account yet? Register here').' &raquo;', '#', true /*disp_when_logged_in*/, $redirect_to, 'inskin login' );
	echo '</div>';

	$Form->end_form();

	echo '<div class="notes" style="margin: 1em"><a href="'.$htsrv_url.'login.php?redirect_to='.$redirect_to.'">'.T_( 'Use standard login form instead').' &raquo;</a></div>
	<div class="clear"></div>';
}
else
{	// -----------------------------------------------------------------------------------------------------------------

	$Form = new Form( $htsrv_url_sensitive.'login.php', 'login_form', 'post' );

	$Form->begin_form( 'bComment' );

	$Form->add_crumb( 'validateform' );
	$Form->hidden( 'action', 'req_validatemail');
	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	$Form->hidden( 'inskin', true );
	if( isset( $blog ) )
	{
		$Form->hidden( 'blog', $blog );
	}
	$Form->hidden( 'req_validatemail_submit', 1 ); // to know if the form has been submitted

	$Form->begin_fieldset();

	echo '<ol>';
	echo '<li>'.T_('Please confirm your email address below.').'</li>';
	echo '<li>'.T_('An email will be sent to this address immediately.').'</li>';
	echo '<li>'.T_('As soon as you receive the email, click on the link therein to activate your account.').'</li>';
	echo '</ol>';

	if( empty( $email ) )
	{
		$email = $current_User->get( 'email' );
	}
	$Form->text_input( $dummy_fields[ 'email' ], $email, 16, T_('Email'), '', array( 'maxlength'=>255, 'class'=>'input_text', 'required'=>true ) );
	$Form->end_fieldset();

	// Submit button:
	$submit_button = array( array( 'name'=>'submit', 'value'=>T_('Send me an email now'), 'class'=>'submit' ) );

	$Form->buttons_input($submit_button);
	$Form->end_form();
}


/*
 * $Log: _login.disp.php,v $
 */
?>