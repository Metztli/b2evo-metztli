<?php
/**
 * This file implements the UI view for the Session stats.
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
 * @version $Id: _stats_sessions.view.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_MAIN_INIT') ) die( 'Please, do not access this page directly.' );

global $blog, $admin_url, $rsc_url;

/**
 * View funcs
 */
require_once dirname(__FILE__).'/_stats_view.funcs.php';

// Create result set:
$SQL = new SQL();
$CountSQL = new SQL();

$SQL->SELECT( 'SQL_NO_CACHE user_login, COUNT( sess_ID ) AS nb_sessions, MAX( sess_lastseen ) AS sess_lastseen' );
$SQL->FROM( 'T_sessions LEFT JOIN T_users ON sess_user_ID = user_ID' );
$SQL->GROUP_BY( 'sess_user_ID' );

$CountSQL->SELECT( 'SQL_NO_CACHE COUNT( DISTINCT(sess_user_ID) )' );
$CountSQL->FROM( 'T_sessions' );

$Results = new Results( $SQL->get(), 'usess_', '-D', 20, $CountSQL->get() );

$Results->title = T_('Recent sessions');

$Results->cols[] = array(
						'th' => T_('User login'),
						'order' => 'user_login',
						'td' => '%stat_session_login( #user_login# )%',
					);

$Results->cols[] = array(
						'th' => T_('Last seen'),
						'order' => 'sess_lastseen',
						'default_dir' => 'D',
						'td_class' => 'timestamp',
						'td' => '%mysql2localedatetime_spans( #sess_lastseen# )%',
 					);

$Results->cols[] = array(
						'th' => T_('Session count'),
						'order' => 'nb_sessions',
						'td_class' => 'center',
						'total_class' => 'right',
						'td' => '%stat_user_sessions( #user_login#, #nb_sessions# )%',
					);

// Display results:
$Results->display();

/*
 * $Log: _stats_sessions.view.php,v $
 */
?>