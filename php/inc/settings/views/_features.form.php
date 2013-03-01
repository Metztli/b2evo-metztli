<?php
/**
 * This file implements the UI view for the general settings.
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
 *
 * Halton STEWART grants Francois PLANQUE the right to license
 * Halton STEWART's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package admin
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author halton: Halton STEWART
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _features.form.php 9 2011-10-24 22:32:00Z fplanque $
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

global $baseurl;


$Form = new Form( NULL, 'feats_checkchanges' );

$Form->begin_form( 'fform', T_('Global Features') );

$Form->add_crumb( 'globalsettings' );
$Form->hidden( 'ctrl', 'features' );
$Form->hidden( 'action', 'update' );

$Form->begin_fieldset( T_('Online Help').get_manual_link('online help'));
	$Form->checkbox_input( 'webhelp_enabled', $Settings->get('webhelp_enabled'), T_('Online Help links'), array( 'note' => T_('Online help links provide context sensitive help to certain features.' ) ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('After each new post or comment...').get_manual_link('after_each_post_settings') );
	$Form->radio_input( 'outbound_notifications_mode', $Settings->get('outbound_notifications_mode'),
		array(
			array( 'value'=>'off', 'label'=>T_('Off'), 'note'=>T_('No notification about your new content will be sent out.') ),
			array( 'value'=>'immediate', 'label'=>T_('Immediate'), 'note'=>T_('This is guaranteed to work but may create an annoying delay after each post or comment publication.') ),
			array( 'value'=>'cron', 'label'=>T_('Asynchronous'), 'note'=>T_('Recommended if you have your scheduled jobs properly set up.') )
		),
		T_('Outbound pings & email notifications'),
		array( 'lines' => true ) );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Blog by email').get_manual_link('blog_by_email') );

	$Form->checkbox_input( 'eblog_enabled', $Settings->get('eblog_enabled'), T_('Enable Blog by email'),
		array( 'note' => sprintf(T_('Note: This feature needs the php_imap extension (currently %s).' ), extension_loaded( 'imap' ) ? T_('loaded') : T_('NOT loaded')), 'onclick' =>
			'document.getElementById("eblog_section").style.display = (this.checked==true ? "" : "none") ;' ) );

	// fp> TODO: this is IMPOSSIBLE to turn back on when you have no javascript!!! :((
	echo '<div id="eblog_section" style="'.( $Settings->get('eblog_enabled') ? '' : 'display:none' ).'">';

		$Form->select_input_array( 'eblog_method', $Settings->get('eblog_method'), array( 'pop3' => T_('POP3'), 'imap' => T_('IMAP'), ), // TRANS: E-Mail retrieval method
			T_('Retrieval method'), T_('Choose a method to retrieve the emails.') );

		$Form->text_input( 'eblog_server_host', $Settings->get('eblog_server_host'), 40, T_('Mail Server'), T_('Hostname or IP address of your incoming mail server.'), array( 'maxlength' => 255 ) );

		$Form->text_input( 'eblog_server_port', $Settings->get('eblog_server_port'), 5, T_('Port Number'), T_('Port number of your incoming mail server (Defaults: pop3: 110 imap: 143).'), array( 'maxlength' => 6 ) );

		$Form->radio( 'eblog_encrypt', $Settings->get('eblog_encrypt'), array(
																			array( 'none', T_('None'), ),
																			array( 'ssl', T_('SSL'), ),
																			array( 'tls', T_('TLS'), ),
																		), T_('Encryption method') );

		$Form->text_input( 'eblog_username', $Settings->get('eblog_username'), 15, T_('Account Name'), T_('User name for authenticating to your mail server.'), array( 'maxlength' => 255 ) );

		$Form->password_input( 'eblog_password', $Settings->get('eblog_password'),15,T_('Password'), array( 'maxlength' => 255, 'note' => T_('Password for authenticating to your mail server.') ) );

		//TODO: have a drop down list of available blogs and categories
		$Form->text_input( 'eblog_default_category', $Settings->get('eblog_default_category'), 5, T_('Default Category ID'), T_('By default emailed posts will have this category.'), array( 'maxlength' => 6 ) );

		$Form->text_input( 'eblog_subject_prefix', $Settings->get('eblog_subject_prefix'), 15, T_('Subject Prefix'), T_('Email subject must start with this prefix to be imported.'), array( 'maxlength' => 255 ) );

		// eblog test links
		// TODO: "cron/" is supposed to not reside in the server's DocumentRoot, therefor is not necessarily accessible
		$getmailurl = $baseurl.'cron/getmail.php?test=';
		$Form->info_field(
			T_('Perform Server Test'),
			' <a id="eblog_test" target="_blank" href="'.$getmailurl.'1" onclick=\'return pop_up_window( "'.$getmailurl.'1", "getmail" )\'>[ ' . T_('connection') . ' ]</a>'
			.' <a id="eblog_test" target="_blank" href="'.$getmailurl.'2" onclick=\'return pop_up_window( "'.$getmailurl.'2", "getmail" )\'>[ ' . T_('messages') . ' ]</a>'
			.' <a id="eblog_test" target="_blank" href="'.$getmailurl.'3" onclick=\'return pop_up_window( "'.$getmailurl.'3", "getmail" )\'>[ ' . T_('verbose') . ' ]</a>',
			array() );

//		$Form->info_field ('','<a id="eblog_test_email" href="#" onclick=\'return pop_up_window( "' . $htsrv_url . 'getmail.php?test=email", "getmail" )\'>' . T_('Test email') . '</a>',array());
		// special show / hide link
		$Form->info_field('', get_link_showhide( 'eblog_show_more','eblog_section_more', T_('Hide extra options'), T_('Show extra options...') ) );


		// TODO: provide Non-JS functionality
		echo '<div id="eblog_section_more" style="display:none">';

			$Form->checkbox( 'eblog_novalidatecert', $Settings->get('eblog_novalidatecert'), T_('Do not validate certificate'), T_('Do not validate the certificate from the TLS/SSL server. Check this if you are using a self-signed certificate.') );

			$Form->checkbox( 'eblog_add_imgtag', $Settings->get('eblog_add_imgtag'), T_('Add &lt;img&gt; tags'), T_('Display image attachments using &lt;img&gt; tags (instead of creating a link).'));

			$Form->checkbox( 'AutoBR', $Settings->get('AutoBR'), T_('Email/MMS Auto-BR'), T_('Add &lt;BR /&gt; tags to mail/MMS posts.') );

			$Form->text_input( 'eblog_body_terminator', $Settings->get('eblog_body_terminator'), 15, T_('Body Terminator'), T_('Starting from this string, everything will be ignored, including this string.'), array( 'maxlength' => 255 )  );

			$Form->checkbox_input( 'eblog_test_mode', $Settings->get('eblog_test_mode'), T_('Test Mode'), array( 'note' => T_('Check to run Blog by Email in test mode.' ) ) );

			/* tblue> this isn't used/implemented at the moment
			$Form->checkbox_input( 'eblog_phonemail', $Settings->get('eblog_phonemail'), T_('Phone Email *'),
				array( 'note' => 'Some mobile phone email services will send identical subject &amp; content on the same line. If you use such a service, check this option, and indicate a separator string when you compose your message, you\'ll type your subject then the separator string then you type your login:password, then the separator, then content.' ) );

			$Form->text_input( 'eblog_phonemail_separator', $Settings->get('eblog_phonemail_separator'), 15, T_('Phonemail Separator'), '',
												array( 'maxlength' => 255 ) );*/

		echo '</div>';

	echo '</div>';
$Form->end_fieldset();

// fp> TODO: it would be awesome to be able to enable the different APIs individually
// that way you minimalize security/spam risks by enable just what you need.
$Form->begin_fieldset( T_('Remote publishing').get_manual_link('remote_publishing') );
	$Form->checkbox_input( 'general_xmlrpc', $Settings->get('general_xmlrpc'), T_('Enable XML-RPC'), array( 'note' => T_('Enable the Movable Type, MetaWeblog, Blogger and B2 XML-RPC publishing protocols.') ) );
	$Form->text_input( 'xmlrpc_default_title', $Settings->get('xmlrpc_default_title'), 50, T_('Default title'), T_('Default title for items created with a XML-RPC API that doesn\'t send a post title (e. g. the Blogger API).'), array( 'maxlength' => 255 ) );
$Form->end_fieldset();


$Form->begin_fieldset( T_('Hit & session logging').get_manual_link('hit_logging') );

	$Form->checklist( array(
			array( 'log_public_hits', 1, T_('on every public page'), $Settings->get('log_public_hits') ),
			array( 'log_admin_hits', 1, T_('on every admin page'), $Settings->get('log_admin_hits') ) ),
		'log_hits', T_('Log hits') );

	// TODO: draw a warning sign if set to off
	$Form->radio_input( 'auto_prune_stats_mode', $Settings->get('auto_prune_stats_mode'), array(
			array(
				'value'=>'off',
				'label'=>T_('Off'),
				'note'=>T_('Not recommended! Your database will grow very large!'),
				'onclick'=>'jQuery("#auto_prune_stats_container").hide();' ),
			array(
				'value'=>'page',
				'label'=>T_('On every page'),
				'note'=>T_('This is guaranteed to work but uses extra resources with every page displayed.'),
				'onclick'=>'jQuery("#auto_prune_stats_container").show();' ),
			array(
				'value'=>'cron',
				'label'=>T_('With a scheduled job'),
				'note'=>T_('Recommended if you have your scheduled jobs properly set up.'), 'onclick'=>'jQuery("#auto_prune_stats_container").show();' ) ),
		T_('Auto pruning'),
		array( 'note' => T_('Note: Even if you don\'t log hits, you still need to prune sessions!'),
		'lines' => true ) );

	echo '<div id="auto_prune_stats_container">';
	$Form->text_input( 'auto_prune_stats', $Settings->get('auto_prune_stats'), 5, T_('Prune after'), T_('days. How many days of hits & sessions do you want to keep in the database for stats?') );
	echo '</div>';

	if( $Settings->get('auto_prune_stats_mode') == 'off' )
	{ // hide the "days" input field, if mode set to off:
		echo '<script type="text/javascript">jQuery("#auto_prune_stats_container").hide();</script>';
	}

$Form->end_fieldset();

$Form->begin_fieldset( T_('Categories').get_manual_link('categories_global_settings'), array( 'id'=>'categories') );
	$Form->checkbox_input( 'allow_moving_chapters', $Settings->get('allow_moving_chapters'), T_('Allow moving categories'), array( 'note' => T_('Check to allow moving categories accross blogs. (Caution: can break pre-existing permalinks!)' ) ) );
	$Form->radio_input( 'chapter_ordering', $Settings->get('chapter_ordering'), array(
					array( 'value'=>'alpha', 'label'=>T_('Alphabetical') ),
					array( 'value'=>'manual', 'label'=>T_('Manual ') ),
			 ), T_('Ordering of categories') );
$Form->end_fieldset();

$Form->begin_fieldset( T_('Cross posting') );
	$Form->checklist( array(
		array( 'cross_posting', 1, T_('Allow cross-posting posts to several blogs'), $Settings->get('cross_posting'), false, T_('(Extra cats in different blogs)') ),
		array( 'cross_posting_blogs', 1, T_('Allow moving posts between different blogs'), $Settings->get('cross_posting_blogs'), false, T_('(Main cat can move to different blog)') ) ),
		'allow_cross_posting', T_('Cross Posting') );
$Form->end_fieldset();

if( $current_User->check_perm( 'options', 'edit' ) )
{
	$Form->end_form( array(
		array( 'submit', '', T_('Update'), 'SaveButton' ),
		array( 'reset', '', T_('Reset'), 'ResetButton' ),
		) );
}


/*
 * $Log: _features.form.php,v $
 */
?>