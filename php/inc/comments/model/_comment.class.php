<?php
/**
 * This file implements the Comment class.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE
 *
 * @version $Id: _comment.class.php 244 2011-11-09 10:05:30Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );

/**
 * Comment Class
 *
 * @package evocore
 */
class Comment extends DataObject
{
	/**
	 * The item (parent) of this Comment (lazy-filled).
	 * @see Comment::get_Item()
	 * @see Comment::set_Item()
	 * @access protected
	 * @var Item
	 */
	var $Item;
	/**
	 * The ID of the comment's Item.
	 * @var integer
	 */
	var $item_ID;
	/**
	 * The comment's user, this is NULL for (anonymous) visitors (lazy-filled).
	 * @see Comment::get_author_User()
	 * @see Comment::set_author_User()
	 * @access protected
	 * @var User
	 */
	var $author_User;
	/**
	 * The ID of the author's user. NULL for anonymous visitors.
	 * @var integer
	 */
	var $author_user_ID;
	/**
	 * Comment type: 'comment', 'linkback', 'trackback' or 'pingback'
	 * @var string
	 */
	var $type;
	/**
	 * Comment visibility status: 'published', 'deprecated', 'redirected', 'protected', 'private' or 'draft'
	 * @var string
	 */
	var $status;
	/**
	 * Name of the (anonymous) visitor (if any).
	 * @var string
	 */
	var $author;
	/**
	 * Email address of the (anonymous) visitor (if any).
	 * @var string
	 */
	var $author_email;
	/**
	 * URL/Homepage of the (anonymous) visitor (if any).
	 * @var string
	 */
	var $author_url;
	/**
	 * IP address of the comment's author (while posting).
	 * @var string
	 */
	var $author_IP;
	/**
	 * Date of the comment (MySQL DATETIME - use e.g. {@link mysql2timestamp()}); local time ({@link $localtimenow})
	 * @var string
	 */
	var $date;
	/**
	 * @var string
	 */
	var $content;
	/**
	 * Spam karma of the comment (0-100), 0 being "probably no spam at all"
	 * @var integer
	 */
	var $spam_karma;
	/**
	 * Does an anonymous commentator allow to send messages through a message form?
	 * @var boolean
	 */
	var $allow_msgform;

	var $nofollow;
	/**
	 * @var string
	 */
	var $secret;
	/**
	 * Have post processing notifications been handled for this comment?
	 * @var string
	 */
	var $notif_status;
	/**
	 * Which cron task is responsible for handling notifications for this comment?
	 * @var integer
	 */
	var $notif_ctsk_ID;

	/**
	 * Constructor
	 */
	function Comment( $db_row = NULL )
	{
		// Call parent constructor:
		parent::DataObject( 'T_comments', 'comment_', 'comment_ID' );

		$this->delete_cascades = array(
				array( 'table'=>'T_links', 'fk'=>'link_cmt_ID', 'msg'=>T_('%d links to destination comments') ),
			);

		if( $db_row == NULL )
		{
			// echo 'null comment';
			$this->rating = NULL;
			$this->featured = 0;
			$this->nofollow = 1;
			$this->notif_status = 'noreq';
		}
		else
		{
			$this->ID = $db_row->comment_ID;
			$this->item_ID = $db_row->comment_post_ID;
			if( ! empty($db_row->comment_author_ID) )
			{
				$this->author_user_ID = $db_row->comment_author_ID;
			}
			$this->type = $db_row->comment_type;
			$this->status = $db_row->comment_status;
			$this->author = $db_row->comment_author;
			$this->author_email = $db_row->comment_author_email;
			$url = trim( $db_row->comment_author_url );
			if( ! empty($url) && ! preg_match( '~^\w+://~', $url ) )
			{ // URL given and does not start with a protocol:
				$url = 'http://'.$url;
			}
			$this->author_url = $url;
			$this->author_IP = $db_row->comment_author_IP;
			$this->date = $db_row->comment_date;
			$this->content = $db_row->comment_content;
			$this->rating = $db_row->comment_rating;
			$this->featured = $db_row->comment_featured;
			$this->nofollow = $db_row->comment_nofollow;
			$this->spam_karma = $db_row->comment_spam_karma;
			$this->allow_msgform = $db_row->comment_allow_msgform;
			$this->secret = $db_row->comment_secret;
			$this->notif_status = $db_row->comment_notif_status;
			$this->notif_ctsk_ID = $db_row->comment_notif_ctsk_ID;
		}
	}


	/**
	 * Get the author User of the comment. This is NULL for anonymous visitors.
	 *
	 * @return User
	 */
	function & get_author_User()
	{
		if( isset($this->author_user_ID) && ! isset($this->author_User) )
		{
			$UserCache = & get_UserCache();
			$this->author_User = & $UserCache->get_by_ID( $this->author_user_ID );
		}

		return $this->author_User;
	}


	/**
	 * Get the Item this comment relates to
	 *
	 * @return Item
	 */
	function & get_Item()
	{
		if( ! isset($this->Item) )
		{
			$ItemCache = & get_ItemCache();
			$this->Item = & $ItemCache->get_by_ID( $this->item_ID );
		}

		return $this->Item;
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		global $post_statuses;

		switch( $parname )
		{
			case 't_status':
				// Text status:
				return T_( $post_statuses[$this->status] );
		}

		return parent::get( $parname );
	}


	/**
	 * Set param value
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
			case 'rating':
				return $this->set_param( $parname, 'string', $parvalue, true );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Set Item this comment relates to
	 * @param Item
	 */
	function set_Item( & $Item )
	{
		$this->Item = & $Item;
		$this->item_ID = $Item->ID;
		parent::set_param( 'post_ID', 'number', $Item->ID );
	}


	/**
	 * Set author User of this comment
	 */
	function set_author_User( & $author_User )
	{
		$this->author_User = & $author_User;
		parent::set_param( 'author_ID', 'number', $author_User->ID );
	}


	/**
	 * Set the spam karma, as a number.
	 * @param integer Spam karma (-100 - 100)
	 * @access protected
	 */
	function set_spam_karma( $spam_karma )
	{
		return $this->set_param( 'spam_karma', 'number', $spam_karma );
	}


	/**
	 * Get the anchor-ID of the comment
	 *
	 * @return string
	 */
	function get_anchor()
	{
		return 'c'.$this->ID;
	}


	/**
	 * Template function: display anchor for permalinks to refer to
	 */
	function anchor()
	{
		echo '<a id="'.$this->get_anchor().'"></a>';
	}


	/**
	 * Get the comment author's name.
	 *
	 * @return string
	 */
	function get_author_name()
	{
		if( $this->get_author_User() )
		{
			return $this->author_User->get_preferred_name();
		}
		else
		{
			return $this->author;
		}
	}


	/**
	 * Get the EMail of the comment's author.
	 *
	 * @return string
	 */
	function get_author_email()
	{
		if( $this->get_author_User() )
		{ // Author is a user
			return $this->author_User->get('email');
		}
		else
		{
			return $this->author_email;
		}
	}


	/**
	 * Get the URL of the comment's author.
	 *
	 * @return string
	 */
	function get_author_url()
	{
		if( $this->get_author_User() )
		{ // Author is a user
			return $this->author_User->get('url');
		}
		else
		{
			return $this->author_url;
		}
	}


	/**
	 * Template function: display the avatar of the comment's author.
	 *
	 */
	function avatar( $size = 'crop-64x64', $class = 'bCommentAvatar', $params = array() )
	{
		if( $r = $this->get_avatar( $size, $class, $params ) )
		{
			echo $r;
		}
	}


	/**
	 * Get the avatar of the comment's author.
	 *
	 * @return string
	 */
	function get_avatar( $size = 'crop-64x64', $class = 'bCommentAvatar', $params = array() )
	{
		global $Settings, $Plugins, $default_avatar;

		if( ! $Settings->get('allow_avatars') ) 
			return;

		if( $comment_author_User = & $this->get_author_User() )
		{	// Author is a user
			if( $r = $comment_author_User->get_avatar_imgtag( $size, $class ) )
			{	// Got an image
				return $r;
			}
		}

		// TODO> add new event
		// See if plugin supplies an image
		// $img_url = $Plugins->trigger_event( 'GetCommentAvatar', array( 'Comment' => & $this, 'size' => $size ) );

		$comment_Item = $this->get_Item();
		$comment_Item->load_Blog();
		$default_gravatar = $this->Item->Blog->get_setting('default_gravatar');
		if( $default_gravatar == 'b2evo' )
		{
			$default_gravatar = $default_avatar;
		}

		if( empty($img_url) )
		{	// Use gravatar
			$params = array_merge( array(
					'default'	=> $default_gravatar,
					'size'		=> '64',
				), $params );

			$img_url = 'http://www.gravatar.com/avatar.php?gravatar_id='.md5( $this->get_author_email() );

			if( !empty($params['rating']) )
				$img_url .= '&rating='.$params['rating'];

			if( !empty($params['size']) )
				$img_url .='&size='.$params['size'];

			if( !empty($params['default']) )
				$img_url .= '&default='.urlencode($params['default']);
		}
		$img_params = array(
			'src' => $img_url,
			'alt' => $this->get_author_name(),
			'title' => $this->get_author_name(),
			'width' => $params['size'], //  dh> NOTE: works with gravatar, check if extending
			'height' => $params['size'], // dh> NOTE: works with gravatar, check if extending
		);
		if( $class )
		{ // add class
			$img_params['class'] = $class;
		}
		$imgtag = '<img'.get_field_attribs_as_string($img_params).' />';

		return $imgtag;
	}


	/**
	 * Template function: display author of comment
	 *
	 * @deprecated use Comment::author2() instead
	 * @param string String to display before author name if not a user
	 * @param string String to display after author name if not a user
	 * @param string String to display before author name if he's a user
	 * @param string String to display after author name if he's a user
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean true for link, false if you want NO html link
	 */
	function author( $before = '', $after = '#', $before_user = '', $after_user = '#',
										$format = 'htmlbody', $makelink = false )
	{
		echo $this->get_author( array(
					'before'       => $before,
					'after'        => $after,
					'before_user'  => $before_user,
					'after_user'   => $after_user,
					'format'       => $format,
					'link_to'		   => ( $makelink ? 'userurl>userpage' : '' )
				)
			);
	}


	/**
	 * Template function: display author of comment
	 *
	 * @param array
	 */
	function author2( $params = array()  )
	{
		echo $this->get_author( $params );
	}


	/**
	 * Get author of comment
	 *
	 * @param array
	 * @return string
	 */
	function get_author( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => ' ',
				'after'        => '#',
				'before_user'  => '',
				'after_user'   => '#',
				'format'       => 'htmlbody',
				'link_to'		   => 'userurl>userpage',		// 'userpage' or 'userurl' or 'userurl>userpage' 'userpage>userurl'
				'link_text'    => 'preferredname',
				'link_rel'     => '',
				'link_class'   => '',
				'thumb_size'   => 'crop-32x32',
				'thumb_class'  => '',
			), $params );

		global $Plugins;

		if( $this->get_author_User() )
		{ // Author is a registered user:
			if( $params['after_user'] == '#' ) $params['after_user'] = ' ['.T_('Member').']';

			$author_name = format_to_output( $this->author_User->get_preferred_name(), $params['format'] );

			$r = $this->author_User->get_link( $params );

			$r = $params['before_user'].$r.$params['after_user'];
		}
		else
		{	// Not a registered user, display info recorded at edit time:
			if( $params['after'] == '#' ) $params['after'] = ' ['.T_('Visitor').']';

			if( evo_strlen( $this->author_url ) <= 10 )
			{	// URL is too short anyways...
				$params['link_to'] = '';
			}

			$author_name = $this->dget( 'author', $params['format'] );

			switch( $params['link_to'] )
			{
				case 'userurl':
				case 'userurl>userpage':
				case 'userpage>userurl':
					// Make a link:
					$r = $this->get_author_url_link( $author_name, $params['before'], $params['after'], true );
					break;

				default:
					// Display the name: (NOTE: get_author_url_link( with nolink option ) would NOT handle this correctly when url is empty
					$r = $params['before'].$author_name.$params['after'];
					break;
			}
		}

		$hook_params = array(
			'data' => & $r,
			'Comment' => & $this,
			'makelink' => ! empty($params['link_to']),
		);

		$Plugins->trigger_event( 'FilterCommentAuthor', $hook_params );

		return $r;
	}


	/**
	 * Template function: display comment's author's IP
	 *
	 * @param string String to display before IP, if IP exists
	 * @param string String to display after IP, if IP exists
	 */
	function author_ip( $before='', $after='' )
	{
		if( !empty( $this->author_IP ) )
		{
			global $Plugins;

			echo $before;
			// Filter the IP by plugins for display, allowing e.g. the DNSBL plugin to add a link that displays info about the IP:
			echo $Plugins->get_trigger_event( 'FilterIpAddress', array(
					'format'=>'htmlbody',
					'data' => $this->author_IP ),
				'data' );
			echo $after;
		}
	}


	/**
	 * Template function: display link to comment author's provided email
	 *
	 * @param string String to display for link: leave empty to display email
	 * @param string String to display before email, if email exists
	 * @param string String to display after email, if email exists
	 * @param boolean false if you want NO html link
	 */
	function author_email( $linktext='', $before='', $after='', $makelink = true )
	{
		$email = $this->get_author_email();

		if( strlen( $email ) > 5 )
		{ // If email exists:
			echo $before;
			if( $makelink ) echo '<a href="mailto:'.$email.'">';
			echo ($linktext != '') ? $linktext : $email;
			if( $makelink ) echo '</a>';
			echo $after;
		}
	}


	/**
	 * Get link to comment author's provided URL
	 *
	 * @param string String to display for link: leave empty to display URL
	 * @param string String to display before link, if link exists
	 * @param string String to display after link, if link exists
	 * @param boolean false if you want NO html link
	 * @return boolean true if URL has been displayed
	 */
	function get_author_url_link( $linktext='', $before='', $after='', $makelink = true )
	{
		global $Plugins;

		$url = $this->get_author_url();

		if( evo_strlen( $url ) < 10 )
		{
			return false;
		}

		// If URL exists:
		$r = $before;
		if( $makelink )
		{
			$r .= '<a ';
			if( $this->nofollow )
			{
				$r .= 'rel="nofollow" ';
			}
			$r .= 'href="'.$url.'">';
		}
		$r .= ( empty($linktext) ? $url : $linktext );
		if( $makelink ) $r .= '</a>';
		$r .= $after;

		$Plugins->trigger_event( 'FilterCommentAuthorUrl', array( 'data' => & $r, 'makelink' => $makelink, 'Comment' => $this ) );

		return $r;
	}


  /**
	 * Template function: display link to comment author's provided URL
	 *
	 * @param string String to display for link: leave empty to display URL
	 * @param string String to display before link, if link exists
	 * @param string String to display after link, if link exists
	 * @param boolean false if you want NO html link
	 * @return boolean true if URL has been displayed
	 */
	function author_url( $linktext='', $before='', $after='', $makelink = true )
	{
		$r = $this->get_author_url_link( $linktext, $before, $after, $makelink );
		if( !empty( $r ) )
		{
			echo $r;
			return true;
		}
		return false;
	}


	/**
	 * Display author url, delete icon and ban icon if user has proper rights
	 *
	 * @param boolean true to use ajax button
	 * @param boolean true to check user permission to edit this comment and antispam screen
	 */
	function author_url_with_actions( $redirect_to = NULL, $ajax_button = false, $check_perms = true, $save_context = true )
	{
		global $current_User;
		if( $this->author_url( '', ' <span &bull; Url: id="commenturl_'.$this->ID.'" <span class="bUrl" >', '</span>' ) )
		{
			$Item = & $this->get_Item();
			if( $current_User->check_perm( $this->blogperm_name(), '', false, $Item->get_blog_ID() ) )
			{ // There is an URL and we have permission to edit this comment...
				if( $redirect_to == NULL )
				{
					$redirect_to = rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
				}
				$this->deleteurl_link( $redirect_to, $ajax_button, false, '&amp;', $save_context );
				$this->banurl_link( $redirect_to, $ajax_button, true, '&amp;', $save_context );
			}
			echo '</span>';
		}
	}


	/**
	 * Template function: display spam karma of the comment (in percent)
	 *
	 * "%s" gets replaced by the karma value
	 *
	 * @param string Template string to display, if we have a karma value
	 * @param string Template string to display, if we have no karma value (pre-Phoenix)
	 */
	function spam_karma( $template = '%s%', $template_unknown = NULL )
	{
		if( isset($this->spam_karma) )
		{
			echo str_replace( '%s', $this->spam_karma, $template );
		}
		else
		{
			if( ! isset($template_unknown) )
			{
				echo /* TRANS: "not available" */ T_('N/A');
			}
			else
			{
				echo $template_unknown;
			}
		}
	}


	/**
	 * Provide link to edit a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @return boolean
	 */
	function edit_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( empty($this->ID) )
		{	// Happens in Preview
			return false;
		}

		$this->get_Item();

		if( ! $current_User->check_perm( $this->blogperm_name(), '', false, $this->Item->get_blog_ID() ) )
		{ // If User has no permission to edit comments with this comment status:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'edit' ).' '.T_('Edit...');
		if( $title == '#' ) $title = T_('Edit this comment');

		echo $before;
		echo '<a href="'.$admin_url.'?ctrl=comments&amp;action=edit&amp;comment_ID='.$this->ID;
		if( $save_context )
		{
			if( $redirect_to != NULL )
			{
				echo $glue.'redirect_to='.$redirect_to;
			}
			else
			{
				echo $glue.'redirect_to='.rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
			}
		}
		echo '" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Display delete icon for deleting author_url if user has proper rights
	 * @param boolean true if create ajax button
	 * @param boolean true if need permission check, because it wasn't checked before
	 * @param glue between url params
	 * @return link on success, false otherwise
	 */
	function deleteurl_link( $redirect_to, $ajax_button = false, $check_perm = true, $glue = '&amp;', $save_context = true )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		$Item = & $this->get_Item();
		if( $check_perm && ! $current_User->check_perm( $this->blogperm_name(), '', false, $Item->get_blog_ID() ) )
		{ // If current user has no permission to edit comments, with this comment status:
			return false;
		}

		if( $save_context )
		{
			if( $redirect_to == NULL )
			{
				$redirect_to = rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
			}
			$redirect_to = $glue.'redirect_to='.$redirect_to;
		}
		else
		{
			$redirect_to = '';
		}

		if( $ajax_button )
		{
			echo ' <a href="javascript:delete_comment_url('.$this->ID.');">'.get_icon( 'delete' ).'</a>';
		}
		else
		{
			$url = $admin_url.'?ctrl=comments&amp;action=delete_url&amp;comment_ID='.$this->ID.'&amp;'.url_crumb('comment') ;
			echo ' <a href="'.$url.$redirect_to.'"'.get_icon( 'delete' ).'</a>';
		}
	}


	/**
	 * Display ban icon, which goes to the antispam screen with keyword=author_url
	 *
	 * @param boolean true if create ajax button
	 * @param boolean true if need permission check, because it wasn't check before
	 * @param glue between url params
	 * @return link on success, false otherwise
	 */
	function banurl_link( $redirect_to, $ajax_button = false, $check_perm = true, $glue = '&amp;', $save_context = true )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( $check_perm && ! $current_User->check_perm( 'spamblacklist', 'edit' ) )
		{ // if current user has no permission to edit spams
			return false;
		}

		if( $save_context )
		{
			if( $redirect_to == NULL )
			{
				$redirect_to = rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
			}
			$redirect_to = $glue.'redirect_to='.$redirect_to;
		}
		else
		{
			$redirect_to = '';
		}

		// TODO: really ban the base domain! - not by keyword
		$authorurl = rawurlencode(get_ban_domain($this->get_author_url()));

		if( $ajax_button )
		{
			echo ' <a id="ban_url" href="javascript:ban_url('.'\''.$authorurl.'\''.');">'.get_icon( 'ban' ).'</a>';
		}
		else
		{
			echo ' <a href="'.$admin_url.'?ctrl=antispam&amp;action=ban&amp;keyword='.$authorurl
					.$redirect_to.'&amp;'.url_crumb('antispam').'">'.get_icon( 'ban' ).'</a> ';
		}
	}


	/**
	 * Displays button for deleeing the Comment if user has proper rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string glue between url params
	 * @param boolean save context?
	 * @param boolean true if create AJAX button
	 */
	function delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button = false, $glue = '&amp;', $save_context = true, $ajax_button = false )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		if( empty($this->ID) )
		{	// Happens in Preview
			return false;
		}

		$this->get_Item();

		if( ! $current_User->check_perm( $this->blogperm_name(), '', false, $this->Item->get_blog_ID() ) )
		{ // If User has no permission to edit comments, with this comment status:
			return false;
		}

		if( $text == '#' )
		{ // Use icon+text as default, if not displayed as button (otherwise just the text)
			if( ! $button )
			{
				$text = get_icon( 'delete', 'imgtag' ).' '.T_('Delete!');
			}
			else
			{
				$text = T_('Delete!');
			}
		}
		if( $title == '#' ) $title = T_('Delete this comment');

		$url = $admin_url.'?ctrl=comments&amp;action=delete&amp;comment_ID='.$this->ID.'&amp;'.url_crumb('comment') ;
   		if( $save_context )
		{
			$url .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
		}

		echo $before;
		if( $ajax_button && ( $this->status != 'trash' ) )
		{
			echo '<a href="javascript:deleteComment('.$this->ID.');" title="'.$title.'"';
			if( !empty( $class ) ) echo ' class="'.$class.'"';
			echo '>'.$text.'</a>';
		}
		else
		{
			if( $button )
			{ // Display as button
				echo '<input type="button"';
				echo ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
				echo TS_('You are about to delete this comment!\\nThis cannot be undone!');
				echo '\') ) { document.location.href=\''.$url.'\' }"';
				if( !empty( $class ) ) echo ' class="'.$class.'"';
				echo '/>';
			}
			else
			{ // Display as link
				echo '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
				echo TS_('You are about to delete this comment!\\nThis cannot be undone!');
				echo '\')"';
				if( !empty( $class ) ) echo ' class="'.$class.'"';
				echo '>'.$text.'</a>';
			}
		}
		echo $after;

		return true;
	}


	/**
	 * Provide link to deprecate a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 * @param boolean true if create AJAX button
	 */
	function get_deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		$this->get_Item();

		if( ($this->status == 'deprecated') // Already deprecateded!
			|| ! $current_User->check_perm( $this->blogperm_name(), '', false, $this->Item->get_blog_ID() ) )
		{ // If User has no permission to edit comments, with this comment status:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'deprecate', 'imgtag' ).' '.T_('Deprecate!');
		if( $title == '#' ) $title = T_('Deprecate this comment!');

		$r = $before;
		$r .= '<a href="';

		if( $ajax_button )
		{
			if( $save_context && ( $redirect_to == NULL ) )
			{
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$r .= 'javascript:setCommentStatus('.$this->ID.', \'deprecated\', \''.$redirect_to.'\');';
		}
		else
		{
			$r .= $admin_url.'?ctrl=comments'.$glue.'action=deprecate'.$glue.'comment_ID='.$this->ID.'&amp;'.url_crumb('comment');
	   		if( $save_context )
			{
				$r .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
			}
		}

		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Display link to deprecate a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 * @param boolean true if create AJAX button
	 */
	function deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		echo $this->get_deprecate_link( $before, $after, $text, $title, $class, $glue, $save_context, $ajax_button, $redirect_to );
	}


	/**
	 * Provide link to publish a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 * @param boolean true if create AJAX button
	 */
	function get_publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in() ) return false;

		$this->get_Item();

		if( ($this->status == 'published') // Already published!
			|| ! $current_User->check_perm( $this->blogperm_name(), '', false, $this->Item->get_blog_ID() ) )
		{ // If User has no permission to edit comments, with this comment status:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'publish', 'imgtag' ).' '.T_('Publish!');
		if( $title == '#' ) $title = T_('Publish this comment!');

		$r = $before;
		$r .= '<a href="';
		if( $ajax_button )
		{
			if( $save_context && ( $redirect_to == NULL ) )
			{
				$redirect_to = regenerate_url( '', 'filter=restore', '', '&' );
			}
			$r .= 'javascript:setCommentStatus('.$this->ID.', \'published\', \''.$redirect_to.'\');';
		}
		else
		{
			$r .= $admin_url.'?ctrl=comments'.$glue.'action=publish'.$glue.'comment_ID='.$this->ID.'&amp;'.url_crumb('comment');
	   		if( $save_context )
			{
				$r .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', 'filter=restore', '', '&' ) );
			}
		}

		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Display link to publish a comment if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 * @param boolean save context?
	 * @param boolean true if create AJAX button
	 */
	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true, $ajax_button = false, $redirect_to = NULL )
	{
		echo $this->get_publish_link( $before, $after, $text, $title, $class, $glue, $save_context, $ajax_button, $redirect_to );
	}


	/**
	 * Provide link to message form for this comment's author
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 */
	function msgform_link( $form_url, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		if( $this->get_author_User() )
		{ // This comment is from a registered user:
			if( empty($this->author_User->email) )
			{ // We have no email for this Author :(
				return false;
			}
			elseif( empty($this->author_User->allow_msgform) )
			{ // User does not allow message form
				return false;
			}
			$form_url = url_add_param( $form_url, 'recipient_id='.$this->author_User->ID );
		}
		else
		{ // This comment is from a visitor:
			if( empty($this->author_email) )
			{ // We have no email for this comment :(
				return false;
			}
			elseif( empty($this->allow_msgform) )
			{ // Anonymous commentator does not allow message form (for this comment)
				return false;
			}
		}

		$form_url = url_add_param( $form_url, 'comment_id='.$this->ID.'&amp;post_id='.$this->item_ID
				.'&amp;redirect_to='.rawurlencode(url_rel_to_same_host(regenerate_url('','','','&'), $form_url)) );

		if( $title == '#' ) $title = T_('Send email to comment author');
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		// TODO: have an SEO setting for nofollow here, default to nofollow
		echo ' rel="nofollow"';
		echo '>'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Generate permalink to this comment.
	 *
	 * Note: This actually only returns the URL, to get a real link, use Comment::get_permanent_link()
	 */
	function get_permanent_url()
	{
		$this->get_Item();

		$post_permalink = $this->Item->get_single_url( 'auto' );

		return $post_permalink.'#'.$this->get_anchor();
	}


	/**
	 * Template function: display permalink to this comment
	 *
	 * Note: This actually only returns the URL, to get a real link, use Comment::permanent_link()
	 *
	 * @param string 'urltitle', 'pid', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permanent_url( $mode = '', $blogurl='' )
	{
		echo $this->get_permanent_url( $mode, $blogurl );
	}


	/**
	 * Returns a permalink link to the Comment
	 *
	 * Note: If you only want the permalink URL, use Comment::get_permanent_url()
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#'
	 * @param string link title
	 * @param string class name
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '', $nofollow = false )
	{
		if( $this->status != 'published' )
		{
			return '';
		}

		global $current_User, $baseurl;

		switch( $text )
		{
			case '#':
				$text = get_icon( 'permalink' ).T_('Permalink');
				break;

			case '#icon#':
				$text = get_icon( 'permalink' );
				break;

			case '#text#':
				$text = T_('Permalink');
				break;
		}

		if( $title == '#' ) $title = T_('Permanent link to this comment');

		$url = $this->get_permanent_url();

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		if( !empty( $nofollow ) ) $r .= ' rel="nofollow"';
		$r .= '>'.$text.'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Comment
	 *
	 * Note: If you only want the permalink URL, use Comment::permanent_url()
	 */
	function permanent_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'text'        => '#',
				'title'       => '#',
				'class'       => '',
				'nofollow'    => false,
			), $params );

		echo $params['before'];
		echo $this->get_permanent_link( $params['text'], $params['title'], $params['class'], $params['nofollow'] );
		echo $params['after'];
	}


	/**
	 * Template function: get content of comment
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @return string
	 */
	function get_content( $format = 'htmlbody' )
	{
		global $Plugins;

		$comment = $this->content;
		// fp> obsolete: $comment = str_replace('<trackback />', '', $comment);
		$Plugins->trigger_event( 'FilterCommentContent', array( 'data' => & $comment, 'Comment' => $this ) );
		$comment = format_to_output( $comment, $format );

		return $comment;
	}


	/**
	 * Template function: display content of comment
	 *
	 * @param string Output format, see {@link format_to_output()}
	 * @param boolean Add ban url action icon after each url or not
	 * @param boolean show comment attachments
	 * @param array attachment display params
	 */
	function content( $format = 'htmlbody', $ban_urls = false, $show_attachments = true, $params = array() )
	{
		global $current_User;

		if( $show_attachments )
		{
			// Make sure we are not missing any param:
			$params = array_merge( array(
					'before_image'        => '<div class="image_block">',
					'before_image_legend' => '<div class="image_legend">',
					'after_image_legend'  => '</div>',
					'after_image'         => '</div>',
					'image_size'          => 'fit-400x320',
				), $params );
			$attachments = array( 'images' => array(), 'docs' => array() );
			if( empty( $this->ID ) && isset( $this->preview_attachments ) )
			{ // PREVIEW
				$attachment_ids = explode( ',', $this->preview_attachments );
				$FileCache = & get_FileCache();
				foreach( $attachment_ids as $ID )
				{
					$File = $FileCache->get_by_ID( $ID, false, false );
					if( $File != NULL )
					{
						$index = $File->is_image() ? 'images' : 'docs';
						$attachments[$index][] = $File;
					}
				}
			}
			else
			{ // Get links
				$LinkCache = & get_LinkCache();
				$commentLinks = $LinkCache->get_by_comment_ID( $this->ID );
				if( !empty( $commentLinks ) )
				{
					foreach( $commentLinks as $Link )
					{
						$File = $Link->get_File();
						$index = $File->is_image() ? 'images' : 'docs';
						$attachments[$index][] = $File;
					}
				}
			}
		}

		if( isset( $attachments['images'] ) )
		{
			foreach( $attachments['images'] as $image_File )
			{ // show image attachments
				echo $image_File->get_tag( $params['before_image'], $params['before_image_legend'], $params['after_image_legend'], $params['after_image'], $params['image_size'] );
			}
		}

		if( $ban_urls )
		{ // add ban icons
			$Item = & $this->get_Item();
			// check if user has edit permission for this comment
			$ban_urls = $current_User->check_perm( $this->blogperm_name(), '', false, $Item->get_blog_ID() );
		}

		if( $ban_urls )
		{ // ban urls and user has permission
			echo add_ban_icons( $this->get_content( $format ) );
		}
		else
		{ // don't ban urls
			echo $this->get_content( $format );
		}

		if( isset( $attachments['docs'] ) )
		{ // show not image attachments
			$after_docs = '';
			if( count( $attachments['docs'] ) > 0 )
			{
				echo '<br /><b>'.T_( 'Attachments:' ).'</b>';
				echo '<ul class="bFiles">';
				$after_docs = '</ul>';
			}
			foreach( $attachments['docs'] as $doc_File )
			{
				echo '<li>';
				echo action_icon( T_('Download file'), 'download', $doc_File->get_url(), '', 5 ).' ';
				echo $doc_File->get_view_link( $doc_File->get_name() );
				echo '('.bytesreadable( $doc_File->get_size() ).')';
				echo '</li>';
			}
			echo $after_docs;
		}
	}


	/**
	 * Get title of comment, e.g. "Comment from: Foo Bar"
	 *
	 * @param array Params
	 *   'author_format': Formatting of the author (%s gets replaced with
	 *                    the author string)
	 * @return string
	 */
	function get_title($params = array())
	{
		if( empty($params['author_format']) )
		{
			$params['author_format'] = '%s';
		}
		$author = sprintf($params['author_format'], $this->get_author());

		switch( $this->get( 'type' ) )
		{
			case 'comment': // Display a comment:
				$s = T_('Comment from %s');
				break;

			case 'trackback': // Display a trackback:
				$s = T_('Trackback from %s');
				break;

			case 'pingback': // Display a pingback:
				$s = T_('Pingback from %s');
				break;
		}
		return sprintf($s, $author);
	}


	/**
	 * Template function: display date (datetime) of comment
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function date( $format='', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->date, $useGM);
		else
			echo mysql2date( $format, $this->date, $useGM);
	}


	/**
	 * Template function: display time (datetime) of comment
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function time( $format='', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->date, $useGM );
		else
			echo mysql2date( $format, $this->date, $useGM );
	}


	/**
	 * Template tag:  display rating
	 */
	function rating( $params = array() )
	{
		if( empty( $this->rating ) )
		{
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '<div class="comment_rating">',
				'after'       => '</div>',
				'star_class'  => 'middle',
			), $params );

		echo $params['before'];

		star_rating( $this->rating, $params['star_class'] );

		echo $params['after'];
	}

  /**
	 * Rating input
	 */
	function rating_input( $params = array() )
	{
		$params = array_merge( array(
									'before'    => '',
									'after'     => '',
									'label_low'  => T_('Poor'),
									'label_high' => T_('Excellent'),
								), $params );

		echo $params['before'];

		echo $params['label_low'];

		for( $i=1; $i<=5; $i++ )
		{
			echo '<input type="radio" class="radio" name="comment_rating" value="'.$i.'"';
			if( $this->rating == $i )
			{
				echo ' checked="checked"';
			}
			echo ' />';
		}

		echo $params['label_high'];

		echo $params['after'];
	}


  /**
	 * Rating reset input
	 */
	function rating_none_input( $params = array() )
	{
		$params = array_merge( array(
									'before'    => '',
									'after'     => '',
									'label'     => T_('No rating'),
								), $params );

		echo $params['before'];

		echo '<label><input type="radio" class="radio" name="comment_rating" value="0"';
		if( empty($this->rating) )
		{
			echo ' checked="checked"';
		}
		echo ' />';

		echo $params['label'].'</label>';

		echo $params['after'];
	}


	/**
	 * Template function: display status of comment
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function status( $format = 'htmlbody' )
	{
		global $post_statuses;

		if( $format == 'raw' )
		{
			$this->disp( 'status', 'raw' );
		}
		else
		{
			echo format_to_output( $this->get('t_status'), $format );
		}
	}


	/**
	 * Handle comment email notifications
	 *
	 * Should be called only when a new comment was posted or when a comment status was changed to published
	 */
	function handle_notifications( $just_posted = false )
	{
		global $Settings;

		if( $just_posted )
		{ // send email notification to moderators
			$this->send_email_notifications( true );
		}

		if( $this->status != 'published' )
		{ // don't send notificaitons about non published comments
			return;
		}

		$notifications_mode = $Settings->get('outbound_notifications_mode');

		if( $notifications_mode == 'off' )
		{ // don't send notification
			return false;
		}

		if( $this->get( 'notif_status' ) != 'noreq' )
		{ // notification have been done before, or is in progress
			return false;
		}

		$edited_Item = & $this->get_Item();

		if( $notifications_mode == 'immediate' )
		{ // Send email notifications now!
			$this->send_email_notifications( false, $just_posted );

			// Record that processing has been done:
			$this->set( 'notif_status', 'finished' );
		}
		else
		{ // Create scheduled job to send notifications
			// CREATE OBJECT:
			load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
			$edited_Cronjob = new Cronjob();

			// start datetime. We do not want to ping before the post is effectively published:
			$edited_Cronjob->set( 'start_datetime', $this->date );

			// name:
			$edited_Cronjob->set( 'name', sprintf( T_('Send notifications about &laquo;%s&raquo; new comment'), strip_tags($edited_Item->get( 'title' ) ) ) );

			// controller:
			$edited_Cronjob->set( 'controller', 'cron/jobs/_comment_notifications.job.php' );

			// params: specify which post this job is supposed to send notifications for:
			$edited_Cronjob->set( 'params', array( 'comment_ID' => $this->ID, 'except_moderators' => $just_posted ) );

			// Save cronjob to DB:
			$edited_Cronjob->dbinsert();

			// Memorize the cron job ID which is going to handle this post:
			$this->set( 'notif_ctsk_ID', $edited_Cronjob->ID );

			// Record that processing has been scheduled:
			$this->set( 'notif_status', 'todo' );
		}
		// update comment notification params
		$this->dbupdate();
	}


	/**
	 * Send email notifications to subscribed users:
	 *
	 * efy-asimo> moderatation and subscription notifications have been separated
	 *
	 * @param boolean true if send only moderation email, false otherwise
	 * @param boolean true if send for everyone else but not for moterators, because a moderation email was sent for them
	 */
	function send_email_notifications( $only_moderators = false, $except_moderators = false )
	{
		global $DB, $admin_url, $baseurl, $debug, $Debuglog, $htsrv_url;

		if( $only_moderators && $except_moderators )
		{ // at least one of them must be false
			return;
		}

		$edited_Item = & $this->get_Item();
		$edited_Blog = & $edited_Item->get_Blog();
		$owner_User = $edited_Blog->get_owner_User();
		$notify_users = array();

		if( $only_moderators || $except_moderators )
		{ // we need the list of moderators:
			$sql = 'SELECT DISTINCT user_email, user_locale, user_ID, user_login, user_nickname, user_firstname, user_unsubscribe_key
						FROM T_coll_user_perms INNER JOIN T_users ON bloguser_user_ID = user_ID
						WHERE bloguser_blog_ID = '.$edited_Blog->ID.
						' AND bloguser_perm_draft_cmts <> 0 AND bloguser_perm_publ_cmts <> 0
						AND bloguser_perm_depr_cmts <> 0 AND user_notify_moderation <> 0 AND LENGTH(TRIM(user_email)) > 0';
			$moderators_to_notify = $DB->get_results( $sql );
		}

		if( $only_moderators )
		{ // Preprocess moderator list:
			foreach( $moderators_to_notify as $moderator )
			{
				$name = get_prefered_name( $moderator->user_nickname, $moderator->user_firstname, $moderator->user_login );
				$notify_users[$moderator->user_ID] = build_notify_data( $moderator->user_email, $moderator->user_locale, $moderator->user_unsubscribe_key, 'moderator', $name, $moderator->user_login );
			}
			if( $owner_User->get( 'notify_moderation' ) && is_email( $owner_User->get( 'email' ) ) )
			{ // add blog owner
				$name = get_prefered_name( $owner_User->get( 'nickname' ), $owner_User->get( 'firstname' ), $owner_User->get( 'login' ) );
				$notify_users[$owner_User->ID] = build_notify_data( $owner_User->get( 'email' ), $owner_User->get( 'locale' ), $owner_User->get( 'unsubscribe_key' ), 'moderator', $name, $owner_User->get( 'login' ) );
			}
		}
		else
		{ // Not just moderators:
			$moderators = array();
			$except_condition = '';

			if( $except_moderators )
			{ // Set except moderators condition. Exclude moderators who already got a notification email.
				foreach( $moderators_to_notify as $moderator )
				{
					$moderators[] = $moderator->user_email;
				}
				if( $owner_User->get( 'notify_moderation' ) && is_email( $owner_User->get( 'email' ) ) )
				{ // add blog owner
					$moderators[] = $owner_User->get( 'email' );
				}
				if( ! empty( $moderators ) )
				{
					$except_condition = ' AND user_email NOT IN ( "'.implode( '", "', $moderators ).'" )';
				}
			}

			// Check if we need to include the item creator user:
			$creator_User = & $edited_Item->get_creator_User();
			if( $creator_User->get( 'notify' ) && ( ! empty( $creator_User->email ) ) )
			{ // Creator wants to be notified...
				if( ( ! ($this->get_author_User() // comment is from registered user
								&& $creator_User->login == $this->author_User->login) ) // comment is from same user as post
						&& ! ( in_array( $creator_User->get( 'email' ), $moderators ) ) ) // creator user is not a moderator (moderators already got an email)
				{	// Creator is not commenting on his own post...
					$name = get_prefered_name( $creator_User->get( 'nickname' ), $creator_User->get( 'firstname' ), $creator_User->get( 'login' ) );
					$notify_users[$creator_User->ID] = build_notify_data( $creator_User->get( 'email' ), $creator_User->get( 'locale' ), $creator_User->get( 'unsubscribe_key' ), 'creator', $name, $creator_User->get( 'login' ) );
				}
			}

			// Get list of users who want to be notified about the this post comments:
			if( $edited_Blog->get_setting( 'allow_item_subscriptions' ) )
			{ // item subscriptions is allowed
				$sql = 'SELECT DISTINCT user_email, user_locale, user_ID, user_unsubscribe_key, user_login, user_nickname, user_firstname
									FROM T_items__subscriptions INNER JOIN T_users ON isub_user_ID = user_ID
								 WHERE isub_item_ID = '.$edited_Item->ID.'
								   AND isub_comments <> 0
								   AND LENGTH(TRIM(user_email)) > 0'.$except_condition;
				$notify_list = $DB->get_results( $sql );

				// Preprocess list:
				foreach( $notify_list as $notification )
				{
					$name = get_prefered_name( $notification->user_nickname, $notification->user_firstname, $notification->user_login );
					$notify_users[$notification->user_ID] = build_notify_data( $notification->user_email, $notification->user_locale, $notification->user_unsubscribe_key, 'item_subscription', $name, $notification->user_login );
				}
			}

			// Get list of users who want to be notfied about this blog comments:
			if( $edited_Blog->get_setting( 'allow_subscriptions' ) )
			{ // blog subscription is allowed
				$sql = 'SELECT DISTINCT user_email, user_locale, user_ID, user_unsubscribe_key, user_login, user_nickname, user_firstname
								FROM T_subscriptions INNER JOIN T_users ON sub_user_ID = user_ID
							 WHERE sub_coll_ID = '.$edited_Blog->ID.'
							   AND sub_comments <> 0
							   AND LENGTH(TRIM(user_email)) > 0'.$except_condition;
				$notify_list = $DB->get_results( $sql );

				// Preprocess list:
				foreach( $notify_list as $notification )
				{
					$name = get_prefered_name( $notification->user_nickname, $notification->user_firstname, $notification->user_login );
					$notify_users[$notification->user_ID] = build_notify_data( $notification->user_email, $notification->user_locale, $notification->user_unsubscribe_key, 'blog_subscription', $name, $notification->user_login );
				}
			}
		}

		if( ! count( $notify_users ) )
		{ // No-one to notify:
			return false;
		}


		/*
		 * We have a list of email addresses to notify:
		 */

		// TODO: dh> this reveals the comments author's email address to all subscribers!!
		//           $notify_from should get used by default, unless the user has opted in to be the sender!
		// fp>If the subscriber has permission to moderate the comments, he SHOULD receive the email address.
    // fp>asimo: please set reply_to for moderators/blog/post owners only -- NOT for other subscribers
		if( $this->get_author_User() )
		{ // Comment from a registered user:
			$reply_to = $this->author_User->get('email');
		}
		elseif( ! empty( $this->author_email ) )
		{ // non-member, but with email address:
			$reply_to = $this->author_email;
		}
		else
		{ // Fallback (we have no email address):  fp>TODO: or the subscriber is not allowed to view it.
			$reply_to = NULL;
		}

		// Send emails:
		foreach( $notify_users as $notify_user_ID => $notify_data )
		{
			// get data content
			$notify_email = $notify_data[ 'email' ];
			$notify_locale = $notify_data[ 'locale' ];
			$notify_key = $notify_data[ 'key' ];
			$notify_type = $notify_data[ 'type' ];

			locale_temp_switch($notify_locale);
			$notify_salutation = sprintf( T_( 'Hello %s !' ), $notify_data[ 'prefered_name' ] )."\n\n";

			switch( $this->type )
			{
				case 'trackback':
					/* TRANS: Subject of the mail to send on new trackbacks. First %s is the blog's shortname, the second %s is the item's title. */
					$subject = T_('[%s] New trackback on "%s"');
					break;

				default:
					/* TRANS: Subject of the mail to send on new comments. First %s is the blog's shortname, the second %s is the item's title. */
					$subject = T_('[%s] New comment on "%s"');
					if( $only_moderators )
					{
						$subject = T_('[%s] New comment awaiting moderation on "%s"');
					}
			}

			$subject = sprintf( $subject, $edited_Blog->get('shortname'), $edited_Item->get('title') );

			$notify_message = T_('Blog').': '.$edited_Blog->get('shortname')."\n"
				// Mail bloat: .' ( '.str_replace('&amp;', '&', $edited_Blog->gen_blogurl())." )\n"
				.T_('Post').': '.$edited_Item->get('title')."\n";
				// Mail bloat: .' ( '.str_replace('&amp;', '&', $edited_Item->get_permanent_url())." )\n";
				// TODO: fp> We MAY want to force short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking

			switch( $this->type )
			{
				case 'trackback':
					$user_domain = gethostbyaddr($this->author_IP);
					$notify_message .= T_('Website').": $this->author (IP: $this->author_IP, $user_domain)\n";
					$notify_message .= T_('Url').": $this->author_url\n";
					break;

				default:
					if( $this->get_author_User() )
					{ // Comment from a registered user:
						$notify_message .= T_('Author').': '.$this->author_User->get('preferredname').' ('.$this->author_User->get('login').")\n";
					}
					else
					{ // Comment from visitor:
						$user_domain = gethostbyaddr($this->author_IP);
						$notify_message .= T_('Author').": $this->author (IP: $this->author_IP, $user_domain)\n";
						$notify_message .= T_('Email').": $this->author_email\n";
						$notify_message .= T_('Url').": $this->author_url\n";
					}
			}

			$notify_message = $notify_salutation.
				T_('Comment').': '.str_replace('&amp;', '&', $this->get_permanent_url())."\n"
				// TODO: fp> We MAY want to force a short URL and avoid it to wrap on a new line in the mail which may prevent people from clicking
				.$notify_message;

			if( !empty( $this->rating ) )
			{
				$notify_message .= T_('Rating').": $this->rating\n";
			}

			$notify_message .= $this->get('content')
				."\n\n-- \n";

			if( $notify_type == 'moderator' )
			{ // moderation email
        // fp> users have asked for this even if not draft:
        // if( $this->status == 'draft' )
				{
					$notify_message .= T_('Quick moderation').': '.$htsrv_url.'comment_review.php?cmt_ID='.$this->ID.'&secret='.$this->secret."\n\n";
				}
				$notify_message .= T_('Edit comment').': '.$admin_url.'?ctrl=comments&action=edit&comment_ID='.$this->ID."\n\n";
			}
			else if( $notify_type == 'blog_subscription' )
			{ // blog subscription
				$notify_message .= T_( 'You are receiving notifications when anyone comments on any post.' )."\n";
				$notify_message .= T_( 'If you don\'t want to receive any more notifications on this blog, click here' ).': '
									.$htsrv_url.'quick_unsubscribe.php?type=collection&user_ID='.$notify_user_ID.'&coll_ID='.$edited_Blog->ID.'&key='.md5( $notify_user_ID.$notify_key )."\n\n";
			}
			else if( $notify_type == 'item_subscription' )
			{ // item subscription
				$notify_message .= T_( 'You are receiving notifications when anyone comments on this post.' )."\n";
				$notify_message .= T_( 'If you don\'t want to receive any more notifications on this post, click here' ).': '
									.$htsrv_url.'quick_unsubscribe.php?type=post&user_ID='.$notify_user_ID.'&post_ID='.$edited_Item->ID.'&key='.md5( $notify_user_ID.$notify_key )."\n\n";
			}
			else if( $notify_type == 'creator' )
			{ // user is the creator of the post
				$notify_message .= T_( 'This is your post. You are receiving notifications when anyone comments on your posts.' )."\n";
				$notify_message .= T_( 'If you don\'t want to receive any more notifications on your posts, click here' ).': '
									.$htsrv_url.'quick_unsubscribe.php?type=creator&user_ID='.$notify_user_ID.'&key='.md5( $notify_user_ID.$notify_key )."\n\n";
			}
			else
			{
				debug_die( 'Unknown user subscription type' );
			}

			$footer = sprintf( T_( 'This message was automatically generated by b2evolution running on %s.' ), $baseurl )
				."\n".T_( 'Please do not reply to this email.' )
				."\n".sprintf( T_( 'Your login is: %s' ), $notify_data[ 'login' ] );
			$notify_message .= $footer;

			if( $debug )
			{
				$mail_dump = "Sending notification to $notify_email:<pre>Subject: $subject\n$notify_message</pre>";

				if( $debug >= 2 )
				{ // output mail content - NOTE: this will kill sending of headers.
					echo "<p>$mail_dump</p>";
				}

				$Debuglog->add( $mail_dump, 'notification' );
			}

      // Send the email:
			send_mail( $notify_email, NULL, $subject, $notify_message, NULL, NULL, array( 'Reply-To' => $reply_to ) );

			locale_restore_previous();
		}
	}


	/**
	 * Trigger event AfterCommentUpdate after calling parent method.
	 *
	 * @return boolean true on success
	 */
	function dbupdate()
	{
		global $Plugins;

		// if( $this->status != 'draft' )
		{	// We don't want to keep "secret" moderation access once we've published or deprecated a comment
      // fp>asimo: please change the following to null not in dbupdate but explicitely when a comment is updated from quick moderation OR from normal edit form
      // $this->set( 'secret', null );
		}

		$dbchanges = $this->dbchanges;

		if( ( $r = parent::dbupdate() ) !== false )
		{
			$Plugins->trigger_event( 'AfterCommentUpdate', $params = array( 'Comment' => & $this, 'dbchanges' => $dbchanges ) );
		}

		return $r;
	}


	/**
	 * Get karma and set it before adding the Comment to DB.
	 *
	 * @return boolean true on success, false if it did not get inserted
	 */
	function dbinsert()
	{
		/**
		 * @var Plugins
		 */
		global $Plugins;
		global $Settings;

		// Get karma percentage (interval -100 - 100)
		$spam_karma = $Plugins->trigger_karma_collect( 'GetSpamKarmaForComment', array( 'Comment' => & $this ) );

		$this->set_spam_karma( $spam_karma );

		// Change status accordingly:
		if( ! is_null($spam_karma) )
		{
			if( $spam_karma < $Settings->get('antispam_threshold_publish') )
			{ // Publish:
				$this->set( 'status', 'published' );
			}
			elseif( $spam_karma > $Settings->get('antispam_threshold_delete') )
			{ // Delete/No insert:
				return false;
			}
		}

		//if( $this->status == 'draft' )
		{	// We will allow "secret" moderation only to draft comments:
      // fp> users have requested this for all comments
      $this->set( 'secret', generate_random_key() );
		}

		$dbchanges = $this->dbchanges;

		if( $r = parent::dbinsert() )
		{
			$Plugins->trigger_event( 'AfterCommentInsert', $params = array( 'Comment' => & $this, 'dbchanges' => $dbchanges ) );
		}

		return $r;
	}


	/**
	 * Trigger event AfterCommentDelete after calling parent method.
	 *
	 * @return boolean true on success
	 */
	function dbdelete()
	{
		global $Plugins, $DB;

		if( $this->status == 'trash' )
		{
			// remember ID, because parent method resets it to 0
			$old_ID = $this->ID;

			// Select comment attachment ids
			$result = $DB->get_col( '
				SELECT link_file_ID
					FROM T_links
				 WHERE link_cmt_ID = '.$this->ID );

			if( $r = parent::dbdelete() )
			{
				if( !empty( $result ) )
				{ // remove deleted comment not linked attachments
					remove_orphan_files( $result );
				}

				// re-set the ID for the Plugin event
				$this->ID = $old_ID;

				$Plugins->trigger_event( 'AfterCommentDelete', $params = array( 'Comment' => & $this ) );

				$this->ID = 0;
			}
		}
		else
		{ // don't delete, just move to the trash:
			$this->set( 'status', 'trash' );
			$r = $this->dbupdate();
		}

		return $r;
	}


	/**
	 * Get the blog advanced permission name for this comment
	 *
	 * @return string status specific blog comment permission name
	 */
	function blogperm_name()
	{
		switch( $this->get( 'status' ) )
		{
			case 'published':
				return 'blog_published_comments';

			case 'draft':
				return 'blog_draft_comments';

			case 'deprecated':
				return 'blog_deprecated_comments';

			case 'trash':
				return 'blog_trash_comments';

			default:
				debug_die( 'Invalid comment status!' );
		}
	}

}


/*
 * $Log: _comment.class.php,v $
 */
?>