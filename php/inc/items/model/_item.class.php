<?php
/**
 * This file implements the Item class.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _item.class.php 4096 2013-06-28 10:39:15Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Includes:
 */
load_funcs( 'items/model/_item.funcs.php');
load_class( 'slugs/model/_slug.class.php', 'Slug' );
load_class( 'links/model/_linkowner.class.php', 'LinkOwner' );
load_class( 'links/model/_linkitem.class.php', 'LinkItem' );

/**
 * Item Class
 *
 * @package evocore
 */
class Item extends ItemLight
{
	/**
	 * Creation date (timestamp)
	 * @var integer
	 */
	var $datecreated;

	/**
	 * The User who has created the Item (lazy-filled).
	 * @see Item::get_creator_User()
	 * @see Item::set_creator_User()
	 * @var User
	 * @access protected
	 */
	var $creator_User;


	/**
	 * The User who has edited the Item last time (lazy-filled).
	 * @see Item::get_lastedit_User()
	 * @var User
	 * @access protected
	 */
	var $lastedit_User;


	/**
	 * ID of the user who has edited the Item last time
	 * @var integer
	 */
	var $lastedit_user_ID;

	/**
	 * Date when comments or links were added/edited/deleted for this Item last time (timestamp)
	 * @see Item::update_last_touched_date()
	 * @var integer
	 */
	var $last_touched_ts;


	/**
	 * The latest Comment on this Item (lazy-filled).
	 * @see Item::get_latest_Comment()
	 * @var Comment
	 * @access protected
	 */
	var $latest_Comment;


	/**
	 * @deprecated by {@link $creator_User}
	 * @var User
	 */
	var $Author;


	/**
	 * ID of the user that created the item
	 * @var integer
	 */
	var $creator_user_ID;


	/**
	 * Login of the user that created the item (lazy-filled)
	 * @var string
	 */
	var $creator_user_login;


	/**
	 * The assigned User to the item.
	 * Can be NULL
	 * @see Item::get_assigned_User()
	 * @see Item::assign_to()
	 *
	 * @var User
	 * @access protected
	 */
	var $assigned_User;

	/**
	 * ID of the user that created the item
	 * Can be NULL
	 *
	 * @var integer
	 */
	var $assigned_user_ID;

	/**
	 * The visibility status of the item.
	 *
	 * 'published', 'community', 'deprecated', 'protected', 'private', 'review' or 'draft'
	 *
	 * @var string
	 */
	var $status;
	/**
	 * Locale code for the Item content.
	 *
	 * Examples: en-US, zh-CN-utf-8
	 *
	 * @var string
	 */
	var $locale;

	var $content;

	var $titletag;

	/**
	 * Lazy filled, use split_page()
	 */
	var $content_pages = NULL;


	var $wordcount;
	/**
	 * The list of renderers, imploded by '.'.
	 * @var string
	 * @access protected
	 */
	var $renderers;
	/**
	 * Comments status
	 *
	 * "open", "disabled" or "closed
	 *
	 * @var string
	 */
	var $comment_status;

	var $pst_ID;
	var $datedeadline = '';
	var $priority;

	/**
	 * @var float
	 */
	var $order;
	/**
	 * @var boolean
	 */
	var $featured;

	/**
	 * Have post processing notifications been handled?
	 * @var string
	 */
	var $notifications_status;
	/**
	 * Which cron task is responsible for handling notifications?
	 * @var integer
	 */
	var $notifications_ctsk_ID;

	/**
	 * array of IDs or NULL if we don't know...
	 *
	 * @var array
	 */
	var $extra_cat_IDs = NULL;

	/**
	 * Array of tags (strings)
	 *
	 * Lazy loaded.
	 * @see Item::get_tags()
	 * @access protected
	 * @var array
	 */
	var $tags = NULL;

	/**
	 * Has the publish date been explicitly set?
 	 *
	 * @var integer
	 */
	var $dateset = 1;

	var $priorities;

	/**
	 * @access protected
	 * @see Item::get_excerpt()
	 * @var string
	 */
	var $excerpt;

	/**
	 * Is the excerpt autogenerated?
	 * @access protected
	 * @var boolean
	 */
	var $excerpt_autogenerated = true;

	/**
	 * Location IDs
	 * @var integer
	 */
	var $ctry_ID = NULL;
	var $rgn_ID = NULL;
	var $subrg_ID = NULL;
	var $city_ID = NULL;

	/**
	 * Additional settings for the items.  lazy filled.
 	 *
	 * @see Item::get_setting()
	 * @see Item::set_setting()
	 * @see Item::load_ItemSettings()
	 * Any non vital params should go into there.
	 *
	 * @var ItemSettings
	 */
	var $ItemSettings;

	/**
	 * Constructor
	 *
	 * @param object table Database row
	 * @param string
	 * @param string
	 * @param string
	 * @param string for derived classes
	 * @param string datetime field name
	 * @param string datetime field name
	 * @param string User ID field name
	 * @param string User ID field name
	 */
	function Item( $db_row = NULL, $dbtable = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'Item',
	               $datecreated_field = 'datecreated', $datemodified_field = 'datemodified',
	               $creator_field = 'creator_user_ID', $lasteditor_field = 'lastedit_user_ID' )
	{
		global $localtimenow, $default_locale, $current_User;

		$this->priorities = array(
				1 => /* TRANS: Priority name */ T_('1 - Highest'),
				2 => /* TRANS: Priority name */ T_('2 - High'),
				3 => /* TRANS: Priority name */ T_('3 - Medium'),
				4 => /* TRANS: Priority name */ T_('4 - Low'),
				5 => /* TRANS: Priority name */ T_('5 - Lowest'),
			);

		// Call parent constructor:
		parent::ItemLight( $db_row, $dbtable, $dbprefix, $dbIDname, $objtype,
	               $datecreated_field, $datemodified_field,
	               $creator_field, $lasteditor_field );

		if( is_null($db_row) )
		{ // New item:
			if( isset($current_User) )
			{ // use current user as default, if available (which won't be the case during install)
				$this->creator_user_login = $current_User->login;
				$this->set_creator_User( $current_User );
			}
			$this->set( 'dateset', 0 );	// Date not explicitly set yet
			$this->set( 'notifications_status', 'noreq' );
			// Set the renderer list to 'default' will trigger all 'opt-out' renderers:
			$this->set( 'renderers', array('default') );
			// we prolluy don't need this: $this->set( 'status', 'published' );
			$this->set( 'locale', $default_locale );
			$this->set( 'priority', 3 );
			$this->set( 'ptyp_ID', 1 /* Post */ );
		}
		else
		{
			$this->datecreated = $db_row->post_datecreated; 					// When Item was created in the system
			$this->last_touched_ts = $db_row->post_last_touched_ts;		// When Item received last visible change (edit, comment, etc.)
			$this->creator_user_ID = $db_row->post_creator_user_ID; 	// Needed for history display
			$this->lastedit_user_ID = $db_row->post_lastedit_user_ID; // Needed for history display
			$this->assigned_user_ID = $db_row->post_assigned_user_ID;
			$this->dateset = $db_row->post_dateset;
			$this->status = $db_row->post_status;
			$this->content = $db_row->post_content;
			$this->titletag = $db_row->post_titletag;
			$this->pst_ID = $db_row->post_pst_ID;
			$this->datedeadline = $db_row->post_datedeadline;
			$this->priority = $db_row->post_priority;
			$this->locale = $db_row->post_locale;
			$this->wordcount = $db_row->post_wordcount;
			$this->notifications_status = $db_row->post_notifications_status;
			$this->notifications_ctsk_ID = $db_row->post_notifications_ctsk_ID;
			$this->comment_status = $db_row->post_comment_status;			// Comments status
			$this->order = $db_row->post_order;
			$this->featured = $db_row->post_featured;

			// echo 'renderers=', $db_row->post_renderers;
			$this->renderers = $db_row->post_renderers;

			$this->views = $db_row->post_views;

			$this->excerpt = $db_row->post_excerpt;
			$this->excerpt_autogenerated = $db_row->post_excerpt_autogenerated;

			// Location

			if ( ! empty ( $db_row->post_ctry_ID ) )
			{
				$this->ctry_ID = $db_row->post_ctry_ID;
			}

			if ( ! empty ( $db_row->post_rgn_ID ) )
			{
				$this->rgn_ID = $db_row->post_rgn_ID;
			}

			if ( ! empty ( $db_row->post_subrg_ID ) )
			{
				$this->subrg_ID = $db_row->post_subrg_ID;
			}

			if ( ! empty ( $db_row->post_city_ID ) )
			{
				$this->city_ID = $db_row->post_city_ID;
			}

		}

		modules_call_method( 'constructor_item', array( 'Item' => & $this ) );
	}


	/**
	 * Set creator user
	 *
	 * @param string login
	 */
	function set_creator_by_login( $login )
	{
		$UserCache = & get_UserCache();
		if( ( $creator_User = &$UserCache->get_by_login( $login ) ) !== false )
		{
			$this->set( $this->creator_field, $creator_User->ID );
		}
	}


	/**
	 * @todo use extended dbchange instead of set_param...
	 * @todo Normalize to set_assigned_User!?
	 */
	function assign_to( $user_ID, $dbupdate = true /* BLOAT!? */ )
	{
		// echo 'assigning user #'.$user_ID;
		if( ! empty($user_ID) )
		{
			if( $dbupdate )
			{ // Record ID for DB:
				$this->set_param( 'assigned_user_ID', 'number', $user_ID, true );
			}
			else
			{
				$this->assigned_user_ID = $user_ID;
			}
			$UserCache = & get_UserCache();
			$this->assigned_User = & $UserCache->get_by_ID( $user_ID );
		}
		else
		{
			// fp>> DO NOT set (to null) immediately OR it may KILL the current User object (big problem if it's the Current User)
			unset( $this->assigned_User );
			if( $dbupdate )
			{ // Record ID for DB:
				$this->set_param( 'assigned_user_ID', 'number', NULL, true );
			}
			else
			{
				$this->assigned_User = NULL;
			}
			$this->assigned_user_ID = NULL;
		}

	}


	/**
	 * Template function: display author/creator of item
	 *
	 */
	function author( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'profile_tab'    => 'user',
				'before'         => ' ',
				'after'          => ' ',
				'format'         => 'htmlbody',
				'link_to'        => 'userpage',
				'link_text'      => 'preferredname',
				'link_rel'       => '',
				'link_class'     => '',
				'thumb_size'     => 'crop-top-32x32',
				'thumb_class'    => '',
				'thumb_zoomable' => false,
			), $params );

		// Load User
		$this->get_creator_User();

		$r = $this->creator_User->get_identity_link( $params );

		echo $params['before'].$r.$params['after'];
	}


	/**
	 * Template function: display user who edited the item last time
	 *
	 */
	function lastedit_user( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'profile_tab'    => 'user',
				'before'         => ' ',
				'after'          => ' ',
				'format'         => 'htmlbody',
				'link_to'        => 'userpage',
				'link_text'      => 'preferredname',
				'link_rel'       => '',
				'link_class'     => '',
				'thumb_size'     => 'crop-top-32x32',
				'thumb_class'    => '',
				'thumb_zoomable' => false,
			), $params );

		// Load User
		$this->get_lastedit_User();

		if( $this->lastedit_User )
		{	// Get a link to user profile page
			$r = $this->lastedit_User->get_identity_link( $params );
		}
		else
		{	// User was deleted
			$r = T_('(deleted user)');
		}

		echo $params['before'].$r.$params['after'];
	}


	/**
	 * Load data from Request form fields.
	 *
	 * This requires the blog (e.g. {@link $blog_ID} or {@link $main_cat_ID} to be set).
	 *
	 * @param boolean true if we are returning to edit mode (new, switchtab...)
	 * @return boolean true if loaded data seems valid.
	 */
	function load_from_Request( $editing = false, $creating = false )
	{
		global $default_locale, $current_User, $localtimenow;
		global $posttypes_reserved_IDs, $item_typ_ID;

		if( param( 'post_locale', 'string', NULL ) !== NULL )
		{
			$this->set_from_Request( 'locale' );
		}

		if( param( 'post_type', 'string', NULL ) !== NULL )
		{ // Set type ID from request type code
			$this->set( 'ptyp_ID', get_item_type_ID( get_param( 'post_type' ) ) );
		}
		elseif( param( 'item_typ_ID', 'integer', NULL ) !== NULL )
		{
			$this->set_from_Request( 'ptyp_ID', 'item_typ_ID' );

			if ( in_array( $item_typ_ID, $posttypes_reserved_IDs ) )
			{
				param_error( 'item_typ_ID', T_( 'This post type is reserved and cannot be used. Please choose another one.' ), '' );
			}
		}

		if( param( 'post_url', 'string', NULL ) !== NULL )
		{
			param_check_url( 'post_url', 'posting', '' );
			$this->set_from_Request( 'url' );
		}
		// Note: post_url is not part of the simple form, so this message can be a little bit awkward there
		if( $this->status == 'redirected' && empty($this->url) )
		{
			param_error( 'post_url', T_('If you want to redirect this post, you must specify an URL! (Expert mode)') );
		}

		$this->load_Blog();
		if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $this->Blog->ID ) )
		{
			$this->set( 'dateset', param( 'item_dateset', 'integer', 0 ) );

			if( $editing || $this->dateset == 1 )
			{ // We can use user date:
				if( param_date( 'item_issue_date', T_('Please enter a valid issue date.'), true )
					&& param_time( 'item_issue_time' ) )
				{ // only set it, if a (valid) date and time was given:
					$this->set( 'issue_date', form_date( get_param( 'item_issue_date' ), get_param( 'item_issue_time' ) ) ); // TODO: cleanup...
				}
			}
			elseif( $this->dateset == 0 )
			{	// Set date to NOW:
				$this->set( 'issue_date', date('Y-m-d H:i:s', $localtimenow) );
			}
		}

		if( param( 'post_urltitle', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'urltitle' );
		}

		if( param( 'titletag', 'string', NULL ) !== NULL ) {
			$this->set_from_Request( 'titletag', 'titletag' );
		}

		if( param( 'metadesc', 'string', NULL ) !== NULL ) {
			$this->set_setting( 'post_metadesc', get_param( 'metadesc' ) );
		}

		if( param( 'custom_headers', 'string', NULL ) !== NULL ) {
			$this->set_setting( 'post_custom_headers', get_param( 'custom_headers' ) );
		}

		if( param( 'item_tags', 'string', NULL ) !== NULL ) {
			$this->set_tags_from_string( get_param('item_tags') );
			// pre_dump( $this->tags );
		}

		// Workflow stuff:
		param( 'item_st_ID', 'integer', NULL );
		$this->set_from_Request( 'pst_ID', 'item_st_ID', true );

		param( 'item_assigned_user_ID', 'integer', NULL );
		$this->assign_to( get_param('item_assigned_user_ID') );

		param( 'item_priority', 'integer', NULL );
		$this->set_from_Request( 'priority', 'item_priority', true );

		$this->set( 'featured', param( 'item_featured', 'integer', 0 ), false );

		$this->set_setting( 'hide_teaser', param( 'item_hideteaser', 'integer', 0 ) );

		// Expiry delay
		$expiry_delay = param_duration( 'expiry_delay' );
		if( empty( $expiry_delay ) )
		{ // Check if we have 'expiry_delay' param set as string from simple or mass form
			$expiry_delay = param( 'expiry_delay', 'string', NULL );
		}
		$this->set_setting( 'post_expiry_delay', $expiry_delay, true );

		param( 'item_order', 'double', NULL );
		$this->set_from_Request( 'order', 'item_order', true );

		$this->creator_user_login = param( 'item_owner_login', 'string', NULL );

		if( $current_User->check_perm( 'users', 'edit' ) && param( 'item_owner_login_displayed', 'string', NULL ) !== NULL )
		{   // only admins can change this..
			if( param_check_not_empty( 'item_owner_login', T_('Please enter valid owner login.') ) && param_check_login( 'item_owner_login', true ) )
			{
				$this->set_creator_by_login( $this->creator_user_login );
			}
		}

		if( $this->Blog->get_setting( 'show_location_coordinates' ) )
		{ // location coordinates are enabled, save map settings
			param( 'item_latitude', 'double', NULL ); // get par value
			$this->set_setting( 'latitude', get_param( 'item_latitude' ), true );
			param( 'item_longitude', 'double', NULL ); // get par value
			$this->set_setting( 'longitude', get_param( 'item_longitude' ), true );
			param( 'google_map_zoom', 'integer', NULL ); // get par value
			$this->set_setting( 'map_zoom', get_param( 'google_map_zoom' ), true );
			param( 'google_map_type', 'string', NULL ); // get par value
			$this->set_setting( 'map_type', get_param( 'google_map_type' ), true );
		}

		// CUSTOM FIELDS
		foreach( array( 'double', 'varchar' ) as $type )
		{
			$field_count = $this->Blog->get_setting( 'count_custom_'.$type );
			for( $i = 1 ; $i <= $field_count; $i++ )
			{ // update each custom field
				$field_guid = $this->Blog->get_setting( 'custom_'.$type.$i );
				$param_name = 'item_'.$type.'_'.$field_guid;
				if( isset_param( $param_name ) )
				{ // param is set
					$param_type = ( $type == 'varchar' ) ? 'string' : $type;
					param( $param_name, $param_type, NULL ); // get par value
					$custom_field_make_null = $type != 'double'; // store '0' values in DB for numeric fields
					$this->set_setting( 'custom_'.$type.'_'.$field_guid, get_param( $param_name ), $custom_field_make_null );
				}
			}
		}

		if( param_date( 'item_deadline', T_('Please enter a valid deadline.'), false, NULL ) !== NULL ) {
			$this->set_from_Request( 'datedeadline', 'item_deadline', true );
		}

		// Save status of "Allow comments for this item" (only if comments are allowed in this blog, and disable_comments_bypost is enabled):
		if( ( $this->Blog->get_setting( 'allow_comments' ) != 'never' ) && ( $this->Blog->get_setting( 'disable_comments_bypost' ) ) )
		{
			$post_comment_status = param( 'post_comment_status', 'string', 'open' );
			if( !empty( $post_comment_status ) )
			{ // 'open' or 'closed' or ...
				$this->set_from_Request( 'comment_status' );
			}
		}

		modules_call_method( 'update_item_settings', array( 'edited_Item' => $this ) );

		if( param( 'renderers_displayed', 'integer', 0 ) )
		{ // use "renderers" value only if it has been displayed (may be empty)
			global $Plugins;
			$renderers = $Plugins->validate_renderer_list( param( 'renderers', 'array/string', array() ), array( 'Item' => & $this ) );
			$this->set( 'renderers', $renderers );
		}
		else
		{
			$renderers = $this->get_renderers_validated();
		}

		if( $this->Blog->get_setting( 'allow_html_post' ) )
		{	// HTML is allowed for this post
			$text_format = 'html';
		}
		else
		{	// HTML is disallowed for this post
			$text_format = 'htmlspecialchars';
		}

		if( param( 'content', $text_format, NULL ) !== NULL )
		{
			// Never allow html content on post titles
			param( 'post_title', 'htmlspecialchars', NULL );

			// Do some optional filtering on the content
			// Typically stuff that will help the content to validate
			// Useful for code display.
			// Will probably be used for validation also.
			$Plugins_admin = & get_Plugins_admin();
			$params = array( 'object_type' => 'Item', 'object_Blog' => & $this->Blog );
			$Plugins_admin->filter_contents( $GLOBALS['post_title'] /* by ref */, $GLOBALS['content'] /* by ref */, $renderers, $params /* by ref */ );


			// Title handling:
			$require_title = $this->Blog->get_setting('require_title');

			if( ( ! $editing || $creating ) && $require_title == 'required' ) // creating is important, when the action is create_edit
			{
				param_check_not_empty( 'post_title', T_('Please provide a title.'), '' );
			}

			// Format raw HTML input to cleaned up and validated HTML:
			param_check_html( 'post_title', T_('Invalid title.'), '' );
			$this->set( 'title', get_param( 'post_title' ) );

			param_check_html( 'content', T_('Invalid content.') );
			$this->set( 'content', get_param( 'content' ) );
		}

		// Excerpt, must come after content (to handle excerpt_autogenerated)
		if( param( 'post_excerpt', 'text', NULL ) !== NULL )
		{
			$this->set( 'excerpt_autogenerated', 0 ); // Set this to the '0' for saving a field 'excerpt' from a request
			$this->set_from_Request( 'excerpt' );
		}

		// Locations
		load_funcs( 'regional/model/_regional.funcs.php' );
		if( $this->Blog->country_visible() )
		{ // Save country
			$country_ID = param( 'item_ctry_ID', 'integer', 0 );
			$country_is_required = $this->Blog->get_setting( 'location_country' ) == 'required'
					&& countries_exist()
					&& ! $this->is_special();
			param_check_number( 'item_ctry_ID', T_('Please select a country'), $country_is_required );
			$this->set_from_Request( 'ctry_ID', 'item_ctry_ID', true );
		}

		if( $this->Blog->region_visible() )
		{ // Save region
			$region_ID = param( 'item_rgn_ID', 'integer', 0 );
			$region_is_required = $this->Blog->get_setting( 'location_region' ) == 'required'
					&& regions_exist( $country_ID )
					&& ! $this->is_special();
			param_check_number( 'item_rgn_ID', T_('Please select a region'), $region_is_required );
			$this->set_from_Request( 'rgn_ID', 'item_rgn_ID', true );
		}

		if( $this->Blog->subregion_visible() )
		{ // Save subregion
			$subregion_ID = param( 'item_subrg_ID', 'integer', 0 );
			$subregion_is_required = $this->Blog->get_setting( 'location_subregion' ) == 'required'
					&& subregions_exist( $region_ID )
					&& ! $this->is_special();
			param_check_number( 'item_subrg_ID', T_('Please select a sub-region'), $subregion_is_required );
			$this->set_from_Request( 'subrg_ID', 'item_subrg_ID', true );
		}

		if( $this->Blog->city_visible() )
		{ // Save city
			param( 'item_city_ID', 'integer', 0 );
			$city_is_required = $this->Blog->get_setting( 'location_city' ) == 'required'
					&& cities_exist( $country_ID, $region_ID, $subregion_ID )
					&& ! $this->is_special();
			param_check_number( 'item_city_ID', T_('Please select a city'), $city_is_required );
			$this->set_from_Request( 'city_ID', 'item_city_ID', true );
		}

		return ! param_errors_detected();
	}


	/**
	 * Template function: display anchor for permalinks to refer to.
	 */
	function anchor()
	{
		global $Settings;

		echo '<a id="'.$this->get_anchor_id().'"></a>';
	}


	/**
	 * @return string
	 */
	function get_anchor_id()
	{
		// In case you have old cafelog permalinks, uncomment the following line:
		// return preg_replace( '/[^a-zA-Z0-9_\.-]/', '_', $this->title );

		return 'item_'.$this->ID;
	}


	/**
	 * Template tag
	 */
	function anchor_id()
	{
		echo $this->get_anchor_id();
	}


	/**
	 * Template function: display assignee of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function assigned_to( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( $this->get_assigned_User() )
		{
			echo $before;
			$this->assigned_User->preferred_name( $format );
			echo $after;
		}
	}


	/**
	 * Get list of assigned user options
	 *
	 * @uses UserCache::get_blog_member_option_list()
	 * @return string HTML select options list
	 */
	function get_assigned_user_options()
	{
		$UserCache = & get_UserCache();
		return $UserCache->get_blog_member_option_list( $this->get_blog_ID(), $this->assigned_user_ID,
							true,	($this->ID != 0) /* if this Item is already serialized we'll load the default anyway */ );
	}


	/**
	 * Check if user can see comments on this post, which he cannot if they
	 * are disabled for the Item or never allowed for the blog.
	 *
	 * @param boolean true will display why user can't see comments
	 * @return boolean
	 */
	function can_see_comments( $display = false )
	{
		global $Settings;

		$this->load_Blog();
		if( $this->Blog->get_setting( 'disable_comments_bypost' ) && ( $this->comment_status == 'disabled' ) )
		{ // Comments are disabled on this post
			return false;
		}

		if( $this->check_blog_settings( 'allow_view_comments' ) )
		{ // User is allowed to see comments
			return true;
		}

		if( !$display )
		{
			return false;
		}

		$number_of_comments = $this->get_number_of_comments( 'published' );
		$allow_view_comments = $this->Blog->get_setting( 'allow_view_comments' );
		$user_can_be_validated = check_user_status( 'can_be_validated' );

		if( ( $allow_view_comments != 'any' ) && ( $user_can_be_validated ) )
		{ // change allow view comments to activated, because user is logged in but the account is not activated, and anomnymous users can't see comments
			$allow_view_comments = 'active_users';
		}

		// Set display text
		switch( $allow_view_comments )
		{
			case 'active_users':
				// users must activate their accounts before they can see the comments
				if( $number_of_comments == 0 )
				{
					$display_text = T_( 'You must activate your account to see the comments.' );
				}
				elseif ( $number_of_comments == 1 )
				{
					$display_text = T_( 'There is <b>one comment</b> on this post but you must activate your account to see the comments.' );
				}
				else
				{
					$display_text = sprintf( T_( 'There are <b>%s comments</b> on this post but you must activate your account to see the comments.' ), $number_of_comments );
				}
				break;

			case 'registered':
				// only registered users can see this post's comments
				if( $number_of_comments == 0 )
				{
					$display_text = T_( 'You must be logged in to see the comments.' );
				}
				elseif ( $number_of_comments == 1 )
				{
					$display_text = T_( 'There is <b>one comment</b> on this post but you must be logged in to see the comments.' );
				}
				else
				{
					$display_text = sprintf( T_( 'There are <b>%s comments</b> on this post but you must be logged in to see the comments.' ), $number_of_comments );
				}
				break;

			case 'member':
				// only members can see this post's comments
				if( $number_of_comments == 0 )
				{
					$display_text = T_( 'You must be a member of this blog to see the comments.' );
				}
				elseif ( $number_of_comments == 1 )
				{
					$display_text = T_( 'There is one comment on this post but you must be a member of this blog to see the comments.' );
				}
				else
				{
					$display_text = sprintf( T_( 'There are %s comments on this post but you must be a member of this blog to see the comments.' ), $number_of_comments );
				}
				break;

			default:
				// any is already handled, moderators shouldn't get any message
				return false;
		}

		echo '<div class="comment_posting_disabled_msg">';

		if( !is_logged_in() )
		{ // user is not logged in at all
			$redirect_to = $this->get_permanent_url().'#comments';
			$login_link = '<a href="'.get_login_url( 'cannot see comments', $redirect_to ).'">'.T_( 'Log in now!' ).'</a>';
			echo '<p>'.$display_text.' '.$login_link.'</p>';
			if( $Settings->get( 'newusers_canregister' ) )
			{ // needs to display register link
				echo '<p>'.sprintf( T_( 'If you have no account yet, you can <a href="%s">register now</a>...<br />(It only takes a few seconds!)' ),
							get_user_register_url( $redirect_to, 'reg to see comments' ) ).'</p>';
			}
		}
		elseif( $user_can_be_validated )
		{ // user is logged in but not activated
			$activateinfo_link = '<a href="'.get_activate_info_url( $this->get_permanent_url().'#comments' ).'">'.T_( 'More info &raquo;' ).'</a>';
			echo '<p>'.$display_text.' '.$activateinfo_link.'</p>';
		}
		else
		{ // user is activated, but not allowed to view comments
			echo $display_text;
		}

		echo '</div>';

		return false;
	}


	/**
	 * Template function: Check if user can leave comment on this post or display error
	 *
	 * @param string|NULL string to display before any error message; NULL to not display anything, but just return boolean
	 * @param string string to display after any error message
	 * @param string error message for non published posts, '#' for default
	 * @param string error message for closed comments posts, '#' for default
	 * @param string section title
	 * @param array Skin params
	 * @return boolean true if user can post, false if s/he cannot
	 */
	function can_comment( $before_error = '<p><em>', $after_error = '</em></p>', $non_published_msg = '#', $closed_msg = '#', $section_title = '', $params = array() )
	{
		global $current_User;

		$display = ( ! is_null($before_error) );

		if( $display )
		{ // display a comment form section even if comment form won't be displayed, "add new comment" links should point to this section
			echo '<a id="form_p'.$this->ID.'"></a>';
		}

		if( $this->check_blog_settings( 'allow_comments' ) )
		{
			if( $this->Blog->get_setting( 'disable_comments_bypost' ) && ( $this->comment_status == 'disabled' ) )
			{ // Comments are disabled on this post
				return false;
			}

			if( $this->comment_status == 'closed' || $this->is_locked() )
			{ // Comments are closed on this post

				if( $display)
				{
					if( $closed_msg == '#' )
						$closed_msg = T_( 'Comments are closed for this post.' );

					echo $before_error;
					echo $closed_msg;
					echo $after_error;
				}

				return false;
			}

			if( ($this->status == 'draft') || ($this->status == 'deprecated' ) || ($this->status == 'redirected' ) )
			{ // Post is not published

				if( $display )
				{
					if( $non_published_msg == '#' )
						$non_published_msg = T_( 'This post is not published. You cannot leave comments.' );

					echo $before_error;
					echo $non_published_msg;
					echo $after_error;
				}

				return false;
			}

			if( is_logged_in() && ( $this->Blog->get( 'advanced_perms' ) ) && !$current_User->check_perm( 'blog_comment_statuses', 'create', false, $this->Blog->ID ) )
			{ // User doesn't have permission to create comments and advanced perms are enabled
				if( $display )
				{
					echo $before_error;
					echo T_('You don\'t have permission to reply on this post.');
					echo $after_error;
				}
				return false;
			}
			return true; // OK, user can comment!
		}

		if( ( $this->Blog->get_setting( 'allow_comments' ) != 'never' ) && $display )
		{
			if( $this->comment_status == 'closed' || $this->comment_status == 'disabled' )
			{	// Don't display the disabled comment form because we cannot create the comments for this post
				return false;
			}
			echo $section_title;
			// set item_url for redirect after login, if login required
			$item_url = $this->get_permanent_url().'#form_p'.$this->ID;
			// display disabled comment form
			echo_disabled_comments( $this->Blog->get_setting( 'allow_comments' ), $item_url, $params );
		}

		// Current user not allowed to comment in this blog
		return false;
	}


	/**
	 * Check if current user is allowed for several action in this post's blog
	 *
	 * @private function
	 *
	 * @param string blog settings name. Param value can be 'allow_comments', 'allow_attachments','allow_rating_items'
	 * @return boolean  true if user is allowed for the corresponding action
	 */
	function check_blog_settings( $settings_name )
	{
		global $current_User;

		$this->load_Blog();

		switch( $this->Blog->get_setting( $settings_name ) )
		{
			case 'never':
				return false;
			case 'any':
				return true;
			case 'registered':
				return is_logged_in( false );
			case 'member':
				return (is_logged_in( false ) && $current_User->check_perm( 'blog_ismember', 'view', false, $this->get_blog_ID() ) );
			case 'moderator':
				return (is_logged_in( false ) && $current_User->check_perm( 'blog_comments', 'edit', false, $this->get_blog_ID() ) );
			default:
				debug_die( 'Invalid blog '.$settings_name.' settings!' );
		}

		return false;
	}


	/**
	 * Template function: Check if user can attach files to this post comments
	 *
	 * @return boolean true if user can attach files to this post comments, false if s/he cannot
	 */
	function can_attach()
	{
		global $Settings;

		$attachments_quota_is_full = false;
		if( is_logged_in() )
		{	// We can check the attachments quota only for registered users
			$this->load_Blog();
			$max_attachments = (int)$this->Blog->get_setting( 'max_attachments' );
			if( $max_attachments > 0 )
			{	// Check attachments quota only when Blog setting "Max # of attachments" is defined
				global $DB, $current_User, $Session;

				// Get a number of attachments for current user on this post
				$attachments_count = $this->get_attachments_number();

				// Get the attachments from preview comment
				global $checked_attachments;
				if( !empty( $checked_attachments ) )
				{	// Calculate also the attachments in the PREVIEW mode
					$attachments_count += count( explode( ',', $checked_attachments ) );
				}

				if( $attachments_count >= $max_attachments )
				{	// Current user already has max number of attachments on this post
					$attachments_quota_is_full = true;
				}
			}
		}

		return !$attachments_quota_is_full && $this->check_blog_settings( 'allow_attachments' ) && $Settings->get( 'upload_enabled' );
	}


	/**
	 * Get a number of attachments on this post
	 *
	 * @param object User
	 * @return integer Number of attachments
	 */
	function get_attachments_number( $User = NULL )
	{
		global $DB, $cache_item_attachments_number;

		if( is_null( $User ) )
		{	// Use current user by default
			global $current_User;
			$User = $current_User;
		}

		if( !isset( $cache_item_attachments_number ) )
		{	// Init cache variable at first time
			$cache_item_attachments_number = array();
		}

		if( isset( $cache_item_attachments_number[$User->ID] ) )
		{	// Get a number of attachments from cache variable
			return $cache_item_attachments_number[$User->ID];
		}

		// Get a number of attachments from DB
		$SQL = new SQL();
		$SQL->SELECT( 'COUNT( link_ID )' );
		$SQL->FROM( 'T_links' );
		$SQL->FROM_add( 'INNER JOIN T_comments ON comment_ID = link_cmt_ID' );
		$SQL->WHERE( 'link_creator_user_ID = '.$DB->quote( $User->ID ) );
		$SQL->WHERE_and( 'comment_post_ID = '.$DB->quote( $this->ID ) );
		$cache_item_attachments_number[$User->ID] = (int)$DB->get_var( $SQL->get() );

		return $cache_item_attachments_number[$User->ID];
	}


	/**
	 * Get how much files user can attach on this post yet
	 *
	 * @param object User
	 * @return integer|string Number of files which current user can attach to this post | 'unlimit'
	 */
	function get_attachments_limit( $User = NULL )
	{
		if( is_logged_in() )
		{	// We can check the attachments quota only for registered users
			$this->load_Blog();
			$max_attachments = (int)$this->Blog->get_setting( 'max_attachments' );
			if( $max_attachments > 0 )
			{	// Get a limit only when Blog setting "Max # of attachments" is defined
				return $max_attachments - $this->get_attachments_number( $User );
			}
		}

		return 'unlimit';
	}


	/**
	 * Template function: Check if user can rate this post
	 *
	 * @return boolean true if user can post, false if s/he cannot
	 */
	function can_rate()
	{
		return $this->check_blog_settings( 'allow_rating_items' );
	}


	/**
	 * Get the prerendered content. If it has not been generated yet, it will.
	 *
	 * NOTE: This calls {@link Item::dbupdate()}, if renderers get changed (from Plugin hook).
	 *       (not for preview though)
	 *
	 * @param string Format, see {@link format_to_output()}.
	 *        Only "htmlbody", "entityencoded", "xml" and "text" get cached.
	 * @return string
	 */
	function get_prerendered_content( $format )
	{
		global $Plugins;
		global $preview;

		if( $preview )
		{
			$this->update_renderers_from_Plugins();
			$post_renderers = $this->get_renderers_validated();

			// Call RENDERER plugins:
			$r = $this->content;
			$Plugins->render( $r /* by ref */, $post_renderers, $format, array( 'Item' => $this ), 'Render' );

			return $r;
		}


		$r = null;

		$post_renderers = $this->get_renderers_validated();
		$cache_key = $format.'/'.implode('.', $post_renderers); // logic gets used below, for setting cache, too.

		$use_cache = $this->ID && in_array( $format, array('htmlbody', 'entityencoded', 'xml', 'text') );

		// $use_cache = false;

		if( $use_cache )
		{ // the format/item can be cached:
			$ItemPrerenderingCache = & get_ItemPrerenderingCache();

			if( isset($ItemPrerenderingCache[$format][$this->ID][$cache_key]) )
			{ // already in PHP cache.
				$r = $ItemPrerenderingCache[$format][$this->ID][$cache_key];
				// Save memory, typically only accessed once.
				unset($ItemPrerenderingCache[$format][$this->ID][$cache_key]);
			}
			else
			{	// Try loading from DB cache, including all items in MainList/ItemList.
				global $DB;

				if( ! isset($ItemPrerenderingCache[$format]) )
				{ // only do the prefetch loading once.
					$prefetch_IDs = $this->get_prefetch_itemlist_IDs();

					// Load prerendered content for all items in MainList/ItemList.
					// We load the current $format only, since it's most likely that only one gets used.
					$ItemPrerenderingCache[$format] = array();

					$rows = $DB->get_results( "
						SELECT itpr_itm_ID, itpr_format, itpr_renderers, itpr_content_prerendered
							FROM T_items__prerendering
						 WHERE itpr_itm_ID IN (".$DB->quote( $prefetch_IDs ).")
							 AND itpr_format = '".$format."'",
							 OBJECT, 'Preload prerendered item content for MainList/ItemList ('.$format.')' );
					foreach($rows as $row)
					{
						$row_cache_key = $row->itpr_format.'/'.$row->itpr_renderers;

						if( ! isset($ItemPrerenderingCache[$format][$row->itpr_itm_ID]) )
						{ // init list
							$ItemPrerenderingCache[$format][$row->itpr_itm_ID] = array();
						}

						$ItemPrerenderingCache[$format][$row->itpr_itm_ID][$row_cache_key] = $row->itpr_content_prerendered;
					}

					// Set the value for current Item.
					if( isset($ItemPrerenderingCache[$format][$this->ID][$cache_key]) )
					{
						$r = $ItemPrerenderingCache[$format][$this->ID][$cache_key];
						// Save memory, typically only accessed once.
						unset($ItemPrerenderingCache[$format][$this->ID][$cache_key]);
					}
				}
				else
				{ // This item has not been fetched by the initial prefetch query; only get this item.
					// dh> This is quite unlikely to happen, but you never know.
					// This gets not added to ItemPrerenderingCache, since it would only waste
					// memory - an item gets typically only accessed once per page, and even if
					// it would get accessed more often, there is a cache higher in the chain
					// ($this->content_pages).
					$cache = $DB->get_var( "
						SELECT itpr_content_prerendered
							FROM T_items__prerendering
						 WHERE itpr_itm_ID = ".$this->ID."
							 AND itpr_format = '".$format."'
							 AND itpr_renderers = '".implode('.', $post_renderers)."'", 0, 0, 'Check prerendered item content' );
					if( $cache !== NULL ) // may be empty string
					{ // Retrieved from cache:
						// echo ' retrieved from prerendered cache';
						$r = $cache;
					}
				}
			}
		}

		if( ! isset( $r ) )
		{	// Not cached yet:
			global $Debuglog;

			if( $this->update_renderers_from_Plugins() )
			{
				$post_renderers = $this->get_renderers_validated(); // might have changed from call above
				$cache_key = $format.'/'.implode('.', $post_renderers);

				// Save new renderers with item:
				$this->dbupdate();
			}

			// Call RENDERER plugins:
			// pre_dump( $this->content );
			$r = $this->content;
			$Plugins->render( $r /* by ref */, $post_renderers, $format, array( 'Item' => $this ), 'Render' );
			// pre_dump( $r );

			$Debuglog->add( 'Generated pre-rendered content ['.$cache_key.'] for item #'.$this->ID, 'items' );

			if( $use_cache )
			{ // save into DB (using REPLACE INTO because it may have been pre-rendered by another thread since the SELECT above)
				$DB->query( "
					REPLACE INTO T_items__prerendering (itpr_itm_ID, itpr_format, itpr_renderers, itpr_content_prerendered)
					 VALUES ( ".$this->ID.", '".$format."', ".$DB->quote(implode('.', $post_renderers)).', '.$DB->quote($r).' )', 'Cache prerendered item content' );
			}
		}

		return $r;
	}


	/**
	 * Unset any prerendered content for this item (in PHP cache).
	 */
	function delete_prerendered_content()
	{
		global $DB;

		// Delete DB rows.
		$DB->query( 'DELETE FROM T_items__prerendering WHERE itpr_itm_ID = '.$this->ID );

		// Delete cache.
		$ItemPrerenderingCache = & get_ItemPrerenderingCache();
		foreach( array_keys($ItemPrerenderingCache) as $format )
		{
			unset($ItemPrerenderingCache[$format][$this->ID]);
		}

		// Delete derived properties.
		unset($this->content_pages);
	}


	/**
	 * Trigger {@link Plugin::ItemApplyAsRenderer()} event and adjust renderers according
	 * to return value.
	 * @return boolean True if renderers got changed.
	 */
	function update_renderers_from_Plugins()
	{
		global $Plugins;

		$r = false;

		if( !isset($Plugins) )
		{	// This can happen in maintenance modules running with minimal init, during install, or in tests.
			return $r;
		}

		foreach( $Plugins->get_list_by_event('ItemApplyAsRenderer') as $Plugin )
		{
			if( empty($Plugin->code) )
				continue;

			$plugin_r = $Plugin->ItemApplyAsRenderer( $tmp_params = array('Item' => & $this) );

			if( is_bool($plugin_r) )
			{
				if( $plugin_r )
				{
					$r = $this->add_renderer( $Plugin->code ) || $r;
				}
				else
				{
					$r = $this->remove_renderer( $Plugin->code ) || $r;
				}
			}
		}

		return $r;
	}


	/**
	 * Display excerpt of an item.
	 * @param array Associative list of params
	 *   - before
	 *   - after
	 *   - excerpt_before_more
	 *   - excerpt_after_more
	 *   - excerpt_more_text
	 *   - format
	 *   - allow_empty: force generation if excert is empty (Default: false)
	 *   - update_db: update the DB if we generated an excerpt (Default: true)
	 */
	function excerpt( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'              => '<div class="excerpt">',
				'after'               => '</div>',
				'excerpt_before_more' => ' <span class="excerpt_more">',
				'excerpt_after_more'  => '</span>',
				'excerpt_more_text'   => T_('more').' &raquo;',
				'format'              => 'htmlbody',
				'allow_empty'         => false,
				'update_db'           => true,
			), $params );

		$r = $this->get_excerpt2($params);

		if( !empty($r) )
		{
			echo $params['before'];
			echo format_to_output( $this->excerpt, $params['format'] );
			if( !empty( $params['excerpt_more_text'] ) )
			{
				echo $params['excerpt_before_more'];
				echo '<a href="'.$this->get_permanent_url().'">'.$params['excerpt_more_text'].'</a>';
				echo $params['excerpt_after_more'];
			}
			echo $params['after'];
		}
	}


	/**
	 * Get item excerpt.
	 *
	 * @todo fp>blueyed WTF? Same function name as in ItemLight but different params!
	 * fp> NOTE: I think we can't move this code to ItemLight because we can't update the excerpt there since we don't have the post text there
	 *
	 * @param array Associative list of params
	 *   - allow_empty: force generation if excert is empty (Default: false)
	 *   - update_db: update the DB if we generated an excerpt (Default: true)
	 * @return string
	 */
	function get_excerpt2( $params = array() )
	{
		$params += array(
			'allow_empty' => false,
			'update_db' => true,
			);

		if( ! $params['allow_empty'] )
		{	// Make sure excerpt the excerpt is not empty by updating it automatically if needed:
			if( $this->update_excerpt() && $params['update_db'] && $this->ID )
			{	// We have updated... let's also update the DB:
				$this->dbupdate( false );		// Do not auto track modification date.
			}
		}
		return $this->excerpt;
	}


	/**
	 * Make sure, the pages have been obtained (and split up_ from prerendered cache.
	 *
	 * @param string Format, used to retrieve the matching cache; see {@link format_to_output()}
	 */
	function split_pages( $format = 'htmlbody' )
	{
		if( ! isset( $this->content_pages[$format] ) )
		{
			// SPLIT PAGES:
			$this->content_pages[$format] = explode( '<!--nextpage-->', $this->get_prerendered_content($format) );

			// Balance HTML tags
			$this->content_pages[$format] = array_map( 'balance_tags', $this->content_pages[$format] );

			$this->pages = count( $this->content_pages[$format] );
			// echo ' Pages:'.$this->pages;
		}
	}


	/**
	 * Get a specific page to display (from the prerendered cache)
	 *
	 * @param integer Page number, NULL/"#" for current
	 * @param string Format, used to retrieve the matching cache; see {@link format_to_output()}
	 */
	function get_content_page( $page = NULL, $format = 'htmlbody' )
	{
		// Get requested content page:
		if( ! isset($page) || $page === '#' )
		{ // We want to display the page requested by the user:
			$page = isset($GLOBALS['page']) ? $GLOBALS['page'] : 1;
		}

		// Make sure, the pages are split up:
		$this->split_pages( $format );

		if( $page < 1 )
		{
			$page = 1;
		}

		if( $page > $this->pages )
		{
			$page = $this->pages;
		}

		return $this->content_pages[$format][$page-1];
	}


	/**
	 * This is like a teaser with no HTML and a cropping.
	 *
	 * Note: Excerpt and Teaser are TWO DIFFERENT THINGS.
	 *
	 * @param int Max length of excerpt
	 * @return string
	 */
	function get_content_excerpt( $crop_at = 200 )
	{
		// Get teaser for page 1:
		$output = $this->get_content_teaser( 1, false, 'text' );

		return excerpt( $output, $crop_at );
	}


	/**
	 * Display content teaser of item (will stop at "<!-- more -->"
	 */
	function content_teaser( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'disppage'    => '#',
				'stripteaser' => '#',
				'format'      => 'htmlbody',
			), $params );

		$r = $this->get_content_teaser( $params['disppage'], $params['stripteaser'], $params['format'], $params );

		if( !empty($r) )
		{
			echo $params['before'];
			echo $r;
			echo $params['after'];
		}
	}

	/**
	 * Template function: get content teaser of item (will stop at "<!-- more -->")
	 *
	 * @param mixed page number to display specific page, # for url parameter
	 * @param boolean # if you don't want to repeat teaser after more link was pressed and <-- noteaser --> has been found
	 * @param string filename to use to display more
	 * @param array Params
	 * @return string
	 */
	function get_content_teaser( $disppage = '#', $stripteaser = '#', $format = 'htmlbody', $params = array() )
	{
		global $Plugins, $preview, $Debuglog;
		global $more;

		$params = array_merge( $params, array(
				'disppage' => $disppage,
				'format' => $format
			) );

		$view_type = 'full';
		if( $this->has_content_parts($params) )
		{ // This is an extended post (has a more section):
			if( $stripteaser === '#' )
			{
				// If we're in "more" mode and we want to strip the teaser, we'll strip:
				$stripteaser = ( $more && $this->get_setting( 'hide_teaser' ) );
			}

			if( $stripteaser )
			{
				return NULL;
			}
			$view_type = 'teaser';
		}

		$output = array_shift( $this->get_content_parts($params) );

		// Render Inline Images  [image:123:caption]  :
		$params['check_code_block'] = true;
		$output = $this->render_inline_images( $output, $params );

		// Trigger Display plugins FOR THE STUFF THAT WOULD NOT BE PRERENDERED:
		$output = $Plugins->render( $output, $this->get_renderers_validated(), $format, array(
				'Item' => $this,
				'preview' => $preview,
				'dispmore' => ($more != 0),
				'view_type' => $view_type,
			), 'Display' );

		// Character conversions
		$output = format_to_output( $output, $format );

		return $output;
	}


	/**
	 * Get content parts (split by "<!--more-->").
	 * @param array 'disppage', 'format'
	 * @return array Array of content parts
	 */
	function get_content_parts($params)
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'disppage'    => '#',
				'format'      => 'htmlbody',
			), $params );

		$content_page = $this->get_content_page( $params['disppage'], $params['format'] ); // cannot include format_to_output() because of the magic below.. eg '<!--more-->' will get stripped in "xml"
		// pre_dump($content_page);

		$content_parts = explode( '<!--more-->', $content_page );
		// echo ' Parts:'.count($content_parts);

		// Balance HTML tags
		$content_parts = array_map( 'balance_tags', $content_parts );

		return $content_parts;
	}


	/**
	 * DEPRECATED
	 */
	function content()
	{
		// ---------------------- POST CONTENT INCLUDED HERE ----------------------
		skin_include( '_item_content.inc.php', array(
				'image_size'	=>	'fit-400x320',
			) );
		// Note: You can customize the default item feedback by copying the generic
		// /skins/_item_feedback.inc.php file into the current skin folder.
		// -------------------------- END OF POST CONTENT -------------------------
	}


	/**
	 * Display content extension of item (part after "<!-- more -->")
	 */
	function content_extension( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'disppage'    => '#',
				'format'      => 'htmlbody',
				'force_more'  => false,
			), $params );

		$r = $this->get_content_extension( $params['disppage'], $params['force_more'], $params['format'] );

		if( !empty($r) )
		{
			echo $params['before'];
			echo $r;
			echo $params['after'];
		}
	}


	/**
	 * Template function: get content extension of item (part after "<!-- more -->")
	 *
	 * @param mixed page number to display specific page, # for url parameter
	 * @param boolean
	 * @param string filename to use to display more
	 * @return string
	 */
	function get_content_extension( $disppage = '#', $force_more = false, $format = 'htmlbody' )
	{
		global $Plugins, $more, $preview;

		if( ! $more && ! $force_more )
		{	// NOT in more mode:
			return NULL;
		}

		$params = array('disppage' => $disppage, 'format' => $format);
		if( ! $this->has_content_parts($params) )
		{ // This is NOT an extended post
			return NULL;
		}

		$content_parts = $this->get_content_parts($params);

		// Output everything after <!-- more -->
		array_shift($content_parts);
		$output = implode('', $content_parts);

		// Render Inline Images  [image:123:caption]  :
		$params['check_code_block'] = true;
		$output = $this->render_inline_images( $output, $params );

		// Trigger Display plugins FOR THE STUFF THAT WOULD NOT BE PRERENDERED:
		$output = $Plugins->render( $output, $this->get_renderers_validated(), $format, array(
				'Item' => $this,
				'preview' => $preview,
				'dispmore' => true,
				'view_type' => 'extension',
			), 'Display' );

		// Character conversions
		$output = format_to_output( $output, $format );

		return $output;
	}


	/**
	 * Increase view counter
	 *
	 * @todo merge with inc_viewcount
	 */
	function count_view( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'allow_multiple_counts_per_page' => false,
			), $params );


		global $Hit, $preview, $Debuglog, $Settings;

		if( $preview )
		{
			// echo 'PREVIEW';
			return false;
		}

		/*
		 * Check if we want to increment view count, see {@link Hit::is_new_view()}
		 */
		if( ( $Settings->get( 'smart_view_count' ) ) && ( ! $Hit->is_new_view() ) )
		{	// This is a reload
			// echo 'RELOAD';
			return false;
		}

		if( ! $params['allow_multiple_counts_per_page'] )
		{	// Check that we don't increase multiple viewcounts on the same page
			// This make the assumption that the first post in a list is "viewed" and the other are not (necesarily)
			global $view_counts_on_this_page;
			if( $view_counts_on_this_page >= 1 )
			{	// we already had a count on this page
				// echo 'ALREADY HAD A COUNT';
				return false;
			}
			$view_counts_on_this_page++;
		}

		//echo 'COUNTING VIEW';

		// Increment view counter (only if current User is not the item's author)
		return $this->inc_viewcount(); // won't increment if current_User == Author
	}


	/**
	 * Load item custom field value by index
	 *
	 * @param String field index, this is the lowercase value of the trimmed field name ( whitespaces are converted to one '_' character )
	 * @return boolean true on success false if custom field with this index doesn't exist
	 */
	function load_custom_field_value( $field_index )
	{
		if( empty( $this->custom_fields ) )
		{ // load item custom_fields
			$this->custom_fields = get_item_custom_fields();
		}

		if( empty( $this->custom_fields[$field_index] ) )
		{ // there is no such custom field
			return false;
		}

		if( empty( $this->custom_fields[$field_index]['value'] ) )
		{ // get custom item field value from the item setting
			$this->custom_fields[$field_index]['value'] = $this->get_setting( 'custom_'.$this->custom_fields[$field_index]['name'] );
		}
		return true;
	}


	/**
	 * Get item custom field value by index
	 *
	 * @param String field index, see {@link load_custom_field_value()}
	 * @return mixed false if the field doesn't exist Double/String otherwise depending from the custom field type
	 */
	function get_custom_field_value( $field_index )
	{
		if( $this->load_custom_field_value( $field_index ) )
		{
			return $this->custom_fields[$field_index]['value'];
		}
		return false;
	}


	/**
	 * Display custom field
	 */
	function custom( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'        => ' ',
				'after'         => ' ',
				'format'        => 'htmlbody',
				'decimals'      => 2,
				'dec_point'     => '.',
				'thousands_sep' => ',',
			), $params );

		if( empty( $params['field'] ) )
		{
			return;
		}

		// Load custom field by index
		$field_index = $params['field'];
		if( !$this->load_custom_field_value( $field_index ) )
		{ // Custom field with this index doesn't exist
			echo $params['before']
				.'<span class="red">'.sprintf( T_('The custom field %s does not exist!'), $field_index ).'</span>'
				.$params['after'];
			return;
		}

		// Get value and type
		$value = $this->custom_fields[$field_index]['value'];
		$type = $this->custom_fields[$field_index]['type'];

		if( !empty( $params['max'] ) && ( $type == 'double' ) && ( $value == 9999999999 ) )
		{
			echo $params['max'];
		}
		elseif( !empty( $value ) )
		{
			echo $params['before'];
			if( $type == 'double' )
			{
				echo number_format( $value, $params['decimals'], $params['dec_point'], $params['thousands_sep']  );
			}
			else
			{
				echo format_to_output( $value, $params['format'] );
			}
			echo $params['after'];
		}
	}


	/**
	 * Display all custom fields of current Item
	 *
	 * @param array Params
	 */
	function custom_fields( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => '<table class="item_custom_fields">',
				'field_format' => '<tr><th>$title$:</th><td>$value$</td></tr>', // $title$ $value$
				'after'        => '</table>',
			), $params );

		if( empty( $this->custom_fields ) )
		{
			$this->custom_fields = get_item_custom_fields();
		}

		if( count( $this->custom_fields ) == 0 )
		{	// No custom fields
			return;
		}

		$fields_exist = false;

		$html = $params['before'];

		$mask = array( '$title$', '$value$' );
		foreach( $this->custom_fields as $field )
		{
			$custom_field_value = $this->get_setting( 'custom_'.$field['name'] );
			if( !empty( $custom_field_value ) ||
			    ( $field['type'] == 'double' && $custom_field_value == '0' ) )
			{ // Display only the filled field AND also numeric field with '0' value
				$values = array( $field['title'], $custom_field_value );
				$html .= str_replace( $mask, $values, $params['field_format'] );
				$fields_exist = true;
			}
		}

		$html .= $params['after'];

		if( $fields_exist )
		{	// Print out if at least one field is filled for this item
			echo $html;
		}
	}


	/**
	 * Template tag
	 */
	function more_link( $params = array() )
	{
		echo $this->get_more_link( $params );
	}


	/**
	 * Display more link
	 */
	function get_more_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'force_more'  => false,
				'before'      => '<p class="bMore">',
				'after'       => '</p>',
				'link_text'   => '#',		// text to display as the more link
				'anchor_text' => '#',		// text to display as the more anchor (once the more link has been clicked, # defaults to "Follow up:")
				'link_to'     => 'single#anchor',	// target URL for more link, 'single' or 'single#anchor'
				'disppage'    => '#',		// page number to display specific page, # for url parameter
				'format'      => 'htmlbody',
			), $params );

		global $more;

		if( ! $this->has_content_parts($params) )
		{ // This is NOT an extended post:
			return '';
		}

		if( ( $more == 0 ) && ( $params[ 'link_to' ] == false ) )
		{ // Don't display "After more" content
			if( !empty( $params[ 'link_text' ] ) )
			{
				return format_to_output( $params[ 'before'].$params[ 'link_text' ].$params[ 'after'] );
			}
			return '';
		}

		$content_parts = $this->get_content_parts($params);

		if( ! $more && ! $params['force_more'] )
		{	// We're NOT in "more" mode:
			if( $params['link_text'] == '#' )
			{ // TRANS: this is the default text for the extended post "more" link
				$params['link_text'] = T_('Full story').' &raquo;';
				// Dummy in order to keep previous translation in the loop:
				$dummy = T_('Read more');
			}

			switch( $params['link_to'] )
			{
				case 'single':
					$params['link_to'] = $this->get_permanent_url();
					break;

				case 'single#anchor':
					$params['link_to'] = $this->get_permanent_url().'#more'.$this->ID;
					break;
			}

			return format_to_output( $params['before']
						.'<a href="'.$params['link_to'].'">'
						.$params['link_text'].'</a>'
						.$params['after'], $params['format'] );
		}
		elseif( ! $this->get_setting( 'hide_teaser' ) )
		{	// We are in more mode and we're not hiding the teaser:
			// (if we're hiding the teaser we display this as a normal page ie: no anchor)
			if( $params['anchor_text'] == '#' )
			{ // TRANS: this is the default text displayed once the more link has been activated
				$params['anchor_text'] = '<p class="bMore">'.T_('Follow up:').'</p>';
			}

			return format_to_output( '<a id="more'.$this->ID.'" name="more'.$this->ID.'"></a>'
							.$params['anchor_text'], $params['format'] );
		}
	}


	/**
	 * Does the post have different content parts (teaser/extension, divided by "<!--more-->")?
	 * This is also true for posts that have images with "aftermore" position.
	 *
	 * @access public
	 * @return boolean
	 */
	function has_content_parts($params)
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'disppage'    => '#',
				'format'      => 'htmlbody',
			), $params );

		$content_page = $this->get_content_page($params['disppage'], $params['format']);

		return strpos($content_page, '<!--more-->') !== false
			|| $this->get_images( array('restrict_to_image_position'=>'aftermore') );
	}


	/**
	 * Template function: display deadline date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function deadline_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->datedeadline, $useGM);
		else
			echo mysql2date( $format, $this->datedeadline, $useGM);
	}


	/**
	 * Template function: display deadline time (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function deadline_time( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->datedeadline, $useGM );
		else
			echo mysql2date( $format, $this->datedeadline, $useGM );
	}


	/**
	 * Get array of tags.
	 *
	 * Load from DB if necessary, prefetching any other tags from MainList/ItemList.
	 *
	 * @return array
	 */
	function & get_tags()
	{
		global $DB;

		if( ! isset( $this->tags ) )
		{
			$ItemTagsCache = & get_ItemTagsCache();
			if( ! isset($ItemTagsCache[$this->ID]) )
			{
				/* Only try to fetch tags for items that are not yet in
				 * the cache. This will always give at least the ID of
				 * this Item.
				 */
				$prefetch_item_IDs = array_diff( $this->get_prefetch_itemlist_IDs(), array_keys( $ItemTagsCache ) );
				// Assume these items don't have any tags:
				foreach( $prefetch_item_IDs as $item_ID )
				{
					$ItemTagsCache[$item_ID] = array();
				}

				// Now fetch the tags:
				foreach( $DB->get_results('
					SELECT itag_itm_ID, tag_name
						FROM T_items__itemtag INNER JOIN T_items__tag ON itag_tag_ID = tag_ID
					 WHERE itag_itm_ID IN ('.$DB->quote($prefetch_item_IDs).')
					 ORDER BY tag_name', OBJECT, 'Get tags for items' ) as $row )
				{
					$ItemTagsCache[$row->itag_itm_ID][] = $row->tag_name;
				}

				//pre_dump( $ItemTagsCache );
			}

			$this->tags = $ItemTagsCache[$this->ID];
		}

		return $this->tags;
	}


	/**
	 * Get the title for the <title> tag
	 *
	 * If it's not specifically entered, use the regular post title instead
	 */
	function get_titletag()
	{
		if( empty($this->titletag) )
		{
			return $this->title;
		}

		return $this->titletag;
	}

	/**
	 * Get the meta description tag
	 *
	 */
	function get_metadesc()
	{
		return $this->get_setting( 'post_metadesc' );
	}

	/**
	 * Get the meta keyword tag
	 *
	 */
	function get_custom_headers()
	{
		return $this->get_setting( 'post_custom_headers' );
	}


	/**
	 * Split tags by comma or semicolon
	 *
	 * @param string The tags, separated by comma or semicolon
	 */
	function set_tags_from_string( $tags )
	{
		if( $tags === '' )
		{
			$this->tags = array();
			return;
		}
		$this->tags = preg_split( '/\s*[;,]+\s*/', $tags, -1, PREG_SPLIT_NO_EMPTY );
		array_walk( $this->tags, create_function( '& $tag', '$tag = evo_strtolower( $tag );' ) );
		$this->tags = array_unique( $this->tags );
		// pre_dump( $this->tags );
	}


	/**
	 * Template function: Provide link to message form for this Item's author.
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @return boolean true, if a link was displayed; false if there's no email address for the Item's author.
	 */
	function msgform_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'text'        => '#',
				'title'       => '#',
				'class'       => '',
				'format'      => 'htmlbody',
				'form_url'    => '#current_blog#',
			), $params );


		if( $params['form_url'] == '#current_blog#' )
		{	// Get
			global $Blog;
			$params['form_url'] = $Blog->get('msgformurl');
		}

		$this->get_creator_User();
		$redirect_to = url_add_param( $params['form_url'], 'post_id='.$this->ID.'&recipient_id='.$this->creator_User->ID, '&' );
		$params['form_url'] = $this->creator_User->get_msgform_url( url_add_param( $params['form_url'], 'post_id='.$this->ID ), $redirect_to );

		if( empty( $params['form_url'] ) )
		{
			return false;
		}

		if( $params['title'] == '#' )
		{
			if( $this->creator_User->get_msgform_possibility() == 'email' )
			{
				$params['title'] = T_('Send email to post author');
			}
			else
			{
				$params['title'] = T_('Send message to post author');
			}
		}
		if( $params['text'] == '#' ) $params['text'] = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $params['title'] ) );

		echo $params['before'];
		echo '<a href="'.$params['form_url'].'" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) echo ' class="'.$params['class'].'"';
		echo ' rel="nofollow">'.$params['text'].'</a>';
		echo $params['after'];

		return true;
	}


	/**
	 * Template function: Provide link to message form for this Item's assigned User.
	 *
	 * @param string url of the message form
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @return boolean true, if a link was displayed; false if there's no email address for the assigned User.
	 */
	function msgform_link_assigned( $form_url, $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '' )
	{
		if( ! $this->get_assigned_User() || empty($this->assigned_User->email) )
		{ // We have no email for this Author :(
			return false;
		}

		$form_url = url_add_param( $form_url, 'recipient_id='.$this->assigned_User->ID );
		$form_url = url_add_param( $form_url, 'post_id='.$this->ID );

		if( $title == '#' ) $title = T_('Send email to assigned user');
		if( $text == '#' ) $text = get_icon( 'email', 'imgtag', array( 'class' => 'middle', 'title' => $title ) );

		echo $before;
		echo '<a href="'.$form_url.'" title="'.$title.'"';
		if( !empty( $class ) ) echo ' class="'.$class.'"';
		echo ' rel="nofollow">'.$text.'</a>';
		echo $after;

		return true;
	}


	/**
	 * Display a link to pages for multi-page items
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function page_links()
	{
		$num_args = func_num_args();
		$args = func_get_args();

		if( $num_args == 1 && is_array($args[0]) )
		{
			$params = $args[0];
			if( !isset($params['format']) ) $params['format'] = 'htmlbody';
		}
		else
		{	// Deprecated since v5, left for compatibility with old skins
			$params['before']		= isset($args[0]) ? $args[0] : '<p class="right">'.T_('Pages:').' ';
			$params['after']		= isset($args[1]) ? $args[1] : '</p>';
			$params['separator']	= isset($args[2]) ? $args[2] : ' ';
			$params['single']		= isset($args[3]) ? $args[3] : '';
			$params['current_page']	= isset($args[4]) ? $args[4] : '#';
			$params['pagelink']		= isset($args[5]) ? $args[5] : '%d';
			$params['url']			= isset($args[6]) ? $args[6] : '';
			$params['format']		= 'htmlbody';
		}

		echo $this->get_page_links( $params, $params['format'] );
	}


	/**
	 * Get a link to pages for multi-page items
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function get_page_links( $params = array(), $format = 'htmlbody' )
	{
		$params = array_merge( array(
					'before'       => '<p class="right">'.T_('Pages:').' ',
					'after'        => '</p>',
					'separator'    => ' ',
					'single'       => '',
					'current_page' => '#',
					'pagelink'     => '%d',
					'url'          => '',
				), $params );

		// Make sure, the pages are split up:
		$this->split_pages();

		if( $this->pages <= 1 )
		{ // Single page:
			return $params['single'];
		}

		if( $params['separator'] == NULL )
		{ // Don't display pages
			if( $params['before'] !== NULL )
			{
				return format_to_output( $params['before'].$params['after'], $format );
			}
			return;
		}

		if( $params['current_page'] == '#' )
		{
			global $page;
			$params['current_page'] = $page;
		}

		if( empty($params['url']) )
		{
			$params['url'] = $this->get_permanent_url( '', '', '&amp;' );
		}

		$page_links = array();

		for( $i = 1; $i <= $this->pages; $i++ )
		{
			$text = str_replace('%d', $i, $params['pagelink']);

			if( $i != $params['current_page'] )
			{
				if( $i == 1 )
				{	// First page special:
					$page_links[] = '<a href="'.$params['url'].'">'.$text.'</a>';
				}
				else
				{
					$page_links[] = '<a href="'.url_add_param( $params['url'], 'page='.$i ).'">'.$text.'</a>';
				}
			}
			else
			{
				$page_links[] = $text;
			}
		}

		$r = $params['before'].implode( $params['separator'], $page_links ).$params['after'];

		return format_to_output( $r, $format );
	}


	/**
	 * Display the images linked to the current Item
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function images( $params = array(), $format = 'htmlbody' )
	{
		echo $this->get_images( $params, $format );
	}


	/**
	 * Get block of images linked to the current Item
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function get_images( $params = array(), $format = 'htmlbody' )
	{
		global $Plugins;

		$r = '';

		$params = array_merge( array(
				'before'                     => '<div>',
				'before_image'               => '<div class="image_block">',
				'before_image_legend'        => '<div class="image_legend">',
				'after_image_legend'         => '</div>',
				'after_image'                => '</div>',
				'after'                      => '</div>',
				'image_size'                 => 'fit-720x500',
				'image_link_to'              => 'original', // Can be 'orginal' (image) or 'single' (this post)
				'limit'                      => 1000, // Max # of images displayed
				'before_gallery'             => '<div class="bGallery">',
				'after_gallery'              => '</div>',
				'gallery_image_size'         => 'crop-80x80',
				'gallery_image_limit'        => 1000,
				'gallery_colls'              => 5,
				'gallery_order'              => '', // 'ASC', 'DESC', 'RAND'
				'restrict_to_image_position' => 'teaser,aftermore', // 'teaser'|'aftermore'|'inline'
				'data'                       =>  & $r,
			), $params );

		// Get list of attached files
		$LinkOnwer = new LinkItem( $this );
		if( ! $FileList = $LinkOnwer->get_attachment_FileList( $params['limit'], $params['restrict_to_image_position'] ) )
		{
			return '';
		}

		$galleries = array();

		/**
		 * @var File
		 */
		$File = NULL;

		while( $File = & $FileList->get_next() )
		{
			$params['File'] = $File;

			if( ! $File->exists() )
			{
				global $Debuglog;
				$Debuglog->add(sprintf('File linked to item #%d does not exist (%s)!', $this->ID, $File->get_full_path()), array('error', 'files'));
				continue;
			}

			if( $File->is_dir() && $params['gallery_image_limit'] > 0 )
			{	// This is a directory/gallery
				if( ($gallery = $File->get_gallery($params)) != '' )
				{	// Got gallery code
					$galleries[] = $gallery;
				}
				continue;
			}

			if( count($Plugins->trigger_event_first_true('RenderItemAttachment', $params)) != 0 )
			{
				continue;
			}

			if( ! $File->is_image() )
			{	// Skip anything that is not an image
				// fp> TODO: maybe this property should be stored in link_ltype_ID

				//$r .= $this->attachment_files($params);
				continue;
			}

			$link_to = $params['image_link_to']; // Can be 'orginal' (image) or 'single' (this post)
			if( $link_to == 'single' )
			{	// We're linking to the post (displayed on a single post page):
				$link_to = $this->get_permanent_url( $link_to );
				$link_title = $this->title;
				$link_rel = '';
			}
			else
			{	// We're linking to the original image, let lighbox (or clone) quick in:
				$link_title = '#title#';	// This title will be used by lightbox (colorbox for instance)
				$link_rel = 'lightbox[p'.$this->ID.']';	// Make one "gallery" per post.
			}
			// Generate the IMG tag with all the alt, title and desc if available
			$r .= $File->get_tag( $params['before_image'], $params['before_image_legend'], $params['after_image_legend'],
					$params['after_image'], $params['image_size'], $link_to, $link_title, $link_rel, '', '', $this->title );
		}

		if( !empty($r) )
		{
			$r = $params['before'].$r.$params['after'];

			// Character conversions
			$r = format_to_output( $r, $format );
		}

		if( !empty($galleries) )
		{	// Append galleries
			// sam2kb> It's done like that only until we figure out a better way to display galleries.

			/*
			sam2kb> TODO: use shortcode [gallery option1="value1" option2="value2"]
				'columns' - table columns
				'limit' - a number of images,
				'size' - selected/large image size
				'thumbsize' - thumbnails image size
				'order' - files order ASC/DESC/RAND
			*/

			// Character conversions
			$r .= "\n".format_to_output( implode("\n", $galleries), $format );
		}

		return $r;
	}


	/**
	 * Display the attachments/files linked to the current Item
	 *
	 * @param array Array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function files( $params = array(), $format = 'htmlbody' )
	{
		echo $this->get_files( $params, $format );
	}


	/**
	 * Get block of attachments/files linked to the current Item
	 *
	 * @param array Array of params
	 * @param string Output format, see {@link format_to_output()}
	 * @return string HTML
	 */
	function get_files( $params = array(), $format = 'htmlbody' )
	{
		global $Plugins;
		$params = array_merge( array(
				'before' =>              '<div class="item_attachments"><h3>'.T_('Attachments').':</h3><ul class="bFiles">',
				'before_attach' =>         '<li>',
				'before_attach_size' =>    ' <span class="file_size">',
				'after_attach_size' =>     '</span>',
				'after_attach' =>          '</li>',
				'after' =>               '</ul></div>',
			// fp> TODO: we should only have one limit param. Or is there a good reason for having two?
			// sam2kb> It's needed only for flexibility, in the meantime if user attaches 200 files he expects to see all of them in skin, I think.
				'limit_attach' =>        1000, // Max # of files displayed
				'limit' =>               1000,
				'restrict_to_image_position' => '',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
				'data'  =>  '',
			), $params );

		// Get list of attached files
		$LinkOnwer = new LinkItem( $this );
		if( ! $FileList = $LinkOnwer->get_attachment_FileList( $params['limit'], $params['restrict_to_image_position'] ) )
		{
			return '';
		}

		load_funcs('files/model/_file.funcs.php');

		$r = '';
		$i = 0;
		$r_file = array();
		/**
		 * @var File
		 */
		$File = NULL;
		while( ( $File = & $FileList->get_next() ) && $params['limit_attach'] > $i )
		{
			$params['File'] = $File;

			if( count($Plugins->trigger_event_first_true('RenderItemAttachment', $params)) != 0 )
			{
				continue;
			}

			if( $File->is_image() )
			{	// Skip images because these are displayed inline already
				// fp> TODO: have a setting for each linked file to decide whether it should be displayed inline or as an attachment
				continue;
			}
			elseif( $File->is_dir() )
			{	// Skip directories/galleries
				continue;
			}

			if( $File->is_audio() )
			{
				$r_file[$i]  = '<div class="podplayer">';
				$r_file[$i] .= $this->get_player( $File->get_url() );
				$r_file[$i] .= '</div>';
			}
			else
			{
				$r_file[$i] = $params['before_attach'];
				$r_file[$i] .= action_icon( T_('Download file'), 'download', $File->get_url(), '', 5 ).' ';
				$r_file[$i] .= $File->get_view_link( $File->get_name() );
				$r_file[$i] .= $params['before_attach_size'].'('.bytesreadable( $File->get_size() ).')'.$params['after_attach_size'];
				$r_file[$i] .= $params['after_attach'];
			}

			$i++;
		}

		if( !empty($r_file) )
		{
			$r = $params['before'].implode( "\n", $r_file ).$params['after'];

			// Character conversions
			$r = format_to_output( $r, $format );
		}

		return $r;
	}


	/**
	 * @param array Associative array of parameters
	 * @return string Output
	 */
	function attachment_files( & $params/* = array()*/ )
	{
		global $Plugins;

		$r = '';

		$ItemAttachment_plugins = $Plugins->get_list_by_event( 'RenderItemAttachment' );

                $Plugins->trigger_event_first_true('RenderItemAttachment', $params);

		return $r;
	}


	/**
	 * Template function: Displays link to the feed for comments on this item
	 *
	 * @param string Type of feedback to link to (rss2/atom)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param string Link title
	 */
	function feedback_feed_link( $skin = '_rss2', $before = '', $after = '', $title='#' )
	{
		if( ! $this->can_see_comments() )
		{	// Comments disabled
			return;
		}

		$this->load_Blog();

		if( $this->Blog->get_setting( 'comment_feed_content' ) == 'none' )
		{	// Comment feeds disabled
			return;
		}

		if( $title == '#' )
		{
			$title = get_icon( 'feed' ).' '.T_('Comment feed for this post');
		}

		$url = $this->get_feedback_feed_url($skin);

		echo $before;
		echo '<a href="'.$url.'">'.format_to_output($title).'</a>';
		echo $after;
	}


	/**
	 * Get URL to display the post comments in an XML feed.
	 *
	 * @param string
	 */
	function get_feedback_feed_url( $skin_folder_name )
	{
		$this->load_Blog();

		return url_add_param( $this->Blog->get_tempskin_url( $skin_folder_name ), 'disp=comments&amp;p='.$this->ID );
	}


	/**
	 * Get URL to display the post comments.
	 *
	 * @return string
	 */
	function get_feedback_url( $popup = false, $glue = '&amp;' )
	{
		$url = $this->get_single_url( 'auto', '', $glue );
		if( $popup )
		{
			$url = url_add_param( $url, 'disp=feedback-popup', $glue );
		}

		return $url;
	}


	/**
	 * Template function: Displays link to feedback page (under some conditions)
	 *
	 * @param array
	 */
	function feedback_link( $params )
	{
		global $ReqURL;

		if( ! $this->can_see_comments() )
		{	// Comments disabled
			return;
		}

		$params = array_merge( array(
									'type' => 'feedbacks',		// Kind of feedbacks to count
									'status' => 'published',	// Status of feedbacks to count
									'link_before' => '',
									'link_after' => '',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_anchor_zero' => '#',
									'link_anchor_one' => '#',
									'link_anchor_more' => '#',
									'link_title' => '#',
									'use_popup' => false,
									'show_in_single_mode' => false,		// Do we want to show this link even if we are viewing the current post in single view mode
									'url' => '#',
								), $params );

		if( $params['show_in_single_mode'] == false && is_same_url( $this->get_permanent_url('','','&'), $ReqURL ) )
		{	// We are viewing the single page for this pos, which (typically) )contains comments, so we dpn't want to display this link
			return;
		}

		// dh> TODO:	Add plugin hook, where a Pingback plugin could hook and provide "pingbacks"
		switch( $params['type'] )
		{
			case 'feedbacks':
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display feedback / Leave a comment');
				if( $params['link_text_zero'] == '#' ) $params['link_text_zero'] = T_('Send feedback').' &raquo;';
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 feedback').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d feedbacks').' &raquo;';
				break;

			case 'comments':
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display comments / Leave a comment');
				if( $params['link_text_zero'] == '#' )
				{
					if( $this->can_comment( NULL ) ) // NULL, because we do not want to display errors here!
					{
						$params['link_text_zero'] = T_('Leave a comment').' &raquo;';
					}
					else
					{
						$params['link_text_zero'] = '';
					}
				}
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 comment').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d comments').' &raquo;';
				break;

			case 'trackbacks':
				$this->get_Blog();
				if( ! $this->can_receive_pings() )
				{ // Trackbacks not allowed on this blog:
					return;
				}
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display trackbacks / Get trackback address for this post');
				if( $params['link_text_zero'] == '#' ) $params['link_text_zero'] = T_('Send a trackback').' &raquo;';
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 trackback').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d trackbacks').' &raquo;';
				break;

			case 'pingbacks':
				// Obsolete, but left for skin compatibility
				$this->get_Blog();
				if( ! $this->can_receive_pings() )
				{ // Trackbacks not allowed on this blog:
					// We'll consider pingbacks to follow the same restriction
					return;
				}
				if( $params['link_title'] == '#' ) $params['link_title'] = T_('Display pingbacks');
				if( $params['link_text_zero'] == '#' ) $params['link_text_zero'] = T_('No pingback yet').' &raquo;';
				if( $params['link_text_one'] == '#' ) $params['link_text_one'] = T_('1 pingback').' &raquo;';
				if( $params['link_text_more'] == '#' ) $params['link_text_more'] = T_('%d pingbacks').' &raquo;';
				break;

			default:
				debug_die( "Unknown feedback type [{$params['type']}]" );
		}

		$link_text = $this->get_feedback_title( $params['type'], $params['link_text_zero'], $params['link_text_one'], $params['link_text_more'], $params['status'] );

		if( empty($link_text) )
		{	// No link, no display...
			return false;
		}

		if( $params['url'] == '#' )
		{ // We want a link to single post:
			$params['url'] = $this->get_feedback_url();
		}

		// Anchor position
		$number = generic_ctp_number( $this->ID, $params['type'], $params['status'] );

		if( $number == 0 )
			$anchor = $params['link_anchor_zero'];
		elseif( $number == 1 )
			$anchor = $params['link_anchor_one'];
		elseif( $number > 1 )
			$anchor = $params['link_anchor_more'];
		if( $anchor == '#' )
		{
			$anchor = '#'.$params['type'];
		}

		echo $params['link_before'];

		if( !empty( $params['url'] ) )
		{
			echo '<a href="'.$params['url'].$anchor.'" ';	// Position on feedback
			echo 'title="'.$params['link_title'].'"';
			if( $params['use_popup'] )
			{	// Special URL if we can open a popup (i-e if JS is enabled):
				$popup_url = url_add_param( $params['url'], 'disp=feedback-popup' );
				echo ' onclick="return pop_up_window( \''.$popup_url.'\', \'evo_comments\' )"';
			}
			echo '>';
			echo $link_text;
			echo '</a>';
		}
		else
		{
			echo $link_text;
		}

		echo $params['link_after'];
	}


	/**
	 * Return true if there is any feedback of given type.
	 *
	 * @param array
	 * @return boolean
	 */
	function has_feedback( $params )
	{
		$params = array_merge( array(
							'type' => 'feedbacks',
							'status' => 'published'
						), $params );

		// Check is a given type is allowed
		switch( $params['type'] )
		{
			case 'feedbacks':
			case 'comments':
			case 'trackbacks':
			case 'pingbacks':
				break;
			default:
				debug_die( "Unknown feedback type [{$params['type']}]" );
		}

		$number = generic_ctp_number( $this->ID, $params['type'], $params['status'] );

		return $number > 0;
	}


	/**
	 * Return true if trackbacks and pingbacks are allowed
	 *
	 * @return boolean
	 */
	function can_receive_pings()
	{
		$this->load_Blog();
		return $this->Blog->get( 'allowtrackbacks' ) && $this->can_comment( NULL );
	}


	/**
	 * Get text depending on number of comments
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments (include %d for # of comments)
	 * @param string Status of feedbacks to count
	 */
	function get_feedback_title( $type = 'feedbacks',	$zero = '#', $one = '#', $more = '#', $status = 'published', $filter_by_perm = true )
	{
		if( ! $this->can_see_comments() )
		{	// Comments disabled
			return NULL;
		}

		// dh> TODO:	Add plugin hook, where a Pingback plugin could hook and provide "pingbacks"
		switch( $type )
		{
			case 'feedbacks':
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 feedback');
				if( $more == '#' ) $more = T_('%d feedbacks');
				break;

			case 'comments':
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 comment');
				if( $more == '#' ) $more = T_('%d comments');
				break;

			case 'trackbacks':
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 trackback');
				if( $more == '#' ) $more = T_('%d trackbacks');
				break;

			case 'pingbacks':
				// Obsolete, but left for skin compatibility
				if( $zero == '#' ) $zero = '';
				if( $one == '#' ) $one = T_('1 pingback');
				if( $more == '#' ) $more = T_('%d pingbacks');
				break;

			default:
				debug_die( "Unknown feedback type [$type]" );
		}

		$number = generic_ctp_number( $this->ID, $type, $status, false, $filter_by_perm );
		if( !$filter_by_perm )
		{ // This is the case when we are only counting comments awaiting moderation, return only not visible feedbacks number
			// count feedbacks with the same statuses where user has permission
			$visible_number = generic_ctp_number( $this->ID, $type, $status, false, true );
			$number = $number - $visible_number;
		}

		if( $number == 0 )
			return $zero;
		elseif( $number == 1 )
			return $one;
		elseif( $number > 1 )
			return str_replace( '%d', $number, $more );
	}


	/**
	 * Get table from ratings data
	 *
	 * @param array ratings data
	 * @param array params
	 */
	function get_rating_table( $ratings, $params )
	{
		$ratings_count = $ratings['all_ratings'];
		$average_real = ( $ratings_count > 0 ) ? number_format( $ratings["summary"] / $ratings_count, 1, ".", "" ) : 0;
		$average = ceil( ( $average_real ) / 5 * 100 );

		$table = '<table class="rating_summary" cellspacing="1">';
		foreach ( $ratings as $r => $count )
		{	// Print a row for each star with formed data
			if( !is_int($r) )
			{
				continue;
			}

			$star_average = ( $ratings_count > 0 ) ? ceil( ( $count / $ratings_count ) * 100 ) : 0;
			switch( $params['rating_summary_star_totals'] )
			{
				case 'count':
					$star_value = '('.$count.')';
				break;
				case 'percent':
					$star_value = '('.$star_average.'%)';
				break;
				case 'none':
				default:
					$star_value = "";
				break;
			}
			$table .= '<tr><th>'.$r.' '.T_('star').':</th>
				<td class="progress"><div style="width:'.$star_average.'%">&nbsp;</div></td>
				<td>'.$star_value.'</td><tr>';
		}
		$table .= '</table>';

		$table .= '<div class="rating_summary_total">
			'.$ratings_count.' '.( $ratings_count > 1 ? T_('ratings') : T_('rating') ).'
			<div class="average_rating">'.T_('Average user rating').':<br />
			'.get_star_rating( $average_real ).'<span>('.$average_real.')</span>
			</div></div><div class="clear"></div>';

		return $table;
	}


	/**
	 * Get table with rating summary
	 *
	 * @param array of params
	 */
	function get_rating_summary( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
			'rating_summary_star_totals' => 'count' // Possible values: 'count', 'percent' and 'none'
		), $params );

		// get ratings and active ratings ( active ratings are younger then post_expiry_delay )
		list( $ratings, $active_ratings ) = $this->get_ratings();
		$ratings_count = $ratings['all_ratings'];
		$active_ratings_count = $active_ratings['all_ratings'];
		if( $ratings_count == 0 )
		{	// No Comments
			return NULL;
		}

		$average_real = number_format( $ratings["summary"] / $ratings_count, 1, ".", "" );
		$active_average_real = ( $active_ratings_count == 0 ) ? 0 : ( number_format( $active_ratings["summary"] / $active_ratings_count, 1, ".", "" ) );

		$result = '<table class="ratings_table" cellspacing="2">';
		$result .= '<tr>';
		$expiry_delay = $this->get_setting( 'post_expiry_delay' );
		if( empty( $expiry_delay ) )
		{
			$all_ratings_title = T_('User ratings');
		}
		else
		{
			$all_ratings_title = T_('Overall user ratings');
			$result .= '<td><div><strong>'.get_duration_title( $expiry_delay ).'</strong></div>';
			$result .= $this->get_rating_table( $active_ratings, $params );
			$result .= '</td>';
			$result .= '<td width="2px"></td>';
		}

		$result .= '<td><div><strong>'.$all_ratings_title.'</strong></div>';
		$result .= $this->get_rating_table( $ratings, $params );
		$result .= '</td>';
		$result .= '</tr></table>';

		return $result;
	}


	/**
	 * Template function: Displays feeback moderation info
	 *
	 * @param string Type of feedback to link to (feedbacks (all)/comments/trackbacks/pingbacks)
	 * @param string String to display before the link (if comments are to be displayed)
	 * @param string String to display after the link (if comments are to be displayed)
	 * @param string Link text to display when there are 0 comments
	 * @param string Link text to display when there is 1 comment
	 * @param string Link text to display when there are >1 comments (include %d for # of comments)
	 * @param string Link
	 * @param boolean true to hide if no feedback
	 */
	function feedback_moderation( $type = 'feedbacks', $before = '', $after = '',
			$zero = '', $one = '#', $more = '#', $edit_comments_link = '#', $params = array() )
	{
		/**
		 * @var User
		 */
		global $current_User;

		/* TODO: finish this...
		$params = array_merge( array(
									'type' => 'feedbacks',
									'block_before' => '',
									'blo_after' => '',
									'link_text_zero' => '#',
									'link_text_one' => '#',
									'link_text_more' => '#',
									'link_title' => '#',
									'use_popup' => false,
									'url' => '#',
									'type' => 'feedbacks',
								), $params );
		*/

		if( isset($current_User) &&	$current_User->check_perm( 'blog_comment!draft', 'moderate', false, $this->get_blog_ID() ) )
		{	// We have permission to edit comments:
			if( $edit_comments_link == '#' )
			{	// Use default link:
				global $admin_url;
				$edit_comments_link = '<a href="'.$admin_url.'?ctrl=items&amp;blog='.$this->get_blog_ID().'&amp;p='.$this->ID.'#comments" title="'.T_('Moderate these feedbacks').'">'.get_icon( 'edit' ).' '.T_('Moderate...').'</a>';
			}
		}
		else
		{ // User has no right to edit comments:
			$edit_comments_link = '';
		}

		// Inject Edit/moderate link as relevant:
		$zero = str_replace( '%s', $edit_comments_link, $zero );
		$one = str_replace( '%s', $edit_comments_link, $one );
		$more = str_replace( '%s', $edit_comments_link, $more );

		$r = $this->get_feedback_title( $type, $zero, $one, $more, array( 'draft', 'review' ), false );

		if( !empty( $r ) )
		{
			echo $before.$r.$after;
		}
	}



	/**
	 * Template tag: display footer for the current Item.
	 *
	 * @param array
	 * @return boolean true if something has been displayed
	 */
	function footer( $params )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'mode'        => '#',				// Will detect 'single' from $disp automatically
				'block_start' => '<div class="item_footer">',
				'block_end'   => '</div>',
				'format'      => 'htmlbody',
			), $params );

		if( $params['mode'] == '#' )
		{
			global $disp;
			$params['mode'] = $disp;
		}

		// pre_dump( $params['mode'] );

		$this->get_Blog();
		switch( $params['mode'] )
		{
			case 'xml':
				$text = $this->Blog->get_setting( 'xml_item_footer_text' );
				break;

			case 'single':
				$text = $this->Blog->get_setting( 'single_item_footer_text' );
				break;

			default:
				// Do NOT display!
				$text = '';
		}

		$text = preg_replace_callback( '#\$([a-z_]+)\$#', array( $this, 'replace_callback' ), $text );

		if( empty($text) )
		{
			return false;
		}

		echo format_to_output( $params['block_start'].$text.$params['block_end'], $params['format'] );

		return true;
	}


	/**
	 * Gets button for deleting the Item if user has proper rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string page url for the delete action
	 * @param string confirmation text
	 */
	function get_delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button = false, $actionurl = '#', $confirm_text = '#', $redirect_to = '' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'delete', false, $this ) )
		{ // User has right to delete this post
			return false;
		}

		if( $text == '#' )
		{
			if( ! $button )
			{
				$text = get_icon( 'delete', 'imgtag' ).' '.T_('Delete!');
			}
			else
			{
				$text = T_('Delete!');
			}
		}

		if( $title == '#' ) $title = T_('Delete this post');

		if( $actionurl == '#' )
		{
			$actionurl = $admin_url.'?ctrl=items&amp;action=delete&amp;post_ID=';
		}
		$url = $actionurl.$this->ID.'&amp;'.url_crumb('item');

		if( !empty( $redirect_to ) )
		{
			$url = $url.'&amp;redirect_to='.rawurlencode( $redirect_to );
		}

		if( $confirm_text == '#' )
		{
			$confirm_text = TS_('You are about to delete this post!\\nThis cannot be undone!');
		}

		$r = $before;
		if( $button )
		{ // Display as button
			$r .= '<input type="button"';
			$r .= ' value="'.$text.'" title="'.$title.'" onclick="if ( confirm(\'';
			$r .= $confirm_text;
			$r .= '\') ) { document.location.href=\''.$url.'\' }"';
			if( !empty( $class ) ) $r .= ' class="'.$class.'"';
			$r .= '/>';
		}
		else
		{ // Display as link
			$r .= '<a href="'.$url.'" title="'.$title.'" onclick="return confirm(\'';
			$r .= $confirm_text;
			$r .= '\')"';
			if( !empty( $class ) ) $r .= ' class="'.$class.'"';
			$r .= '>'.$text.'</a>';
		}
		$r .= $after;

		return $r;
	}


	/**
	 * Displays button for deleting the Item if user has proper rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param boolean true to make this a button instead of a link
	 * @param string page url for the delete action
	 * @param string confirmation text
	 */
	function delete_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $button = false, $actionurl = '#', $confirm_text = '#', $redirect_to = '' )
	{
		echo $this->get_delete_link( $before, $after, $text, $title, $class, $button, $actionurl, $confirm_text, $redirect_to );
	}


	/**
	 * Provide link to copy a post if user has edit rights
	 *
	 * @param array Params:
	 *  - 'before': to display before link
	 *  - 'after':    to display after link
	 *  - 'text': link text
	 *  - 'title': link title
	 *  - 'class': CSS class name
	 *  - 'save_context': redirect to current URL?
	 */
	function get_copy_link( $params = array() )
	{
		global $current_User, $admin_url;

		$actionurl = $this->get_copy_url($params);
		if( ! $actionurl )
		{
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => ' ',
				'after'        => ' ',
				'text'         => '#', // '#' - icon + text, '#icon#' - only icon, '#text#' - only text
				'title'        => '#',
				'class'        => '',
				'save_context' => true,
			), $params );

		switch( $params['text'] )
		{
			case '#':
				$params['text'] = get_icon( 'copy', 'imgtag', array( 'title' => T_('Duplicate this post...') ) ).' '.T_('Duplicate...');
				break;

			case '#icon#':
				$params['text'] = get_icon( 'copy', 'imgtag', array( 'title' => T_('Duplicate this post...') ) );
				break;

			case '#text#':
				$params['text'] = T_('Duplicate...');
				break;
		}

		if( $params['title'] == '#' ) $params['title'] = T_('Duplicate this post...');

		$r = $params['before'];
		$r .= '<a href="'.$actionurl;
		$r .= '" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) $r .= ' class="'.$params['class'].'"';
		$r .=  '>'.$params['text'].'</a>';
		$r .= $params['after'];

		return $r;
	}


	/**
	 * Get URL to copy a post if user has edit rights.
	 *
	 * @param array Params:
	 *  - 'save_context': redirect to current URL?
	 */
	function get_copy_url( $params = array() )
	{
		global $admin_url, $current_User;

		if( ! is_logged_in( false ) ) return false;

		if( ! $this->ID )
		{ // preview..
			return false;
		}

		$this->load_Blog();
		$write_item_url = $this->Blog->get_write_item_url();
		if( empty( $write_item_url ) )
		{ // User has no right to copy this post
			return false;
		}

		// default params
		$params += array('save_context' => true);

		$url = false;
		if( $this->Blog->get_setting( 'in_skin_editing' ) && !is_admin_page() )
		{	// We have a mode 'In-skin editing' for the current Blog
			if( check_item_perm_edit( 0, false ) )
			{	// Current user can copy this post from Front-office
				$url = url_add_param( $this->Blog->get( 'url' ), 'disp=edit&cp='.$this->ID );
			}
			else if( $current_User->check_perm( 'admin', 'restricted' ) )
			{	// Current user can copy this post from Back-office
				$url = $admin_url.'?ctrl=items&amp;action=copy&amp;blog='.$this->Blog->ID.'&amp;p='.$this->ID;
			}
		}
		else if( $current_User->check_perm( 'admin', 'restricted' ) )
		{	// Copy a post from Back-office
			$url = $admin_url.'?ctrl=items&amp;action=copy&amp;blog='.$this->Blog->ID.'&amp;p='.$this->ID;
			if( $params['save_context'] )
			{
				$url .= '&amp;redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ).'#'.$this->get_anchor_id() );
			}
		}
		return $url;
	}


	/**
	 * Template tag
	 * @see Item::get_copy_link()
	 */
	function copy_link( $params = array() )
	{
		echo $this->get_copy_link( $params );
	}


	/**
	 * Provide link to edit a post if user has edit rights
	 *
	 * @param array Params:
	 *  - 'before': to display before link
	 *  - 'after':    to display after link
	 *  - 'text': link text
	 *  - 'title': link title
	 *  - 'class': CSS class name
	 *  - 'save_context': redirect to current URL?
	 */
	function get_edit_link( $params = array() )
	{
		global $current_User, $admin_url;

		$actionurl = $this->get_edit_url($params);
		if( ! $actionurl )
		{
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => ' ',
				'after'        => ' ',
				'text'         => '#',
				'title'        => '#',
				'class'        => '',
				'save_context' => true,
			), $params );


		if( $params['text'] == '#' ) $params['text'] = get_icon( 'edit' ).' '.T_('Edit...');
		if( $params['title'] == '#' ) $params['title'] = T_('Edit this post...');

		$r = $params['before'];
		$r .= '<a href="'.$actionurl;
		$r .= '" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) $r .= ' class="'.$params['class'].'"';
		$r .=  '>'.$params['text'].'</a>';
		$r .= $params['after'];

		return $r;
	}


	/**
	 * Get URL to edit a post if user has edit rights.
	 *
	 * @param array Params:
	 *  - 'save_context': redirect to current URL?
	 */
	function get_edit_url($params = array())
	{
		global $admin_url, $current_User;

		if( ! is_logged_in( false ) ) return false;

		if( ! $this->ID )
		{ // preview..
			return false;
		}

		if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $this ) )
		{ // User has no right to edit this post
			return false;
		}

		// default params
		$params += array('save_context' => true);

		$this->load_Blog();
		$url = false;
		if( $this->Blog->get_setting( 'in_skin_editing' ) && ! is_admin_page() )
		{	// We have a mode 'In-skin editing' for the current Blog
			if( check_item_perm_edit( $this->ID, false ) )
			{	// Current user can edit this post
				$url = url_add_param( $this->Blog->get( 'url' ), 'disp=edit&p='.$this->ID );
			}
		}
		else if( $current_User->check_perm( 'admin', 'restricted' ) )
		{	// Edit a post from Back-office
			$url = $admin_url.'?ctrl=items&amp;action=edit&amp;p='.$this->ID;
			if( $params['save_context'] )
			{
				$url .= '&amp;redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ).'#'.$this->get_anchor_id() );
			}
		}
		return $url;
	}


	/**
	 * Template tag
	 * @see Item::get_edit_link()
	 */
	function edit_link( $params = array() )
	{
		echo $this->get_edit_link( $params );
	}


	/**
	 * Get next status to publish/restrict to this item
	 * TODO: asimo>Refactor this with Comment->get_next_status()
	 *
	 * @param boolean true to get next publish status, and false to get next restrict status
	 * @return mixed false if user has no permission | array( status, status_text, icon_color ) otherwise
	 */
	function get_next_status( $publish )
	{
		global $current_User;

		if( !is_logged_in( false ) )
		{
			return false;
		}

		$status_order = get_visibility_statuses( 'ordered-array' );
		$status_index = get_visibility_statuses( 'ordered-index' );

		$curr_index = $status_index[$this->status];
		if( ( !$publish ) && ( $curr_index == 0 ) && ( $this->status != 'deprecated' ) )
		{
			$curr_index = $curr_index + 1;
		}
		$has_perm = false;
		while( !$has_perm && ( $publish ? ( $curr_index < 4 ) : ( $curr_index > 0 ) ) )
		{
			$curr_index = $publish ? ( $curr_index + 1 ) : ( $curr_index - 1 );
			$has_perm = $current_User->check_perm( 'item_post!'.$status_order[$curr_index][0], 'moderate', false, $this );
		}
		if( $has_perm )
		{
			$label_index = $publish ? 1 : 2;
			return array( $status_order[$curr_index][0], $status_order[$curr_index][$label_index], $status_order[$curr_index][3] );
		}
		return false;
	}


	/**
	 * Provide link to publish a post if user has edit rights
	 *
	 * Note: publishing date will be updated
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function get_publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true )
	{
		global $current_User, $admin_url;

		if( $this->status != 'draft' )
		{
			return false;
		}

		if( ! is_logged_in( false ) ) return false;

		$this->load_Blog();
		if( ! ($current_User->check_perm( 'item_post!published', 'edit', false, $this ))
			|| ! ($current_User->check_perm( 'blog_edit_ts', 'edit', false, $this->Blog->ID ) ) )
		{ // User has no right to publish this post now:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'publish', 'imgtag' ).' '.T_('Publish NOW!');
		if( $title == '#' ) $title = T_('Publish now using current date and time.');

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=items'.$glue.'action=publish_now'.$glue.'post_ID='.$this->ID.$glue.url_crumb('item');
		if( $save_context )
		{
			$r .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ) );
		}
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Provide link to publish a post to the highest available public status for the current User
	 *
	 * @param $params
	 * @return boolean true if link was displayed false otherwise
	 */
	function highest_publish_link( $params = array() )
	{
		global $current_User, $admin_url;

		if( !is_logged_in( false ) )
		{
			return false;
		}

		$params = array_merge( array(
				'before'       => '',
				'after'        => '',
				'text'         => '#',
				'before_text'  => '',
				'after_text'   => '',
				'title'        => '',
				'class'        => '',
				'glue'         => '&amp;',
				'save_context' => true,
				'redirect_to'  => '',
			), $params );

		$curr_status_permvalue = get_status_permvalue( $this->status );
		// get the current User highest publish status for this item Blog
		list( $highest_status, $publish_text ) = get_highest_publish_status( 'post', $this->get_blog_ID() );
		// Get binary value of the highest available status
		$highest_status_permvalue = get_status_permvalue( $highest_status );
		if( $curr_status_permvalue >= $highest_status_permvalue || ( $highest_status_permvalue <= get_status_permvalue( 'private' ) ) )
		{ // Current User has no permission to change this comment status to a more public status
			return false;
		}

		if( ! ($current_User->check_perm( 'item_post!'.$highest_status, 'edit', false, $this ) ) )
		{ // User has no right to edit this post
			return false;
		}

		$glue = $params[ 'glue' ];
		$text = ( $params[ 'text' ] == '#' ) ? $publish_text : $params[ 'text' ];

		$r = $params[ 'before' ];
		$r .= '<a href="'.$admin_url.'?ctrl=items'.$glue.'action=publish'.$glue.'post_status='.$highest_status.$glue.'post_ID='.$this->ID.$glue.url_crumb('item');
		if( $params[ 'redirect_to' ] )
		{
			$r .= $glue.'redirect_to='.rawurlencode( $params[ 'redirect_to' ] );
		}
		elseif( $params[ 'save_context' ] )
		{
			$r .= $glue.'redirect_to='.rawurlencode( regenerate_url( '', '', '', '&' ) );
		}
		$r .= '" title="'.$params[ 'title' ].'"';
		if( !empty( $params[ 'class' ] ) ) $r .= ' class="'.$params[ 'class' ].'"';
		$r .= '>'.$params[ 'before_text' ].$text.$params[ 'after_text' ].'</a>';
		$r .= $params[ 'after' ];

		echo $r;
		return true;
	}


	function publish_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $save_context = true )
	{
		$publish_link = $this->get_publish_link( $before, $after, $text, $title, $class, $glue, $save_context );

		if( $publish_link === false )
		{	// The publish link is unavailable for current user and for this item
			return false;
		}

		// Display the publish link
		echo $publish_link;

		return true;
	}


	/**
	 * Display next Publish/Restrict to link
	 *
	 * @param array link params
	 * @param boolean true to display next publish status, and false to display next restrict status link
	 * @return boolean true if link was displayed | false otherwise
	 */
	function next_status_link( $params, $publish )
	{
		global $admin_url;

		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'before_text' => '',
				'after_text'  => '',
				'text'        => '#',
				'title'       => '',
				'class'       => '',
				'glue'        => '&amp;',
				'redirect_to' => '',
				'post_navigation' => 'same_blog',
				'nav_target'  => NULL,
			), $params );

		if( $publish )
		{
			$next_status_in_row = $this->get_next_status( true );
			$action = 'publish';
			$button_default_icon = 'move_up_'.$next_status_in_row[2];
		}
		else
		{
			$next_status_in_row =  $this->get_next_status( false );
			$action = 'restrict';
			$button_default_icon = 'move_down_'.$next_status_in_row[2];
		}

		if( $next_status_in_row === false )
		{ // Next status is not allowed for current user
			return false;
		}

		$next_status = $next_status_in_row[0];
		$next_status_label = $next_status_in_row[1];

		if( isset( $params['text_'.$next_status] ) )
		{ // Set text from params for next status
			$text = $params['text_'.$next_status];
		}
		elseif( $params['text' ] != '#' )
		{ // Set text from params for any atatus
			$text = $params['text'];
		}
		else
		{ // Default text
			$text = get_icon( $button_default_icon, 'imgtag', array( 'title' => '' ) ).' '.$next_status_label;
		}

		if( empty( $params['title'] ) )
		{
			$status_title = get_visibility_statuses( 'moderation-titles' );
			$params['title'] = $status_title[$next_status];
		}
		$glue = $params['glue'];

		$r = $params['before'];
		$r .= '<a href="'.$admin_url.'?ctrl=items'.$glue.'action='.$action.$glue.'post_status='.$next_status.$glue.'post_ID='.$this->ID.$glue.url_crumb('item');

		// set redirect_to
		$redirect_to = $params['redirect_to'];
		if( empty( $redirect_to ) && ( !is_admin_page() ) )
		{ // we are in front office
			if( $next_status == 'deprecated' )
			{
				if( $params['post_navigation'] == 'same_category' )
				{
					$redirect_to = get_caturl( $params['nav_target'] );
				}
				else
				{
					$this->get_Blog();
					$redirect_to = $this->Blog->gen_blogurl();
				}
			}
			else
			{
				$redirect_to = $this->add_navigation_param( $this->get_permanent_url(), $params['post_navigation'], $params['nav_target'] );
			}
		}
		if( !empty( $redirect_to ) )
		{
			$r .= $glue.'redirect_to='.rawurlencode( $redirect_to );
		}

		$r .= '" title="'.$params['title'].'"';
		if( empty( $params['class_'.$next_status] ) )
		{ // Set class for all statuses
			$class = empty( $params['class'] ) ? '' : $params['class'];
		}
		else
		{ // Set special class for next status
			$class = $params['class_'.$next_status];
		}
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$params['before_text'].$text.$params['after_text'].'</a>';
		$r .= $params['after'];

		echo $r;
		return true;
	}


	/**
	 * Provide link to deprecate a post if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function get_deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $redirect_to = '' )
	{
		global $current_User, $admin_url;

		if( ! is_logged_in( false ) ) return false;

		if( ($this->status == 'deprecated') // Already deprecated!
			|| ! ($current_User->check_perm( 'item_post!deprecated', 'edit', false, $this )) )
		{ // User has no right to deprecated this post:
			return false;
		}

		if( $text == '#' ) $text = get_icon( 'deprecate', 'imgtag' ).' '.T_('Deprecate!');
		if( $title == '#' ) $title = T_('Deprecate this post!');

		if( !empty( $redirect_to ) )
		{
			$redirect_to = $glue.'redirect_to='.rawurlencode( $redirect_to );
		}

		$r = $before;
		$r .= '<a href="'.$admin_url.'?ctrl=items'.$glue.'action=deprecate'.$glue.'post_ID='.$this->ID.$glue.url_crumb('item').$redirect_to;
		$r .= '" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.$text.'</a>';
		$r .= $after;

		return $r;
	}


	/**
	 * Display link to deprecate a post if user has edit rights
	 *
	 * @param string to display before link
	 * @param string to display after link
	 * @param string link text
	 * @param string link title
	 * @param string class name
	 * @param string glue between url params
	 */
	function deprecate_link( $before = ' ', $after = ' ', $text = '#', $title = '#', $class = '', $glue = '&amp;', $redirect_to = '' )
	{
		$deprecate_link = $this->get_deprecate_link( $before, $after, $text, $title, $class, $glue, $redirect_to );

		if( $deprecate_link === false )
		{	// The deprecate link is unavailable for current user and for this item
			return false;
		}

		// Display the deprecate link
		echo $deprecate_link;

		return true;
	}


	/**
	 * Template function: display priority of item
	 *
	 * @param string
	 * @param string
	 */
	function priority( $before = '', $after = '' )
	{
		if( isset($this->priority) )
		{
			echo $before;
			echo $this->priority;
			echo $after;
		}
	}


	/**
	 * Template function: display list of priority options
	 */
	function priority_options( $field_value, $allow_none )
	{
		$priority = isset($field_value) ? $field_value : $this->priority;

		$r = '';
		if( $allow_none )
		{
			$r = '<option value="">'./* TRANS: "None" select option */T_('No priority').'</option>';
		}

		foreach( $this->priorities as $i => $name )
		{
			$r .= '<option value="'.$i.'"';
			if( $priority == $i )
			{
				$r .= ' selected="selected"';
			}
			$r .= '>'.$name.'</option>';
		}

		return $r;
	}


	/**
	 * Get checkable list of renderers
	 *
	 * @param array|NULL If given, assume these renderers to be checked.
	 * @return string Renderer checkboxes
	 */
	function get_renderer_checkboxes( $item_renderers = NULL )
	{
		global $Plugins;

		if( is_null( $item_renderers ) )
		{
			$item_renderers = $this->get_renderers();
		}

		return $Plugins->get_renderer_checkboxes( $item_renderers, array( 'Item' => & $this ) );
	}


	/**
	 * Template function: display checkable list of renderers
	 *
	 * @param array|NULL If given, assume these renderers to be checked.
	 */
	function renderer_checkboxes( $item_renderers = NULL )
	{
		echo $this->get_renderer_checkboxes( $item_renderers );
	}


	/**
	 * Get status of item
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * @param array Params
	 */
	function get_status( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
				'format' => 'htmlbody', // Output format, see {@link format_to_output()}
			), $params );

		$r = $params['before'];

		switch( $params['format'] )
		{
			case 'raw':
				$r .= $this->get_status_raw();
				break;

			case 'styled':
				$r .= get_styled_status( $this->status, $this->get('t_status') );
				break;

			default:
				$r .= format_to_output( $this->get('t_status'), $params['format'] );
				break;
		}

		$r .= $params['after'];

		return $r;
	}


	/**
	 * Template function: display status of item
	 *
	 * Statuses:
	 * - published
	 * - deprecated
	 * - protected
	 * - private
	 * - draft
	 *
	 * @param array Params
	 */
	function status( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'format'      => 'htmlbody', // Output format, see {@link format_to_output()}
			), $params );

		echo $this->get_status( $params );
	}


	/**
	 * Output classes for the Item <div>
	 */
	function div_classes( $params = array(), $output = true )
	{
		global $disp;

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'item_class'        => 'bPost',
				'item_type_class'   => 'bPost_ptyp',
				'item_status_class' => 'bPost',
				'item_disp_class'   => 'bPost_disp_',
			), $params );

		$classes = array( $params['item_class'],
						  $params['item_type_class'].$this->ptyp_ID,
						  $params['item_status_class'].$this->status,
						  $params['item_disp_class'].$disp,
						);

		$r = implode( ' ', $classes );

		if( ! $output ) return $r;

		echo $r;
	}


	/**
	 * Get raw status
	 * 
	 * @return string Status
	 */
	function get_status_raw()
	{
		return $this->status;
	}


	/**
	 * Output raw status.
	 */
	function status_raw()
	{
		echo $this->get_status_raw();
	}


	/**
	 * Template function: display extra status of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function extra_status( $before = '', $after = '', $format = 'htmlbody' )
	{
		if( $format == 'raw' )
		{
			$this->disp( $this->get('t_extra_status'), 'raw' );
		}
		elseif( $extra_status = $this->get('t_extra_status') )
		{
			echo $before.format_to_output( $extra_status, $format ).$after;
		}
	}


 	/**
	 * Display tags for Item
	 *
	 * @param array of params
	 * @param string Output format, see {@link format_to_output()}
	 */
	function tags( $params = array() )
	{
		$params = array_merge( array(
				'before' =>           '<div>'.T_('Tags').': ',
				'after' =>            '</div>',
				'separator' =>        ', ',
				'links' =>            true,
			), $params );

		$tags = $this->get_tags();

		if( !empty( $tags ) )
		{
			echo $params['before'];

			if( $links = $params['links'] )
			{
				$this->get_Blog();
			}

			$i = 0;
			foreach( $tags as $tag )
			{
				if( $i++ > 0 )
				{
					echo $params['separator'];
				}

				if( $links )
				{	// We want links
					echo $this->Blog->get_tag_link( $tag );
				}
				else
				{
					echo htmlspecialchars($tag);
				}
			}

			echo $params['after'];
		}
	}


	/**
	 * Template function: Displays trackback autodiscovery information
	 *
	 * TODO: build into headers
	 */
	function trackback_rdf()
	{
		$this->get_Blog();
		if( ! $this->can_receive_pings() )
		{ // Trackbacks not allowed on this blog:
			return;
		}

		echo '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" '."\n";
		echo '  xmlns:dc="http://purl.org/dc/elements/1.1/"'."\n";
		echo '  xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">'."\n";
		echo '<rdf:Description'."\n";
		echo '  rdf:about="';
		$this->permanent_url( 'single' );
		echo '"'."\n";
		echo '  dc:identifier="';
		$this->permanent_url( 'single' );
		echo '"'."\n";
		$this->title( array(
			'before'    => ' dc:title="',
			'after'     => '"'."\n",
			'link_type' => 'none',
			'format'    => 'xmlattr',
			) );
		echo '  trackback:ping="';
		$this->trackback_url();
		echo '" />'."\n";
		echo '</rdf:RDF>';
	}


	/**
	 * Template function: displays url to use to trackback this item
	 */
	function trackback_url()
	{
		echo $this->get_trackback_url();
	}


	/**
	 * Template function: get url to use to trackback this item
	 * @return string
	 */
	function get_trackback_url()
	{
		global $htsrv_url, $Settings;

		// fp> TODO: get a clean (per blog) setting for this
		//	return $htsrv_url.'trackback.php/'.$this->ID;

		return $htsrv_url.'trackback.php?tb_id='.$this->ID;
	}


	/**
	 * Get HTML code to display a flash audio player for playback of a
	 * given URL.
	 *
	 * @param string The URL of a MP3 audio file.
	 * @return string The HTML code.
	 */
	function get_player( $url )
	{
		global $rsc_url;

		return '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="200" height="20" id="dewplayer" align="middle"><param name="wmode" value="transparent"><param name="allowScriptAccess" value="sameDomain" /><param name="movie" value="'.$rsc_url.'swf/dewplayer.swf?mp3='.$url.'&amp;showtime=1" /><param name="quality" value="high" /><param name="bgcolor" value="" /><embed src="'.$rsc_url.'swf/dewplayer.swf?mp3='.$url.'&amp;showtime=1" quality="high" bgcolor="" width="200" height="20" name="dewplayer" wmode="transparent" align="middle" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer"></embed></object>';
	}


	/**
	 * Template function: Display link to item related url.
	 *
	 * By default the link is displayed as a link.
	 * Optionally some smart stuff may happen.
	 */
	function url_link( $params = array() )
	{

		if( empty( $this->url ) )
		{
			return;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'        => ' ',
				'after'         => ' ',
				'text_template' => '$url$',		// If evaluates to empty, nothing will be displayed (except player if podcast)
				'url_template'  => '$url$',
				'target'        => '',
				'format'        => 'htmlbody',
				'podcast'       => '#',						// handle as podcast. # means depending on post type
				'before_podplayer' => '<div class="podplayer">',
				'after_podplayer'  => '</div>',
			), $params );

		if( $params['podcast'] == '#' )
		{	// Check if this post is a podcast
			$params['podcast'] = ( $this->ptyp_ID == 2000 );
		}

		if( $params['podcast'] && $params['format'] == 'htmlbody' )
		{	// We want podcast display:

			echo $params['before_podplayer'];

			echo $this->get_player( $this->url );

			echo $params['after_podplayer'];

		}
		else
		{ // Not displaying podcast player:

			$text = str_replace( '$url$', $this->url, $params['text_template'] );
			if( empty($text) )
			{	// Nothing to display
				return;
			}

			$r = $params['before'];

			$r .= '<a href="'.str_replace( '$url$', $this->url, $params['url_template'] ).'"';

			if( !empty( $params['target'] ) )
			{
				$r .= ' target="'.$params['target'].'"';
			}

			$r .= '>'.$text.'</a>';

			$r .= $params['after'];

			echo format_to_output( $r, $params['format'] );
		}
	}


	/**
	 * Template function: Display the number of words in the post
	 */
	function wordcount()
	{
		echo (int)$this->wordcount; // may have been saved as NULL until 1.9
	}


	/**
	 * Template function: Display the number of times the Item has been viewed
	 *
	 * Note: viewcount is incremented whenever the Item's content is displayed with "MORE"
	 * (i-e full content), see {@link Item::content()}.
	 *
	 * Viewcount is NOT incremented on page reloads and other special cases, see {@link Hit::is_new_view()}
	 *
	 * %d gets replaced in all params by the number of views.
	 *
	 * @param string Link text to display when there are 0 views
	 * @param string Link text to display when there is 1 views
	 * @param string Link text to display when there are >1 views
	 * @return string The phrase about the number of views.
	 */
	function get_views( $zero = '#', $one = '#', $more = '#' )
	{
		if( !$this->views )
		{
			$r = ( $zero == '#' ? T_( 'No views' ) : $zero );
		}
		elseif( $this->views == 1 )
		{
			$r = ( $one == '#' ? T_( '1 view' ) : $one );
		}
		else
		{
			$r = ( $more == '#' ? T_( '%d views' ) : $more );
		}

		return str_replace( '%d', $this->views, $r );
	}


	/**
	 * Template function: Display a phrase about the number of Item views.
	 *
	 * @param string Link text to display when there are 0 views
	 * @param string Link text to display when there is 1 views
	 * @param string Link text to display when there are >1 views (include %d for # of views)
	 * @return integer Number of views.
	 */
	function views( $zero = '#', $one = '#', $more = '#' )
	{
		echo $this->get_views( $zero, $one, $more );

		return $this->views;
	}


	/**
	 * Set param value
	 *
	 * By default, all values will be considered strings
	 *
	 * @todo extra_cat_IDs recording
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
			case 'pst_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

			case 'content':
				$r1 = $this->set_param( 'content', 'string', $parvalue, $make_null );
				// Update wordcount as well:
				$r2 = $this->set_param( 'wordcount', 'number', bpost_count_words($this->content), false );
				return ( $r1 || $r2 ); // return true if one changed

			case 'wordcount':
			case 'featured':
				return $this->set_param( $parname, 'number', $parvalue, false );

			case 'datedeadline':
				return $this->set_param( 'datedeadline', 'date', $parvalue, true );

			case 'order':
				return $this->set_param( 'order', 'number', $parvalue, true );

			case 'renderers': // deprecated
				return $this->set_renderers( $parvalue );

			case 'excerpt':
				if( $this->excerpt_autogenerated )
				{	// Check if the excerpt needs to keep getting autogenerated...
					$autovalue = $this->get_autogenerated_excerpt();
					$post_excerpt_previous_md5 = param('post_excerpt_previous_md5', 'string');
					// TODO: this is itemform specific and should not be like that.
					if( $post_excerpt_previous_md5 == md5($parvalue) || empty($post_excerpt_previous_md5) /* empty in simple form */ )
					{ // old value has not changed, it keeps getting autogenerated:
						$parvalue = $autovalue;
					}
				}

				if( parent::set( 'excerpt', $parvalue, $make_null ) )
				{ // mark excerpt as not being autogenerated anymore, if user has changed it from the autogenerated value.
					if( isset($autovalue) && $parvalue != $autovalue )
					{
						$this->set('excerpt_autogenerated', 0);
					}
				}
				break;

			default:
				return parent::set( $parname, $parvalue, $make_null );
		}
	}


	/**
	 * Set the renderers of the Item.
	 *
	 * @param array List of renderer codes.
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_renderers( $renderers )
	{
		return $this->set_param( 'renderers', 'string', implode( '.', $renderers ) );
	}


	/**
	 * Set the Author of the Item.
	 *
	 * @param User (Do NOT set to NULL or you may kill the current_User)
	 * @return boolean true, if it has been set; false if it has not changed
	 */
	function set_creator_User( & $creator_User )
	{
		$this->creator_User = & $creator_User;
		$this->Author = & $this->creator_User; // deprecated  fp> TODO: Test and see if this line can be put once and for all in the constructor
		return $this->set( $this->creator_field, $creator_User->ID );
	}


	/**
	 * Set the Item location from the current user. Use to create a new post.
	 *
	 * @param string Location (country | region | subregion | city)
	 */
	function set_creator_location( $location )
	{
		global $current_User;

		if( !isset( $current_User ) )
		{	// No logged in user
			return;
		}

		$locations = array(
				'country'   => 'ctry_ID',
				'region'    => 'rgn_ID',
				'subregion' => 'subrg_ID',
				'city'      => 'city_ID',
			);

		$field_ID = $locations[$location];

		$this->load_Blog();
		if( $this->Blog->{$location.'_visible'}() )
		{	// Location is visible
			if( empty( $this->$field_ID ) )
			{	// Set default location
				$this->set( $field_ID, $current_User->$field_ID );
			}
		}
	}


	/**
	 * Create a new Item/Post and insert it into the DB
	 *
	 * This function has to handle all needed DB dependencies!
	 *
	 * @deprecated Use set() + dbinsert() instead
	 */
	function insert(
		$author_user_ID,              // Author
		$post_title,
		$post_content,
		$post_timestamp,              // 'Y-m-d H:i:s'
		$main_cat_ID = 1,             // Main cat ID
		$extra_cat_IDs = array(),     // Table of extra cats
		$post_status = 'published',
		$post_locale = '#',
		$post_urltitle = '',
		$post_url = '',
		$post_comment_status = 'open',
		$post_renderers = array('default'),
		$item_typ_ID = 1,
		$item_st_ID = NULL,
		$post_order = NULL )
	{
		global $DB, $query, $UserCache;
		global $default_locale;

		if( $post_locale == '#' ) $post_locale = $default_locale;

		// echo 'INSERTING NEW POST ';

		if( isset( $UserCache ) )	// DIRTY HACK
		{ // If not in install procedure...
			$this->set_creator_User( $UserCache->get_by_ID( $author_user_ID ) );
		}
		else
		{
			$this->set( $this->creator_field, $author_user_ID );
		}
		$this->set( $this->lasteditor_field, $this->{$this->creator_field} );
		$this->set( 'title', $post_title );
		$this->set( 'urltitle', $post_urltitle );
		$this->set( 'content', $post_content );
		$this->set( 'datestart', $post_timestamp );

		$this->set( 'main_cat_ID', $main_cat_ID );
		$this->set( 'extra_cat_IDs', $extra_cat_IDs );
		$this->set( 'status', $post_status );
		$this->set( 'locale', $post_locale );
		$this->set( 'url', $post_url );
		$this->set( 'comment_status', $post_comment_status );
		$this->set_renderers( $post_renderers );
		$this->set( 'ptyp_ID', $item_typ_ID );
		$this->set( 'pst_ID', $item_st_ID );
		$this->set( 'order', $post_order );

		// INSERT INTO DB:
		$this->dbinsert();

		return $this->ID;
	}


	/**
	 * Insert object into DB based on previously recorded changes
	 *
	 * @param string Source of item creation ( 'through_admin', 'through_xmlrpc', 'through_email' )
	 * @return boolean true on success
	 */
	function dbinsert( $created_through = 'through_admin' )
	{
		global $DB, $current_User, $Plugins;

		$DB->begin( 'SERIALIZABLE' );

		if( $this->status != 'draft' )
		{	// The post is getting published in some form, set the publish date so it doesn't get auto updated in the future:
			$this->set( 'dateset', 1 );
		}

		if( empty($this->creator_user_ID) )
		{ // No creator assigned yet, use current user:
			$this->set_creator_User( $current_User );
		}

		// Create new slug with validated title
		$new_Slug = new Slug();
		$new_Slug->set( 'title', urltitle_validate( $this->urltitle, $this->title, $this->ID, false, $new_Slug->dbprefix.'title', $new_Slug->dbprefix.'itm_ID', $new_Slug->dbtablename, $this->locale ) );
		$new_Slug->set( 'type', 'item' );
		$this->set( 'urltitle', $new_Slug->get( 'title' ) );

		$this->update_renderers_from_Plugins();

		$this->update_excerpt();

		if( isset($Plugins) )
		{	// Note: Plugins may not be available during maintenance, install or test cases
			// TODO: allow a plugin to cancel update here (by returning false)?
			$Plugins->trigger_event( 'PrependItemInsertTransact', $params = array( 'Item' => & $this ) );
		}

		global $localtimenow;
		$this->set_param( 'last_touched_ts', 'date', date('Y-m-d H:i:s',$localtimenow) );

		$dbchanges = $this->dbchanges; // we'll save this for passing it to the plugin hook

		if( $result = parent::dbinsert() )
		{ // We could insert the item object..

			// Let's handle the extracats:
			$result = $this->insert_update_extracats( 'insert' );

			if( $result )
			{ // Let's handle the tags:
				$this->insert_update_tags( 'insert' );
			}

			// save Item settings
			if( $result && isset( $this->ItemSettings ) && isset( $this->ItemSettings->cache[0] ) )
			{
				// update item ID in the ItemSettings cache
				$this->ItemSettings->cache[$this->ID] = $this->ItemSettings->cache[0];
				unset( $this->ItemSettings->cache[0] );

				$this->ItemSettings->dbupdate();
			}

			if( $result )
			{
				modules_call_method( 'update_item_after_insert', array( 'edited_Item' => $this ) );
			}

			// Let's handle the slugs:
			// set slug item ID:
			$new_Slug->set( 'itm_ID', $this->ID );

			// Create tiny slug:
			$new_tiny_Slug = new Slug();
			load_funcs( 'slugs/model/_slug.funcs.php' );
			$tinyurl = getnext_tinyurl();
			$new_tiny_Slug->set( 'title', $tinyurl );
			$new_tiny_Slug->set( 'type', 'item' );
			$new_tiny_Slug->set( 'itm_ID', $this->ID );

			if( $result && ( $result = ( $new_Slug->dbinsert() && $new_tiny_Slug->dbinsert() ) ) )
			{
				$this->set( 'canonical_slug_ID', $new_Slug->ID );
				$this->set( 'tiny_slug_ID', $new_tiny_Slug->ID );
				if( $result = parent::dbupdate() )
				{
					$DB->commit();

					// save the last tinyurl
					global $Settings;
					$Settings->set( 'tinyurl', $tinyurl );
					$Settings->dbupdate();

					if( isset($Plugins) )
					{	// Note: Plugins may not be available during maintenance, install or test cases
						$Plugins->trigger_event( 'AfterItemInsert', $params = array( 'Item' => & $this, 'dbchanges' => $dbchanges ) );
					}
				}
			}
		}

		if( ! $result )
		{	// Rollback current transaction
			$DB->rollback();
		}
		else
		{	// Log a creating of new item on success result
			log_new_item_create( $created_through );
		}

		return $result;
	}


	/**
	 * Insert new item in test mode, Use this function only in test tool to create very much items at one time
	 * 
	 * @return boolean true on success
	 */
	function dbinsert_test()
	{
		global $DB, $localtimenow;

		$this->set_param( 'last_touched_ts', 'date', date( 'Y-m-d H:i:s', $localtimenow ) );

		$DB->begin( 'SERIALIZABLE' );

		if( $result = parent::dbinsert() )
		{ // We could insert the item object..

			if( ! is_null( $this->extra_cat_IDs ) )
			{ // Insert new extracats:
				$query = 'INSERT INTO T_postcats ( postcat_post_ID, postcat_cat_ID ) VALUES ';
				foreach( $this->extra_cat_IDs as $extra_cat_ID )
				{
					$query .= '( '.$this->ID.', '.$extra_cat_ID.' ),';
				}
				$query = substr( $query, 0, strlen( $query ) - 1 );
				$DB->query( $query, 'insert new extracats' );
			}

			// Create canonical slug with urltitle
			$canonical_Slug = new Slug();
			$canonical_Slug->set( 'title', $this->urltitle );
			$canonical_Slug->set( 'type', 'item' );
			$canonical_Slug->set( 'itm_ID', $this->ID );

			// Create tiny slug:
			$tiny_Slug = new Slug();
			load_funcs( 'slugs/model/_slug.funcs.php' );
			$tinyurl = getnext_tinyurl();
			$tiny_Slug->set( 'title', $tinyurl );
			$tiny_Slug->set( 'type', 'item' );
			$tiny_Slug->set( 'itm_ID', $this->ID );

			if( $result = ( $canonical_Slug->dbinsert() && $tiny_Slug->dbinsert() ) )
			{
				$this->set( 'canonical_slug_ID', $canonical_Slug->ID );
				$this->set( 'tiny_slug_ID', $tiny_Slug->ID );
				if( $result = parent::dbupdate() )
				{ // save the last tinyurl
					global $Settings;
					$Settings->set( 'tinyurl', $tinyurl );
					$Settings->dbupdate();
				}
			}
		}

		if( $result )
		{ // The post and all related object was successfully created
			$DB->commit();
		}
		else
		{ // Some error occured the transaction needs to be rollbacked
			$DB->rollback();
		}

		return $result;
	}


	/**
	 * Update the DB based on previously recorded changes
	 *
	 * @param boolean do we want to auto track the mod date?
	 * @param boolean Update slug? - We want to PREVENT updating slug when item dbupdate is called,
	 * 	because of the item canonical url title was changed on the slugs edit form, so slug update is already done.
	 *  If slug update wasn't done already, then this param has to be true.
	 * @param boolean Update excerpt? - We want to PREVENT updating exerpts when the item content wasn't changed ( e.g. only item canonical slug was changed )
	 * @return boolean true on success
	 */
	function dbupdate( $auto_track_modification = true, $update_slug = true, $update_excerpt = true )
	{
		global $DB, $Plugins;

		$DB->begin( 'SERIALIZABLE' );

		if( $this->status != 'draft' )
		{	// The post is getting published in some form, set the publish date so it doesn't get auto updated in the future:
			$this->set( 'dateset', 1 );
		}

		// save Item settings
		if( isset( $this->ItemSettings ) )
		{
			$this->ItemSettings->dbupdate();
		}

		// validate url title / slug
		if( $update_slug )
		{ // item canonical slug wasn't updated outside from this call, if it was changed or it wasn't set yet, we must update the slugs
			if( empty( $this->urltitle ) || isset( $this->dbchanges['post_urltitle'] )  )
			{ // Url title has changed or is empty, we do need to update the slug:
				$edited_slugs = $this->update_slugs();
			}
		}

		$this->update_renderers_from_Plugins();

		if( $update_excerpt )
		{	// We want to update the excerpt:
			$this->update_excerpt();
		}

		// TODO: dh> allow a plugin to cancel update here (by returning false)?
		$Plugins->trigger_event( 'PrependItemUpdateTransact', $params = array( 'Item' => & $this ) );

		$dbchanges = $this->dbchanges; // we'll save this for passing it to the plugin hook

		// pre_dump($this->dbchanges);
		// fp> note that dbchanges isn't actually 100% accurate. At this time it does include variables that actually haven't changed.
		if( isset($this->dbchanges['post_status'])
			|| isset($this->dbchanges['post_title'])
			|| isset($this->dbchanges['post_content']) )
		{	// One of the fields we track in the revision history has changed:
			// Save the "current" (soon to be "old") data as a version before overwriting it in parent::dbupdate:
			// fp> TODO: actually, only the fields that have been changed should be copied to the version, the other should be left as NULL

			// Get next version ID
			$iver_SQL = new SQL();
			$iver_SQL->SELECT( 'MAX( iver_ID )' );
			$iver_SQL->FROM( 'T_items__version' );
			$iver_SQL->WHERE( 'iver_itm_ID = '.$this->ID );
			$iver_ID = (int)$DB->get_var( $iver_SQL->get() ) + 1;

			$sql = 'INSERT INTO T_items__version( iver_ID, iver_itm_ID, iver_edit_user_ID, iver_edit_datetime, iver_status, iver_title, iver_content )
				SELECT "'.$iver_ID.'" AS iver_ID, post_ID, post_lastedit_user_ID, post_datemodified, post_status, post_title, post_content
					FROM T_items__item
				 WHERE post_ID = '.$this->ID;
			$DB->query( $sql, 'Save a version of the Item' );
		}

		if( $auto_track_modification && ( count( $dbchanges ) > 0 ) && ( !isset( $dbchanges['last_touched_ts'] ) ) )
		{ // Update last_touched_ts field only if it wasn't updated yet and the datemodified will be updated for sure.
			global $localtimenow;
			$this->set_param( 'last_touched_ts', 'date', date('Y-m-d H:i:s',$localtimenow) );
		}

		if( $result = ( parent::dbupdate( $auto_track_modification ) !== false ) )
		{ // We could update the item object:

			// Let's handle the extracats:
			$result = $this->insert_update_extracats( 'update' );

			if( $result )
			{ // Let's handle the tags:
				$this->insert_update_tags( 'update' );
			}

			// Let's handle the slugs:
			// TODO: dh> $result handling here feels wrong: when it's true already, it should not become false (add "|| $result"?)
			// asimo>dh The result handling is in a transaction. If somehow the new slug creation fails, then the item insertion should rollback either
			if( $result && !empty( $edited_slugs ) )
			{ // if we have new created $edited_slugs, we have to insert it into the database:
				foreach( $edited_slugs as $edited_Slug )
				{
					if( $edited_Slug->ID == 0 )
					{ // Insert only new created slugs
						$edited_Slug->dbinsert();
					}
				}
				if( isset( $edited_slugs[0] ) && $edited_slugs[0]->ID > 0 )
				{ // Make first slug from list as main slug for this item
					$this->set( 'canonical_slug_ID', $edited_slugs[0]->ID );
					$this->set( 'urltitle', $edited_slugs[0]->get( 'title' ) );
					$result = parent::dbupdate();
				}
			}
		}

		if( $result === false )
		{ // Update failed
			$DB->rollback();
		}
		else
		{ // Update was successful
			$this->delete_prerendered_content();

			$DB->commit();

			$Plugins->trigger_event( 'AfterItemUpdate', $params = array( 'Item' => & $this, 'dbchanges' => $dbchanges ) );
		}

		// Load the blog we're in:
		$Blog = & $this->get_Blog();

		// Thick grained invalidation:
		// This collection has been modified, cached content depending on it should be invalidated:
		BlockCache::invalidate_key( 'coll_ID', $Blog->ID );

		// Fine grained invalidation:
		// EXPERIMENTAL: Below are more granular invalidation dates:
		// set_coll_ID // Settings have not changed
		BlockCache::invalidate_key( 'cont_coll_ID', $Blog->ID ); // Content has changed

		return $result;
	}


	/**
	 * Create new slugs with validated title
	 * !!!private!!! This function should be called only from Item dbupdate() function
	 * @private
	 * @return array Slug objects
	 */
	function update_slugs( $urltitle = NULL )
	{
		if( ! isset( $urltitle ) )
		{
			$urltitle = $this->urltitle;
		}

		// Split slugs by comma
		$urltitles = explode( ',', $urltitle );

		$edited_slugs = array();
		foreach( $urltitles as $urltitle )
		{
			$urltitle = trim( $urltitle );

			// create new slug
			$new_Slug = new Slug();
			// urltitle_validate may modify the urltitle !!!
			$new_Slug->set( 'title', urltitle_validate( $urltitle, $this->title, $this->ID, false, $new_Slug->dbprefix.'title', $new_Slug->dbprefix.'itm_ID', $new_Slug->dbtablename, $this->locale ) );
			$new_Slug->set( 'type', 'item' );
			$new_Slug->set( 'itm_ID', $this->ID );

			// Check if this slug was already used by this item or not.
			// We need this check, because urltitle_validate() function will modify an existing urltitle only if it belongs to a different object
			$SlugCache = & get_SlugCache();
			$prev_Slug = $SlugCache->get_by_name( $new_Slug->get('title'), false, false );
			if( $prev_Slug )
			{ // A slug with this title already exists. It must belong to the same item!
				if( $prev_Slug->get('itm_ID') == $new_Slug->get('itm_ID') )
				{
					$edited_slugs[] = $prev_Slug;
					continue;
				}
				else
				{ // This case should never happen, because urltitle validate check this case. It is only an extra check.
					debug_die('The slugs table is broken');
				}
			}
			else
			{ // No slug with such urltitle in DB, we can add this new one
				$edited_slugs[] = $new_Slug;
			}
		}

		return $edited_slugs;
	}


	/**
	 * Trigger event AfterItemDelete after calling parent method.
	 *
	 * @todo fp> delete related stuff: comments, cats, file links...
	 *
	 * @return boolean true on success
	 */
	function dbdelete()
	{
		global $DB, $Plugins;

		// Get list of comments that are going to be deleted
		$comments_list = implode( ',', $DB->get_col( '
			SELECT comment_ID
			  FROM T_comments
			 WHERE comment_post_ID = '.$DB->quote( $this->ID ) ) );

		// remember ID, because parent method resets it to 0
		$old_ID = $this->ID;

		$DB->begin();

		if( $r = parent::dbdelete() )
		{
			// re-set the ID for the Plugin event & for a deleting of the prerendered content
			$this->ID = $old_ID;

			$this->delete_prerendered_content();

			if( !empty( $comments_list ) )
			{	// Delete dependencies of the comments
				$DB->query( "DELETE FROM T_comments__votes
				  WHERE cmvt_cmt_ID IN ($comments_list)" );
			}

			$DB->commit();

			$Plugins->trigger_event( 'AfterItemDelete', $params = array( 'Item' => & $this ) );

			$this->ID = 0;
		}
		else
		{
			$DB->rollback();
		}

		return $r;
	}


	/**
	 * Quick and dirty "excerpts should not stay empty".
	 *
	 * @todo have a maxlength param for excerpts in blog properties
	 * @todo crop at word boundary, maybe even sentence boundary.
	 *       This should get added to strmaxlen probably.
	 *
	 * @param integer Crop length
	 * @param string Suffix, if cropped
	 * @return boolean true if excerpt has been changed
	 */
	function update_excerpt( $crop_length = 254, $suffix = '&hellip;' )
	{
		if( empty($this->excerpt) || $this->excerpt_autogenerated )
		{	// We want to regenrate the excerpt from the content:
			$excerpt = $this->get_autogenerated_excerpt($crop_length, $suffix);

			if( !empty($excerpt) )
			{	// We have something to act as an excerpt...
				$this->set( 'excerpt', $excerpt );
				$this->set( 'excerpt_autogenerated', 1 );
				return true;
			}
		}

		return false;
	}


	/**
	 * Get autogenerated excerpt, derived from {@link Item::$content}.
	 *
	 * @param integer Crop length
	 * @param string Suffix, if cropped
	 * @return string
	 */
	function get_autogenerated_excerpt( $crop_length = 254, $suffix = '&hellip;' )
	{
		// autogenerated excerpt should NEVER show anything after <!-- more --> or after <!-- page -->
		$excerpt_content = array_shift( $this->get_content_parts( array( 'disppage' => 1 ) ) );
		$r = str_replace( '<p>', ' <p>', $excerpt_content );
		$r = str_replace( '<br', ' <br', $excerpt_content );
		$r = trim(strip_tags($r));
		// fp> this is borked: $r = preg_replace('~(\r?\n)+~', '\n', $r);
		$r = trim($r);
		$r = strmaxlen( $r, $crop_length, $suffix );
		return $r;
	}


	/**
	 * Insert/Update post extracats
	 *
	 * @param string 'insert' | 'update'
	 * @return boolean true on success | false one failure
	 */
	function insert_update_extracats( $mode )
	{
		global $DB, $Messages;

		if( ! is_null( $this->extra_cat_IDs ) )
		{ // Okay the extra cats are defined:
			$DB->begin( 'SERIALIZABLE' );

			$meta_count = $DB->get_var( 'SELECT count( cat_ID ) FROM T_categories WHERE cat_meta = 1 AND cat_ID IN ('.implode( ',', $this->extra_cat_IDs ).')' );
			if( $meta_count > 0 )
			{
				$DB->rollback();
				$Messages->add( T_('Could not set the selected categories!'), 'error' );
				return false;
			}
			if( $mode == 'update' )
			{
				// delete previous extracats:
				$DB->query( 'DELETE FROM T_postcats WHERE postcat_post_ID = '.$this->ID, 'delete previous extracats' );
			}

			// insert new extracats:
			$query = "INSERT INTO T_postcats( postcat_post_ID, postcat_cat_ID ) VALUES ";
			foreach( $this->extra_cat_IDs as $extra_cat_ID )
			{
				//echo "extracat: $extracat_ID <br />";
				$query .= "( $this->ID, $extra_cat_ID ),";
			}
			$query = substr( $query, 0, strlen( $query ) - 1 );
			$DB->query( $query, 'insert new extracats' );

			$DB->commit();
		}

		return true;
	}


	/**
	 * Save tags to DB
	 *
	 * @param string 'insert' | 'update'
	 */
	function insert_update_tags( $mode )
	{
		global $DB;

		if( isset( $this->tags ) )
		{ // Okay the tags are defined:

			$DB->begin();

			if( $mode == 'update' )
			{	// delete previous tag associations:
				// Note: actual tags never get deleted
				$DB->query( 'DELETE FROM T_items__itemtag
											WHERE itag_itm_ID = '.$this->ID, 'delete previous tags' );
			}

			if( !empty($this->tags) )
			{
				// Find the tags that are already in the DB
				$query = 'SELECT LOWER( tag_name )
										FROM T_items__tag
									 WHERE tag_name IN ('.$DB->quote($this->tags).')';
				$existing_tags = $DB->get_col( $query, 0, 'Find existing tags' );

				$new_tags = array_diff( array_map('evo_strtolower', $this->tags), $existing_tags );

				if( !empty( $new_tags ) )
				{	// insert new tags:
					$query = "INSERT INTO T_items__tag( tag_name ) VALUES ";
					foreach( $new_tags as $tag )
					{
						$query .= '( '.$DB->quote($tag).' ),';
					}
					$query = substr( $query, 0, strlen( $query ) - 1 );
					$DB->query( $query, 'insert new tags' );
				}

				// ASSOC:
				$query = 'INSERT INTO T_items__itemtag( itag_itm_ID, itag_tag_ID )
								  SELECT '.$this->ID.', tag_ID
									  FROM T_items__tag
									 WHERE tag_name IN ('.$DB->quote($this->tags).')';
				$DB->query( $query, 'Make tag associations!' );
			}

			$DB->commit();
		}
	}


	/**
	 * Increment the view count of the item directly in DB (if the item's Author is not $current_User).
	 *
	 * This method serves TWO purposes (that would break if we used dbupdate() ) :
	 *  - Increment the viewcount WITHOUT affecting the lastmodified date and user.
	 *  - Increment the viewcount in an ATOMIC manner (even if several hits on the same Item occur simultaneously).
	 *
	 * This also triggers the plugin event 'ItemViewsIncreased' if the view count has been increased.
	 *
	 * @return boolean Did we increase view count?
	 */
	function inc_viewcount()
	{
		global $Plugins, $DB, $current_User, $Debuglog;

		if( isset( $current_User ) && ( $current_User->ID == $this->creator_user_ID ) )
		{
			$Debuglog->add( 'Not incrementing view count, because viewing user is creator of the item.', 'items' );

			return false;
		}

		$DB->query( 'UPDATE T_items__item
		                SET post_views = post_views + 1
		              WHERE '.$this->dbIDname.' = '.$this->ID );

		// Trigger event that the item's view has been increased
		$Plugins->trigger_event( 'ItemViewsIncreased', array( 'Item' => & $this ) );

		return true;
	}


	/**
	 * Get the User who is assigned to the Item.
	 *
	 * @return User|NULL NULL if no user is assigned.
	 */
	function get_assigned_User()
	{
		if( ! isset($this->assigned_User) && isset($this->assigned_user_ID) )
		{
			$UserCache = & get_UserCache();
			$this->assigned_User = & $UserCache->get_by_ID( $this->assigned_user_ID );
		}

		return $this->assigned_User;
	}


	/**
	 * Get the User who edited the Item last time.
	 *
	 * @return User
	 */
	function & get_lastedit_User()
	{
		if( is_null( $this->lastedit_User ) )
		{
			$UserCache = & get_UserCache();
			$this->lastedit_User = & $UserCache->get_by_ID( $this->lastedit_user_ID, false, false );
		}

		return $this->lastedit_User;
	}


	/**
	 * Get the User who created the Item.
	 *
	 * @return User
	 */
	function & get_creator_User()
	{
		if( is_null($this->creator_User) )
		{
			$UserCache = & get_UserCache();
			$this->creator_User = & $UserCache->get_by_ID( $this->creator_user_ID );
			$this->Author = & $this->creator_User;  // deprecated
		}

		return $this->creator_User;
	}


	/**
	 * Get login of the User who created the Item.
	 *
	 * @return string login
	 */
	function get_creator_login()
	{
		$this->get_creator_User();
		if( is_null( $this->creator_user_login ) && !is_null( $this->creator_User ) )
		{
			$this->creator_user_login = $this->creator_User->login;
		}
		return $this->creator_user_login;
	}


	/**
	 * Execute or schedule post(=after) processing tasks
	 *
	 * Includes notifications & pings
	 *
	 * @param boolean a new post was just created or it was called after an update
	 * @param boolean give more info messages (we want to avoid that when we save & continue editing)
	 */
	function handle_post_processing( $just_created, $verbose = true )
	{
		global $Settings, $Messages, $localtimenow, $posttypes_nopermanentURL;

		if( $just_created )
		{ // we must try to send moderation notifications for the newly created posts
			$already_notified = $this->send_moderation_emails();
		}
		else
		{ // Moderation notifications were not sent, so there are no already notified users 
			$already_notified = NULL;
		}

		$notifications_mode = $Settings->get('outbound_notifications_mode');

		if( $notifications_mode == 'off' )
		{	// Exit silently
			return false;
		}

		if( $this->notifications_status == 'finished' )
		{ // pings have been done before
			if( $verbose )
			{
				$Messages->add( T_('Post had already pinged: skipping notifications...'), 'note' );
			}
			return false;
		}

		if( $this->notifications_status != 'noreq' )
		{ // pings have been done before

			// TODO: Check if issue_date has changed and reschedule
			if( $verbose )
			{
				$Messages->add( T_('Post processing already pending...'), 'note' );
			}
			return false;
		}

		if( $this->status != 'published' )
		{
			// TODO: discard any notification that may be pending!
			if( $verbose )
			{
				$Messages->add( T_('Post not publicly published: skipping notifications...'), 'note' );
			}
			return false;
		}

		if( in_array( $this->ptyp_ID, $posttypes_nopermanentURL ) )
		{
			// TODO: discard any notification that may be pending!
			if( $verbose )
			{
				$Messages->add( T_('This post type doesn\'t need notifications...'), 'note' );
			}
			return false;
		}

		if( $notifications_mode == 'immediate' && strtotime( $this->issue_date ) <= $localtimenow )
		{	// We want to do the post processing immediately:
			// send outbound pings:
			$this->send_outbound_pings( $verbose );

			// Send email notifications now!
			$this->send_email_notifications( false, $already_notified );

			// Record that processing has been done:
			$this->set( 'notifications_status', 'finished' );
		}
		else
		{	// We want asynchronous post processing. This applies to posts with date in future too.
			$Messages->add( T_('Scheduling asynchronous notifications...'), 'note' );

			// CREATE OBJECT:
			load_class( '/cron/model/_cronjob.class.php', 'Cronjob' );
			$edited_Cronjob = new Cronjob();

			// start datetime. We do not want to ping before the post is effectively published:
			$edited_Cronjob->set( 'start_datetime', $this->issue_date );

			// no repeat.

			// name:
			$edited_Cronjob->set( 'name', sprintf( T_('Send notifications for &laquo;%s&raquo;'), strip_tags($this->title) ) );

			// controller:
			$edited_Cronjob->set( 'controller', 'cron/jobs/_post_notifications.job.php' );

			// params: specify which post this job is supposed to send notifications for:
			$edited_Cronjob->set( 'params', array( 'item_ID' => $this->ID ) );

			// Save cronjob to DB:
			$edited_Cronjob->dbinsert();

			// Memorize the cron job ID which is going to handle this post:
			$this->set( 'notifications_ctsk_ID', $edited_Cronjob->ID );

			// Record that processing has been scheduled:
			$this->set( 'notifications_status', 'todo' );
		}

		// Save the new processing status to DB
		$this->dbupdate();

		return true;
	}


	/**
	 * Send post may need moderation notifications for those users who have rights to moderate this post, and would like to receive notifications
	 *
	 * @return array the notified user ids
	 */
	function send_moderation_emails()
	{
		global $Settings, $UserSettings, $DB;

		// Select all users who are post moderators in this Item's blog
		$blog_ID = $this->load_Blog();

		$notify_condition = 'uset_value IS NOT NULL AND uset_value <> "0"';
		if( $Settings->get( 'def_notify_post_moderation' ) )
		{
			$notify_condition = '( uset_value IS NULL OR ( '.$notify_condition.' ) )';
		}

		// Select user_ids with the corresponding item edit permission on this item's blog
		$SQL = new SQL();
		$SQL->SELECT( 'user_ID, IF( grp_perm_blogs = "editall" OR user_ID = blog_owner_user_ID, "all", IF( bloguser_perm_edit > bloggroup_perm_edit, bloguser_perm_edit, bloggroup_perm_edit ) ) as perm' );
		$SQL->FROM( 'T_users' );
		$SQL->FROM_add( 'LEFT JOIN T_blogs ON ( blog_ID = '.$this->blog_ID.' )' );
		$SQL->FROM_add( 'LEFT JOIN T_coll_user_perms ON (blog_advanced_perms <> 0 AND user_ID = bloguser_user_ID AND bloguser_blog_ID = '.$this->blog_ID.' )' );
		$SQL->FROM_add( 'LEFT JOIN T_coll_group_perms ON (blog_advanced_perms <> 0 AND user_grp_ID = bloggroup_group_ID AND bloggroup_blog_ID = '.$this->blog_ID.' )' );
		$SQL->FROM_add( 'LEFT JOIN T_users__usersettings ON uset_user_ID = user_ID AND uset_name = "notify_post_moderation"' );
		$SQL->FROM_add( 'LEFT JOIN T_groups ON grp_ID = user_grp_ID' );
		$SQL->WHERE( $notify_condition );
		$SQL->WHERE_and( '( bloguser_perm_edit IS NOT NULL AND bloguser_perm_edit <> "no" AND bloguser_perm_edit <> "own" )
				OR ( bloggroup_perm_edit IS NOT NULL AND bloggroup_perm_edit <> "no" AND bloggroup_perm_edit <> "own" )
				OR ( grp_perm_blogs = "editall" ) OR ( user_ID = blog_owner_user_ID )' );

		$post_moderators = $DB->get_assoc( $SQL->get() );

		$post_creator_User = & $this->get_creator_User();
		if( isset( $post_moderators[$post_creator_User->ID] ) )
		{ // Don't notify the user who just created this Item
			unset( $post_moderators[$post_creator_User->ID] );
		}

		if( empty( $post_moderators ) )
		{ // There are no moderator users who would like to receive notificaitons
			return NULL;
		}

		// Collect all notified User ID in this array
		$notfied_Users = array();

		$post_creator_level = $post_creator_User->level;
		$UserCache = & get_UserCache();
		$UserCache->load_list( array_keys( $post_moderators ) );

		foreach( $post_moderators as $moderator_ID => $perm )
		{
			$moderator_User = $UserCache->get_by_ID( $moderator_ID );
			if( ( $perm == 'lt' ) && ( $moderator_User->level <= $post_creator_level ) )
			{ // User has no permission moderate this post
				continue;
			}
			if( ( $perm == 'le' ) && ( $moderator_User->level < $post_creator_level ) )
			{ // User has no permission moderate this post
				continue;
			}

			$moderator_user_Group = $moderator_User->get_Group();
			$notify_full = $moderator_user_Group->check_perm( 'post_moderation_notif', 'full' );

			$email_template_params = array(
				'locale'         => $moderator_User->locale,
				'notify_full'    => $notify_full,
				'Item'           => $this,
				'recipient_User' => $moderator_User,
				'notify_type'    => 'moderator',
			);

			locale_temp_switch( $moderator_User->locale );
			$subject = T_('New post may need moderation:').' '.sprintf( T_('%s created a new post on "%s"'), $post_creator_User->login, $this->Blog->get('shortname') );
			// Send the email
			send_mail_to_User( $moderator_ID, $subject, 'post_new', $email_template_params, false, array( 'Reply-To' => $post_creator_User->email ) );
			locale_restore_previous();

			// A send notification email request to the user with $moderator_ID ID was processed
			$notfied_Users[] = $moderator_ID;
		}

		return $notfied_Users;
	}


	/**
	 * Send email notifications to subscribed users
	 *
	 * @todo fp>> shall we notify suscribers of blog were this is in extra-cat? blueyed>> IMHO yes.
	 *
	 * @param boolean Display notification messages or not
	 * @param array Already notified user ids, or NULL if it is not the case
	 */
	function send_email_notifications( $display = true, $already_notified = NULL )
	{
		global $DB, $admin_url, $baseurl, $debug, $Debuglog;

		$edited_Blog = & $this->get_Blog();

		if( ! $edited_Blog->get_setting( 'allow_subscriptions' ) )
		{	// Subscriptions not enabled!
			return;
		}

		if( $display )
		{
			echo "<div class=\"panelinfo\">\n";
			echo '<h3>', T_('Notifying subscribed users...'), "</h3>\n";
		}

		// Create condition to not select already notified modertor users
		$except_users_condition = empty( $already_notified ) ? '' : ' AND sub_user_ID NOT IN ( '.implode( ',', $already_notified ).' )';

		// Get list of users who want to be notfied:
		// TODO: also use extra cats/blogs??
		$sql = 'SELECT DISTINCT sub_user_ID
							FROM T_subscriptions
						WHERE sub_coll_ID = '.$this->get_blog_ID().'
							AND sub_items <> 0'.$except_users_condition;
		$notify_users = $DB->get_col( $sql );

		if( empty( $notify_users ) )
		{ // No-one to notify:
			if( $display )
			{
				echo '<p>', T_('No-one to notify.'), "</p>\n</div>\n";
			}
			return false;
		}

		// Load all users who will be notified
		$UserCache = & get_UserCache();
		$UserCache->load_list( $notify_users );

		/*
		 * We have a list of user IDs to notify:
		 */
		$this->get_creator_User();

		// Load a list with the blocked emails in cache
		load_blocked_emails( $notify_users );

		// Send emails:
		$cache_by_locale = array();
		foreach( $notify_users as $user_ID )
		{
			$notify_User = $UserCache->get_by_ID( $user_ID, false, false );
			if( empty( $notify_User ) )
			{ // skip invalid users
				continue;
			}

			$notify_email = $notify_User->get( 'email' );
			if( empty( $notify_email ) )
			{ // skip users with empty email address
				continue;
			}
			$notify_locale = $notify_User->get( 'locale' );
			$notify_user_Group = $notify_User->get_Group();

			$notify_full = $notify_user_Group->check_perm( 'post_subscription_notif', 'full' );
			if( ! isset($cache_by_locale[$notify_locale]) )
			{ // No message for this locale generated yet:
				locale_temp_switch( $notify_locale );

				$cache_by_locale[$notify_locale]['subject']['short'] = sprintf( T_('%s created a new post in blog "%s"'), $this->creator_User->get( 'login' ), $edited_Blog->get('shortname') );

				$cache_by_locale[$notify_locale]['subject']['full'] = sprintf( T_('[%s] New post: "%s"'), $edited_Blog->get('shortname'), $this->get('title') );

				locale_restore_previous();
			}

			$email_template_params = array(
					'locale'         => $notify_locale,
					'notify_full'    => $notify_full,
					'Item'           => $this,
					'recipient_User' => $notify_User,
					'notify_type'    => 'subscription',
				);

			if( $display ) echo T_('Notifying:').$notify_email."<br />\n";
			if( $debug >= 2 )
			{
				$message_content = mail_template( 'post_new', 'txt', $email_template_params );
				echo "<p>Sending notification to $notify_email:<pre>$message_content</pre>";
			}

			$subject_type = $notify_full ? 'full' : 'short';
			send_mail_to_User( $user_ID, $cache_by_locale[$notify_locale]['subject'][$subject_type], 'post_new', $email_template_params );

			blocked_emails_memorize( $notify_User->email );
		}

		blocked_emails_display();

		if( $display ) echo '<p>', T_('Done.'), "</p>\n</div>\n";
	}


	/**
	 * Send outbound pings for a post
	 *
	 * @param boolean give more info messages (we want to avoid that when we save & continue editing)
	 */
	function send_outbound_pings( $verbose = true )
	{
		global $Plugins, $baseurl, $Messages, $evonetsrv_host, $test_pings_for_real;

		load_funcs('xmlrpc/model/_xmlrpc.funcs.php');

		$this->load_Blog();
		$ping_plugins = array_unique(explode(',', $this->Blog->get_setting('ping_plugins')));

		// init result
		$r = true;

		if( (preg_match( '#^http://localhost[/:]#', $baseurl)
				|| preg_match( '~^\w+://[^/]+\.local/~', $baseurl ) ) /* domain ending in ".local" */
			&& $evonetsrv_host != 'localhost'	// OK if we are pinging locally anyway ;)
			&& empty($test_pings_for_real) )
		{
			if( $verbose )
			{
				$Messages->add( T_('Skipping pings (Running on localhost).'), 'note' );
			}
			return false;
		}
		else foreach( $ping_plugins as $plugin_code )
		{
			$Plugin = & $Plugins->get_by_code($plugin_code);

			if( $Plugin )
			{
				$Messages->add( sprintf(T_('Pinging %s...'), $Plugin->ping_service_name), 'note' );
				$params = array( 'Item' => & $this, 'xmlrpcresp' => NULL, 'display' => false );

				$r = $r && ( $Plugin->ItemSendPing( $params ) );

				if( !empty($params['xmlrpcresp']) )
				{
					if( is_a($params['xmlrpcresp'], 'xmlrpcresp') )
					{
						// dh> TODO: let xmlrpc_displayresult() handle $Messages (e.g. "error", but should be connected/after the "Pinging %s..." from above)
						ob_start();
						xmlrpc_displayresult( $params['xmlrpcresp'], true );
						$Messages->add( ob_get_contents(), 'note' );
						ob_end_clean();
					}
					else
					{
						$Messages->add( $params['xmlrpcresp'], 'note' );
					}
				}
			}
		}
		return $r;
	}


	/**
	 * Callback user for footer()
	 */
	function replace_callback( $matches )
	{
		switch( $matches[1] )
		{
			case 'perm_url':
			case 'item_perm_url':
				return $this->get_permanent_url();

			case 'title':
			case 'item_title':
				return $this->title;

			case 'excerpt':
				return $this->get_excerpt2();

			case 'views':
				return $this->views;

			case 'author':
				return $this->get('t_author');

			case 'author_login':
				return $this->get_creator_login();

			default:
				return $matches[1];
		}
	}

	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @return mixed Value of parameter
	 */
	function get( $parname )
	{
		switch( $parname )
		{
			case 't_author':
				// Text: author
				$this->get_creator_User();
				return $this->creator_User->get( 'preferredname' );

			case 't_assigned_to':
				// Text: assignee
				if( ! $this->get_assigned_User() )
				{
					return '';
				}
				return $this->assigned_User->get( 'preferredname' );

			case 't_status':
				// Text status:
				$post_statuses = get_visibility_statuses();
				return $post_statuses[$this->status];

			case 't_extra_status':
				$ItemStatusCache = & get_ItemStatusCache();
				if( ! ($Element = & $ItemStatusCache->get_by_ID( $this->pst_ID, true, false ) ) )
				{ // No status:
					return '';
				}
				return $Element->get_name();

			case 't_type':
				// Item type (name):
				if( empty($this->ptyp_ID) )
				{
					return '';
				}

				$ItemTypeCache = & get_ItemTypeCache();
				$type_Element = & $ItemTypeCache->get_by_ID( $this->ptyp_ID );
				return $type_Element->get_name();

			case 't_priority':
				return $this->priorities[ $this->priority ];

			case 'pingsdone':
				// Deprecated by fp 2006-08-21
				return ($this->post_notifications_status == 'finished');

			case 'excerpt':
				return $this->get_excerpt2();
		}

		return parent::get( $parname );
	}


	/**
	 * Assign the item to the first category we find in the requested collection
	 *
	 * @param integer $collection_ID
	 */
	function assign_to_first_cat_for_collection( $collection_ID )
	{
		global $DB;

		// Get the first category ID for the collection ID param
		$cat_ID = $DB->get_var( '
				SELECT cat_ID
					FROM T_categories
				 WHERE cat_blog_ID = '.$collection_ID.'
				 ORDER BY cat_ID ASC
				 LIMIT 1' );

		// Set to the item the first category we got
		$this->set( 'main_cat_ID', $cat_ID );
	}


	/**
	 * Get the list of renderers for this Item.
	 * @return array
	 */
	function get_renderers()
	{
		return explode( '.', $this->renderers );
	}


	/**
	 * Get the list of validated renderers for this Item. This includes stealth plugins etc.
	 * @return array List of validated renderer codes
	 */
	function get_renderers_validated()
	{
		if( ! isset($this->renderers_validated) )
		{
			global $Plugins;
			$this->renderers_validated = $Plugins->validate_renderer_list( $this->get_renderers(), array( 'Item' => & $this ) );
		}
		return $this->renderers_validated;
	}


	/**
	 * Add a renderer (by code) to the Item.
	 * @param string Renderer code to add for this item
	 * @return boolean True if renderers have changed
	 */
	function add_renderer( $renderer_code )
	{
		$renderers = $this->get_renderers();
		if( in_array( $renderer_code, $renderers ) )
		{
			return false;
		}

		$renderers[] = $renderer_code;
		$this->set_renderers( $renderers );

		$this->renderers_validated = NULL;
		return true;
	}


	/**
	 * Remove a renderer (by code) from the Item.
	 * @param string Renderer code to remove for this item
	 * @return boolean True if renderers have changed
	 */
	function remove_renderer( $renderer_code )
	{
		$r = false;
		$renderers = $this->get_renderers();
		while( ( $key = array_search( $renderer_code, $renderers ) ) !== false )
		{
			$r = true;
			unset($renderers[$key]);
		}

		if( $r )
		{
			$this->set_renderers( $renderers );
			$this->renderers_validated = NULL;
			//echo 'Removed renderer '.$renderer_code;
		}
		return $r;
	}


	/**
	 * Get a list of item IDs from $MainList and $ItemList, if they are loaded.
	 * This is used for prefetching item related data for the whole list(s).
	 * This will at least return the item's ID itself.
	 * @return array
	 */
	function get_prefetch_itemlist_IDs()
	{
		global $MainList, $ItemList;

		// Add the current ID to the list to prefetch, if it's not in the MainList/ItemList (e.g. featured item).
		$r = array($this->ID);

		if( $MainList )
		{
			$r = array_merge($r, $MainList->get_page_ID_array());
		}
		if( $ItemList )
		{
			$r = array_merge($r, $ItemList->get_page_ID_array());
		}

		return array_unique( $r );
	}


	/**
	 * Get the item tinyslug. If not exists -> create new
	 *
	 * @return string|boolean tinyslug on success, false otherwise
	 */
	function get_tinyslug()
	{
		global $preview;

		$tinyslug_ID = $this->tiny_slug_ID;
		if( $tinyslug_ID != NULL )
		{ // the tiny slug for this item was already created
			$SlugCache = & get_SlugCache();
			$Slug = & $SlugCache->get_by_ID( $tinyslug_ID, false, false );
			return $Slug === false ? false : $Slug->get( 'title' );
		}
		elseif( ( $this->ID > 0 ) && ( ! $preview ) )
		{ // create new tiny Slug for this item
			// Note: This may happen only in case of posts created before the tiny slug was introduced
			global $DB;
			load_funcs( 'slugs/model/_slug.funcs.php' );

			$Slug = new Slug();
			$Slug->set( 'title', getnext_tinyurl() );
			$Slug->set( 'itm_ID', $this->ID );
			$Slug->set( 'type', 'item' );
			$DB->begin();
			if( ! $Slug->dbinsert() )
			{ // Slug dbinsert failed
				$DB->rollback();
				return false;
			}
			$this->set( 'tiny_slug_ID', $Slug->ID );

			// Update Item preserving mod date:
			if( ! $this->dbupdate( false ) )
			{ // Item dbupdate failed
				$DB->rollback();
				return false;
			}
			$DB->commit();

			// update last tinyurl value on database
			// Note: This doesn't have to be part of the above transaction, no problem if it doesn't succeed to update, or if override a previously updated value.
			global $Settings;
			$Settings->set( 'tinyurl', $Slug->get( 'title' ) );
			$Settings->dbupdate();

			return $Slug->get( 'title' );
		}

		return false;
	}


	/**
	 * Get all slugs of this Item, except of tiny slug
	 *
	 * @param string Separator
	 * @return string Slugs list
	 */
	function get_slugs( $separator = ', ' )
	{
		if( empty( $this->ID ) )
		{ // New creating Item
			return $this->get('urltitle');
		}

		global $DB;
		$SQL = new SQL( 'Get slugs of the Item' );
		$SQL->SELECT( 'slug_title, IF( slug_ID = '.intval( $this->canonical_slug_ID ).', 0, slug_ID ) AS slug_order_num' );
		$SQL->FROM( 'T_slug' );
		$SQL->WHERE( 'slug_itm_ID = '.$DB->quote( $this->ID ) );
		if( !empty( $this->tiny_slug_ID ) )
		{ // Exclude tiny slug from list
			$SQL->WHERE_and( 'slug_ID != '.$DB->quote( $this->tiny_slug_ID ) );
		}
		$SQL->ORDER_BY( 'slug_order_num' );
		$slugs = $DB->get_col( $SQL->get() );

		return implode( $separator, $slugs );
	}


	/**
	 * Get the item tiny url
	 * @return string the tiny url on success, empty string otherwise
	 */
	function get_tinyurl()
	{
		if( ( $tinyslug = $this->get_tinyslug() ) == false )
		{
			return '';
		}
		$Blog = & $this->get_Blog();
		return url_add_tail( $Blog->get( 'url'), '/'.$tinyslug );
	}


	/**
	 * Create and return the item tinyurl link.
	 *
	 * @param array Params:
	 *  - 'before': to display before link
	 *  - 'after': to display after link
	 *  - 'text': link text
	 *  - 'title': link title
	 *  - 'class': class name
	 *  - 'style': link style
	 * @return string the tinyurl link on success, empty string otherwise
	 */
	function get_tinyurl_link( $params = array() )
	{
		if( ( $tinyslug = $this->get_tinyslug() ) == false )
		{
			return '';
		}

		if( ! $this->ID )
		{ // preview..
			return false;
		}

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'       => ' ',
				'after'        => ' ',
				'text'         => '#',
				'title'        => '#',
				'class'        => '',
				'style'		   => '',
			), $params );

		if( $params['title'] == '#' )
		{
			$params['title'] = T_( 'This is a tinyurl you can copy/paste into twitter, emails and other places where you need a short link to this post' );
		}
		if( $params['text'] == '#' )
		{
			$params['text'] = $tinyslug;
		}

		$actionurl = $this->get_tinyurl();

		$r = $params['before'];
		$r .= '<a href="'.$actionurl;
		$r .= '" title="'.$params['title'].'"';
		if( !empty( $params['class'] ) ) $r .= ' class="'.$params['class'].'"';
		if( !empty( $params['style'] ) ) $r .= ' style="'.$params['style'].'"';
		$r .=  '>'.$params['text'].'</a>';
		$r .= $params['after'];

		return $r;
	}


	/**
	 * Display the item tinyurl link
	 */
	function tinyurl_link( $params = array() )
	{
		echo $this->get_tinyurl_link( $params );
	}


	/**
	 * Get an url to this item
	 * @param string values:
	 * 		- 'admin_view': url to this item admin interface view
	 * 		- 'public_view': url to this item public interface view (permanent url)
	 * 		- 'edit': url to this item edit screen
	 * @return string the url if exists, empty string otherwise
	 */
	function get_url( $type )
	{
		global $admin_url;
		switch( $type )
		{
			case 'admin_view':
				return $admin_url.'?ctrl=items&amp;blog='.$this->get_blog_ID().'&amp;p='.$this->ID;
			case 'public_view':
				return $this->get_permanent_url();
			case 'edit':
				return $this->get_edit_url();
			default:
				return '';
		}
	}


	/**
	 * Get the number of comments on this item
	 *
	 * @param string the status of counted comments
	 * @return integer the number of comments
	 */
	function get_number_of_comments( $status = NULL )
	{
		global $DB;

		$sql = 'SELECT count( comment_ID )
				FROM T_comments
				WHERE comment_post_ID = '.$this->ID;

		if( $status != NULL )
		{
			$sql .= ' AND comment_status = "'.$status.'"';
		}

		return $DB->get_var( $sql );
	}


	/**
	 * Get the latest Comment on this Item
	 *
	 * @param string the status of the latest comment
	 * @return Comment
	 */
	function & get_latest_Comment( $status = NULL )
	{
		global $DB;

		if( is_null($this->latest_Comment) )
		{
			$SQL = new SQL( 'Get the latest Comment on the Item' );
			$SQL->SELECT( 'comment_ID' );
			$SQL->FROM( 'T_comments' );
			$SQL->WHERE( 'comment_post_ID = '.$DB->quote( $this->ID ) );
			$SQL->ORDER_BY( 'comment_date DESC' );
			$SQL->LIMIT( '1' );

			if( $status != NULL )
			{
				$SQL->WHERE_and( 'comment_status = '.$DB->quote( $status ) );
			}

			if( $comment_ID = $DB->get_var( $SQL->get() ) )
			{
				$CommentCache = & get_CommentCache();
				$this->latest_Comment = & $CommentCache->get_by_ID( $comment_ID );
			}
		}

		return $this->latest_Comment;
	}


	/**
	 * Get the ratings of comments on this item
	 *
	 * @retrun array of [ ratings, active ratings ] for this comment
	 */
	function get_ratings()
	{
		global $DB, $localtimenow;

		// Count each published comments rating grouped by active/expired status and by rating value
		$sql = 'SELECT comment_rating, count( comment_ID ) AS cnt,
					IF( iset_value IS NULL OR iset_value = "" OR TIMESTAMPDIFF(SECOND, comment_date, '.$DB->quote( date2mysql( $localtimenow ) ).') < iset_value, "active", "expired" ) as expiry_status
						FROM T_comments
						LEFT JOIN T_items__item_settings ON iset_item_ID = comment_post_ID AND iset_name = "post_expiry_delay"
						WHERE comment_post_ID = '.$this->ID.' AND comment_status = "published"
						GROUP BY expiry_status, comment_rating
						ORDER BY comment_rating DESC';
		$results = $DB->get_results( $sql );

		// init rating arrays
		$ratings = array();
		$ratings['total'] = 0;
		$ratings['summary'] = 0;
		$ratings['unrated'] = 0;
		$active_ratings = array();
		$active_ratings['total'] = 0;
		$active_ratings['summary'] = 0;
		$active_ratings['unrated'] = 0;

		if( empty( $results ) )
		{ // No rating at all
			$ratings['all_ratings'] = 0;
			$active_ratings['all_ratings'] = 0;
			return array( $ratings, $active_ratings );
		}

		// Init all ratings count to 0
		for( $i=5; $i>=1; $i-- )
		{
			$ratings[$i] = 0;
			$active_ratings[$i] = 0;
		}

		// Count active and overall rating values
		foreach($results as $rating)
		{
			$index = ( $rating->comment_rating == 0 ) ? 'unrated' : $rating->comment_rating;
			$ratings[$index] += $rating->cnt;
			$ratings['total'] += $rating->cnt;
			$ratings['summary'] += ( $rating->cnt * $rating->comment_rating );
			if( $rating->expiry_status == 'active' )
			{ // this rating is not expired yet
				$active_ratings[$index] = $rating->cnt;
				$active_ratings['total'] += $rating->cnt;
				$active_ratings['summary'] += ( $rating->cnt * $rating->comment_rating );
			}
		}

		$ratings['all_ratings'] = $ratings['total'] - $ratings['unrated'];
		$active_ratings['all_ratings'] = $active_ratings['total'] - $active_ratings['unrated'];

		return array( $ratings, $active_ratings );
	}


 	/**
	 * Get a setting.
	 *
	 * @return string|false|NULL value as string on success; NULL if not found; false in case of error
	 */
	function get_setting( $parname )
	{
		$this->load_ItemSettings();

		return $this->ItemSettings->get( $this->ID, $parname );
	}


	/**
	 * Set a setting.
	 *
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function set_setting( $parname, $value, $make_null = false )
	{
		// Make sure item settings are loaded
		$this->load_ItemSettings();

		if( $make_null && empty($value) )
		{
			$value = NULL;
		}

		return $this->ItemSettings->set( $this->ID, $parname, $value );
	}


	/**
	 * Delete a setting.
	 *
	 * @return boolean true, if the value has been set, false if it has not changed.
	 */
	function delete_setting( $parname )
	{
	 	// Make sure item settings are loaded
		$this->load_ItemSettings();

		return $this->ItemSettings->delete( $this->ID, $parname );
	}


	/**
	 * Make sure item settings are loaded.
	 */
	function load_ItemSettings()
	{
		if( ! isset($this->ItemSettings) )
		{
			load_class( 'items/model/_itemsettings.class.php', 'ItemSettings' );
			$this->ItemSettings = new ItemSettings();
		}
	}


	/**
	 * Display location of current Item
	 *
	 * @param string Text before location
	 * @param string Text after location
	 * @param string Separator
	 */
	function location( $before, $after, $separator = ', ' )
	{
		$location = array();
		$location[] = $this->get_city();
		$location[] = $this->get_subregion();
		$location[] = $this->get_region();
		$location[] = $this->get_country();

		// Delete empty elements
		$location = array_filter($location);

		if( !empty( $location ) )
		{	// Display location
			echo $before;

			echo implode( $separator, $location );

			echo $after;
		}
	}


	/**
	 * Get country of current Item
	 *
	 * @param array params
	 * @return string Country name
	 */
	function get_country( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
			), $params );

		$this->load_Blog();
		if( $this->ctry_ID == 0 || ! $this->Blog->country_visible() )
		{	// Country is not defined for current Item OR Counries are hidden
			return;
		}

		load_class( 'regional/model/_country.class.php', 'Country' );
		$CountryCache = & get_CountryCache();

		if( $Country = $CountryCache->get_by_ID( $this->ctry_ID ) )
		{	// Display country name
			$result = $params['before'];

			$result .= $Country->get_name();

			$result .= $params['after'];

			return $result;
		}
	}


	/**
	 * Get region of current Item
	 *
	 * @param array params
	 * @return string Region name
	 */
	function get_region( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
			), $params );

		$this->load_Blog();
		if( $this->rgn_ID == 0 || ! $this->Blog->region_visible() )
		{	// Region is not defined for current Item
			return;
		}

		load_class( 'regional/model/_region.class.php', 'Region' );
		$RegionCache = & get_RegionCache();

		if( $Region = $RegionCache->get_by_ID( $this->rgn_ID ) )
		{	// Display region name
			$result = $params['before'];

			$result .= $Region->get_name();

			$result .= $params['after'];

			return $result;
		}
	}


	/**
	 * Get subregion of current Item
	 *
	 * @param array params
	 * @return string Subregion name
	 */
	function get_subregion( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
			), $params );

		$this->load_Blog();
		if( $this->subrg_ID == 0 || ! $this->Blog->subregion_visible() )
		{	// Subregion is not defined for current Item
			return;
		}

		load_class( 'regional/model/_subregion.class.php', 'Subregion' );
		$SubregionCache = & get_SubregionCache();

		if( $Subregion = $SubregionCache->get_by_ID( $this->subrg_ID ) )
		{	// Display subregion name
			$result = $params['before'];

			$result .= $Subregion->get_name();

			$result .= $params['after'];

			return $result;
		}
	}


	/**
	 * Get city of current Item
	 *
	 * @param array params
	 * @return string City name + postcode
	 */
	function get_city( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before' => '',
				'after'  => '',
				'template' => '$name$ ($postcode$)', // $name$ - City name; $postcode$ - City postcode
			), $params );

		$this->load_Blog();
		if( $this->city_ID == 0 || ! $this->Blog->city_visible() )
		{	// City is not defined for current Item
			return;
		}

		load_class( 'regional/model/_city.class.php', 'City' );
		$CityCache = & get_CityCache();

		if( $City = $CityCache->get_by_ID( $this->city_ID ) )
		{	// Display city info
			$result = $params['before'];

			$city_tamplates = array( '$name$', '$postcode$' );
			$city_data = array( $City->get_name(), $City->get_postcode() );
			$result .= str_replace( $city_tamplates, $city_data, $params['template'] );

			$result .= $params['after'];

			return $result;
		}
	}


	/**
	 * Get item revision
	 *
	 * @param integer Revision ID
	 * @return object Revision
	 */
	function get_revision( $iver_ID = 0 )
	{
		if( $iver_ID > 0 )
		{	// Get revision from archive
			global $DB;

			$revision_SQL = new SQL();
			$revision_SQL->SELECT( '*' );
			$revision_SQL->FROM( 'T_items__version' );
			$revision_SQL->WHERE( 'iver_ID = '.$DB->quote( $iver_ID ) );
			$revision_SQL->WHERE_and( 'iver_itm_ID = '.$DB->quote( $this->ID ) );
			$Revision = $DB->get_row( $revision_SQL->get() );
		}
		else
		{	// Get current version
			$Revision = (object) array(
				'iver_ID'            => 0,
				'iver_edit_datetime' => $this->datemodified,
				'iver_edit_user_ID'  => $this->lastedit_user_ID,
				'iver_status'        => $this->status,
				'iver_title'         => $this->title,
				'iver_content'       => $this->content
			);
		}

		return $Revision;
	}


	/**
	 * Check if item is locked
	 *
	 * @return boolean TRUE - if item is locked
	 */
	function is_locked()
	{
		if( isset( $this->is_locked ) )
		{ // item lock status was already set
			return $this->is_locked;
		}

		// presuppose that all category is locked, we will change this value if only one category is not locked
		$this->is_locked = true;

		// Get item chapters to check lock status, but use cached chapters array instead of db query
		$item_chapters = $this->get_Chapters();
		foreach( $item_chapters as $item_Chapter )
		{ // check if all item categories is locked
			if( !$item_Chapter->lock )
			{ // this category is not locked so the item is not locked either
				$this->is_locked = false;
				break;
			}
		}

		return $this->is_locked;
	}


	/**
	 * Convert inline image tags like [image:123:abc] into HTML img tags
	 *
	 * @param string Source content
	 * @param array Params
	 * @return string Content
	 */
	function render_inline_images( $content, $params = array() )
	{
		if( isset( $params['check_code_block'] ) && $params['check_code_block'] && ( ( stristr( $content, '<code' ) !== false ) || ( stristr( $content, '<pre' ) !== false ) ) )
		{ // Call $this->render_inline_images() on everything outside code/pre:
			$params['check_code_block'] = false;
			$content = callback_on_non_matching_blocks( $content,
				'~<(code|pre)[^>]*>.*?</\1>~is',
				array( $this, 'render_inline_images' ), array( $params ) );
			return $content;
		}

		// No code/pre blocks, replace on the whole thing

		$params = array_merge( array(
				'before'              => '<div>',
				'before_image'        => '<div class="image_block">',
				'before_image_legend' => '<div class="image_legend">',
				'after_image_legend'  => '</div>',
				'after_image'         => '</div>',
				'after'               => '</div>',
				'image_size'          => 'fit-400x320',
				'image_link_to'       => 'original', // Can be 'orginal' (image) or 'single' (this post)
				'limit'               => 1000, // Max # of images displayed
			), $params );

		// Find all matches with image inline tags
		preg_match_all( '/\[image:(\d+)(:?)([^\]]*)\]/i', $content, $images );

		if( !empty( $images[0] ) )
		{	// There are image inline tags in the content
			foreach( $images[0] as $i => $current_link_tag )
			{
				$current_link_ID = (int)$images[1][$i];
				$current_link_caption = empty( $images[2][$i] ) ? '#' : $images[3][$i];

				if( empty( $current_link_ID ) )
				{	// Invalid link ID, Go to next match
					continue;
				}

				if( !isset( $FileList ) )
				{	// Get list of attached files only first time
					$LinkOnwer = new LinkItem( $this );
					$FileList = $LinkOnwer->get_attachment_FileList( $params['limit'], 'inline' );
					if( empty( $FileList ) )
					{	// This Item has not the inline attached files, Exit here
						break;
					}
				}

				if( $File = & $FileList->get_by_field( 'link_ID', $current_link_ID ) )
				{	// File is found by link ID
					if( !$File->exists() )
					{
						global $Debuglog;
						$Debuglog->add(sprintf('File linked to item #%d does not exist (%s)!', $this->ID, $File->get_full_path()), array('error', 'files'));
						break;
					}
					elseif( $File->is_image() )
					{	// Generate the IMG tag with all the alt, title and desc if available
						$link_to = $params['image_link_to']; // Can be 'orginal' (image) or 'single' (this post)
						if( $link_to == 'single' )
						{	// We're linking to the post (displayed on a single post page):
							$link_to = $this->get_permanent_url( $link_to );
							$link_rel = '';
						}
						else
						{	// We're linking to the original image, let lighbox (or clone) quick in:
							$link_rel = 'lightbox[p'.$this->ID.']';	// Make one "gallery" per post.
						}
						// Generate the IMG tag with all the alt, title and desc if available
						$image_tag = $File->get_tag( $params['before_image'], $params['before_image_legend'], $params['after_image_legend'],
								$params['after_image'], $params['image_size'], $link_to, '', $link_rel, '', '', '', $current_link_caption );

						// Replace inline image tag with HTML img tag
						$content = str_replace( $current_link_tag, $image_tag, $content );
					}
					else
					{	// Display error if file is not image
						$content = str_replace( $current_link_tag, '<div class="error">'.sprintf( T_('This file is not image: %s'), $current_link_tag ).'</div>', $content );
					}
				}
			}
		}

		return $content;
	}


	/**
	 * Update field last_touched_ts
	 */
	function update_last_touched_date()
	{
		global $localtimenow;

		$this->set_param( 'last_touched_ts', 'date', date('Y-m-d H:i:s',$localtimenow) );
		$this->dbupdate( false, false, false );
	}
}

?>