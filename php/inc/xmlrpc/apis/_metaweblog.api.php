<?php
/**
 * XML-RPC : MetaWeblog API
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @author tor
 *
 * @see http://manual.b2evolution.net/MetaWeblog_API
 * @see http://www.xmlrpc.com/metaWeblogApi
 *
 * @package xmlsrv
 *
 * @version $Id: _metaweblog.api.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


/**
 * Decode the dateCreated
 *
 * @param struct
 * @return string MYSQL date
 */
function _mw_decode_postdate( $contentstruct, $now_if_empty = true )
{
	global $Settings;

	$postdate = NULL;

	if( ! empty($contentstruct['dateCreated']) )
	{
		$postdate = $contentstruct['dateCreated'];
		logIO( 'Using contentstruct dateCreated: '.$postdate );
	}
	elseif( $now_if_empty );
	{
		$postdate = date('Y-m-d H:i:s', (time() + $Settings->get('time_difference')));
		logIO( 'No contentstruct dateCreated, using now: '.$postdate );
	}

	return $postdate;
}


/**
 * Get IDs for requested categories
 *
 * @param array struct
 * @param integer blog ID
 * @param boolean Return empty array (instead of error), if no cats given in struct?
 * @return array|xmlrpcresp A list of category IDs or xmlrpcresp in case of error.
 */
function _mw_get_cat_IDs( $contentstruct, $blog_ID, $empty_struct_ok = false )
{
	global $DB;

	$categories = array();
	if( isset($contentstruct['categories']) )
	{
		foreach( $contentstruct['categories'] as $l_catname )
		{
			$categories[] = trim(strip_tags($l_catname));
		}
	}

	logIO( 'finished getting categories...'.implode( ', ', $categories ) );

	if( $empty_struct_ok && empty($categories) )
	{
		return $categories;
	}

	logIO( 'Categories: '.implode( ', ', $categories ) );

	// for cross-blog-entries, the cat_blog_ID WHERE clause should be removed (but cats are given by name!)
	if( ! empty($categories) )
	{
		$sql = "
			SELECT cat_ID FROM T_categories
			 WHERE cat_blog_ID = $blog_ID
				 AND cat_name IN ( ";
		foreach( $categories as $l_cat )
		{
			$sql .= $DB->quote($l_cat).', ';
		}
		if( ! empty($categories) )
		{
			$sql = substr($sql, 0, -2); // remove ', '
		}
		$sql .= ' )';
		logIO('sql for finding IDs ...'.$sql);

		$cat_IDs = $DB->get_col( $sql );
		if( $DB->error )
		{	// DB error
			logIO('user error finding categories info ...');
		}
	}
	else
	{
		$cat_IDs = array();
	}

	if( ! empty($cat_IDs) )
	{ // categories requested to be set:

		// Check if category exists
		// Tblue> Why is this needed?
		$ChapterCache = & get_ChapterCache();
		if( $ChapterCache->get_by_ID( $cat_IDs[0], false ) === false )
		{ // Main cat does not exist:
			logIO('usererror 5 ...');
			return xmlrpcs_resperror( 5, 'Requested category does not exist.' ); // user error 5
		}
		logIO('finished checking if main category exists ...'.$cat_IDs[0]);
	}
	else
	{ // No category given/valid - use the first for the blog:
		logIO('No category for post given ...');

		$first_cat = $DB->get_var( '
			SELECT cat_ID
			  FROM T_categories
			 WHERE cat_blog_ID = '.$blog_ID.'
			 LIMIT 1' );
		if( empty($first_cat) )
		{
			logIO( 'No categories for this blog...');
			return xmlrpcs_resperror( 5, 'No categories for this blog.' ); // user error 5
		}
		else
		{
			$cat_IDs = array($first_cat);
		}
	}

	return $cat_IDs;
}



$mwnewMediaObject_doc = 'Uploads a file to the media library of the blog';
$mwnewMediaObject_sig = array(array( $xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcStruct ));
/**
 * metaWeblog.newMediaObject  image upload
 *
 * image is supplied coded in the info struct as bits
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metaweblognewmediaobject
 *
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 * 							- name : filename
 * 							- type : mimetype
 * 							- bits : base64 encoded file
 * @return xmlrpcresp XML-RPC Response
 */
function mw_newmediaobject($m)
{
	logIO('mw_newmediaobject');
	return _wp_mw_newmediaobject( $m );
}




$mwnewpost_doc='Adds a post, blogger-api like, +title +category +postdate';
$mwnewpost_sig =  array(array($xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));
/**
 * metaWeblog.newPost
 *
 * NB! (Tor Feb 2005) status in metaweblog API speak dictates whether static html files are generated or not, so fairly misleading
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 */
function mw_newpost($m)
{
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// getParam(4) should now be a flag for publish or draft
	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO("Publish: $xstatus -> Status: $status");

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO( 'Decoded xcontent' );

	// Categories:
	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $Blog->ID );
	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;	// This can be a preformatted error message
	}
	$main_cat = $cat_IDs[0];

	if( ! xmlrpcs_check_cats( $main_cat, $Blog, $cat_IDs ) )
	{	// Error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, $cat_IDs ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	$post_date = _mw_decode_postdate( $contentstruct, true );
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];

	// non-standard MT extensions
	$tags = isset( $contentstruct['mt_keywords'] ) ? $contentstruct['mt_keywords'] : '';

	$allow_comments = 'open';
	if( isset($contentstruct['mt_allow_comments']) && ! $contentstruct['mt_allow_comments'] )
	{
		$allow_comments = 'disabled'; // Tblue> I think disabled makes sense here since it is a new post.
	}

	$excerpt = '';
	if( isset($contentstruct['mt_excerpt']) )
	{
		$excerpt = $contentstruct['mt_excerpt'];
	}

	// COMPLETE VALIDATION & INSERT:
	return xmlrpcs_new_item( $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags, $allow_comments, $excerpt );
}




$mweditpost_doc='Edits a post, blogger-api like, +title +category +postdate';
$mweditpost_sig =  array(array($xmlrpcBoolean,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcStruct,$xmlrpcBoolean));
/**
 * metaWeblog.editPost (metaWeblog.editPost)
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#basicEntrypoints
 *
 * @todo Tor - TODO
 *		- Sort out sql select with blog ID
 *		- screws up posts with multiple categories
 *		  partly due to the fact that Movable Type calls to this API are different to Metaweblog API calls when handling categories.
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post to edit
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 *					3 struct (struct)
 */
function mw_editpost( $m )
{
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 0 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// We need to be able to edit this post:
	if( ! $current_User->check_perm( 'item_post!CURSTATUS', 'edit', false, $edited_Item ) )
	{
		return xmlrpcs_resperror( 3 ); // Permission denied
	}

	$xstatus = $m->getParam(4);
	$xstatus = $xstatus->scalarval();
	$status = $xstatus ? 'published' : 'draft';
	logIO("Publish: $xstatus -> Status: $status");

	$xcontent = $m->getParam(3);
	$contentstruct = xmlrpc_decode_recurse($xcontent);
	logIO('Decoded xcontent');

	// Categories:
	$cat_IDs = _mw_get_cat_IDs( $contentstruct, $edited_Item->get_blog_ID(), true /* empty is ok */ );
	if( ! is_array($cat_IDs) )
	{ // error:
		return $cat_IDs;
	}

	if( empty( $cat_IDs ) )
	{
		$cat_IDs = postcats_get_byID( $edited_Item->ID );
	}
	$main_cat = $cat_IDs[0];

	$Blog = & $edited_Item->get_Blog();
	if( ! xmlrpcs_check_cats( $main_cat, $Blog, $cat_IDs ) )
	{	// Error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION: (we need perm on all categories, especially if they are in different blogs)
	if( ! $current_User->check_perm( 'cats_post!'.$status, 'edit', false, $cat_IDs ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );

	$post_date = _mw_decode_postdate( $contentstruct, false );
	$post_title = $contentstruct['title'];
	$content = $contentstruct['description'];
	$tags = isset( $contentstruct['mt_keywords'] ) ? $contentstruct['mt_keywords'] : NULL /* don't change tags */; // non-standard MT extension

	// COMPLETE VALIDATION & UPDATE:
	return xmlrpcs_edit_item( $edited_Item, $post_title, $content, $post_date, $main_cat, $cat_IDs, $status, $tags );


	/*
	// Time to perform trackbacks NB NOT WORKING YET
	//
	// NB Requires a change to the _trackback library
	//
	// function trackbacks( $post_trackbacks, $content, $post_title, $post_ID )

	// first extract these from posting as post_trackbacks array, then rest is easy
	// 	<member>
	//		<name>mt_tb_ping_urls</name>
	//	<value><array><data>
	//		<value><string>http://archive.scripting.com/2005/04/17</string></value>
	//	</data></array></value>
	//	</member>
	// First check that trackbacks are allowed - mt_allow_pings
	$trackback_ok = 0;
	$trackbacks = array();
	$trackback_ok = $contentstruct['mt_allow_pings'];
	logIO("Trackback OK  ...".$trackback_ok);
	if ($trackback_ok == 1)
	{
		$trackbacks = $contentstruct['mt_tb_ping_urls'];
		logIO("Trackback url 0  ...".$trackbacks[0]);
		$no_of_trackbacks = count($trackbacks);
		logIO("Number of Trackbacks  ...".$no_of_trackbacks);
		if ($no_of_trackbacks > 0)
		{
			logIO("Calling Trackbacks  ...");
			load_funcs('comments/_trackback.funcs.php');
 			$result = trackbacks( $trackbacks, $content, $post_title, $post_ID );
			logIO("Returned from  Trackbacks  ...");
 		}

	}
	*/
}




$mwgetcats_sig =  array(array($xmlrpcStruct,$xmlrpcString,$xmlrpcString,$xmlrpcString));
$mwgetcats_doc = 'Get categories of a post, MetaWeblog API-style';
/**
 * metaWeblog.getCategories
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetcategories
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 */
function mw_getcategories( $m )
{
	logIO('mw_getcategories');
	return _wp_mw_getcategories ( $m ) ;
}




$metawebloggetrecentposts_doc = 'fetches X most recent posts, blogger-api like';
$metawebloggetrecentposts_sig =  array(array($xmlrpcArray,$xmlrpcString,$xmlrpcString,$xmlrpcString,$xmlrpcInt));
/**
 * metaWeblog.getRecentPosts
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#metawebloggetrecentposts
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 blogid (string): Unique identifier of the blog the post will be added to.
 *						Currently ignored in b2evo, in favor of the category.
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 */
function mw_getrecentposts( $m )
{
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET BLOG:
	/**
	 * @var Blog
	 */
	if( ! $Blog = & xmlrpcs_get_Blog( $m, 0 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	$numposts = $m->getParam(3);
	$numposts = $numposts->scalarval();
	logIO('In mw_getrecentposts, current numposts is ...'. $numposts);

	// Get the posts to display:
	load_class( 'items/model/_itemlist.class.php', 'ItemList' );
	$MainList = new ItemList2( $Blog, NULL, NULL, $numposts );

	// Protected and private get checked by statuses_where_clause().
	$statuses = array( 'published', 'redirected', 'protected', 'private' );
	if( $current_User->check_perm( 'blog_ismember', 'view', false, $Blog->ID ) )
	{	// These statuses require member status:
		$statuses = array_merge( $statuses, array( 'draft', 'deprecated' ) );
	}
	logIO( 'Statuses: '.implode( ', ', $statuses ) );

	$MainList->set_filters( array(
			'visibility_array' => $statuses,
			'order' => 'DESC',
			'unit' => 'posts',
		) );
	// Run the query:
	$MainList->query();

	logIO( 'Items:'.$MainList->result_num_rows );

	$data = array();
	/**
	 * @var Item
	 */
	while( $Item = & $MainList->get_item() )
	{
		logIO( 'Item:'.$Item->title.
					' - Issued: '.$Item->issue_date.
					' - Modified: '.$Item->mod_date );
		$post_date = mysql2date('U', $Item->issue_date);
		$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);
		$content = $Item->content;
		// Load Item's creator User:
		$Item->get_creator_User();
		$authorname = $Item->creator_User->get('preferredname');
		// need a loop here to extract all categoy names
		// $extra_cat_IDs is the variable for the rest of the IDs
		$hope_Chapter = & $Item->get_main_Chapter();
		logIO( 'postcats: '.$hope_Chapter->name );
		$data[] = new xmlrpcval(array(
				'dateCreated' => new xmlrpcval($post_date,'dateTime.iso8601'),
				'userid' => new xmlrpcval($Item->creator_user_ID),
				'postid' => new xmlrpcval($Item->ID),
				'categories' => new xmlrpcval(array(new xmlrpcval($hope_Chapter->name)),'array'),
				'title' => new xmlrpcval($Item->title),
				'description' => new xmlrpcval($content),
				'link' => new xmlrpcval($Item->url),
				'publish' => new xmlrpcval(($Item->status == 'published'),'boolean'),
				'mt_keywords' => new xmlrpcval( implode( ',', $Item->get_tags() ), 'string' ),
				/*
				"permalink" => new xmlrpcval($Item->urltitle),
				"mt_excerpt" => new xmlrpcval($content),
				"mt_allow_comments" => new xmlrpcval('1'),
				"mt_allow_pings" => new xmlrpcval('1'),
				"mt_text_more" => new xmlrpcval('')
				*/
			),'struct');
	}

	logIO( 'OK.' );
	return new xmlrpcresp( new xmlrpcval( $data, 'array' ) );
}


$mwgetusersblogs_doc='returns the user\'s blogs - this is a dummy function, just so that BlogBuddy and other blogs-retrieving apps work';
$mwgetusersblogs_sig=array(array($xmlrpcArray, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * metaweblog.getUsersBlogs returns information about all the blogs a given user is a member of.
 *
 * Data is returned as an array of <struct>s containing the ID (blogid), name (blogName),
 * and URL (url) of each blog.
 *
 * Non official: Also return a boolean stating wether or not the user can edit th eblog templates
 * (isAdmin).
 *
 * @see http://www.xmlrpc.com/stories/storyReader$2460
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 username (string): Login for the Blogger user who's blogs will be retrieved.
 *					2 password (string): Password for said username.
 *						(currently not required by b2evo)
 * @return xmlrpcresp XML-RPC Response, an array of <struct>s containing for each blog:
 *					- ID (blogid),
 *					- name (blogName),
 *					- URL (url),
 *					- bool: can user edit template? (isAdmin).
 */
function mw_getusersblogs($m)
{
	logIO('mw_getusersblogs start');
	return _wp_or_blogger_getusersblogs( 'blogger', $m );
}


$mwgetpost_doc = 'Fetches a post, blogger-api like';
$mwgetpost_sig = array(array($xmlrpcStruct, $xmlrpcString, $xmlrpcString, $xmlrpcString));
/**
 * metaweblog.getPost retieves a given post.
 *
 * @see http://www.xmlrpc.com/metaWeblogApi#basicEntrypoints
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 postid (string): Unique identifier of the post
 *					1 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					2 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function mw_getpost($m)
{
	// CHECK LOGIN:
	/**
	 * @var User
	 */
	if( ! $current_User = & xmlrpcs_login( $m, 1, 2 ) )
	{	// Login failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// GET POST:
	/**
	 * @var Item
	 */
	if( ! $edited_Item = & xmlrpcs_get_Item( $m, 0 ) )
	{	// Failed, return (last) error:
		return xmlrpcs_resperror();
	}

	// CHECK PERMISSION:
	if( ! xmlrpcs_can_view_item( $edited_Item, $current_User ) )
	{	// Permission denied
		return xmlrpcs_resperror( 3 );	// User error 3
	}
	logIO( 'Permission granted.' );


	$post_date = mysql2date( 'U', $edited_Item->issue_date );
	$post_date = gmdate('Ymd', $post_date).'T'.gmdate('H:i:s', $post_date);

	$struct = new xmlrpcval(array(
			'link'              => new xmlrpcval( $edited_Item->get_permanent_url()),
			'title'             => new xmlrpcval( $edited_Item->title),
			'description'       => new xmlrpcval( $edited_Item->content),
			'dateCreated'       => new xmlrpcval( $post_date,'dateTime.iso8601'),
			'userid'            => new xmlrpcval( $edited_Item->creator_user_ID),
			'postid'            => new xmlrpcval( $edited_Item->ID),
			'content'           => new xmlrpcval( $edited_Item->content),
			'permalink'         => new xmlrpcval( $edited_Item->get_permanent_url()),
			'categories'        => new xmlrpcval( $edited_Item->main_cat_ID),	// TODO: CATEGORY NAMES!
			'mt_keywords'       => new xmlrpcval( implode( ',', $edited_Item->get_tags() ), 'string' ),
			'mt_excerpt'        => new xmlrpcval( $edited_Item->excerpt),
			/*
			'mt_allow_comments' => new xmlrpcval( $edited_Item->comment_status,'int'), // TODO: convert, looking for doc!!?
			'mt_allow_pings'    => new xmlrpcval( $edited_Item->notifications_status,'int'), // TODO: convert
			'mt_text_more'      => new xmlrpcval( "")	// Doc?
			*/
		),'struct');

	logIO( 'OK.' );
	return new xmlrpcresp($struct);
}


$mwdeletepost_doc = 'Deletes a post, blogger-api like';
$mwdeletepost_sig = array(array($xmlrpcBoolean, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcString, $xmlrpcBoolean));
/**
 * metaWeblog.deletePost deletes a given post.
 *
 * This API call is not documented on
 * {@link http://www.blogger.com/developers/api/1_docs/}
 * @see http://www.xmlrpc.com/stories/storyReader$2460
 *
 * @param xmlrpcmsg XML-RPC Message
 *					0 appkey (string): Unique identifier/passcode of the application sending the post.
 *						(See access info {@link http://www.blogger.com/developers/api/1_docs/#access} .)
 *					1 postid (string): Unique identifier of the post to be deleted.
 *					2 username (string): Login for a Blogger user who has permission to edit the given
 *						post (either the user who originally created it or an admin of the blog).
 *					3 password (string): Password for said username.
 * @return xmlrpcresp XML-RPC Response
 */
function mw_deletepost($m)
{
	logIO('mw_deletepost start');
	return _mw_blogger_deletepost( $m );
}


$xmlrpc_procs['metaWeblog.newMediaObject'] = array(
				'function' => 'mw_newmediaobject',
				'signature' => $mwnewMediaObject_sig,
				'docstring' => $mwnewMediaObject_doc);

$xmlrpc_procs['metaWeblog.newPost'] = array(
				'function' => 'mw_newpost',
				'signature' => $mwnewpost_sig,
				'docstring' => $mwnewpost_doc );

$xmlrpc_procs['metaWeblog.editPost'] = array(
				'function' => 'mw_editpost',
				'signature' => $mweditpost_sig,
				'docstring' => $mweditpost_doc );

$xmlrpc_procs['metaWeblog.getPost'] = array(
				'function' => 'mw_getpost',
				'signature' => $mwgetpost_sig,
				'docstring' => $mwgetpost_doc );

$xmlrpc_procs['metaWeblog.getCategories'] = array(
				'function' => 'mw_getcategories',
				'signature' => $mwgetcats_sig,
				'docstring' => $mwgetcats_doc );

$xmlrpc_procs['metaWeblog.getRecentPosts'] = array(
				'function' => 'mw_getrecentposts',
				'signature' => $metawebloggetrecentposts_sig,
				'docstring' => $metawebloggetrecentposts_doc );

// Blogger aliases, as in http://www.xmlrpc.com/stories/storyReader$2460

$xmlrpc_procs['metaWeblog.deletePost'] = array(
				'function' => 'mw_deletepost',
				'signature' => $mwdeletepost_sig,
				'docstring' => $mwdeletepost_doc );

$xmlrpc_procs['metaWeblog.getUsersBlogs'] = array(
				'function' => 'mw_getusersblogs',
				'signature' => $mwgetusersblogs_sig,
				'docstring' => $mwgetusersblogs_doc );


/*
 * $Log: _metaweblog.api.php,v $
 */
?>