<?php
/**
 * This file implements the register form
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
 * @version $Id: _register.disp.php 1518 2012-07-16 07:16:42Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'regional/model/_country.class.php', 'Country' );

global $Settings;

global $htsrv_url;

global $notify_from;

global $Blog, $dummy_fields;

if( is_logged_in() )
{ // if a user is already logged in don't allow to register
	echo '<p>'.T_('You are already logged in').'</p>';
	return;
}

if( ! $Settings->get('newusers_canregister') )
{
	echo '<p>'.T_('User registration is currently not allowed.').'</p>';
	return;
}

$action = param( 'action', 'string', '' );
$login = param( $dummy_fields[ 'login' ], 'string', '' );
$email = param( $dummy_fields[ 'email' ], 'string', '' );
$country = param( 'country', 'string', NULL );
$gender = param( 'gender', 'string', false );
$source = param( 'source', 'string', '' );
$redirect_to = param( 'redirect_to', 'string', '' );

$Form = new Form( $htsrv_url.'register.php', 'login_form', 'post' );

$Form->add_crumb( 'regform' );
if( empty( $action ) )
{
	$action = 'register';
}

$Form->hidden( 'inskin', true );
$Form->hidden( 'blog', $Blog->ID );

if( $action == 'register' )
{ // disp register form
	$Form->begin_form( 'bComment' );

	$Form->hidden( 'action', 'register' );
	$Form->hidden( 'source', $source );
	// fp>asimo: why is there no hidden redirect_to here?

	$Form->begin_field();
	$Form->text_input( $dummy_fields[ 'login' ], $login, 22, T_('Login'), T_('Choose a username.'), array( 'maxlength' => 20, 'class' => 'input_text', 'required' => true ) );
	$Form->end_field();

	$Form->begin_field();
	$Form->password_input( $dummy_fields[ 'pass1' ], '', 18, T_('Password'), array( 'note'=>T_('Choose a password.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->password_input( $dummy_fields[ 'pass2' ], '', 18, '', array( 'note'=>T_('Please type your password again.'), 'maxlength' => 70, 'class' => 'input_text', 'required'=>true ) );
	$Form->end_field();

	$Form->begin_field();
	$Form->text_input( $dummy_fields[ 'email' ], $email, 50, T_('Email'), '<br />'.T_('We respect your privacy. Your email will remain strictly confidential.'),
					array( 'maxlength'=>255, 'class'=>'input_text wide_input', 'required'=>true ) );

	$registration_require_country = (bool)$Settings->get('registration_require_country');

	if( $registration_require_country )
	{ // country required
		$CountryCache = & get_CountryCache();
		$Form->select_input_object( 'country', $country, $CountryCache, T_('Country'), array('allow_none'=>true, 'required'=>true) );
	}

	$registration_require_gender = $Settings->get( 'registration_require_gender' );
	if( $registration_require_gender == 'required' )
	{ // gender required
		$Form->radio_input( 'gender', $gender, array(
					array( 'value' => 'M', 'label' => T_('A man') ),
					array( 'value' => 'F', 'label' => T_('A woman') ),
				), T_('I am'), array( 'required' => true ) );
	}

	if( $Settings->get( 'registration_ask_locale' ) )
	{ // ask user language
		$locale = 'en_US';
		$Form->select( 'locale', $locale, 'locale_options_return', T_('Locale'), T_('Preferred language') );
	}
	$Form->end_field();

	$Form->end_fieldset();

	// Submit button:
	$submit_button = array( array( 'name'=>'register', 'value'=>T_('Register my account now!'), 'class'=>'search', 'style'=>'font-size: 120%' ) );

	$Form->buttons_input($submit_button);

	// $Form->info( '', '', sprintf( T_('Your IP address (%s) and the current time are being logged.'), $Hit->IP ) );

	echo '<div class="login_actions" style="margin: 1em 0 1ex">';
	echo '<strong><a href="'.get_login_url($redirect_to).'">&laquo; '.T_('Already have an account... ?').'</a></strong>';
	echo '</div>';

	$Form->end_form();
}
elseif( $action == "reg_complete" )
{	// -----------------------------------------------------------------------------------------------------------------
	// display register complete info ( email validation not required )
	$Form->begin_form( 'bComment' );

	$Form->hidden( 'redirect_to', url_rel_to_same_host($redirect_to, $htsrv_url_sensitive) );
	$Form->hidden( 'inskin', 1 );

	$Form->begin_fieldset();
	$Form->info( T_('Login'), $login );
	$Form->info( T_('Email'), $email );
	$Form->end_fieldset();

	echo '<p class="center"><a href="'.$Blog->gen_blogurl().'">'.T_('Continue').' &raquo;</a> ';
	echo '</p>';

	$Form->end_form();
}
elseif( $action == "reg_validation" )
{ // display "validation email sent" info ( email validation required )
	$Form->begin_form( 'bComment' );

	echo '<p>'.sprintf( T_( 'An email has just been sent to %s . Please check your email and click on the validation link you will find in that email.' ), '<b>'.$email.'</b>' ).'</p>';
	echo '<p>'.sprintf( T_( 'If you have not received the email in the next few minutes, please check your spam folder. The email was sent from %s and has the title &laquo;%s&raquo;.' ), $notify_from,
					'<b>'.sprintf( T_('Validate your email address for "%s"'), $login ).'</b>' ).'</p>';
	echo '<p>'.T_( 'If you still can\'t find the email or if you would like to try with a different email address,' ).' '.
					'<a href="'.$Blog->gen_blogurl().'">'.T_( 'click here to try again' ).'.</a></p>';

	$Form->end_form();
}




/*
 * $Log: _register.disp.php,v $
 */
?>