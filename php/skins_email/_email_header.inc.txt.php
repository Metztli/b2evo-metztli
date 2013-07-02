<?php
/**
 * This is included into every email and typically includes a personalized greeting.
 *
 * For more info about email skins, see: http://b2evolution.net/man/themes-templates-skins/email-skins/
 *
 * b2evolution - {@link http://b2evolution.net/}
 * Released under GNU GPL License - {@link http://b2evolution.net/about/license.html}
 * @copyright (c)2003-2013 by Francois Planque - {@link http://fplanque.com/}
 *
 * @version $Id: _email_header.inc.txt.php 3152 2013-03-04 22:01:46Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );


echo T_( 'Hello $login$ !' )."\n\n";
?>