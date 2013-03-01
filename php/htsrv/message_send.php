<?php
/**
 * This file sends an email or a private message to the user! 
 * It's used to handle the contact form send message action. Even visitors are able to send emails.
 *
 * It's the form action for {@link _msgform.disp.php}.
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
 * @author Jeff Bearer - {@link http://www.jeffbearer.com/} + blueyed, fplanque
 *
 * @todo dh> we should use the current_User's ID, if he's logged in here. It seems that only the message form gets pre-filled with hidden fields currently.
 */

/**
 * Includes
 */
require_once dirname(__FILE__).'/../conf/_config.php';

require_once $inc_path.'_main.inc.php';

global $Session, $dummy_fields;

header( 'Content-Type: text/html; charset='.$io_charset );

// Check that this action request is not a CSRF hacked request:
$Session->assert_received_crumb( 'newmessage' );

// TODO: Flood protection (Use Hit class to prevent mass mailings to members..)

// --------------------------------------------------
// TODO: fp> v2.0: this bloats this file. MOVE to msg_remove.php or sth alike
if( param( 'optout_cmt_email', 'string', '' ) )
{ // an anonymous commentator wants to opt-out from receiving mails through a message form:

	if( param( 'req_ID', 'string', '' ) )
	{ // clicked on link from e-mail
		if( $req_ID == $Session->get( 'core.msgform.optout_cmt_reqID' )
		    && $optout_cmt_email == $Session->get( 'core.msgform.optout_cmt_email' ) )
		{
			$DB->query( '
				UPDATE T_comments
				   SET comment_allow_msgform = 0
				 WHERE comment_author_email = '.$DB->quote($optout_cmt_email) );

			$Messages->add( T_('All your comments have been marked not to allow emailing you through a message form.'), 'success' );

			$Session->delete('core.msgform.optout_cmt_email');
		}
		else
		{
			$Messages->add( T_('The request not to receive emails through a message form for your comments failed.'), 'error' );
		}

		$Messages->display();
		exit(0);
	}

	$req_ID = generate_random_key(32);

	$message = sprintf( T_("We have received a request that you do not want to receive emails through\na message form on your comments anymore.\n\nTo confirm that this request is from you, please click on the following link:") )
		."\n\n"
		.$htsrv_url.'message_send.php?optout_cmt_email='.$optout_cmt_email.'&req_ID='.$req_ID
		."\n\n"
		.T_('Please note:')
		.' '.T_('For security reasons the link is only valid for your current session (by means of your session cookie).')
		."\n\n"
		.T_('If it was not you that requested this, simply ignore this mail.');

	if( send_mail( $optout_cmt_email, NULL, T_('Confirm opt-out for emails through message form'), $message ) )
	{
		echo T_('An email has been sent to you, with a link to confirm your request not to receive emails through the comments you have made on this blog.');
		$Session->set( 'core.msgform.optout_cmt_email', $optout_cmt_email );
		$Session->set( 'core.msgform.optout_cmt_reqID', $req_ID );
	}
	else
	{
		$Messages->add( T_('Sorry, could not send email.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
	}

	exit(0);
}
// END OF BLOCK TO BE MOVED
// --------------------------------------------------


// Getting GET or POST parameters:
param( 'blog', 'integer', '' );
param( 'recipient_id', 'integer', '' );
param( 'post_id', 'integer', '' );
param( 'comment_id', 'integer', '' );
// Note: we use funky field names in order to defeat the most basic guestbook spam bots:
$sender_name = param( $dummy_fields[ 'name' ], 'string', '' );
$sender_address = param( $dummy_fields[ 'email' ], 'string', '' );
$subject = param( $dummy_fields[ 'subject' ], 'string', '' );
$message = param( $dummy_fields[ 'content' ], 'html', '' );	// We accept html but we will NEVER display it

// Prevent register_globals injection!
$recipient_address = '';
$recipient_name = '';
$recipient_User = NULL;
$Comment = NULL;

// Core param validation
if( empty($sender_name) )
{
	$Messages->add( T_('Please fill in your name.'), 'error' );
}

if( empty($subject) )
{
	$Messages->add( T_('Please fill in the subject of your message.'), 'error' );
}

if( empty( $message ) )
{ // message should not be empty!
	$Messages->add( T_('Please do not send empty messages.'), 'error' );
}
elseif( $antispam_on_message_form && antispam_check( $message ) )
{ // a blacklisted keyword ha sbeen found in the message:
	$Messages->add( T_('The supplied message is invalid / appears to be spam.'), 'error' );
}


// Build message footer:
$BlogCache = & get_BlogCache();
$message_footer = '';
if( !empty( $comment_id ) )
{
	// Getting current blog info:
	$Blog = & $BlogCache->get_by_ID( $blog );	// Required
	$message_footer .= T_('Message sent from your comment:') . "\n"
		.url_add_param( $Blog->get('url'), 'p='.$post_id.'#'.$comment_id, '&' )
		."\n\n";
}
elseif( !empty( $post_id ) )
{
	// Getting current blog info:
	$Blog = & $BlogCache->get_by_ID( $blog );	// Required
	$message_footer .= T_('Message sent from your post:') . "\n"
		.url_add_param( $Blog->get('url'), 'p='.$post_id, '&' )
		."\n\n";
}
else
{
	// Getting current blog info:
	$Blog = & $BlogCache->get_by_ID( $blog, true, false );	// Optional
}


if( ! empty( $recipient_id ) )
{ // Get the email address for the recipient if a member:
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	$allow_msgform = $recipient_User->get_msgform_possibility();
	if( ! $allow_msgform )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient!' );
	}

	$recipient_name = trim($recipient_User->get('preferredname'));
	$recipient_address = $recipient_User->get('email');

	// Change the locale so the email is in the recipients language
	locale_temp_switch($recipient_User->locale);
}
elseif( ! empty( $comment_id ) )
{ // Get the email address for the recipient if a visiting commenter.

	// Load comment from DB:
	$row = $DB->get_row(
		'SELECT *
		   FROM T_comments
		  WHERE comment_ID = '.$comment_id );
	$Comment = new Comment( $row );

	if( $recipient_User = & $Comment->get_author_User() )
	{ // Comment is from a registered user:
		$allow_msgform = $recipient_User->get_msgform_possibility();
		if( ! $allow_msgform )
		{ // should be prevented by UI
			debug_die( 'Invalid recipient!' );
		}
	}
	elseif( empty($Comment->allow_msgform) )
	{ // should be prevented by UI
		debug_die( 'Invalid recipient!' );
	}
	else
	{
		$allow_msgform = 'email';
	}

	$recipient_name = trim($Comment->get_author_name());
	$recipient_address = $Comment->get_author_email();

	// We don't know the recipient's language - Change the locale so the email is in the blog's language:
	locale_temp_switch($Blog->locale);
}

if( $allow_msgform == 'email' )
{
	if( empty($sender_address) )
	{
		$Messages->add( T_('Please fill in your email.'), 'error' );
	}
	elseif( !is_email($sender_address) || antispam_check( $sender_address ) ) // TODO: dh> using antispam_check() here might not allow valid users to contact the admin in case of problems due to the antispam list itself.. :/
	{
		$Messages->add( T_('Supplied email address is invalid.'), 'error' );
	}

	if( empty($recipient_address) )
	{ // should be prevented by UI
		debug_die( 'No recipient specified!' );
	}

	// opt-out links:
	if( $recipient_User )
	{ // Member:
		if( !empty( $Blog ) )
		{
			$message_footer .= T_("You can edit your profile to not receive emails through a form:")
				."\n".url_add_param( str_replace( '&amp;', '&', $Blog->get('url') ), 'disp=profile', '&' );
		}
		// TODO: else go to admin
	}
	elseif( $Comment )
	{ // Visitor:
		$message_footer .= T_("Click on the following link to not receive e-mails on your comments\nfor this e-mail address anymore:")
			."\n".$htsrv_url.'message_send.php?optout_cmt_email='.rawurlencode($Comment->author_email);
	}
	
	
	// Trigger event: a Plugin could add a $category="error" message here..
	$Plugins->trigger_event( 'MessageFormSent', array(
		'recipient_ID' => & $recipient_id,
		'item_ID' => $post_id,
		'comment_ID' => $comment_id,
		'subject' => & $subject,
		'message' => & $message,
		'message_footer' => & $message_footer,
		'Blog' => & $Blog,
		'sender_name' => & $sender_name,
		'sender_email' => & $sender_address,
		) );
	
	
	if( $Messages->has_errors() )
	{ // there were errors: display them and get out of here
		$Messages->display( T_('Cannot send email, please correct these errors:'),
		'[<a href="javascript:history.go(-1)">'. T_('Back to email editing') . '</a>]' );
		exit(0);
	}

	// show sender name
	$message_header = $sender_name." has sent you this message:\n\n";

	// show sender email address
	$message_footer = sprintf( T_( 'By replying to this message, your email will go directly to %s' ), $sender_address )."\n\n".$message_footer;

	if( !empty( $Blog ) )
	{
		$message = $message
			."\n\n-- \n"
			.sprintf( T_('This message was sent via the messaging system on %s.'), $Blog->name )."\n"
			.$Blog->get('url')."\n\n"
			.$message_footer;
	}
	else
	{
		$message = $message
			."\n\n-- \n"
			.sprintf( T_('This message was sent via the messaging system on %s.'), $baseurl )."\n\n"
			.$message_footer;
	}

	 // Send mail
	$success_message = send_mail( $recipient_address, $recipient_name, $subject, $message, $notify_from, NULL, array( 'Reply-To' => $sender_address ) );
}
elseif( ! $Messages->has_errors() )
{ // There were no errors, Send private message
	load_funcs( 'messaging/model/_messaging.funcs.php' );
	$success_message = send_private_message( $recipient_User->get( 'login' ), $subject, $message );
}
else
{
	$success_message = false;
}


// Plugins should cleanup their temporary data here:
$Plugins->trigger_event( 'MessageFormSentCleanup' );


// restore the locale to the blog visitor language
locale_restore_previous();

if( $success_message )
{
	// Never say to whom we sent the email -- prevent user enumeration.
	$Messages->add( T_('Your message has been sent.'), 'success' );
}
else
{
	if( $allow_msgform == 'email' )
	{
		if( $demo_mode )
		{
			$Messages->add( T_('Sorry, could not send email. Sending email in debug mode is disabled.' ), 'error' );
		}
		else
		{
			$Messages->add( T_('Sorry, could not send email.')
					.'<br />'.T_('Possible reason: the PHP mail() function may have been disabled on the server.'), 'error' );
		}
	}
	else
	{
		$Messages->add( T_('Sorry, could not send your message.'), 'error' );
	}
	header_redirect( url_add_param( $Blog->gen_blogurl(), 'disp=msgform&recipient_id='.$recipient_id ) );
	//exited here
}

// redirect Will save $Messages into Session:
header_redirect(); // exits!


/*
 * $Log: message_send.php,v $
 */
?>