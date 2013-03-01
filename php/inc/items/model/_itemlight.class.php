<?php
/**
 * This file implements the ItemLight class.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _itemlight.class.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( '_core/model/dataobjects/_dataobject.class.php', 'DataObject' );


/**
 * ItemLight Class
 *
 * Basically, all we want to achieve here is:
 * - permalinks
 * - last mod dates
 *
 * This object SHOULD NOT be saved.
 *
 * @package evocore
 */
class ItemLight extends DataObject
{
	/**
	 * Publish date ("Y-m-d H:i:s"). This may be in the future.
	 * This should get compared to {@link $localtimenow}.
	 * @var string
	 */
	var $issue_date;
	var $mod_date;

 	var $title;

 	var $excerpt;

 	var $urltitle;

	var $canonical_slug_ID;

	var $tiny_slug_ID;

	/**
	 * External URL the item links to (if any).
	 * @var string
	 */
	var $url;

 	var $ptyp_ID;

	/**
	 * ID of the main category.
	 * Use {@link ItemLight::set()} to set it, since other vars get lazily derived from it.
	 * @var integer
	 */
	var $main_cat_ID = 0;
	/**
	 * @var Chapter
	 * @access protected
	 * @see ItemLight::get_main_Chapter()
	 */
	var $main_Chapter;

	/**
	 * Derived from $main_cat_ID.
	 *
	 * @var integer
	 * @access protected
	 * @see ItemLight::get_blog_ID()
	 */
	var $blog_ID;

	/**
	 * The Blog of the Item (lazy filled, use {@link get_Blog()} to access it.
	 * @access protected
	 * @var Blog
	 */
	var $Blog;


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
	function ItemLight( $db_row = NULL, $dbtable = 'T_items__item', $dbprefix = 'post_', $dbIDname = 'post_ID', $objtype = 'ItemLight',
	               $datecreated_field = '', $datemodified_field = 'datemodified',
	               $creator_field = '', $lasteditor_field = '' )
	{
		global $localtimenow, $default_locale, $current_User;

		// Call parent constructor:
		parent::DataObject( $dbtable, $dbprefix, $dbIDname, $datecreated_field, $datemodified_field,
												$creator_field, $lasteditor_field );

		$this->delete_restrictions = array(
				array( 'table'=>'T_links', 'fk'=>'link_dest_itm_ID', 'msg'=>T_('%d links to source items') ),
				array( 'table'=>'T_items__item', 'fk'=>'post_parent_ID', 'msg'=>T_('%d links to child items') ),
			);

		$this->delete_cascades = array(
				array( 'table'=>'T_links', 'fk'=>'link_itm_ID', 'msg'=>T_('%d links to destination items') ),
				array( 'table'=>'T_postcats', 'fk'=>'postcat_post_ID', 'msg'=>T_('%d links to extra categories') ),
				array( 'table'=>'T_comments', 'fk'=>'comment_post_ID', 'msg'=>T_('%d comments') ),
				array( 'table'=>'T_items__version', 'fk'=>'iver_itm_ID', 'msg'=>T_('%d versions') ),
				array( 'table'=>'T_slug', 'fk'=>'slug_itm_ID', 'msg'=>T_('%d slugs') ),
			);

		$this->objtype = $objtype;

		if( $db_row == NULL )
		{ // New item:
			$this->ID = 0;
			$this->set( 'issue_date', date('Y-m-d H:i:s', $localtimenow) );
		}
		else
		{
			$this->ID = $db_row->$dbIDname;
			$this->datemodified = $db_row->post_datemodified; // Needed for history display
			/* Tblue> Both of the next two member variables are used in
			 *        various places, maybe we should decide on one?! */
			$this->issue_date = $db_row->post_datestart;
			$this->datestart = $db_row->post_datestart;
			$this->mod_date = $db_row->post_datemodified;
			$this->main_cat_ID = $db_row->post_main_cat_ID;
			$this->urltitle = $db_row->post_urltitle;
			$this->canonical_slug_ID = $db_row->post_canonical_slug_ID;
			$this->tiny_slug_ID = $db_row->post_tiny_slug_ID;
			$this->title = $db_row->post_title;
			$this->excerpt = $db_row->post_excerpt;
			$this->ptyp_ID = $db_row->post_ptyp_ID;
			$this->url = $db_row->post_url;
		}
	}


	/**
	 * Is this an Intro post
	 *
	 * @return boolean
	 */
	function is_intro()
	{
		return ($this->ptyp_ID >= 1500 && $this->ptyp_ID <= 1600);
	}


	/**
	 * Is this a featured post (any intro post will return false even if it's checked as "featured")
	 *
	 * @return boolean
	 */
	function is_featured()
	{
		return !( empty($this->featured) || $this->is_intro() );
	}


  /**
	 * Generate a single post link for the item
	 *
	 * @param boolean allow redir to permalink, true | false | 'auto' to prevent redit only if single isn't the current permalink type
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_single_url( $allow_redir = true, $blogurl = '', $glue = '&amp;' )
	{
		$this->get_Blog();

		if( empty( $blogurl ) )
		{
			$blogurl = $this->Blog->gen_blogurl();
		}

		$single_links = $this->Blog->get_setting('single_links');

 		if( !empty( $this->urltitle ) && $single_links != 'param_num' )
		{	// We can and we want to use the url title:
			$urlparam = 'title='.$this->urltitle;
			$urltail = $this->urltitle;
		}
		else
		{
			$urlparam = 'p='.$this->ID;
			$urltail = 'p'.$this->ID;
		}

		switch( $single_links )
		{
			case 'param_num':
			case 'param_title':
				$permalink = url_add_param( $blogurl, $urlparam.$glue.'more=1'.$glue.'c=1'.$glue.'tb=1'.$glue.'pb=1', $glue );
				break;

			case 'y':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/', $this->issue_date).$urltail );
				break;

			case 'ym':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/', $this->issue_date).$urltail );
				break;

			case 'ymd':
				$permalink = url_add_tail( $blogurl, mysql2date('/Y/m/d/', $this->issue_date).$urltail );
				break;

 			case 'subchap':
				$main_Chapter = & $this->get_main_Chapter();
				$permalink = url_add_tail( $blogurl, '/'.$main_Chapter->urlname.'/'.$urltail );
				break;

 			case 'chapters':
				$main_Chapter = & $this->get_main_Chapter();
				$permalink = url_add_tail( $blogurl, '/'.$main_Chapter->get_url_path().$urltail );
				break;

			case 'short':
			default:
				$permalink = url_add_tail( $blogurl, '/'.$urltail );
				break;
		}

		if( $allow_redir == 'auto' )
		{	// We allow redir only if the permalink is already single.
			// In other words: we implicitely allow redir if there is no need to redir!
			// and more useful: we explicitly prevent redir if we know it would take place.
			$allow_redir = ($this->Blog->get_setting( 'permalinks' ) == 'single');
		}

		if( ! $allow_redir )
		{
			$permalink = url_add_param( $permalink, 'redir=no', $glue );
		}

		return $permalink;
	}


	/**
	 * Generate a link to the post in the archives
	 *
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_archive_url( $blogurl = '', $glue = '&amp;' )
	{
		$this->get_Blog();

		if( empty( $blogurl ) )
		{
			$blogurl = $this->Blog->gen_blogurl();
		}

		$permalink = $this->Blog->get_archive_url( $this->issue_date, $glue );

		return $permalink.'#item_'.$this->ID;
	}


	/**
	 * Generate a link to the post in the category
	 *
 	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_chapter_url( $blogurl = '', /* TODO: not used.. */ $glue = '&amp;' )
	{
		if( empty( $blogurl ) )
		{
			$this->get_Blog();
			$blogurl = $this->Blog->gen_blogurl();
		}

		$main_Chapter = & $this->get_main_Chapter();
		$permalink = url_add_tail( $blogurl, '/'.$main_Chapter->get_url_path() );

		return $permalink.'#item_'.$this->ID;
	}


	/**
	 * Generate the permalink for the item.
	 *
	 * Note: Each item has an unique permalink at any given time.
	 * Some admin settings may however change the permalinks for previous items.
	 * Note: This actually only returns the URL, to get a real link, use {@link Item::get_permanent_link()}
	 *
	 * @todo archives modes in clean URL mode
	 *
	 * @param string single, archive, subchap
	 * @param string base url to use
	 * @param string glue between url params
	 */
	function get_permanent_url( $permalink_type = '', $blogurl = '', $glue = '&amp;' )
	{
		global $DB, $cacheweekly, $Settings;

		if( in_array( $this->ptyp_ID, array( 1000, 1500, 1520, 1530, 1570, 1600 ) ) ) // page & intros
		{	// force use of single url:
			$permalink_type = 'single';
		}
		elseif( empty( $permalink_type ) )
		{ // Use default from collection settings:
			$this->get_Blog();
			$permalink_type = $this->Blog->get_setting( 'permalinks' );
		}

		switch( $permalink_type )
		{
			case 'archive':
				return $this->get_archive_url( $blogurl, $glue );

			case 'subchap':
				return $this->get_chapter_url( $blogurl, $glue );

			case 'single':
			default:
				return $this->get_single_url( true, $blogurl, $glue );
		}
	}


	/**
	 * Template function: list all the category names
	 *
	 * @param string Output format for each cat, see {@link format_to_output()}
	 */
	function categories( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'          => ' ',
				'after'           => ' ',
				'include_main'    => true,
				'include_other'   => true,
				'include_external'=> true,
				'before_main'     => '',       // string fo display before the MAIN category,
				'after_main'      => '',       // string fo display after the MAIN category
				'before_other'    => '',       // string fo display before OTHER categories
				'after_other'     => '',       // string fo display after OTHER categories
				'before_external' => '<em>',   // string fo display before EXTERNAL categories
				'after_external'  => '</em>',  // string fo display after EXTERNAL categories,
				'separator'       => ', ',
				'link_categories' => true,
				'link_title'      => '#',
				'format'          => 'htmlbody',
			), $params );


		if( $params['link_title'] == '#' )
		{ /* TRANS: When the categories for a specific post are displayed, the user can click
					on these cats to browse them, this is the default href title displayed there */
			$params['link_title'] = T_('Browse category');
		}

		$categoryNames = array();
		foreach( $this->get_Chapters() as $Chapter )
		{
			$cat_name = $Chapter->dget( 'name' );

			if( $params['link_categories'] )
			{ // we want to display links
				$lBlog = & $Chapter->get_Blog();
				$cat_name = '<a href="'.$Chapter->get_permanent_url().'" title="'.htmlspecialchars($params['link_title']).'">'.$cat_name.'</a>';
			}

			if( $Chapter->ID == $this->main_cat_ID )
			{ // We are displaying the main cat!
				if( !$params['include_main'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_main'].$cat_name.$params['after_main'];
			}
			elseif( $Chapter->blog_ID == $this->blog_ID )
			{ // We are displaying another cat in the same blog
				if( !$params['include_other'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_other'].$cat_name.$params['after_other'];
			}
			else
			{ // We are displaying an external cat (in another blog)
				if( !$params['include_external'] )
				{ // ignore main cat !!!
					continue;
				}
				$cat_name = $params['before_external'].$cat_name.$params['after_external'];
			}

			$categoryNames[] = $cat_name;
		}

		echo $params['before'];
		echo format_to_output( implode( $params['separator'], $categoryNames ), $params['format'] );
 		echo $params['after'];
	}


	/**
	 * Template function: display main category name
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function main_category( $format = 'htmlbody' )
	{
		$Chapter = & $this->get_main_Chapter();
		$Chapter->disp( 'name', $format );
	}


	/**
	 * Get list of Chapter objects.
	 *
	 * @return array of {@link Chapter chapters} (references)
	 */
	function get_Chapters()
	{
		global $cache_postcats;

		$ChapterCache = & get_ChapterCache();

		// Load cache for category associations with current posts
		// TODO: dh> This fails, if $postIDlist is not set! (e.g. in admin)
		cat_load_postcats_cache();

		if( isset($cache_postcats[$this->ID]) )
		{ // dh> may not be set! (demo logs)
			$categoryIDs = $cache_postcats[$this->ID];
		}
		else $categoryIDs = array();

		$chapters = array();
		foreach( $categoryIDs as $cat_ID )
		{
			$chapters[] = & $ChapterCache->get_by_ID( $cat_ID );
		}

		return $chapters;
	}


	/**
	 * Get the main Chapter.
	 *
	 * @return Chapter
	 */
	function & get_main_Chapter()
	{
		if( is_null($this->main_Chapter) )
		{
			$ChapterCache = & get_ChapterCache();
			/**
			 * @var Chapter
			 */
			$this->main_Chapter = & $ChapterCache->get_by_ID( $this->main_cat_ID );
		}
		return $this->main_Chapter;
	}


	/**
	 * Get the blog ID of this item (derived from main chapter).
	 * @return integer
	 */
	function get_blog_ID()
	{
		if( is_null($this->blog_ID) )
		{
			$main_Chapter = & $this->get_main_Chapter();
			$this->blog_ID = $main_Chapter->blog_ID;
		}
		return $this->blog_ID;
	}


	/**
	 * returns issue date (datetime) of Item
	 * @param array
	 *   - 'before'
	 *   - 'after'
	 *   - 'date_format': Date format
	 *   - 'use_GMT': Use GMT/UTC date
	 */
	function get_issue_date( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'date_format' => '#',
				'use_GMT'     => false,
			), $params );

		if( $params['date_format'] == '#' )
		{
			$params['date_format'] = locale_datefmt();
		}

		return $params['before'].mysql2date( $params['date_format'], $this->issue_date, $params['use_GMT'] ).$params['after'];
	}


	/**
	 * Template function: display issue date (datetime) of Item
	 * @see get_issue_date()
	 */
	function issue_date( $params = array() )
	{
		echo $this->get_issue_date( $params );
	}


	/**
	 * Template function: display issue time (datetime) of Item
	 * @param array
	 *   - 'time_format': Time format
	 *   - ... see {@link get_issue_date()}
	 * @see get_issue_date()
	 */
	function issue_time( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'time_format' => '#',
			), $params );

		if( !isset($params['date_format']) )
		{
			$params['date_format'] = $params['time_format'];
		}

		if( $params['date_format'] == '#' )
		{
			$params['date_format'] = locale_timefmt();
		}

		echo $this->get_issue_date( $params );
	}


	/**
	 * Template function: display locale for item
	 */
	function lang()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template function: display locale for item
	 */
	function locale()
	{
		$this->disp( 'locale', 'raw' );
	}


	/**
	 * Template tag
	 */
	function locale_flag( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => ' ',
				'after'       => ' ',
				'collection'  => 'h10px',
				'format'      => 'htmlbody',
				'class'       => 'flag',
				'align'       => '',
			), $params );

		echo $params['before'];
		echo locale_flag( $this->locale, $params['collection'], $params['class'], $params['align'] );
		echo $params['after'];
	}


	/**
	 * Template function: Temporarily switch to this post's locale
	 */
	function locale_temp_switch()
	{
		locale_temp_switch( $this->locale );
	}


	/**
	 * Template function: display language name for item
	 *
	 * @param string Output format, see {@link format_to_output()}
	 */
	function language( $format = 'htmlbody' )
	{
		global $locales;
		$locale = $locales[ $this->locale ];
		echo format_to_output( $locale['name'], $format );
	}


	/**
	 * Template function: display last mod date (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default date format
	 * @param boolean true if you want GMT
	 */
	function mod_date( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_datefmt(), $this->mod_date, $useGM );
		else
			echo mysql2date( $format, $this->mod_date, $useGM );
	}


	/**
	 * Template function: display last mod time (datetime) of Item
	 *
	 * @param string date/time format: leave empty to use locale default time format
	 * @param boolean true if you want GMT
	 */
	function mod_time( $format = '', $useGM = false )
	{
		if( empty($format) )
			echo mysql2date( locale_timefmt(), $this->mod_date, $useGM );
		else
			echo mysql2date( $format, $this->mod_date, $useGM );
	}


	/**
	 * Check if current item has at least one category, which belongs to the given blog
	 *
	 * @param integer the given blog ID
	 * @return boolean true if there is at least one category in the given blog, false otherwise
	 */
	function is_part_of_blog( $blog_ID )
	{
		global $DB;
		$cat_count = $DB->get_var( '
				SELECT count( cat_ID )
				FROM T_categories, T_postcats
				WHERE
					T_categories.cat_ID = T_postcats.postcat_cat_ID
					and T_categories.cat_blog_ID = '.$blog_ID.'
					and T_postcats.postcat_post_ID = '.$this->ID
		);

		// $cat_count>0 means that this item has at least one category that belongs to the target blog.
		return $cat_count > 0;
	}


	/**
	 * Check if cross post navigation should stay in the current blog or not.
	 * Also check that this item has at least one category that belongs to the given blog.
	 * If current blog is the same as item blog then, this function will return false, because no need to check.
	 *
	 * @param string 'auto' value means this call needs to decide to stay in the current blog or not. Every other value will return false!
	 * @param integer the given "current" blog ID (its usually the current blog id)
	 * @return boolean true if we have to stay in the current blog, false otherwise
	 */
	function check_cross_post_nav( $target_blog, $blog_ID )
	{
		global $cross_post_nav_in_same_blog;

		if( $target_blog != 'auto' )
		{ // target_blog is not set to auto, we have to navigate to the item's main cat's blog.
			return false;
		}

		$this->get_Blog();
		if( $this->Blog->ID == $blog_ID )
		{ // item's blog is the same as target blog
			return false;
		}

		if( ! $cross_post_nav_in_same_blog )
		{ // we have to navigate to the item's main cat's blog.
			return false;
		}

		// return true if current item has at least one category, which belongs to the corresponding blog, false otherwise
		return $this->is_part_of_blog( $blog_ID );
	}


	/**
	 * Template function: display permalink for item
	 *
	 * Note: This actually only outputs the URL, to display a real link, use {@link Item::permanent_link()}
	 *
	 * @param string 'post', 'archive#id' or 'archive#title'
	 * @param string url to use
	 */
	function permanent_url( $mode = '', $blogurl='' )
	{
		echo $this->get_permanent_url( $mode, $blogurl );
	}


	/**
	 * Returns a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::get_permanent_url()}
	 *
	 * @param string link text or special value: '#', '#icon#', '#text#', '#title#' '... $title$ ...'
	 * @param string link title
	 * @param string class name
	 */
	function get_permanent_link( $text = '#', $title = '#', $class = '', $target_blog = '' )
	{
		global $current_User, $Blog;

		switch( $text )
		{
			case '#':
				$text = get_icon( 'permalink', 'imgtag', array('class'=>'icon') ).T_('Permalink');
				break;

			case '#icon#':
				$text = get_icon( 'permalink', 'imgtag', array('class'=>'icon') );
				break;

			case '#text#':
				$text = T_('Permalink');
				break;

			case '#title#':
				$text = format_to_output( $this->title );
				break;
		}

		if( $title == '#' ) $title = T_('Permanent link to full entry');

		$blogurl = '';
		$permalink_type = '';
		if( !empty($Blog) && $this->check_cross_post_nav( $target_blog, $Blog->ID ) )
		{
			$permalink_type = $Blog->get_setting( 'permalinks' );
			$blogurl = $Blog->gen_blogurl();
		}

		$url = $this->get_permanent_url( $permalink_type, $blogurl );

		// Display as link
		$r = '<a href="'.$url.'" title="'.$title.'"';
		if( !empty( $class ) ) $r .= ' class="'.$class.'"';
		$r .= '>'.str_replace( '$title$', format_to_output( $this->title ), $text ).'</a>';

		return $r;
	}


	/**
	 * Displays a permalink link to the Item
	 *
	 * Note: If you only want the permalink URL, use {@link Item::permanent_url()}
	 *
	 * @param string link text or special value:
	 * @param string link title
	 * @param string class name
	 */
	function permanent_link( $params = array() )
	{
		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'text'        => '#',	// possible special values: '#', '#icon#', '#text#', '#title#'
				'title'       => '#',
				'class'       => '',
				'target_blog' => '',
			//	'format'      => 'htmlbody',
			), $params );

		$link = $this->get_permanent_link( $params['text'], $params['title'], $params['class'], $params['target_blog'] );

		if( !empty( $link ) )
		{
			echo $params['before'];
			echo $link;
			echo $params['after'];
		}
	}


	/**
	 * Template function: display title for item and link to related URL
	 */
	function title( $params = array() )
	{
		$params = array_merge( array(
				'target_blog'  => 'auto',
			), $params );
		echo $this->get_title($params);
	}


	/**
	 * Get "nice" title of the Item
	 * @return string
	 */
	function get_title( $params = array() )
	{
		global $ReqURL, $Blog;

		// Make sure we are not missing any param:
		$params = array_merge( array(
				'before'      => '',
				'after'       => '',
				'format'      => 'htmlbody',
				'link_type'   => '#',
				'max_length'  => '',
				'target_blog' => '',
			), $params );

		$blogurl = '';
		if( !empty($Blog) && $this->check_cross_post_nav( $params['target_blog'], $Blog->ID ) )
		{
			$blogurl = $Blog->gen_blogurl();
		}

		$title = format_to_output( $this->title, $params['format'] );

		if( $params['max_length'] != '' )
		{	// Crop long title
			$title = strmaxlen( $title, intval($params['max_length']) );
		}

		if( empty( $title ) )
		{
			return;
		}

		if( $params['link_type'] == '#' )
		{	// Use default link type from settings:
			if( $this->is_intro() )
			{	// This is an intro, do not link title by default:
				$params['link_type'] = 'none';
			}
			elseif( is_same_url( $this->get_permanent_url( '', $blogurl, '&' ), $ReqURL ) )
			{	// We are on the single url already:
				$params['link_type'] = 'none';
			}
			else if( $this->ptyp_ID == 3000 )
			{	// tblue> This is a sidebar link, link to its "link to" URL by default:
				$params['link_type'] = 'linkto_url';
			}
			else
			{	// This is a normal post: use default link strategy from Blog settings:
				$this->get_Blog();
				$params['link_type'] = $this->Blog->get_setting( 'title_link_type' );
			}
		}

		switch( $params['link_type'] )
		{
			case 'auto':
				$url = ( empty($this->url) ? $this->get_permanent_url() : $this->url );
				break;

			case 'permalink':
				$url = $this->get_permanent_url( '', $blogurl );
				break;

			case 'linkto_url':
				$url = $this->url;
				break;

			case 'admin_view':
				$url = '?ctrl=items&amp;blog='.$this->get_blog_ID().'&amp;p='.$this->ID;
				break;

			case 'none':
			default:
		}

		$r = $params['before'];
		if( !empty($url) )
		{
			$r .= '<a href="'.$url.'">'.$title.'</a>';
		}
		else
		{
			$r .= $title;
		}
		$r .= $params['after'];
		return $r;
	}


	/**
	 * Template function: display type of item
	 *
	 * @param string
	 * @param string
	 * @param string Output format, see {@link format_to_output()}
	 */
	function type( $before = '', $after = '', $format = 'htmlbody' )
	{
		$ItemTypeCache = & get_ItemTypeCache();
		$Element = & $ItemTypeCache->get_by_ID( $this->ptyp_ID, true, false );
		if( !$Element )
		{ // No status:
			return;
		}

		$type_name = $Element->get('name');

		if( $format == 'raw' )
		{
			$this->disp( $type_name, 'raw' );
		}
		else
		{
			echo $before.format_to_output( $type_name, $format ).$after;
		}
	}


	/**
	 * Template function: get excerpt
	 *
	 * @todo do we want excerpts in itemLight or not?
	 *       dh> I'd say "no". I have added excerpt_autogenerated
	 *           only to Item now. But makes sense in the same class.
	 *           update_excerpt is also on in Item.
	 *  fp> the issue is about display only. of course we don't want update code in ItemLight.
	 *  The question is typically about being able to display excerpts in ItemLight list
	 *  sitemaps, feed, recent posts, post widgets where the exceprt might be used as a title, etc.
	 *
	 * @param string filename to use to display more
	 * @return string
	 */
	function get_excerpt( $format = 'htmlbody' )
	{
		// Character conversions
		return format_to_output( $this->excerpt, $format );
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
			case 'main_cat_ID':
				$r = $this->set_param( 'main_cat_ID', 'number', $parvalue, false );
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				// Invalidate derived property:
				$this->blog_ID = NULL;
				unset($this->main_Chapter); // dereference
				$this->main_Chapter = NULL;
				unset($this->Blog);
				$this->Blog = NULL;
				return $r;

			case 'extra_cat_IDs':
				// ARRAY! We do not record this change (yet)
				$this->extra_cat_IDs = $parvalue;
				// make sure main cat is in extracat list and there are no duplicates
				$this->extra_cat_IDs[] = $this->main_cat_ID;
				$this->extra_cat_IDs = array_unique( $this->extra_cat_IDs );
				break;

			case 'issue_date':
			case 'datestart':
				$this->issue_date = $parvalue;
				return $this->set_param( 'datestart', 'date', $parvalue, false );

			case 'ptyp_ID':
			case 'canonical_slug_ID':
			case 'tiny_slug_ID':
				return $this->set_param( $parname, 'number', $parvalue, true );

			default:
				return $this->set_param( $parname, 'string', $parvalue, $make_null );
		}
	}


	/**
	 * Get the Blog object for the Item.
	 *
	 * @return Blog
	 */
	function & get_Blog()
	{
		if( is_null($this->Blog) )
		{
			$this->load_Blog();
		}

		return $this->Blog;
	}


	/**
	 * Load the Blog object for the Item, without returning it.
	 *
	 * This is needed for {@link Results} object callbacks.
	 */
	function load_Blog()
	{
		if( is_null($this->Blog) )
		{
			$BlogCache = & get_BlogCache();
			$this->Blog = & $BlogCache->get_by_ID( $this->get_blog_ID() );
		}
	}

}


/*
 * $Log: _itemlight.class.php,v $
 */
?>