<?php
/**
 * This is the init file for the session module.
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
 *
 * @version $Id: _sessions.init.php 9 2011-10-24 22:32:00Z fplanque $
 */
if( !defined('EVO_CONFIG_LOADED') ) die( 'Please, do not access this page directly.' );


/**
 * Aliases for table names:
 *
 * (You should not need to change them.
 *  If you want to have multiple b2evo installations in a single database you should
 *  change {@link $tableprefix} in _basic_config.php)
 */
$db_config['aliases']['T_basedomains'] = $tableprefix.'basedomains';
$db_config['aliases']['T_hitlog'] = $tableprefix.'hitlog';
$db_config['aliases']['T_track__keyphrase'] = $tableprefix.'track__keyphrase';
$db_config['aliases']['T_sessions'] = $tableprefix.'sessions';
$db_config['aliases']['T_track__goal'] = $tableprefix.'track__goal';
$db_config['aliases']['T_track__goalhit'] = $tableprefix.'track__goalhit';


/**
 * Controller mappings.
 *
 * For each controller name, we associate a controller file to be found in /inc/ .
 * The advantage of this indirection is that it is easy to reorganize the controllers into
 * subdirectories by modules. It is also easy to deactivate some controllers if you don't
 * want to provide this functionality on a given installation.
 *
 * Note: while the controller mappings might more or less follow the menu structure, we do not merge
 * the two tables since we could, at any time, decide to make a skin with a different menu structure.
 * The controllers however would most likely remain the same.
 *
 * @global array
 */
$ctrl_mappings['stats'] = 'sessions/stats.ctrl.php';
$ctrl_mappings['goals'] = 'sessions/goals.ctrl.php';


/**
 * Get the GoalCache
 *
 * @return GoalCache
 */
function & get_GoalCache()
{
	global $GoalCache;

	if( ! isset( $GoalCache ) )
	{	// Cache doesn't exist yet:
		$GoalCache = new DataObjectCache( 'Goal', false, 'T_track__goal', 'goal_', 'goal_ID', 'goal_name', 'goal_name' ); // COPY (FUNC)
	}

	return $GoalCache;
}


/**
 * sessions_Module definition
 */
class sessions_Module extends Module
{
	/**
	 * Do the initializations. Called from in _main.inc.php.
	 * This is typically where classes matching DB tables for this module are registered/loaded.
	 *
	 * Note: this should only load/register things that are going to be needed application wide,
	 * for example: for constructing menus.
	 * Anything that is needed only in a specific controller should be loaded only there.
	 * Anything that is needed only in a specific view should be loaded only there.
	 */
	function init()
	{
	}

	/**
	 * Build the evobar menu
	 */
	function build_evobar_menu()
	{
		/**
		 * @var Menu
		 */
		global $topleft_Menu;
		global $current_User;
		global $admin_url;
		global $Blog;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( !empty($Blog) && $current_User->check_perm( 'stats', 'list' ) )
		{	// Permission to view stats for user's blogs:
			$entries = array();
			$entries['stats_sep'] = array(
				'separator' => true,
			);
			$entries['stats'] = array(
				'text' => T_('Blog stats').'&hellip;',
				'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$Blog->ID,
			);

			$topleft_Menu->add_menu_entries( 'blog', $entries );
		}

		if( $current_User->check_perm( 'stats', 'view' ) )
		{	// We have permission to view all stats

			// TODO: this is hackish and would require a proper function call
			$topleft_Menu->_menus['entries']['tools']['disabled'] = false;

			// TODO: this is hackish and would require a proper function call
			if( ! empty($topleft_Menu->_menus['entries']['tools']['entries']) )
			{	// There are already entries aboce, insert a separator:
				$topleft_Menu->add_menu_entries( 'tools', array(
						'stats_sep' => array(
								'separator' => true,
							),
					)
				);
			}

			$entries = array(
				'stats' => array(
						'text' => T_('Global Stats').'&hellip;',
						'href' => $admin_url.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog=0',
					 ),
				'sessions' => array(
						'text' => T_('User sessions').'&hellip;',
						'href' => $admin_url.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0',
					 ),
				'goals' => array(
						'text' => T_('Goals').'&hellip;',
						'href' => $admin_url.'?ctrl=goals',
					 ),
				);

			$topleft_Menu->add_menu_entries( 'tools', $entries );
		}
	}


	/**
	 * Builds the 1st half of the menu. This is the one with the most important features
	 */
	function build_menu_1()
	{
		global $blog, $dispatcher;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'stats', 'list' ) )
		{	// Permission to view stats for user's blogs:
			$AdminUI->add_menu_entries(
					NULL, // root
					array(
						'stats' => array(
							'text' => T_('Stats'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=global',
							'entries' => array(
								'summary' => array(
									'text' => T_('Hit summary'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog,
									'entries' => array(
										'global' => array(
											'text' => T_('Global hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=global&amp;blog='.$blog ),
										'browser' => array(
											'text' => T_('Browser hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=browser&amp;blog='.$blog ),
										'robot' => array(
											'text' => T_('Robot hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=robot&amp;blog='.$blog ),
										'feed' => array(
											'text' => T_('RSS/Atom hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=summary&amp;tab3=feed&amp;blog='.$blog ),
										),
									),
								),
							),
						)
					);

			$AdminUI->add_menu_entries( 'stats', array(
								'refsearches' => array(
									'text' => T_('Search B-hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog,
									'entries' => array(
										'hits' => array(
											'text' => T_('Search hits'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=hits&amp;blog='.$blog ),
										'keywords' => array(
											'text' => T_('Keywords'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=keywords&amp;blog='.$blog ),
										'topengines' => array(
											'text' => T_('Top engines'),
											'href' => $dispatcher.'?ctrl=stats&amp;tab=refsearches&amp;tab3=topengines&amp;blog='.$blog ),
										),
									 ),
								'referers' => array(
									'text' => T_('Referered B-hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=referers&amp;blog='.$blog ),
								'other' => array(
									'text' => T_('Direct B-hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=other&amp;blog='.$blog ),
								'domains' => array(
									'text' => T_('Referring domains'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=domains&amp;blog='.$blog ),
							)
						);
		}

		if( $blog == 0 && $current_User->check_perm( 'stats', 'view' ) )
		{	// Viewing aggregate + Permission to view stats for ALL blogs:
			$AdminUI->add_menu_entries(
					'stats',
					array(
						'goals' => array(
							'text' => T_('Goals'),
							'href' => $dispatcher.'?ctrl=goals',
							'entries' => array(
								'goals' => array(
									'text' => T_('Goals'),
									'href' => $dispatcher.'?ctrl=goals'
									),
								'hits' => array(
									'text' => T_('Goal hits'),
									'href' => $dispatcher.'?ctrl=stats&amp;tab=goals&amp;tab3=hits&amp;blog=0'
									),
								'stats' => array(
									'text' => T_('Stats'),
									'href' => $dispatcher.'?ctrl=goals&amp;tab3=stats'
									),
								),
							),
						)
				);
		}
	}

	/**
	 * Builds the 3rd half of the menu. This is the one with the configuration features
	 *
	 * At some point this might be displayed differently than the 1st half.
	 */
	function build_menu_3()
	{

		global $blog, $dispatcher, $ctrl;
		/**
		 * @var User
		 */
		global $current_User;
		global $Blog;
		/**
		 * @var AdminUI_general
		 */
		global $AdminUI;

		if( !$current_User->check_perm( 'admin', 'normal' ) )
		{
			return;
		}

		if( $current_User->check_perm( 'stats', 'view' ) )
		{	// Viewing aggregate + Permission to view stats for ALL blogs:
			$sessions_menu = array(
				'sessions' => array(
					'text' => T_('User sessions'),
					'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0',
					'entries' => array(
						'login' => array(
							'text' => T_('Users'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=login&amp;blog=0'
							),
						'sessid' => array(
							'text' => T_('Sessions'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=sessid&amp;blog=0'
							),
						'hits' => array(
							'text' => T_('Hits'),
							'href' => $dispatcher.'?ctrl=stats&amp;tab=sessions&amp;tab3=hits&amp;blog=0'
							),
						),
					),
			 	);
			// insert at 2nd position:
			if( ! $AdminUI->insert_menu_entries_after( 'users', $sessions_menu, 0 )	)
			{
				$AdminUI->add_menu_entries( 'user', $sessions_menu );
			}
		}
	}
}

$sessions_Module = new sessions_Module();


/*
 * $Log: _sessions.init.php,v $
 */
?>