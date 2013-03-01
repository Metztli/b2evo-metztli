<?php
/**
 * This file implements the UI view for the Advanced blog properties.
 *
 * This file is part of the b2evolution/evocms project - {@link http://b2evolution.net/}.
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}.
 * Parts of this file are copyright (c)2004-2005 by Daniel HAHLER - {@link http://thequod.de/contact}.
 *
 * @license http://b2evolution.net/about/license.html GNU General Public License (GPL)
 *
 * {@internal Open Source relicensing agreement:
 * Daniel HAHLER grants Francois PLANQUE the right to license
 * Daniel HAHLER's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author gorgeb: Bertrand GORGE / EPISTEMA
 * @author blueyed: Daniel HAHLER
 *
 * @package admin
 *
 *
 * @version $Id: _coll_advanced.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var Blog
 */
global $edited_Blog;

global $Plugins;

global $basepath, $rsc_url, $dispatcher;

$Form = new Form( NULL, 'blogadvanced_checkchanges' );

$Form->begin_form( 'fform' );

$Form->add_crumb( 'collection' );
$Form->hidden_ctrl();
$Form->hidden( 'action', 'update' );
$Form->hidden( 'tab', 'advanced' );
$Form->hidden( 'blog', $edited_Blog->ID );


$Form->begin_fieldset( T_('Multiple authors').get_manual_link('multiple_author_settings') );
	$Form->checkbox( 'advanced_perms', $edited_Blog->get( 'advanced_perms' ), T_('Use advanced perms'), T_('This will turn on the advanced User and Group permissions tabs for this blog.') );
	$Form->checkbox( 'blog_use_workflow', $edited_Blog->get_setting( 'use_workflow' ), T_('Use workflow'), T_('This will notably turn on the Tracker tab in the Posts view.') );
$Form->end_fieldset();


$Form->begin_fieldset( T_('After each new post...').get_manual_link('after_each_new_post') );
	$ping_plugins = preg_split( '~\s*,\s*~', $edited_Blog->get_setting('ping_plugins'), -1, PREG_SPLIT_NO_EMPTY);

	$available_ping_plugins = $Plugins->get_list_by_event('ItemSendPing');
	$displayed_ping_plugin = false;
	if( $available_ping_plugins )
	{
		foreach( $available_ping_plugins as $loop_Plugin )
		{
			if( empty($loop_Plugin->code) )
			{ // Ping plugin needs a code
				continue;
			}
			$displayed_ping_plugin = true;

			$checked = in_array( $loop_Plugin->code, $ping_plugins );
			$Form->checkbox_input( 'blog_ping_plugins[]', $checked, /* TRANS: %s is a ping service name */ sprintf( T_('Ping %s'), $loop_Plugin->ping_service_name ), array('value'=>$loop_Plugin->code, 'note'=>$loop_Plugin->ping_service_note) );

			while( ($key = array_search($loop_Plugin->code, $ping_plugins)) !== false )
			{
				unset($ping_plugins[$key]);
			}
		}
	}
	if( ! $displayed_ping_plugin )
	{
		echo '<p>'.T_('There are no ping plugins activated.').'</p>';
	}

	// Provide previous ping services as hidden fields, in case the plugin is temporarily disabled:
	foreach( $ping_plugins as $ping_plugin_code )
	{
		$Form->hidden( 'blog_ping_plugins[]', $ping_plugin_code );
	}
$Form->end_fieldset();


$Form->begin_fieldset( T_('External Feeds').get_manual_link('external_feeds') );

	$Form->text_input( 'atom_redirect', $edited_Blog->get_setting( 'atom_redirect' ), 50, T_('Atom Feed URL'),
	'<br />'.T_('Example: Your Feedburner Atom URL which should replace the original feed URL.').'<br />'
			.sprintf( T_( 'Note: the original URL was: %s' ), url_add_param( $edited_Blog->get_item_feed_url( '_atom' ), 'redir=no' ) ),
	array('maxlength'=>255, 'class'=>'large') );

	$Form->text_input( 'rss2_redirect', $edited_Blog->get_setting( 'rss2_redirect' ), 50, T_('RSS2 Feed URL'),
	'<br />'.T_('Example: Your Feedburner RSS2 URL which should replace the original feed URL.').'<br />'
			.sprintf( T_( 'Note: the original URL was: %s' ), url_add_param( $edited_Blog->get_item_feed_url( '_rss2' ), 'redir=no' ) ),
	array('maxlength'=>255, 'class'=>'large') );

$Form->end_fieldset();


if( $current_User->check_perm( 'blog_admin', 'edit', false, $edited_Blog->ID ) )
{	// Permission to edit advanced admin settings

	$Form->begin_fieldset( T_('Aggregation').' ['.T_('Admin').']'.get_manual_link('collection_aggregation_settings') );
		$Form->text( 'aggregate_coll_IDs', $edited_Blog->get_setting( 'aggregate_coll_IDs' ), 30, T_('Blogs to aggregate'), T_('List blog IDs separated by , or use * for all blogs'), 255 );
	$Form->end_fieldset();


	$Form->begin_fieldset( T_('Caching').' ['.T_('Admin').']'.get_manual_link('collection_cache_settings') );
		$Form->checkbox_input( 'ajax_form_enabled', $edited_Blog->get_setting('ajax_form_enabled'), T_('Enable AJAX forms'), array( 'note'=>T_('Comment and contacts forms will be fetched by javascript') ) );
		$Form->checkbox_input( 'cache_enabled', $edited_Blog->get_setting('cache_enabled'), T_('Enable page cache'), array( 'note'=>T_('Cache rendered blog pages') ) );
		$Form->checkbox_input( 'cache_enabled_widgets', $edited_Blog->get_setting('cache_enabled_widgets'), T_('Enable widget cache'), array( 'note'=>T_('Cache rendered widgets') ) );
	$Form->end_fieldset();

	$Form->begin_fieldset( T_('Login').' ['.T_('Admin').']'.get_manual_link('collection_login_settings') );
		$Form->checkbox_input( 'in_skin_login', $edited_Blog->get_setting( 'in_skin_login' ), T_( 'In-skin login' ), array( 'note' => T_( 'Use in-skin login form every time it\'s possible' ) ) );
	$Form->end_fieldset();

	$Form->begin_fieldset( '['. T_('Deprecated'). '] '.T_('Static file generation').' ['.T_('Admin').']'.get_manual_link('static_file_generation') );
		$Form->text_input( 'source_file', $edited_Blog->get_setting( 'source_file' ), 25, T_('Source file'),
												T_('.php (stub) file used to generate the static homepage.'),
												array( 'input_prefix' => "<code>$basepath</code>", 'maxlength' => 255 ) );
		$Form->text_input( 'static_file', $edited_Blog->get_setting( 'static_file' ), 25, T_('Destination file'),
												T_('.html file that will be created.'),
												array( 'input_prefix' => "<code>$basepath</code>", 'maxlength' => 255 ) );
		if( $current_User->check_perm( 'blog_genstatic', 'any', false, $edited_Blog->ID ) )
		{
			$Form->info( T_('Static page'), '<a href="'.$dispatcher.'?ctrl=collections&amp;action=GenStatic&amp;blog='.$edited_Blog->ID.'&amp;redir_after_genstatic='.rawurlencode(regenerate_url( '', '', '', '&' )).'">'.T_('Generate now!').'</a>' );
		}
	$Form->end_fieldset();


	$Form->begin_fieldset( T_('Media directory location').' ['.T_('Admin').']'.get_manual_link('media_directory_location') );
	global $media_path;
	$Form->radio( 'blog_media_location', $edited_Blog->get( 'media_location' ),
			array(
				array( 'none', T_('None') ),
				array( 'default', T_('Default'), $media_path.$edited_Blog->urlname.'/' ),
				array( 'subdir', T_('Subdirectory of media folder').':',
					'',
					' <span class="nobr"><code>'.$media_path.'</code><input
						type="text" name="blog_media_subdir" class="form_text_input" size="20" maxlength="255"
						class="'.( param_has_error('blog_media_subdir') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_subdir', 'formvalue' ).'" /></span>', '' ),
				array( 'custom',
					T_('Custom location').':',
					'',
					'<fieldset>'
					.'<div class="label">'.T_('directory').':</div><div class="input"><input
						type="text" class="form_text_input" name="blog_media_fullpath" size="50" maxlength="255"
						class="'.( param_has_error('blog_media_fullpath') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_fullpath', 'formvalue' ).'" /></div>'
					.'<div class="label">'.T_('URL').':</div><div class="input"><input
						type="text" class="form_text_input" name="blog_media_url" size="50" maxlength="255"
						class="'.( param_has_error('blog_media_url') ? 'field_error' : '' ).'"
						value="'.$edited_Blog->dget( 'media_url', 'formvalue' ).'" /></div></fieldset>' )
			), T_('Media directory'), true
		);
	$Form->end_fieldset();

}

$Form->begin_fieldset( T_('Meta data').get_manual_link('blog_meta_data') );
	// TODO: move stuff to coll_settings
	$Form->text( 'blog_description', $edited_Blog->get( 'description' ), 60, T_('Short Description'), T_('This is is used in meta tag description and RSS feeds. NO HTML!'), 250, 'large' );
	$Form->text( 'blog_keywords', $edited_Blog->get( 'keywords' ), 60, T_('Keywords'), T_('This is is used in meta tag keywords. NO HTML!'), 250, 'large' );
	$Form->text( 'blog_footer_text', $edited_Blog->get_setting( 'blog_footer_text' ), 60, T_('Blog footer'), sprintf(
		T_('Use &lt;br /&gt; to insert a line break. You might want to put your copyright or <a href="%s" target="_blank">creative commons</a> notice here.'),
		'http://creativecommons.org/license/' ), 1000, 'large' );
	$Form->textarea( 'single_item_footer_text', $edited_Blog->get_setting( 'single_item_footer_text' ), 2, T_('Single post footer'),
		T_('This will be displayed after each post in single post view.').' '.sprintf( T_('Replacement tags: %s.'), '<b>$perm_url$</b>, <b>$title$</b>, <b>$excerpt$</b>, <b>$views$</b>, <b>$author$</b>, <b>$author_login$</b>' ), 50, 'large' );
	$Form->textarea( 'xml_item_footer_text', $edited_Blog->get_setting( 'xml_item_footer_text' ), 2, T_('Post footer in RSS/Atom'),
		T_('This will be appended to each post in your RSS/Atom feeds.').' '.sprintf( T_('Replacement tags: %s.'), T_('same as above') ), 50, 'large' );
	$Form->textarea( 'blog_notes', $edited_Blog->get( 'notes' ), 5, T_('Notes'),
		T_('Additional info. Appears in the backoffice.'), 50, 'large' );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Software credits').get_manual_link('software_credits') );
	$max_credits = $edited_Blog->get_setting( 'max_footer_credits' );
	$note = T_('You get the b2evolution software for <strong>free</strong>. We do appreciate you giving us credit. <strong>Thank you for your support!</strong>');
	if( $max_credits < 1 )
	{
		$note = '<img src="'.$rsc_url.'smilies/icon_sad.gif" alt="" class="bottom"> '.$note;
	}
	$Form->text( 'max_footer_credits', $max_credits, 1, T_('Max footer credits'), $note, 1 );
$Form->end_fieldset();


$Form->end_form( array(
	array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
	array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );

?>

<script type="text/javascript">
	jQuery( '#ajax_form_enabled' ).click( function()
	{
		if( jQuery( '#ajax_form_enabled' ).attr( "checked" ) == false )
		{
			jQuery( '#cache_enabled' ).attr( "checked", false );
		}
	} );
	jQuery( '#cache_enabled' ).click( function()
	{
		if( jQuery( '#cache_enabled' ).attr( "checked" ) )
		{
			jQuery( '#ajax_form_enabled' ).attr( "checked", true );
		}
	} );
</script>
<?php

/*
 * $Log: _coll_advanced.form.php,v $
 */
?>