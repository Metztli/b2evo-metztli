<?php
/**
 * Upgrade - This is a LINEAR controller
 *
 * This file is part of b2evolution - {@link http://b2evolution.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2009-2013 by Francois PLANQUE - {@link http://fplanque.net/}
 * Parts of this file are copyright (c)2009 by The Evo Factory - {@link http://www.evofactory.com/}.
 *
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 *
 * {@internal Open Source relicensing agreement:
 * The Evo Factory grants Francois PLANQUE the right to license
 * The Evo Factory's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package maintenance
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author efy-maxim: Evo Factory / Maxim.
 * @author fplanque: Francois Planque.
 *
 * @version $Id: upgrade.ctrl.php 3508 2013-04-19 06:58:02Z yura $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * @var instance of User class
 */
global $current_User;

/**
 * @vars string paths
 */
global $basepath, $upgrade_path, $install_path;

// Check minimum permission:
$current_User->check_perm( 'perm_maintenance', 'upgrade', true );

// Used in the upgrade process
$script_start_time = $servertimenow;

$tab = param( 'tab', 'string', '', true );

// Set options path:
$AdminUI->set_path( 'options', 'misc', 'upgrade'.$tab );

// Get action parameter from request:
param_action();

// Display message if the upgrade config file doesn't exist
check_upgrade_config( true );

$AdminUI->breadcrumbpath_init( false );  // fp> I'm playing with the idea of keeping the current blog in the path here...
$AdminUI->breadcrumbpath_add( T_('System'), '?ctrl=system' );
$AdminUI->breadcrumbpath_add( T_('Maintenance'), '?ctrl=tools' );
$AdminUI->breadcrumbpath_add( T_('Upgrade'), '?ctrl=upgrade' );


// Display <html><head>...</head> section! (Note: should be done early if actions do not redirect)
$AdminUI->disp_html_head();

// Display title, menu, messages, etc. (Note: messages MUST be displayed AFTER the actions)
$AdminUI->disp_body_top();

$AdminUI->disp_payload_begin();

echo '<h2 class="red">WARNING: EXPERIMENTAL FEATURE!</h2>';

echo '<h3>Use for testing only at this point!</h3>';

/**
 * Display payload:
 */
switch( $action )
{
	case 'start':
	default:
		// STEP 1: Check for updates.
		if( $tab == '' )
		{
			$block_item_Widget = new Widget( 'block_item' );
			$block_item_Widget->title = T_('Updates from b2evolution.net');
			$block_item_Widget->disp_template_replaced( 'block_start' );

			// Note: hopefully, the update will have been downloaded in the shutdown function of a previous page (including the login screen)
			// However if we have outdated info, we will load updates here.
			load_funcs( 'dashboard/model/_dashboard.funcs.php' );
			// Let's clear any remaining messages that should already have been displayed before...
			$Messages->clear();
			b2evonet_get_updates( true );

			// Display info & error messages
			echo $Messages->display( NULL, NULL, false, 'action_messages' );


			/**
			 * @var AbstractSettings
			 */
			global $global_Cache;

			// Display the current version info for now. We may remove this in the future.
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

			// Extract available updates:
			$updates = $global_Cache->get( 'updates' );
		}

		// DEBUG:
		// $updates[0]['url'] = 'http://xxx/b2evolution-1.0.0.zip'; // TODO: temporary URL

		$action = 'start';

		break;

	case 'download':
		// STEP 2: DOWNLOAD.

		if( $demo_mode )
		{
			echo('This feature is disabled on the demo server.');
			break;
		}

		$block_item_Widget = new Widget( 'block_item' );
		$block_item_Widget->title = T_('Downloading, unzipping & installing package...');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		$download_url = param( 'upd_url', 'string' );

		$upgrade_name = param( 'upd_name', 'string', '', true );
		$upgrade_file = $upgrade_path.$upgrade_name.'.zip';

		if( $success = prepare_maintenance_dir( $upgrade_path, true ) )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			echo '<p>'.sprintf( T_( 'Downloading package to &laquo;<strong>%s</strong>&raquo;...' ), $upgrade_file ).'</p>';
			evo_flush();

			// Downloading
			$file_contents = fetch_remote_page( $download_url, $info, 1800 );

			if( empty($file_contents) )
			{
				echo '<p style="color:red">'.sprintf( T_( 'Unable to download package from &laquo;%s&raquo;' ), $download_url ).'</p>';
				evo_flush();
			}
			elseif( ! save_to_file( $file_contents, $upgrade_file, 'w' ) )
			{
				echo '<p style="color:red">'.sprintf( T_( 'Unable to create &laquo;%s&raquo file;' ), $upgrade_file ).'</p>';
				evo_flush();

				@unlink( $upgrade_file );
			}
		}

	case 'unzip':
		// STEP 3: UNZIP.

		if( $demo_mode )
		{
			echo('This feature is disabled on the demo server.');
			break;
		}

		if( !isset( $block_item_Widget ) )
		{
			$block_item_Widget = new Widget( 'block_item' );
			$block_item_Widget->title = T_('Unzipping & installing package...');
			$block_item_Widget->disp_template_replaced( 'block_start' );

			$upgrade_name = param( 'upd_name', 'string', '', true );
			$upgrade_file = $upgrade_path.$upgrade_name.'.zip';

			$success = true;
		}

		if( $success )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			echo '<p>'.sprintf( T_( 'Unpacking package to &laquo;<strong>%s</strong>&raquo;...' ), $upgrade_path.$upgrade_name ).'</p>';
			evo_flush();

			// Unpack package
			if( $success = unpack_archive( $upgrade_file, $upgrade_path.$upgrade_name, true ) )
			{
				global $debug;

				$new_version_status = check_version( $upgrade_name );
				if( $debug == 0 && !empty( $new_version_status ) )
				{
					echo '<h4 style="color:red">'.$new_version_status.'</h4>';
					break;
				}
			}
			else
			{
				// Additional check
				@rmdir_r( $upgrade_path.$upgrade_name );
			}
		}

		// Pause a process before upgrading
		$action = 'install';
		$AdminUI->disp_view( 'maintenance/views/_upgrade_continue.form.php' );
		unset( $block_item_Widget );
		break;

	case 'install':
		// STEP 4: INSTALL.

		if( $demo_mode )
		{
			echo('This feature is disabled on the demo server.');
			break;
		}

		if( !isset( $block_item_Widget ) )
		{
			$block_item_Widget = new Widget( 'block_item' );
			$block_item_Widget->title = T_('Installing package...');
			$block_item_Widget->disp_template_replaced( 'block_start' );

			$upgrade_name = param( 'upd_name', 'string', '', true );
			$upgrade_file = $upgrade_path.$upgrade_name.'.zip';

			$success = true;
		}

		// Enable maintenance mode
		if( $success && switch_maintenance_mode( true, 'upgrade', T_( 'System upgrade is in progress. Please reload this page in a few minutes.' ) ) )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			// Verify that all destination files can be overwritten
			echo '<h4>'.T_( 'Verifying that all destination files can be overwritten...' ).'</h4>';
			evo_flush();

			$read_only_list = array();
			verify_overwrite( $upgrade_path.$upgrade_name.'/b2evolution/blogs', no_trailing_slash( $basepath ), 'Verifying', false, $read_only_list );

			if( empty( $read_only_list ) )
			{	// We can do backup files and database

				// Load Backup class (PHP4) and backup all of the folders and files
				load_class( 'maintenance/model/_backup.class.php', 'Backup' );
				$Backup = new Backup();
				$Backup->include_all();

				if( !function_exists('gzopen') )
				{
					$Backup->pack_backup_files = false;
				}

				// Start backup
				if( $success = $Backup->start_backup() )
				{	// We can upgrade files and database

					// Copying new folders and files
					echo '<h4>'.T_( 'Copying new folders and files...' ).'</h4>';
					evo_flush();

					verify_overwrite( $upgrade_path.$upgrade_name.'/b2evolution/blogs', no_trailing_slash( $basepath ), 'Copying', true, $read_only_list );

					// Upgrade database using regular upgrader script
					require_once( $install_path.'/_functions_install.php' );
					require_once( $install_path.'/_functions_evoupgrade.php' );

					echo '<h4>'.T_( 'Upgrading data in existing b2evolution database...' ).'</h4>';
					evo_flush();

					global $DB, $locale, $current_locale, $form_action;

					$action = 'evoupgrade';
					$form_action = 'install/index.php';
					$locale = $current_locale;

					$DB->begin();
					if( $success = upgrade_b2evo_tables() )
					{
						$DB->commit();
					}
					else
					{
						$DB->rollback();
					}
				}
			}
			else
			{
				echo '<p style="color:red">'.T_( '<strong>The following folders and files can\'t be overwritten:</strong>' ).'</p>';
				foreach( $read_only_list as $read_only_file )
				{
					echo $read_only_file.'<br/>';
				}
				$success = false;
			}
		}

		if( $success )
		{ // Remove folders and files after upgrade
			remove_after_upgrade();
		}

		// Disable maintenance mode
		switch_maintenance_mode( false, 'upgrade' );

		if( $success )
		{
			echo '<h4 style="color:green">'.T_( 'Upgrade completed successfully!' ).'</h4>';
		}
		else
		{
			echo '<h4 style="color:red">'.T_( 'Upgrade failed!' ).'</h4>';
		}

		break;

	/****** UPGRADE FROM SVN *****/
	case 'upgrade_svn':
		// SVN STEP 1: EXPORT.

		if( $demo_mode )
		{
			echo('This feature is disabled on the demo server.');
			break;
		}

		$block_item_Widget = new Widget( 'block_item' );
		$block_item_Widget->title = T_('Exporting package from SVN...');
		$block_item_Widget->disp_template_replaced( 'block_start' );

		$svn_url = param( 'svn_url', 'string', '' );
		$svn_folder = param( 'svn_folder', 'string', '/' );
		$svn_user = param( 'svn_user', 'string', false );
		$svn_password = param( 'svn_password', 'string', false );
		$svn_revision = param( 'svn_revision', 'integer' );

		$UserSettings->set( 'svn_upgrade_url', $svn_url );
		$UserSettings->set( 'svn_upgrade_folder', $svn_folder );
		$UserSettings->set( 'svn_upgrade_user', $svn_user );
		$UserSettings->set( 'svn_upgrade_revision', $svn_revision );
		$UserSettings->dbupdate();

		if( empty( $svn_url ) )
		{
			param_check_not_empty( 'svn_url', T_('Please enter the URL of repository') );
			$action = 'start';
			break;
		}

		if( $success = prepare_maintenance_dir( $upgrade_path, true ) )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			load_class('_ext/phpsvnclient/phpsvnclient.php', 'phpsvnclient' );

			$phpsvnclient = new phpsvnclient( $svn_url, $svn_user, $svn_password );

			if( $phpsvnclient->getVersion() < 1 )
			{ // Incorrect version
				echo '<p class="red">'.T_( 'Unable to get a repository version, probably URL of repository is incorrect.' ).'</p>';
				evo_flush();
				$action = 'start';
				break;
			}

			if( $svn_revision > 0 )
			{ // Set revision from request
				if( $phpsvnclient->getVersion() < $svn_revision )
				{ // Incorrect revision number
					echo '<p class="red">'.sprintf( T_( 'Please select a correct revision number. The latest revision is %s.' ), $phpsvnclient->getVersion() ).'</p>';
					evo_flush();
					$action = 'start';
					break;
				}
				else
				{ // Use only correct revision
					$phpsvnclient->setVersion( $svn_revision );
				}
			}

			$repository_version = $phpsvnclient->getVersion();

			$upgrade_name = 'export_svn_'.$repository_version;
			memorize_param( 'upd_name', 'string', '', $upgrade_name );
			$upgrade_folder = $upgrade_path.$upgrade_name;

			if( file_exists( $upgrade_path.$upgrade_name ) )
			{ // Current version already is downloaded
				echo '<p class="green">'.sprintf( T_('Revision %s has already been downloaded. Using: %s'), $repository_version, $upgrade_path.$upgrade_name );
			}
			else
			{ // Download files
				echo '<p>'.sprintf( T_( 'Downloading package to &laquo;<strong>%s</strong>&raquo;...' ), $upgrade_folder );
				evo_flush();

				// Export all files in temp folder for following coping
				$svn_result = $phpsvnclient->checkOut( $svn_folder, $upgrade_folder, false, true );

				echo '</p>';

				if( $svn_result === false )
				{ // Checkout is failed
					echo '<p style="color:red">'.sprintf( T_( 'Unable to download package from &laquo;%s&raquo;' ), $svn_url ).'</p>';
					evo_flush();
					$action = 'start';
					break;
				}
			}
		}

		// Pause a process before upgrading
		$action = 'install_svn';
		$AdminUI->disp_view( 'maintenance/views/_upgrade_continue.form.php' );
		unset( $block_item_Widget );
		break;

	case 'install_svn':
		// SVN STEP 2: INSTALL.

		if( $demo_mode )
		{
			echo('This feature is disabled on the demo server.');
			break;
		}

		if( !isset( $block_item_Widget ) )
		{
			$block_item_Widget = new Widget( 'block_item' );
			$block_item_Widget->title = T_('Installing package from SVN...');
			$block_item_Widget->disp_template_replaced( 'block_start' );

			$upgrade_name = param( 'upd_name', 'string', '', true );

			$success = true;
		}

		// Enable maintenance mode
		if( $success && switch_maintenance_mode( true, 'upgrade', T_( 'System upgrade is in progress. Please reload this page in a few minutes.' ) ) )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			// Verify that all destination files can be overwritten
			echo '<h4>'.T_( 'Verifying that all destination files can be overwritten...' ).'</h4>';
			evo_flush();

			$read_only_list = array();
			verify_overwrite( $upgrade_path.$upgrade_name, no_trailing_slash( $basepath ), 'Verifying', false, $read_only_list );

			if( empty( $read_only_list ) )
			{	// We can do backup files and database

				// Load Backup class (PHP4) and backup all of the folders and files
				load_class( 'maintenance/model/_backup.class.php', 'Backup' );
				$Backup = new Backup();
				$Backup->include_all();

				if( !function_exists('gzopen') )
				{
					$Backup->pack_backup_files = false;
				}

				// Start backup
				if( $success = $Backup->start_backup() )
				{	// We can upgrade files and database

					// Copying new folders and files
					echo '<h4>'.T_( 'Copying new folders and files...' ).'</h4>';
					evo_flush();

					verify_overwrite( $upgrade_path.$upgrade_name, no_trailing_slash( $basepath ), 'Copying', true, $read_only_list );

					// Upgrade database using regular upgrader script
					require_once( $install_path.'/_functions_install.php' );
					require_once( $install_path.'/_functions_evoupgrade.php' );

					echo '<h4>'.T_( 'Upgrading data in existing b2evolution database...' ).'</h4>';
					evo_flush();

					global $DB, $locale, $current_locale, $form_action;

					$action = 'evoupgrade';
					$form_action = 'install/index.php';
					$locale = $current_locale;

					$DB->begin();
					if( $success = upgrade_b2evo_tables() )
					{
						$DB->commit();
					}
					else
					{
						$DB->rollback();
					}
				}
			}
			else
			{
				echo '<p style="color:red">'.T_( '<strong>The following folders and files can\'t be overwritten:</strong>' ).'</p>';
				foreach( $read_only_list as $read_only_file )
				{
					echo $read_only_file.'<br/>';
				}
				$success = false;
			}
		}

		if( $success )
		{ // Remove folders and files after upgrade
			remove_after_upgrade();
		}

		// Disable maintenance mode
		switch_maintenance_mode( false, 'upgrade' );

		if( $success )
		{
			echo '<h4 style="color:green">'.T_( 'Upgrade completed successfully!' ).'</h4>';
		}
		else
		{
			echo '<h4 style="color:red">'.T_( 'Upgrade failed!' ).'</h4>';
		}

		break;

	case 'continue_upgrade':
		// CONTINUE the upgrade process

		if( $demo_mode )
		{
			echo('This feature is disabled on the demo server.');
			break;
		}

		if( !isset( $block_item_Widget ) )
		{
			$block_item_Widget = new Widget( 'block_item' );
			$block_item_Widget->title = T_('Updating package...');
			$block_item_Widget->disp_template_replaced( 'block_start' );

			$success = true;
		}

		// Enable maintenance mode
		if( $success )
		{
			// Set maximum execution time
			set_max_execution_time( 1800 ); // 30 minutes

			// Upgrade database using regular upgrader script
			require_once( $install_path.'/_functions_install.php' );
			require_once( $install_path.'/_functions_evoupgrade.php' );

			echo '<h4>'.T_( 'Upgrading data in existing b2evolution database...' ).'</h4>';
			evo_flush();

			global $DB, $locale, $current_locale, $form_action;

			$action = 'evoupgrade';
			$form_action = 'install/index.php';
			$locale = $current_locale;

			$DB->begin();
			if( $success = upgrade_b2evo_tables() )
			{
				$DB->commit();
			}
			else
			{
				$DB->rollback();
			}
		}

		if( $success )
		{ // Remove folders and files after upgrade
			remove_after_upgrade();
		}

		// Disable maintenance mode
		switch_maintenance_mode( false, 'upgrade' );

		if( $success )
		{
			echo '<h4 style="color:green">'.T_( 'Upgrade completed successfully!' ).'</h4>';
		}
		else
		{
			echo '<h4 style="color:red">'.T_( 'Upgrade failed!' ).'</h4>';
		}

		break;
}

if( isset( $block_item_Widget ) )
{
	$block_item_Widget->disp_template_replaced( 'block_end' );
}

switch( $tab )
{
	case 'svn':
		$AdminUI->disp_view( 'maintenance/views/_upgrade_svn.form.php' );
		break;

	default:
		$AdminUI->disp_view( 'maintenance/views/_upgrade.form.php' );
		break;
}

$AdminUI->disp_payload_end();

// Display body bottom, debug info and close </html>:
$AdminUI->disp_global_footer();

?>