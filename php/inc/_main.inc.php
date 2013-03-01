<?php
/**
 * This file initializes everything BUT the blog!
 *
 * It is useful when you want to do very customized templates!
 * It is also called by more complete initializers.
 *
 * This file is part of the evoCore framework - {@link http://evocore.net/}
 * See also {@link http://sourceforge.net/projects/evocms/}.
 *
 * @copyright (c)2003-2011 by Francois Planque - {@link http://fplanque.com/}
 * Parts of this file are copyright (c)2004-2006 by Daniel HAHLER - {@link http://thequod.de/contact}.
 * Parts of this file are copyright (c)2005-2006 by PROGIDISTRI - {@link http://progidistri.com/}.
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
 * Matt FOLLETT grants Francois PLANQUE the right to license
 * Matt FOLLETT's contributions to this file and the b2evolution project
 * under any OSI approved OSS license (http://www.opensource.org/licenses/).
 * }}
 *
 * @package evocore
 *
 * {@internal Below is a list of authors who have contributed to design/coding of this file: }}
 * @author fplanque: Francois PLANQUE
 * @author blueyed: Daniel HAHLER
 * @author mfollett: Matt FOLLETT
 * @author mbruneau: Marc BRUNEAU / PROGIDISTRI
 *
 * @version $Id: _main.inc.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


// In case of incomplete config folder:
if( !isset($use_db) ) $use_db = true;
if( !isset($use_session) ) $use_session = true;
if( !isset($use_hacks) ) $use_hacks = false;


if( defined( 'EVO_MAIN_INIT' ) )
{	/*
	 * Prevent double loading since require_once won't work in all situations
	 * on windows when some subfolders have caps :(
	 * (Check it out on static page generation)
	 */
	return;
}
define( 'EVO_MAIN_INIT', true );


// Initialize the most basic stuff
require dirname(__FILE__).'/_init_base.inc.php';


if( $use_db )
{
	// Initialize DB connection
	require dirname(__FILE__).'/_init_db.inc.php';


	// Let the modules load/register what they need:
	$Timer->resume('init modules');
	modules_call_method( 'init' );
	$Timer->pause( 'init modules' );


	// Initialize Plugins
	// At this point, the first hook is "SessionLoaded"
	// The dnsbl_antispam plugin is an example that uses this to check the user's IP against a list of DNS blacklists.
	load_class( 'plugins/model/_plugins.class.php', 'Plugins' );
	/**
	 * @global Plugins The Plugin management object
	 */
	$Plugins = new Plugins();


	// Initialize WWW HIT
	if( ! $is_cli )
	{
		require dirname(__FILE__).'/_init_hit.inc.php';
	}
}

// Load hacks file if it exists (DEPRECATED):
if( $use_hacks && file_exists($conf_path.'hacks.php') )
{
	$Timer->resume( 'hacks.php' );
	include_once $conf_path.'hacks.php';
	$Timer->pause( 'hacks.php' );
}


/*
 * $Log: _main.inc.php,v $
 */
?>