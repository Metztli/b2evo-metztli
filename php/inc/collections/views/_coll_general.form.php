<?php
/**
 * This file implements the UI view for the General blog properties.
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author blueyed: Daniel HAHLER
 * @author fplanque: Francois PLANQUE.
 *
 * @version $Id: _coll_general.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;


global $action, $next_action, $blogtemplate, $blog, $tab;

$Form = new Form();

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', $next_action );
$Form->hidden( 'tab', $tab );
if( $next_action == 'create' )
{
	$Form->hidden( 'kind', get_param('kind') );
	$Form->hidden( 'skin_ID', get_param('skin_ID') );
}
else
{
	$Form->hidden( 'blog', $blog );
}

$Form->begin_fieldset( T_('General parameters').get_manual_link('blogs_general_parameters'), array( 'class'=>'fieldset clear' ) );

	$Form->text( 'blog_name', $edited_Blog->get( 'name' ), 50, T_('Title'), T_('Will be displayed on top of the blog.'), 255 );

	$Form->text( 'blog_shortname', $edited_Blog->get( 'shortname', 'formvalue' ), 15, T_('Short name'), T_('Will be used in selection menus and throughout the admin interface.'), 255 );

	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings
	}

	$owner_User = & $edited_Blog->get_owner_User();
	if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
	{	// Permission to edit advanced admin settings

		$Form->text( 'blog_urlname', $edited_Blog->get( 'urlname' ), 20, T_('URL "filename"'),
				sprintf( T_('"slug" used to uniquely identify this blog in URLs. Also used as <a %s>default media folder</a>.'),
					'href="?ctrl=coll_settings&tab=advanced&blog='.$blog.'"'), 255 );

		// fp> Note: There are 2 reasons why we don't provide a select here:
		// 1. If there are 1000 users, it's a pain.
		// 2. A single blog owner is not necessarily allowed to see all other users.
		$Form->username( 'owner_login', $owner_User, T_('Owner'), T_('Login of this blog\'s owner.') );
	}
	else
	{
		$Form->info( T_('URL Name'), $edited_Blog->get( 'urlname' ), T_('Used to uniquely identify this blog in URLs.') /* Note: message voluntarily shorter than admin message */ );

		$Form->info( T_('Owner'), $owner_User->login, $owner_User->dget('fullname') );
	}

	$Form->select( 'blog_locale', $edited_Blog->get( 'locale' ), 'locale_options_return', T_('Main Locale'), T_('Determines the language of the navigation links on the blog.') );

	$Form->end_fieldset();


$Form->begin_fieldset( T_('Content / Posts') );
	$Form->select_input_array( 'orderby', $edited_Blog->get_setting('orderby'), get_available_sort_options(), T_('Order by'), T_('Default ordering of posts.') );
	$Form->select_input_array( 'orderdir', $edited_Blog->get_setting('orderdir'), array(
												'ASC'  => T_('Ascending'),
												'DESC' => T_('Descending'), ), T_('Direction') );
	$Form->radio( 'what_to_show', $edited_Blog->get_setting('what_to_show'),
								array(  array( 'days', T_('days') ),
												array( 'posts', T_('posts') ),
											), T_('Display unit'), false,  T_('Do you want to restrict on the number of days or the number of posts?') );
	$Form->text( 'posts_per_page', $edited_Blog->get_setting('posts_per_page'), 4, T_('Posts/Days per page'), T_('How many days or posts do you want to display on the home page?'), 4 );
	$Form->radio( 'archive_mode',  $edited_Blog->get_setting('archive_mode'),
							array(  array( 'monthly', T_('monthly') ),
											array( 'weekly', T_('weekly') ),
											array( 'daily', T_('daily') ),
											array( 'postbypost', T_('post by post') )
										), T_('Archive grouping'), false,  T_('How do you want to browse the post archives? May also apply to permalinks.') );

	// TODO: Hide if archive_mode != 'postbypost' (JS)
	// fp> there should probably be no post by post mode since we do have other ways to list posts now
	// fp> TODO: this is display param and should go to plugin/widget
	$Form->radio( 'archives_sort_order',  $edited_Blog->get_setting('archives_sort_order'),
							array(  array( 'date', T_('date') ),
											array( 'title', T_('title') ),
										), T_('Archive sorting'), false,  T_('How to sort your archives? (only in post by post mode)') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Description') );
	$Form->text( 'blog_tagline', $edited_Blog->get( 'tagline' ), 50, T_('Tagline'), T_('This is displayed under the blog name on the blog template.'), 250 );
	$Form->textarea( 'blog_longdesc', $edited_Blog->get( 'longdesc' ), 5, T_('Long Description'), T_('This is displayed on the blog template.'), 50, 'large' );
$Form->end_fieldset();


$Form->buttons( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

$Form->end_form();

/*
 * $Log: _coll_general.form.php,v $
 */
?>