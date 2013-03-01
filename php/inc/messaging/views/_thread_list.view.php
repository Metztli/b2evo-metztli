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
 * @version $Id: _thread_list.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $dispatcher;
global $current_User;
global $unread_messages_count;
global $read_unread_recipients;

// Select read/unread users for each thread

$recipients_SQL = new SQL();

$recipients_SQL->SELECT( 'ts.tsta_thread_ID AS thr_ID,
							GROUP_CONCAT(DISTINCT ur.user_login ORDER BY ur.user_login SEPARATOR \', \') AS thr_read,
    						GROUP_CONCAT(DISTINCT uu.user_login ORDER BY uu.user_login SEPARATOR \', \') AS thr_unread' );

$recipients_SQL->FROM( 'T_messaging__threadstatus ts
							LEFT OUTER JOIN T_messaging__threadstatus tsr
								ON ts.tsta_thread_ID = tsr.tsta_thread_ID AND tsr.tsta_first_unread_msg_ID IS NULL
							LEFT OUTER JOIN T_users ur
								ON tsr.tsta_user_ID = ur.user_ID AND ur.user_ID <> '.$current_User->ID.'
							LEFT OUTER JOIN T_messaging__threadstatus tsu
								ON ts.tsta_thread_ID = tsu.tsta_thread_ID AND tsu.tsta_first_unread_msg_ID IS NOT NULL
							LEFT OUTER JOIN T_users uu
								ON tsu.tsta_user_ID = uu.user_ID AND uu.user_ID <> '.$current_User->ID );

$recipients_SQL->WHERE( 'ts.tsta_user_ID ='.$current_User->ID );

$recipients_SQL->GROUP_BY( 'ts.tsta_thread_ID' );

foreach( $DB->get_results( $recipients_SQL->get() ) as $row )
{
	$read_by = '';

	if( !empty( $row->thr_read ) )
	{
		$read_by .= '<span style="color:green">';
		$read_by .= get_avatar_imgtags( $row->thr_read, true, false );
		if( !empty( $row->thr_unread ) )
		{
			$read_by .= ', ';
		}
		$read_by .= '</span>';
	}

	if( !empty( $row->thr_unread ) )
	{
		$read_by .= '<span style="color:red">'.get_avatar_imgtags( $row->thr_unread, true, false ).'</span>';
	}

	$read_unread_recipients[$row->thr_ID] = $read_by;
}

// Get params from request
$s = param( 's', 'string', '', true );

if( !empty( $s ) )
{	// We want to filter on search keyword:

	// Create SELECT query
	$select_SQL = 'SELECT * FROM
						(SELECT mt.thrd_ID, mt.thrd_title, mt.thrd_datemodified,
								mts.tsta_first_unread_msg_ID AS thrd_msg_ID, mm.msg_datetime AS thrd_unread_since,
								(SELECT GROUP_CONCAT(ru.user_login ORDER BY ru.user_login SEPARATOR \', \')
									FROM T_messaging__threadstatus AS rts
										LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
										WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients,
								(SELECT CONCAT_WS(" ", GROUP_CONCAT(ru.user_firstname), GROUP_CONCAT(ru.user_lastname), GROUP_CONCAT(ru.user_nickname))
									FROM T_messaging__threadstatus AS rts
										LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
										WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_usernames
						FROM T_messaging__threadstatus mts
								LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
								LEFT OUTER JOIN T_messaging__message mm ON mts.tsta_first_unread_msg_ID = mm.msg_ID
								WHERE mts.tsta_user_ID = '.$current_User->ID.'
								ORDER BY mts.tsta_first_unread_msg_ID DESC, mt.thrd_datemodified DESC) AS threads
					WHERE CONCAT_WS( " ", threads.thrd_title, threads.thrd_recipients, threads.thrd_usernames) LIKE "%'.$DB->escape($s).'%"';

	// Create COUNT query
	$count_SQL = 'SELECT COUNT(*) FROM
					(SELECT mt.thrd_title,
						(SELECT GROUP_CONCAT(ru.user_login SEPARATOR \', \')
		      			FROM T_messaging__threadstatus AS rts
		          			LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
		              		WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients,
		              	(SELECT CONCAT_WS(" ", GROUP_CONCAT(ru.user_firstname), GROUP_CONCAT(ru.user_lastname), GROUP_CONCAT(ru.user_nickname))
						FROM T_messaging__threadstatus AS rts
							LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
							WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_usernames
		  			FROM T_messaging__threadstatus mts
		  				LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
		          		WHERE mts.tsta_user_ID = '.$current_User->ID.') AS r
		          WHERE CONCAT_WS( " ", r.thrd_title, r.thrd_recipients, r.thrd_usernames) LIKE "%'.$DB->escape($s).'%"';
}
else
{
	// Create SELECT query
	$select_SQL = 'SELECT * FROM
					(SELECT mt.thrd_ID, mt.thrd_title, mt.thrd_datemodified,
							mts.tsta_first_unread_msg_ID AS thrd_msg_ID, mm.msg_datetime AS thrd_unread_since,
						(SELECT GROUP_CONCAT(ru.user_login ORDER BY ru.user_login SEPARATOR \', \')
						FROM T_messaging__threadstatus AS rts
							LEFT OUTER JOIN T_users AS ru ON rts.tsta_user_ID = ru.user_ID AND ru.user_ID <> '.$current_User->ID.'
							WHERE rts.tsta_thread_ID = mt.thrd_ID) AS thrd_recipients
					FROM T_messaging__threadstatus mts
						LEFT OUTER JOIN T_messaging__thread mt ON mts.tsta_thread_ID = mt.thrd_ID
						LEFT OUTER JOIN T_messaging__message mm ON mts.tsta_first_unread_msg_ID = mm.msg_ID
						WHERE mts.tsta_user_ID = '.$current_User->ID.'
						ORDER BY mts.tsta_first_unread_msg_ID DESC, mt.thrd_datemodified DESC) AS threads';

	// Create COUNT quiery
	$count_SQL = 'SELECT COUNT(*)
					FROM T_messaging__threadstatus
						WHERE tsta_user_ID = '.$current_User->ID;
}

// Create result set:

$Results = new Results( $select_SQL, 'thrd_', '', NULL, $count_SQL );

$Results->Cache = & get_ThreadCache();

$Results->title = T_('Conversations list');

if( $unread_messages_count > 0 )
{
	$Results->title = $Results->title.' <span class="badge">'.$unread_messages_count.'</span></b>';
}

/**
 * Callback to add filters on top of the result set
 *
 * @param Form
 */
function filter_recipients( & $Form )
{
	$Form->text( 's', get_param('s'), 30, T_('Search'), '', 255 );
}

$Results->filter_area = array(
	'callback' => 'filter_recipients',
	'presets' => array(
		'all' => array( T_('All'), '?ctrl=threads' ),
		)
	);

$Results->cols[] = array(
					'th' => T_('With'),
					'th_class' => 'thread_with',
					'td_class' => 'thread_with',
					'td' => '%get_avatar_imgtags( #thrd_recipients# )%',
					);

$Results->cols[] = array(
					'th' => T_('Subject'),
					'th_class' => 'thread_subject',
					'td_class' => 'thread_subject',
					'td' => '¤conditional( #thrd_msg_ID#>0, \'<strong><a href="'.$dispatcher
							.'?ctrl=messages&amp;thrd_ID=$thrd_ID$" title="'.
							T_('Show messages...').'">$thrd_title$</a></strong>\', \'<a href="'
							.$dispatcher.'?ctrl=messages&amp;thrd_ID=$thrd_ID$" title="'.T_('Show messages...').'">$thrd_title$</a>\' )¤',
					);

$Results->cols[] = array(
					'th' => T_('Last message'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'shrinkwrap',
					'td' => '¤conditional( #thrd_msg_ID#>0, \'<span style="color:red">%mysql2localedatetime(#thrd_unread_since#)%</span>\', \'<span style="color:green">%mysql2localedatetime(#thrd_datemodified#)%</span>\')¤' );

function get_read_by( $thread_ID )
{
	global $read_unread_recipients;

	return $read_unread_recipients[$thread_ID];
}

$Results->cols[] = array(
					'th' => T_('Read by'),
					'th_class' => 'shrinkwrap',
					'td_class' => 'top',
					'td' => '%get_read_by( #thrd_ID# )%',
					);


if( $current_User->check_perm( 'perm_messaging', 'delete' ) )
{	// We have permission to modify:
	$Results->cols[] = array(
							'th' => T_('Actions'),
							'th_class' => 'shrinkwrap',
							'td_class' => 'shrinkwrap',
							'td' => '@action_icon("delete")@',
						);
}

$Results->global_icon( T_('Create a new conversation...'), 'new', regenerate_url( 'action', 'action=new'), T_('Compose new').' &raquo;', 3, 4  );

$Results->display();

/*
 * $Log: _thread_list.view.php,v $
 */
?>