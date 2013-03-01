<?php
/**
 * This file implements the UI controller for Global Features.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005 by Halton STEWART - {@link http://hstewart.net/}.
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
 *
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author halton: Halton STEWART.
 * @author fplanque: Francois PLANQUE.
 * @author blueyed: Daniel HAHLER.
 *
 * @version $Id: features.ctrl.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


// Check minimum permission:
$current_User->check_perm( 'options', 'view', true );

// Memorize this as the last "tab" used in the Blog Settings:
$UserSettings->set( 'pref_glob_settings_tab', $ctrl );
$UserSettings->dbupdate();

$AdminUI->set_path( 'options', 'features' );

param( 'action', 'string' );

switch( $action )
{
	case 'update':
		// Check that this action request is not a CSRF hacked request:
		$Session->assert_received_crumb( 'globalsettings' );

		// Check permission:
		$current_User->check_perm( 'options', 'edit', true );

		// fp> Restore defaults has been removed because it's extra maintenance work and no real benefit to the user.

		// Online help
		param( 'webhelp_enabled', 'integer', 0 );
		$Settings->set( 'webhelp_enabled', $webhelp_enabled );

		// Outbound pinging:
 		param( 'outbound_notifications_mode', 'string', true );
		$Settings->set( 'outbound_notifications_mode',  get_param('outbound_notifications_mode') );

		// Blog by email
		param( 'eblog_enabled', 'boolean', 0 );
		$Settings->set( 'eblog_enabled', $eblog_enabled );

		param( 'eblog_method', 'string', true );
		$Settings->set( 'eblog_method', strtolower(trim($eblog_method)));

		param( 'eblog_encrypt', 'string', true );
		$Settings->set( 'eblog_encrypt', $eblog_encrypt );

		param( 'eblog_novalidatecert', 'boolean', 0 );
		$Settings->set( 'eblog_novalidatecert', $eblog_novalidatecert );

		param( 'eblog_server_host', 'string', true );
		$Settings->set( 'eblog_server_host', evo_strtolower(trim($eblog_server_host)));

		param( 'eblog_server_port', 'integer', true );
		$Settings->set( 'eblog_server_port', $eblog_server_port );

		param( 'eblog_username', 'string', true );
		$Settings->set( 'eblog_username', trim($eblog_username));

		param( 'eblog_password', 'string', true );
		$Settings->set( 'eblog_password', trim($eblog_password));

		param( 'eblog_default_category', 'integer', true );
		$Settings->set( 'eblog_default_category', $eblog_default_category );

		param( 'eblog_subject_prefix', 'string', true );
		$Settings->set( 'eblog_subject_prefix', trim($eblog_subject_prefix) );

		param( 'AutoBR', 'boolean', 0 );
		$Settings->set( 'AutoBR', $AutoBR );

		param( 'eblog_body_terminator', 'string', true );
		$Settings->set( 'eblog_body_terminator', trim($eblog_body_terminator) );

		param( 'eblog_test_mode', 'boolean', 0 );
		$Settings->set( 'eblog_test_mode', $eblog_test_mode );

		param( 'eblog_add_imgtag', 'boolean', 0 );
		$Settings->set( 'eblog_add_imgtag', $eblog_add_imgtag );

		/* tblue> this isn't used/implemented at the moment
		param( 'eblog_phonemail', 'integer', 0 );
		$Settings->set( 'eblog_phonemail', $eblog_phonemail );

		param( 'eblog_phonemail_separator', 'string', true );
		$Settings->set( 'eblog_phonemail_separator', trim($eblog_phonemail_separator) );*/


		// Hit & Session logs
		$Settings->set( 'log_public_hits', param( 'log_public_hits', 'integer', 0 ) );
		$Settings->set( 'log_admin_hits', param( 'log_admin_hits', 'integer', 0 ) );
		$Settings->set( 'log_spam_hits', param( 'log_spam_hits', 'integer', 0 ) );

		param( 'auto_prune_stats_mode', 'string', true );
		$Settings->set( 'auto_prune_stats_mode',  get_param('auto_prune_stats_mode') );

		// TODO: offer to set-up cron job if mode == 'cron' and to remove cron job if mode != 'cron'

		param( 'auto_prune_stats', 'integer', $Settings->get_default('auto_prune_stats'), false, false, true, false );
		$Settings->set( 'auto_prune_stats', get_param('auto_prune_stats') );


		// Categories:
		$Settings->set( 'allow_moving_chapters', param( 'allow_moving_chapters', 'integer', 0 ) );
		$Settings->set( 'chapter_ordering', param( 'chapter_ordering', 'string', 'alpha' ) );

		$Settings->set( 'cross_posting', param( 'cross_posting', 'integer', 0 ) );
		$Settings->set( 'cross_posting_blogs', param( 'cross_posting_blogs', 'integer', 0 ) );

		//XML-RPC
		$Settings->set( 'general_xmlrpc', param( 'general_xmlrpc', 'integer', 0 ) );

		param( 'xmlrpc_default_title', 'string', true );
		$Settings->set( 'xmlrpc_default_title', trim($xmlrpc_default_title) );

		if( ! $Messages->has_errors() )
		{
			$Settings->dbupdate();
			$Messages->add( T_('Settings updated.'), 'success' );
			// Redirect so that a reload doesn't write to the DB twice:
			header_redirect( '?ctrl=features', 303 ); // Will EXIT
			// We have EXITed already at this point!!
		}
		break;
}


$AdminUI->breadcrumbpath_init();
$AdminUI->breadcrumbpath_add( T_('Global settings'), '?ctrl=settings',
		T_('Global settings are shared between all blogs; see Blog settings for more granular settings.') );
$AdminUI->breadcrumbpath_add( T_('Features'), '?ctrl=features' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

// Begin payload block:
$AdminUI->disp_payload_begin();

// Display VIEW:
$AdminUI->disp_view( 'settings/views/_features.form.php' );

// End payload block:
$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

/*
 * $Log: features.ctrl.php,v $
 */
?>