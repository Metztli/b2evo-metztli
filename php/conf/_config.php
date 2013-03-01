<?php
/**
 * This is b2evolution's main config file, which just includes all the other
 * config files.
 *
 * This file should not be edited. You should edit the sub files instead.
 *
 * See {@link _basic_config.php} for the basic settings.
 *
 * @package conf
 */

if( defined('EVO_CONFIG_LOADED') )
{
	return;
}

// HARD MAINTENANCE !
if( file_exists(dirname(__FILE__).'/maintenance.html') )
{ // Stop execution as soon as possible. This is useful while uploading new app files via FTP.
	header('HTTP/1.0 503 Service Unavailable');
	readfile(dirname(__FILE__).'/maintenance.html');
	die();
}

/**
 * This makes sure the config does not get loaded twice in Windows
 * (when the /conf file is in a path containing uppercase letters as in /Blog/conf).
 */
define( 'EVO_CONFIG_LOADED', true );

// basic settings
if( file_exists(dirname(__FILE__).'/_basic_config.php') )
{	// Use configured base config:
	require_once  dirname(__FILE__).'/_basic_config.php';
}
else
{	// Use default template:
	require_once  dirname(__FILE__).'/_basic_config.template.php';
}

// DEPRECATED -- You can now have a _basic_config.php file that will not be overwritten by new releases
if( file_exists(dirname(__FILE__).'/_config_TEST.php') )
{ // Put testing conf in there (For testing, you can also set $install_password here):
	include_once dirname(__FILE__).'/_config_TEST.php';   	// FOR TESTING / DEVELOPMENT OVERRIDES
}

require_once  dirname(__FILE__).'/_advanced.php';       	// advanced settings
require_once  dirname(__FILE__).'/_locales.php';        	// locale settings
require_once  dirname(__FILE__).'/_formatting.php';     	// formatting settings
require_once  dirname(__FILE__).'/_admin.php';          	// admin settings
require_once  dirname(__FILE__).'/_stats.php';          	// stats/hitlogging settings
require_once  dirname(__FILE__).'/_application.php';    	// application settings
if( file_exists(dirname(__FILE__).'/_overrides_TEST.php') )
{ // Override for testing in there:
	include_once dirname(__FILE__).'/_overrides_TEST.php';	// FOR TESTING / DEVELOPMENT OVERRIDES
}

// Handle debug cookie:
if( $debug == 'pwd' )
{	// Debug *can* be enabled/disabled by cookie:

	// Disabled until we find a reason to enable:
	$debug = 0;

	if( !empty($debug_pwd) )
	{	// We have configured a password that could enable debug mode:
		if( isset($_GET['debug']) )
		{	// We have submitted a ?debug=password
			if( $_GET['debug'] == $debug_pwd )
			{	// Password matches
				$debug = 1;
				setcookie( 'debug', $debug_pwd, 0, $cookie_path, $cookie_domain );
			}
			else
			{	// Password doesn't match: turn off debug mode:
				setcookie( 'debug', '', $cookie_expired, $cookie_path, $cookie_domain );
			}
		}
		elseif( !empty($_COOKIE['debug'])	&& $_COOKIE['debug'] == $debug_pwd )
		{	// We have a cookie with the correct debug password:
			$debug = 1;
		}
	}
}

// STUFF THAT SHOULD BE INITIALIZED (to avoid param injection on badly configured PHP)
$use_db = true;
$use_session = true;

/*
 * $Log: _config.php,v $
 */
?>