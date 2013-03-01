<?php
/**
 * This is the login screen. It also handles actions related to loggin in and registering.
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
 *
 * Matt FOLLETT grants Francois PLANQUE the right to license
 * Matt FOLLETT's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package htsrv
 *
 * @version $Id: login.php 1518 2012-07-16 07:16:42Z attila $
 */

/**
 * Includes:
 */
require_once dirname(__FILE__).'/../conf/_config.php';
require_once $inc_path.'_main.inc.php';

$login = param( $dummy_fields[ 'login' ], 'string', '' );
param( 'action', 'string', 'req_login' );
param( 'mode', 'string', '' );
param( 'inskin', 'boolean', false );
if( $inskin )
{
	param( 'blog', 'integer', NULL );
}

// gets used by header_redirect();
// TODO: dh> problem here is that $ReqURI won't include the e.g. "ctrl" param in a POSTed form and therefor the user lands on the default admin page after logging in (again)
// fp> I think this will fix itself when we do another improvement: 303 redirect after each POST so that we never have an issue with people trying to reload a post
param( 'redirect_to', 'string', $ReqURI );

switch( $action )
{
	case 'logout':
		logout();          // logout $Session and set $current_User = NULL

		// TODO: to give the user feedback through Messages, we would need to start a new $Session here and append $Messages to it.

		// Redirect to $baseurl on logout if redirect URI is not set. Temporarily fix until we remove actions from redirect URIs
		if( $redirect_to == $ReqURI )
			$redirect_to = $baseurl;

		header_redirect(); // defaults to redirect_to param and exits
		/* exited */
		break;


	case 'retrievepassword': // Send passwort change request by mail
		$login_required = true; // Do not display "Without login.." link on the form

		$UserCache = & get_UserCache();
		$ForgetfulUser = & $UserCache->get_by_login( $login );

		if( ! $ForgetfulUser )
		{ // User does not exist
			// pretend that the email is sent for avoiding guessing user_login
			$Messages->add( T_('If you correctly typed in your login, a link to change your password has been sent to your registered email address.' ), 'success' );
			$action = 'req_login';
			break;
		}

		// echo 'email: ', $ForgetfulUser->email;
		// echo 'locale: '.$ForgetfulUser->locale;

		if( $demo_mode && ( $ForgetfulUser->ID <= 3 ) )
		{
			$Messages->add( T_('You cannot reset this account in demo mode.'), 'error' );
			$action = 'req_login';
			break;
		}

		locale_temp_switch( $ForgetfulUser->locale );

		// DEBUG!
		// echo $message.' (password not set yet, only when sending email does not fail);

		if( empty( $ForgetfulUser->email ) )
		{
			$Messages->add( T_('You have no email address with your profile, therefore we cannot reset your password.')
				.' '.T_('Please try contacting the admin.'), 'error' );
		}
		else
		{
			$request_id = generate_random_key(22); // 22 to make it not too long for URL but unique/safe enough

			$message = T_( 'Somebody (presumably you) has requested a password change for your account.' )
				."\n\n"
				.T_('Login:')." $login\n"
				.T_('Link to change your password:')
				."\n"
				.$htsrv_url_sensitive.'login.php?action=changepwd'
					.'&'.$dummy_fields[ 'login' ].'='.rawurlencode( $ForgetfulUser->login )
					.'&reqID='.$request_id
					.'&sessID='.$Session->ID  // used to detect cookie problems
				."\n\n-- \n"
				.T_('Please note:')
				.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')
				."\n\n"
				.T_('If it was not you that requested this password change, simply ignore this mail.');

			if( ! send_mail( $ForgetfulUser->email, NULL, sprintf( T_('Password change request for %s'), $ForgetfulUser->login ), $message, $notify_from ) )
			{
				$Messages->add( T_('Sorry, the email with the link to reset your password could not be sent.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
			else
			{
				$Session->set( 'core.changepwd.request_id', $request_id, 86400 * 2 ); // expires in two days (or when clicked)
				$Session->dbsave(); // save immediately

				$Messages->add( T_('If you correctly typed in your login, a link to change your password has been sent to your registered email address.' ), 'success' );
			}
		}

		locale_restore_previous();

		$action = 'req_login';
		break;


	case 'changepwd': // Clicked "Change password request" link from a mail
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		$UserCache = & get_UserCache();
		$ForgetfulUser = & $UserCache->get_by_login($login);

		if( ! $ForgetfulUser || empty($reqID) )
		{ // This was not requested
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		if( $sessID != $Session->ID )
		{ // Another session ID than for requesting password change link used!
			$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		// Validate provided reqID against the one stored in the user's session
		if( $Session->get( 'core.changepwd.request_id' ) != $reqID )
		{
			$Messages->add( T_('Invalid password change request! Please try again...'), 'error' );
			$action = 'lostpassword';
			$login_required = true; // Do not display "Without login.." link on the form
			break;
		}

		// Link User to Session:
		$Session->set_user_ID( $ForgetfulUser->ID );

		// Add Message to change the password:
		$Messages->add( T_( 'Please change your password to something you remember now.' ), 'success' );

		// Note: the 'core.changepwd.request_id' Session setting gets removed in b2users.php

		// Redirect to the user's profile in the "users" controller:
		// TODO: This will probably fail if the user has no admin-access permission! Redirect to profile page in blog instead!?
		// redirect Will save $Messages into Session:
		header_redirect( url_add_param( $admin_url, 'ctrl=users&user_ID='.$ForgetfulUser->ID, '&' ) ); // display user's profile
		/* exited */
		break;


	case 'validatemail': // Clicked "Validate email" link from a mail
		param( 'reqID', 'string', '' );
		param( 'sessID', 'integer', '' );

		if( is_logged_in() && $current_User->validated )
		{ // Already validated, e.g. clicked on an obsolete email link:
			$Messages->add( T_('Your account has already been validated.'), 'note' );
			// no break: cleanup & redirect below
		}
		else
		{
			// Check valid format:
			if( empty($reqID) )
			{ // This was not requested
				$Messages->add( T_('Invalid email address validation request!'), 'error' );
				$action = 'req_validatemail';
				break;
			}

			// Check valid session (format only, meant as help for the user):
			if( $sessID != $Session->ID )
			{ // Another session ID than for requesting account validation link used!
				$Messages->add( T_('You have to use the same session (by means of your session cookie) as when you have requested the action. Please try again...'), 'error' );
				$action = 'req_validatemail';
				break;
			}

			// Validate provided reqID against the one stored in the user's session
			$request_ids = $Session->get( 'core.validatemail.request_ids' );
			if( ( ! is_array($request_ids) || ! in_array( $reqID, $request_ids ) )
				&& ! ( isset($current_User) && $current_User->group_ID == 1 && $reqID == 1 /* admin users can validate themselves by a button click */ ) )
			{
				$Messages->add( T_('Invalid email address validation request!'), 'error' );
				$action = 'req_validatemail';
				$login_required = true; // Do not display "Without login.." link on the form
				break;
			}

			if( ! is_logged_in() )
			{ // this can happen, if a new user registers and clicks on the "validate by email" link, without logging in first
				// Note: we reuse $reqID and $sessID in the form to come back here.

				$Messages->add( T_('Please login to validate your account.'), 'error' );
				break;
			}

			// Validate user:

			$current_User->set( 'validated', 1 );
			$current_User->dbupdate();

			$Messages->add( T_( 'Your email address has been validated.' ), 'success' );
		}

		$redirect_to = $Session->get( 'core.validatemail.redirect_to' );

		if( empty($redirect_to) && $current_User->check_perm('admin') )
		{ // User can access backoffice
			$redirect_to = $admin_url;
		}

		// Cleanup:
		$Session->delete('core.validatemail.request_ids');
		$Session->delete('core.validatemail.redirect_to');

		// redirect Will save $Messages into Session:
		header_redirect();
		/* exited */
		break;

} // switch( $action ) (1st)



/* For actions that other delegate to from the switch above: */
switch( $action )
{
	case 'req_validatemail': // Send email validation link by mail (initial form and action)
		if( ! is_logged_in() )
		{
			$Messages->add( T_('You have to be logged in to request an account validation link.'), 'error' );
			$action = '';
			break;
		}

		if( $current_User->validated || ! $Settings->get('newusers_mustvalidate') )
		{ // validating emails is not activated/necessary (check this after login, so it gets not "announced")
			$action = '';
			break;
		}

		param( 'req_validatemail_submit', 'integer', 0 ); // has the form been submitted
		$email = param( $dummy_fields[ 'email' ], 'string', $current_User->email ); // the email address is editable

		if( $req_validatemail_submit )
		{ // Form has been submitted
			param_check_email( $dummy_fields[ 'email' ], true );

			// Call plugin event to allow catching input in general and validating own things from DisplayRegisterFormFieldset event
			$Plugins->trigger_event( 'ValidateAccountFormSent' );

			if( $Messages->has_errors() )
			{
				break;
			}

			// Update user's email:
			$current_User->set_email( $email );
			if( $current_User->dbupdate() )
			{
				$Messages->add( T_('Your profile has been updated.'), 'note' );
			}

			if( $current_User->send_validate_email($redirect_to) )
			{
				$Messages->add( sprintf( /* TRANS: %s gets replaced by the user's email address */ T_('An email has been sent to your email address (%s). Please click on the link therein to validate your account.'), $current_User->dget('email') ), 'success' );
			}
			elseif( $demo_mode )
			{
				$Messages->add( T_('Sorry, could not send email. Sending email in debug mode is disabled.' ), 'error' );
			}
			else
			{
				$Messages->add( T_('Sorry, the email with the link to validate and activate your password could not be sent.')
							.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
			}
		}
		else
		{ // Form not yet submitted:
			// Add a note, if we have already sent validation links:
			$request_ids = $Session->get( 'core.validatemail.request_ids' );
			if( is_array($request_ids) && count($request_ids) )
			{
				$Messages->add( sprintf( T_('We have already sent you %d email(s) with a validation link.'), count($request_ids) ), 'note' );
			}

			if( empty($current_User->email) )
			{ // add (error) note to be displayed in the form
				$Messages->add( T_('You have no email address with your profile, therefore we cannot validate it. Please give your email address below.'), 'error' );
			}
		}
		break;
}


if( ! defined( 'EVO_MAIN_INIT' ) )
{	// Do not check this if the form was included inside of _main.inc
	// echo $htsrv_url_sensitive.'login.php';
	// echo '<br>'.$ReqHost.$ReqPath;
	if( $ReqHost.$ReqPath != $htsrv_url_sensitive.'login.php' )
	{
		$Messages->add( sprintf( T_('WARNING: you are trying to log in on <strong>%s</strong> but we expect you to log in on <strong>%s</strong>. If this is due to an automatic redirect, this will prevent you from successfully loging in. You must either fix your webserver configuration, or your %s configuration in order for these two URLs to match.'), $ReqHost.$ReqPath, $htsrv_url_sensitive.'login.php', $app_name ), 'error' );
	}
}


// Test if cookie_domain matches the URL where we want to redirect to:
if( strlen($redirect_to) )
{
	// Make it relative to the form's target, in case it has been set absolute (and can be made relative).
	// Just in case it gets sent absolute. This should not trigger this warning then..!
	$redirect_to = url_rel_to_same_host($redirect_to, $htsrv_url_sensitive);

	$cookie_domain_match = false;
	if( $redirect_to[0] == '/' )
	{ // relative => ok
		$cookie_domain_match = true;
	}
	else
	{
		$parsed_redirect_to = @parse_url($redirect_to);
		if( isset($parsed_redirect_to['host']) )
		{
			if( $cookie_domain == $parsed_redirect_to['host'] )
			{
				$cookie_domain_match = true;
			}
			elseif( !empty($cookie_domain) && $cookie_domain[0] == '.'
				&& substr($cookie_domain,1) == substr($parsed_redirect_to['host'], 1-strlen($cookie_domain)) )
			{ // cookie domain includes subdomains and matches the last part of where we want to redirect to
				$cookie_domain_match = true;
			}
		}
		else
		{
			$cookie_domain_match = preg_match( '#^https?://[a-z\-.]*'.preg_quote( $cookie_domain, '#' ).'#i', $redirect_to );
		}
	}
	if( ! $cookie_domain_match )
	{
		$Messages->add( sprintf( T_('WARNING: you are trying to log in to <strong>%s</strong> but your cookie domain is <strong>%s</strong>. You will not be able to successfully log in to the requested domain until you fix your cookie domain in your %s configuration.'), $redirect_to, $cookie_domain, $app_name ), 'error' );
	}
}


if( preg_match( '#/login.php([&?].*)?$#', $redirect_to ) )
{ // avoid "endless loops"
	$redirect_to = $admin_url;
}

// Remove login and pwd parameters from URL, so that they do not trigger the login screen again:
$redirect_to = preg_replace( '~(?<=\?|&) (login|pwd) = [^&]+ ~x', '', $redirect_to );
$Debuglog->add( 'redirect_to: '.$redirect_to );


/*
 * Display in-skin login if it's supported
 */
if( $inskin && use_in_skin_login() )
{ // in-skin display:
	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( ! empty( $Blog ) )
	{
		if( !empty( $login_error ) )
		{
			$Messages->add( T_( $login_error ) );
		}
		if( empty( $redirect_to ) )
		{
			$redirect_to = $Blog->gen_blogurl();
		}
		$redirect = url_add_param( $Blog->gen_blogurl(), 'disp=login&redirect_to='.$redirect_to, '&' );
		header_redirect( $redirect );
		// already exited here
		exit(0);
	}
}

/**
 * Display standard login screen:
 */
switch( $action )
{
	case 'lostpassword':
		// Lost password:
		// Display retrieval form:
		require $adminskins_path.'login/_lostpass_form.main.php';
		break;

	case 'req_validatemail':
		// Send email validation link by mail (initial form and action)
		// Display validation form:
		require $adminskins_path.'login/_validate_form.main.php';
		break;

	default:
		// Display login form:
		require $adminskins_path.'login/_login_form.main.php';
}

exit(0);


/*
 * $Log: login.php,v $
 */
?>