<?php
/**
 * This file implements the GeneralSettings class, which handles Name/Value pairs.
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
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 *
 * @version $Id: _generalsettings.class.php 16 2011-10-25 01:34:59Z sam2kb $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

load_class( 'settings/model/_abstractsettings.class.php', 'AbstractSettings' );

/**
 * Class to handle the global settings.
 *
 * @package evocore
 */
class GeneralSettings extends AbstractSettings
{
	/**
	 * The default settings to use, when a setting is not given
	 * in the database.
	 *
	 * @todo Allow overriding from /conf/_config_TEST.php?
	 * @access protected
	 * @var array
	 */
	var $_defaults = array(
		'admin_skin' => 'chicago',

		'antispam_last_update' => '2000-01-01 00:00:00',
		'antispam_threshold_publish' => '-90',
		'antispam_threshold_delete' => '100', // do not delete by default!
		'antispam_block_spam_referers' => '0',	// By default, let spam referers go in silently (just don't log them). This is in case the blacklist is too paranoid (social media, etc.)
		'antispam_report_to_central' => '1',

		'evonet_last_update' => '1196000000',		// just around the time we implemented this ;)
		'evonet_last_attempt' => '1196000000',		// just around the time we implemented this ;)

		'log_public_hits' => '1',
		'log_admin_hits' => '0',
		'log_spam_hits' => '0',
		'auto_prune_stats_mode' => 'page',  // 'page' is the safest mode for average installs (may be "off", "page" or "cron")
		'auto_prune_stats' => '15',         // days (T_hitlog and T_sessions)

		'outbound_notifications_mode' => 'immediate', // 'immediate' is the safest mode for average installs (may be "off", "immediate" or "cron")

		'fm_enable_create_dir' => '1',
		'fm_enable_create_file' => '1',
		'fm_enable_roots_blog' => '1',
		'fm_enable_roots_user' => '1',
		'fm_enable_roots_shared' => '1',
		'fm_enable_roots_skins' => '1',

		'fm_showtypes' => '0',
		'fm_showfsperms' => '0',

		'fm_default_chmod_file' => '664',
		'fm_default_chmod_dir' => '775',

		'newusers_canregister' => '0',
		'newusers_mustvalidate' => '1',
		'newusers_revalidate_emailchg' => '0',
		'newusers_level' => '1',
		'registration_require_gender' => 'optional',
		'registration_ask_locale' => '0',

		'allow_avatars' => 1,

		'regexp_filename' => '^[a-zA-Z0-9\-_. ]+$', // TODO: accept (spaces and) special chars / do full testing on this
		'regexp_dirname' => '^[a-zA-Z0-9\-_]+$', // TODO: accept spaces and special chars / do full testing on this
		'reloadpage_timeout' => '300',
		'smart_hit_count' => '0',
		'time_difference' => '0',
		'timeout_sessions' => '604800',     // seconds (604800 == 7 days)
		'upload_enabled' => '1',
		'upload_maxkb' => '10000',					// 10 MB
		'evocache_foldername' => '.evocache',

		'user_minpwdlen' => '5',
		'js_passwd_hashing' => '1',					// Use JS password hashing by default

		'webhelp_enabled' => '1',

		'allow_moving_chapters' => '0',				// Do not allow moving chapters by default
		'chapter_ordering' => 'alpha',

		'cross_posting' => 0,						// Allow additional categories from other blogs
		'cross_posting_blog' => 0,					// Allow to choose main category from another blog

		'general_cache_enabled' => 0,
		'newblog_cache_enabled' => 0,
		'newblog_cache_enabled_widget' => 0,

		'eblog_enabled' => 0,						// blog by email
		'eblog_method' => 'pop3',					// blog by email
		'eblog_encrypt' => 'none',					// blog by email
		'eblog_server_port' => 110,					// blog by email
		'eblog_default_category' => 1,				// blog by email
		'AutoBR' => 0,								// Used for email blogging. fp> TODO: should be replaced by "email renderers/decoders/cleaners"...
		'eblog_add_imgtag' => 1,					// blog by email
		'eblog_body_terminator' => '___',			// blog by email
		'eblog_subject_prefix' => 'blog:',			// blog by email
		'general_xmlrpc' => 1,
		'xmlrpc_default_title' => '',		//default title for posts created throgh blogger api

		'nickname_editing' => 'edited-user',		// "never" - Never allow; "default-no" - Let users decide, default to "no" for new users; "default-yes" - Let users decide, default to "yes" for new users; "always" - Always allow
		'multiple_sessions' => 'userset_default_no', // multiple sessions settings -- overriden for demo mode in contructor

		// The following back-end settings can't be modified by users:
		'last_invalidation_timestamp' => 0,
	);


	/**
	 * Constructor.
	 *
	 * This loads the general settings and checks db_version.
	 *
	 * It will also turn off error-reporting/halting of the {@link $DB DB object}
	 * temporarily to present a more decent error message if tables do not exist yet.
	 *
	 * Because the {@link $DB DB object} itself creates a connection when it gets
	 * created "Error selecting database" occurs before we can check for it here.
	 */
	function GeneralSettings()
	{
		global $new_db_version, $DB, $demo_mode;

		$save_DB_show_errors = $DB->show_errors;
		$save_DB_halt_on_error = $DB->halt_on_error;
		$DB->halt_on_error = false;
		$DB->show_errors = false;

		// Init through the abstract constructor. This should be the first DB connection.
		parent::AbstractSettings( 'T_settings', array( 'set_name' ), 'set_value', 0 );

		// check DB version:
		if( $this->get( 'db_version' ) != $new_db_version )
		{ // Database is not up to date:
			if( $DB->last_error )
			{
				$error_message = '<p>MySQL error:</p>'.$DB->last_error;
			}
			else
			{
				$error_message = '<p>Database schema is not up to date!</p>'
					.'<p>You have schema version &laquo;'.(integer)$this->get( 'db_version' ).'&raquo;, '
					.'but we would need &laquo;'.(integer)$new_db_version.'&raquo;.</p>';
			}
			global $adminskins_path;
			require $adminskins_path.'conf_error.main.php'; // error & exit
		}

		$DB->halt_on_error = $save_DB_halt_on_error;
		$DB->show_errors = $save_DB_show_errors;


		if( $demo_mode )
		{ // Demo mode requires to allow multiple concurrent sessions:
			$this->_defaults['multiple_sessions'] = 'always';
		}
	}


	/**
	 * Get a 32-byte string that can be used as salt for public keys.
	 *
	 * @return string
	 */
	function get_public_key_salt()
	{
		$public_key_salt = $this->get( 'public_key_salt' );
		if( empty($public_key_salt) )
		{
			$public_key_salt = generate_random_key(32);
			$this->set( 'public_key_salt', $public_key_salt );
			$this->dbupdate();
		}
		return $public_key_salt;
	}


	/**
	 * Get a member param by its name
	 *
	 * @param mixed Name of parameter
	 * @param boolean true to return param's real value
	 * @return mixed Value of parameter
	 */
	function get( $parname, $real_value = false )
	{
		if( $real_value )
		{
			return parent::get( $parname );
		}
		
		switch($parname)
		{
			case 'allow_avatars':
				return ( parent::get( $parname ) && isset($GLOBALS['files_Module']) );
				break;

			case 'upload_enabled':
				return ( parent::get( $parname ) && isset($GLOBALS['files_Module']) );
				break;

			default:
				return parent::get( $parname );
		}
	}

}



/*
 * $Log: _generalsettings.class.php,v $
 */
?>