<?php
/**
 * This file implements Post handling functions.
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
 * @author cafelog (team)
 * @author blueyed: Daniel HAHLER.
 * @author fplanque: Francois PLANQUE.
 * @author tswicegood: Travis SWICEGOOD.
 * @author vegarg: Vegar BERG GULDAL.
 *
 * @version $Id: _item.funcs.php 1201 2012-04-07 04:03:31Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'items/model/_itemlight.class.php', 'ItemLight' );
load_class( 'items/model/_itemlist.class.php', 'ItemList2' );

/**
 * Prepare the MainList object for displaying skins.
 *
 * @param integer max # of posts on the page
 */
function init_MainList( $items_nb_limit )
{
	global $MainList;
	global $Blog;
	global $timestamp_min, $timestamp_max;
	global $preview;
	global $disp;
	global $postIDlist, $postIDarray;

	$MainList = new ItemList2( $Blog, $timestamp_min, $timestamp_max, $items_nb_limit );	// COPY (FUNC)

	if( ! $preview )
	{
		if( $disp == 'page' )
		{	// Get  pages:
			$MainList->set_default_filters( array(
					'types' => '1000',		// pages
					// 'types' => '1000,1500,1520,1530,1570',		// pages and intros (intros should normally never be called)
				) );
		}
		// else: we are either in single or in posts mode

		// pre_dump( $MainList->default_filters );
		$MainList->load_from_Request( false );
		// pre_dump( $MainList->filters );
		// echo '<br/>'.( $MainList->is_filtered() ? 'filtered' : 'NOT filtered' );
		// $MainList->dump_active_filters();

		// Run the query:
		$MainList->query();

		// Old style globals for category.funcs:
		$postIDlist = $MainList->get_page_ID_list();
		$postIDarray = $MainList->get_page_ID_array();
	}
	else
	{	// We want to preview a single post, we are going to fake a lot of things...
		$MainList->preview_from_request();

		// Legacy for the category display
		$cat_array = array();
	}

	param( 'more', 'integer', 0, true );
	param( 'page', 'integer', 1, true ); // Post page to show
	param( 'c',    'integer', 0, true ); // Display comments?
	param( 'tb',   'integer', 0, true ); // Display trackbacks?
	param( 'pb',   'integer', 0, true ); // Display pingbacks?
}


/**
 * Return an Item if an Intro or a Featured item is available for display in current disp.
 *
 * @return Item
 */
function & get_featured_Item()
{
	global $Blog;
	global $timestamp_min, $timestamp_max;
	global $disp, $disp_detail, $MainList;
	global $featured_displayed_item_ID;

	if( $disp != 'posts' || !isset($MainList) )
	{	// If we're not displaying postS, don't display a feature post on top!
		$Item = NULL;
		return $Item;
	}

	$FeaturedList = new ItemList2( $Blog, $timestamp_min, $timestamp_max, 1 );

	$FeaturedList->set_default_filters( $MainList->filters );

	if( ! $MainList->is_filtered() )
	{	// Restrict to 'main' and 'all' intros:
		$restrict_to_types = '1500,1600';
	}
	else
	{	// Filtered...
		// echo $disp_detail;
		switch( $disp_detail )
		{
			case 'posts-cat':
				$restrict_to_types = '1520,1600';
				break;
			case 'posts-subcat':
				$restrict_to_types = '1570,1600';
				break;
			case 'posts-tag':
				$restrict_to_types = '1530,1570,1600';
				break;
			default:
				$restrict_to_types = '1570,1600';
		}
	}

	$FeaturedList->set_filters( array(
			'types' => $restrict_to_types,
		), false /* Do NOT memorize!! */ );
	// pre_dump( $FeaturedList->filters );
	// Run the query:
	$FeaturedList->query();

	if( $FeaturedList->result_num_rows == 0 )
	{ // No Intro page was found, try to find a featured post instead:

		$FeaturedList->reset();

		$FeaturedList->set_filters( array(
				'featured' => 1,  // Featured posts only (TODO!)
				// Types will already be reset to defaults here
			), false /* Do NOT memorize!! */ );

		// Run the query:
		$FeaturedList->query();
	}

	$Item = $FeaturedList->get_item();

	// Memorize that ID so that it can later be filtered out normal display:
	$featured_displayed_item_ID = $Item ? $Item->ID : NULL;

	return $Item;
}


/**
 * Validate URL title (slug) / Also used for category slugs
 *
 * Using title as a source if url title is empty.
 * We allow up to 200 chars (which is ridiculously long) for WP import compatibility.
 * New slugs will be cropped to 5 words so the URLs are not too long.
 *
 * @param string url title to validate
 * @param string real title to use as a source if $urltitle is empty (encoded in $evo_charset)
 * @param integer ID of post
 * @param boolean Query the DB, but don't modify the URL title if the title already exists (Useful if you only want to alert the pro user without making changes for him)
 * @param string The prefix of the database column names (e. g. "post_" for post_urltitle)
 * @param string The name of the post ID column
 * @param string The name of the DB table to use
 * @param NULL|string The post locale or NULL if there is no specific locale.
 * @return string validated url title
 */
function urltitle_validate( $urltitle, $title, $post_ID = 0, $query_only = false,
									$dbSlugFieldName = 'post_urltitle', $dbIDname = 'post_ID',
									$dbtable = 'T_items__item', $post_locale = NULL )
{
	global $DB, $Messages;

	$urltitle = trim( $urltitle );
	$orig_title = $urltitle;

	if( empty( $urltitle ) )
	{
		if( ! empty($title) )
			$urltitle = $title;
		else
			$urltitle = 'title';
	}

	// echo 'starting with: '.$urltitle.'<br />';

	// Replace special chars/umlauts, if we can convert charsets:
	load_funcs('locales/_charset.funcs.php');
	$urltitle = replace_special_chars($urltitle, $post_locale);

	// Make everything lowercase and use trim again after replace_special_chars
	$urltitle = strtolower( trim ( $urltitle ) );

	if( empty( $urltitle ) )
	{
		$urltitle = 'title';
	}

	// Leave only first 5 words in order to get a shorter URL
	// (which is generally accepted as a better practice)
	// User can manually enter a very long URL if he wants
	$slug_changed = param( 'slug_changed' );
	if( $slug_changed == 0 )
	{ // this should only happen when the slug is auto generated
		$title_words = array();
		$title_words = explode( '-', $urltitle );
		$count_of_words = 5;
		if( count($title_words) > $count_of_words )
		{
			$urltitle = '';
			for( $i = 0; $i < $count_of_words; $i++ )
			{
				$urltitle .= $title_words[$i].'-';
			}
			//delete last '-'
			$urltitle = substr( $urltitle, 0, strlen($urltitle) - 1 );
		}

		// echo 'leaving 5 words: '.$urltitle.'<br />';
	}

	// Normalize to 200 chars + a number
	preg_match( '/^(.*?)((-|_)+([0-9]+))?$/', $urltitle, $matches );
	$urlbase = substr( $matches[1], 0, 200 );
	// strip a possible dash at the end of the URL title:
	$urlbase = rtrim( $urlbase, '-' );
	$urltitle = $urlbase;
	if( ! empty( $matches[4] ) )
	{
		$urltitle .= '-'.$matches[4];
	}

	if( !$query_only )
	{
		// TODO: dh> this might get used to utilize the SlugCache instead of the processing below.
		#if( $post_ID && $dbtable == 'T_slug' )
		#{
		#	$existing_Slug = get_SlugCache()->get_by_name($urltitle, false, false);
		#	if( $existing_Slug )
		#	{
		#		$slug_field_name = preg_replace('~^slug_~', '', $dbIDname);
		#		if( $existing_Slug->get($slug_field_name) == $urltitle )
		#		{
		#			// OK
		#		}
		#	}
		#}
		// CHECK FOR UNIQUENESS:
		// Find all occurrences of urltitle-number in the DB:
		$sql = 'SELECT '.$dbSlugFieldName.', '.$dbIDname.'
						  FROM '.$dbtable.'
						 WHERE '.$dbSlugFieldName." REGEXP '^".$urlbase."(-[0-9]+)?$'";
		$exact_match = false;
		$highest_number = 0;
		$use_existing_number = NULL;
		foreach( $DB->get_results( $sql, ARRAY_A ) as $row )
		{
			$existing_urltitle = $row[$dbSlugFieldName];
			// echo "existing = $existing_urltitle <br />";
			if( $existing_urltitle == $urltitle && $row[$dbIDname] != $post_ID )
			{ // We have an exact match, we'll have to change the number.
				$exact_match = true;
			}
			if( preg_match( '/-([0-9]+)$/', $existing_urltitle, $matches ) )
			{ // This one has a number, we extract it:
				$existing_number = (int)$matches[1];

				if( ! isset($use_existing_number) && $row[$dbIDname] == $post_ID )
				{ // if there is a numbered entry for the current ID, use this:
					$use_existing_number = $existing_number;
				}

				if( $existing_number > $highest_number )
				{ // This is the new high
					$highest_number = $existing_number;
				}
			}
		}
		// echo "highest existing number = $highest_number <br />";

		if( $exact_match && !$query_only )
		{ // We got an exact (existing) match, we need to change the number:
			$number = $use_existing_number ? $use_existing_number : ($highest_number+1);
			$urltitle = $urlbase.'-'.$number;
		}
	}

	// echo "using = $urltitle <br />";

	if( !empty($orig_title) && $urltitle != $orig_title )
	{
		$Messages->add( sprintf(T_('Warning: the URL slug has been changed to &laquo;%s&raquo;.'), $urltitle ), 'note' );
	}

	return $urltitle;
}


/**
 * if global $postdata was not set it will be
 */
function get_postdata($postid)
{
	global $DB, $postdata, $show_statuses;

	if( !empty($postdata) && $postdata['ID'] == $postid )
	{ // We are asking for postdata of current post in memory! (we're in the b2 loop)
		// Already in memory! This will be the case when generating permalink at display
		// (but not when sending trackbacks!)
		// echo "*** Accessing post data in memory! ***<br />\n";
		return($postdata);
	}

	// echo "*** Loading post data! ***<br>\n";
	// We have to load the post
	$sql = 'SELECT post_ID, post_creator_user_ID, post_datestart, post_datemodified, post_status, post_content, post_title,
											post_main_cat_ID, cat_blog_ID ';
	$sql .= ', post_locale, post_url, post_wordcount, post_comment_status, post_views ';
	$sql .= '	FROM T_items__item
					 INNER JOIN T_categories ON post_main_cat_ID = cat_ID
					 WHERE post_ID = '.$postid;
	// Restrict to the statuses we want to show:
	// echo $show_statuses;
	// fplanque: 2004-04-04: this should not be needed here. (and is indeed problematic when we want to
	// get a post before even knowning which blog it belongs to. We can think of putting a security check
	// back into the Item class)
	// $sql .= ' AND '.statuses_where_clause( $show_statuses );

	// echo $sql;

	if( $myrow = $DB->get_row( $sql ) )
	{
		$mypostdata = array (
			'ID' => $myrow->post_ID,
			'Author_ID' => $myrow->post_creator_user_ID,
			'Date' => $myrow->post_datestart,
			'Status' => $myrow->post_status,
			'Content' => $myrow->post_content,
			'Title' => $myrow->post_title,
			'Category' => $myrow->post_main_cat_ID,
			'Locale' => $myrow->post_locale,
			'Url' => $myrow->post_url,
			'Wordcount' => $myrow->post_wordcount,
			'views' => $myrow->post_views,
			'comment_status' => $myrow->post_comment_status,
			'Blog' => $myrow->cat_blog_ID,
			);

		// Caching is particularly useful when displaying a single post and you call single_post_title several times
		if( !isset( $postdata ) ) $postdata = $mypostdata;	// Will save time, next time :)

		return($mypostdata);
	}

	return false;
}





// @@@ These aren't template tags, do not edit them


/**
 * Returns the number of the words in a string, sans HTML
 *
 * @todo dh> Test if http://de3.php.net/manual/en/function.str-word-count.php#85579 works better/faster
 *           (only one preg_* call and no loop).
 *
 * @param string The string.
 * @return integer Number of words.
 *
 * @internal PHP's str_word_count() is not accurate. Inaccuracy reported
 *           by sam2kb: http://forums.b2evolution.net/viewtopic.php?t=16596
 */
function bpost_count_words( $str )
{
	global $evo_charset;

	$str = trim( strip_tags( $str ) );

	// Note: The \p escape sequence is available since PHP 4.4.0 and 5.1.0.
	if( @preg_match( '|\pL|u', 'foo' ) === false )
	{
		return str_word_count( $str );
	}

	$count = 0;

	foreach( preg_split( '#\s+#', convert_charset( $str, 'UTF-8', $evo_charset ), -1,
							PREG_SPLIT_NO_EMPTY ) as $word )
	{
		if( preg_match( '#\pL#u', $word ) )
		{
			++$count;
		}
	}

	return $count;
}


/**
 * Construct the where clause to limit retrieved posts on their status
 *
 * @param Array statuses of posts we want to get
 */
function statuses_where_clause( $show_statuses = '', $dbprefix = 'post_', $req_blog = NULL )
{
	global $current_User, $blog;

	if( is_null($req_blog ) )
	{
		global $blog;
		$req_blog = $blog;
	}

	if( empty($show_statuses) )
		$show_statuses = array( 'published', 'protected', 'private' );

	$where = ' ( ';
	$or = '';

	if( ($key = array_search( 'private', $show_statuses )) !== false )
	{ // Special handling for Private status:
		unset( $show_statuses[$key] );
		if( is_logged_in() )
		{ // We need to be logged in to have a chance to see this:
			$where .= $or.' ( '.$dbprefix."status = 'private' AND ".$dbprefix.'creator_user_ID = '.$current_User->ID.' ) ';
			$or = ' OR ';
		}
	}

	if( $key = array_search( 'protected', $show_statuses ) )
	{ // Special handling for Protected status:
		if( (!is_logged_in())
			|| ($req_blog == 0) // No blog specified (ONgsb)
			|| (!$current_User->check_perm( 'blog_ismember', 1, false, $req_blog )) )
		{ // we are not allowed to see this if we are not a member of the current blog:
			unset( $show_statuses[$key] );
		}
	}

	// Remaining statuses:
	$other_statuses = '';
	$sep = '';
	foreach( $show_statuses as $other_status )
	{
		$other_statuses .= $sep.'\''.$other_status.'\'';
		$sep = ',';
	}
	if( strlen( $other_statuses ) )
	{
		$where .= $or.$dbprefix.'status IN ('. $other_statuses .') ';
	}

	$where .= ') ';

	// echo $where;
	return $where;
}


/**
 * Compose screen: display attachment iframe
 *
 * @param Form
 * @param boolean
 * @param Item
 * @param Blog
 */
function attachment_iframe( & $Form, $creating, & $edited_Item, & $Blog, $iframe_name = NULL )
{
	global $admin_url, $dispatcher;
	global $current_User;
	global $Settings;

	if( ! isset($GLOBALS['files_Module']) )
		return;

	$fieldset_title = T_('Images &amp; Attachments').get_manual_link('post_attachments_fieldset');

	if( $creating )
	{	// Creating new post
		$fieldset_title .= ' - <a id="title_file_add" href="#" >'.get_icon( 'folder', 'imgtag' ).' '.T_('Add/Link files').'</a> <span class="note">(popup)</span>';

		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_createlinks' ) );
		$Form->hidden( 'is_attachments', 'false' );

		echo '<table cellspacing="0" cellpadding="0"><tr><td>';
		$Form->submit( array( 'actionArray[create_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & start attaching files'), 'SaveEditButton' ) );
		echo '</td></tr></table>';

		$Form->end_fieldset();
	}
	else
	{ // Editing post

		if( $iframe_name == NULL )
		{
			$iframe_name = 'attach_'.generate_random_key( 16 );
		}

		$fieldset_title .= ' - <a href="'.$admin_url.'?ctrl=items&amp;action=edit_links&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;item_ID='.$edited_Item->ID
					.'" target="'.$iframe_name.'">'.get_icon( 'refresh', 'imgtag' ).' '.T_('Refresh').'</a>';

		if( $current_User->check_perm( 'files', 'view', false, $Blog->ID )
			&& $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
		{	// Check that we have permission to edit item:

			$fieldset_title .= ' - <a href="'.$dispatcher.'?ctrl=files&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID
						.'" onclick="return pop_up_window( \''.$dispatcher.'?ctrl=files&amp;mode=upload&amp;iframe_name='
						.$iframe_name.'&amp;fm_mode=link_item&amp;item_ID='.$edited_Item->ID.'\', \'fileman_upload\', 1000 )">'
						.get_icon( 'folder', 'imgtag' ).' '.T_('Add/Link files').'</a> <span class="note">(popup)</span>';
		}

		$Form->begin_fieldset( $fieldset_title, array( 'id' => 'itemform_links' ) );

		echo '<iframe src="'.$admin_url.'?ctrl=items&amp;action=edit_links&amp;mode=iframe&amp;iframe_name='.$iframe_name.'&amp;item_ID='.$edited_Item->ID
					.'" name="'.$iframe_name.'" width="100%" marginwidth="0" height="160" marginheight="0" align="top" scrolling="auto" frameborder="0" id="attachmentframe"></iframe>';

		$Form->end_fieldset();
	}
}

/**
 * Get post category setting
 *
 * @param int blog id
 * @return int setting value
 */
function get_post_cat_setting( $blog )
{
	$BlogCache = & get_BlogCache();
	$Blog = $BlogCache->get_by_ID( $blog, false, false );
	if( ! $Blog )
	{
		return -1;
	}
	$post_categories = $Blog->get_setting( 'post_categories' );
	switch( $post_categories )
	{
		case 'no_cat_post':
			return 0;
		case 'one_cat_post':
			return 1;
		case 'multiple_cat_post':
			return 2;
		case 'main_extra_cat_post':
			return 3;
	}
}

/**
 * Creates a link to new category, with properties icon
 *
 * @return string link url
 */
function get_newcategory_link()
{
	global $dispatcher, $blog;
	$new_url = $dispatcher.'?ctrl=chapters&amp;action=new&amp;blog='.$blog;
	$link = ' <span class="floatright">'.action_icon( T_('Add new category'), 'new', $new_url, '', 5, 1 ).'</span>';
	return $link;
}

/**
 * Allow recursive category selection.
 *
 * @todo Allow to use a dropdown (select) to switch between blogs ( CSS / JS onchange - no submit.. )
 *
 * @param Form
 * @param boolean true: use form fields, false: display only
 */
function cat_select( $Form, $form_fields = true )
{
	global $cache_categories, $blog, $current_blog_ID, $current_User, $edited_Item, $cat_select_form_fields;
	global $cat_sel_total_count, $dispatcher;
	global $rsc_url;

	if( get_post_cat_setting( $blog ) < 1 )
	{ // No categories for $blog
		return;
	}

	$Form->begin_fieldset( get_newcategory_link().T_('Categories').get_manual_link('item_categories_fieldset'), array( 'class'=>'extracats', 'id' => 'itemform_categories' ) );

	$cat_sel_total_count = 0;
	$r ='';

	$cat_select_form_fields = $form_fields;

	cat_load_cache(); // make sure the caches are loaded

	$r .= '<table cellspacing="0" class="catselect">';
	if( get_post_cat_setting($blog) == 3 )
	{ // Main + Extra cats option is set, display header
		$r .= cat_select_header();
	}

	if( get_allow_cross_posting() >= 2 ||
	  ( isset( $blog) && get_post_cat_setting( $blog ) > 1 && get_allow_cross_posting() == 1 ) )
	{ // If BLOG cross posting enabled, go through all blogs with cats:
		/**
		 * @var BlogCache
		 */
		$BlogCache = & get_BlogCache();

		/**
		 * @var Blog
		 */
		for( $l_Blog = & $BlogCache->get_first(); !is_null($l_Blog); $l_Blog = & $BlogCache->get_next() )
		{ // run recursively through the cats
			if( ! blog_has_cats( $l_Blog->ID ) )
				continue;

			if( ! $current_User->check_perm( 'blog_post_statuses', 'edit', false, $l_Blog->ID ) )
				continue;

			$r .= '<tr class="group" id="catselect_blog'.$l_Blog->ID.'"><td colspan="3">'.$l_Blog->dget('name')."</td></tr>\n";
			$cat_sel_total_count++; // the header uses 1 line

			$current_blog_ID = $l_Blog->ID;	// Global needed in callbacks
			$r .= cat_children( $cache_categories, $l_Blog->ID, NULL, 'cat_select_before_first',
										'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
			if( $blog == $l_Blog->ID )
			{
				$r .= cat_select_new();
			}
		}
	}
	else
	{ // BLOG Cross posting is disabled. Current blog only:
		$current_blog_ID = $blog;
		$r .= cat_children( $cache_categories, $current_blog_ID, NULL, 'cat_select_before_first',
									'cat_select_before_each', 'cat_select_after_each', 'cat_select_after_last', 1 );
		$r .= cat_select_new();
	}

	$r .= '</table>';

	echo $r;

	if( isset($blog) && get_allow_cross_posting() )
	{
		echo '<script type="text/javascript">jQuery.getScript("'.$rsc_url.'js/jquery/jquery.scrollto.js", function () {
			jQuery("#itemform_categories").scrollTo( "#catselect_blog'.$blog.'" );
		});</script>';
	}
	$Form->end_fieldset();
}

/**
 * Header for {@link cat_select()}
 */
function cat_select_header()
{
	// main cat header
	$r = '<thead><tr><th class="selector catsel_main" title="'.T_('Main category').'">'.T_('Main').'</th>';

	// extra cat header
	$r .= '<th class="selector catsel_extra" title="'.T_('Additional category').'">'.T_('Extra').'</th>';

	// category header
	$r .= '<th class="catsel_name">'.T_('Category').'</th><!--[if IE 7]><th width="1"><!-- for IE7 --></th><![endif]--></tr></thead>';

	return $r;
}

/**
 * callback to start sublist
 */
function cat_select_before_first( $parent_cat_ID, $level )
{ // callback to start sublist
	return ''; // "\n<ul>\n";
}

/**
 * callback to display sublist element
 */
function cat_select_before_each( $cat_ID, $level, $total_count )
{ // callback to display sublist element
	global $current_blog_ID, $blog, $post_extracats, $edited_Item;
	global $creating, $cat_select_level, $cat_select_form_fields;
	global $cat_sel_total_count;

	$cat_sel_total_count++;

	$ChapterCache = & get_ChapterCache();
	$thisChapter = $ChapterCache->get_by_ID($cat_ID);
	$r = "\n".'<tr class="'.( $total_count%2 ? 'odd' : 'even' ).'">';

	// RADIO for main cat:
	if( get_post_cat_setting($blog) != 2 )
	{ // if no "Multiple categories per post" option is set display radio
		if( ($current_blog_ID == $blog) || (get_allow_cross_posting( $blog ) >= 2) )
		{ // This is current blog or we allow moving posts accross blogs
			if( $cat_select_form_fields )
			{	// We want a form field:
				$r .= '<td class="selector catsel_main"><input type="radio" name="post_category" class="checkbox" title="'
							.T_('Select as MAIN category').'" value="'.$cat_ID.'"';
				if( $cat_ID == $edited_Item->main_cat_ID )
				{ // main cat of the Item or set as default main cat above
					$r .= ' checked="checked"';
				}
				$r .= ' id="sel_maincat_'.$cat_ID.'"';
				$r .= ' onclick="check_extracat(this);" /></td>';
			}
			else
			{	// We just want info:
				$r .= '<td class="selector catsel_main">'.bullet( $cat_ID == $edited_Item->main_cat_ID ).'</td>';
			}
		}
		else
		{ // Don't allow to select this cat as a main cat
			$r .= '<td class="selector catsel_main">&nbsp;</td>';
		}
	}

	// CHECKBOX:
	if( get_post_cat_setting( $blog ) >= 2 )
	{ // We allow multiple categories or main + extra cat,  display checkbox:
		if( ($current_blog_ID == $blog) || ( get_allow_cross_posting( $blog ) % 2 == 1 )
			|| ( ( get_allow_cross_posting( $blog ) == 2 ) && ( get_post_cat_setting( $blog ) == 2 ) ) )
		{ // This is the current blog or we allow cross posting (select extra cat from another blog)
			if( $cat_select_form_fields )
			{	// We want a form field:
				$r .= '<td class="selector catsel_extra"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
							.T_('Select as an additional category').'" value="'.$cat_ID.'"';
				// if( ($cat_ID == $edited_Item->main_cat_ID) || (in_array( $cat_ID, $post_extracats )) )  <--- We don't want to precheck the default cat because it will stay checked if we change the default main. On edit, the checkbox will always be in the array.
				if( in_array( $cat_ID, $post_extracats ) )
				{ // This category was selected
					$r .= ' checked="checked"';
				}
				$r .= ' id="sel_extracat_'.$cat_ID.'"';
				$r .= ' /></td>';
			}
			else
			{	// We just want info:
				$r .= '<td class="selector catsel_main">'.bullet( ($cat_ID == $edited_Item->main_cat_ID) || (in_array( $cat_ID, $post_extracats )) ).'</td>';
			}
		}
		else
		{ // Don't allow to select this cat as an extra cat
			$r .= '<td class="selector catsel_main">&nbsp;</td>';
		}
	}

	$BlogCache = & get_BlogCache();
	$r .= '<td class="catsel_name"><label'
				.' for="'.( get_post_cat_setting( $blog ) == 2
					? 'sel_extracat_'.$cat_ID
					: 'sel_maincat_'.$cat_ID ).'"'
				.' style="padding-left:'.($level-1).'em;">'
				.htmlspecialchars($thisChapter->name).'</label>'
				.' <a href="'.htmlspecialchars($thisChapter->get_permanent_url()).'" title="'.htmlspecialchars(T_('View category in blog.')).'">'
				.'&nbsp;&raquo;&nbsp; ' // TODO: dh> provide an icon instead? // fp> maybe the A(dmin)/B(log) icon from the toolbar? And also use it for permalinks to posts?
				.'</a></td>'
				.'<!--[if IE 7]><td width="1"><!-- for IE7 --></td><![endif]--></tr>'
				."\n";

	return $r;
}

/**
 * callback after each sublist element
 */
function cat_select_after_each( $cat_ID, $level )
{ // callback after each sublist element
	return '';
}

/**
 * callback to end sublist
 */
function cat_select_after_last( $parent_cat_ID, $level )
{ // callback to end sublist
	return ''; // "</ul>\n";
}

/**
 * new category line for catselect table
 * @return string new row code
 */
function cat_select_new()
{
	global $blog;
	$new_maincat = param( 'new_maincat', 'boolean', false );
	$new_extracat = param( 'new_extracat', 'boolean', false );
	if( $new_maincat || $new_extracat )
	{
		$category_name = param( 'category_name', 'string', '' );
	}
	else
	{
		$category_name = '';
	}

	global $cat_sel_total_count;
	$cat_sel_total_count++;
	$r = "\n".'<tr class="'.( $cat_sel_total_count%2 ? 'odd' : 'even' ).'">';

	if( get_post_cat_setting( $blog ) != 2 )
	{
		// RADIO for new main cat:
		$r .= '<td class="selector catsel_main"><input type="radio" name="post_category" class="checkbox" title="'
							.T_('Select as MAIN category').'" value="0"';
		if( $new_maincat )
		{
			$r.= ' checked="checked"';
		}
		$r .= ' id="sel_maincat_new"';
		$r .= ' onclick="check_extracat(this);"';
		$r .= '/></td>';
	}

	if( get_post_cat_setting( $blog ) >= 2 )
	{
		// CHECKBOX
		$r .= '<td class="selector catsel_extra"><input type="checkbox" name="post_extracats[]" class="checkbox" title="'
							.T_('Select as an additional category').'" value="0"';
		if( $new_extracat )
		{
			$r.= ' checked="checked"';
		}
		$r .= 'id="sel_extracat_new"/></td>';
	}

	// INPUT TEXT for new category name
	$r .= '<td class="catsel_name">'
				.'<input maxlength="255" style="width: 100%;" value="'.$category_name.'" size="20" type="text" name="category_name" id="new_category_name" />'
				."</td>";
	$r .= '<!--[if IE 7]><td width="1">&nbsp</td><![endif]-->';
	$r .= "</tr>";
	return $r;
}


/**
 * Used by the items & the comments controllers
 */
function attach_browse_tabs()
{
	global $AdminUI, $Blog, $current_User, $dispatcher, $ItemTypeCache;
	$AdminUI->add_menu_entries(
			'items',
			array(
					'full' => array(
						'text' => T_('All'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=full&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'list' => array(
						'text' => T_('Posts'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=list&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'pages' => array(
						'text' => T_('Pages'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=pages&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'intros' => array(
						'text' => T_('Intros'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=intros&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'podcasts' => array(
						'text' => T_('Podcasts'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=podcasts&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					'links' => array(
						'text' => T_('Sidebar links'),
						'href' => $dispatcher.'?ctrl=items&amp;tab=links&amp;filter=restore&amp;blog='.$Blog->ID,
						),
				)
		);

	/* fp> Custom types should be variations of normal posts by default
	  I am ok with giving extra tabs to SOME of them but then the
		posttype list has to be transformed into a normal CREATE/UPDATE/DELETE (CRUD)
		(see the stats>goals CRUD for an example of a clean CRUD)
		and each post type must have a checkbox like "use separate tab"
		Note: you can also make a select list "group with: posts|pages|etc...|other|own tab"

		$ItemTypeCache = & get_ItemTypeCache();
		$default_post_types = array(1,1000,1500,1520,1530,1570,1600,2000,3000);
		$items_types = array_values( array_keys( $ItemTypeCache->get_option_array() ) );
		// a tab for custom types
		if ( array_diff($items_types,$default_post_types) )
		{
			$AdminUI->add_menu_entries(
					'items',
					array(
						'custom' => array(
							'text' => T_('Custom Types'),
							'href' => $dispatcher.'?ctrl=items&amp;tab=custom&amp;filter=restore&amp;blog='.$Blog->ID,
						),
					)
			);
		}
	*/

	if( $Blog->get_setting( 'use_workflow' ) )
	{	// We want to use workflow properties for this blog:
		$AdminUI->add_menu_entries(
				'items',
				array(
						'tracker' => array(
							'text' => T_('Tracker'),
							'href' => $dispatcher.'?ctrl=items&amp;tab=tracker&amp;filter=restore&amp;blog='.$Blog->ID,
							),
					)
			);
	}

	if( $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
	{ // User has permission to edit published, draft or deprecated comments (at least one kind)
		$AdminUI->add_menu_entries(
				'items',
				array(
						'comments' => array(
							'text' => T_('Comments'),
							'href' => $dispatcher.'?ctrl=comments&amp;blog='.$Blog->ID.'&amp;filter=restore',
							),
					)
			);

		$AdminUI->add_menu_entries( array('items','comments'), array(
				'fullview' => array(
					'text' => T_('Full text view'),
					'href' => $dispatcher.'?ctrl=comments&amp;tab3=fullview&amp;filter=restore'
					),
				'listview' => array(
					'text' => T_('List view'),
					'href' => $dispatcher.'?ctrl=comments&amp;tab3=listview&amp;filter=restore'
					),
				)
			);
	}


	// What perms do we have?
	$coll_settings_perm = $current_User->check_perm( 'options', 'view', false, $Blog->ID );
	$settings_url = '?ctrl=itemtypes&amp;tab=settings&amp;tab3=types';
	if( $coll_chapters_perm = $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
	{
		$settings_url = '?ctrl=chapters&amp;blog='.$Blog->ID;
	}

	if( $coll_settings_perm || $coll_chapters_perm )
	{
		$AdminUI->add_menu_entries(
			'items',
			array(
				'settings' => array(
					'text' => T_('Content settings'),
					'href' => $settings_url,
					)
				)
			);

		if( $coll_chapters_perm )
		{
			$AdminUI->add_menu_entries( array('items','settings'), array(
				'chapters' => array(
					'text' => T_('Categories'),
					'href' => '?ctrl=chapters&amp;blog='.$Blog->ID
					),
				)
			);
		}

		if( $coll_settings_perm )
		{
			$AdminUI->add_menu_entries( array('items','settings'), array(
				'types' => array(
					'text' => T_('Post types'),
					'title' => T_('Post types management'),
					'href' => '?ctrl=itemtypes&amp;tab=settings&amp;tab3=types'
					),
				'statuses' => array(
					'text' => T_('Post statuses'),
					'title' => T_('Post statuses management'),
					'href' => '?ctrl=itemstatuses&amp;tab=settings&amp;tab3=statuses'
					),
				)
			);
		}
	}
}



/**
 * Allow to select status/visibility
 */
function visibility_select( & $Form, $post_status, $mass_create = false )
{
	global $current_User, $Blog;

	$sharing_options = array();

	if( $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'published', T_('Published').' <span class="notes">'.T_('(Public)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!protected', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'protected', T_('Protected').' <span class="notes">'.T_('(Members only)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!private', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'private', T_('Private').' <span class="notes">'.T_('(You only)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!draft', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'draft', T_('Draft').' <span class="notes">'.T_('(Not published!)').'</span>' );
	}

	if( $current_User->check_perm( 'blog_post!deprecated', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'deprecated', T_('Deprecated').' <span class="notes">'.T_('(Not published!)').'</span>' );
	}

	if( !$mass_create && $current_User->check_perm( 'blog_post!redirected', 'edit', false, $Blog->ID ) )
	{
		$sharing_options[] = array( 'redirected', T_('Redirected').' <span class="notes">(301)</span>' );
	}

	$Form->radio( 'post_status', $post_status, $sharing_options, '', true );
}


/**
 * Selection of the issue date
 *
 * @todo dh> should display erroneous values (e.g. when giving invalid date) as current (form) value, too.
 * @param Form
 */
function issue_date_control( $Form, $break = false )
{
	global $edited_Item;

	echo T_('Issue date').':<br />';

	echo '<label><input type="radio" name="item_dateset" id="set_issue_date_now" value="0" '
				.( ($edited_Item->dateset == 0) ? 'checked="checked"' : '' )
				.'/><strong>'.T_('Update to NOW').'</strong></label>';

	if( $break )
	{
		echo '<br />';
	}

	echo '<label><input type="radio" name="item_dateset" id="set_issue_date_to" value="1" '
				.( ($edited_Item->dateset == 1) ? 'checked="checked"' : '' )
				.'/><strong>'.T_('Set to').':</strong></label>';
	$Form->date( 'item_issue_date', $edited_Item->get('issue_date'), '' );
	echo ' '; // allow wrapping!
	$Form->time( 'item_issue_time', $edited_Item->get('issue_date'), '', 'hh:mm:ss', '' );
	echo ' '; // allow wrapping!

	// Autoselect "change date" is the date is changed.
	?>
	<script>
	jQuery( function()
			{
				jQuery('#item_issue_date, #item_issue_time').change(function()
				{
					jQuery('#set_issue_date_to').attr("checked", "checked")
				})
			}
		)
	</script>
	<?php

}


/**
 * Template tag: Link to an item identified by its url title / slug / name
 *
 * Note: this will query the database. Thus, in most situations it will make more sense
 * to use a hardcoded link. This tag can be useful for prototyping location independant
 * sites.
 */
function item_link_by_urltitle( $params = array() )
{
	// Make sure we are not missing any param:
	$params = array_merge( array(
			'urltitle'    => NULL,  // MUST BE SPECIFIED
			'before'      => ' ',
			'after'       => ' ',
			'text'        => '#',
		), $params );

  /**
	 * @var ItemCache
	 */
	$ItemCache = & get_ItemCache();

  /**
	 * @var Item
	 */
	$Item = & $ItemCache->get_by_urltitle( $params['urltitle'], false );

	if( empty($Item) )
	{
		return false;
	}

	$Item->permanent_link( $params );
}


function echo_publish_buttons( $Form, $creating, $edited_Item )
{
	global $Blog, $current_User;
	global $next_action; // needs to be passed out for echo_publishnowbutton_js( $action )

	// ---------- PREVIEW ----------
	$url = url_same_protocol( $Blog->get( 'url' ) ); // was dynurl
	$Form->button( array( 'button', '', T_('Preview'), 'PreviewButton', 'b2edit_open_preview(this.form, \''.$url.'\');' ) );

	// ---------- SAVE ----------
	$next_action = ($creating ? 'create' : 'update');
	$Form->submit( array( 'actionArray['.$next_action.'_edit]', /* TRANS: This is the value of an input submit button */ T_('Save & edit'), 'SaveEditButton' ) );
	$Form->submit( array( 'actionArray['.$next_action.']', /* TRANS: This is the value of an input submit button */ T_('Save'), 'SaveButton' ) );

	if( $edited_Item->status == 'draft'
			&& $current_User->check_perm( 'blog_post!published', 'edit', false, $Blog->ID )	// TODO: if we actually set the primary cat to another blog, we may still get an ugly perm die
			&& $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
	{	// Only allow publishing if in draft mode. Other modes are too special to run the risk of 1 click publication.
		$publish_style = 'display: inline';
	}
	else
	{
		$publish_style = 'display: none';
	}
	$Form->submit( array(
		'actionArray['.$next_action.'_publish]',
		/* TRANS: This is the value of an input submit button */ T_('Publish!'),
		'SaveButton',
		'',
		$publish_style
	) );
}

/**
 * Output JavaScript code to dynamically show popup files attachment window
 *
 * This is a part of the process that makes it smoother to "Save & start attaching files".
 */
function echo_attaching_files_button_js( & $iframe_name )
{
	global $dispatcher;
	global $edited_Item;
	$iframe_name = 'attach_'.generate_random_key( 16 );
	?>
	<script type="text/javascript">
			pop_up_window( '<?php echo $dispatcher; ?>?ctrl=files&mode=upload&iframe_name=<?php echo $iframe_name ?>&fm_mode=link_item&item_ID=<?php echo $edited_Item->ID?>', 'fileman_upload', 1000 );
	</script>
	<?php
}

/**
 * Output JavaScript code to set hidden field is_attachments
 * which indicates that we must show attachments files popup window
 *
 * This is a part of the process that makes it smoother to "Save & start attaching files".
 */
function echo_set_is_attachments()
{
	?>
	<script type="text/javascript">
		jQuery( '#itemform_createlinks td input' ).click( function()
		{
			jQuery( 'input[name=is_attachments]' ).attr("value", "true");
		} );
	</script>
	<?php
}

/**
 * Output JavaScript code for "Add/Link files" link
 *
 * This is a part of the process that makes it smoother to "Save & start attaching files".
 */
function echo_link_files_js()
{
	?>
	<script type="text/javascript">
			jQuery( '#title_file_add' ).click( function()
			{
				jQuery( 'input[name=is_attachments]' ).attr("value", "true");
				jQuery( '#itemform_createlinks input[name="actionArray[create_edit]"]' ).click();
			} );
	</script>
	<?php
}

/**
 * Output JavaScript code to dynamically show or hide the "Publish NOW!"
 * button depending on the selected post status.
 *
 * This function is used by the simple and expert write screens.
 */
function echo_publishnowbutton_js()
{
	global $next_action;
	?>
	<script type="text/javascript">
		jQuery( '#itemform_visibility input[type=radio]' ).click( function()
		{
			var publishnow_btn = jQuery( '.edit_actions input[name="actionArray[<?php echo $next_action; ?>_publish]"]' );

			if( this.value != 'draft' )
			{	// Hide the "Publish NOW !" button:
				publishnow_btn.css( 'display', 'none' );
			}
			else
			{	// Show the button:
				publishnow_btn.css( 'display', 'inline' );
			}
		} );
	</script>
	<?php
}


/**
 * Output Javascript for tags autocompletion.
 * @todo dh> a more facebook like widget would be: http://plugins.jquery.com/project/facelist
 *           "ListBuilder" is being planned for jQuery UI: http://wiki.jqueryui.com/ListBuilder
 */
function echo_autocomplete_tags()
{
	global $htsrv_url;

	$url_crumb = url_crumb('item');

	echo <<<EOD
	<script type="text/javascript">
	(function($){
		$(function() {
			function split(val) {
				return val.split(/\s*,\s*/);
			}
			function extractLast(term) {
				return split(term).pop();
			}

			$("#item_tags").autocomplete({
				source: function(request, response) {
					$.getJSON("${htsrv_url}async.php?action=get_tags&${url_crumb}", {
						term: extractLast(request.term)
					}, response);
				},
				search: function() {
					// custom minLength
					var term = extractLast(this.value);
					if (term.length < 1) {
						return false;
					}
				},
				focus: function() {
					// prevent value inserted on focus
					return false;
				},
				select: function(event, ui) {
					var terms = split( this.value );
					// remove the current input
					terms.pop();
					// add the selected item
					terms.push( ui.item.value );
					// add placeholder to get the comma-and-space at the end
					terms.push("");
					this.value = terms.join(", ");
					return false;
				}
			});
		});
	})(jQuery);
	</script>
EOD;
}


/**
 * Assert that the supplied post type can be used by the current user in
 * the post's extra categories' context.
 *
 * @param array The extra cats of the post.
 */
function check_perm_posttype( $post_extracats )
{
	global $posttypes_perms, $item_typ_ID, $current_User;

	static $posttype2perm = NULL;
	if( $posttype2perm === NULL )
	{	// "Reverse" the $posttypes_perms array:
		// Tblue> Possibly bloat; this function usually is invoked only
		//        once, thus it *may* be better to simply iterate through
		//        the $posttypes_perms array every time and look for the
		//        post type ID.
		foreach( $posttypes_perms as $l_permname => $l_posttypes )
		{
			foreach( $l_posttypes as $ll_posttype )
			{
				$posttype2perm[$ll_posttype] = $l_permname;
			}
		}
	}

	// Tblue> Usually, when this function is invoked, item_typ_ID is not
	//        loaded yet... If it is, it doesn't get loaded again anyway.
	//        Item::load_from_Request() uses param() again, in case this
	//        function wasn't called yet when load_from_Request() gets
	//        called (does this happen?).
	param( 'item_typ_ID', 'integer', true /* require input */ );
	if( ! isset( $posttype2perm[$item_typ_ID] ) )
	{	// Allow usage:
		return;
	}

	// Check permission:
	$current_User->check_perm( 'cats_'.$posttype2perm[$item_typ_ID], 'edit', true /* assert */, $post_extracats );
}


/**
 * Mass create.
 *
 * Create multiple posts from one post.
 *
 * @param object Instance of Item class (by reference).
 * @param boolean true if create paragraphs at each line break
 * @return array The posts, by reference.
 */
function & create_multiple_posts( & $Item, $linebreak = false )
{
	$Items = array();

	// Parse text into titles and contents:
	$current_title = '';
	$current_data  = '';

	// Append a newline to the end of the original contents to make sure
	// that the last item gets created - this saves a second loop.
	foreach( explode( "\n", $Item->content."\n" ) as $line )
	{
		$line = trim( strip_tags( $line ) );

		if( $current_title === '' && $line !== '' )
		{	// We got a new title:
			$current_title = $line;
		}
		elseif( $current_title !== '' )
		{
			if( $line !== '' )
			{	// We got a new paragraph for this post:
				if( $linebreak )
				{
					$current_data .= '<p>'.$line.'</p>';
				}
				else
				{
					$current_data .= $line.' ';
				}
			}
			else
			{	// End of this post:
				$new_Item = duplicate( $Item );

				$new_Item->set_param( 'title', 'string', $current_title );

				if( !$linebreak )
				{
					$current_data = trim( $current_data );
				}
				$new_Item->set_param( 'content', 'string', $current_data );

				$Items[] = $new_Item;

				$current_title = '';
				$current_data  = '';
			}
		}
	}

	return $Items;
}

/**
 *
 * Check if new category needs to be created or not (after post editing).
 * If the new category radio is checked creates the new category and set it to post category
 * If the new category checkbox is checked creates the new category and set it to post extracat
 *
 * Function is called during post creation or post update
 *
 * @param Object Post category (by reference).
 * @param Array Post extra categories (by reference).
 * @return boolean true - if there is no new category, or new category created succesfull; false if new category creation failed.
 */
function check_categories( & $post_category, & $post_extracats )
{
	$post_category = param( 'post_category', 'integer', -1 );
	$post_extracats = param( 'post_extracats', 'array', array() );
	global $Messages, $Blog, $blog;

	load_class( 'chapters/model/_chaptercache.class.php', 'ChapterCache' );
	$GenericCategoryCache = & get_ChapterCache();

	if( $post_category == -1 )
	{ // no main cat select
		if( count( $post_extracats ) == 0 )
		{ // no extra cat select
			$post_category = $Blog->get_default_cat_ID();
		}
		else
		{ // first extracat become main_cat
			if( get_allow_cross_posting() >= 2 )
			{ // allow moving posts between different blogs is enabled, set first selected cat as main cat
				$post_category = $post_extracats[0];
			}
			else
			{ // allow moving posts between different blogs is disabled - we need a main cat from $blog
				foreach( $post_extracats as $cat )
				{
					if( get_catblog( $cat ) != $blog )
					{ // this cat is not from $blog
						continue;
					}
					// set first cat from $blog as main cat
					$post_category = $cat;
					break;
				}
				if( $post_category == -1 )
				{ // wasn't cat selected from $blog select a default as main cat
					$post_category = $Blog->get_default_cat_ID();
				}
			}
		}
		if( $post_category )
		{ // If main cat is not a new category, and has been autoselected
			$GenericCategory = & $GenericCategoryCache->get_by_ID( $post_category );
			$post_category_Blog = $GenericCategory->get_Blog();
			$Messages->add( sprintf( T_('The main category for this post has been automatically set to "%s" (Blog "%s")'),
				$GenericCategory->get_name(), $post_category_Blog->get( 'name') ), 'warning' );
		}
	}

	if( ! $post_category || in_array( 0, $post_extracats ) )	// if category key is 0 => means it is a new category
	{
		$category_name = param( 'category_name', 'string', true );
		if( $category_name == '' )
		{
			$show_error = ! $post_category;	// new main category without name => error message
			check_categories_nosave( $post_category, $post_extracats); // set up the category parameters
			if( $show_error )
			{ // new main category without name
				$Messages->add( T_('Please provide a name for new category.'), 'error' );
				return false;
			}
			return true;
		}

		$new_GenericCategory = & $GenericCategoryCache->new_obj( NULL, $blog );	// create new category object
		$new_GenericCategory->set( 'name', $category_name );
		if( $new_GenericCategory->dbinsert() !== false )
		{
			$Messages->add( T_('New category created.'), 'success' );
			if( ! $post_category ) // if new category is main category
			{
				$post_category = $new_GenericCategory->ID;	// set the new ID
			}

			if( ( $extracat_key = array_search( '0', $post_extracats ) ) || $post_extracats[0] == '0' )
			{
				if( $extracat_key )
				{
					unset($post_extracats[$extracat_key]);
				}
				else
				{
					unset($post_extracats[0]);
				}
				$post_extracats[] = $new_GenericCategory->ID;
			}

			$GenericCategoryCache->add($new_GenericCategory);
		}
		else
		{
			$Messages->add( T_('New category creation failed.'), 'error' );
			return false;
		}
	}

	if( get_allow_cross_posting() == 2 )
	{ // Extra cats in different blogs is disabled, check selected extra cats
		$post_category_blog = get_catblog( $post_category );
		$ignored_cats = '';
		foreach( $post_extracats as $key => $cat )
		{
			if( get_catblog( $cat ) != $post_category_blog )
			{ // this cat is not from main category blog, it has to be ingnored
				$GenericCategory = & $GenericCategoryCache->get_by_ID( $cat );
				$ignored_cats = $ignored_cats.$GenericCategory->get_name().', ';
				unset( $post_extracats[$key] );
			}
		}
		$ingnored_length = strlen( $ignored_cats );
		if( $ingnored_length > 2 )
		{ // ingnore list is not empty
			global $current_User, $admin_url;
			if( $current_User->check_perm( 'options', 'view', false ) )
			{
				$cross_posting_text = '<a href="'.$admin_url.'?ctrl=features">'.T_('cross-posting is disabled').'</a>';
			}
			else
			{
				$cross_posting_text = T_('cross-posting is disabled');
			}
			$ignored_cats = substr( $ignored_cats, 0, $ingnored_length - 2 );
			$Messages->add( sprintf( T_('The category selection "%s" was ignored since %s'),
				$ignored_cats, $cross_posting_text ), 'warning' );
		}
	}

	// make sure main cat is in extracat list and there are no duplicates
	$post_extracats[] = $post_category;
	$post_extracats = array_unique( $post_extracats );

	return true;
}

/*
 * Set up params for new category creation
 * Set main category to default category, if the current category does not exist yet
 * Delete non existing category from extracats
 * It is called after simple/expert tab switch, and can be called during post creation or modification
 *
 * @param Object Post category (by reference).
 * @param Array Post extra categories (by reference).
 *
 */
function check_categories_nosave( & $post_category, & $post_extracats )
{
	global $Blog;
	$post_category = param( 'post_category', 'integer', $Blog->get_default_cat_ID() );
	$post_extracats = param( 'post_extracats', 'array', array() );

	if( ! $post_category )	// if category key is 0 => means it is a new category
	{
		$post_category = $Blog->get_default_cat_ID();
		param( 'new_maincat', 'boolean', 1 );
	}

	if( ! empty( $post_extracats) && ( ( $extracat_key = array_search( '0', $post_extracats ) ) || $post_extracats[0] == '0' ) )
	{
		param( 'new_extracat', 'boolean', 1 );
		if( $extracat_key )
		{
			unset($post_extracats[$extracat_key]);
		}
		else
		{
			unset($post_extracats[0]);
		}
	}
}

/*
 * check the new category radio button, if the new category text has changed
 */
function echo_onchange_newcat()
{
?>
	<script type="text/javascript">
		jQuery( '#new_category_name' ).keypress( function()
		{
			var newcategory_radio = jQuery( '#sel_maincat_new' );
			if( ! newcategory_radio.attr('checked') )
			{
				newcategory_radio.attr('checked', true);
				jQuery( '#sel_extracat_new' ).attr('checked', true);
			}
		} );
	</script>
<?php
}

/**
 * Automatically update the slug field when a title is typed.
 *
 * Variable slug_changed hold true if slug was manually changed
 * (we already do not need autocomplete) and false in other case.
 */
function echo_slug_filler()
{
?>
	<script type="text/javascript">
		var slug_changed = false;
		jQuery( '#post_title' ).keyup( function()
		{
			if(!slug_changed)
			{
				jQuery( '#post_urltitle' ).val( jQuery( '#post_title' ).val() );
			}
		} );

		jQuery( '#post_urltitle' ).change( function()
		{
			slug_changed = true;
			jQuery( '[name=slug_changed]' ).val( 1 );
		} );
	</script>
<?php
}


/**
 * Set slug_changed to 1 for cases when it is not needed trim slug
 */
function echo_set_slug_changed()
{
?>
	<script type="text/javascript">
		jQuery( '[name=slug_changed]' ).val( 1 );
	</script>
<?php
}


/**
 * Handle show_comments radioboxes on item list full view
 */
function echo_show_comments_changed()
{
?>
	<script type="text/javascript">
		jQuery( '[name |= show_comments]' ).change( function()
		{
			var item_id = $('#comments_container').attr('value');
			if( ! isDefined( item_id) )
			{ // if item_id is not defined, we have to show all comments from current blog
				item_id = -1;
			}
			refresh_item_comments( item_id, 1 );
		} );
	</script>
<?php
}


/**
 * Display CommentList with the given filters
 *
 * @param int blog
 * @param item item
 * @param array status filters
 * @param int limit
 * @param $comment_IDs
 * @param string comment IDs string to exclude from the list
 */
function echo_item_comments( $blog_ID, $item_ID, $statuses = array( 'draft', 'published', 'deprecated' ),
	$currentpage = 1, $limit = 20, $comment_IDs = array() )
{
	global $inc_path, $status_list, $Blog, $admin_url;

	$BlogCache = & get_BlogCache();
	$Blog = & $BlogCache->get_by_ID( $blog_ID, false, false );

	global $CommentList;
	$CommentList = new CommentList2( $Blog );

	$exlude_ID_list = NULL;
	if( !empty($comment_IDs) )
	{
		$exlude_ID_list = '-'.implode( ",", $comment_IDs );
	}

	// if item_ID == -1 then don't use item filter! display all comments from current blog
	if( $item_ID == -1 )
	{
		$item_ID = NULL;
	}
	// set redirect_to
	if( $item_ID != null )
	{ // redirect to the items full view
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&blog='.$blog_ID.'&p='.$item_ID, '&' ) );
		param( 'item_id', 'integer', $item_ID );
		param( 'currentpage', 'integer', $currentpage );
		if( count( $statuses ) == 1 )
		{
			$show_comments = $statuses[0];
		}
		else
		{
			$show_comments = 'all';
		}
		param( 'comments_number', 'integer', generic_ctp_number( $item_ID, 'comments', $show_comments ) );
		// Filter list:
		$CommentList->set_filters( array(
			'types' => array( 'comment', 'trackback', 'pingback' ),
			'statuses' => $statuses,
			'comment_ID_list' => $exlude_ID_list,
			'post_ID' => $item_ID,
			'order' => 'ASC',//$order,
			'comments' => $limit,
			'page' => $currentpage,
		) );
	}
	else
	{ // redirect to the comments full view
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=comments&blog='.$blog_ID.'&filter=restore', '&' ) );
		// this is an ajax call we always have to restore the filterst (we can set filters only without ajax call)
		$CommentList->set_filters( array(
			'types' => array( 'comment', 'trackback', 'pingback' ),
		) );
		$CommentList->restore_filterset();
	}

	// Get ready for display (runs the query):
	$CommentList->display_init();

	$CommentList->display_if_empty( array(
		'before'    => '<div class="bComment"><p>',
		'after'     => '</p></div>',
		'msg_empty' => T_('No feedback for this post yet...'),
	) );

	// display comments
	require $inc_path.'comments/views/_comment_list.inc.php';
}


/**
 * Display a comment corresponding the given comment id
 *
 * @param int comment id
 * @param string where to redirect after comment edit
 * @param boolean true to set the new redirect param, false otherwise
 */
function echo_comment( $comment_ID, $redirect_to = NULL, $save_context = false )
{
	global $current_User;

	$CommentCache = & get_CommentCache();
	$Comment = $CommentCache->get_by_ID( $comment_ID );

	$is_published = ( $Comment->get( 'status' ) == 'published' );

	$Item = & $Comment->get_Item();
	$Blog = & $Item->get_Blog();

	if( $current_User->check_perm( $Comment->blogperm_name(), 'edit', false, $Blog->ID ) )
	{
		echo '<div id="c'.$comment_ID.'" class="bComment bComment';
		$Comment->status('raw');
		echo '">';

		echo '<div class="bSmallHead">';
		echo '<div>';

		echo '<div class="bSmallHeadRight">';
		echo T_('Visibility').': ';
		echo '<span class="bStatus">';
		$Comment->status();
		echo '</span>';
		echo '</div>';

		echo '<span class="bDate">';
		$Comment->date();
		echo '</span>@<span class = "bTime">';
		$Comment->time( 'H:i' );
		echo '</span>';

		$Comment->author_email( '', ' &middot; Email: <span class="bEmail">', '</span>' );
		echo ' &middot; <span class="bKarma">';
		$Comment->spam_karma( T_('Spam Karma').': %s%', T_('No Spam Karma') );
		echo '</span>';

		echo '</div>';
		echo '<div style="padding-top:3px">';
		$Comment->author_ip( 'IP: <span class="bIP">', '</span> &middot; ' );
		$Comment->author_url_with_actions( /*$redirect_to*/'', true, true );
		echo '</div>';
		echo '</div>';

		echo '<div class="bCommentContent">';
		echo '<div class="bTitle">';
		echo T_('In response to:')
				.' <a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$Item->dget('title').'</a>';
		echo '</div>';
		echo '<div class="bCommentTitle">';
		echo $Comment->get_title();
		echo '</div>';
		echo '<div class="bCommentText">';
		$Comment->rating();
		$Comment->avatar();
		$Comment->content( 'htmlbody', 'true' );
		echo '</div>';
		echo '</div>';

		echo '<div class="CommentActionsArea">';
		$Comment->permanent_link( array(
				'class'    => 'permalink_right'
			) );

		// Display edit button if current user has the rights:
		$Comment->edit_link( ' ', ' ', '#', '#', 'ActionButton', '&amp;', $save_context, $redirect_to );

		// Display publish NOW button if current user has the rights:
		$Comment->publish_link( ' ', ' ', '#', '#', 'PublishButton', '&amp;', $save_context, true, $redirect_to );

		// Display deprecate button if current user has the rights:
		$Comment->deprecate_link( ' ', ' ', '#', '#', 'DeleteButton', '&amp;', $save_context, true, $redirect_to );

		// Display delete button if current user has the rights:
		$Comment->delete_link( ' ', ' ', '#', '#', 'DeleteButton', false, '&amp;', $save_context, true );

		echo '<div class="clear"></div>';
		echo '</div>';
		echo '</div>';
	}
	else
	{
		echo '<div id="c'.$comment_ID.'" class="bComment bComment';
		$Comment->status('raw');
		echo '">';

		echo '<div class="bSmallHead">';
		echo '<div>';

		echo '<div class="bSmallHeadRight">';
		echo T_('Visibility').': ';
		echo '<span class="bStatus">';
		$Comment->status();
		echo '</span>';
		echo '</div>';

		echo '<span class="bDate">';
		$Comment->date();
		echo '</span>@<span class = "bTime">';
		$Comment->time( 'H:i' );
		echo '</span>';

		echo '</div>';
		echo '</div>';

		if( $is_published )
		{
			echo '<div class="bCommentContent">';
			echo '<div class="bCommentTitle">';
			echo $Comment->get_title();
			echo '</div>';
			echo '<div class="bCommentText">';
			$Comment->rating();
			$Comment->avatar();
			$Comment->content();
			echo '</div>';
			echo '</div>';
		}

		echo '<div class="clear"></div>';
		echo '</div>';
	}
}


/**
 * Display a page link on item full view
 *
 * @param integer the item id
 * @param string link text
 * @param integer the page number
 */
function echo_pagenumber( $item_ID, $text, $value )
{
	echo ' <a href="javascript:startRefreshComments( '.$item_ID.', '.$value.' )">'.$text.'</a>';
}


/**
 * Display page links on item full view
 *
 * @param integer the item id
 * @param integer current page number
 * @param integer all comments number in the list
 */
function echo_pages( $item_ID, $currentpage, $comments_number )
{
	$comments_per_page = 20;
	if( ( ( $currentpage - 1 ) * $comments_per_page ) >= $comments_number )
	{ // current page number is greater then all page number, set current page to the last existing page
		$currentpage = intval( ( $comments_number - 1 ) / $comments_per_page ) + 1;
	}
	echo '<div id="currentpage" value='.$currentpage.' /></div>';
	echo '<div class="results_nav" id="paging">';
	if( $comments_number > 0 )
	{
		echo '<strong>'.T_('Pages').'</strong>:';
		if( $currentpage > 1 )
		{ // previous link
			echo_pagenumber( $item_ID, T_('Previous'), $currentpage - 1 );
		}
		for( $i = 1; ( ( $i - 1 ) * $comments_per_page ) < $comments_number; $i++ )
		{
			if( $i == $currentpage )
			{
				echo ' <strong>'.$i.'</strong>';
			}
			else
			{
				echo_pagenumber( $item_ID, $i, $i );
			}
		}
		if( ( $currentpage * $comments_per_page ) < $comments_number )
		{ // next link
			echo_pagenumber( $item_ID, T_('Next'), $currentpage + 1 );
		}
	}
	echo '</div>';
}


/*
 * $Log: _item.funcs.php,v $
 */
?>