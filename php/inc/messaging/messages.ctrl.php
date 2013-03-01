<?php
/**
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package messaging
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: messages.ctrl.php 9 2011-10-24 22:32:00Z fplanque $
 */

if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

// Load classes
load_class( 'messaging/model/_thread.class.php', 'Thread' );
load_class( 'messaging/model/_message.class.php', 'Message' );


/**
 * @var User
 */
global $current_User;

// Set options path:
$AdminUI->set_path( 'messaging', 'messages' );

// Get action parameter from request:
param_action();

if( param( 'thrd_ID', 'integer', '', true) )
{// Load thread from cache:
	$ThreadCache = & get_ThreadCache();
	if( ($edited_Thread = & $ThreadCache->get_by_ID( $thrd_ID, false )) === false )
	{	unset( $edited_Thread );
		forget_param( 'thrd_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Thread') ), 'error' );
		$action = 'nil';
	}
}

// Check minimum permission:
$current_User->check_perm( 'perm_messaging', 'write', true, $thrd_ID );

if( param( 'msg_ID', 'integer', '', true) )
{// Load message from cache:
	$MessageCache = & get_MessageCache();
	if( ($edited_Message = & $MessageCache->get_by_ID( $msg_ID, false )) === false )
	{	unset( $edited_Message );
		forget_param( 'msg_ID' );
		$Messages->add( sprintf( T_('Requested &laquo;%s&raquo; object does not exist any longer.'), T_('Message') ), 'error' );
		$action = 'nil';
	}
}

// Preload users to show theirs avatars

load_messaging_thread_recipients( $thrd_ID );

switch( $action )
{
	case 'create': // Record new message
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'message' );
		
		// Insert new message:
		$edited_Message = new Message();
		$edited_Message->thread_ID = $thrd_ID;

		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'write', true );

		// Load data from request
		if( $edited_Message->load_from_Request() )
		{	// We could load data from form without errors:

			if( $current_User->check_perm( 'perm_messaging', 'reply' ) )
			{
				$non_blocked_contacts = $edited_Thread->load_contacts();
				if( empty( $non_blocked_contacts ) )
				{
					param_error( '', T_( 'You don\'t have permission to reply here.' ) );
				}
			}

			if( ! param_errors_detected() )
			{
				// Insert in DB:
				$edited_Message->dbinsert_message();
				$Messages->add( T_('New message created.'), 'success' );

				// Redirect so that a reload doesn't write to the DB twice:
				header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID, 303 ); // Will EXIT
				// We have EXITed already at this point!!
			}
		}
		break;

	case 'delete':
		// Delete message:
		
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'message' );

		// Check permission:
		$current_User->check_perm( 'perm_messaging', 'delete', true );

		// Make sure we got an msg_ID:
		param( 'msg_ID', 'integer', true );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed, Delete from DB:
			$edited_Message->dbdelete();
			unset( $edited_Message );
			forget_param( 'msg_ID' );
			$Messages->add( T_('Message deleted.'), 'success' );

			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=messages&thrd_ID='.$thrd_ID, 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		else
		{	// not confirmed, Check for restrictions:
			if( ! $edited_Message->check_delete( T_('Cannot delete message.') ) )
			{	// There are restrictions:
				$action = 'view';
			}
		}
		break;

}


$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('Messages'), '?ctrl=threads' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;

	case 'delete':
		// We need to ask for confirmation:
		$edited_Message->confirm_delete( T_('Delete message?'),
				'message', $action, get_memorized( 'action' ) );
	default:
		// No specific request, list all messages:
		// Cleanup context:
		forget_param( 'msg_ID' );
		// Display messages list:
		$action = 'create';
		$AdminUI->disp_view( 'messaging/views/_message_list.view.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: messages.ctrl.php,v $
 */
?>