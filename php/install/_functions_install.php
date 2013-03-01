<?php
/**
 * This file implements support functions for the installer
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 *
 * @package install
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

/**
 * Open a block
 */
function block_open()
{
	global $block_status;
	if( isset($block_status) && $block_status == 'open' )
	{
		return;
	}
	$block_status = 'open';
	echo '<div class="block1"><div class="block2"><div class="block3">';
}

/**
 * Close a block
 */
function block_close()
{
	global $block_status;
	if( !isset($block_status) || $block_status == 'closed' )
	{
		return;
	}
	$block_status = 'closed';
	echo '</div></div></div>';
}

/**
 * Language selector
 */
function display_locale_selector()
{
	global $locales, $default_locale, $action;

	static $selector_already_displayed = false;

	if( $selector_already_displayed )
	{
		return;
	}
	$selector_already_displayed = true;
	block_open();
	?>
	<h2><?php echo T_('Language / Locale')?></h2>
	<form action="index.php" method="get">
	<?php
	echo '<div class="floatright"><a href="index.php?action=localeinfo&amp;locale='.$default_locale.'">'.T_('More languages').' &raquo;</a></div>';

	locale_flag( $default_locale, 'w16px', 'flag', '', true, /* Do not rely on $baseurl/$rsc_url here: */ '../rsc/flags' );
	echo '<select name="locale" onchange="this.form.submit()">';
	foreach( $locales as $lkey => $lvalue )
	{
		echo '<option';
		if( $default_locale == $lkey ) echo ' selected="selected"';
		echo ' value="'.$lkey.'">';
		echo T_( $lvalue['name'] );
		echo '</option>';
	}
	?>
	</select>
	<noscript>
		<input type="submit" value="<?php echo T_('Select as default language/locale'); ?>" />
	</noscript>
	</form>
	<?php
	block_close();
}

/**
 * Base config recap
 */
function display_base_config_recap()
{
	global $default_locale, $conf_db_user, $conf_db_password, $conf_db_name, $conf_db_host, $db_config, $tableprefix, $baseurl, $admin_email;

	static $base_config_recap_already_displayed = false;

	if( $base_config_recap_already_displayed )
	{
		return;
	}
	$base_config_recap_already_displayed = true;

	block_open();
	?>
	<h2><?php echo T_('Base config recap...')?></h2>

	<p><?php printf( T_('If you don\'t see correct settings here, STOP before going any further, and <a %s>update your base configuration</a>.'), 'href="index.php?action=start&amp;locale='.$default_locale.'"' ) ?></p>

	<?php
	if( !isset($conf_db_user) ) $conf_db_user = $db_config['user'];
	if( !isset($conf_db_password) ) $conf_db_password = $db_config['password'];
	if( !isset($conf_db_name) ) $conf_db_name = $db_config['name'];
	if( !isset($conf_db_host) ) $conf_db_host = isset($db_config['host']) ? $db_config['host'] : 'localhost';

	echo '<pre>',
	T_('MySQL Username').': '.$conf_db_user."\n".
	T_('MySQL Password').': '.(($conf_db_password != 'demopass' ? T_('(Set, but not shown for security reasons)') : 'demopass') )."\n".
	T_('MySQL Database name').': '.$conf_db_name."\n".
	T_('MySQL Host/Server').': '.$conf_db_host."\n".
	T_('MySQL tables prefix').': '.$tableprefix."\n\n".
	T_('Base URL').': '.$baseurl."\n\n".
	T_('Admin email').': '.$admin_email.
	'</pre>';
	block_close();
}


/**
 * Install new DB.
 */
function install_newdb()
{
	global $new_db_version, $admin_url, $random_password;

	/*
	 * -----------------------------------------------------------------------------------
	 * NEW DB: Create a plain new db structure + sample contents
	 * -----------------------------------------------------------------------------------
	 */
	require_once dirname(__FILE__).'/_functions_create.php';

	if( $old_db_version = get_db_version() )
	{
		echo '<p><strong>'.T_('OOPS! It seems b2evolution is already installed!').'</strong></p>';

		if( $old_db_version < $new_db_version )
		{
			echo '<p>'.sprintf( T_('Would you like to <a %s>upgrade your existing installation now</a>?'), 'href="?action=evoupgrade"' ).'</p>';
		}

		return;
	}

	$installer_version = param( 'installer_version', 'integer', 0 );
	if( $installer_version >= 10 )
	{
		$create_sample_contents = param( 'create_sample_contents', 'integer', 0 );
	}
	else
	{	// OLD INSTALLER call. Probably an automated script calling.
		// Let's force the sample contents since they haven't been explicitly disabled
		$create_sample_contents = 1;
	}

	echo '<h2>'.T_('Creating b2evolution tables...').'</h2>';
	flush();
	create_tables();

	echo '<h2>'.T_('Creating minimum default data...').'</h2>';
	flush();
	create_default_data();

	if( $create_sample_contents )
	{
		global $Settings;

		echo '<h2>'.T_('Installing sample contents...').'</h2>';
		flush();

		// We're gonna need some environment in order to create the demo contents...
		load_class( 'settings/model/_generalsettings.class.php', 'GeneralSettings' );
		load_class( 'users/model/_usersettings.class.php', 'UserSettings' );
		/**
		 * @var GeneralSettings
		 */
		$Settings = new GeneralSettings();

		/**
		 * @var UserCache
		 */
		$UserCache = & get_UserCache();
		// Create $current_User object.
		// (Assigning by reference does not work with "global" keyword (PHP 5.2.8))
		$GLOBALS['current_User'] = & $UserCache->get_by_ID( 1 );

		create_demo_contents();
	}

	echo '<h2>'.T_('Installation successful!').'</h2>';

	echo '<p><strong>';
	printf( T_('Now you can <a %s>log in</a> with the following credentials:'), 'href="'.$admin_url.'"' );
	echo '</strong></p>';

	echo '<table>';
	echo '<tr><td>', T_( 'Login' ), ': &nbsp;</td><td><strong><evo:password>admin</evo:password></strong></td></tr>';
	printf( '<tr><td>%s: &nbsp;</td><td><strong><evo:password>%s</evo:password></strong></td></tr>', T_( 'Password' ), $random_password );
	echo '</table>';

	echo '<p>'.T_('Note that password carefully! It is a <em>random</em> password that is given to you when you install b2evolution. If you lose it, you will have to delete the database tables and re-install anew.').'</p>';
}


/**
 * Begin install task.
 * This will offer other display methods in the future
 */
function task_begin( $title )
{
	echo $title;
}


/**
 * End install task.
 * This will offer other display methods in the future
 */
function task_end()
{
	echo "OK.<br />\n";
	flush();
}


function get_db_version()
{
	global $DB;

	$DB->save_error_state();
	$DB->halt_on_error = false;
	$DB->show_errors = false;
	$DB->log_errors = false;

	$r = NULL;

	if( db_col_exists( 'T_settings', 'set_name' ) )
	{ // we have new table format (since 0.9)
		$r = $DB->get_var( 'SELECT set_value FROM T_settings WHERE set_name = "db_version"' );
	}
	else
	{
		$r = $DB->get_var( 'SELECT db_version FROM T_settings' );
	}

	$DB->restore_error_state();

	return $r;
}


/**
 * @return boolean Does a given column name exist in DB?
 */
function db_col_exists( $table, $col_name )
{
	global $DB;

	$col_name = strtolower($col_name);

	$r = false;
	$DB->save_error_state();
	foreach( $DB->get_results('SHOW COLUMNS FROM '.$table) as $row )
	{
		if( strtolower($row->Field) == $col_name )
		{
			$r = true;
			break;
		}
	}
	$DB->restore_error_state();

	return $r;
}


/**
 * Clean up extra quotes in comments
 */
function cleanup_comment_quotes()
{
  global $DB;

	echo "Checking for extra quote escaping in comments... ";
	$query = "SELECT comment_ID, comment_content
							FROM T_comments
						 WHERE comment_content LIKE '%\\\\\\\\\'%'
						 		OR comment_content LIKE '%\\\\\\\\\"%' ";
	/* FP: the above looks overkill, but MySQL is really full of surprises...
					tested on 4.0.14-nt */
	// echo $query;
	$rows = $DB->get_results( $query, ARRAY_A );
	if( $DB->num_rows )
	{
		echo 'Updating '.$DB->num_rows.' comments... ';
		foreach( $rows as $row )
		{
			$query = "UPDATE T_comments
								SET comment_content = ".$DB->quote( stripslashes( $row['comment_content'] ) )."
								WHERE comment_ID = ".$row['comment_ID'];
			// echo '<br />'.$query;
			$DB->query( $query );
		}
	}
	echo "OK.<br />\n";

}


/**
 * Validate install requirements.
 *
 * @return array List of errors, empty array if ok.
 */
function install_validate_requirements()
{
	$errors = array();

	return $errors;
}


/**
 * Insert default settings into T_settings.
 *
 * It only writes those to DB, that get overridden (passed as array), or have
 * no default in {@link _generalsettings.class.php} / {@link GeneralSettings::default}.
 *
 * @param array associative array (settings name => value to use), allows
 *              overriding of defaults
 */
function create_default_settings( $override = array() )
{
	global $DB, $new_db_version, $default_locale, $Group_Users;

	$defaults = array(
		'db_version' => $new_db_version,
		'default_locale' => $default_locale,
		'newusers_grp_ID' => $Group_Users->ID,
		'default_blog_ID' => 1,
		'evocache_foldername' => '_evocache',
	);

	$settings = array_merge( array_keys($defaults), array_keys($override) );
	$settings = array_unique( $settings );
	$insertvalues = array();
	foreach( $settings as $name )
	{
		if( isset($override[$name]) )
		{
			$insertvalues[] = '('.$DB->quote($name).', '.$DB->quote($override[$name]).')';
		}
		else
		{
			$insertvalues[] = '('.$DB->quote($name).', '.$DB->quote($defaults[$name]).')';
		}
	}

	echo 'Creating default settings'.( count($override) ? ' (with '.count($override).' existing values)' : '' ).'... ';
	$DB->query(
		"INSERT INTO T_settings (set_name, set_value)
		VALUES ".implode( ', ', $insertvalues ) );
	echo "OK.<br />\n";
}


/**
 * Install basic skins.
 */
function install_basic_skins()
{
	load_funcs( 'skins/_skin.funcs.php' );

	echo 'Installing default skins... ';

	// Note: Skin #1 will we used by Blog A
	skin_install( 'evopress' );

	// Note: Skin #2 will we used by Blog B
	skin_install( 'evocamp' );

	// Note: Skin #3 will we used by Linkblog
	skin_install( 'miami_blue' );

	// Note: Skin #4 will we used by Photoblog
	skin_install( 'photoblog' );

	skin_install( 'asevo' );
	skin_install( 'custom' );
	skin_install( 'dating_mood' );
	skin_install( 'glossyblue' );
	skin_install( 'intense' );
	skin_install( 'natural_pink' );
	skin_install( 'nifty_corners' );
	skin_install( 'pixelgreen' );
	skin_install( 'pluralism' );
	skin_install( 'terrafirma' );
	skin_install( 'vastitude' );
	skin_install( '_atom' );
	skin_install( '_rss2' );

	echo "OK.<br />\n";
}


/**
 * Install basic plugins.
 *
 * This gets called separately on fresh installs.
 *
 * {@internal
 * NOTE: this won't call the "AfterInstall" method on the plugin nor install its DB schema.
 *       This get done in the plugins controller, on manually installing a plugin.
 *
 * If you change the plugins here, please also adjust {@link InstallUnitTestCase::basic_plugins}.
 * }}
 *
 * @param integer Old DB version, so that only new plugins gets installed
 */
function install_basic_plugins( $old_db_version = 0 )
{
	/**
	 * @var Plugins_admin
	 */
	global $Plugins_admin;

	$Plugins_admin = & get_Plugins_admin();

	// Create global $Plugins instance, which is required during installation of basic plugins,
	// not only for the ones getting installed, but also during e.g. count_regs(), which instantiates
	// each plugin (which may then use (User)Settings in PluginInit (through Plugin::__get)).
	$GLOBALS['Plugins'] = & $Plugins_admin;

	if( $old_db_version < 9100 )
	{
		// Toolbars:
		install_plugin( 'quicktags_plugin' );
		// Renderers:
		install_plugin( 'auto_p_plugin' );
		install_plugin( 'autolinks_plugin' );
		install_plugin( 'texturize_plugin' );

		// SkinTags:
		install_plugin( 'calendar_plugin' );
		install_plugin( 'archives_plugin' );
	}

	if( $old_db_version < 9290 )
	{
		install_plugin( 'smilies_plugin' );
		install_plugin( 'videoplug_plugin' );
	}

	if( $old_db_version < 9330 )
	{ // Upgrade to 1.9-beta
		install_plugin( 'ping_b2evonet_plugin' );
		install_plugin( 'ping_pingomatic_plugin' );
	}

	if( $old_db_version < 9930 )
	{ // Upgrade to 3.1.0
		install_plugin( 'tinymce_plugin' );
	}

	if( $old_db_version < 9940 )
	{ // Upgrade to 3.2.0
		install_plugin( 'twitter_plugin' );
	}
}


/**
 * @return true on success
 */
function install_plugin( $plugin )
{
	/**
	 * @var Plugins_admin
	 */
	global $Plugins_admin;

	echo 'Installing plugin: '.$plugin.'... ';
	$edit_Plugin = & $Plugins_admin->install( $plugin, 'broken' ); // "broken" by default, gets adjusted later
	if( ! is_a( $edit_Plugin, 'Plugin' ) )
	{
		echo $edit_Plugin."<br />\n";
		return false;
	}

	// install_plugin_db_schema_action()

	// Try to enable plugin:
	$enable_return = $edit_Plugin->BeforeEnable();
	if( $enable_return !== true )
	{
		$Plugins_admin->set_Plugin_status( $edit_Plugin, 'disabled' ); // does not unregister it
		echo $enable_return."<br />\n";
		return false;
	}

	$Plugins_admin->set_Plugin_status( $edit_Plugin, 'enabled' );

	echo "OK.<br />\n";
	return true;
}


/**
 * Install basic widgets.
 */
function install_basic_widgets()
{
	/**
	* @var DB
	*/
	global $DB;

	load_funcs( 'widgets/_widgets.funcs.php' );

	$blog_ids = $DB->get_col( 'SELECT blog_ID FROM T_blogs' );
	foreach( $blog_ids as $blog_id )
	{
		echo 'Installing default widgets for blog #'.$blog_id.'... ';
		insert_basic_widgets( $blog_id, true );
		echo "OK.<br />\n";
	}

}



function advanced_properties()
{
	/*
// file_path needs to be case sensitive on unix
// Note: it should be ok on windows too if we take care of updating the db on case renames
ALTER TABLE `T_files` CHANGE `file_path` `file_path` VARCHAR( 255 ) CHARACTER SET latin1 COLLATE latin1_bin NOT NULL
or
ALTER TABLE `T_files` CHANGE `file_path` `file_path` VARCHAR( 255 ) CHARACTER SET utf8 COLLATE utf8_bin NOT NULL
	*/
}


/**
 * Create relations
 *
 * @todo NOT UP TO DATE AT ALL :( -- update field names before activating this
 */
function create_relations()
{
	global $DB;

	echo 'Creating relations... ';

	$DB->query( 'alter table T_coll_user_perms
								add constraint FK_bloguser_blog_ID
											foreign key (bloguser_blog_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_coll_user_perms
								add constraint FK_bloguser_user_ID
											foreign key (bloguser_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_categories
								add constraint FK_cat_blog_ID
											foreign key (cat_blog_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict,
								add constraint FK_cat_parent_ID
											foreign key (cat_parent_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_comments
								add constraint FK_comment_post_ID
											foreign key (comment_post_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_postcats
								add constraint FK_postcat_cat_ID
											foreign key (postcat_cat_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict,
								add constraint FK_postcat_post_ID
											foreign key (postcat_post_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_items__item
								add constraint FK_post_assigned_user_ID
											foreign key (post_assigned_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_lastedit_user_ID
											foreign key (post_lastedit_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_creator_user_ID
											foreign key (post_creator_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_main_cat_ID
											foreign key (post_main_cat_ID)
											references T_categories (cat_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_parent_ID
											foreign key (post_parent_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_pst_ID
											foreign key (post_pst_ID)
											references T_items__status (pst_ID)
											on delete restrict
											on update restrict,
								add constraint FK_post_ptyp_ID
											foreign key (post_ptyp_ID)
											references T_items__type (ptyp_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_links
								add constraint FK_link_creator_user_ID
											foreign key (link_creator_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_lastedit_user_ID
											foreign key (link_lastedit_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_dest_itm_ID
											foreign key (link_dest_itm_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_file_ID
											foreign key (link_file_ID)
											references T_files (file_ID)
											on delete restrict
											on update restrict' );
	$DB->query( 'alter table T_links
								add constraint FK_link_itm_ID
											foreign key (link_itm_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_pluginsettings
	              add constraint FK_pset_plug_ID
	                    foreign key (pset_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_pluginusersettings
	              add constraint FK_puset_plug_ID
	                    foreign key (puset_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_pluginevents
	              add constraint FK_pevt_plug_ID
	                    foreign key (pevt_plug_ID)
	                    references T_plugins (plug_ID)
	                    on delete restrict
	                    on update restrict' );

	$DB->query( 'alter table T_users
								add constraint FK_user_grp_ID
											foreign key (user_grp_ID)
											references T_groups (grp_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_users__usersettings
								add constraint FK_uset_user_ID
											foreign key (uset_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_subscriptions
								add constraint FK_sub_coll_ID
											foreign key (sub_coll_ID)
											references T_blogs (blog_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_subscriptions
								add constraint FK_sub_user_ID
											foreign key (sub_user_ID)
											references T_users (user_ID)
											on delete restrict
											on update restrict' );

	$DB->query( 'alter table T_slug
								add constraint FK_slug_itm_ID
											foreign key (slug_itm_ID)
											references T_items__item (post_ID)
											on delete restrict
											on update restrict' );

	echo "OK.<br />\n";
}


/**
 * Loads the b2evo database scheme.
 *
 * This gets updated through {@link db_delta()} which generates the queries needed to get
 * to this scheme.
 *
 * Please see {@link db_delta()} for things to take care of.
 */
function load_db_schema()
{
	global $schema_queries;
	global $modules, $inc_path;

	global $db_storage_charset, $DB;
	if( empty($db_storage_charset) )
	{	// If no specific charset has been requested for datstorage, use the one of the current connection (optimize for speed - no conversions)
		$db_storage_charset = $DB->connection_charset;
	}
	//pre_dump( 'db_storage_charset', $db_storage_charset );

	// Load modules:
	foreach( $modules as $module )
	{
		echo 'Loading module: '.$module.'/model/_'.$module.'.install.php<br />';
		require_once $inc_path.$module.'/model/_'.$module.'.install.php';
	}

}


/**
 * Install htaccess: Check if it works with the webserver, then install it for real.
 *
 * @return string error message
 */
function install_htaccess( $upgrade = false )
{
	echo '<p>Preparing to install .htaccess ... ';

	$error_message = do_install_htaccess( $upgrade );

	if( $error_message)
	{
		echo 'ERROR!<br/><b>'.$error_message.'</b><br />';
		printf( T_('Everything should still work, but for optimization you should follow <a %s>these instructions</a>.'), 'href="http://manual.b2evolution.net/Tricky_stuff" target="_blank"');
	}
	echo '</p>';
}

/**
 * This does the actual file manipulations for installing .htaccess
 * This will verify that the provided sample.htaccess does not crash apache in a test folder before installing it for real.
 *
 * @param boolean are we upgrading (vs installing)?
 * @return mixed
 */
function do_install_htaccess( $upgrade = false )
{
	global $baseurl;
	global $basepath;

	if( @file_exists($basepath.'.htaccess') )
	{
		if( $upgrade )
		{
			echo T_('Already installed.');
			return ''; // all is well :)
		}
		return T_('You already have a file named .htaccess in your your base url folder.');
	}

	// Make sure we have a sample file to start with:
	if( ! @file_exists($basepath.'sample.htaccess') )
	{
		return 'Can not find file [ sample.htaccess ] in your base url folder.';
	}

	// Try to copy that file to the test folder:
	if( ! @copy( $basepath.'sample.htaccess', $basepath.'install/test/.htaccess' ) )
	{
		return 'Failed to copy files!';
	}

	// Make sure .htaccess does not crash in the test folder:
	load_funcs('_core/_url.funcs.php');
	$info = array();
	if( ! $remote_page = fetch_remote_page( $baseurl.'install/test/', $info ) )
	{
		return $info['error'];
	}
	if( substr( $remote_page, 0, 16 ) != 'Test successful.' )
	{
		return 'install/test/index.html was not found as expected.';
	}

	// Now we consider it's safe, copy .htaccess to its real location:
	if( ! @copy( $basepath.'sample.htaccess', $basepath.'.htaccess' ) )
	{
		return 'Test was successful, but failed to copy .htaccess into baseurl directory!';
	}

	echo 'Install successful.';
	return '';
}


/**
 * Return antispam SQL query.
 * This is obfuscated because some hosting companies prevent uploading PHP files
 * containing "spam" strings.
 *
 * @return string;
 */
function get_antispam_query()
{
	//used base64_encode() for getting this code
	$r = base64_decode('SU5TRVJUIElOVE8gVF9hbnRpc3BhbShhc3BtX3N0cmluZykgVkFMVUVTICgnb25saW5lLWNhc2lubycpLCAoJ3BlbmlzLWVubGFyZ2VtZW50JyksICgnb3JkZXItdmlhZ3JhJyksICgnb3JkZXItcGhlbnRlcm1pbmUnKSwgKCdvcmRlci14ZW5pY2FsJyksICgnb3JkZXItcHJvcGhlY2lhJyksICgnc2V4eS1saW5nZXJpZScpLCAoJy1wb3JuLScpLCAoJy1hZHVsdC0nKSwgKCctdGl0cy0nKSwgKCdidXktcGhlbnRlcm1pbmUnKSwgKCdvcmRlci1jaGVhcC1waWxscycpLCAoJ2J1eS14ZW5hZHJpbmUnKSwJKCd4eHgnKSwgKCdwYXJpcy1oaWx0b24nKSwgKCdwYXJpc2hpbHRvbicpLCAoJ2NhbWdpcmxzJyksICgnYWR1bHQtbW9kZWxzJyk=');
	// pre_dump($r);
	return $r;
}

/*
 * $Log: _functions_install.php,v $
 */
?>