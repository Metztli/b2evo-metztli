<?php
/**
 * This file implements the UI controller for managing comments.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _comments.ctrl.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var AdminUI
 */
global $AdminUI;

/**
 * @var UserSettings
 */
global $UserSettings;

$action = param_action( 'list' );

/*
 * Init the objects we want to work on.
 */
switch( $action )
{
	case 'edit':
	case 'update':
	case 'publish':
	case 'deprecate':
	case 'delete_url':
	case 'update_publish':
	case 'delete':
		param( 'comment_ID', 'integer', true );
		$edited_Comment = & Comment_get_by_ID( $comment_ID );

		$edited_Comment_Item = & $edited_Comment->get_Item();
		set_working_blog( $edited_Comment_Item->get_blog_ID() );
		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( $edited_Comment->blogperm_name(), 'edit', true, $blog );

		// Where are we going to redirect to?
		param( 'redirect_to', 'string', url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&p='.$edited_Comment_Item->ID, '&' ) );
		break;

	case 'elevate':
		global $blog;
		load_class( 'items/model/_item.class.php', 'Item' );

		param( 'comment_ID', 'integer', true );
		$edited_Comment = & Comment_get_by_ID( $comment_ID );

		$BlogCache = & get_BlogCache();
		$Blog = & $BlogCache->get_by_ID( $blog );

		// Check permission:
		$current_User->check_perm( 'blog_post!draft', 'edit', true, $blog );
		break;

	case 'trash_delete':
		param( 'blog_ID', 'integer', 0 );

		// Check permission:
		$current_User->check_perm( 'blogs', 'editall', true );
		break;

	case 'emptytrash':
		// Check permission:
		$current_User->check_perm( 'blogs', 'all', true );
		break;

	case 'list':
	  // Check permission:
		$selected = autoselect_blog( 'blog_comments', 'edit' );
		if( ! $selected )
		{ // No blog could be selected
			$Messages->add( T_('You have no permission to edit comments.' ), 'error' );
			$action = 'nil';
		}
		elseif( set_working_blog( $selected ) )	// set $blog & memorize in user prefs
		{	// Selected a new blog:
			$BlogCache = & get_BlogCache();
			$Blog = & $BlogCache->get_by_ID( $blog );
		}
		break;

	default:
		debug_die( 'unhandled action 1' );
}


$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Contents'), '?ctrl=items&amp;blog=$blog$&amp;tab=full&amp;filter=restore' );
$AdminUI->breadcrumbpath_add( T_('Comments'), '?ctrl=comments&amp;blog=$blog$&amp;filter=restore' );

$AdminUI->set_path( 'items' );	// Sublevel may be attached below

/**
 * Perform action:
 */
switch( $action )
{
 	case 'nil':
		// Do nothing
		break;


	case 'edit':
		$AdminUI->title = $AdminUI->title_titlearea = T_('Editing comment').' #'.$edited_Comment->ID;
		break;


	case 'update_publish':
	case 'update':
		// fp> TODO: $edited_Comment->load_from_Request( true );

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		if( ! $edited_Comment->get_author_User() )
		{ // If this is not a member comment
			param( 'newcomment_author', 'string', true );
			param( 'newcomment_author_email', 'string' );
			param( 'newcomment_author_url', 'string' );
			param( 'comment_allow_msgform', 'integer', 0 /* checkbox */ );

			param_check_not_empty( 'newcomment_author', T_('Please enter and author name.'), '' );
			$edited_Comment->set( 'author', $newcomment_author );
			param_check_email( 'newcomment_author_email', false );
			$edited_Comment->set( 'author_email', $newcomment_author_email );
			param_check_url( 'newcomment_author_url', 'posting', '' ); // Give posting permissions here
			$edited_Comment->set( 'author_url', $newcomment_author_url );
			$edited_Comment->set( 'allow_msgform', $comment_allow_msgform );
		}

		// Move to different post
		if( param( 'moveto_post', 'string', false ) )
		{ // Move to post is set

			$comment_Item = & $edited_Comment->get_Item();
			if( $comment_Item->ID != $moveto_post )
			{ // Move to post was changed
				// Check destination post
				$ItemCache = & get_ItemCache();
				if( ( $dest_Item = $ItemCache->get_by_ID( $moveto_post, false, false) ) !== false )
				{ // the item exists

					$dest_Item_Blog = & $dest_Item->get_Blog();
					$dest_Item_Blog_User = & $dest_Item_Blog->get_owner_User();

					$comment_Item_Blog = & $comment_Item->get_Blog();
					$comment_Item_Blog_User = & $comment_Item_Blog->get_owner_User();

					if( ($current_User->ID == $dest_Item_Blog_User->ID &&
						$current_User->ID == $comment_Item_Blog_User->ID ) ||
						( $current_User->check_perm( 'blog_admin', 'edit', false, $dest_Item_Blog->ID ) &&
						$current_User->check_perm( 'blog_admin', 'edit', false, $comment_Item_Blog->ID ) ) )
					{ // current user is the owner of both the source and the destination blogs or current user is admin for both blogs
						$edited_Comment->set_Item( $dest_Item );
					}
					else
					{
						$Messages->add( T_('Destination post blog owner is different!'), 'error' );
					}
				}
				else
				{ // the item doesn't exists
					$Messages->add( sprintf( T_('Post ID &laquo;%d&raquo; does not exist!'), $moveto_post ), 'error' );
				}
			}
		}

		// Content:
		param( 'content', 'html' );
		param( 'post_autobr', 'integer', ($comments_use_autobr == 'always') ? 1 : 0 );

		param_check_html( 'content', T_('Invalid comment text.'), '#', $post_autobr );	// Check this is backoffice content (NOT with comment rules)
		$edited_Comment->set( 'content', get_param( 'content' ) );

		if( $current_User->check_perm( 'blog_edit_ts', 'edit', false, $Blog->ID ) )
		{ // We use user date
			param_date( 'comment_issue_date', T_('Please enter a valid comment date.'), true );
			if( strlen(get_param('comment_issue_date')) )
			{ // only set it, if a date was given:
				param_time( 'comment_issue_time' );
				$edited_Comment->set( 'date', form_date( get_param( 'comment_issue_date' ), get_param( 'comment_issue_time' ) ) ); // TODO: cleanup...
			}
		}

		param( 'comment_rating', 'integer', NULL );
		$edited_Comment->set_from_Request( 'rating' );

		$comment_status = param( 'comment_status', 'string', 'published' );
		if( $action == 'update_publish' )
		{
			$comment_status = 'published';
		}
		$old_comment_status = $edited_Comment->get( 'status' );
		$edited_Comment->set( 'status', $comment_status );

		param( 'comment_nofollow', 'integer', 0 );
		$edited_Comment->set_from_Request( 'nofollow' );

		if( $Messages->has_errors() )
		{	// There have been some validation errors:
			break;
		}

		if( $old_comment_status != $comment_status )
		{ // Comment moderation is done, don't keep "secret" moderation access
			$edited_Comment->set( 'secret', NULL );
		}

		// UPDATE DB:
		$edited_Comment->dbupdate();	// Commit update to the DB

		if( $edited_Comment->status == 'published' )
		{ // comment status was set to published or it was already published, needs to handle notifications
			$edited_Comment->handle_notifications();
		}

		$Messages->add( T_('Comment has been updated.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'publish':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set('status', 'published' );
		// Comment moderation is done, don't keep "secret" moderation access
		$edited_Comment->set( 'secret', NULL );

		$edited_Comment->dbupdate();	// Commit update to the DB

		// comment status was set to published, needs to handle notifications
		$edited_Comment->handle_notifications();

		$Messages->add( T_('Comment has been published.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'deprecate':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set('status', 'deprecated' );
		// Comment moderation is done, don't keep "secret" moderation access
		$edited_Comment->set( 'secret', NULL );

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment has been deprecated.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'delete_url':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$edited_Comment->set('author_url', NULL );

		$edited_Comment->dbupdate();	// Commit update to the DB

		$Messages->add( T_('Comment url has been deleted.'), 'success' );

		header_redirect( $redirect_to );
		/* exited */
		break;


	case 'delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		// fp> TODO: non JS confirm

		// Delete from DB:
		$edited_Comment->dbdelete();

		$Messages->add( T_('Comment has been deleted.'), 'success' );

		header_redirect( $redirect_to );
		break;



	case 'trash_delete':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$query = 'SELECT T_comments.*
					FROM T_blogs LEFT OUTER JOIN T_categories ON blog_ID = cat_blog_ID
						LEFT OUTER JOIN T_items__item ON cat_ID = post_main_cat_ID
						LEFT OUTER JOIN T_comments ON post_ID = comment_post_ID
					WHERE comment_status = "trash"';

		if( isset($blog_ID) && ( $blog_ID != 0 ) )
		{
			$query .=  'AND blog_ID='.$blog_ID;
		}

		$DB->begin();
		$trash_comments = $DB->get_results( $query, OBJECT, 'get_trash_comments' );

		$result = true;
		foreach( $trash_comments as $row_stats )
		{
			$Comment = new Comment( $row_stats );
			$result = $result && $Comment->dbdelete();
			if( !$result )
			{
				$DB->rollback();
				break;
			}
		}

		if( $result )
		{
			$DB->commit();
			$Messages->add( T_('Recycle bin contents were successfully deleted.'), 'success' );
		}
		else
		{
			$Messages->add( T_('Could not empty recycle bin.'), 'error' );
		}

		header_redirect( regenerate_url( 'action', 'action=list', '', '&' ) );
		break;

	case 'emptytrash':
		/*
		 * Trash comments:
		 */
		$AdminUI->title = $AdminUI->title_titlearea = T_('Comment recycle bins');
		break;

	case 'elevate':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'comment' );

		$item_content = $edited_Comment->get_author_name().' '.T_( 'wrote' ).': <blockquote>'.$edited_Comment->get_content().'</blockquote>';
		$new_Item = new Item();
		$new_Item->set( 'status', 'draft' );
		$new_Item->set_creator_by_login( $current_User->login );
		$new_Item->set( 'main_cat_ID', $Blog->get_default_cat_ID() );
		$new_Item->set( 'title', T_( 'Elevated from comment' ) );
		$new_Item->set( 'content', $item_content );

		if( !$new_Item->dbinsert() )
		{
			$Messages->add( T_( 'Unable to create the new post!' ), 'error' );
			break;
		}

		$edited_Comment->set( 'status', 'deprecated' );
		$edited_Comment->dbupdate();

		header_redirect( url_add_param( $admin_url, 'ctrl=items&blog='.$blog.'&action=edit&p='.$new_Item->ID, '&' ) );
		break;

	case 'list':
		/*
		 * Latest comments:
		 */
		$AdminUI->title = $AdminUI->title_titlearea = T_('Latest comments');

		// Generate available blogs list:
		$AdminUI->set_coll_list_params( 'blog_comments', 'edit',
						array( 'ctrl' => 'comments', 'filter' => 'restore' ), NULL, '' );

		/*
		 * Add sub menu entries:
		 * We do this here instead of _header because we need to include all filter params into regenerate_url()
		 */
		attach_browse_tabs();

		$AdminUI->append_path_level( 'comments' );

		// Set the third level tab
		param( 'tab3', 'string', 'fullview', true );
		$AdminUI->set_path( 'items', 'comments', $tab3 );

		/*
		 * List of comments to display:
		 */
		$CommentList = new CommentList2( $Blog );

		// Filter list:
		$CommentList->set_default_filters( array(
				'statuses' => array( 'published', 'draft', 'deprecated' ),
				'comments' => $UserSettings->get( 'results_per_page', $current_User->ID ),
			) );

		$CommentList->load_from_Request();

		break;


	default:
		debug_die( 'unhandled action 2' );
}


/*
 * Page navigation:
 */

$AdminUI->set_path( 'items', 'comments' );

if( ( $action == 'edit' ) || ( $action == 'update_publish' ) || ( $action == 'update' ) || ( $action == 'elevate' ) )
{ // load date picker style for _comment.form.php
	require_css( 'ui.datepicker.css' );
}

require_css( 'rsc/css/blog_base.css', true );
require_js( 'communication.js' ); // auto requires jQuery

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

/**
 * Display payload:
 */
switch( $action )
{
	case 'nil':
		// Do nothing
		break;


	case 'edit':
	case 'elevate':
	case 'update_publish':
	case 'update':	// on error
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'comments/views/_comment.form.php' );


		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'emptytrash':
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		// Display VIEW:
		$AdminUI->disp_view( 'comments/views/_trash_comments.view.php' );

		// End payload block:
		$AdminUI->disp_payload_end();
		break;

	case 'list':
	default:
		// Begin payload block:
		$AdminUI->disp_payload_begin();

		echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr>';
		echo '<td class="browse_left_col">';
		// Display VIEW:
		if( $tab3 == 'fullview' )
		{
			$AdminUI->disp_view( 'comments/views/_browse_comments.view.php' );
		}
		else
		{
			$AdminUI->disp_view( 'comments/views/_comment_list_table.view.php' );
		}
		echo '</td>';

		echo '<td class="browse_right_col">';
			// Display VIEW:
			$AdminUI->disp_view( 'comments/views/_comments_sidebar.view.php' );
		echo '</td>';

		echo '</tr></table>';

		// End payload block:
		$AdminUI->disp_payload_end();
		break;
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log: _comments.ctrl.php,v $
 */
?>