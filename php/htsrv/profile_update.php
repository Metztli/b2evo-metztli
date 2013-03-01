<?php
/**
 * This file updates the current user's profile!
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
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 *
 * @todo integrate it into the skins to avoid ugly die() on error and confusing redirect on success.
 *
 * @version $Id: profile_update.php 1010 2012-03-08 08:39:41Z attila $
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $Session;

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'profileform' );

// Getting GET or POST parameters:
param( 'checkuser_id', 'integer', '' );
param( 'newuser_firstname', 'string', '' );
param( 'newuser_lastname', 'string', '' );
param( 'newuser_nickname', 'string', '' );
param( 'newuser_idmode', 'string', '' );
param( 'newuser_locale', 'string', $default_locale );
param( 'newuser_icq', 'string', '' );
param( 'newuser_aim', 'string', '' );
param( 'newuser_msn', 'string', '' );
param( 'newuser_yim', 'string', '' );
param( 'newuser_url', 'string', '' );
param( 'newuser_email', 'string', '' );
param( 'allow_pm', 'integer', 0 );           // checkbox
param( 'allow_email', 'integer', 0 );        // checkbox
param( 'newuser_notify', 'integer', 0 );        // checkbox
param( 'newuser_ctry_ID', 'integer', 0 );
param( 'newuser_showonline', 'integer', 0 );    // checkbox
param( 'newuser_gender', 'string', NULL );
param( 'pass1', 'string', '' );
param( 'pass2', 'string', '' );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{ // must be logged in!
	bad_request_die( T_('You are not logged in.') );
}

if( $checkuser_id != $current_User->ID )
{ // Can only edit your own profile
	bad_request_die( 'You are not logged in under the same account you are trying to modify.' );
}

if( $demo_mode && ( $current_User->ID <= 3 ) )
{
	bad_request_die( sprintf( 'Demo mode: You can\'t edit %s profile!', $current_User->login ).'<br />[<a href="javascript:history.go(-1)">'
		. T_('Back to profile') . '</a>]' );
}


// Trigger event: a Plugin could add a $category="error" message here..
// This must get triggered before any internal validation and must pass all relevant params.
$Plugins->trigger_event( 'ProfileFormSent', array(
		'newuser_firstname' => & $newuser_firstname,
		'newuser_lastname' => & $newuser_lastname,
		'newuser_nickname' => & $newuser_nickname,
		'newuser_idmode' => & $newuser_idmode,
		'newuser_locale' => & $newuser_locale,
		'newuser_icq' => & $newuser_icq,
		'newuser_aim' => & $newuser_aim,
		'newuser_msn' => & $newuser_msn,
		'newuser_yim' => & $newuser_yim,
		'newuser_url' => & $newuser_url,
		'newuser_email' => & $newuser_email,
		'allow_pm' => & $allow_pm,
		'allow_email' => & $allow_email,
		'newuser_notify' => & $newuser_notify,
		'newuser_ctry_ID' => & $newuser_ctry_ID,
		'newuser_showonline' => & $newuser_showonline,
		'newuser_gender' => & $newuser_gender,
		'pass1' => & $pass1,
		'pass2' => & $pass2,
		'User' => & $current_User,
	) );


/**
 * Additional checks:
 */
profile_check_params( array(
	'nickname' => $newuser_nickname,
	'icq' => $newuser_icq,
	'email' => $newuser_email,
	'url' => $newuser_url,
	'pass1' => $pass1,
	'pass2' => $pass2,
	'pass_required' => false ), $current_User );


if( $Messages->has_errors() )
{
	headers_content_mightcache( 'text/html', 0 );		// Do NOT cache error messages! (Users would not see they fixed them)

	// TODO: dh> these error should get displayed with the profile form itself, or at least there should be a "real HTML page" here (without JS-backlink)
	$Messages->display( T_('Cannot update profile. Please correct the following errors:'),
		'[<a href="javascript:history.go(-1)">' . T_('Back to profile') . '</a>]' );
	exit(0);
}


// Do the update:

$updatepassword = '';
if( !empty($pass1) )
{
	$newuser_pass = md5($pass1);
	$current_User->set( 'pass', $newuser_pass );
}

$current_User->set( 'firstname', $newuser_firstname );
$current_User->set( 'lastname', $newuser_lastname );
$current_User->set( 'nickname', $newuser_nickname );
$current_User->set( 'icq', $newuser_icq );
$current_User->set_email( $newuser_email );
$current_User->set( 'url', $newuser_url );
$current_User->set( 'aim', $newuser_aim );
$current_User->set( 'msn', $newuser_msn );
$current_User->set( 'yim', $newuser_yim );
$current_User->set( 'idmode', $newuser_idmode );
$current_User->set( 'locale', $newuser_locale );
// set allow_msgform: 
// 0 - none, 
// 1 - only private message, 
// 2 - only email, 
// 3 - private message and email
$newuser_allow_msgform = 0;
if( $allow_pm )
{ // PM is enabled
	$newuser_allow_msgform = 1;
}
if( $allow_email )
{ // email is enabled
	$newuser_allow_msgform = $newuser_allow_msgform + 2;
}
$current_User->set( 'allow_msgform', $newuser_allow_msgform );
$current_User->set( 'notify', $newuser_notify );
$current_User->set( 'ctry_ID', $newuser_ctry_ID );
$current_User->set( 'showonline', $newuser_showonline );
$current_User->set( 'gender', $newuser_gender );


// Set Messages into user's session, so they get restored on the next page (after redirect):
if( $current_User->dbupdate() )
{
	$Messages->add( T_('Your profile has been updated.'), 'success' );
}
else
{
	$Messages->add( T_('Your profile has not been changed.'), 'note' );
}


// redirect Will save $Messages into Session:
header_redirect();

/*
 * $Log: profile_update.php,v $
 */
?>