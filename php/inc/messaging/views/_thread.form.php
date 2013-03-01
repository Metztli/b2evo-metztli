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
 * @version $Id: _thread.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Message
 */
global $edited_Message;
global $edited_Thread;

global $DB, $action;

$creating = is_create_action( $action );

$Form = new Form( NULL, 'thread_checkchanges', 'post', 'compact' );

$Form->global_icon( T_('Cancel editing!'), 'close', regenerate_url( 'action' ) );

$Form->begin_form( 'fform', T_('New thread') );

	$Form->add_crumb( 'thread' );
	$Form->hiddens_by_key( get_memorized( 'action'.( $creating ? ',msg_ID' : '' ) ) ); // (this allows to come back to the right list order & page)

$recent_recipients = $DB->get_var('SELECT GROUP_CONCAT(DISTINCT user_login SEPARATOR \', \')
									FROM (SELECT u.user_login
											FROM T_messaging__threadstatus t
													LEFT OUTER JOIN T_messaging__thread td ON t.tsta_thread_ID = td.thrd_ID
													LEFT OUTER JOIN T_messaging__threadstatus tu
																ON t.tsta_thread_ID = tu.tsta_thread_ID AND tu.tsta_user_ID <> '.$current_User->ID.'
													LEFT OUTER JOIN T_users u ON tu.tsta_user_ID = u.user_ID
											WHERE t.tsta_user_ID = '.$current_User->ID.' ORDER BY td.thrd_datemodified DESC LIMIT 20) AS users');

$user_login = param( 'user_login', 'string', '');

$Form->text_input( 'thrd_recipients', empty( $user_login ) ? $edited_Thread->recipients : $user_login, 70, T_('Recipients'), T_('Enter comma or space separated logins').'<br />'.get_avatar_imgtags( $recent_recipients ), array( 'maxlength'=> 255, 'required'=>true ) );

$Form->text_input( 'thrd_title', $edited_Thread->title, 70, T_('Subject'), '', array( 'maxlength'=> 255, 'required'=>true ) );

$Form->textarea_input( 'msg_text', $edited_Message->text, 10, T_('Message'), array( 'cols'=>80 ) );

$Form->radio( 'thrdtype', param( 'thrdtype', 'string', 'discussion' ), array(
								array( 'discussion', T_( 'Group discussion' ) ),
								array( 'individual', T_( 'Individual messages' ) )
							), T_('Multiple recipients'), true );

$Form->end_form( array( array( 'submit', 'actionArray[create]', T_('Record'), 'SaveButton' ),
												array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

/*
 * $Log: _thread.form.php,v $
 */
?>