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
 * @version $Id: _thread.class.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Thread Class
 *
 */
class Thread extends DataObject
{
	var $title = '';
	var $datemodified;
	var $recipients = '';

	/**
	 * Number unread messages
	 * @var integer
	 */
	var $num_unread_messages;

	/**
	 * Recipients IDs lazy filled
	 *
	 * @var array
	 */
	var $recipients_list;


	/**
	 * Unblocked contacts IDs lazy filled
	 *
	 * @var array
	 */
	var $contacts_list;


	/**
	 * Constructor
	 * @param db_row database row
	 */
	function Thread( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_messaging__thread', 'thrd_', 'thrd_ID', 'datemodified' );

		$this->delete_restrictions = array();
  		$this->delete_cascades = array();

 		if( $db_row != NULL )
		{
			$this->ID           = $db_row->thrd_ID;
			$this->title        = $db_row->thrd_title;
			$this->datemodified = $db_row->thrd_datemodified;
		}
	}


	/**
	 * Load data from Request form fields.
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request()
	{
		global $thrd_recipients;

		// Title
		param( 'thrd_title', 'string' );
		param_check_not_empty( 'thrd_title', T_('Please enter a subject') );
		$this->set_from_Request( 'title', 'thrd_title' );

		// Resipients
		$this->set_string_from_param( 'recipients', true );

		$this->param_check__recipients( 'thrd_recipients', $thrd_recipients );

		return ! param_errors_detected();
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @param string parameter name
	 * @param mixed parameter value
	 * @param boolean true to set to NULL if empty value
	 * @return boolean true, if a value has been set; false if it has not changed
	 */
	function set( $parname, $parvalue, $make_null = false )
	{
		switch( $parname )
		{
			case 'recipients':
				$this->recipients = $parvalue;
				break;
			case 'title':
			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Check if recipients available in database
	 *
	 * @param string recipients
	 */
	function param_check__recipients ( $var, $recipients )
	{
		global $DB, $current_User;

		// split recipients into array using comma separator
		$recipients_list = array();
		$recipients = trim( str_replace( ',', ' ', $recipients ) );
		foreach( explode(' ', $recipients) as $recipient )
		{
			$login = trim($recipient);
			if( ! empty( $login ) )
			{
				$recipients_list[] = evo_strtolower( $login );
			}
		}

		$recipients_list = array_unique( $recipients_list );

		$error_msg = '';

		// check has recipients list login of current user
		if( in_array( $current_User->login, $recipients_list ) )
		{
			$error_msg = sprintf( T_( 'You cannot send threads to yourself: %s' ), $current_User->login );
		}

		// select all users from database
		$db_users_list = array();
		foreach( $DB->get_results( 'SELECT user_ID, user_login
									FROM T_users') as $row )
		{
			$db_users_list[$row->user_login] = $row->user_ID;
		}

		// check are recipients available in database
		$this->recipients_list = array();
		$unavailable_recipients_list = array();
		foreach( $recipients_list as $recipient )
		{
			if ( array_key_exists( $recipient, $db_users_list ) )
			{
				$this->recipients_list[] = $db_users_list[$recipient];
			}
			else
			{
				$unavailable_recipients_list[] = $recipient;
			}
		}

		if ( count( $unavailable_recipients_list ) > 0 )
		{
			if ( ! empty( $error_msg ) )
			{
				$error_msg .= '<br />';
			}

			$error_msg .= sprintf( 'The following users were not found: %s', implode( ', ', $unavailable_recipients_list ) );
		}

		if( ! empty( $error_msg ) )
		{	// show error

			param_error( $var, $error_msg );
			return false;
		}

		return true;
	}


	/**
	 * Delete thread and dependencies from database
	 */
	function dbdelete()
	{
		global $DB;

		if( $this->ID == 0 ) debug_die( 'Non persistant object cannot be deleted!' );

		$DB->begin();

		// Delete Messages
		$ret = $DB->query( 'DELETE FROM T_messaging__message
												WHERE msg_thread_ID='.$this->ID );
		// Delete Statuses
		$ret = $DB->query( 'DELETE FROM T_messaging__threadstatus
												WHERE tsta_thread_ID='.$this->ID );
		// Delete Thread
		if( ! parent::dbdelete() )
		{
			$DB->rollback();

			return false;
		}

		$DB->commit();

		return true;
	}


	/**
	 * Load recipients of the current thread
	 *
	 * @return recipients list
	 */
	function load_recipients()
	{
		global $DB;

		if( empty( $this->recipients_list ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'tsta_user_ID' );
			$SQL->FROM( 'T_messaging__threadstatus' );
			$SQL->WHERE( 'tsta_thread_ID = '.$this->ID );

			$this->recipients_list = array();
			foreach( $DB->get_results( $SQL->get() ) as $row )
			{
				$this->recipients_list[] = $row->tsta_user_ID;
			}
		}

		return $this->recipients_list;
	}


	/**
	 * Load all of the non blocked contacts of current thread

	 * @return contacts
	 */
	function load_contacts()
	{
		global $DB, $current_User;

		if( empty( $this->contacts_list ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'u.user_ID' );
			$SQL->FROM( 'T_messaging__threadstatus ts
							INNER JOIN T_messaging__contact mc
								ON ts.tsta_user_ID = mc.mct_from_user_ID
								AND mc.mct_to_user_ID = '.$current_User->ID.'
								AND mc.mct_blocked = 0
							LEFT OUTER JOIN T_users u
								ON ts.tsta_user_ID = u.user_ID' );
			$SQL->WHERE( 'ts.tsta_user_ID <> '.$current_User->ID );
			$SQL->WHERE_and( 'ts.tsta_thread_ID ='.$this->ID );

			foreach( $DB->get_results( $SQL->get() ) as $row )
			{
				$this->contacts_list[] = $row->user_ID;
			}
		}

		return $this->contacts_list;
	}


	/**
	 * Check permission on a persona
	 *
	 * @return boolean true if granted
	 */
	function check_perm( $action, $assert = true )
	{
		global $current_User;

		return $current_User->check_perm( 'perm_messaging', $action, $assert );
	}


	/**
	 * Check if user is recipient of the current thread
	 *
	 * @param user ID
	 * @return true is user is recipient, instead false
	 */
	function check_thread_recipient( $user_ID )
	{
		$this->load_recipients();
		return in_array( $user_ID, $this->recipients_list );
	}
}

/*
 * $Log: _thread.class.php,v $
 */
?>