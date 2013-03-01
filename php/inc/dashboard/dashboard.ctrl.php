<?php
/**
 * This file implements the UI controller for the dashboard.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * @copyright (c)2003-2007 by Francois PLANQUE - {@link http://fplanque.net/}
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 *
 * @todo add 5 plugin hooks. Will be widgetized later (same as SkinTag became Widgets)
 *
 * @version $Id: dashboard.ctrl.php 272 2011-11-11 09:58:01Z attila $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;

global $dispatcher, $allow_evo_stats;

if( $blog )
{
	if( ! $current_User->check_perm( 'blog_ismember', 'view', false, $blog ) )
	{	// We don't have permission for the requested blog (may happen if we come to admin from a link on a different blog)
		set_working_blog( 0 );
		unset( $Blog );
	}
}

$AdminUI->set_coll_list_params( 'blog_ismember', 'view', array(), T_('Global'), '?blog=0' );

$AdminUI->set_path( 'dashboard' );

require_js( 'communication.js' ); // auto requires jQuery

$AdminUI->breadcrumbpath_init();

// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

if( $blog )
{	// We want to look at a specific blog:
	// Begin payload block:

	// This div is to know where to display the message after overlay close:
	echo '<div class="first_payload_block">'."\n";

	$AdminUI->disp_payload_begin();

	echo '<h2>'.$Blog->dget( 'name' ).'</h2>';

	echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr><td>';

	load_class( 'items/model/_itemlist.class.php', 'ItemList' );

	$block_item_Widget = new Widget( 'dash_item' );

	$nb_blocks_displayed = 0;

	$user_draftc_perm = $current_User->check_perm( 'blog_draft_comments', 'edit', false, $blog );

	if( $user_draftc_perm )
	{
		/*
		 * COMMENTS:
		 */
		$CommentList = new CommentList2( $Blog );

		// Filter list:
		$CommentList->set_filters( array(
				'types' => array( 'comment','trackback','pingback' ),
				'statuses' => array ( 'draft' ),
				'order' => 'DESC',
				'comments' => 5,
			) );

		// Get ready for display (runs the query):
		$CommentList->display_init();
	}

	if( $user_draftc_perm && $CommentList->result_num_rows )
	{	// We have drafts

		global $htsrv_url;

		?>

		<script type="text/javascript">
			<!--
			// currently midified comments id and status. After update is done, the appropiate item will be removed.
			var modifieds = new Array();

			// Process result after publish/deprecate/delete action has been completed
			function processResult(result, modifiedlist)
			{
				$('#comments_container').html(result);
				for(var id in modifiedlist)
				{
					switch(modifiedlist[id])
					{
						case 'published':
							fadeIn(id, '#339900');
							break;
						case 'deprecated':
							fadeIn(id, '#656565');
							break;
						case 'deleted':
							fadeIn(id, '#fcc');
							break;
					};
				}

				var comments_number = $('#new_badge').val();
				if(comments_number == '0')
				{
					var options = {};
					$('#comments_block').effect('blind', options, 200);
					$('#comments_block').remove();
				}
				else
				{
					$('#badge').text(comments_number);
				}
			}

			// Set comments status
			function setCommentStatus(id, status)
			{
				var divid = 'comment_' + id;
				switch(status)
				{
					case 'published':
						fadeIn(divid, '#339900');
						break;
					case 'deprecated':
						fadeIn(divid, '#656565');
						break;
				};

				modifieds[divid] = status;

				$.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&status=' + status + '&action=set_comment_status&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result)
					{
						// var divid = 'comment_' + id;
						delete modifieds[divid];
						processResult(result, modifieds);
					}
				});
			}

			// Delete comment
			function deleteComment(id)
			{
				var divid = 'comment_' + id;
				fadeIn(divid, '#fcc');

				modifieds[divid] = 'deleted';

				$.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'action=get_opentrash_link&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result)
					{
						var recycle_bin = jQuery('#recycle_bin');
						if( recycle_bin.length )
						{
							recycle_bin.replaceWith( result );
						}
					}
				});

				$.ajax({
				type: 'POST',
				url: '<?php echo $htsrv_url; ?>async.php',
				data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&action=delete_comment&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
				success: function(result)
					{
						jQuery('#' + divid).effect('transfer', { to: $('#recycle_bin') }, 700, function() {
							delete modifieds[divid];
							processResult(result, modifieds);
						});
					}
				});
			}

			// Fade in background color
			function fadeIn(id, color)
			{
				jQuery('#' + id).animate({ backgroundColor: color }, 200);
			}

			// Delete comment author_url
			function delete_comment_url(id)
			{
				var divid = 'commenturl_' + id;
				fadeIn(divid, '#fcc');

				$.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&commentid=' + id + '&action=delete_comment_url' + '&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result) { $('#' + divid).remove(); }
				});
			}

			// This is called when we get the response from the server:
			function antispamSettings( the_html )
			{
				// add placeholder for antispam settings form:
				jQuery( 'body' ).append( '<div id="screen_mask" onclick="closeAntispamSettings()"></div><div id="overlay_page"></div>' );
				var evobar_height = jQuery( '#evo_toolbar' ).height();
				jQuery( '#screen_mask' ).css({ top: evobar_height });
				jQuery( '#screen_mask' ).fadeTo(1,0.5).fadeIn(200);
				jQuery( '#overlay_page' ).html( the_html ).addClass( 'overlay_page_active' );
				AttachServerRequest( 'antispam_ban' ); // send form via hidden iframe
				jQuery( '#close_button' ).bind( 'click', closeAntispamSettings );
				jQuery( '.SaveButton' ).bind( 'click', refresh_overlay );

				// Close antispam popup if Escape key is pressed:
				var keycode_esc = 27;
				jQuery(document).keyup(function(e)
				{
					if( e.keyCode == keycode_esc )
					{
						closeAntispamSettings();
					}
				});
			}

			// This is called to close the antispam ban overlay page
			function closeAntispamSettings()
			{
				jQuery( '#overlay_page' ).hide();
				jQuery( '.action_messages').remove();
				jQuery( '#server_messages' ).insertBefore( '.first_payload_block' );
				jQuery( '#overlay_page' ).remove();
				jQuery( '#screen_mask' ).remove();
				return false;
			}

			// Ban comment url
			function ban_url(authorurl)
			{
				$.ajax({
					type: 'POST',
					url: '<?php echo $admin_url; ?>',
					data: 'ctrl=antispam&action=ban&display_mode=js&mode=iframe&request=checkban&keyword=' + authorurl +
						  '&' + <?php echo '\''.url_crumb('antispam').'\''; ?>,
					success: function(result)
					{
						antispamSettings( result );
					}
				});
			}

			// Refresh overlay page after Check&ban button click
			function refresh_overlay()
			{
				var parameters = jQuery( '#antispam_add' ).serialize();

				$.ajax({
					type: 'POST',
					url: '<?php echo $admin_url; ?>',
					data: 'action=ban&display_mode=js&mode=iframe&request=checkban&' + parameters,
					success: function(result)
					{
						antispamSettings( result );
					}
				});
				return false;
			}

			// Refresh comments on dashboard after ban url -> delete comment
			function refreshAfterBan(deleted_ids)
			{
				var comment_ids = String(deleted_ids).split(',');
				for( var i=0;i<comment_ids.length; ++i )
				{
					var divid = 'comment_' + comment_ids[i];
					fadeIn(divid, '#fcc');
				}

				$.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_comments&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result)
					{
						processResult(result, modifieds);
					}
				});
			}

			function startRefreshComments()
			{
				$('#comments_container').slideUp('fast', refreshComments());
			}

			// Absolute refresh comment list
			function refreshComments()
			{
				$.ajax({
					type: 'POST',
					url: '<?php echo $htsrv_url; ?>async.php',
					data: 'blogid=' + <?php echo $Blog->ID; ?> + '&action=refresh_comments&' + <?php echo '\''.url_crumb('comment').'\''; ?>,
					success: function(result)
					{
						processResult(result, modifieds);
						$('#comments_container').slideDown('fast');
					}
				});
			}

			-->
		</script>
		<?php

		$nb_blocks_displayed++;

		$opentrash_link = get_opentrash_link();
		$refresh_link = '<span class="floatright">'.action_icon( T_('Refresh comment list'), 'refresh', 'javascript:startRefreshComments()' ).'</span> ';

		$block_item_Widget->title = $refresh_link.$opentrash_link.T_('Comments awaiting moderation').
			' <a href="'.$admin_url.'?ctrl=comments&amp;show_statuses[]=draft'.'">'.
			'<span id="badge" class="badge">'.get_comments_awaiting_moderation_number( $Blog->ID ).'</span></a>';

		echo '<div id="comments_block">';

		$block_item_Widget->disp_template_replaced( 'block_start' );

		echo '<div id="comments_container">';

		load_funcs( 'dashboard/model/_dashboard.funcs.php' );
		// GET COMMENTS AWAITING MODERATION (the code generation is shared with the AJAX callback):
		show_comments_awaiting_moderation( $Blog->ID );

		echo '</div>';

		$block_item_Widget->disp_template_raw( 'block_end' );

		echo '</div>';
	}

	/*
	 * RECENT DRAFTS
	 */
	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => array( 'draft' ),
			'orderby' => 'datemodified',
			'order' => 'DESC',
			'posts' => 5,
		) );

	// Get ready for display (runs the query):
	$ItemList->display_init();

	if( $ItemList->result_num_rows )
	{	// We have drafts

		$nb_blocks_displayed++;

		$block_item_Widget->title = T_('Recent drafts');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		while( $Item = & $ItemList->get_item() )
		{
			echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
			// We don't switch locales in the backoffice, since we use the user pref anyway
			// Load item's creator user:
			$Item->get_creator_User();

			echo '<div class="dashboard_float_actions">';
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' ',
					'after'     => ' ',
					'class'     => 'ActionButton'
				) );
			$Item->publish_link( '', '', '#', '#', 'PublishButton' );
			echo '<img src="'.$rsc_url.'/img/blank.gif" alt="" />';
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			$item_title = $Item->dget('title');
			if( ! strlen($item_title) )
			{
				$item_title = '['.format_to_output(T_('No title')).']';
			}
			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
			echo ' <span class="dashboard_post_details">';
			$Item->status( array(
					'before' => '<div class="floatright"><span class="status_'.$Item->status.'">',
					'after'  => '</span></div>',
				) );
			echo '</span>';
			echo '</h3>';

			echo '</div>';

		}

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	/*
	 * RECENTLY EDITED
	 */
	// Create empty List:
	$ItemList = new ItemList2( $Blog, NULL, NULL );

	// Filter list:
	$ItemList->set_filters( array(
			'visibility_array' => array( 'published', 'protected', 'private', 'deprecated', 'redirected' ),
			'orderby' => 'datemodified',
			'order' => 'DESC',
			'posts' => 5,
		) );

	// Get ready for display (runs the query):
	$ItemList->display_init();

	if( $ItemList->result_num_rows )
	{	// We have recent edits

		$nb_blocks_displayed++;

		if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
		{	// We have permission to add a post with at least one status:
			$block_item_Widget->global_icon( T_('Write a new post...'), 'new', '?ctrl=items&amp;action=new&amp;blog='.$Blog->ID, T_('New post').' &raquo;', 3, 4 );
		}

		$block_item_Widget->title = T_('Recently edited');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		while( $Item = & $ItemList->get_item() )
		{
			echo '<div class="dashboard_post dashboard_post_'.($ItemList->current_idx % 2 ? 'even' : 'odd' ).'" lang="'.$Item->get('locale').'">';
			// We don't switch locales in the backoffice, since we use the user pref anyway
			// Load item's creator user:
			$Item->get_creator_User();

			echo '<div class="dashboard_float_actions">';
			$Item->edit_link( array( // Link to backoffice for editing
					'before'    => ' ',
					'after'     => ' ',
					'class'     => 'ActionButton'
				) );
			echo '</div>';

			echo '<h3 class="dashboard_post_title">';
			$item_title = $Item->dget('title');
			if( ! strlen($item_title) )
			{
				$item_title = '['.format_to_output(T_('No title')).']';
			}
			echo '<a href="?ctrl=items&amp;blog='.$Blog->ID.'&amp;p='.$Item->ID.'">'.$item_title.'</a>';
			echo ' <span class="dashboard_post_details">';
			$Item->status( array(
					'before' => '<div class="floatright"><span class="status_'.$Item->status.'">',
					'after'  => '</span></div>',
				) );
			$Item->views();
			echo '</span>';
			echo '</h3>';

			// Display images that are linked to this post:
			$Item->images( array(
					'before' =>              '<div class="dashboard_thumbnails">',
					'before_image' =>        '',
					'before_image_legend' => NULL,	// No legend
					'after_image_legend' =>  NULL,
					'after_image' =>         '',
					'after' =>               '</div>',
					'image_size' =>          'fit-80x80',
					'restrict_to_image_position' => 'teaser',	// Optionally restrict to files/images linked to specific position: 'teaser'|'aftermore'
				) );

			echo '<div class="small">'.$Item->get_content_excerpt( 150 ).'</div>';

			echo '<div style="clear:left;">'.get_icon('pixel').'</div>'; // IE crap
			echo '</div>';
		}

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	if( $nb_blocks_displayed == 0 )
	{	// We haven't displayed anything yet!

		$nb_blocks_displayed++;

		$block_item_Widget = new Widget( 'block_item' );
		$block_item_Widget->title = T_('Getting started');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		echo '<p><strong>'.T_('Welcome to your new blog\'s dashboard!').'</strong></p>';

		echo '<p>'.T_('Use the links on the right to write a first post or to customize your blog.').'</p>';

		echo '<p>'.T_('You can see your blog page at any time by clicking "See" in the b2evolution toolbar at the top of this page.').'</p>';

 		echo '<p>'.T_('You can come back here at any time by clicking "Manage" in that same evobar.').'</p>';

		$block_item_Widget->disp_template_raw( 'block_end' );
	}


	/*
	 * DashboardBlogMain to be added here (anyone?)
	 */


	echo '</td><td>';

	/*
	 * RIGHT COL
	 */

	$side_item_Widget = new Widget( 'side_item' );

	$side_item_Widget->title = T_('Manage your blog');
	$side_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="dashboard_sidebar">';
	echo '<ul>';
		if( $current_User->check_perm( 'blog_post_statuses', 'edit', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=items&amp;action=new&amp;blog='.$Blog->ID.'">'.T_('Write a new post').' &raquo;</a></li>';
		}

 		echo '<li>'.T_('Browse').':<ul>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&tab=full&filter=restore&blog='.$Blog->ID.'">'.T_('Posts (full)').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=items&tab=list&filter=restore&blog='.$Blog->ID.'">'.T_('Posts (list)').' &raquo;</a></li>';
		if( $current_User->check_perm( 'blog_comments', 'edit', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=comments&amp;filter=restore&amp;blog='.$Blog->ID.'">'.T_('Comments').' &raquo;</a></li>';
		}
		echo '</ul></li>';

		if( $current_User->check_perm( 'blog_cats', '', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=chapters&blog='.$Blog->ID.'">'.T_('Edit categories').' &raquo;</a></li>';
		}

		if( $current_User->check_perm( 'blog_genstatic', 'any', false, $Blog->ID ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=collections&amp;action=GenStatic&amp;blog='.$Blog->ID.'&amp;redir_after_genstatic='.rawurlencode(regenerate_url( '', '', '', '&' )).'">'.T_('Generate static page!').'</a></li>';
		}

 		echo '<li><a href="'.$Blog->get('url').'">'.T_('View this blog').'</a></li>';
	echo '</ul>';
	echo '</div>';

	$side_item_Widget->disp_template_raw( 'block_end' );

	if( $current_User->check_perm( 'blog_properties', 'edit', false, $Blog->ID ) )
	{
		$side_item_Widget->title = T_('Customize your blog');
		$side_item_Widget->disp_template_replaced( 'block_start' );

		echo '<div class="dashboard_sidebar">';
		echo '<ul>';

		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=general&amp;blog='.$Blog->ID.'">'.T_('Blog properties').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=features&amp;blog='.$Blog->ID.'">'.T_('Blog features').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=skin&amp;blog='.$Blog->ID.'">'.T_('Blog skin').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=widgets&amp;blog='.$Blog->ID.'">'.T_('Blog widgets').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=coll_settings&amp;tab=urls&amp;blog='.$Blog->ID.'">'.T_('Blog URLs').' &raquo;</a></li>';

		echo '</ul>';
		echo '</div>';

		$side_item_Widget->disp_template_raw( 'block_end' );
	}


	/*
	 * DashboardBlogSide to be added here (anyone?)
	 */


 	echo '</td></tr></table>';


	// End payload block:
	$AdminUI->disp_payload_end();

	echo '</div>'."\n";
}
else
{	// We're on the GLOBAL tab...

	$AdminUI->disp_payload_begin();
	echo '<h2>'.T_('Select a blog').'</h2>';
	// Display blog list VIEW:
	$AdminUI->disp_view( 'collections/views/_coll_list.view.php' );
	$AdminUI->disp_payload_end();


	/*
	 * DashboardGlobalMain to be added here (anyone?)
	 */
}


/*
 * Administrative tasks
 */

if( $current_User->check_perm( 'options', 'edit' ) )
{	// We have some serious admin privilege:
	// Begin payload block:
	$AdminUI->disp_payload_begin();

	echo '<table class="browse" cellspacing="0" cellpadding="0" border="0"><tr><td>';

	$block_item_Widget = new Widget( 'block_item' );

	$block_item_Widget->title = T_('Updates from b2evolution.net');
	$block_item_Widget->disp_template_replaced( 'block_start' );


	// Note: hopefully, the update swill have been downloaded in the shutdown function of a previous page (including the login screen)
	// However if we have outdated info, we will load updates here.
	load_funcs( 'dashboard/model/_dashboard.funcs.php' );
	// Let's clear any remaining messages that should already have been displayed before...
	$Messages->clear();

	if( b2evonet_get_updates() !== NULL )
	{	// Updates are allowed, display them:

		// Display info & error messages
		echo $Messages->display( NULL, NULL, false, 'action_messages' );

		/**
		 * @var AbstractSettings
		 */
		global $global_Cache;
		$version_status_msg = $global_Cache->get( 'version_status_msg' );
		if( !empty($version_status_msg) )
		{	// We have managed to get updates (right now or in the past):
			echo '<p>'.$version_status_msg.'</p>';
			$extra_msg = $global_Cache->get( 'extra_msg' );
			if( !empty($extra_msg) )
			{
				echo '<p>'.$extra_msg.'</p>';
			}
		}


		$block_item_Widget->disp_template_replaced( 'block_end' );

		/*
		 * DashboardAdminMain to be added here (anyone?)
		 */

	}
	else
	{
		echo '<p>Updates from b2evolution.net are disabled!</p>';
		echo '<p>You will <b>NOT</b> be alerted if you are running an insecure configuration.</p>';
	}

	echo '</td><td>';

	/*
	 * RIGHT COL
	 */
	$side_item_Widget = new Widget( 'side_item' );

	$side_item_Widget->title = T_('Administrative tasks');
	$side_item_Widget->disp_template_replaced( 'block_start' );

	echo '<div class="dashboard_sidebar">';
	echo '<ul>';
		if( $current_User->check_perm( 'users', 'edit' ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=user&amp;user_tab=profile&amp;action=new">'.T_('Create new user').' &raquo;</a></li>';
		}
		if( $current_User->check_perm( 'blogs', 'create' ) )
		{
			echo '<li><a href="'.$dispatcher.'?ctrl=collections&amp;action=new">'.T_('Create new blog').' &raquo;</a></li>';
		}
		echo '<li><a href="'.$dispatcher.'?ctrl=skins">'.T_('Install a skin').' &raquo;</a></li>';
		echo '<li><a href="'.$dispatcher.'?ctrl=plugins">'.T_('Install a plugin').' &raquo;</a></li>';
		// TODO: remember system date check and only remind every 3 months
		echo '<li><a href="'.$dispatcher.'?ctrl=system">'.T_('Check system &amp; security').' &raquo;</a></li>';
		echo '<li><a href="'.$baseurl.'default.php">'.T_('View default page').' &raquo;</a></li>';
	echo '</ul>';
	echo '</div>';

	$side_item_Widget->disp_template_raw( 'block_end' );

	/*
	 * DashboardAdminSide to be added here (anyone?)
	 */

 	echo '</td></tr></table>';

 	// End payload block:
	$AdminUI->disp_payload_end();
}

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: dashboard.ctrl.php,v $
 */
?>