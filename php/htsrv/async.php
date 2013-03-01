<?php
/**
 * This is the handler for asynchronous 'AJAX' calls.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * fp> TODO: it would be better to have the code for the actions below part of the controllers they belong to.
 * This would require some refectoring but would be better for maintenance and code clarity.
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
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package evocore
 *
 * @version $Id: async.php 1599 2012-07-26 06:22:33Z yura $
 */


/**
 * Do the MAIN initializations:
 */
require_once dirname(__FILE__).'/../conf/_config.php';

/**
 * HEAVY :(
 *
 * @todo dh> refactor _main.inc.php to be able to include small parts
 *           (e.g. $current_User, charset init, ...) only..
 *           It worked already for $DB (_connect_db.inc.php).
 * fp> I think I'll try _core_main.inc , _evo_main.inc , _blog_main.inc ; this file would only need _core_main.inc
 */
require_once $inc_path.'_main.inc.php';

param( 'action', 'string', '' );

// Check global permission:
if( empty($current_User) || ! $current_User->check_perm( 'admin', 'restricted' ) )
{	// No permission to access admin...
	require $adminskins_path.'_access_denied.main.php';
}


// Make sure the async responses are never cached:
header_nocache();
header_content_type( 'text/html', $io_charset );

// Do not append Debuglog to response!
$debug = false;


// fp> Does the following have an HTTP fallback when Javascript/AJ is not available?
// dh> yes, but not through this file..
// dh> IMHO it does not make sense to let the "normal controller" handle the AJAX call
//     if there's something lightweight like calling "$UserSettings->param_Request()"!
//     Hmm.. bad example (but valid). Better example: something like the actions below, which
//     output only a small part of what the "real controller" does..
switch( $action )
{
	case 'add_plugin_sett_set':
		// Add a Plugin(User)Settings set (for "array" type settings):

		param( 'plugin_ID', 'integer', true );

		$admin_Plugins = & get_Plugins_admin(); // use Plugins_admin, because a plugin might be disabled
		$Plugin = & $admin_Plugins->get_by_ID($plugin_ID);
		if( ! $Plugin )
		{
			bad_request_die('Invalid Plugin.');
		}
		param( 'set_type', 'string', '' ); // "Settings" or "UserSettings"
		if( $set_type != 'Settings' /* && $set_type != 'UserSettings' */ )
		{
			bad_request_die('Invalid set_type param!');
		}
		param( 'set_path', '/^\w+(?:\[\w+\])+$/', '' );

		load_funcs('plugins/_plugin.funcs.php');

		// Init the new setting set:
		_set_setting_by_path( $Plugin, $set_type, $set_path, array() );

		$r = get_plugin_settings_node_by_path( $Plugin, $set_type, $set_path, /* create: */ false );

		$Form = new Form(); // fake Form
		autoform_display_field( $set_path, $r['set_meta'], $Form, $set_type, $Plugin, NULL, $r['set_node'] );
		exit(0);

	case 'del_plugin_sett_set':
		// TODO: may use validation here..
		echo 'OK';
		exit(0);

	case 'admin_blogperms_set_layout':
		// Save blog permission tab layout into user settings. This gets called on JS-toggling.
		$UserSettings->param_Request( 'layout', 'blogperms_layout', 'string', $debug ? 'all' : 'default' );  // table layout mode
		exit(0);

	case 'set_item_link_position':
		// Change a position of a link on the edit item screen (fieldset "Images & Attachments")

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'itemlink' );

		param('link_ID', 'integer', true);
		param('link_position', 'string', true);

		$LinkCache = & get_LinkCache();
		$Link = & $LinkCache->get_by_ID($link_ID);

		if( $Link->set('position', $link_position)
			&& $Link->dbupdate() )
		{
			echo 'OK';
		}
		else
		{ // return the current value on failure
			echo $Link->get('position');
		}
		exit(0);

	case 'get_login_list':
		// fp> TODO: is there a permission to just 'view' users? It would be appropriate here
		$current_User->check_perm( 'users', 'edit', true );

		$text = trim( urldecode( param( 'q', 'string', '' ) ) );

		/**
		 * sam2kb> The code below decodes percent-encoded unicode string produced by Javascript "escape"
		 * function in format %uxxxx where xxxx is a Unicode value represented as four hexadecimal digits.
		 * Example string "MAMA" (cyrillic letters) encoded with "escape": %u041C%u0410%u041C%u0410
		 * Same word encoded with "encodeURI": %D0%9C%D0%90%D0%9C%D0%90
		 *
		 * jQuery hintbox plugin uses "escape" function to encode URIs
		 *
		 * More info here: http://en.wikipedia.org/wiki/Percent-encoding#Non-standard_implementations
		 */
		if( preg_match( '~%u[0-9a-f]{3,4}~i', $text ) && version_compare(PHP_VERSION, '5', '>=') )
		{	// Decode UTF-8 string (PHP 5 and up)
			$text = preg_replace( '~%u([0-9a-f]{3,4})~i', '&#x\\1;', $text );
			$text = html_entity_decode( $text, ENT_COMPAT, 'UTF-8' );
		}

		if( !empty( $text ) )
		{
			$SQL = new SQL();
			$SQL->SELECT( 'user_login' );
			$SQL->FROM( 'T_users' );
			$SQL->WHERE( 'user_login LIKE "'.$DB->escape($text).'%"' );
			$SQL->LIMIT( '10' );
			$SQL->ORDER_BY('user_login');

			echo implode( "\n", $DB->get_col($SQL->get()) );
		}

		exit(0);

	case 'get_opentrash_link':
		// Used to get a link 'Open recycle bin' in order to show it in the header of comments list

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		echo get_opentrash_link( true, true );
		exit(0);

	case 'set_comment_status':
		// Used for quick moderation of comments

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$moderation = param( 'moderation', 'string', NULL );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
		if( $edited_Comment !== false )
		{	// The comment still exists
			$redirect_to = param( 'redirect_to', 'string', NULL );
			// Check permission:
			$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

			$status = param( 'status', 'string' );
			$edited_Comment->set( 'status', $status );
			// Comment moderation is done, don't keep "secret" moderation access
			$edited_Comment->set( 'secret', NULL );
			$edited_Comment->dbupdate();

			if( $status == 'published' )
			{
				$edited_Comment->handle_notifications();
			}

			if( $moderation != NULL )
			{
				$statuses = param( 'statuses', 'string', NULL );
				$item_ID = param( 'itemid', 'integer' );
				$currentpage = param( 'currentpage', 'integer', 1 );

				if( strlen($statuses) > 2 )
				{
					$statuses = substr( $statuses, 1, strlen($statuses) - 2 );
				}
				$status_list = explode( ',', $statuses );
				if( $status_list == NULL )
				{
					$status_list = array( 'published', 'draft', 'deprecated' );
				}

				echo_item_comments( $blog, $item_ID, $status_list, $currentpage );
				exit(0);
			}
		}

		if( $moderation == NULL )
		{
			get_comments_awaiting_moderation( $blog );
		}

		exit(0);

	case 'delete_comment':
		// Delete a comment from dashboard screen

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
		if( $edited_Comment !== false )
		{	// The comment still exists
			// Check permission:
			$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );
			$edited_Comment->dbdelete();
		}

		get_comments_awaiting_moderation( $blog );
		exit(0);

	case 'delete_comments':
		// Delete the comments from the list on dashboard, on comments full text view screen or on a view item screen

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$commentIds = param( 'commentIds', 'array' );
		$statuses = param( 'statuses', 'string', NULL );
		$item_ID = param( 'itemid', 'integer' );
		$currentpage = param( 'currentpage', 'integer', 1 );

		foreach( $commentIds as $commentID )
		{
			$edited_Comment = & Comment_get_by_ID( $commentID, false );
			if( $edited_Comment !== false )
			{ // The comment still exists
				$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );
				$edited_Comment->dbdelete();
			}
		}

		if( strlen($statuses) > 2 )
		{
			$statuses = substr( $statuses, 1, strlen($statuses) - 2 );
		}
		$status_list = explode( ',', $statuses );
		if( $status_list == NULL )
		{
			$status_list = array( 'published', 'draft', 'deprecated' );
		}

		echo_item_comments( $blog, $item_ID, $status_list, $currentpage );
		exit(0);

	case 'delete_comment_url':
		// Delete spam URL from a comment directly in the dashboard - comment remains otherwise untouched

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );
		$edited_Comment = & Comment_get_by_ID( param( 'commentid', 'integer' ), false );
		if( $edited_Comment !== false && $edited_Comment->author_url != NULL )
		{	// The comment still exists
			// Check permission:
			$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );
			$edited_Comment->set( 'author_url', NULL );
			$edited_Comment->dbupdate();
		}

		exit(0);

	case 'refresh_comments':
		// Refresh the comments list on dashboard by clicking on the refresh icon or after ban url

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		global $blog;

		$blog = param( 'blogid', 'integer' );

		get_comments_awaiting_moderation( $blog );
		exit(0);

	case 'refresh_item_comments':
		// Refresh item comments on the item view screen, or refresh all blog comments, if param itemid = -1
		// A refresh is used on the actions:
		// 1) click on the refresh icon.
		// 2) limit by selected status(radioboxes 'Draft', 'Published', 'All comments').
		// 3) ban by url of a comment

		load_funcs( 'items/model/_item.funcs.php' );

		$blog = param( 'blogid', 'integer' );
		$item_ID = param( 'itemid', 'integer', NULL );
		$statuses = param( 'statuses', 'string', NULL );
		$currentpage = param( 'currentpage', 'string', 1 );

		//$statuses = init_show_comments();
		if( strlen($statuses) > 2 )
		{
			$statuses = substr( $statuses, 1, strlen($statuses) - 2 );
		}
		$status_list = explode( ',', $statuses );
		if( $status_list == NULL )
		{
			$status_list = array( 'published', 'draft', 'deprecated' );
		}

		echo_item_comments( $blog, $item_ID, $status_list, $currentpage );
		exit(0);

	case 'get_tags':
		// Get list of tags, where $term matches at the beginning or anywhere (sorted)
		// To be used for Tag autocompletion

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'item' ); // via item forms

		$term = param('term', 'string');

		echo json_encode( $DB->get_col('
			(
			SELECT tag_name
			  FROM T_items__tag
			 WHERE tag_name LIKE '.$DB->quote($term.'%').'
			 ORDER BY tag_name
			) UNION (
			SELECT tag_name
			  FROM T_items__tag
			 WHERE tag_name LIKE '.$DB->quote('%'.$term.'%').'
			 ORDER BY tag_name
			)') );
		exit(0);
}


/**
 * Get comments awaiting moderation
 *
 * @param integer blog_ID
 */
function get_comments_awaiting_moderation( $blog_ID )
{
	$limit = 5;

	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	show_comments_awaiting_moderation( $blog_ID, $limit, array(), false );
}


/**
 * Call the handler/dispatcher (it is a common handler for asynchronous calls -- both AJax calls and HTTP GET fallbacks)
 */
require_once $inc_path.'_async.inc.php';


// Debug info:
echo '-expand='.$expand;
echo '-collapse='.$collapse;

/*
 * $Log: async.php,v $
 */
?>