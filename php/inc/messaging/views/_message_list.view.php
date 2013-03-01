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
 * @version $Id: _message_list.view.php 1744 2012-08-29 15:56:23Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher, $action, $current_User, $edited_Thread;

global $read_by_list;

$creating = is_create_action( $action );

// Update message statuses

$DB->query( 'UPDATE T_messaging__threadstatus
				SET tsta_first_unread_msg_ID = NULL
				WHERE tsta_thread_ID = '.$edited_Thread->ID.'
				AND tsta_user_ID = '.$current_User->ID );

// Select all recipients

$recipients_SQL = new SQL();

$recipients_SQL->SELECT( 'GROUP_CONCAT(u.user_login ORDER BY u.user_login SEPARATOR \',\')' );

$recipients_SQL->FROM( 'T_messaging__threadstatus mts
								LEFT OUTER JOIN T_users u ON mts.tsta_user_ID = u.user_ID' );

$recipients_SQL->WHERE( 'mts.tsta_thread_ID = '.$edited_Thread->ID.'
								AND mts.tsta_user_ID <> '.$current_User->ID );

$recipients = explode( ',', $DB->get_var( $recipients_SQL->get() ) );

// Select unread recipients

$unread_recipients_SQL = new SQL();

$unread_recipients_SQL->SELECT( 'mm.msg_ID, GROUP_CONCAT(uu.user_login ORDER BY uu.user_login SEPARATOR \',\') AS msg_unread' );

$unread_recipients_SQL->FROM( 'T_messaging__message mm
										LEFT OUTER JOIN T_messaging__threadstatus tsu ON mm.msg_ID = tsu.tsta_first_unread_msg_ID
										LEFT OUTER JOIN T_users uu ON tsu.tsta_user_ID = uu.user_ID' );

$unread_recipients_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );

$unread_recipients_SQL->GROUP_BY( 'mm.msg_ID' );

$unread_recipients_SQL->ORDER_BY( 'mm.msg_datetime' );

$unread_recipients = array();

// Create array for read by

foreach( $DB->get_results( $unread_recipients_SQL->get() ) as $row )
{
	if( !empty( $row->msg_unread ) )
	{
		$unread_recipients = array_merge( $unread_recipients, explode( ',', $row->msg_unread ) );
	}

	$read_recipiens = array_diff( $recipients, $unread_recipients );
	$read_recipiens[] = $current_User->login;

	asort( $read_recipiens );
	asort( $unread_recipients );

	$read_by = '';
	if( !empty( $read_recipiens ) )
	{
		$read_by .= '<span style="color:green">'.get_avatar_imgtags( $read_recipiens, true, false );
		if( !empty ( $unread_recipients ) )
		{
			$read_by .= ', ';
		}
		$read_by .= '</span>';
	}

	if( !empty ( $unread_recipients ) )
	{
		$read_by .= '<span style="color:red">'.get_avatar_imgtags( $unread_recipients, true, false ).'</span>';
	}

	$read_by_list[$row->msg_ID] = $read_by ;
}


// Create SELECT query:

$select_SQL = new SQL();

$select_SQL->SELECT( 	'mm.msg_ID, mm.msg_author_user_ID, mm.msg_thread_ID, mm.msg_datetime,
						u.user_ID AS msg_user_ID, u.user_login AS msg_author,
						u.user_firstname AS msg_firstname, u.user_lastname AS msg_lastname,
						u.user_avatar_file_ID AS msg_user_avatar_ID, mm.msg_text' );

$select_SQL->FROM( 'T_messaging__message mm
						LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID' );

$select_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );

$select_SQL->ORDER_BY( 'mm.msg_datetime' );

// Create COUNT query

$count_SQL = new SQL();
$count_SQL->SELECT( 'COUNT(*)' );

// Get params from request
$s = param( 's', 'string', '', true );

if( !empty( $s ) )
{
	$select_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname, msg_text ) LIKE "%'.$DB->escape($s).'%"' );

	$count_SQL->FROM( 'T_messaging__message mm LEFT OUTER JOIN T_users u ON u.user_ID = mm.msg_author_user_ID' );
	$count_SQL->WHERE( 'mm.msg_thread_ID = '.$edited_Thread->ID );
	$count_SQL->WHERE_and( 'CONCAT_WS( " ", u.user_login, u.user_firstname, u.user_lastname, u.user_nickname, msg_text ) LIKE "%'.$DB->escape($s).'%"' );
}
else
{
	$count_SQL->FROM( 'T_messaging__message' );
	$count_SQL->WHERE( 'msg_thread_ID = '.$edited_Thread->ID );
}

// Create result set:

$Results = new Results( $select_SQL->get(), 'msg_', '', 0, $count_SQL->get() );

$Results->Cache = & get_MessageCache();

$Results->title = $edited_Thread->title;

$Results->global_icon( T_('Cancel!'), 'close', '?ctrl=threads' );

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_messages( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_messages',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=messages&thrd_ID='.$edited_Thread->ID ),
		)
	);

/*
 * Author col:
 */

/**
 * Get user avatar
 *
 * @param integer user ID
 * @param integer avatar ID
 * @return string
 */
function user_avatar( $user_ID, $user_avatar_file_ID )
{
	global $current_User;

	if( ! $GLOBALS['Settings']->get('allow_avatars') ) 
		return '';

	$FileCache = & get_FileCache();

	if( ! $File = & $FileCache->get_by_ID( $user_avatar_file_ID, false, false ) )
	{
		return '';
	}

	if( $current_User->check_perm( 'users', 'view' ) )
	{
		return '<a href="?ctrl=user&amp;user_tab=profile&amp;user_ID='.$user_ID.'">'.$File->get_thumb_imgtag( 'crop-80x80' ).'</a>';
	}
	else
	{
		return $File->get_thumb_imgtag( 'crop-80x80' );
	}
}
/**
 * Create author cell for message list table
 *
 * @param integer user ID
 * @param string login
 * @param string first name
 * @param string last name
 * @param integer avatar ID
 * @param string datetime
 */
function author( $user_ID, $user_login, $user_first_name, $user_last_name, $user_avatar_ID, $datetime)
{
	$author = '<b>'.$user_login.'</b>';

	$avatar = user_avatar( $user_ID, $user_avatar_ID );

	if( !empty( $avatar ) )
	{
		$author = $avatar.'<br />'.$author;
	}

	$full_name = '';

	if( !empty( $user_first_name ) )
	{
		$full_name .= $user_first_name;
	}

	if( !empty( $user_last_name ) )
	{
		$full_name .= ' '.$user_last_name;
	}

	if( !empty( $full_name ) )
	{
		$author .= '<br />'.$full_name;
	}

	return $author.'<br /><span class="note">'.mysql2localedatetime( $datetime ).'</span>';
}
$Results->cols[] = array(
		'th' => T_('Author'),
		'th_class' => 'shrinkwrap',
		'td_class' => 'left top',
		'td' => '%author( #msg_user_ID#, #msg_author#, #msg_firstname#, #msg_lastname#, #msg_user_avatar_ID#, #msg_datetime#)%'
	);

function format_msg_text( $msg_text, $thread_title )
{
	global $evo_charset;

	if( empty( $msg_text ) )
	{
		return format_to_output( $thread_title, 'htmlspecialchars' );
	}

	// WARNING: the messages may contain MALICIOUS HTML and javascript snippets. They must ALWAYS be ESCAPED prior to display!
	$msg_text = htmlentities( $msg_text, ENT_COMPAT, $evo_charset );

	$msg_text = make_clickable( $msg_text );
	$msg_text = preg_replace( '#<a #i', '<a rel="nofollow" target="_blank"', $msg_text );
	$msg_text = nl2br( $msg_text );

	return $msg_text;
}
/*
 * Message col
 */
$Results->cols[] = array(
		'th' => T_('Message'),
		'td_class' => 'left top',
		'td' => '%format_msg_text(#msg_text#, "'.$edited_Thread->title.'")%',
	);

function get_read_by( $message_ID )
{
	global $read_by_list;

	return $read_by_list[$message_ID];
}

$Results->cols[] = array(
					'th' => T_('Read by'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'top',
					'td' => '%get_read_by( #msg_ID# )%',
					);

if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
{
	// We have permission to modify:

	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							// Do not display the icon if the message cannot be deleted
							'td' => $Results->total_rows == 1 ? '' : '@action_icon("delete")@',
						);
}

$Results->display();

$Form = new Form( NULL, 'messages_checkchanges', 'post', 'compact' );

$Form->begin_form( 'fform', '' );

	$Form->add_crumb( 'message' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

	$Form->info_field(T_('Reply to'), get_avatar_imgtags( $recipients ), array('required'=>true));

	$Form->textarea('msg_text', '', 10, '', '', 80, '', true);

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
/*
 * $Log: _message_list.view.php,v $
 */
?>