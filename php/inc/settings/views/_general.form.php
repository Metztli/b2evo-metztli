<?php
/**
 * This file implements the UI view for the general settings.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
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
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id: _general.form.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var User
 */
global $current_User;
/**
 * @var GeneralSettings
 */
global $Settings;

global $dispatcher;

global $collections_Module;

$Form = new Form( NULL, 'settings_checkchanges' );
$Form->begin_form( 'fform', T_('General Settings'),
	// enable all form elements on submit (so values get sent):
	array( 'onsubmit'=>'var es=this.elements; for( var i=0; i < es.length; i++ ) { es[i].disabled=false; };' ) );

$Form->add_crumb( 'globalsettings' );
$Form->hidden( 'ctrl', 'gensettings' );
$Form->hidden( 'action', 'update' );

// --------------------------------------------

if( isset($collections_Module) )
{
	$Form->begin_fieldset( T_('Display options').get_manual_link('display_options') );

	$BlogCache = & get_BlogCache();

		$Form->select_input_object( 'default_blog_ID', $Settings->get('default_blog_ID'), $BlogCache, T_('Default blog to display'), array(
				'note' => T_('This blog will be displayed on index.php.').' <a href="'.$dispatcher.'?ctrl=collections&action=new">'.T_('Create new blog').' &raquo;</a>',
				'allow_none' => true,
				'class' => '',
				'loop_object_method' => 'get_maxlen_name',
				'onchange' => '' )  );

	$Form->end_fieldset();
}

// --------------------------------------------

$Form->begin_fieldset( T_('Timeouts') );

	// fp>TODO: enhance UI with a general Form method for Days:Hours:Minutes:Seconds

	$Form->duration_input( 'timeout_sessions', $Settings->get('timeout_sessions'), T_('Session timeout'), 'months', 'seconds', array( 'minutes_step' => 1, 'required'=>true ) );
	// $Form->text_input( 'timeout_sessions', $Settings->get('timeout_sessions'), 9, T_('Session timeout'), T_('seconds. How long can a user stay inactive before automatic logout?'), array( 'required'=>true) );

	// fp>TODO: It may make sense to have a different (smaller) timeout for sessions with no logged user.
	// fp>This might reduce the size of the Sessions table. But this needs to be checked against the hit logging feature.

	$Form->duration_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), T_('Reload-page timeout'), 'minutes', 'seconds', array( 'minutes_step' => 1, 'required' => true ) );
	// $Form->text_input( 'reloadpage_timeout', (int)$Settings->get('reloadpage_timeout'), 5,
	// T_('Reload-page timeout'), T_('Time (in seconds) that must pass before a request to the same URI from the same IP and useragent is considered as a new hit.'), array( 'maxlength'=>5, 'required'=>true ) );

	$Form->checkbox_input( 'smart_hit_count', $Settings->get( 'smart_hit_count' ), T_( 'Smart view counting' ), array( 'note' => T_( 'Check this to count post views only once per session and ignore reloads.' ) ) );

$Form->end_fieldset();

// --------------------------------------------

$Form->begin_fieldset( T_('Caching') );

	$Form->checkbox_input( 'general_cache_enabled', $Settings->get('general_cache_enabled'), T_('Enable general cache'), array( 'note'=>T_('Cache rendered pages that are not controlled by a skin. See Blog Settings for skin output caching.') ) );

	$cache_note = '('.T_( 'See Blog Settings for existing' ).')';
	$Form->checklist( array(
			array( 'newblog_cache_enabled', 1, T_( 'Enable page cache for NEW blogs' ), $Settings->get('newblog_cache_enabled'), false, $cache_note ),
			array( 'newblog_cache_enabled_widget', 1, T_( 'Enable widget cache for NEW blogs' ), $Settings->get('newblog_cache_enabled_widget'), false, $cache_note )
			), 'new_blogs_cahe', T_( 'Enable for new blogs' ) );

$Form->end_fieldset();

// --------------------------------------------

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array( array( 'submit', 'submit', T_('Save !'), 'SaveButton' ),
													array( 'reset', '', T_('Reset'), 'ResetButton' ) ) );
}

/*
 * $Log: _general.form.php,v $
 */
?>