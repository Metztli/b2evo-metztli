<?php
/**
 * This file implements the UI controller for blog params management, including permissions.
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @todo (sessions) When creating a blog, provide "edit options" (3 tabs) instead of a single long "New" form (storing the new Blog object with the session data).
 * @todo Currently if you change the name of a blog it gets not reflected in the blog list buttons!
 *
 * @version $Id: collections.ctrl.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

$AdminUI->set_path( 'blogs' );

param_action( 'list' );

if( $action != 'new'
	&& $action != 'new-selskin'
	&& $action != 'new-name'
	&& $action != 'list'
	&& $action != 'create' )
{
	if( valid_blog_requested() )
	{
		// echo 'valid blog requested';
		$edited_Blog = & $Blog;
	}
	else
	{
		// echo 'NO valid blog requested';
		$action = 'list';
	}
}
else
{	// We are not working on a specific blog (yet) -- prevent highlighting one in the list
	set_working_blog( 0 );
}


/**
 * Perform action:
 */
switch( $action )
{
	case 'new':
		// New collection:
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$AdminUI->append_path_level( 'new', array( 'text' => T_('New') ) );
		break;

	case 'new-selskin':
		// New collection:
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		param( 'kind', 'string', true );

		// dh> TODO: "New %s" is probably too generic. What can %s become? (please comment it in "TRANS")
		// Tblue> Look at get_collection_kinds(). I wrote a TRANS comment (30.01.09 22:03, HEAD).
		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( /* TRANS: %s can become "Photoblog", "Group blog" or "Standard blog" */ T_('New %s'), get_collection_kinds($kind) ) ) );
		break;

	case 'new-name':
		// New collection:
		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );

 		param( 'skin_ID', 'integer', true );

		$AdminUI->append_path_level( 'new', array( 'text' => sprintf( T_('New %s'), get_collection_kinds($kind) ) ) );
		break;

	case 'create':
		// Insert into DB:

		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );

		// Check permissions:
		$current_User->check_perm( 'blogs', 'create', true );

		$edited_Blog = new Blog( NULL );

		$edited_Blog->set( 'owner_user_ID', $current_User->ID );

		param( 'kind', 'string', true );
		$edited_Blog->init_by_kind( $kind );
		if( ! $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
		{ // validate the urlname, which was already set by init_by_kind() function
		 	// It needs to validated, because the user can not set the blog urlname, and every new blog would have the same urlname without validation.
		 	// When user has edit permission to blog admin part, the urlname will be validated in load_from_request() function.
			$edited_Blog->set( 'urlname', urltitle_validate( $edited_Blog->get( 'urlname' ) , '', 0, false, 'blog_urlname', 'blog_ID', 'T_blogs' ) );
		}

 		param( 'skin_ID', 'integer', true );
		$edited_Blog->set( 'skin_ID', $skin_ID );

		if( $edited_Blog->load_from_Request( array() ) )
		{
			// create the new blog
			$edited_Blog->create();

			// We want to highlight the edited object on next list display:
 			// $Session->set( 'fadeout_array', array( 'blog_ID' => array($edited_Blog->ID) ) );

			header_redirect( $dispatcher.'?ctrl=coll_settings&tab=features&blog='.$edited_Blog->ID ); // will save $Messages into Session
		}
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'collection' );
		
		// Check permissions:
		$current_User->check_perm( 'blog_properties', 'edit', true, $blog );

		if( param( 'confirm', 'integer', 0 ) )
		{ // confirmed
			// Delete from DB:
			$msg = sprintf( T_('Blog &laquo;%s&raquo; deleted.'), $edited_Blog->dget('name') );

			param( 'delete_static_file', 'integer', 0 );
			$edited_Blog->dbdelete( $delete_static_file );

			$Messages->add( $msg, 'success' );

			$BlogCache->remove_by_ID( $blog );
			unset( $edited_Blog );
			unset( $Blog );
			forget_param( 'blog' );
			set_working_blog( 0 );
			$UserSettings->delete( 'selected_blog' );	// Needed or subsequent pages may try to access the delete blog
			$UserSettings->dbupdate();

			$action = 'list';
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=collections', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;


	case 'GenStatic':
		// ----------  Generate static homepage for blog ----------
		$AdminUI->append_to_titlearea( sprintf( T_('Generating static page for blog [%s]'), $edited_Blog->dget('name') ) );
		$current_User->check_perm( 'blog_genstatic', 'any', true, $blog );

		param( 'redir_after_genstatic', 'string', NULL );

		$sourcefile = $edited_Blog->get_setting('source_file');
		if( empty( $sourcefile ) )
		{
			$Messages->add( T_('You haven\'t defined a source file for this blog!') );
		}
		else
		{
			$staticfilename = $edited_Blog->get_setting('static_file');
			if( empty( $staticfilename ) )
			{
				$Messages->add( T_('You haven\'t defined a static file for this blog!') );
			}
			else
			{
				// GENERATION!
				$static_gen_saved_locale = $current_locale;
				$generating_static = true;
				$resolve_extra_path = false;

				ob_start();

				// Set some defaults in case they're not set by stub/source file:
				// We need to set required variables
				$blog = $edited_Blog->ID;
				# This setting retricts posts to those published, thus hiding drafts.
				$show_statuses = array();
				# Here you can set a limit before which posts will be ignored
				$timestamp_min = '';
				# Here you can set a limit after which posts will be ignored
				$timestamp_max = 'now';

				require $edited_Blog->get('dynfilepath');

				$generated_static_page_html = ob_get_contents();
				ob_end_clean();

				unset( $generating_static );

				// Switch back to saved locale (the blog page may have changed it):
				locale_activate( $static_gen_saved_locale);

				$staticfilename = $edited_Blog->get('staticfilepath');

				if( ! ($fp = @fopen( $staticfilename, 'w' )) )
				{ // could not open file
					$Messages->add( T_('File cannot be written!') );
					$Messages->add( sprintf( '<p>'.T_('You should check the file permissions for [%s]. See <a %s>online manual on file permissions</a>.').'</p>',$staticfilename, 'href="http://manual.b2evolution.net/Directory_and_file_permissions"' ) );
				}
				else
				{ // file is writable
					fwrite( $fp, $generated_static_page_html );
					fclose( $fp );
					$Messages->add( sprintf( T_('Generated static file &laquo;%s&raquo;.'), $staticfilename ), 'success' );
				}
			}
		}

		if( !empty( $redir_after_genstatic ) )
		{
			header_redirect( $redir_after_genstatic );
		}
		break;
}

/**
 * Display page header, menus & messages:
 */
if( strpos( $action, 'new' ) === false )
{ // Not creating a new blog:
	// fp> TODO: fall back to ctrl=chapters when no perm for blog_properties
	$AdminUI->set_coll_list_params( 'blog_properties', 'edit',
												array( 'ctrl' => 'coll_settings', 'tab' => 'general' ),
												T_('List'), '?ctrl=collections&amp;blog=0' );
}

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();


switch($action)
{
	case 'new':
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'collections/views/_coll_sel_type.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-selskin':
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		$AdminUI->disp_view( 'skins/views/_coll_sel_skin.view.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'new-name':
	case 'create': // in case of validation error
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();

		// ---------- "New blog" form ----------
		echo '<h2>'.sprintf( T_('New %s'), get_collection_kinds($kind) ).':</h2>';

		$next_action = 'create';

		$AdminUI->disp_view( 'collections/views/_coll_general.form.php' );

		$AdminUI->disp_payload_end();
		break;


	case 'delete':
		// ----------  Delete a blog from DB ----------
		// Not confirmed
		?>
		<div class="panelinfo">
			<h3><?php printf( T_('Delete blog [%s]?'), $edited_Blog->dget( 'name' ) )?></h3>

			<p><?php echo T_('Deleting this blog will also delete all its categories, posts and comments!') ?></p>

			<p><?php echo T_('THIS CANNOT BE UNDONE!') ?></p>

			<p>

			<?php

				$Form = new Form( NULL, '', 'get', 'none' );

				$Form->begin_form( 'inline' );

				$Form->add_crumb( 'collection' );
				$Form->hidden_ctrl();
				$Form->hidden( 'action', 'delete' );
				$Form->hidden( 'blog', $edited_Blog->ID );
				$Form->hidden( 'confirm', 1 );

				if( is_file( $edited_Blog->get('staticfilepath') ) )
				{
					?>
					<input type="checkbox" id="delete_static_file" name="delete_static_file" value="1" />
					<label for="delete_static_file"><?php printf( T_('Also try to delete static file [<strong><a %s>%s</a></strong>]'), 'href="'.$edited_Blog->dget('staticurl').'"', $edited_Blog->dget('staticfilepath') ); ?></label><br />
					<br />
					<?php
				}

				$Form->submit( array( '', T_('I am sure!'), 'DeleteButton' ) );

				$Form->end_form();


				$Form->begin_form( 'inline' );
					$Form->hidden_ctrl();
					$Form->hidden( 'blog', 0 );
					$Form->submit( array( '', T_('CANCEL'), 'CancelButton' ) );
				$Form->end_form();
			?>

			</p>

			</div>
		<?php
		break;


	default:
		// List the blogs:
		$AdminUI->displayed_sub_begin = 1;	// DIRTY HACK :/ replacing an even worse hack...
		$AdminUI->disp_payload_begin();
		// Display VIEW:
		$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
		$AdminUI->disp_payload_end();

}


// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();


/*
 * $Log: collections.ctrl.php,v $
 */
?>