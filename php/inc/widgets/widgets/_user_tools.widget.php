<?php
/**
 * This file implements the xyz Widget class.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _user_tools.widget.php 57 2011-10-26 08:18:58Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'widgets/model/_widget.class.php', 'ComponentWidget' );

/**
 * ComponentWidget Class
 *
 * A ComponentWidget is a displayable entity that can be placed into a Container on a web page.
 *
 * @package evocore
 */
class user_tools_Widget extends ComponentWidget
{
	/**
	 * Constructor
	 */
	function user_tools_Widget( $db_row = NULL )
	{
		// Call parent constructor:
		parent::ComponentWidget( $db_row, 'core', 'user_tools' );
	}


  /**
   * Get definitions for editable params
   *
	 * @see Plugin::GetDefaultSettings()
	 * @param local params like 'for_editing' => true
	 */
	function get_param_definitions( $params )
	{
		$r = array_merge( array(
			'title' => array(
				'label' => T_('Block title'),
				'note' => T_( 'Title to display in your skin.' ),
				'size' => 40,
				'defaultvalue' => T_('User tools'),
			),
			
			'user_login_link_show' => array(
				'label' => T_( 'Login link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_login_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => /* TRANS: with tailing space = action to log in */ T_( 'Login ' ),
			),
			
			'user_logout_link_show' => array(
				'label' => T_( 'Logout link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_logout_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Logout' ),
			),
			
			'user_profile_link_show' => array(
				'label' => T_( 'Profile link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_profile_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Profile' ),
			),
			
			'user_subs_link_show' => array(
				'label' => T_( 'Subscriptions link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_subs_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Subscriptions' ),
			),
			
			'user_admin_link_show' => array(
				'label' => T_( 'Admin link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_admin_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Admin' ),
			),
			
			'user_register_link_show' => array(
				'label' => T_( 'Register link'),
				'note' => T_( 'Show link' ),
				'type' => 'checkbox',
				'defaultvalue' => 1,
			),
			'user_register_link' => array(
				'size' => 30,
				'note' => T_( 'Link text to display' ),
				'type' => 'text',
				'defaultvalue' => T_( 'Register' ),
			),
		), parent::get_param_definitions( $params )	);

		return $r;
	}

	/**
	 * Get name of widget
	 */
	function get_name()
	{
		return T_('User Tools');
	}


	/**
	 * Get a very short desc. Used in the widget list.
	 */
	function get_short_desc()
	{
		return format_to_output($this->disp_params['title']);
	}


  /**
	 * Get short description
	 */
	function get_desc()
	{
		return T_('Display user tools: Log in, Admin, Profile, Subscriptions, Log out');
	}


	/**
	 * Display the widget!
	 *
	 * @param array MUST contain at least the basic display params
	 */
	function display( $params )
	{
		$this->init_display( $params );

		// User tools:
		echo $this->disp_params['block_start'];

		echo $this->disp_params['block_title_start'];
		echo $this->disp_params['title'];
		echo $this->disp_params['block_title_end'];

		echo $this->disp_params['list_start'];
		if ( $this->get_param('user_login_link_show') ) 
		{
			user_login_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_login_link' ] );
		}
		if ( $this->get_param('user_register_link_show') ) 
		{
			user_register_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_register_link' ], '#', false, 'user tools widget' );
		}
		if ( $this->get_param('user_admin_link_show') ) 
		{
			user_admin_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_admin_link' ] );
		}
		if ( $this->get_param('user_profile_link_show') ) 
		{
			user_profile_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_profile_link' ] );
		}
		if ( $this->get_param('user_subs_link_show') ) 
		{
			user_subs_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_subs_link' ] );
		}
		if ( $this->get_param('user_logout_link_show') ) 
		{
			user_logout_link( $this->disp_params['item_start'], $this->disp_params['item_end'], $this->disp_params[ 'user_logout_link' ] );
		}

		if( isset($this->BlockCache) )
		{	// Do NOT cache because some of these links are using a redirect_to param, which makes it page dependent.
			// Note: also beware of the source param.
			// so this will be cached by the PageCache; there is no added benefit to cache it in the BlockCache
			// (which could have been shared between several pages):
			$this->BlockCache->abort_collect();
		}

		echo $this->disp_params['list_end'];

		echo $this->disp_params['block_end'];
	}


	/**
	 * Maybe be overriden by some widgets, depending on what THEY depend on..
	 *
	 * @return array of keys this widget depends on
	 */
	function get_cache_keys()
	{
		global $Blog, $current_User;

		return array(
				'wi_ID'   => $this->ID,					// Have the widget settings changed ?
				'set_coll_ID' => $Blog->ID,			// Have the settings of the blog changed ? (ex: new owner, new skin)
				'loggedin' => (is_logged_in() ? 1 : 0),
				// fp> note: if things get tough in the future, use a per User caching scheme:
				// 'user_ID' => (is_logged_in() ? $current_User->ID : 0), // Has the current User changed?
			);
	}
}


/*
 * $Log: _user_tools.widget.php,v $
 */
?>