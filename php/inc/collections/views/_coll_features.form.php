<?php
/**
 * This file implements the UI view for the Collection features properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * }}
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 *
 * @package admin
 *
 * @version $Id: _coll_features.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

?>
<script type="text/javascript">
	<!--
	function show_hide_feedback_details(ob)
	{
		var fldset = jQuery( '.feedback_details_container' );
		if( ob.value == 'never' )
		{
			for( i = 0; i < fldset.length; i++ )
			{
				fldset[i].style.display = 'none';
			}
		}
		else
		{
			for( i = 0; i < fldset.length; i++ )
			{
				fldset[i].style.display = '';
			}
		}
	}
	//-->
</script>
<?php

$Form = new Form( NULL, 'coll_features_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'features' );
$Form->hidden( 'blog', $edited_Blog->ID );

$Form->begin_fieldset( T_('Post options').get_manual_link('blog_features_settings') );
	$Form->radio( 'require_title', $edited_Blog->get_setting('require_title'),
								array(  array( 'required', T_('Always'), T_('The blogger must provide a title') ),
												array( 'optional', T_('Optional'), T_('The blogger can leave the title field empty') ),
												array( 'none', T_('Never'), T_('No title field') ),
											), T_('Post titles'), true );
											
	$Form->checkbox( 'enable_goto_blog', $edited_Blog->get_setting( 'enable_goto_blog' ),
						T_( 'View blog after publishing' ), T_( 'Check this to automatically view the blog after publishing a post.' ) );

	// FP> TODO:
	// -post_url  always('required')|optional|never
	// -multilingual:  true|false   or better yet: provide a list to narrow down the active locales
	// -tags  always('required')|optional|never

	$Form->radio( 'post_categories', $edited_Blog->get_setting('post_categories'),
		array( array( 'one_cat_post', T_('Allow only one category per post') ),
			array( 'multiple_cat_post', T_('Allow multiple categories per post') ),
			array( 'main_extra_cat_post', T_('Allow one main + several extra categories') ),
			array( 'no_cat_post', T_('Don\'t allow category selections'), T_('(Main cat will be assigned automatically)') ) ),
			T_('Post category options'), true );

$Form->end_fieldset();

$Form->begin_fieldset( T_('Feedback options') );
	$Form->radio( 'allow_view_comments', $edited_Blog->get_setting( 'allow_view_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users') ),
								array( 'registered', T_('Registered users only') ),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ) ),
								array( 'moderator', T_('Moderators & Admins only') ),
					), T_('Comment viewing by'), true );

	$Form->radio( 'allow_comments', $edited_Blog->get_setting( 'allow_comments' ),
						array(  array( 'any', T_('Any user'), T_('Including anonymous users'),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'registered', T_('Registered users only'),  '',
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'member', T_('Members only'),  T_( 'Users have to be members of this blog' ),
										'', 'onclick="show_hide_feedback_details(this);"'),
								array( 'never', T_('Not allowed'), '',
										'', 'onclick="show_hide_feedback_details(this);"'),
					), T_('Comment posting by'), true );

	echo '<div class="feedback_details_container">';

	$Form->checkbox( 'disable_comments_bypost', $edited_Blog->get_setting( 'disable_comments_bypost' ), '', T_('Comments can be disabled on each post separately') );

	$Form->checkbox( 'allow_anon_url', $edited_Blog->get_setting( 'allow_anon_url' ), T_('Anonymous URLs'), T_('Allow anonymous commenters to submit an URL') );

	$any_option = array( 'any', T_('Any user'), T_('Including anonymous users'), '' );
	$registered_option = array( 'registered', T_('Registered users only'),  '', '' );
	$member_option = array( 'member', T_('Members only'), T_('Users have to be members of this blog'), '' );
	$never_option = array( 'never', T_('Not allowed'), '', '' );
	$Form->radio( 'allow_attachments', $edited_Blog->get_setting( 'allow_attachments' ),
						array(  $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow attachments from'), true );

	$Form->radio( 'allow_rating', $edited_Blog->get_setting( 'allow_rating' ),
						array( $any_option, $registered_option, $member_option, $never_option,
						), T_('Allow ratings from'), true );

	$Form->checkbox( 'blog_allowtrackbacks', $edited_Blog->get( 'allowtrackbacks' ), T_('Trackbacks'), T_("Allow other bloggers to send trackbacks to this blog, letting you know when they refer to it. This will also let you send trackbacks to other blogs.") );

	$status_options = array(
			'draft'      => T_('Draft'),
			'published'  => T_('Published'),
			'deprecated' => T_('Deprecated')
		);
	$Form->select_input_array( 'new_feedback_status', $edited_Blog->get_setting('new_feedback_status'), $status_options,
				T_('New feedback status'), T_('This status will be assigned to new comments/trackbacks from non moderators (unless overriden by plugins).') );

	$Form->radio( 'comments_orderdir', $edited_Blog->get_setting('comments_orderdir'),
						array(	array( 'ASC', T_('Chronologic') ),
								array ('DESC', T_('Reverse') ),
						), T_('Display order'), true );

	$Form->checkbox( 'paged_comments', $edited_Blog->get_setting( 'paged_comments' ), T_( 'Paged comments' ), T_( 'Check to enable paged comments on the public pages.' ) );

	$Form->text( 'comments_per_page', $edited_Blog->get_setting('comments_per_page'), 4, T_('Comments/Page'),  T_('How many comments do you want to display on one page?'), 4 );

	global $default_avatar;
	$Form->radio( 'default_gravatar', $edited_Blog->get_setting('default_gravatar'),
						array(	array( 'b2evo', T_('Default image'), $default_avatar ),
								array ('', 'Gravatar' ),
								array ('identicon', 'Identicon' ),
								array ('monsterid', 'Monsterid' ),
								array ('wavatar', 'Wavatar' ),
						), T_('Default gravatars'), true, T_('Gravatar users can choose to set up a unique icon for themselves, and if they don\'t, they will be assigned a default image.') );

	echo '</div>';

	if( $edited_Blog->get_setting( 'allow_comments' ) == 'never' )
	{ ?>
	<script type="text/javascript">
		<!--
		var fldset = jQuery( '.feedback_details_container' );
		for( i = 0; i < fldset.length; i++ )
		{
			fldset[i].style.display = 'none';
		}
		//-->
	</script>
	<?php
	}

$Form->end_fieldset();


$Form->begin_fieldset( T_('RSS/Atom feeds') );
	$Form->radio( 'feed_content', $edited_Blog->get_setting('feed_content'),
								array(  array( 'none', T_('No feeds') ),
												array( 'title', T_('Titles only') ),
												array( 'excerpt', T_('Post excerpts') ),
												array( 'normal', T_('Standard post contents (stopping at "&lt;!-- more -->")') ),
												array( 'full', T_('Full post contents (including after "&lt;!-- more -->")') ),
											), T_('Post feed contents'), true, T_('How much content do you want to make available in post feeds?') );

	$Form->radio( 'comment_feed_content', $edited_Blog->get_setting('comment_feed_content'),
								array(  array( 'none', T_('No feeds') ),
										array( 'excerpt', T_('Comment excerpts') ),
										array( 'normal', T_('Standard comment contents') ),
									), T_('Comment feed contents'), true, T_('How much content do you want to make available in comment feeds?') );

	$Form->text( 'posts_per_feed', $edited_Blog->get_setting('posts_per_feed'), 4, T_('Posts in feeds'),  T_('How many of the latest posts do you want to include in RSS & Atom feeds?'), 4 );

	if( isset($GLOBALS['files_Module']) )
	{
		load_funcs( 'files/model/_image.funcs.php' );
		$params['force_keys_as_values'] = true;
		$Form->select_input_array( 'image_size', $edited_Blog->get_setting('image_size') , get_available_thumb_sizes(), T_('Image size'), '', $params );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('Sitemaps') );
	$Form->checkbox( 'enable_sitemaps', $edited_Blog->get_setting( 'enable_sitemaps' ),
						T_( 'Enable sitemaps' ), T_( 'Check to allow usage of skins with the "sitemap" type.' ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Custom field names') );
	$notes = array(
			T_('Ex: Price'),
			T_('Ex: Weight'),
			T_('Ex: Latitude or Length'),
			T_('Ex: Longitude or Width'),
			T_('Ex: Altitude or Height'),
		);
	for( $i = 1 ; $i <= 5; $i++ )
	{
		$Form->text( 'custom_double'.$i, $edited_Blog->get_setting('custom_double'.$i), 20, T_('(numeric)').' double'.$i, $notes[$i-1], 40 );
	}

	$notes = array(
			T_('Ex: Color'),
			T_('Ex: Fabric'),
			T_('Leave empty if not needed'),
		);
	for( $i = 1 ; $i <= 3; $i++ )
	{
		$Form->text( 'custom_varchar'.$i, $edited_Blog->get_setting('custom_varchar'.$i), 30, T_('(text)').' varchar'.$i, $notes[$i-1], 60 );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('Subscriptions') );
	$Form->checkbox( 'allow_subscriptions', $edited_Blog->get_setting( 'allow_subscriptions' ), T_('Email subscriptions'), T_('Allow users to subscribe and receive email notifications for each new post and/or comment.') );
	$Form->checkbox( 'allow_item_subscriptions', $edited_Blog->get_setting( 'allow_item_subscriptions' ), '', T_( 'Allow users to subscribe and receive email notifications for comments on a specific post.' ) );
	// TODO: checkbox 'Enable RSS/Atom feeds'
	// TODO2: which feeds (skins)?
$Form->end_fieldset();

$Form->begin_fieldset( T_('List of public blogs') );
	$Form->checkbox( 'blog_in_bloglist', $edited_Blog->get( 'in_bloglist' ), T_('Include in public blog list'), T_('Check this if you want this blog to be advertised in the list of all public blogs on this system.') );
$Form->end_fieldset();

if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( T_('Skin and style').' ['.T_('Admin').']' );

		$SkinCache = & get_SkinCache();
		$SkinCache->load_all();
		$Form->select_input_object( 'blog_skin_ID', $edited_Blog->skin_ID, $SkinCache, T_('Skin') );
		$Form->checkbox( 'blog_allowblogcss', $edited_Blog->get( 'allowblogcss' ), T_('Allow customized blog CSS file'), T_('You will be able to customize the blog\'s skin stylesheet with a file named style.css in the blog\'s media file folder.') );
		$Form->checkbox( 'blog_allowusercss', $edited_Blog->get( 'allowusercss' ), T_('Allow user customized CSS file for this blog'), T_('Users will be able to customize the blog and skin stylesheets with a file named style.css in their personal file folder.') );
	$Form->end_fieldset();

}


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>
<script type="text/javascript">
	jQuery( '#paged_comments' ).click( function()
	{
		if ( $('#paged_comments').is(':checked') )
		{
			$('#comments_per_page').val('20');
		}
		else
		{
			$('#comments_per_page').val('1000');
		}
	} );
</script>
<?php


/*
 * $Log: _coll_features.form.php,v $
 */
?>