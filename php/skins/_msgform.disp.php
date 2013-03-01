<?php
/**
 * This is the template that displays the message user form
 *
 * This file is not meant to be called directly.
 * It is meant to be called by an include in the main.page.php template.
 * To display a feedback, you should call a stub AND pass the right parameters
 * For example: /blogs/index.php?disp=msgform&recipient_id=n
 * Note: don't code this URL by hand, use the template functions to generate it!
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package evoskins
 *
 * @todo dh> A user/blog might want to accept only mails from logged in users (fp>yes!)
 * @todo dh> For logged in users the From name and address should be not editable/displayed
 *           (the same as when commenting). (fp>yes!!!)
 * @todo dh> Display recipient's avatar?! fp> of course! :p
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $cookie_name, $cookie_email;

global $DB;

// Parameters
/* TODO: dh> params should get remembered, e.g. if somebody clicks on the
 *       login/logout link from the msgform page.
 *       BUT, for the logout link remembering it here is too late normally.. :/
 */
$redirect_to = param( 'redirect_to', 'string', '' ); // pass-through (hidden field)
$recipient_id = param( 'recipient_id', 'integer', 0 );
$post_id = param( 'post_id', 'integer', 0 );
$comment_id = param( 'comment_id', 'integer', 0 );
$subject = param( 'subject', 'string', '' );


// User's preferred name or the stored value in her cookie (from commenting):
$email_author = '';
if( is_logged_in() )
{
	$email_author = $current_User->get_preferred_name();
}
if( ! strlen($email_author) && isset($_COOKIE[$cookie_name]) )
{
	$email_author = trim($_COOKIE[$cookie_name]);
}

// User's email address or the stored value in her cookie (from commenting):
$email_author_address = '';
if( is_logged_in() )
{
	$email_author_address = $current_User->email;
}
if( ! strlen($email_author_address) && isset($_COOKIE[$cookie_email]) )
{
	$email_author_address = trim($_COOKIE[$cookie_email]);
}

$recipient_User = NULL;
$Comment = NULL;


// Get the name of the recipient and check if he wants to receive mails through the message form


if( ! empty($recipient_id) )
{ // If the email is to a registered user get the email address from the users table
	$UserCache = & get_UserCache();
	$recipient_User = & $UserCache->get_by_ID( $recipient_id );

	if( $recipient_User )
	{
		// get_msgform_possibility returns NULL (false), only if there is no messaging option between current_User and recipient user
		$allow_msgform = $recipient_User->get_msgform_possibility();
		if( ! $allow_msgform )
		{ // should be prevented by UI
			if( is_logged_in() && $recipient_User->accepts_pm() )
			{ // current User is loggeg in, and recipient User accepts private messages. 
		    	global $current_User;
		    	if( $current_User->accepts_pm() )
			    { // if recipient user accepts private messages and current user accpets as well, then allow_msgform can be false, only if this two users are the same
			    	echo '<p class="error">'.T_('You cannot send a private message to yourself.').'</p>';
			    }
			    else
			    {
			    	echo '<p class="error">'.T_('This user can only be contacted through private messages but you are not allowed to send any private messages.').'</p>';
			    }
			}
			else
			{ // recipient User doesn't accepts private messages, and doesn't accept email
				echo '<p class="error">'.T_('This user does not wish to be contacted directly.').'</p>'; 
			}
			return;
		}
		$recipient_name = $recipient_User->get('preferredname');
		$recipient_address = $recipient_User->get('email');
	}
}
elseif( ! empty($comment_id) )
{ // If the email is through a comment, get the email address from the comments table (or the linked member therein):

	// Load comment from DB:
	$row = $DB->get_row( '
		SELECT *
		  FROM T_comments
		 WHERE comment_ID = '.$comment_id );

	if( $row )
	{
		$Comment = new Comment( $row );
		if( $recipient_User = & $Comment->get_author_User() )
		{ // Source comment is from a registered user:
			$allow_msgform = $recipient_User->get_msgform_possibility();
			if( ! $allow_msgform )
			{
				echo '<p class="error">The user does not want to get contacted through the message form.</p>'; // should be prevented by UI
				return;
			}
		}
		elseif( ! $Comment->allow_msgform )
		{ // Source comment is from an anonymou suser:
			echo '<p class="error">This commentator does not want to get contacted through the message form.</p>'; // should be prevented by UI
			return;
		}
		else
		{
			$allow_msgform = 'email';
		}

		$recipient_name = $Comment->get_author_name();
		$recipient_address = $Comment->get_author_email();
	}
}

if( !isset($recipient_User) && empty($recipient_address) )
{	// We should never have called this in the first place!
	// Could be that commenter did not provide an email, etc...
	echo 'No recipient specified!';
	return;
}

if( $allow_msgform == 'login' )
{ // try to login to send private message (there is no other option)
	echo '<p class="error">'.T_( 'You must log in before you can contact this user' ).'</p>';
	param( 'action', 'string', 'req_login' );
	require '_login.disp.php';
}
else
{
	// Get the suggested subject for the email:
	if( empty($subject) )
	{ // no subject provided by param:
		if( ! empty($comment_id) )
		{
			$row = $DB->get_row( '
				SELECT post_title
				  FROM T_items__item, T_comments
				 WHERE comment_ID = '.$DB->quote($comment_id).'
				   AND post_ID = comment_post_ID' );
	
			if( $row )
			{
				$subject = T_('Re:').' '.sprintf( /* TRANS: Used as mail subject; %s gets replaced by an item's title */ T_( 'Comment on %s' ), $row->post_title );
			}
		}
	
		if( empty($subject) && ! empty($post_id) )
		{
			$row = $DB->get_row( '
					SELECT post_title
					  FROM T_items__item
					 WHERE post_ID = '.$post_id );
			if( $row )
			{
				$subject = T_('Re:').' '.$row->post_title;
			}
		}
	}
	?>

	<!-- form to send email -->
	<?php

	// Form to send email
	if( !empty( $Blog ) && ( $Blog->get_setting( 'ajax_form_enabled' ) ) )
	{
		if( empty( $subject ) )
		{
			$subject = '';
		}
		// init params
		$json_params = array( 
			'action' => 'get_msg_form',
			'subject' => $subject,
			'recipient_id' => $recipient_id,
			'recipient_name' => $recipient_name,
			'email_author' => $email_author,
			'email_author_address' => $email_author_address,
			'allow_msgform' => $allow_msgform,
			'blog' => $Blog->ID,
			'redirect_to' => $redirect_to,
			'params' => $params );

		// generate form wtih ajax request
		display_ajax_form( $json_params );
	}
	else
	{
		require '_contact_msg.form.php';
	}
}

/*
 * $Log: _msgform.disp.php,v $
 */
?>