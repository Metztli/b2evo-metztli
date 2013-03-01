<?php
/**
 * This file updates the current user's item subscriptions!
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
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
 * }}
 *
 * @package htsrv
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-asimo: Attila Simo
 *
 * @version $Id: isubs_update.php 1010 2012-03-08 08:39:41Z attila $
 */

/**
 * Initialize everything:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $DB, $Session, $Messages;

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'itemsubs' );

// Get params
$item_ID = param( 'p', 'integer', true );
param( 'notify', 'integer', 0 );
param( 'type', 'string', '' );

/**
 * Basic security checks:
 */
if( ! is_logged_in() )
{ // must be logged in!
	bad_request_die( T_('You are not logged in.') );
}

if( $demo_mode && ( $current_User->ID <= 3 ) )
{ // don't allow subscribe in demo mode
	bad_request_die( sprintf( 'Demo mode: You can\'t edit %s profile!', $current_User->login ).'<br />[<a href="javascript:history.go(-1)">'
				. T_('Back to blog') . '</a>]' );
}

// Set item subscription type
$isub_type = 'isub_comments';

if( ! is_email( $current_User->get( 'email' ) ) )
{ // user doesn't have a valid email address
	$Messages->add( T_( 'Your email address is invalid. Please set your email address first.' ), 'error' );
}

if( ( $notify < 0 ) || ( $notify > 1 ) )
{ // Invalid notify param. It should be 0 for unsubscribe and 1 for subscribe.
	$Messages->add( 'Invalid params!', 'error' );
}

if( $Messages->has_errors() )
{ // errors detected
	header_redirect();
}

if( set_user_isubscription( $current_User->ID, $item_ID, $notify, $isub_type ) )
{ // user subscription was set
	if( $notify == 0 )
	{
		$Messages->add( T_( 'You have successfully unsubscribed.' ), 'success' );
	}
	else
	{
		$Messages->add( T_( 'You have successfully subscribed to notifications.' ), 'success' );
	}
}
else
{ // couldn't update the database
	$Messages->add( T_( 'Could not subscribe to notifications.' ), 'error' );
}

header_redirect();

/*
 * $Log: isubs_update.php,v $
 */
?>