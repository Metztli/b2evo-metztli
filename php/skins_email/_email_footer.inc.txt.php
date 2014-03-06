<?php
/**
 * This is included into every email to provide footer text, including a quick unsubscribe link.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: _email_footer.inc.txt.php 5927 2014-02-07 13:42:40Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $admin_url, $baseurl, $app_name, $Settings;

// Default params:
$params = array_merge( array(
		'unsubscribe_text' => '',
	), $params );

$edit_notification_url = $admin_url.'?ctrl=user&user_tab=subs';

echo "\n\n-- \n";

echo T_( 'Please do not reply to this email!' )."\n";
echo sprintf( T_( 'This message was automatically generated by %s running on %s: %s.' ), $app_name, $Settings->get( 'notification_short_name' ), $baseurl )."\n";
echo sprintf( T_( 'Your login on %s is: $login$' ), $Settings->get( 'notification_short_name' ) );
echo "\n\n";

echo T_( 'Too many emails?' )."\n";
echo sprintf( T_('To edit your email notification preferences, click here: %s'), $edit_notification_url );
if( !empty( $params['unsubscribe_text'] ) )
{ // Display the unsubscribe message with link
	echo "\n".$params['unsubscribe_text'];
}
echo "\n\n".'Powered by b2evolution'."\n";
?>